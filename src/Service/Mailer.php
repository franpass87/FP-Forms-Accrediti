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
     */
    public function send_approval_email( string $to, string $message, ?int $attachment_id ): bool {
        $templates = Settings::get()['email_templates'];
        $subject   = (string) $templates['approval_subject'];
        $body      = (string) $templates['approval_body'];
        if ( $message !== '' ) {
            $body .= "\n\n" . $message;
        }

        $headers = [ 'Content-Type: text/plain; charset=UTF-8' ];
        $attachments = $this->resolve_attachments( $attachment_id );

        return wp_mail( sanitize_email( $to ), $subject, $body, $headers, $attachments );
    }

    /**
     * Invia email di rifiuto.
     */
    public function send_rejection_email( string $to, string $message ): bool {
        $templates = Settings::get()['email_templates'];
        $subject   = (string) $templates['rejection_subject'];
        $body      = (string) $templates['rejection_body'];
        if ( $message !== '' ) {
            $body .= "\n\n" . $message;
        }

        $headers = [ 'Content-Type: text/plain; charset=UTF-8' ];

        return wp_mail( sanitize_email( $to ), $subject, $body, $headers );
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
