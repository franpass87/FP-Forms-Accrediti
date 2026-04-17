<?php
declare(strict_types=1);

namespace FP\FormsAccrediti\Admin;

use FP\FormsAccrediti\Domain\RequestRepository;
use FP\FormsAccrediti\Security\Permissions;
use FP\FormsAccrediti\Service\ApplicantEmailResolver;
use FP\FormsAccrediti\Settings\Settings;

/**
 * Tool di backfill richieste accredito da submission FP Forms esistenti.
 *
 * Utile quando nuove versioni del plugin cambiano la logica di intercettazione
 * hook e le submission pregresse non hanno generato la richiesta accredito.
 *
 * Operazione idempotente: RequestRepository::create_pending_request dedup
 * su submission_id, quindi eseguire più volte non crea duplicati.
 */
final class BackfillController {

    private RequestRepository $repository;

    private ApplicantEmailResolver $email_resolver;

    public function __construct() {
        $this->repository     = new RequestRepository();
        $this->email_resolver = new ApplicantEmailResolver();
    }

    /**
     * Handler admin_post per eseguire il backfill.
     *
     * Parametri POST:
     * - _wpnonce: obbligatorio (fp_forms_accrediti_backfill_requests).
     * - form_id:  0 per tutti i form abilitati, altrimenti ID specifico.
     *
     * Redirect in pagina settings con conteggi in query string.
     */
    public function handle_backfill(): void {
        if ( ! Permissions::can_manage_accrediti() ) {
            wp_die( esc_html__( 'Permessi insufficienti.', 'fp-forms-accrediti' ) );
        }

        check_admin_referer( 'fp_forms_accrediti_backfill_requests' );

        if ( ! Settings::is_enabled() ) {
            $this->redirect_with_error( 'module_disabled' );
        }

        if ( ! class_exists( '\\FPForms\\Plugin' ) ) {
            $this->redirect_with_error( 'fpforms_missing' );
        }

        $requested_form_id = isset( $_POST['form_id'] ) ? absint( (string) $_POST['form_id'] ) : 0;

        $result = $this->run_backfill( $requested_form_id );

        wp_safe_redirect(
            add_query_arg(
                [
                    'page'                => 'fp-forms-accrediti-settings',
                    'backfill_done'       => 1,
                    'backfill_scanned'    => $result['scanned'],
                    'backfill_created'    => $result['created'],
                    'backfill_skipped'    => $result['skipped_existing'],
                    'backfill_no_email'   => $result['skipped_no_email'],
                    'backfill_errors'     => $result['errors'],
                    'backfill_forms'      => $result['forms_processed'],
                ],
                admin_url( 'admin.php' )
            )
        );
        exit;
    }

    /**
     * Esegue il backfill in batch su tutti i form abilitati (o solo uno).
     *
     * @param int $only_form_id Se > 0 processa solo quel form, altrimenti tutti gli abilitati.
     * @return array{scanned:int,created:int,skipped_existing:int,skipped_no_email:int,errors:int,forms_processed:int}
     */
    private function run_backfill( int $only_form_id ): array {
        $settings    = Settings::get();
        $form_configs = is_array( $settings['form_configs'] ?? null ) ? $settings['form_configs'] : [];

        $result = [
            'scanned'          => 0,
            'created'          => 0,
            'skipped_existing' => 0,
            'skipped_no_email' => 0,
            'errors'           => 0,
            'forms_processed'  => 0,
        ];

        try {
            $plugin    = \FPForms\Plugin::instance();
        } catch ( \Throwable $e ) {
            $result['errors']++;
            return $result;
        }

        if ( ! isset( $plugin->submissions ) || ! is_object( $plugin->submissions ) ) {
            $result['errors']++;
            return $result;
        }

        foreach ( $form_configs as $form_key => $config ) {
            if ( ! is_array( $config ) || empty( $config['enabled'] ) ) {
                continue;
            }

            $form_id = absint( (string) $form_key );
            if ( $form_id <= 0 ) {
                continue;
            }

            if ( $only_form_id > 0 && $form_id !== $only_form_id ) {
                continue;
            }

            $email_field = isset( $config['email_field'] ) ? sanitize_key( (string) $config['email_field'] ) : '';

            $result['forms_processed']++;
            $this->process_form( $plugin, $form_id, $email_field, $result );
        }

        return $result;
    }

    /**
     * Processa tutte le submission di un form (paginato) e crea le richieste mancanti.
     *
     * @param object              $plugin      Istanza FP Forms (per accedere a ->submissions).
     * @param int                 $form_id     ID form.
     * @param string              $email_field Slug campo email (da config).
     * @param array<string,int>   $result      Riferimento ai contatori aggregati.
     */
    private function process_form( $plugin, int $form_id, string $email_field, array &$result ): void {
        $limit  = 100;
        $offset = 0;

        do {
            $submissions = $plugin->submissions->get_submissions(
                $form_id,
                [
                    'limit'  => $limit,
                    'offset' => $offset,
                    'order'  => 'ASC',
                ]
            );

            if ( ! is_array( $submissions ) || $submissions === [] ) {
                return;
            }

            foreach ( $submissions as $submission ) {
                $result['scanned']++;

                $submission_id = isset( $submission->id ) ? absint( (string) $submission->id ) : 0;
                if ( $submission_id <= 0 ) {
                    $result['errors']++;
                    continue;
                }

                if ( $this->repository->find_by_submission_id( $submission_id ) ) {
                    $result['skipped_existing']++;
                    continue;
                }

                $data = $this->extract_submission_data( $submission );
                if ( $data === [] ) {
                    $result['errors']++;
                    continue;
                }

                $email = $this->email_resolver->resolve( $form_id, $email_field, $data );
                if ( ! is_email( $email ) ) {
                    $result['skipped_no_email']++;
                    continue;
                }

                try {
                    $request_id = $this->repository->create_pending_request( $submission_id, $form_id, $email );
                } catch ( \Throwable $e ) {
                    $result['errors']++;
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        error_log( 'FP Forms Accrediti backfill: create_pending_request failed - ' . $e->getMessage() );
                    }
                    continue;
                }

                if ( $request_id ) {
                    $result['created']++;
                } else {
                    $result['errors']++;
                }
            }

            $offset += $limit;
            $has_more = count( $submissions ) === $limit;
        } while ( $has_more );
    }

    /**
     * Estrae e decodifica i dati submission. FP Forms ritorna `data` come JSON string
     * quando si usa get_submissions() (batch) mentre get_submission() lo decodifica.
     *
     * @param object $submission Riga submission FP Forms.
     * @return array<string, mixed>
     */
    private function extract_submission_data( $submission ): array {
        if ( ! is_object( $submission ) || ! isset( $submission->data ) ) {
            return [];
        }

        $raw = $submission->data;

        if ( is_array( $raw ) ) {
            return $raw;
        }

        if ( ! is_string( $raw ) || $raw === '' ) {
            return [];
        }

        $decoded = json_decode( $raw, true );
        if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $decoded ) ) {
            return [];
        }

        return $decoded;
    }

    /**
     * Redirect rapido con codice errore in query string.
     */
    private function redirect_with_error( string $error_code ): void {
        wp_safe_redirect(
            add_query_arg(
                [
                    'page'           => 'fp-forms-accrediti-settings',
                    'backfill_error' => $error_code,
                ],
                admin_url( 'admin.php' )
            )
        );
        exit;
    }
}
