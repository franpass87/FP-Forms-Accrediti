<?php
declare(strict_types=1);

namespace FP\FormsAccrediti\Integration;

use FP\FormsAccrediti\Domain\RequestRepository;
use FP\FormsAccrediti\Settings\Settings;

/**
 * Integrazione hook con FP Forms.
 */
final class FpFormsHooks {

    private RequestRepository $repository;

    public function __construct() {
        $this->repository = new RequestRepository();
    }

    /**
     * Registra hook di integrazione.
     */
    public function register(): void {
        add_action( 'fp_forms_after_save_submission', [ $this, 'on_after_save_submission' ], 10, 3 );
    }

    /**
     * Crea richiesta accredito pending alla nuova submission.
     */
    public function on_after_save_submission( int $submission_id, int $form_id, array $data ): void {
        if ( ! Settings::is_enabled() ) {
            return;
        }

        $form_config = Settings::get_form_config( $form_id );
        if ( ! $form_config ) {
            return;
        }

        $email = $this->resolve_applicant_email( $form_id, $form_config, $data );
        if ( ! is_email( $email ) ) {
            return;
        }

        $request_id = $this->repository->create_pending_request( $submission_id, $form_id, $email );
        if ( ! $request_id ) {
            return;
        }

        do_action(
            'fp_tracking_event',
            'accrediti_request_created',
            [
                'request_id'    => (int) $request_id,
                'submission_id' => $submission_id,
                'form_id'       => $form_id,
                'source_plugin' => 'fp-forms-accrediti',
                'event_id'      => 'fp_acc_req_' . (int) $request_id . '_' . time(),
            ]
        );
    }

    /**
     * Risolve email candidato: slug in impostazioni, chiave dati che contiene «email», primo campo tipo email nel form.
     *
     * @param int                  $form_id     ID form FP Forms.
     * @param array<string, mixed> $form_config Config da Settings::get_form_config (enabled già verificato).
     * @param array<string, mixed> $data        Dati submission sanitizzati.
     */
    private function resolve_applicant_email( int $form_id, array $form_config, array $data ): string {
        $email_field = $form_config['email_field'] ?? '';
        if ( $email_field !== '' ) {
            $raw = $data[ $email_field ] ?? null;
            if ( is_scalar( $raw ) && is_email( (string) $raw ) ) {
                return sanitize_email( (string) $raw );
            }
        }

        foreach ( $data as $key => $value ) {
            if ( ! is_scalar( $value ) ) {
                continue;
            }

            $string_value = (string) $value;
            if ( stripos( (string) $key, 'email' ) !== false && is_email( $string_value ) ) {
                return sanitize_email( $string_value );
            }
        }

        $from_schema = $this->resolve_email_from_form_schema( $form_id, $data );
        if ( $from_schema !== '' ) {
            return $from_schema;
        }

        return '';
    }

    /**
     * Primo campo di tipo «email» nel builder FP Forms con valore valido in $data.
     *
     * @param array<string, mixed> $data Dati submission.
     */
    private function resolve_email_from_form_schema( int $form_id, array $data ): string {
        if ( ! class_exists( '\FPForms\Plugin' ) ) {
            return '';
        }

        $plugin = \FPForms\Plugin::instance();
        if ( ! $plugin->forms || ! method_exists( $plugin->forms, 'get_fields' ) ) {
            return '';
        }

        $fields = $plugin->forms->get_fields( $form_id );
        if ( ! is_array( $fields ) ) {
            return '';
        }

        foreach ( $fields as $field ) {
            if ( ! is_array( $field ) ) {
                continue;
            }
            if ( ( $field['type'] ?? '' ) !== 'email' ) {
                continue;
            }
            $name = isset( $field['name'] ) ? (string) $field['name'] : '';
            if ( $name === '' ) {
                continue;
            }
            $raw = $data[ $name ] ?? null;
            if ( is_scalar( $raw ) && is_email( (string) $raw ) ) {
                return sanitize_email( (string) $raw );
            }
        }

        return '';
    }
}
