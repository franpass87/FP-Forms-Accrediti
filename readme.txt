=== FP Forms Accrediti ===

Contributors: franpass87
Tags: forms, accreditation, workflow, approvals, email
Requires at least: 6.0
Tested up to: 6.6
Stable tag: 1.0.0
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

= 1.0.0 = (2026-03-21)
* Added: modulo completo richieste accredito (pending/approved/rejected).
* Added: pagina admin con lista, dettaglio, approva/rifiuta.
* Added: invio email approvazione con allegato e rifiuto con messaggio.
* Added: tabella audit eventi e impostazioni dedicate.
