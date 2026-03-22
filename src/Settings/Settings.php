<?php
declare(strict_types=1);

namespace FP\FormsAccrediti\Settings;

/**
 * Accesso centralizzato alle impostazioni add-on.
 */
final class Settings {

    /**
     * Restituisce impostazioni sanitizzate.
     */
    public static function get(): array {
        $defaults = [
            'enabled' => true,
            'form_configs' => [],
            'allowed_mime_types' => [ 'application/pdf' ],
            'operator_capability' => 'manage_fp_forms_accrediti',
            'email_templates' => [
                'approval_subject' => __( 'La tua richiesta accredito è stata approvata - {site_name}', 'fp-forms-accrediti' ),
                'approval_body' => __( "Gentile candidato,\n\nla tua richiesta di accredito per {form_title} è stata approvata.\n\nIn allegato trovi il documento ufficiale.\n\n{decision_message}\n\nCordiali saluti,\n{site_name}", 'fp-forms-accrediti' ),
                'rejection_subject' => __( 'Esito richiesta accredito - {site_name}', 'fp-forms-accrediti' ),
                'rejection_body' => __( "Gentile candidato,\n\npurtroppo la tua richiesta di accredito per {form_title} non è stata approvata.\n\n{decision_message}\n\nPer ulteriori informazioni puoi contattarci.\n\nCordiali saluti,\n{site_name}", 'fp-forms-accrediti' ),
            ],
        ];

        $settings = get_option( 'fp_forms_accrediti_settings', [] );
        if ( ! is_array( $settings ) ) {
            $settings = [];
        }

        $settings = wp_parse_args( $settings, $defaults );
        $settings['enabled'] = ! empty( $settings['enabled'] );
        $settings['operator_capability'] = sanitize_key( (string) $settings['operator_capability'] ) ?: 'manage_fp_forms_accrediti';

        if ( ! is_array( $settings['form_configs'] ) ) {
            $settings['form_configs'] = [];
        }

        if ( ! is_array( $settings['allowed_mime_types'] ) ) {
            $settings['allowed_mime_types'] = [ 'application/pdf' ];
        }

        if ( ! is_array( $settings['email_templates'] ) ) {
            $settings['email_templates'] = $defaults['email_templates'];
        } else {
            $settings['email_templates'] = wp_parse_args( $settings['email_templates'], $defaults['email_templates'] );
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
}
