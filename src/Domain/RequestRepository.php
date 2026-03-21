<?php
declare(strict_types=1);

namespace FP\FormsAccrediti\Domain;

use FP\FormsAccrediti\Database\Schema;

/**
 * Repository richieste accredito.
 */
final class RequestRepository {

    /**
     * Crea richiesta pending idempotente per submission.
     *
     * @return int|false
     */
    public function create_pending_request( int $submission_id, int $form_id, string $applicant_email ) {
        global $wpdb;

        $existing = $this->find_by_submission_id( $submission_id );
        if ( $existing ) {
            return (int) $existing->id;
        }

        $result = $wpdb->insert(
            Schema::requests_table(),
            [
                'submission_id' => $submission_id,
                'form_id' => $form_id,
                'applicant_email' => sanitize_email( $applicant_email ),
                'status' => 'pending',
            ],
            [ '%d', '%d', '%s', '%s' ]
        );

        if ( ! $result ) {
            return false;
        }

        $request_id = (int) $wpdb->insert_id;
        $this->append_audit_event( $request_id, 'request_created', [ 'form_id' => $form_id ], get_current_user_id() ?: null );

        return $request_id;
    }

    /**
     * Trova richiesta per ID.
     */
    public function find_by_id( int $request_id ): ?object {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT * FROM ' . Schema::requests_table() . ' WHERE id = %d',
                $request_id
            )
        );

        return $row ?: null;
    }

    /**
     * Trova richiesta per submission ID.
     */
    public function find_by_submission_id( int $submission_id ): ?object {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT * FROM ' . Schema::requests_table() . ' WHERE submission_id = %d',
                $submission_id
            )
        );

        return $row ?: null;
    }

    /**
     * Lista richieste filtrata.
     *
     * @return array<int, object>
     */
    public function list_by_filters( array $filters = [] ): array {
        global $wpdb;

        $defaults = [
            'status' => '',
            'form_id' => 0,
            'search' => '',
            'limit' => 20,
            'offset' => 0,
        ];

        $filters = wp_parse_args( $filters, $defaults );
        $where   = [ '1=1' ];
        $values  = [];

        if ( $filters['status'] !== '' ) {
            $where[]  = 'status = %s';
            $values[] = $filters['status'];
        }

        if ( (int) $filters['form_id'] > 0 ) {
            $where[]  = 'form_id = %d';
            $values[] = (int) $filters['form_id'];
        }

        if ( $filters['search'] !== '' ) {
            $where[]  = 'applicant_email LIKE %s';
            $values[] = '%' . $wpdb->esc_like( (string) $filters['search'] ) . '%';
        }

        $values[] = (int) $filters['limit'];
        $values[] = (int) $filters['offset'];

        $query = $wpdb->prepare(
            'SELECT * FROM ' . Schema::requests_table() . ' WHERE ' . implode( ' AND ', $where ) . ' ORDER BY created_at DESC LIMIT %d OFFSET %d',
            $values
        );

        return $wpdb->get_results( $query ) ?: [];
    }

    /**
     * Conta richieste con filtri.
     */
    public function count_by_filters( array $filters = [] ): int {
        global $wpdb;

        $defaults = [
            'status' => '',
            'form_id' => 0,
            'search' => '',
        ];

        $filters = wp_parse_args( $filters, $defaults );
        $where   = [ '1=1' ];
        $values  = [];

        if ( $filters['status'] !== '' ) {
            $where[]  = 'status = %s';
            $values[] = $filters['status'];
        }

        if ( (int) $filters['form_id'] > 0 ) {
            $where[]  = 'form_id = %d';
            $values[] = (int) $filters['form_id'];
        }

        if ( $filters['search'] !== '' ) {
            $where[]  = 'applicant_email LIKE %s';
            $values[] = '%' . $wpdb->esc_like( (string) $filters['search'] ) . '%';
        }

        $query = 'SELECT COUNT(*) FROM ' . Schema::requests_table() . ' WHERE ' . implode( ' AND ', $where );
        if ( ! empty( $values ) ) {
            $query = $wpdb->prepare( $query, $values );
        }

        return (int) $wpdb->get_var( $query );
    }

    /**
     * Approva richiesta.
     */
    public function approve_request( int $request_id, int $operator_id, string $message, ?int $attachment_id ): bool {
        return $this->update_decision( $request_id, 'approved', $operator_id, $message, $attachment_id );
    }

    /**
     * Rifiuta richiesta.
     */
    public function reject_request( int $request_id, int $operator_id, string $message ): bool {
        return $this->update_decision( $request_id, 'rejected', $operator_id, $message, null );
    }

    /**
     * Aggiorna stato decisione richiesta.
     */
    private function update_decision( int $request_id, string $status, int $operator_id, string $message, ?int $attachment_id ): bool {
        global $wpdb;

        $result = $wpdb->update(
            Schema::requests_table(),
            [
                'status' => $status,
                'operator_id' => $operator_id,
                'decision_message' => $message,
                'attachment_id' => $attachment_id,
                'decided_at' => current_time( 'mysql' ),
            ],
            [ 'id' => $request_id ],
            [ '%s', '%d', '%s', '%d', '%s' ],
            [ '%d' ]
        );

        if ( $result === false ) {
            return false;
        }

        $this->append_audit_event(
            $request_id,
            $status === 'approved' ? 'request_approved' : 'request_rejected',
            [
                'message' => $message,
                'attachment_id' => $attachment_id,
            ],
            $operator_id
        );

        return true;
    }

    /**
     * Appende evento audit.
     */
    public function append_audit_event( int $request_id, string $event_type, array $payload = [], ?int $actor_user_id = null ): bool {
        global $wpdb;

        return (bool) $wpdb->insert(
            Schema::audit_table(),
            [
                'request_id' => $request_id,
                'event_type' => sanitize_key( $event_type ),
                'event_payload' => wp_json_encode( $payload ),
                'actor_user_id' => $actor_user_id,
            ],
            [ '%d', '%s', '%s', '%d' ]
        );
    }

    /**
     * Elenco eventi audit per richiesta.
     *
     * @return array<int, object>
     */
    public function get_audit_events( int $request_id ): array {
        global $wpdb;
        $query = $wpdb->prepare(
            'SELECT * FROM ' . Schema::audit_table() . ' WHERE request_id = %d ORDER BY created_at DESC',
            $request_id
        );

        return $wpdb->get_results( $query ) ?: [];
    }
}
