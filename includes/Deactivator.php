<?php
declare(strict_types=1);

namespace FP\FormsAccrediti;

/**
 * Gestisce disattivazione add-on.
 */
final class Deactivator {

    /**
     * Esegue routine di disattivazione.
     */
    public static function deactivate(): void {
        flush_rewrite_rules();
    }
}
