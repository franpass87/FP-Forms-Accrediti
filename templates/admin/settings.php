<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap fpfa-admin">
    <h1 class="screen-reader-text"><?php esc_html_e( 'Impostazioni Accrediti', 'fp-forms-accrediti' ); ?></h1>
    <div class="fpfa-page-header">
        <div class="fpfa-page-header-content">
            <h2 class="fpfa-page-header-title" aria-hidden="true"><?php esc_html_e( 'Impostazioni Accrediti', 'fp-forms-accrediti' ); ?></h2>
            <p class="fpfa-page-header-desc"><?php esc_html_e( 'Configura workflow accrediti e template email.', 'fp-forms-accrediti' ); ?></p>
        </div>
        <span class="fpfa-page-header-badge">v<?php echo esc_html( defined( 'FP_FORMS_ACCREDITI_VERSION' ) ? FP_FORMS_ACCREDITI_VERSION : '0' ); ?></span>
    </div>

    <?php if ( isset( $_GET['updated'] ) ) : ?>
        <div class="notice notice-success"><p><?php esc_html_e( 'Impostazioni salvate.', 'fp-forms-accrediti' ); ?></p></div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <?php wp_nonce_field( 'fp_forms_accrediti_save_settings' ); ?>
        <input type="hidden" name="action" value="fp_forms_accrediti_save_settings">

        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'Modulo attivo', 'fp-forms-accrediti' ); ?></th>
                <td><label><input type="checkbox" name="settings[enabled]" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?>> <?php esc_html_e( 'Abilita workflow accrediti', 'fp-forms-accrediti' ); ?></label></td>
            </tr>
            <tr>
                <th scope="row"><label for="operator_capability"><?php esc_html_e( 'Capability operatore', 'fp-forms-accrediti' ); ?></label></th>
                <td><input type="text" id="operator_capability" name="settings[operator_capability]" value="<?php echo esc_attr( (string) $settings['operator_capability'] ); ?>" class="regular-text"></td>
            </tr>
        </table>

        <h2><?php esc_html_e( 'Form abilitati', 'fp-forms-accrediti' ); ?></h2>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Form', 'fp-forms-accrediti' ); ?></th>
                    <th><?php esc_html_e( 'Abilitato', 'fp-forms-accrediti' ); ?></th>
                    <th><?php esc_html_e( 'Campo email (opzionale)', 'fp-forms-accrediti' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $forms as $form_item ) : ?>
                    <?php
                    $fid = (string) $form_item['id'];
                    $cfg = $settings['form_configs'][ $fid ] ?? [ 'enabled' => false, 'email_field' => '' ];
                    ?>
                    <tr>
                        <td>#<?php echo esc_html( $fid ); ?> - <?php echo esc_html( $form_item['title'] ); ?></td>
                        <td><input type="checkbox" name="settings[form_configs][<?php echo esc_attr( $fid ); ?>][enabled]" value="1" <?php checked( ! empty( $cfg['enabled'] ) ); ?>></td>
                        <td><input type="text" name="settings[form_configs][<?php echo esc_attr( $fid ); ?>][email_field]" value="<?php echo esc_attr( (string) ( $cfg['email_field'] ?? '' ) ); ?>" class="regular-text"></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="fpfa-card fpfa-card-email-templates">
            <div class="fpfa-card-header">
                <span class="dashicons dashicons-email-alt"></span>
                <h2><?php esc_html_e( 'Template email', 'fp-forms-accrediti' ); ?></h2>
            </div>
            <div class="fpfa-card-body">
                <p class="description fpfa-card-desc"><?php esc_html_e( 'Personalizza gli oggetti e i testi delle email inviate al candidato. Usa i tag sotto per inserire dati dinamici.', 'fp-forms-accrediti' ); ?></p>
                <div class="fpfa-tags-hint">
                    <strong><?php esc_html_e( 'Tag disponibili:', 'fp-forms-accrediti' ); ?></strong>
                    <code>{applicant_email}</code> <code>{form_title}</code> <code>{site_name}</code> <code>{site_url}</code> <code>{date}</code> <code>{time}</code> <code>{decision_message}</code>
                </div>

                <div class="fpfa-email-section">
                    <h3 class="fpfa-email-section-title"><?php esc_html_e( 'Approvazione', 'fp-forms-accrediti' ); ?></h3>
                    <div class="fpfa-field">
                        <label for="approval_subject"><?php esc_html_e( 'Oggetto', 'fp-forms-accrediti' ); ?></label>
                        <input type="text" id="approval_subject" class="large-text" name="settings[email_templates][approval_subject]" value="<?php echo esc_attr( (string) ( $settings['email_templates']['approval_subject'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'La tua richiesta accredito è stata approvata - {site_name}', 'fp-forms-accrediti' ); ?>">
                    </div>
                    <div class="fpfa-field">
                        <label for="approval_body"><?php esc_html_e( 'Testo', 'fp-forms-accrediti' ); ?></label>
                        <textarea id="approval_body" class="large-text" rows="6" name="settings[email_templates][approval_body]" placeholder="<?php esc_attr_e( "Gentile candidato,\n\nla tua richiesta di accredito per {form_title} è stata approvata.\n\nIn allegato trovi il documento ufficiale.\n\n{decision_message}\n\nCordiali saluti,\n{site_name}", 'fp-forms-accrediti' ); ?>"><?php echo esc_textarea( (string) ( $settings['email_templates']['approval_body'] ?? '' ) ); ?></textarea>
                        <span class="fpfa-hint"><?php esc_html_e( 'Se l\'operatore scrive un messaggio, viene aggiunto in automatico dopo il testo (oppure usa {decision_message}).', 'fp-forms-accrediti' ); ?></span>
                    </div>
                </div>

                <div class="fpfa-email-section">
                    <h3 class="fpfa-email-section-title"><?php esc_html_e( 'Rifiuto', 'fp-forms-accrediti' ); ?></h3>
                    <div class="fpfa-field">
                        <label for="rejection_subject"><?php esc_html_e( 'Oggetto', 'fp-forms-accrediti' ); ?></label>
                        <input type="text" id="rejection_subject" class="large-text" name="settings[email_templates][rejection_subject]" value="<?php echo esc_attr( (string) ( $settings['email_templates']['rejection_subject'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'Esito richiesta accredito - {site_name}', 'fp-forms-accrediti' ); ?>">
                    </div>
                    <div class="fpfa-field">
                        <label for="rejection_body"><?php esc_html_e( 'Testo', 'fp-forms-accrediti' ); ?></label>
                        <textarea id="rejection_body" class="large-text" rows="6" name="settings[email_templates][rejection_body]" placeholder="<?php esc_attr_e( "Gentile candidato,\n\npurtroppo la tua richiesta di accredito per {form_title} non è stata approvata.\n\n{decision_message}\n\nPer ulteriori informazioni puoi contattarci.\n\nCordiali saluti,\n{site_name}", 'fp-forms-accrediti' ); ?>"><?php echo esc_textarea( (string) ( $settings['email_templates']['rejection_body'] ?? '' ) ); ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <h2><?php esc_html_e( 'MIME allegati consentiti', 'fp-forms-accrediti' ); ?></h2>
        <label><input type="checkbox" name="settings[allowed_mime_types][]" value="application/pdf" <?php checked( in_array( 'application/pdf', (array) $settings['allowed_mime_types'], true ) ); ?>> application/pdf</label>

        <p class="submit">
            <button type="submit" class="button button-primary"><?php esc_html_e( 'Salva impostazioni', 'fp-forms-accrediti' ); ?></button>
        </p>
    </form>
</div>
