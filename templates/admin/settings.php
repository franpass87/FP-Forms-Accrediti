<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap fpfa-admin">
    <h1><?php esc_html_e( 'Impostazioni Accrediti', 'fp-forms-accrediti' ); ?></h1>

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

        <h2><?php esc_html_e( 'Template email', 'fp-forms-accrediti' ); ?></h2>
        <table class="form-table">
            <tr>
                <th><label for="approval_subject"><?php esc_html_e( 'Oggetto approvazione', 'fp-forms-accrediti' ); ?></label></th>
                <td><input type="text" id="approval_subject" class="large-text" name="settings[email_templates][approval_subject]" value="<?php echo esc_attr( (string) $settings['email_templates']['approval_subject'] ); ?>"></td>
            </tr>
            <tr>
                <th><label for="approval_body"><?php esc_html_e( 'Testo approvazione', 'fp-forms-accrediti' ); ?></label></th>
                <td><textarea id="approval_body" class="large-text" rows="5" name="settings[email_templates][approval_body]"><?php echo esc_textarea( (string) $settings['email_templates']['approval_body'] ); ?></textarea></td>
            </tr>
            <tr>
                <th><label for="rejection_subject"><?php esc_html_e( 'Oggetto rifiuto', 'fp-forms-accrediti' ); ?></label></th>
                <td><input type="text" id="rejection_subject" class="large-text" name="settings[email_templates][rejection_subject]" value="<?php echo esc_attr( (string) $settings['email_templates']['rejection_subject'] ); ?>"></td>
            </tr>
            <tr>
                <th><label for="rejection_body"><?php esc_html_e( 'Testo rifiuto', 'fp-forms-accrediti' ); ?></label></th>
                <td><textarea id="rejection_body" class="large-text" rows="5" name="settings[email_templates][rejection_body]"><?php echo esc_textarea( (string) $settings['email_templates']['rejection_body'] ); ?></textarea></td>
            </tr>
        </table>

        <h2><?php esc_html_e( 'MIME allegati consentiti', 'fp-forms-accrediti' ); ?></h2>
        <label><input type="checkbox" name="settings[allowed_mime_types][]" value="application/pdf" <?php checked( in_array( 'application/pdf', (array) $settings['allowed_mime_types'], true ) ); ?>> application/pdf</label>

        <p class="submit">
            <button type="submit" class="button button-primary"><?php esc_html_e( 'Salva impostazioni', 'fp-forms-accrediti' ); ?></button>
        </p>
    </form>
</div>
