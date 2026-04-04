<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$fpfa_mail_ui = \FP\FormsAccrediti\Settings\Settings::normalize_email_templates(
	is_array( $settings['email_templates'] ?? null ) ? $settings['email_templates'] : []
);
?>
<div class="wrap fpfa-admin fpfa-settings-page">
	<h1 class="screen-reader-text"><?php esc_html_e( 'Impostazioni Accrediti', 'fp-forms-accrediti' ); ?></h1>
	<div class="fpfa-page-header">
		<div class="fpfa-page-header-content">
			<h2 class="fpfa-page-header-title" aria-hidden="true"><?php esc_html_e( 'Impostazioni Accrediti', 'fp-forms-accrediti' ); ?></h2>
			<p class="fpfa-page-header-desc"><?php esc_html_e( 'Collega i form FP Forms al workflow accredito, definisci chi può approvare e come sono scritte le email al candidato.', 'fp-forms-accrediti' ); ?></p>
		</div>
		<span class="fpfa-page-header-badge">v<?php echo esc_html( defined( 'FP_FORMS_ACCREDITI_VERSION' ) ? FP_FORMS_ACCREDITI_VERSION : '0' ); ?></span>
	</div>

	<?php if ( isset( $_GET['updated'] ) ) : ?>
		<div class="notice notice-success"><p><?php esc_html_e( 'Impostazioni salvate.', 'fp-forms-accrediti' ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="fpfa-settings-form">
		<?php wp_nonce_field( 'fp_forms_accrediti_save_settings' ); ?>
		<input type="hidden" name="action" value="fp_forms_accrediti_save_settings">

		<div class="fpfa-card fpfa-card-intro">
			<div class="fpfa-card-header">
				<span class="dashicons dashicons-info" aria-hidden="true"></span>
				<h2><?php esc_html_e( 'Come funziona', 'fp-forms-accrediti' ); ?></h2>
			</div>
			<div class="fpfa-card-body">
				<ol class="fpfa-steps-list">
					<li><?php esc_html_e( 'Scegli quali form, tra quelli di FP Forms, devono creare una richiesta «in attesa» dopo l’invio.', 'fp-forms-accrediti' ); ?></li>
					<li><?php esc_html_e( 'Il team apre FP Forms → Accrediti, approva o rifiuta e il candidato riceve l’email con il testo che configuri sotto.', 'fp-forms-accrediti' ); ?></li>
					<li><?php esc_html_e( 'L’allegato PDF può essere scelto ogni volta oppure impostato una volta come predefinito (sezione Documenti).', 'fp-forms-accrediti' ); ?></li>
				</ol>
			</div>
		</div>

		<div class="fpfa-card">
			<div class="fpfa-card-header">
				<span class="dashicons dashicons-admin-settings" aria-hidden="true"></span>
				<div class="fpfa-card-header-text">
					<h2><?php esc_html_e( 'Generale', 'fp-forms-accrediti' ); ?></h2>
					<p class="fpfa-card-lead"><?php esc_html_e( 'Attiva il modulo e indica quale permesso WordPress serve per vedere le pagine Accrediti e decidere le richieste.', 'fp-forms-accrediti' ); ?></p>
				</div>
			</div>
			<div class="fpfa-card-body">
				<div class="fpfa-fields-grid">
					<div class="fpfa-field fpfa-field-span-2">
						<label class="fpfa-toggle-label">
							<input type="checkbox" name="settings[enabled]" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?>>
							<span class="fpfa-toggle-text">
								<strong><?php esc_html_e( 'Modulo accrediti attivo', 'fp-forms-accrediti' ); ?></strong>
								<span class="fpfa-hint-inline"><?php esc_html_e( 'Se disattivi, non vengono create nuove richieste (quelle già presenti restano in elenco).', 'fp-forms-accrediti' ); ?></span>
							</span>
						</label>
					</div>
					<div class="fpfa-field">
						<label for="operator_capability"><?php esc_html_e( 'Permesso operatore (capability)', 'fp-forms-accrediti' ); ?></label>
						<input type="text" id="operator_capability" name="settings[operator_capability]" value="<?php echo esc_attr( (string) $settings['operator_capability'] ); ?>" class="regular-text fpfa-monospace-input" autocomplete="off">
						<span class="fpfa-hint"><?php esc_html_e( 'Suggerimento: lascia «manage_fp_forms» se gli stessi utenti che gestiscono FP Forms devono gestire anche gli accrediti. Solo chi ha questo permesso (o è amministratore) vede Accrediti e Accrediti Settings.', 'fp-forms-accrediti' ); ?></span>
					</div>
				</div>
			</div>
		</div>

		<div class="fpfa-card">
			<div class="fpfa-card-header">
				<span class="dashicons dashicons-feedback" aria-hidden="true"></span>
				<div class="fpfa-card-header-text">
					<h2><?php esc_html_e( 'Form che generano una richiesta accredito', 'fp-forms-accrediti' ); ?></h2>
					<p class="fpfa-card-lead"><?php esc_html_e( 'Per ogni riga: abilita il form e, se serve, indica esattamente quale campo contiene l’email del candidato.', 'fp-forms-accrediti' ); ?></p>
				</div>
			</div>
			<div class="fpfa-card-body">
				<?php if ( empty( $forms ) ) : ?>
					<div class="fpfa-alert fpfa-alert-warning">
						<span class="dashicons dashicons-warning" aria-hidden="true"></span>
						<div>
							<strong><?php esc_html_e( 'Nessun form in FP Forms', 'fp-forms-accrediti' ); ?></strong>
							<p><?php esc_html_e( 'Crea almeno un form in FP Forms → Nuovo form, poi torna qui per collegarlo.', 'fp-forms-accrediti' ); ?></p>
						</div>
					</div>
				<?php else : ?>
					<div class="fpfa-table-legend" role="note">
						<ul>
							<li><strong><?php esc_html_e( 'Abilitato:', 'fp-forms-accrediti' ); ?></strong> <?php esc_html_e( 'se spuntato, ogni nuovo invio da quel form crea una riga in Accrediti (stato In attesa).', 'fp-forms-accrediti' ); ?></li>
							<li><strong><?php esc_html_e( 'Campo email:', 'fp-forms-accrediti' ); ?></strong> <?php esc_html_e( 'inserisci lo slug del campo email definito nel costruttore del form (es. email o field_xxx). Se lasci vuoto, il sistema prova a trovare da solo un campo il cui nome contiene «email».', 'fp-forms-accrediti' ); ?></li>
						</ul>
					</div>
					<div class="fpfa-table-scroll">
						<table class="widefat striped fpfa-settings-table">
							<thead>
								<tr>
									<th scope="col" class="fpfa-col-form"><?php esc_html_e( 'Form FP Forms', 'fp-forms-accrediti' ); ?></th>
									<th scope="col" class="fpfa-col-narrow"><?php esc_html_e( 'Crea richiesta', 'fp-forms-accrediti' ); ?></th>
									<th scope="col" class="fpfa-col-email"><?php esc_html_e( 'Slug campo email', 'fp-forms-accrediti' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $forms as $form_item ) : ?>
									<?php
									$fid = (string) $form_item['id'];
									$cfg = $settings['form_configs'][ $fid ] ?? [ 'enabled' => false, 'email_field' => '' ];
									?>
									<tr>
										<td>
											<span class="fpfa-form-id">#<?php echo esc_html( $fid ); ?></span>
											<span class="fpfa-form-title"><?php echo esc_html( $form_item['title'] ); ?></span>
										</td>
										<td class="fpfa-td-center">
											<label class="fpfa-sr-only" for="fpfa_form_en_<?php echo esc_attr( $fid ); ?>"><?php echo esc_html( sprintf( /* translators: %s: form title */ __( 'Abilita accredito per %s', 'fp-forms-accrediti' ), $form_item['title'] ) ); ?></label>
											<input id="fpfa_form_en_<?php echo esc_attr( $fid ); ?>" type="checkbox" name="settings[form_configs][<?php echo esc_attr( $fid ); ?>][enabled]" value="1" <?php checked( ! empty( $cfg['enabled'] ) ); ?>>
										</td>
										<td>
											<label class="fpfa-sr-only" for="fpfa_form_em_<?php echo esc_attr( $fid ); ?>"><?php echo esc_html( sprintf( /* translators: %s: form title */ __( 'Slug campo email per %s', 'fp-forms-accrediti' ), $form_item['title'] ) ); ?></label>
											<input id="fpfa_form_em_<?php echo esc_attr( $fid ); ?>" type="text" name="settings[form_configs][<?php echo esc_attr( $fid ); ?>][email_field]" value="<?php echo esc_attr( (string) ( $cfg['email_field'] ?? '' ) ); ?>" class="regular-text fpfa-monospace-input" placeholder="<?php esc_attr_e( 'es. email (opzionale)', 'fp-forms-accrediti' ); ?>">
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<div class="fpfa-card">
			<div class="fpfa-card-header">
				<span class="dashicons dashicons-media-document" aria-hidden="true"></span>
				<div class="fpfa-card-header-text">
					<h2><?php esc_html_e( 'Documenti inviati al candidato', 'fp-forms-accrediti' ); ?></h2>
					<p class="fpfa-card-lead"><?php esc_html_e( 'Solo i file consentiti possono essere allegati alle email di approvazione.', 'fp-forms-accrediti' ); ?></p>
				</div>
			</div>
			<div class="fpfa-card-body">
				<div class="fpfa-subsection">
					<h3 class="fpfa-subsection-title"><?php esc_html_e( 'PDF predefinito per le approvazioni', 'fp-forms-accrediti' ); ?></h3>
					<p class="fpfa-subsection-desc"><?php esc_html_e( 'Se lo imposti, l’operatore non deve più scegliere un file ogni volta: viene allegato automaticamente quando approva senza selezionare un altro PDF. Resta sempre possibile scegliere un file diverso sul singolo caso.', 'fp-forms-accrediti' ); ?></p>
					<input type="hidden" id="fpfa_default_approval_attachment_id" name="settings[default_approval_attachment_id]" value="<?php echo esc_attr( (string) (int) ( $settings['default_approval_attachment_id'] ?? 0 ) ); ?>">
					<div class="fpfa-btn-row">
						<button type="button" class="button button-secondary" id="fpfa_select_default_attachment"><?php esc_html_e( 'Scegli PDF dalla libreria media', 'fp-forms-accrediti' ); ?></button>
						<button type="button" class="button" id="fpfa_clear_default_attachment"><?php esc_html_e( 'Rimuovi predefinito', 'fp-forms-accrediti' ); ?></button>
					</div>
					<p id="fpfa_default_attachment_label" class="fpfa-default-attachment-label" aria-live="polite">
						<?php
						$fpfa_def_id = (int) ( $settings['default_approval_attachment_id'] ?? 0 );
						if ( $fpfa_def_id > 0 ) {
							$fpfa_def_path = get_attached_file( $fpfa_def_id );
							$fpfa_def_name = $fpfa_def_path ? basename( $fpfa_def_path ) : '';
							if ( $fpfa_def_name !== '' ) {
								echo esc_html( sprintf( /* translators: %1$s: filename, %2$d: attachment ID */ __( 'PDF attuale: %1$s (ID %2$d)', 'fp-forms-accrediti' ), $fpfa_def_name, $fpfa_def_id ) );
							}
						}
						?>
					</p>
				</div>
				<div class="fpfa-subsection fpfa-subsection-divider">
					<h3 class="fpfa-subsection-title"><?php esc_html_e( 'Tipi di file ammessi', 'fp-forms-accrediti' ); ?></h3>
					<p class="fpfa-static-note"><?php esc_html_e( 'Gli allegati inviati con l’email di approvazione possono essere solo PDF. Non è necessario configurare nulla: è un vincolo di sicurezza del modulo.', 'fp-forms-accrediti' ); ?></p>
					<input type="hidden" name="settings[allowed_mime_types][]" value="application/pdf">
				</div>
			</div>
		</div>

		<div class="fpfa-card fpfa-card-email-templates">
			<div class="fpfa-card-header">
				<span class="dashicons dashicons-email-alt" aria-hidden="true"></span>
				<div class="fpfa-card-header-text">
					<h2><?php esc_html_e( 'Testi delle email al candidato', 'fp-forms-accrediti' ); ?></h2>
					<p class="fpfa-card-lead"><?php esc_html_e( 'Oggetto e corpo per approvazione e rifiuto: è già presente un testo predefinito chiaro e professionale. Puoi personalizzarlo liberamente; se svuoti un campo e salvi, al caricamento successivo viene ripristinato il predefinito. Il testo è convertito in HTML e, con FP Mail SMTP attivo, riceve anche il layout grafico unificato.', 'fp-forms-accrediti' ); ?></p>
				</div>
			</div>
			<div class="fpfa-card-body">
				<div class="fpfa-alert fpfa-alert-info fpfa-email-preset-notice" role="status">
					<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
					<div>
						<strong><?php esc_html_e( 'Testi preimpostati sempre disponibili', 'fp-forms-accrediti' ); ?></strong>
						<p><?php esc_html_e( 'Anche senza modifiche qui, le e-mail al candidato usano già messaggi completi (saluto, motivazione, riferimento al form e ai tag). Personalizza solo se vuoi un tono o dettagli diversi.', 'fp-forms-accrediti' ); ?></p>
					</div>
				</div>
				<div class="fpfa-tags-hint">
					<strong><?php esc_html_e( 'Tag che puoi incollare nel testo:', 'fp-forms-accrediti' ); ?></strong>
					<code>{applicant_email}</code> <code>{form_title}</code> <code>{site_name}</code> <code>{site_url}</code> <code>{date}</code> <code>{time}</code> <code>{decision_message}</code>
					<span class="fpfa-tags-hint-extra"><?php esc_html_e( '{decision_message} è il messaggio che l’operatore scrive nella schermata di approvazione/rifiuto.', 'fp-forms-accrediti' ); ?></span>
				</div>

				<div class="fpfa-email-section">
					<h3 class="fpfa-email-section-title"><?php esc_html_e( 'Email di approvazione', 'fp-forms-accrediti' ); ?></h3>
					<div class="fpfa-field">
						<label for="approval_subject"><?php esc_html_e( 'Oggetto', 'fp-forms-accrediti' ); ?></label>
						<input type="text" id="approval_subject" class="large-text" name="settings[email_templates][approval_subject]" value="<?php echo esc_attr( (string) ( $fpfa_mail_ui['approval_subject'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'Es. Accredito approvato — {site_name}', 'fp-forms-accrediti' ); ?>">
					</div>
					<div class="fpfa-field">
						<label for="approval_body"><?php esc_html_e( 'Corpo del messaggio', 'fp-forms-accrediti' ); ?></label>
						<textarea id="approval_body" class="large-text" rows="10" name="settings[email_templates][approval_body]" placeholder="<?php esc_attr_e( 'Il testo predefinito è già compilato qui sopra; puoi modificarlo liberamente.', 'fp-forms-accrediti' ); ?>"><?php echo esc_textarea( (string) ( $fpfa_mail_ui['approval_body'] ?? '' ) ); ?></textarea>
						<span class="fpfa-hint"><?php esc_html_e( 'Se nel testo non usi {decision_message}, il messaggio dell’operatore viene comunque aggiunto in coda al corpo.', 'fp-forms-accrediti' ); ?></span>
					</div>
				</div>

				<div class="fpfa-email-section">
					<h3 class="fpfa-email-section-title"><?php esc_html_e( 'Email di rifiuto', 'fp-forms-accrediti' ); ?></h3>
					<div class="fpfa-field">
						<label for="rejection_subject"><?php esc_html_e( 'Oggetto', 'fp-forms-accrediti' ); ?></label>
						<input type="text" id="rejection_subject" class="large-text" name="settings[email_templates][rejection_subject]" value="<?php echo esc_attr( (string) ( $fpfa_mail_ui['rejection_subject'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'Es. Aggiornamento richiesta accredito — {site_name}', 'fp-forms-accrediti' ); ?>">
					</div>
					<div class="fpfa-field">
						<label for="rejection_body"><?php esc_html_e( 'Corpo del messaggio', 'fp-forms-accrediti' ); ?></label>
						<textarea id="rejection_body" class="large-text" rows="10" name="settings[email_templates][rejection_body]" placeholder="<?php esc_attr_e( 'Il testo predefinito è già compilato qui sopra; puoi modificarlo liberamente.', 'fp-forms-accrediti' ); ?>"><?php echo esc_textarea( (string) ( $fpfa_mail_ui['rejection_body'] ?? '' ) ); ?></textarea>
					</div>
				</div>
			</div>
		</div>

		<div class="fpfa-settings-footer">
			<button type="submit" class="button button-primary button-hero fpfa-save-settings"><?php esc_html_e( 'Salva tutte le impostazioni', 'fp-forms-accrediti' ); ?></button>
			<p class="fpfa-save-hint"><?php esc_html_e( 'Un solo salvataggio applica modulo, form, documenti e template email.', 'fp-forms-accrediti' ); ?></p>
		</div>
	</form>
</div>
