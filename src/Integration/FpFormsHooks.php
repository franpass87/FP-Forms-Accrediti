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

        $email = $this->resolve_applicant_email( $form_config, $data );
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
                'request_id'     => (int) $request_id,
                'submission_id'  => $submission_id,
                'form_id'        => $form_id,
                'source_plugin'  => 'fp-forms-accrediti',
            ]
        );
    }

    /**
     * Risolve email candidato da mapping o fallback.
     */
    private function resolve_applicant_email( array $form_config, array $data ): string {
        $email_field = $form_config['email_field'] ?? '';
        if ( $email_field !== '' && isset( $data[ $email_field ] ) && is_email( $data[ $email_field ] ) ) {
            return sanitize_email( (string) $data[ $email_field ] );
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

        return '';
    }
}
