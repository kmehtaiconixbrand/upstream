<?php
if ( !defined( 'ABSPATH' ) ) exit;
// @todo anders in elkaar zetten met html ob_start...
$section = ( isset( $_GET['section'] ) ) ? $_GET['section'] : '';

// Handle the uploaded csv file.
if ( isset( $_FILES['wpsl_csv_file'] ) ) {

    check_admin_referer( 'wpsl-csv-upload' );
    
    if ( !current_user_can( 'manage_wpsl_settings' ) ) {
        return;
    }
    
    if ( $_FILES['wpsl_csv_file']['error'] == 0 ) {
        if ( move_uploaded_file( $_FILES['wpsl_csv_file']['tmp_name'], $this->csv->file_path ) ) {
            $section = 'match-fields';
        } else {
            $upload_error = sprintf( __( 'Could not move the uploaded CSV file to the %s folder. Please make sure the folder is writable!', 'wpsl-csv' ), WPSL_CSV_UPLOAD_DIR );
        }
    } else {
        switch ( $_FILES['wpsl_csv_file']['error'] ) {
            case UPLOAD_ERR_INI_SIZE:
                $upload_error = __( 'The uploaded file exceeds the upload_max_filesize directive in php.ini', 'wpsl-csv' );
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $upload_error = __( 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form', 'wpsl-csv' );
                break;
            case UPLOAD_ERR_NO_FILE:
                $upload_error = __( 'No file was uploaded', 'wpsl-csv' );
                break; 
            case UPLOAD_ERR_PARTIAL:
                $upload_error = __( 'The uploaded file was only partially uploaded', 'wpsl-csv' );
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $upload_error = __( 'Missing a temporary folder', 'wpsl-csv' );
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $upload_error = __( 'Failed to write file to disk', 'wpsl-csv' );
                break;
            case UPLOAD_ERR_EXTENSION:
                $upload_error = __( 'File upload stopped by extension', 'wpsl-csv' );
                break;
        }
    }
} 

if ( isset( $_POST['wpsl_csv_action'] ) ) {
    switch ( $_POST['wpsl_csv_action'] ) {
        case 'import_csv':
            $this->csv->import_csv();
            break;
        case 'export_csv':
            $this->csv->export_csv();
            break;        
    }
}

// If any upload errors occured, then show them in an error notice.
if ( isset( $upload_error ) ) {
    echo '<div class="error"><p>' . $upload_error . '</p></div>';
}

// Show the the OVER_QUERY_LIMIT notice if the 'geocode_error' url param is set.
if ( isset( $_GET['geocode_error'] ) && $_GET['geocode_error'] == 'over_query_limit' ) {
    echo '<div class="error">';
    echo '<p><strong>' . __( 'Too many OVER_QUERY_LIMIT errors.', 'wpsl-csv' ) . '</strong></p>';
    echo '<p>' . sprintf( __( 'It looks like you exceeded the Google Maps API %susage limits%s for today. The usage limits are reset at midnight, Pacific Time.', 'wpsl-csv' ), '<a href="https://developers.google.com/maps/documentation/geocoding/usage-limits">', '</a>' ) . '</p>';
    echo '<p>' . sprintf( __( 'If you repeatedly see this error, then you can raise the usage limits by setting an %sAPI key%s and %senable billing%s.', 'wpsl-csv' ), '<a href="https://developers.google.com/maps/documentation/geocoding/get-api-key#key">', '</a>', '<a href="https://developers.google.com/maps/documentation/geocoding/usage-limits#increase-your-quota-by-enabling-pay-as-you-go-billing">', '</a>' ) . '</p>';
    echo '<p>' . sprintf( __( 'You can also try to use a third-party %sservice%s to geocode the addresses before importing the CSV file.', 'wpsl-csv' ), '<a href="http://findlatitudeandlongitude.com/batch-geocode/">', '</a>' ) . '</p>';
    echo '</div>';    
}

$wp_field_map = $this->csv->wp_post_field_map();
$meta_fields  = $this->csv->get_field_names( false );


//@todo export filter check, zip/country/ etc
// Grab all the wpsl_stores
//$locations = get_posts( array( 'post_type' => 'wpsl_stores', 'posts_per_page' => -1 ) );

$args = array(
    'meta_key' => 'wpsl_city',
    'meta_value' => 'Almere',
    'post_type' => 'wpsl_stores',
    'post_status' => 'any',
    'posts_per_page' => -1
);

$locations = get_posts($args);

foreach ( $locations as $location ) {
    
    echo '<pre>';
    print_r( $location );
    echo '</pre>';

//    // Get the post data, like the name, id, author, permalink etc
//    foreach ( $wp_field_map as $csv_header => $wp_field ) {
//        $post_data[$csv_header] = $location->$wp_field;
//    }
//
//    $meta_data = get_post_custom( $location->ID );
//
//    // Loop over the wpsl meta fields, and sort the meta data.
//    foreach ( $meta_fields as $meta_field ) {
//        if ( isset( $meta_data['wpsl_' . $meta_field][0] ) ) {
//            $post_meta[$meta_field] = $meta_data['wpsl_' . $meta_field][0]; // maybe_unserialize
//        } else {
//            $post_meta[$meta_field] = '';
//        }
//    }
    

    // post category / featured image
    // $location_data[] = array_merge( $post_data, $post_meta );
}

//$term_names = wp_get_object_terms( 34062, 'wpsl_store_category', array( 'fields' => 'names' ) );
//$term_count = count( $term_names );
//$terms      = '';
//
//if ( $term_count == 1 ) {
//    $terms = $term_names[0];
//} elseif ( $term_count > 1 ) {
//    $terms = implode( '|', $term_names );
//}
//
//echo '<br>';
//echo '$terms';
//echo '<pre>';
//print_r( $terms );
//echo '</pre>';
//echo '<br>';
//echo '<pre>';
//print_r( $location_data );
//echo '</pre>';
//echo '<br>';
//echo '$post_thumbnail_id = ' . $post_thumbnail_id = get_post_thumbnail_id( 34063 );
//echo '<br>';
?>

<div class="wrap wpsl-csv">
    <h2><?php _e( 'CSV Import & Export', 'wpsl-csv' ); ?></h2>

    <h2 class="nav-tab-wrapper" id="wpsl-tabs">
        <a class="nav-tab <?php if ( in_array( $section, array( 'import', 'match-fields', '' ) ) ) { echo 'nav-tab-active'; } ?>" href="<?php echo admin_url( 'edit.php?post_type=wpsl_stores&page=wpsl_csv&section=import' ); ?>"><?php _e( 'Import', 'wpsl-csv' ); ?></a>
        <a class="nav-tab <?php if ( $section == 'export' ) { echo 'nav-tab-active'; } ?>" href="<?php echo admin_url( 'edit.php?post_type=wpsl_stores&page=wpsl_csv&section=export' ); ?>"><?php _e( 'Export', 'wpsl-csv' ); ?></a>
    </h2>
    
    <?php 
    switch ( $section ) {
        case 'export':
            require_once( WPSL_CSV_PLUGIN_DIR . 'admin/templates/html-export.php' );
            break;
        case 'match-fields':
            require_once( WPSL_CSV_PLUGIN_DIR . 'admin/templates/html-import-match.php' );
            break;
        case 'import':
        default:
            require_once( WPSL_CSV_PLUGIN_DIR . 'admin/templates/html-import.php' );
            break;
    }
    ?>        
</div>