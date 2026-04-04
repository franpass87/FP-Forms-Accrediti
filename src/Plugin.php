<?php
declare(strict_types=1);

namespace FP\FormsAccrediti;

use FP\FormsAccrediti\Admin\Menu;
use FP\FormsAccrediti\Infrastructure\DependencyGuard;
use FP\FormsAccrediti\Integration\FpFormsHooks;
use FP\FormsAccrediti\Settings\Settings;

/**
 * Bootstrap principale del plugin add-on accrediti.
 */
final class Plugin {

    private static ?self $instance = null;

    /**
     * Restituisce l'istanza singleton del plugin.
     */
    public static function instance(): self {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Costruttore privato singleton.
     */
    private function __construct() {
        if ( ! DependencyGuard::is_fp_forms_available() ) {
            DependencyGuard::register_admin_notice();
            return;
        }

        load_plugin_textdomain(
            'fp-forms-accrediti',
            false,
            dirname( FP_FORMS_ACCREDITI_PLUGIN_BASENAME ) . '/languages'
        );

        ( new FpFormsHooks() )->register();
        ( new Menu() )->register();

        add_action( 'admin_init', [ Settings::class, 'maybe_persist_replacing_stub_email_templates' ], 5 );
    }
}
