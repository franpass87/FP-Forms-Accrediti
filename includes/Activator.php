<?php
declare(strict_types=1);

namespace FP\FormsAccrediti;

use FP\FormsAccrediti\Database\Schema;

/**
 * Gestisce attivazione add-on.
 */
final class Activator {

    /**
     * Esegue routine di attivazione.
     */
    public static function activate(): void {
        if ( ! class_exists( '\FPForms\Plugin' ) ) {
            deactivate_plugins( FP_FORMS_ACCREDITI_PLUGIN_BASENAME );
            wp_die( esc_html__( 'FP Forms Accrediti richiede FP Forms attivo prima dell\'attivazione.', 'fp-forms-accrediti' ) );
        }

        Schema::create_tables();
        self::ensure_capabilities();
        self::set_default_options();
    }

    /**
     * Aggiunge capability custom all'amministratore.
     */
    private static function ensure_capabilities(): void {
        $role = get_role( 'administrator' );
        if ( $role ) {
            $role->add_cap( 'manage_fp_forms_accrediti' );
        }
    }

    /**
     * Inizializza opzioni add-on.
     */
    private static function set_default_options(): void {
        if ( get_option( 'fp_forms_accrediti_settings', null ) !== null ) {
            return;
        }

        add_option(
            'fp_forms_accrediti_settings',
            [
                'enabled' => true,
                'form_configs' => [],
                'allowed_mime_types' => [ 'application/pdf' ],
                'email_templates' => [
                    'approval_subject' => __( 'Richiesta accredito approvata', 'fp-forms-accrediti' ),
                    'approval_body' => __( "La tua richiesta e' stata approvata. In allegato trovi l'accredito.", 'fp-forms-accrediti' ),
                    'rejection_subject' => __( 'Richiesta accredito non approvata', 'fp-forms-accrediti' ),
                    'rejection_body' => __( "La tua richiesta non e' stata approvata.", 'fp-forms-accrediti' ),
                ],
            ]
        );
    }
}
