<?php
/**
 * Plugin Name: FP Forms Accrediti
 * Plugin URI: https://francescopasseri.com/
 * Description: Add-on per FP Forms: workflow richieste accredito con approvazione/rifiuto e invio email con allegato.
 * Version: 1.0.0
 * Author: Francesco Passeri
 * Author URI: https://francescopasseri.com
 * Text Domain: fp-forms-accrediti
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'FP_FORMS_ACCREDITI_VERSION', '1.0.0' );
define( 'FP_FORMS_ACCREDITI_PLUGIN_FILE', __FILE__ );
define( 'FP_FORMS_ACCREDITI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FP_FORMS_ACCREDITI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'FP_FORMS_ACCREDITI_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

$fp_forms_accrediti_autoload = FP_FORMS_ACCREDITI_PLUGIN_DIR . 'vendor/autoload.php';

if ( is_readable( $fp_forms_accrediti_autoload ) ) {
    require_once $fp_forms_accrediti_autoload;
} else {
    spl_autoload_register(
        static function ( string $class ): void {
            $prefix = 'FP\\FormsAccrediti\\';

            if ( strpos( $class, $prefix ) !== 0 ) {
                return;
            }

            $relative = substr( $class, strlen( $prefix ) );
            $relative = str_replace( '\\', DIRECTORY_SEPARATOR, $relative );
            $file     = FP_FORMS_ACCREDITI_PLUGIN_DIR . 'src/' . $relative . '.php';

            if ( file_exists( $file ) ) {
                require_once $file;
            }
        }
    );
}

function fp_forms_accrediti_init(): void {
    \FP\FormsAccrediti\Plugin::instance();
}
add_action( 'plugins_loaded', 'fp_forms_accrediti_init' );

function fp_forms_accrediti_activate(): void {
    require_once FP_FORMS_ACCREDITI_PLUGIN_DIR . 'includes/Activator.php';
    \FP\FormsAccrediti\Activator::activate();
}
register_activation_hook( __FILE__, 'fp_forms_accrediti_activate' );

function fp_forms_accrediti_deactivate(): void {
    require_once FP_FORMS_ACCREDITI_PLUGIN_DIR . 'includes/Deactivator.php';
    \FP\FormsAccrediti\Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, 'fp_forms_accrediti_deactivate' );
