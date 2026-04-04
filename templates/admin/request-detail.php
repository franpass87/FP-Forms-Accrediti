<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap fpfa-admin fpfa-admin-page">
    <h1 class="screen-reader-text"><?php esc_html_e( 'Dettaglio Richiesta Accredito', 'fp-forms-accrediti' ); ?></h1>
    <div class="fpfa-page-header">
        <div class="fpfa-page-header-content">
            <h2 class="fpfa-page-header-title" aria-hidden="true">
                <span class="dashicons dashicons-visibility" aria-hidden="true"></span>
                <?php esc_html_e( 'Dettaglio Richiesta Accredito', 'fp-forms-accrediti' ); ?>
            </h2>
            <p class="fpfa-page-header-desc"><?php esc_html_e( 'Esamina e approva o rifiuta la richiesta.', 'fp-forms-accrediti' ); ?></p>
        </div>
        <div class="fpfa-page-header-actions">
            <a href="<?php echo esc_url( add_query_arg( [ 'page' => 'fp-forms-accrediti-requests' ], admin_url( 'admin.php' ) ) ); ?>" class="button fpfa-btn fpfa-btn-ghost fpfa-page-header-link">&larr; <?php esc_html_e( 'Torna alla lista', 'fp-forms-accrediti' ); ?></a>
            <span class="fpfa-page-header-badge">v<?php echo esc_html( defined( 'FP_FORMS_ACCREDITI_VERSION' ) ? FP_FORMS_ACCREDITI_VERSION : '0' ); ?></span>
        </div>
    </div>

    <?php if ( isset( $_GET['decision'] ) ) : ?>
        <?php if ( $_GET['decision'] === 'ok' ) : ?>
            <div class="notice notice-success"><p><?php esc_html_e( 'Decisione salvata e email inviata.', 'fp-forms-accrediti' ); ?></p></div>
        <?php else : ?>
            <div class="notice notice-error"><p><?php esc_html_e( 'Operazione non riuscita. Verifica stato richiesta e configurazione email.', 'fp-forms-accrediti' ); ?></p></div>
        <?php endif; ?>
    <?php endif; ?>

    <table class="form-table">
        <tr><th><?php esc_html_e( 'Richiesta', 'fp-forms-accrediti' ); ?></th><td>#<?php echo esc_html( (string) $request->id ); ?></td></tr>
        <tr><th><?php esc_html_e( 'Submission', 'fp-forms-accrediti' ); ?></th><td>#<?php echo esc_html( (string) $request->submission_id ); ?></td></tr>
        <tr><th><?php esc_html_e( 'Form', 'fp-forms-accrediti' ); ?></th><td>#<?php echo esc_html( (string) $request->form_id ); ?></td></tr>
        <tr><th><?php esc_html_e( 'Email candidato', 'fp-forms-accrediti' ); ?></th><td><?php echo esc_html( (string) $request->applicant_email ); ?></td></tr>
        <tr><th><?php esc_html_e( 'Stato', 'fp-forms-accrediti' ); ?></th><td><span class="fpfa-status fpfa-status-<?php echo esc_attr( (string) $request->status ); ?>"><?php echo esc_html( ucfirst( (string) $request->status ) ); ?></span></td></tr>
    </table>

    <?php if ( ! empty( $submission ) && is_array( $submission->data ) ) : ?>
        <h2><?php esc_html_e( 'Dati submission', 'fp-forms-accrediti' ); ?></h2>
        <table class="widefat striped">
            <tbody>
            <?php foreach ( $submission->data as $key => $value ) : ?>
                <tr>
                    <th><?php echo esc_html( (string) $key ); ?></th>
                    <td><?php echo esc_html( is_array( $value ) ? implode( ', ', $value ) : (string) $value ); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php if ( (string) $request->status === 'pending' ) : ?>
        <h2><?php esc_html_e( 'Decisione operatore', 'fp-forms-accrediti' ); ?></h2>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <?php wp_nonce_field( 'fp_forms_accrediti_decide_request' ); ?>
            <input type="hidden" name="action" value="fp_forms_accrediti_decide_request">
            <input type="hidden" name="request_id" value="<?php echo esc_attr( (string) $request->id ); ?>">

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="decision_message"><?php esc_html_e( 'Messaggio decisione', 'fp-forms-accrediti' ); ?></label></th>
                    <td><textarea id="decision_message" name="decision_message" rows="6" class="large-text"></textarea></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Allegato accredito (solo approvazione)', 'fp-forms-accrediti' ); ?></th>
                    <td>
                        <input type="hidden" id="fpfa_attachment_id" name="attachment_id" value="">
                        <button type="button" class="button" id="fpfa_select_attachment"><?php esc_html_e( 'Seleziona allegato', 'fp-forms-accrediti' ); ?></button>
                        <span id="fpfa_attachment_label"></span>
                        <?php
                        $fpfa_default_att = \FP\FormsAccrediti\Settings\Settings::get_default_approval_attachment_id();
                        if ( $fpfa_default_att > 0 ) :
                            $fpfa_def_path = get_attached_file( $fpfa_default_att );
                            $fpfa_def_name = $fpfa_def_path ? basename( $fpfa_def_path ) : '';
                            ?>
                        <p class="description fpfa-attachment-default-hint">
                            <?php
                            if ( $fpfa_def_name !== '' ) {
                                echo esc_html(
                                    sprintf(
                                        /* translators: %s: PDF file name */
                                        __( 'Se non scegli un file qui, verrà usato automaticamente il PDF predefinito dalle impostazioni: %s', 'fp-forms-accrediti' ),
                                        $fpfa_def_name
                                    )
                                );
                            } else {
                                esc_html_e( 'Se non scegli un file qui, verrà usato automaticamente il PDF predefinito impostato in Accrediti Settings (se valido).', 'fp-forms-accrediti' );
                            }
                            ?>
                        </p>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" name="decision_action" value="approve" class="button fpfa-btn fpfa-btn-primary"><?php esc_html_e( 'Approva e invia email', 'fp-forms-accrediti' ); ?></button>
                <button type="submit" name="decision_action" value="reject" class="button fpfa-btn fpfa-btn-secondary"><?php esc_html_e( 'Rifiuta e invia email', 'fp-forms-accrediti' ); ?></button>
            </p>
        </form>
    <?php endif; ?>

    <h2><?php esc_html_e( 'Audit eventi', 'fp-forms-accrediti' ); ?></h2>
    <table class="widefat striped">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Data', 'fp-forms-accrediti' ); ?></th>
                <th><?php esc_html_e( 'Evento', 'fp-forms-accrediti' ); ?></th>
                <th><?php esc_html_e( 'Attore', 'fp-forms-accrediti' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $audit ) ) : ?>
                <tr><td colspan="3"><?php esc_html_e( 'Nessun evento audit.', 'fp-forms-accrediti' ); ?></td></tr>
            <?php else : ?>
                <?php foreach ( $audit as $event ) : ?>
                    <tr>
                        <td><?php echo esc_html( (string) $event->created_at ); ?></td>
                        <td><?php echo esc_html( (string) $event->event_type ); ?></td>
                        <td><?php echo esc_html( $event->actor_user_id ? (string) $event->actor_user_id : '-' ); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
