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

    /**
     * Campioni diagnostici raccolti durante il backfill (max 3) per permettere
     * il rendering di un dettaglio in UI quando l'estrazione dati fallisce.
     *
     * @var array<int, array{id:int,type:string,sample:string}>
     */
    private array $diagnostic_samples = [];

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

        delete_transient( 'fp_forms_accrediti_backfill_samples' );
        $this->diagnostic_samples = [];

        $result = $this->run_backfill( $requested_form_id );

        if ( $this->diagnostic_samples !== [] ) {
            set_transient( 'fp_forms_accrediti_backfill_samples', $this->diagnostic_samples, 10 * MINUTE_IN_SECONDS );
        }

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

                $data = $this->extract_submission_data( $submission, $plugin );
                if ( $data === [] ) {
                    $result['errors']++;
                    $this->capture_diagnostic_sample( $submission_id, $submission );
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
     * Estrae e decodifica i dati submission.
     *
     * FP Forms normalmente ritorna `data` come JSON string (get_submissions batch).
     * In installazioni reali possono comparire varianti: JSON già array, serializzazione
     * PHP legacy, stringa con slash escapati da magic quotes o da Helper::safe_json_encode
     * su input già serializzati. Fallback a cascata + ultima chance: chiamare
     * get_submission($id) che in FP Forms 1.6+ prova a decodificare lato core.
     *
     * @param object $submission Riga submission FP Forms.
     * @param object $plugin     Istanza FPForms\Plugin per fallback get_submission().
     * @return array<string, mixed>
     */
    private function extract_submission_data( $submission, $plugin ): array {
        if ( ! is_object( $submission ) || ! isset( $submission->data ) ) {
            return [];
        }

        $raw = $submission->data;

        if ( is_array( $raw ) ) {
            return $raw;
        }

        if ( ! is_string( $raw ) || $raw === '' ) {
            return $this->fallback_via_get_submission( $submission, $plugin );
        }

        $decoded = json_decode( $raw, true );
        if ( is_array( $decoded ) ) {
            return $decoded;
        }

        $unslashed = wp_unslash( $raw );
        if ( is_string( $unslashed ) && $unslashed !== $raw ) {
            $decoded = json_decode( $unslashed, true );
            if ( is_array( $decoded ) ) {
                return $decoded;
            }
        }

        if ( function_exists( 'maybe_unserialize' ) ) {
            $maybe = @maybe_unserialize( $raw );
            if ( is_array( $maybe ) ) {
                return $maybe;
            }
        }

        return $this->fallback_via_get_submission( $submission, $plugin );
    }

    /**
     * Ultimo fallback: chiedi a FP Forms la submission già decodificata.
     *
     * @param object $submission Riga grezza (per leggere ->id).
     * @param object $plugin     Istanza FPForms\Plugin.
     * @return array<string, mixed>
     */
    private function fallback_via_get_submission( $submission, $plugin ): array {
        if ( ! isset( $submission->id ) ) {
            return [];
        }

        $submission_id = absint( (string) $submission->id );
        if ( $submission_id <= 0 ) {
            return [];
        }

        if ( ! isset( $plugin->submissions ) || ! is_object( $plugin->submissions ) || ! method_exists( $plugin->submissions, 'get_submission' ) ) {
            return [];
        }

        try {
            $fetched = $plugin->submissions->get_submission( $submission_id );
        } catch ( \Throwable $e ) {
            return [];
        }

        if ( ! is_object( $fetched ) || ! isset( $fetched->data ) ) {
            return [];
        }

        if ( is_array( $fetched->data ) ) {
            return $fetched->data;
        }

        if ( is_string( $fetched->data ) && $fetched->data !== '' ) {
            $decoded = json_decode( $fetched->data, true );
            if ( is_array( $decoded ) ) {
                return $decoded;
            }
        }

        return [];
    }

    /**
     * Memorizza fino a 3 campioni di `data` quando l'estrazione fallisce,
     * per permettere la diagnosi lato UI in assenza di log.
     *
     * @param int    $submission_id ID submission.
     * @param object $submission    Riga submission FP Forms.
     */
    private function capture_diagnostic_sample( int $submission_id, $submission ): void {
        if ( count( $this->diagnostic_samples ) >= 3 ) {
            return;
        }

        $raw  = $submission->data ?? null;
        $type = gettype( $raw );

        if ( is_string( $raw ) ) {
            $sample = substr( $raw, 0, 300 );
        } elseif ( is_array( $raw ) || is_object( $raw ) ) {
            $sample = substr( (string) wp_json_encode( $raw ), 0, 300 );
        } elseif ( $raw === null ) {
            $sample = '(null)';
        } else {
            $sample = (string) $raw;
        }

        $this->diagnostic_samples[] = [
            'id'     => $submission_id,
            'type'   => $type,
            'sample' => $sample,
        ];
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
