<<<<<<< HEAD
# FP-Forms-Accrediti
=======
# FP Forms Accrediti

Add-on opzionale per `FP Forms` che aggiunge un workflow di richieste accredito: creazione richiesta pending da submission, revisione operatore, approvazione/rifiuto con email dedicata e allegato.

## Requisiti

- WordPress 6.0+
- PHP 8.0+
- Plugin `FP Forms` attivo

## Funzionalita'

- Creazione automatica richiesta accredito da nuove submission (solo form abilitati)
- Stato richiesta: `pending`, `approved`, `rejected`
- Pannello admin richieste con filtri e dettaglio
- Approvazione con allegato via Media Library
- Rifiuto con messaggio dedicato
- Audit eventi per tracciamento operatore
- Template email configurabili

## Installazione

1. Carica la cartella `FP-Forms-Accrediti` in `wp-content/plugins/`.
2. Assicurati che `FP Forms` sia attivo.
3. Attiva `FP Forms Accrediti`.
4. Vai in `FP Forms -> Accrediti Settings` e abilita i form desiderati.

## Architettura

- `src/Integration/FpFormsHooks.php` intercetta l'hook submit di FP Forms
- `src/Domain/RequestRepository.php` gestisce persistenza richieste/audit
- `src/Service/DecisionService.php` orchestra approvazione/rifiuto
- `src/Service/Mailer.php` invia email con/senza allegati
- `src/Admin/RequestsController.php` espone pagine e azioni admin

## Changelog

Vedi `CHANGELOG.md`.

## Autore

**Francesco Passeri**
- Sito: [francescopasseri.com](https://francescopasseri.com)
- Email: [info@francescopasseri.com](mailto:info@francescopasseri.com)
- GitHub: [github.com/franpass87](https://github.com/franpass87)
>>>>>>> ef47045 (feat: bootstrap FP Forms Accrediti addon (v 1.0.0))
