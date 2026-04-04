<?php
declare(strict_types=1);

namespace FP\FormsAccrediti\Service;

use FP\FormsAccrediti\Domain\RequestRepository;
use FP\FormsAccrediti\Settings\Settings;

/**
 * Gestisce decisione approvazione/rifiuto richieste.
 */
final class DecisionService {

    private RequestRepository $repository;

    private Mailer $mailer;

    public function __construct() {
        $this->repository = new RequestRepository();
        $this->mailer     = new Mailer();
    }

    /**
     * Approva richiesta con invio email allegato.
     *
     * L'allegato effettivo è: file scelto dall'operatore se valido, altrimenti l'allegato predefinito
     * impostato in Accrediti Settings (se valido), altrimenti nessun allegato.
     */
    public function approve( int $request_id, int $operator_id, string $decision_message, ?int $attachment_id ): bool {
        $request = $this->repository->find_by_id( $request_id );
        if ( ! $request || $request->status !== 'pending' ) {
            return false;
        }

        $effective_attachment_id = $this->resolve_effective_approval_attachment_id( $attachment_id );

        $context = $this->build_email_context( (int) $request->form_id );

        $email_sent = $this->mailer->send_approval_email(
            (string) $request->applicant_email,
            $decision_message,
            $effective_attachment_id,
            $context
        );

        if ( ! $email_sent ) {
            return false;
        }

        $updated = $this->repository->approve_request( $request_id, $operator_id, $decision_message, $effective_attachment_id );
        if ( ! $updated ) {
            return false;
        }

        do_action(
            'fp_tracking_event',
            'accrediti_request_approved',
            [
                'request_id'     => $request_id,
                'submission_id'  => (int) $request->submission_id,
                'form_id'        => (int) $request->form_id,
                'operator_id'    => $operator_id,
                'has_attachment' => $effective_attachment_id !== null && $effective_attachment_id > 0,
                'source_plugin'  => 'fp-forms-accrediti',
                'event_id'       => 'fp_acc_appr_' . $request_id . '_' . time(),
            ]
        );

        return true;
    }

    /**
     * Risolve ID allegato per l'email di approvazione (operatore → predefinito impostazioni).
     */
    private function resolve_effective_approval_attachment_id( ?int $operator_attachment_id ): ?int {
        $op = $operator_attachment_id !== null ? (int) $operator_attachment_id : 0;
        if ( $op > 0 && $this->mailer->is_valid_acrediti_attachment( $op ) ) {
            return $op;
        }

        $default = Settings::get_default_approval_attachment_id();
        if ( $default > 0 && $this->mailer->is_valid_acrediti_attachment( $default ) ) {
            return $default;
        }

        return null;
    }

    /**
     * Rifiuta richiesta con invio email dedicata.
     */
    public function reject( int $request_id, int $operator_id, string $decision_message ): bool {
        $request = $this->repository->find_by_id( $request_id );
        if ( ! $request || $request->status !== 'pending' ) {
            return false;
        }

        $context = $this->build_email_context( (int) $request->form_id );

        $email_sent = $this->mailer->send_rejection_email(
            (string) $request->applicant_email,
            $decision_message,
            $context
        );

        if ( ! $email_sent ) {
            return false;
        }

        $updated = $this->repository->reject_request( $request_id, $operator_id, $decision_message );
        if ( ! $updated ) {
            return false;
        }

        do_action(
            'fp_tracking_event',
            'accrediti_request_rejected',
            [
                'request_id'    => $request_id,
                'submission_id' => (int) $request->submission_id,
                'form_id'       => (int) $request->form_id,
                'operator_id'   => $operator_id,
                'source_plugin' => 'fp-forms-accrediti',
                'event_id'      => 'fp_acc_rej_' . $request_id . '_' . time(),
            ]
        );

        return true;
    }

    /**
     * Costruisce contesto per placeholder email.
     *
     * @return array<string, string>
     */
    private function build_email_context( int $form_id ): array {
        $form_title = '';
        if ( $form_id > 0 && class_exists( '\FPForms\Plugin' ) ) {
            $form = \FPForms\Plugin::instance()->forms->get_form( $form_id );
            $form_title = is_array( $form ) && ! empty( $form['title'] ) ? (string) $form['title'] : '';
        }
        return [ 'form_title' => $form_title ];
    }
}
