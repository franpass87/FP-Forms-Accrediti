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
     *
     * Registra gli hook di integrazione anche quando la classe principale
     * di FP Forms non è ancora visibile al momento di `plugins_loaded`:
     * il retry su `init` copre ordini di caricamento plugin anomali o
     * autoloader PSR-4 registrati più tardi nello stesso tick `plugins_loaded`.
     */
    private function __construct() {
        load_plugin_textdomain(
            'fp-forms-accrediti',
            false,
            dirname( FP_FORMS_ACCREDITI_PLUGIN_BASENAME ) . '/languages'
        );

        add_action( 'admin_init', [ Settings::class, 'maybe_persist_replacing_stub_email_templates' ], 5 );

        if ( DependencyGuard::is_fp_forms_available() ) {
            $this->boot_integration();
            return;
        }

        add_action(
            'init',
            function (): void {
                if ( ! DependencyGuard::is_fp_forms_available() ) {
                    DependencyGuard::register_admin_notice();
                    return;
                }
                $this->boot_integration();
            },
            20
        );
    }

    /**
     * Istanzia l'integrazione una sola volta (idempotente sugli hook FP Forms).
     */
    private function boot_integration(): void {
        static $booted = false;
        if ( $booted ) {
            return;
        }
        $booted = true;
        ( new FpFormsHooks() )->register();
        ( new Menu() )->register();
    }
}
