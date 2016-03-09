<?php
/* CSV import match fields template */

if ( !defined( 'ABSPATH' ) ) exit;
?>

<div id="wpsl-import">
    <form id="wpsl-csv-fields" method="post" action="<?php echo admin_url( 'edit.php?post_type=wpsl_stores&page=wpsl_csv' ); ?>">
        <?php wp_nonce_field( 'wpsl-csv-import' ); ?>
        <input type="hidden" name="wpsl_csv_action" value="import_csv" />
        
        <p><?php _e( 'Map the csv fields to the WP Store Locator fields.', 'wpsl-csv' ); ?></p>

        <?php echo $this->csv->preview_csv(); ?>

        <p class="wpsl-import-btn">
            <input id="wpsl-csv-import" type="submit" value="<?php _e( 'Import Locations', 'wpsl-csv' ); ?>" class="button-primary">
        </p>
    </form>
</div>