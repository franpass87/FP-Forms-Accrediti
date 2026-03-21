<?php
declare(strict_types=1);

namespace FP\FormsAccrediti\Service;

use FP\FormsAccrediti\Domain\RequestRepository;

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
     */
    public function approve( int $request_id, int $operator_id, string $decision_message, ?int $attachment_id ): bool {
        $request = $this->repository->find_by_id( $request_id );
        if ( ! $request || $request->status !== 'pending' ) {
            return false;
        }

        $email_sent = $this->mailer->send_approval_email(
            (string) $request->applicant_email,
            $decision_message,
            $attachment_id
        );

        if ( ! $email_sent ) {
            return false;
        }

        return $this->repository->approve_request( $request_id, $operator_id, $decision_message, $attachment_id );
    }

    /**
     * Rifiuta richiesta con invio email dedicata.
     */
    public function reject( int $request_id, int $operator_id, string $decision_message ): bool {
        $request = $this->repository->find_by_id( $request_id );
        if ( ! $request || $request->status !== 'pending' ) {
            return false;
        }

        $email_sent = $this->mailer->send_rejection_email(
            (string) $request->applicant_email,
            $decision_message
        );

        if ( ! $email_sent ) {
            return false;
        }

        return $this->repository->reject_request( $request_id, $operator_id, $decision_message );
    }
}
