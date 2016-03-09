<?php
/* CSV import template */

if ( !defined( 'ABSPATH' ) ) exit;

$max_upload_size = wp_max_upload_size();
?> 

<div id="wpsl-import">
    <form enctype="multipart/form-data" method="post" action="<?php echo admin_url( 'edit.php?post_type=wpsl_stores&page=wpsl_csv' ); ?>">
        <?php 
        wp_nonce_field( 'wpsl_csv_upload', 'wpsl_csv_upload_nonce' );

        if ( !is_writable( WPSL_CSV_IMPORT_DIR ) ) {
            echo '<div class="error">';
            echo '<p>' . sprintf( __( '%sWarning!%s Before you can upload your CSV file, you need to make sure the %s directory is writeable.', 'wpsl-csv' ), '<strong>', '</strong>', '<code>' . WPSL_CSV_IMPORT_DIR . '</code>' ) . '</p>';
            echo '</div>';  
        }
        ?>
        
        <p><?php echo sprintf( __( 'Before getting started %sprepare%s your CSV file, and view the %snotes%s.', 'wpsl-csv' ), '<a href="http://wpstorelocator.co/document/csv-manager/#import">', '</a>', '<a href="http://wpstorelocator.co/document/csv-manager/#notes">', '</a>' ); ?></p>
        <p>
            <label for="wpsl-csv-file"><?php _e( 'Select CSV file', 'wpsl-csv' ); ?>:</label> 
            <input type="file" name="wpsl_csv_file" id="wpsl-csv-file" accept="text/csv">
            <input type="hidden" name="max_file_size" value="<?php echo apply_filters( 'import_upload_size_limit', $max_upload_size ); ?>" />
            <input type="hidden" name="wpsl_action" value="csv_upload" />
        </p> 
        <em><?php printf( __( 'Maximum file size: %s.', 'wpsl-csv' ), size_format( $max_upload_size ) ); ?></em>
        <p><input type="submit" value="<?php _e( 'Upload File', 'wpsl-csv' ); ?>" class="button-primary"></p>
    </form>
</div>