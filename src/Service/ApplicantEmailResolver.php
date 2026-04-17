<?php
declare(strict_types=1);

namespace FP\FormsAccrediti\Service;

/**
 * Risolve l'email del candidato da una submission FP Forms.
 *
 * Incapsula la cascata di fallback usata sia dal listener live
 * (FpFormsHooks::on_submission_saved) sia dal backfill manuale
 * (Admin\BackfillController) per avere un unico punto di verità.
 */
final class ApplicantEmailResolver {

    /**
     * Risolve l'email applicant per un dato form/submission.
     *
     * Cascata:
     * 1. Slug campo email configurato in Accrediti Settings ($email_field).
     * 2. Qualunque chiave in $data che contenga la sottostringa «email»
     *    (tipico dei campi FP Forms con slug timestampato come email_1776015900684).
     * 3. Primo campo di tipo «email» nello schema form FP Forms con valore valido.
     *
     * @param int                  $form_id     ID form FP Forms.
     * @param string               $email_field Slug campo email dalla config (può essere vuoto).
     * @param array<string, mixed> $data        Dati submission (chiave => valore).
     * @return string Email sanitizzata o stringa vuota se nessuna trovata.
     */
    public function resolve( int $form_id, string $email_field, array $data ): string {
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

        return $this->resolve_from_form_schema( $form_id, $data );
    }

    /**
     * Cerca il primo campo di tipo «email» nello schema FP Forms con valore valido in $data.
     *
     * @param int                  $form_id ID form FP Forms.
     * @param array<string, mixed> $data    Dati submission.
     */
    private function resolve_from_form_schema( int $form_id, array $data ): string {
        if ( ! class_exists( '\\FPForms\\Plugin' ) ) {
            return '';
        }

        try {
            $plugin = \FPForms\Plugin::instance();
        } catch ( \Throwable $e ) {
            return '';
        }

        if ( ! isset( $plugin->forms ) || ! is_object( $plugin->forms ) || ! method_exists( $plugin->forms, 'get_fields' ) ) {
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
