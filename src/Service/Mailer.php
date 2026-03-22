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

        $headers    = [ 'Content-Type: text/plain; charset=UTF-8' ];
        $attachments = $this->resolve_attachments( $attachment_id );

        return wp_mail( sanitize_email( $to ), $subject, $body, $headers, $attachments );
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

        $headers = [ 'Content-Type: text/plain; charset=UTF-8' ];

        return wp_mail( sanitize_email( $to ), $subject, $body, $headers );
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
     * Risolve allegato in path valido e consentito.
     *
     * @return array<int, string>
     */
    private function resolve_attachments( ?int $attachment_id ): array {
        if ( ! $attachment_id ) {
            return [];
        }

        $path = get_attached_file( $attachment_id );
        if ( ! $path || ! file_exists( $path ) ) {
            return [];
        }

        $mime_types = Settings::get()['allowed_mime_types'];
        $mime = get_post_mime_type( $attachment_id );
        if ( ! is_array( $mime_types ) || ! in_array( $mime, $mime_types, true ) ) {
            return [];
        }

        return [ $path ];
    }
}
