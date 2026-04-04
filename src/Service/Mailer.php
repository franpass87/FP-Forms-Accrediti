<?php
declare(strict_types=1);

namespace FP\FormsAccrediti\Service;

use FP\FormsAccrediti\Settings\Settings;

/**
 * Servizio invio email decisione accredito.
 */
final class Mailer {

    /**
     * Invia email di approvazione.
     *
     * @param array<string, string> $context Chiavi: applicant_email, form_title, decision_message (e opzionali site_name, date, time).
     */
    public function send_approval_email( string $to, string $message, ?int $attachment_id, array $context = [] ): bool {
        $templates  = Settings::get()['email_templates'];
        $body_raw   = (string) $templates['approval_body'];
        $ctx        = array_merge( $this->default_context( $to, $message ), $context );
        $subject    = $this->replace_tags( (string) $templates['approval_subject'], $ctx );
        $body       = $this->replace_tags( $body_raw, $ctx );
        if ( $message !== '' && strpos( $body_raw, '{decision_message}' ) === false ) {
            $body .= "\n\n" . $message;
        }

        $headers     = [ 'Content-Type: text/html; charset=UTF-8' ];
        $attachments = $this->resolve_attachments( $attachment_id );
        $html        = $this->plain_templates_to_branded_html( $body );

        return wp_mail( sanitize_email( $to ), $subject, $html, $headers, $attachments );
    }

    /**
     * Invia email di rifiuto.
     *
     * @param array<string, string> $context Chiavi: applicant_email, form_title, decision_message (e opzionali site_name, date, time).
     */
    public function send_rejection_email( string $to, string $message, array $context = [] ): bool {
        $templates = Settings::get()['email_templates'];
        $body_raw  = (string) $templates['rejection_body'];
        $ctx       = array_merge( $this->default_context( $to, $message ), $context );
        $subject   = $this->replace_tags( (string) $templates['rejection_subject'], $ctx );
        $body      = $this->replace_tags( $body_raw, $ctx );
        if ( $message !== '' && strpos( $body_raw, '{decision_message}' ) === false ) {
            $body .= "\n\n" . $message;
        }

        $headers = [ 'Content-Type: text/html; charset=UTF-8' ];
        $html    = $this->plain_templates_to_branded_html( $body );

        return wp_mail( sanitize_email( $to ), $subject, $html, $headers );
    }

    /**
     * Converte il testo del template (con newline) in HTML e applica il layout FP Mail SMTP se disponibile.
     */
    private function plain_templates_to_branded_html( string $plain ): string {
        $plain = trim( $plain );
        if ( $plain === '' ) {
            $plain = ' ';
        }
        $escaped     = esc_html( $plain );
        $with_breaks = nl2br( $escaped, false );
        $fragment    = '<div class="fp-forms-accrediti-email-body" style="font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif;font-size:15px;line-height:1.6;color:#334155;">' . $with_breaks . '</div>';

        if ( function_exists( 'fp_fpmail_brand_html' ) ) {
            return fp_fpmail_brand_html( $fragment );
        }

        return '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="margin:0;padding:16px;background:#f8fafc;">' . $fragment . '</body></html>';
    }

    /**
     * Contesto di default per i placeholder.
     *
     * @return array<string, string>
     */
    private function default_context( string $applicant_email, string $decision_message ): array {
        return [
            'applicant_email'   => $applicant_email,
            'decision_message'  => $decision_message,
            'form_title'        => '',
            'site_name'         => get_bloginfo( 'name' ),
            'site_url'          => home_url(),
            'date'              => date_i18n( get_option( 'date_format' ) ),
            'time'              => date_i18n( get_option( 'time_format' ) ),
        ];
    }

    /**
     * Sostituisce i tag nel testo.
     *
     * @param array<string, string> $context
     */
    private function replace_tags( string $text, array $context ): string {
        foreach ( $context as $key => $value ) {
            $text = str_replace( '{' . $key . '}', (string) $value, $text );
        }
        return $text;
    }

    /**
     * Verifica se l'attachment è un file consentito per accredito (esiste, MIME in whitelist).
     */
    public function is_valid_acrediti_attachment( int $attachment_id ): bool {
        if ( $attachment_id <= 0 ) {
            return false;
        }

        $path = get_attached_file( $attachment_id );
        if ( ! $path || ! file_exists( $path ) ) {
            return false;
        }

        $mime_types = Settings::get()['allowed_mime_types'];
        $mime       = get_post_mime_type( $attachment_id );
        return is_array( $mime_types ) && in_array( $mime, $mime_types, true );
    }

    /**
     * Risolve allegato in path valido e consentito.
     *
     * @return array<int, string>
     */
    private function resolve_attachments( ?int $attachment_id ): array {
        if ( ! $attachment_id || $attachment_id <= 0 ) {
            return [];
        }

        if ( ! $this->is_valid_acrediti_attachment( $attachment_id ) ) {
            return [];
        }

        $path = get_attached_file( $attachment_id );
        return $path ? [ $path ] : [];
    }
}
