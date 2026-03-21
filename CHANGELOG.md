# CHANGELOG - FP Forms Accrediti

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
