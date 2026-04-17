=== FP Forms Accrediti ===

Contributors: franpass87
Tags: forms, accreditation, workflow, approvals, email
Requires at least: 6.0
Tested up to: 6.6
Stable tag: 1.0.12
Requires PHP: 8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add-on per FP Forms che introduce workflow richieste accredito con revisione operatore, approvazione/rifiuto e invio email con allegato.

== Description ==

FP Forms Accrediti estende FP Forms senza modificarne il core.

Include:
* creazione richiesta accredito `pending` su nuove submission (form abilitati)
* lista richieste e dettaglio in admin
* approvazione con allegato accredito
* rifiuto con messaggio dedicato
* audit eventi operatore
* impostazioni per form abilitati, campo email e template email

== Installation ==

1. Carica la cartella plugin in `wp-content/plugins/`.
2. Attiva prima `FP Forms`.
3. Attiva `FP Forms Accrediti`.
4. Configura in `FP Forms -> Accrediti Settings`.

== Changelog ==

= 1.0.12 = (2026-04-17)
* Fix: richieste accredito non create quando FP Forms emette l'hook `fp_forms_after_save_submission` con argomenti non strettamente tipizzati (submission_id string da `wpdb->insert_id`). Ascolto multi-hook e normalizzazione payload.
* Fix: compatibilit√† con varianti legacy `fp_forms_after_submit` e `fp_forms_submission_saved`.
* Changed: bootstrap plugin resiliente a ordini di caricamento anomali. Se FP Forms non √® visibile su `plugins_loaded`, l'integrazione viene registrata su `init` (priority 20).
* Changed: `create_pending_request` protetto da `try/catch` per non interrompere la submission in caso di errore DB.

= 1.0.11 = (2026-04-13)
* Fix: risoluzione email anche tramite primo campo tipo Email del form se slug opzionale assente/errato; testo guida in Accrediti Settings.

= 1.0.10 = (2026-04-04)
* Changed: event_id negli eventi fp_tracking_event per deduplica GA4; grafica admin allineata al design system FP.
* Fixed: ripristino template email senza form annidato.

= 1.0.9 = (2026-04-04)
* Fixed: correzione automatica segnaposto email di test (Subj A/Body A ecc.) e pulsante ripristino testi predefiniti.

= 1.0.8 = (2026-04-04)
* Fixed: in Accrediti Settings i template email approvazione/rifiuto mostrano il testo predefinito nei campi (value/textarea), non solo come hint.

= 1.0.7 = (2026-04-04)
* Changed: testi email predefiniti al candidato pi+¶ completi; campi vuoti ripristinano sempre il predefinito.
* Added: nota in impostazioni sui template preimpostati.

= 1.0.6 = (2026-04-04)
* Changed: Accrediti Settings pi+¶ chiara (card, testi guida, legenda tabella, CSS admin).

= 1.0.5 = (2026-04-04)
* Added: allegato PDF predefinito in Accrediti Settings, usato automaticamente in approvazione se l'operatore non sceglie un file.

= 1.0.4 = (2026-03-24)
* Changed: email approvazione/rifiuto in HTML con branding FP Mail SMTP (`fp_fpmail_brand_html`) se disponibile.

= 1.0.3 = (2026-03-22)
* Added: placeholder nei template email ({form_title}, {site_name}, {date}, {decision_message}, ecc.).
* Changed: UI migliorata per template email in settings con card e hint tag.

= 1.0.2 = (2026-03-22)
* Fix: 404 su pagine Accrediti e Accrediti Settings risolto con priorit+· admin_menu corretta.

= 1.0.1 = (2026-03-21)
* Fix: permessi menu/admin Accrediti allineati a capability `manage_fp_forms`.
* Fix: fallback permessi esteso in `can_manage_accrediti()`.

= 1.0.0 = (2026-03-21)
* Added: modulo completo richieste accredito (pending/approved/rejected).
* Added: pagina admin con lista, dettaglio, approva/rifiuta.
* Added: invio email approvazione con allegato e rifiuto con messaggio.
* Added: tabella audit eventi e impostazioni dedicate.
