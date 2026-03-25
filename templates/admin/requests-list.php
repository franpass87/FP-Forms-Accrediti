<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap fpfa-admin">
    <h1 class="screen-reader-text"><?php esc_html_e( 'Richieste Accrediti', 'fp-forms-accrediti' ); ?></h1>
    <div class="fpfa-page-header">
        <div class="fpfa-page-header-content">
            <h2 class="fpfa-page-header-title" aria-hidden="true"><?php esc_html_e( 'Richieste Accrediti', 'fp-forms-accrediti' ); ?></h2>
            <p class="fpfa-page-header-desc"><?php esc_html_e( 'Visualizza e gestisci le richieste di accredito in attesa.', 'fp-forms-accrediti' ); ?></p>
        </div>
        <span class="fpfa-page-header-badge">v<?php echo esc_html( defined( 'FP_FORMS_ACCREDITI_VERSION' ) ? FP_FORMS_ACCREDITI_VERSION : '0' ); ?></span>
    </div>

    <form method="get" class="fpfa-filters">
        <input type="hidden" name="page" value="fp-forms-accrediti-requests">
        <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Cerca email...', 'fp-forms-accrediti' ); ?>">
        <select name="status">
            <option value=""><?php esc_html_e( 'Tutti gli stati', 'fp-forms-accrediti' ); ?></option>
            <option value="pending" <?php selected( $filters['status'], 'pending' ); ?>><?php esc_html_e( 'Pending', 'fp-forms-accrediti' ); ?></option>
            <option value="approved" <?php selected( $filters['status'], 'approved' ); ?>><?php esc_html_e( 'Approved', 'fp-forms-accrediti' ); ?></option>
            <option value="rejected" <?php selected( $filters['status'], 'rejected' ); ?>><?php esc_html_e( 'Rejected', 'fp-forms-accrediti' ); ?></option>
        </select>
        <select name="form_id">
            <option value="0"><?php esc_html_e( 'Tutti i form', 'fp-forms-accrediti' ); ?></option>
            <?php foreach ( $forms as $form_item ) : ?>
                <option value="<?php echo esc_attr( $form_item['id'] ); ?>" <?php selected( $filters['form_id'], (int) $form_item['id'] ); ?>>
                    <?php echo esc_html( $form_item['title'] ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button class="button"><?php esc_html_e( 'Filtra', 'fp-forms-accrediti' ); ?></button>
    </form>

    <table class="wp-list-table widefat striped">
        <thead>
            <tr>
                <th><?php esc_html_e( 'ID', 'fp-forms-accrediti' ); ?></th>
                <th><?php esc_html_e( 'Submission', 'fp-forms-accrediti' ); ?></th>
                <th><?php esc_html_e( 'Form', 'fp-forms-accrediti' ); ?></th>
                <th><?php esc_html_e( 'Email', 'fp-forms-accrediti' ); ?></th>
                <th><?php esc_html_e( 'Stato', 'fp-forms-accrediti' ); ?></th>
                <th><?php esc_html_e( 'Data', 'fp-forms-accrediti' ); ?></th>
                <th><?php esc_html_e( 'Azioni', 'fp-forms-accrediti' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $requests ) ) : ?>
                <tr>
                    <td colspan="7"><?php esc_html_e( 'Nessuna richiesta trovata.', 'fp-forms-accrediti' ); ?></td>
                </tr>
            <?php else : ?>
                <?php foreach ( $requests as $item ) : ?>
                    <tr>
                        <td>#<?php echo esc_html( (string) $item->id ); ?></td>
                        <td>#<?php echo esc_html( (string) $item->submission_id ); ?></td>
                        <td>#<?php echo esc_html( (string) $item->form_id ); ?></td>
                        <td><?php echo esc_html( $item->applicant_email ); ?></td>
                        <td><span class="fpfa-status fpfa-status-<?php echo esc_attr( $item->status ); ?>"><?php echo esc_html( ucfirst( (string) $item->status ) ); ?></span></td>
                        <td><?php echo esc_html( (string) $item->created_at ); ?></td>
                        <td>
                            <a class="button button-small" href="<?php echo esc_url( add_query_arg( [ 'page' => 'fp-forms-accrediti-requests', 'request_id' => (int) $item->id ], admin_url( 'admin.php' ) ) ); ?>">
                                <?php esc_html_e( 'Apri', 'fp-forms-accrediti' ); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ( $total_pages > 1 ) : ?>
        <div class="tablenav">
            <div class="tablenav-pages">
                <?php
                echo wp_kses_post(
                    paginate_links(
                        [
                            'base' => add_query_arg( [ 'page' => 'fp-forms-accrediti-requests', 'paged' => '%#%', 'status' => $filters['status'], 'form_id' => $filters['form_id'], 's' => $search ], admin_url( 'admin.php' ) ),
                            'format' => '',
                            'current' => $paged,
                            'total' => $total_pages,
                        ]
                    )
                );
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>
