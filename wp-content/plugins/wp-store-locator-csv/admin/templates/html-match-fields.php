<?php
/* CSV import match fields template */

if ( !defined( 'ABSPATH' ) ) exit;
?>

<div id="wpsl-import">
    <form id="wpsl-csv-fields" method="post" action="<?php echo admin_url( 'edit.php?post_type=wpsl_stores&page=wpsl_csv&section=import' ); ?>">
        <?php wp_nonce_field( 'wpsl_csv_import', 'wpsl_csv_import_nonce' ); ?>
        <input type="hidden" name="wpsl_action" value="csv_import" />
        
        <p><?php _e( 'Map the CSV headers to the WPSL fields.', 'wpsl-csv' ); ?></p>

        <?php echo $this->import->match_fields(); ?>

        <p class="wpsl-import-btn">
            <input id="wpsl-csv-import" type="submit" value="<?php _e( 'Import Locations', 'wpsl-csv' ); ?>" class="button-primary">
        </p>
    </form>
</div>