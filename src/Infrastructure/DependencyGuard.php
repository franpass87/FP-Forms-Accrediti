<?php
declare(strict_types=1);

namespace FP\FormsAccrediti\Infrastructure;

/**
 * Verifica dipendenze richieste dall'add-on.
 */
final class DependencyGuard {

    /**
     * Controlla se FP Forms e' disponibile.
     */
    public static function is_fp_forms_available(): bool {
        return class_exists( '\FPForms\Plugin' );
    }

    /**
     * Registra avviso admin quando FP Forms non e' disponibile.
     */
    public static function register_admin_notice(): void {
        if ( ! is_admin() ) {
            return;
        }

        add_action(
            'admin_notices',
            static function (): void {
                if ( ! current_user_can( 'activate_plugins' ) ) {
                    return;
                }

                echo '<div class="notice notice-error"><p>';
                echo esc_html__( 'FP Forms Accrediti richiede FP Forms attivo.', 'fp-forms-accrediti' );
                echo '</p></div>';
            }
        );
    }
}
