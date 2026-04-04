<?php
declare(strict_types=1);

namespace FP\FormsAccrediti\Settings;

/**
 * Accesso centralizzato alle impostazioni add-on.
 */
final class Settings {

    /**
     * Template email predefiniti (oggetto e corpo) inviati al candidato.
     *
     * @return array{
     *     approval_subject: string,
     *     approval_body: string,
     *     rejection_subject: string,
     *     rejection_body: string
     * }
     */
    public static function default_email_templates(): array {
        return [
            'approval_subject' => __( 'Accredito approvato — {site_name}', 'fp-forms-accrediti' ),
            'approval_body'    => __(
                "Gentile candidato,\n\n" .
                "siamo lieti di comunicarti che la tua richiesta di accredito per «{form_title}» è stata approvata.\n\n" .
                "In allegato trovi il documento ufficiale. Ti invitiamo a conservarlo per eventuali controlli d’accesso.\n\n" .
                "Messaggio dello staff (se presente):\n{decision_message}\n\n" .
                "Per informazioni puoi visitare {site_url} o rispondere a questa e-mail.\n\n" .
                "Cordiali saluti,\nIl team di {site_name}",
                'fp-forms-accrediti'
            ),
            'rejection_subject' => __( 'Aggiornamento sulla tua richiesta di accredito — {site_name}', 'fp-forms-accrediti' ),
            'rejection_body'     => __(
                "Gentile candidato,\n\n" .
                "in riferimento alla tua richiesta di accredito per «{form_title}», siamo spiacenti di comunicarti che in questa fase non è stata accolta.\n\n" .
                "Motivazione o note dello staff (se presenti):\n{decision_message}\n\n" .
                "Se desideri chiarimenti o ritieni che si tratti di un errore, puoi contattarci rispondendo a questa e-mail o tramite i recapiti su {site_url}.\n\n" .
                "Cordiali saluti,\nIl team di {site_name}",
                'fp-forms-accrediti'
            ),
        ];
    }

    /**
     * Unisce i template salvati con i predefiniti: stringhe vuote diventano il testo preimpostato.
     *
     * @param array<string, string> $stored
     * @return array<string, string>
     */
    public static function normalize_email_templates( array $stored ): array {
        $defaults = self::default_email_templates();
        $merged   = wp_parse_args( $stored, $defaults );
        foreach ( $defaults as $key => $default_value ) {
            if ( trim( (string) ( $merged[ $key ] ?? '' ) ) === '' ) {
                $merged[ $key ] = $default_value;
            }
        }
        return $merged;
    }

    /**
     * Restituisce impostazioni sanitizzate.
     */
    public static function get(): array {
        $defaults = [
            'enabled' => true,
            'form_configs' => [],
            'allowed_mime_types' => [ 'application/pdf' ],
            'default_approval_attachment_id' => 0,
            'operator_capability' => 'manage_fp_forms_accrediti',
            'email_templates' => self::default_email_templates(),
        ];

        $settings = get_option( 'fp_forms_accrediti_settings', [] );
        if ( ! is_array( $settings ) ) {
            $settings = [];
        }

        $settings = wp_parse_args( $settings, $defaults );
        $settings['enabled'] = ! empty( $settings['enabled'] );
        $settings['operator_capability'] = sanitize_key( (string) $settings['operator_capability'] ) ?: 'manage_fp_forms_accrediti';
        $settings['default_approval_attachment_id'] = max( 0, (int) ( $settings['default_approval_attachment_id'] ?? 0 ) );

        if ( ! is_array( $settings['form_configs'] ) ) {
            $settings['form_configs'] = [];
        }

        if ( ! is_array( $settings['allowed_mime_types'] ) ) {
            $settings['allowed_mime_types'] = [ 'application/pdf' ];
        }

        if ( ! is_array( $settings['email_templates'] ) ) {
            $settings['email_templates'] = self::default_email_templates();
        } else {
            $settings['email_templates'] = self::normalize_email_templates( $settings['email_templates'] );
        }

        return $settings;
    }

    /**
     * Restituisce se modulo globalmente attivo.
     */
    public static function is_enabled(): bool {
        $settings = self::get();
        return ! empty( $settings['enabled'] );
    }

    /**
     * Ritorna configurazione form se abilitata.
     */
    public static function get_form_config( int $form_id ): ?array {
        $settings = self::get();
        $key      = (string) $form_id;

        if ( ! isset( $settings['form_configs'][ $key ] ) || ! is_array( $settings['form_configs'][ $key ] ) ) {
            return null;
        }

        $config = $settings['form_configs'][ $key ];
        $enabled = ! empty( $config['enabled'] );
        if ( ! $enabled ) {
            return null;
        }

        return [
            'enabled' => true,
            'email_field' => isset( $config['email_field'] ) ? sanitize_key( (string) $config['email_field'] ) : '',
        ];
    }

    /**
     * Restituisce l'ID allegato predefinito per email di approvazione (0 = non impostato).
     */
    public static function get_default_approval_attachment_id(): int {
        return max( 0, (int) ( self::get()['default_approval_attachment_id'] ?? 0 ) );
    }
}
