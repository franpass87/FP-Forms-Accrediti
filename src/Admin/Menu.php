<?php
declare(strict_types=1);

namespace FP\FormsAccrediti\Admin;

use FP\FormsAccrediti\Security\Permissions;

/**
 * Registra menu e pagine admin add-on accrediti.
 */
final class Menu {

    private RequestsController $controller;

    public function __construct() {
        $this->controller = new RequestsController();
    }

    /**
     * Registra hook admin.
     */
    public function register(): void {
        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_action( 'admin_post_fp_forms_accrediti_save_settings', [ $this->controller, 'handle_save_settings' ] );
        add_action( 'admin_post_fp_forms_accrediti_decide_request', [ $this->controller, 'handle_decision' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /**
     * Crea submenu sotto FP Forms.
     */
    public function register_menu(): void {
        add_submenu_page(
            'fp-forms',
            __( 'Richieste Accrediti', 'fp-forms-accrediti' ),
            __( 'Accrediti', 'fp-forms-accrediti' ),
            'manage_fp_forms',
            'fp-forms-accrediti-requests',
            [ $this->controller, 'render_requests_page' ]
        );

        add_submenu_page(
            'fp-forms',
            __( 'Impostazioni Accrediti', 'fp-forms-accrediti' ),
            __( 'Accrediti Settings', 'fp-forms-accrediti' ),
            'manage_fp_forms',
            'fp-forms-accrediti-settings',
            [ $this->controller, 'render_settings_page' ]
        );
    }

    /**
     * Carica CSS/JS pagine accrediti.
     */
    public function enqueue_assets( string $hook ): void {
        if ( strpos( $hook, 'fp-forms-accrediti' ) === false ) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_style(
            'fp-forms-accrediti-admin',
            FP_FORMS_ACCREDITI_PLUGIN_URL . 'assets/css/admin.css',
            [],
            FP_FORMS_ACCREDITI_VERSION
        );

        wp_enqueue_script(
            'fp-forms-accrediti-admin',
            FP_FORMS_ACCREDITI_PLUGIN_URL . 'assets/js/admin.js',
            [ 'jquery' ],
            FP_FORMS_ACCREDITI_VERSION,
            true
        );
    }
}
