<?php
declare(strict_types=1);

namespace FP\FormsAccrediti\Integration;

use FP\FormsAccrediti\Domain\RequestRepository;
use FP\FormsAccrediti\Settings\Settings;

/**
 * Integrazione hook con FP Forms.
 *
 * Ascolta più varianti di hook emessi da FP Forms (nomi/firme nel tempo)
 * e normalizza il payload per garantire compatibilità con versioni storiche
 * e future. Evita dipendenze strict dai tipi dell'argomento (int vs string)
 * per non far fallire silenziosamente il listener con strict_types.
 */
final class FpFormsHooks {

    private RequestRepository $repository;

    public function __construct() {
        $this->repository = new RequestRepository();
    }

    /**
     * Registra gli hook di integrazione.
     *
     * Tre hook storici sono ascoltati tutti per la massima compatibilità:
     * - fp_forms_after_save_submission (v1.6.x+)
     * - fp_forms_after_submit          (legacy)
     * - fp_forms_submission_saved      (legacy)
     *
     * Il metodo callback è idempotente su submission_id, quindi anche
     * l'eventuale emissione di più hook per la stessa submission crea
     * al massimo una richiesta accredito.
     */
    public function register(): void {
        add_action( 'fp_forms_after_save_submission', [ $this, 'on_submission_saved' ], 10, 3 );
        add_action( 'fp_forms_after_submit', [ $this, 'on_submission_saved' ], 10, 3 );
        add_action( 'fp_forms_submission_saved', [ $this, 'on_submission_saved' ], 10, 3 );
    }

    /**
     * Crea richiesta accredito pending alla nuova submission.
     *
     * Compatibile con varianti hook FP Forms che possono cambiare nome/firma.
     * Parametri volutamente non tipizzati (mixed): con strict_types=1 un
     * mismatch di tipo in ingresso farebbe fallire il callback senza log utile.
     *
     * @param mixed $submission_id_or_payload ID submission o payload submission (array).
     * @param mixed $form_id_or_payload       ID form o payload form (array).
     * @param mixed $data_or_null             Dati submission o null.
     */
    public function on_submission_saved( $submission_id_or_payload, $form_id_or_payload = null, $data_or_null = null ): void {
        [ $submission_id, $form_id, $data ] = $this->normalize_submission_payload(
            $submission_id_or_payload,
            $form_id_or_payload,
            $data_or_null
        );

        if ( $submission_id <= 0 || $form_id <= 0 || $data === [] ) {
            return;
        }

        if ( ! Settings::is_enabled() ) {
            return;
        }

        $form_config = Settings::get_form_config( $form_id );
        if ( ! $form_config ) {
            return;
        }

        $email = $this->resolve_applicant_email( $form_id, $form_config, $data );
        if ( ! is_email( $email ) ) {
            return;
        }

        try {
            $request_id = $this->repository->create_pending_request( $submission_id, $form_id, $email );
        } catch ( \Throwable $e ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'FP Forms Accrediti: create_pending_request failed - ' . $e->getMessage() );
            }
            return;
        }

        if ( ! $request_id ) {
            return;
        }

        do_action(
            'fp_tracking_event',
            'accrediti_request_created',
            [
                'request_id'    => (int) $request_id,
                'submission_id' => $submission_id,
                'form_id'       => $form_id,
                'source_plugin' => 'fp-forms-accrediti',
                'event_id'      => 'fp_acc_req_' . (int) $request_id . '_' . time(),
            ]
        );
    }

    /**
     * Normalizza i parametri ricevuti dai diversi hook FP Forms.
     *
     * Accetta tre firme possibili:
     *  1. (int $submission_id, int $form_id, array $data)          — hook moderno
     *  2. (array $submission_payload)                               — hook legacy
     *  3. (array $submission_payload, array $form_payload, …)       — variante raramente usata
     *
     * Fa anche il cast robusto da string a int per submission_id/form_id
     * (utile quando un hook upstream emette wpdb->insert_id come stringa).
     *
     * @param mixed $submission_id_or_payload ID submission o payload submission.
     * @param mixed $form_id_or_payload       ID form o payload form.
     * @param mixed $data_or_null             Dati submission o null.
     * @return array{0:int,1:int,2:array<string,mixed>} [submission_id, form_id, data]
     */
    private function normalize_submission_payload( $submission_id_or_payload, $form_id_or_payload, $data_or_null ): array {
        $submission_id = 0;
        $form_id       = 0;
        $data          = [];

        if ( is_array( $submission_id_or_payload ) ) {
            $submission_id = isset( $submission_id_or_payload['id'] ) ? absint( (string) $submission_id_or_payload['id'] ) : 0;
            if ( $submission_id === 0 && isset( $submission_id_or_payload['submission_id'] ) ) {
                $submission_id = absint( (string) $submission_id_or_payload['submission_id'] );
            }
            $form_id = isset( $submission_id_or_payload['form_id'] ) ? absint( (string) $submission_id_or_payload['form_id'] ) : 0;
            if ( isset( $submission_id_or_payload['data'] ) && is_array( $submission_id_or_payload['data'] ) ) {
                $data = $submission_id_or_payload['data'];
            } elseif ( isset( $submission_id_or_payload['fields'] ) && is_array( $submission_id_or_payload['fields'] ) ) {
                $data = $submission_id_or_payload['fields'];
            }
        } else {
            $submission_id = is_scalar( $submission_id_or_payload ) ? absint( (string) $submission_id_or_payload ) : 0;
            $form_id       = is_scalar( $form_id_or_payload ) ? absint( (string) $form_id_or_payload ) : 0;
            if ( is_array( $data_or_null ) ) {
                $data = $data_or_null;
            }
        }

        if ( $form_id <= 0 && is_array( $form_id_or_payload ) ) {
            $form_id = isset( $form_id_or_payload['id'] ) ? absint( (string) $form_id_or_payload['id'] ) : $form_id;
        }

        if ( $data === [] && is_array( $form_id_or_payload ) && isset( $form_id_or_payload['data'] ) && is_array( $form_id_or_payload['data'] ) ) {
            $data = $form_id_or_payload['data'];
        }

        return [ $submission_id, $form_id, $data ];
    }

    /**
     * Risolve email candidato con cascata di fallback.
     *
     * Ordine di tentativi:
     * 1. Slug campo email esplicito nelle impostazioni (se configurato).
     * 2. Qualunque chiave in $data che contenga «email» nel nome (tipico
     *    dei campi FP Forms con slug timestampato tipo email_1776015900684).
     * 3. Primo campo di tipo «email» nello schema form (se disponibile).
     *
     * @param int                  $form_id     ID form FP Forms.
     * @param array<string, mixed> $form_config Config da Settings::get_form_config (enabled già verificato).
     * @param array<string, mixed> $data        Dati submission sanitizzati.
     */
    private function resolve_applicant_email( int $form_id, array $form_config, array $data ): string {
        $email_field = $form_config['email_field'] ?? '';
        if ( $email_field !== '' ) {
            $raw = $data[ $email_field ] ?? null;
            if ( is_scalar( $raw ) && is_email( (string) $raw ) ) {
                return sanitize_email( (string) $raw );
            }
        }

        foreach ( $data as $key => $value ) {
            if ( ! is_scalar( $value ) ) {
                continue;
            }

            $string_value = (string) $value;
            if ( stripos( (string) $key, 'email' ) !== false && is_email( $string_value ) ) {
                return sanitize_email( $string_value );
            }
        }

        $from_schema = $this->resolve_email_from_form_schema( $form_id, $data );
        if ( $from_schema !== '' ) {
            return $from_schema;
        }

        return '';
    }

    /**
     * Primo campo di tipo «email» nel builder FP Forms con valore valido in $data.
     *
     * @param int                  $form_id ID form FP Forms.
     * @param array<string, mixed> $data    Dati submission.
     */
    private function resolve_email_from_form_schema( int $form_id, array $data ): string {
        if ( ! class_exists( '\FPForms\Plugin' ) ) {
            return '';
        }

        try {
            $plugin = \FPForms\Plugin::instance();
        } catch ( \Throwable $e ) {
            return '';
        }

        if ( ! isset( $plugin->forms ) || ! is_object( $plugin->forms ) || ! method_exists( $plugin->forms, 'get_fields' ) ) {
            return '';
        }

        $fields = $plugin->forms->get_fields( $form_id );
        if ( ! is_array( $fields ) ) {
            return '';
        }

        foreach ( $fields as $field ) {
            if ( ! is_array( $field ) ) {
                continue;
            }
            if ( ( $field['type'] ?? '' ) !== 'email' ) {
                continue;
            }
            $name = isset( $field['name'] ) ? (string) $field['name'] : '';
            if ( $name === '' ) {
                continue;
            }
            $raw = $data[ $name ] ?? null;
            if ( is_scalar( $raw ) && is_email( (string) $raw ) ) {
                return sanitize_email( (string) $raw );
            }
        }

        return '';
    }
}
