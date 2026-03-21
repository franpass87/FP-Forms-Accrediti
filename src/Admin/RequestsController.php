<?php
declare(strict_types=1);

namespace FP\FormsAccrediti\Admin;

use FP\FormsAccrediti\Domain\RequestRepository;
use FP\FormsAccrediti\Security\Permissions;
use FP\FormsAccrediti\Service\DecisionService;
use FP\FormsAccrediti\Settings\Settings;

/**
 * Controller admin richieste accredito.
 */
final class RequestsController {

    private RequestRepository $repository;

    private DecisionService $decision_service;

    public function __construct() {
        $this->repository       = new RequestRepository();
        $this->decision_service = new DecisionService();
    }

    /**
     * Render lista richieste.
     */
    public function render_requests_page(): void {
        if ( ! Permissions::can_manage_accrediti() ) {
            wp_die( esc_html__( 'Permessi insufficienti.', 'fp-forms-accrediti' ) );
        }

        $request_id = isset( $_GET['request_id'] ) ? absint( $_GET['request_id'] ) : 0;
        if ( $request_id > 0 ) {
            $request = $this->repository->find_by_id( $request_id );
            if ( ! $request ) {
                wp_die( esc_html__( 'Richiesta non trovata.', 'fp-forms-accrediti' ) );
            }

            $submission = \FPForms\Plugin::instance()->submissions->get_submission( (int) $request->submission_id );
            $audit      = $this->repository->get_audit_events( $request_id );

            include FP_FORMS_ACCREDITI_PLUGIN_DIR . 'templates/admin/request-detail.php';
            return;
        }

        $status = isset( $_GET['status'] ) ? sanitize_key( (string) $_GET['status'] ) : '';
        $form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0;
        $search = isset( $_GET['s'] ) ? sanitize_text_field( (string) $_GET['s'] ) : '';
        $paged = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
        $limit = 20;
        $offset = ( $paged - 1 ) * $limit;

        $filters = [
            'status' => in_array( $status, [ 'pending', 'approved', 'rejected' ], true ) ? $status : '',
            'form_id' => $form_id,
            'search' => $search,
            'limit' => $limit,
            'offset' => $offset,
        ];

        $requests = $this->repository->list_by_filters( $filters );
        $total    = $this->repository->count_by_filters( $filters );
        $total_pages = (int) ceil( $total / $limit );
        $forms = \FPForms\Plugin::instance()->forms->get_forms();

        include FP_FORMS_ACCREDITI_PLUGIN_DIR . 'templates/admin/requests-list.php';
    }

    /**
     * Render pagina impostazioni add-on.
     */
    public function render_settings_page(): void {
        if ( ! Permissions::can_manage_accrediti() ) {
            wp_die( esc_html__( 'Permessi insufficienti.', 'fp-forms-accrediti' ) );
        }

        $settings = Settings::get();
        $forms = \FPForms\Plugin::instance()->forms->get_forms();
        include FP_FORMS_ACCREDITI_PLUGIN_DIR . 'templates/admin/settings.php';
    }

    /**
     * Salvataggio impostazioni add-on.
     */
    public function handle_save_settings(): void {
        if ( ! Permissions::can_manage_accrediti() ) {
            wp_die( esc_html__( 'Permessi insufficienti.', 'fp-forms-accrediti' ) );
        }

        check_admin_referer( 'fp_forms_accrediti_save_settings' );

        $raw = isset( $_POST['settings'] ) && is_array( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : [];
        $settings = $this->sanitize_settings( $raw );
        update_option( 'fp_forms_accrediti_settings', $settings );

        wp_safe_redirect(
            add_query_arg(
                [ 'page' => 'fp-forms-accrediti-settings', 'updated' => 1 ],
                admin_url( 'admin.php' )
            )
        );
        exit;
    }

    /**
     * Gestione approvazione/rifiuto richiesta.
     */
    public function handle_decision(): void {
        if ( ! Permissions::can_manage_accrediti() ) {
            wp_die( esc_html__( 'Permessi insufficienti.', 'fp-forms-accrediti' ) );
        }

        check_admin_referer( 'fp_forms_accrediti_decide_request' );

        $request_id = isset( $_POST['request_id'] ) ? absint( $_POST['request_id'] ) : 0;
        $action     = isset( $_POST['decision_action'] ) ? sanitize_key( (string) $_POST['decision_action'] ) : '';
        $message    = isset( $_POST['decision_message'] ) ? sanitize_textarea_field( (string) wp_unslash( $_POST['decision_message'] ) ) : '';
        $attachment_id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;

        if ( $request_id <= 0 || ! in_array( $action, [ 'approve', 'reject' ], true ) ) {
            wp_die( esc_html__( 'Parametri non validi.', 'fp-forms-accrediti' ) );
        }

        $operator_id = get_current_user_id();
        $ok = false;

        if ( $action === 'approve' ) {
            $ok = $this->decision_service->approve( $request_id, $operator_id, $message, $attachment_id ?: null );
        } elseif ( $action === 'reject' ) {
            $ok = $this->decision_service->reject( $request_id, $operator_id, $message );
        }

        wp_safe_redirect(
            add_query_arg(
                [
                    'page' => 'fp-forms-accrediti-requests',
                    'request_id' => $request_id,
                    'decision' => $ok ? 'ok' : 'fail',
                ],
                admin_url( 'admin.php' )
            )
        );
        exit;
    }

    /**
     * Sanitizza payload impostazioni add-on.
     */
    private function sanitize_settings( array $raw ): array {
        $sanitized = [
            'enabled' => ! empty( $raw['enabled'] ),
            'operator_capability' => isset( $raw['operator_capability'] ) ? sanitize_key( (string) $raw['operator_capability'] ) : 'manage_fp_forms_accrediti',
            'allowed_mime_types' => [ 'application/pdf' ],
            'form_configs' => [],
            'email_templates' => [
                'approval_subject' => sanitize_text_field( (string) ( $raw['email_templates']['approval_subject'] ?? '' ) ),
                'approval_body' => sanitize_textarea_field( (string) ( $raw['email_templates']['approval_body'] ?? '' ) ),
                'rejection_subject' => sanitize_text_field( (string) ( $raw['email_templates']['rejection_subject'] ?? '' ) ),
                'rejection_body' => sanitize_textarea_field( (string) ( $raw['email_templates']['rejection_body'] ?? '' ) ),
            ],
        ];

        $mime_raw = isset( $raw['allowed_mime_types'] ) && is_array( $raw['allowed_mime_types'] ) ? $raw['allowed_mime_types'] : [];
        $allowed = [];
        foreach ( $mime_raw as $mime ) {
            $mime = sanitize_text_field( (string) $mime );
            if ( in_array( $mime, [ 'application/pdf' ], true ) ) {
                $allowed[] = $mime;
            }
        }
        if ( empty( $allowed ) ) {
            $allowed = [ 'application/pdf' ];
        }
        $sanitized['allowed_mime_types'] = array_values( array_unique( $allowed ) );

        $form_configs_raw = isset( $raw['form_configs'] ) && is_array( $raw['form_configs'] ) ? $raw['form_configs'] : [];
        foreach ( $form_configs_raw as $form_id => $config ) {
            $fid = absint( (string) $form_id );
            if ( $fid <= 0 || ! is_array( $config ) ) {
                continue;
            }

            $sanitized['form_configs'][ (string) $fid ] = [
                'enabled' => ! empty( $config['enabled'] ),
                'email_field' => isset( $config['email_field'] ) ? sanitize_key( (string) $config['email_field'] ) : '',
            ];
        }

        return $sanitized;
    }
}
