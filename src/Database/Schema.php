<?php
declare(strict_types=1);

namespace FP\FormsAccrediti\Database;

/**
 * Gestione schema DB add-on accrediti.
 */
final class Schema {

    /**
     * Nome tabella richieste.
     */
    public static function requests_table(): string {
        global $wpdb;
        return $wpdb->prefix . 'fp_forms_accrediti_requests';
    }

    /**
     * Nome tabella audit.
     */
    public static function audit_table(): string {
        global $wpdb;
        return $wpdb->prefix . 'fp_forms_accrediti_audit';
    }

    /**
     * Crea/aggiorna tabelle necessarie.
     */
    public static function create_tables(): void {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $requests_table  = self::requests_table();
        $audit_table     = self::audit_table();

        $sql_requests = "CREATE TABLE {$requests_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            submission_id bigint(20) unsigned NOT NULL,
            form_id bigint(20) unsigned NOT NULL,
            applicant_email varchar(190) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            operator_id bigint(20) unsigned DEFAULT NULL,
            decision_message longtext DEFAULT NULL,
            attachment_id bigint(20) unsigned DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            decided_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_submission (submission_id),
            KEY status (status),
            KEY form_id (form_id),
            KEY applicant_email (applicant_email)
        ) {$charset_collate};";

        $sql_audit = "CREATE TABLE {$audit_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            request_id bigint(20) unsigned NOT NULL,
            event_type varchar(60) NOT NULL,
            event_payload longtext DEFAULT NULL,
            actor_user_id bigint(20) unsigned DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY request_id (request_id),
            KEY event_type (event_type)
        ) {$charset_collate};";

        dbDelta( $sql_requests );
        dbDelta( $sql_audit );
    }
}
