<?php
declare(strict_types=1);

namespace FP\FormsAccrediti\Admin;

use FP\FormsAccrediti\Security\Permissions;

/**
 * Registra menu e pagine admin add-on accrediti.
 */
final class Menu {

    private RequestsController $controller;

    private BackfillController $backfill_controller;

    public function __construct() {
        $this->controller          = new RequestsController();
        $this->backfill_controller = new BackfillController();
    }

    /**
     * Registra hook admin.
     * Priorità 20: dopo FP Forms (10) che crea il menu padre 'fp-forms'.
     */
    public function register(): void {
        add_action( 'admin_menu', [ $this, 'register_menu' ], 20 );
        add_action( 'admin_post_fp_forms_accrediti_save_settings', [ $this->controller, 'handle_save_settings' ] );
        add_action( 'admin_post_fp_forms_accrediti_decide_request', [ $this->controller, 'handle_decision' ] );
        add_action( 'admin_post_fp_forms_accrediti_backfill_requests', [ $this->backfill_controller, 'handle_backfill' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_filter( 'admin_body_class', [ $this, 'filter_admin_body_class' ] );
    }

    /**
     * Aggiunge classe body sulle schermate add-on (spaziatura rispetto alle notice WP).
     *
     * @param string $classes Classi esistenti.
     */
    public function filter_admin_body_class( string $classes ): string {
        $page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
        if ( $page !== '' && strpos( $page, 'fp-forms-accrediti' ) === 0 ) {
            return trim( $classes . ' fpfa-admin-shell fp-forms-accrediti-admin' );
        }
        return $classes;
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
        $page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
        $is_our_page = ( strpos( $hook, 'fp-forms-accrediti' ) !== false )
            || ( $page !== '' && strpos( $page, 'fp-forms-accrediti' ) !== false );
        if ( ! $is_our_page ) {
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

        wp_localize_script(
            'fp-forms-accrediti-admin',
            'fpFormsAccreditiAdmin',
            [
                'i18n' => [
                    'selectRequestAttachment' => __( 'Seleziona allegato accredito', 'fp-forms-accrediti' ),
                    'useThisFile'           => __( 'Usa questo file', 'fp-forms-accrediti' ),
                    'selectDefaultPdf'      => __( 'Seleziona PDF predefinito per approvazioni', 'fp-forms-accrediti' ),
                    'useAsDefault'          => __( 'Usa come predefinito', 'fp-forms-accrediti' ),
                    'currentFile'           => __( 'Attuale: %1$s (ID %2$d)', 'fp-forms-accrediti' ),
                ],
            ]
        );
    }
}
