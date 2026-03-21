<?php
declare(strict_types=1);

namespace FP\FormsAccrediti\Security;

use FP\FormsAccrediti\Settings\Settings;

/**
 * Utility permessi/capability modulo accrediti.
 */
final class Permissions {

    /**
     * Verifica permesso operatore accrediti.
     */
    public static function can_manage_accrediti(): bool {
        $settings = Settings::get();
        $cap      = $settings['operator_capability'] ?? 'manage_fp_forms_accrediti';

        return current_user_can( $cap ) || current_user_can( 'manage_options' );
    }
}
