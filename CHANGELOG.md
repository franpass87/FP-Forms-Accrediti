# CHANGELOG - FP Forms Accrediti

## [1.0.3] - 2026-03-22
### Added
- Placeholder nei template email: `{applicant_email}`, `{form_title}`, `{site_name}`, `{site_url}`, `{date}`, `{time}`, `{decision_message}`.
### Changed
- Migliorata UI sezione template email in Accrediti Settings: card con descrizione, hint tag e layout a sezioni.
- Default template email più ricchi e professionali con struttura saluto/chiusura.

## [1.0.2] - 2026-03-22
### Fixed
- Fix 404 su pagine Accrediti e Accrediti Settings: priorità `admin_menu` impostata a 20 per registrare i submenu dopo FP Forms.

## [1.0.1] - 2026-03-21
### Fixed
- Corretto il controllo permessi admin del menu Accrediti: rimosso gate in registrazione submenu e allineata capability a `manage_fp_forms`.
- Esteso fallback permessi in `Permissions::can_manage_accrediti()` includendo `manage_fp_forms` oltre a capability custom e `manage_options`.

## [1.0.0] - 2026-03-21
### Added
- Bootstrap plugin add-on separato con dependency guard su FP Forms.
- Schema DB dedicato richieste accredito e tabella audit eventi.
- Integrazione hook `fp_forms_after_save_submission` con creazione richiesta pending idempotente.
- UI admin completa: lista richieste filtrabile, dettaglio richiesta, approvazione/rifiuto.
- Decision service con invio email approvazione (allegato) e rifiuto (messaggio dedicato).
- Pagina impostazioni: toggle modulo, mapping form/email, template email e MIME consentiti.

### Security
- Nonce/capability check su azioni admin e salvataggi.
- Sanitizzazione impostazioni, input decisione e validazione MIME allegati.
