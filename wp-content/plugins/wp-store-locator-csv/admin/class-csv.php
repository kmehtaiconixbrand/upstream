<?php
/**
 * Handle WPSL CSV Import / Export
 *
 * @since 1.0.0
 */

// @todo delete transient calls uitzoeken...

if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'WPSL_CSV' ) ) {
    
	class WPSL_CSV {
        
        /**
         * @since 1.0.0
         * @var WPSL_Parse_CSV $parse_csv
         */
        public $parse_csv;
        
        /**
         * @since 1.0.0
         * @var WPSL_Geocode $geocode
         */
        public $geocode;
        
        /**
         * Class constructor
         */
        function __construct() {

            $this->geocode   = new WPSL_Geocode(); // From the WP Store Locator plugin
            $this->parse_csv = new parseCSV();

            $this->file_path = WPSL_CSV_IMPORT_DIR . 'import.csv';
            
            add_action( 'init', array( $this, 'start_output_buffer' ) );
        }
        
        /**
         * Prevent the 'headers already sent' msg if wp_redirect 
         * is used and a notice is shown from another plugin.
         * 
         * @since 1.0.0
         * @return void
         */
        public function start_output_buffer() {
            // @todo andere oplossing voor zoeken!!
            if ( isset( $_GET['page'] ) && $_GET['page'] == 'wpsl_csv' ) {
                ob_start();
            }
        }

        /**
         * Allow users to match the CSV headers with the WPSL meta fields.
         *
         * @since 1.0.0
         * @return string|void $csv_select_list One row of CSV data | redirect if an error occured
         */
        public function match_fields() {

            $this->parse_csv->auto( $this->file_path );

            // Make sure we have data to process.
            if ( !empty( $this->parse_csv->data ) ) {
                $csv_select_list = '<table>';
                $csv_select_list .= '<tr>';
                $csv_select_list .= '<th>'. __( 'CSV Headers', 'wpsl-csv' ) .'</th>';
                $csv_select_list .= '<th>'. __( 'WPSL Fields', 'wpsl-csv' ) .'</th>';
                $csv_select_list .= '</tr>';
                
                /*
                 * Loop over the titles from the imported CSV file, and create a dropdown where 
                 * users can match the CSV headers with the available WPSL data fields.
                 */
                foreach ( $this->parse_csv->titles as $index => $title ) {
                    if ( isset( $title ) && $title ) {
                        $csv_select_list .= '<tr><td class="wpsl-csv-header">' . esc_html( stripslashes( $title ) ) . '</td><td>' . $this->create_csv_select_fields( $index, $title ) . '</td></tr>';
                    }
                }
                
                $csv_select_list .= '</table>';

                return $csv_select_list;
                
            } else {
               //@todo error handling voor leeg bestand.
            }
        }

        /**
         * Built the dropdown list for the CSV preview with the available field names.
         *
         * @since 1.0.0
         * @param  integer $index  The value for the 'name' attribute
         * @param  string  $title  The title of the csv column
         * @return string  $select The html for the dropdown
         */
		public function create_csv_select_fields( $index, $title ) {
            
            $field_names = $this->get_field_names();
            
			$select = "<select name='csv_fields[" . absint( $index ) . "]'>";
			$select .= "<option value=''>". __( 'Unmapped', 'wpsl-csv' ) . "</option>";
            
			foreach ( $field_names as $k => $field_name ) {
				$select .= "<option value='" . esc_attr( $field_name ) . "' " . selected( $field_name, trim( $title ), false ) . " >" . esc_html( $field_name ) . "</option>";
			}
			
			$select .= "</select>";
			
			return $select;
		}
        
        /**
         * Process the imported CSV file.
         *
         * @since 1.0.0
         * @return void
         */
        public function import_csv() {

            //@todo duplicate selected field check toevoegen?

            global $wpsl_settings; // From the WP Store Locator plugin
            
            check_admin_referer( 'wpsl-csv-import' );
            
            if ( !current_user_can( 'manage_wpsl_settings' ) ) {
                return;
            }
            
            // Try to disable the time limit to prevent timeouts.
            @set_time_limit( 0 );
            
            //$time_start = microtime( true );

            $csv_fields       = $_POST['csv_fields'];
            $csv_field_types  = array();
            $store_locations  = array();
            $failed_locations = array();
            $success_count    = 0;
            
            // Load the CSV file.
            $this->parse_csv->auto( $this->file_path );

            // Get the WPSL field names.
            $field_names = $this->get_field_names();

            // Loop over the list of WPSL field names, and map it to the selected dropdown values.
            foreach ( $field_names as $index => $field_name ) {
                $csv_field_types[$field_name] = array_search( $field_name, $csv_fields );
            }

            // Loop over the store date from the CSV file.
            foreach ( $this->parse_csv->data as $csv_row => $csv_data ) {
                $csv_data = array_values( $csv_data );

                // Map the selected field types to the correct store data fields.
                foreach ( $csv_field_types as $index => $field_type ) {
                    if ( is_numeric( $field_type ) ) {
                        $sorted_csv_data[$index] = wp_slash( $csv_data[$field_type] ); //@todo testen of nodig is?
                    } else {
                        $sorted_csv_data[$index] = '';
                    }
                }

                array_push( $store_locations, $sorted_csv_data );
            }

            // Get the post meta fields, but remove the 'hours' field. This field requires special attention.
            $meta_keys = array_values( array_diff( $this->get_field_names( false ), array( 'hours' ) ) );
            
            // Get the fields that are used as the args for wp_insert_post / wp_update_post.
            $post_fields = array_flip( $this->wp_post_field_map() );

            // Loop over the collected store locations and save them.
            foreach ( $store_locations as $store_location ) {

                // If the name, address and city fields are empty, then skip this row.
                if ( !$this->check_required_fields( $store_location ) ) {
                    continue;
                }
                
                $add_data  = true;
                $post_args = $this->get_post_args( $post_fields, $store_location );
                $wpsl_id   = isset( $post_args['ID'] ) ? $post_args['ID'] : '';

                /*
                 * Check if we need to create a new store location,
                 * or update existing location data. 
                 * 
                 * Updating existing location data requires a wpsl_id 
                 * that is assigned to a 'wpsl_stores' post type.
                 */
                if ( !strlen( trim( $wpsl_id ) ) ) {
                    $post_args['post_type'] = 'wpsl_stores';
                    
                    // If no post_status is provided we default to 'publish'.
                    if ( !isset( $post_args['post_status'] ) ) {
                        $post_args['post_status'] = 'publish';
                    }

                    $post_id = wp_insert_post( $post_args, true );
                } else if ( is_numeric( $wpsl_id ) ) {

                    /* 
                     * Check if the imported 'wpsl_id' belongs 
                     * to a 'wpsl_stores' post types. 
                     * 
                     * If this is not the case, then we create a WP_Error.
                     */
                    if ( get_post_type( $wpsl_id ) == 'wpsl_stores' ) {
                        $add_data = false;
                        $post_id  = wp_update_post( $post_args, true );
                    } else { //@todo anders doen, als geen valid ID is toch toevoegen ipv overslaan?
                        $post_id = new WP_Error( 'invalid_id', sprintf( __( 'Update failed! The provided wpsl_id %s doesn\'t belong to a store location.', 'wpsl-csv' ), $store_location['wpsl_id'] ) );
                    }
                } else {
                    $post_id  = '';
                }
                
                if ( !is_wp_error( $post_id ) && $post_id ) {
                    
                    /*
                     * Save / update ( based on the $add_data true|false ) the post meta fields that 
                     * don't require any special attention. Like the phone, fax, email, url and any 
                     * custom fields that where added by filters.
                     */
                    foreach ( $meta_keys as $meta_key ) {
                        if ( isset( $store_location[$meta_key] ) && !empty( $store_location[$meta_key] ) ) {
                            $this->process_location_meta( $post_id, $meta_key, $store_location[$meta_key], $add_data );
                        } else {
                            delete_post_meta( $post_id, 'wpsl_' . $meta_key );
                        }
                    }
                    
                    /*
                     * Check if we need to assign the store location to one or more categories.
                     * 
                     * If the category field is empty, and we are updating 
                     * existing location data, then we remove the existing terms.
                     */
                    if ( isset( $store_location['category'] ) && $store_location['category'] ) {

                        // Do we have multiple categories?
                        if ( strpos( $store_location['category'], '|' ) !== false ) {
                            $categories = explode( '|', $store_location['category'] );
                            
                            foreach ( $categories as $category ) {
                                wp_set_object_terms( $post_id, $category, 'wpsl_store_category', true );
                            }
                        } else {
                            wp_set_object_terms( $post_id, $store_location['category'], 'wpsl_store_category' );
                        }
                    } else if ( !$add_data ) {
                        wp_set_object_terms( $post_id, NULL, 'wpsl_store_category' );
                    }

                    /*
                     * If we have an image, set it as the featured image.
                     * 
                     * Otherwise check if we are updating an existing store location.
                     * 
                     * If this is the case, and the 'image' field in the imported data is left empty, 
                     * but the current post id has an existing post thumbnail, 
                     * then we delete the old post thumbnail.                     
                     */
                    if ( isset( $store_location['image'] ) && $store_location['image'] ) {

                        /*
                         * If the image field contains an ID, then use it to set the post thumbnail. 
                         * Otherwise try to download the image before setting the post thumbnail.   
                         */
                        if ( is_numeric( $store_location['image'] ) ) {
                            set_post_thumbnail( $post_id, $store_location['image'] );
                        } else {
                            $this->set_featured_image( $post_id, $store_location['image'] );
                        }
                    } else if ( !$add_data && has_post_thumbnail( $post_id ) ) {
                        delete_post_thumbnail( $post_id );
                    }

                    // Either process the imported opening hours, or use the default hours from the settings page.
                    if ( isset( $store_location['hours'] ) && $store_location['hours'] ) {

                        /*
                         * Imported opening hours for the dropdown input need to be formated 
                         * in a specific way before we can show them in dropdowns in the wp-admin area.
                         */
                        if ( $wpsl_settings['editor_hour_input'] == 'dropdown' ) {
                            $store_location['hours'] = $this->format_opening_hours( $store_location['hours'] );
                        }

                        $this->process_location_meta( $post_id, 'hours', $store_location['hours'], $add_data );
                    } else {

                        /*
                         * If no openings hour are provided, then we use the defaults from the settings page.
                         * Only users who upgraded from WPSL 1.x can have the textarea option.  
                         */
                        if ( $wpsl_settings['editor_hour_input'] == 'dropdown' ) {
                            $default_hours = $wpsl_settings['editor_hours']['dropdown'];
                        } else {
                            $default_hours = $wpsl_settings['editor_hours']['textarea'];                            
                        }

                        $this->process_location_meta( $post_id, 'hours', $default_hours, $add_data );
                    }

                    // Check if we need to geocode the provided location details before saving the geolocation data.
                    if ( empty( $store_location['lat'] ) || empty( $store_location['lng'] ) ) {
                        $this->geocode_imported_data( $post_id, $store_location );
                    } else {
                        $this->add_geolocation_meta_data( $post_id, $store_location );
                    }
                    
                    $success_count++;
                } else {
                    if ( is_wp_error( $post_id ) ) {
                        $error_msg = $post_id->get_error_message();
                    } else {
                        $error_msg = sprintf( __( 'Update failed! Make sure %s is a valid wpsl_id', 'wpsl-csv' ), esc_html( $wpsl_id ) );
                    }
                    
                    $failed_locations[] = array(
                        'location' => $store_location['address'] . ' ' . $store_location['city'],
                        'msg'      => $error_msg
                    );
                }
            } // end foreach
            
            // Show how many locations where imported succesfully.
            if ( $success_count ) {
                echo '<div class="updated"><p>' . sprintf( _n( 'Successfully processed %d %slocation%s.', 'Successfully processed %d %slocations%s.', $success_count, 'wpsl-csv' ), $success_count, '<a href="' . admin_url( 'edit.php?post_type=wpsl_stores' ) . '">', '</a>' ) . '</p></div>';
            }
            
            // Show how many locations failed to import.
            if ( !empty( $failed_locations ) ) {
                $failed_count = count( $failed_locations );
                
                echo '<div class="error">';
                echo '<p><strong>' . sprintf( _n( 'Failed to process the following location.', 'Failed to process the following locations.', $failed_count, 'wpsl-csv' ) ) . '</strong></p>';

                foreach ( $failed_locations as $failed_location ) {
                    echo '<p>' . esc_html( $failed_location['location'] ) . '<br><strong>' . __( 'Returned error', 'wpsl-csv' ) . ':</strong> ' . $failed_location['msg'] . '</p>';
                }
                
                echo '</div>'; 
            }
            
            // If we have geocode errors, then we show them.
            if ( !empty( $this->geocode_errors ) ) {
                $error_fields = array(
                    'zero_results' => __( 'The geocoder returned no results for the following addresses', 'wpsl-csv '),
                    'failed'       => __( 'Geocoding errors', 'wpsl-csv ')
                );

                echo '<div class="error">';
                
                foreach ( $error_fields as $field_key => $error_field ) {
                    if ( isset( $this->geocode_errors[$field_key] ) ) {
                        echo '<p><strong>' . esc_html( $error_fields[$field_key] ). '</strong></p>';
                        
                        echo '<ul style="padding-left:2px;">';
                        
                        foreach ( $this->geocode_errors[$field_key] as $geocode_error ) {
                            echo '<li>' . $geocode_error . '</li>';
                        }

                        echo '</ul>';   
                        
                        if ( $field_key == 'failed' ) {
                            echo '<p>' . sprintf( __( 'Read more about the different %sstatus codes%s.', 'wpsl-csv' ), '<a href="https://developers.google.com/maps/documentation/geocoding/intro#StatusCodes">', '</a>' ) . '</p>';
                        }
                    }
                }
                
                echo '</div>';
            }

            // Remove the uploaded file
            if ( file_exists( $this->file_path ) ) {
                unlink( $this->file_path );
            }
            
//            $time_end = microtime( true );
//            $time = $time_end - $time_start;
//
//            echo "Run for $time seconds\n";
        }
        
        /**
         * Check if the imported CSV data contains fields that 
         * we need to include in the args for wp_insert_post or wp_update_post.
         * 
         * @since 1.0.0
         * @param array  $post_fields    List of post arguments
         * @param array  $store_location Store location data
         * @return array $post_args
         */
        public function get_post_args( $post_fields, $store_location ) {
            
            $post_args = array();

            foreach ( $post_fields as $arg => $csv_header ) {
                if ( isset( $store_location[$csv_header] ) && $store_location[$csv_header] ) {
                    $post_args[$arg] = $store_location[$csv_header];
                }
            }

            return $post_args;
        }
        
        /**
         * Add or update the post meta data for the store location
         * 
         * @since 1.0.0
         * @param integer $post_id    The id of current location
         * @param string  $meta_key   The location meta key
         * @param string  $meta_value The location meta value 
         * @param bool    $add_data   True is we need to add the data, false is we need update it.
         * @return void
         */  
        public function process_location_meta( $post_id, $meta_key, $meta_value, $add_data ) {

            if ( $add_data  ) {
                add_post_meta( $post_id, 'wpsl_' . $meta_key, $meta_value );   
            } else {
                update_post_meta( $post_id, 'wpsl_' . $meta_key, $meta_value );                
            }
        }
        
        /**
         * Check if the CSV row contains the required data.
         *
         * @since 1.0.0
         * @param array $store_location The store location data
         * @return boolean
         */
        public function check_required_fields( $store_location ) {
            
            if ( !empty( $store_location['name'] ) && ( !empty( $store_location['address'] ) ) && ( !empty( $store_location['city'] ) ) ) {
               return true;
            } 
        }
        
        /**
         * Geocode the imported CSV data.
         * 
         * @since 1.0.0
         * @param integer $post_id        The id of current location
         * @param array   $store_location The location meta data
         * @return void
         */  
        public function geocode_imported_data( $post_id, $store_location ) {
           
            $attempts       = 3;
            $throttle_speed = 100000;
            $loop_count     = 1;
            $delay          = 0;

            // Make a request to the Geocode API to get the latlng values,
            $geocode_response = $this->geocode->get_latlng( $store_location );
            
            if ( isset( $geocode_response['status'] ) ) {

                // Check the geocode response.
                switch ( $geocode_response['status'] ) {
                    case 'OK':
                        $this->process_geolocation_response( $post_id, $geocode_response, $store_location );                  
                        break;
                    case 'ZERO_RESULTS':
                        $this->geocode_errors['zero_results'][] = sprintf( __( '%s, %s. %sEdit details%s', 'wpsl-csv' ), esc_html( $store_location['name'] ), esc_html( $store_location['address'] ), '<a href="' . admin_url( 'post.php?post=' . esc_attr( $post_id ) . '&action=edit' ) . '">', '</a>' );
                        
                        break;
                    case 'OVER_QUERY_LIMIT':
                        
                        /* 
                         * If we hit the OVER_QUERY_LIMIT error, then we retry the 
                         * same address three times in a row. 
                         * 
                         * If it keeps failing we stop the import script.
                         * 
                         * See https://developers.google.com/maps/documentation/business/articles/usage_limits#limitexceeded
                         */
                        while ( $loop_count <= $attempts ) {
                            if ( $loop_count == 3 ) {
                                wp_redirect( add_query_arg( array( 'geocode_error' => 'over_query_limit' ), admin_url( 'edit.php?post_type=wpsl_stores&page=wpsl_csv' ) ) );
                                
                                exit();
                            }

                            $delay += $throttle_speed;
                            $geocode_response = $this->geocode->get_latlng( $store_location );

                            // Check for a valid response, if so stop the loop.
                            if ( $geocode_response['status'] == 'OK' ) {
                                $this->process_geolocation_response( $post_id, $geocode_response, $store_location );
                                break;
                            }

                            // Pause the script for 2 seconds before making another geocode attempt.
                            sleep(2);
                            $loop_count++;
                        }

                        break;
                    default:
                        $this->geocode_errors['failed'][] = sprintf( __( 'Status code "%s" for %s, %s. %sEdit details%s', 'wpsl-csv' ), esc_html( $geocode_response['status'] ) , esc_html( $store_location['name'] ), esc_html( $store_location['address'] ), '<a href="' . admin_url( 'post.php?post=' . esc_attr( $post_id ) . '&action=edit' ) . '">', '</a>' );
                        
                        break;	
                }
            } else {
                /* 
                 * This contains the error msg when wp_remote_get 
                 * fails to get a response from the Geocode API. 
                 */
                echo '<div class="error"><p>' . $geocode_response . '</p></div>';
                exit();    
            }
        }
        
        /**
         * Assign the returned geolocation data to the correct array key.
         * 
         * @since 1.0.0
         * @param integer $post_id          The id of current location
         * @param array   $geocode_response The returned data from the Geocode API
         * @param array   $store_location   The store location
         * @return void
         */
        public function process_geolocation_response( $post_id, $geocode_response, $store_location ) {

            // Make sure the latlng has a max of 6 decimals.
            $latlng = $this->geocode->format_latlng( $geocode_response['results'][0]['geometry']['location'] );

            $store_location['lat']         = $latlng['lat'];
            $store_location['lng']         = $latlng['lng'];
            $store_location['country_iso'] = $this->geocode->filter_country_name( $geocode_response );  
            
            // Add the geolocation meta data to the store location.
            $this->add_geolocation_meta_data( $post_id, $store_location );
        }

        /**
         * Add the latlng and country ISO to the store post meta.
         * 
         * @since 1.0.0
         * @param integer $post_id        The id of current location
         * @param array   $store_location The location meta data
         * @return void
         */
        public function add_geolocation_meta_data( $post_id, $store_location ) {

            add_post_meta( $post_id, 'wpsl_lat', $store_location['lat'], true );
            add_post_meta( $post_id, 'wpsl_lng', $store_location['lng'], true );
            add_post_meta( $post_id, 'wpsl_country_iso', $store_location['country_iso'], true );    
        }

        /**
         * Format the imported openings hours to make it compatible 
         * with the dropdown structure. 
         * 
         * This only works if the recommended structure is followed 
         * in the CSV file,
         * 
         * @todo see met url toevoegen voor structure example
         *
         * @since 1.0.0
         * @param string        $opening_hours            The id of current location
         * @return string|array $formatted_openings_hours Empty or the formatted openings hours.
         */
        public function format_opening_hours( $opening_hours ) {
                        
            $formatted_openings_hours = '';
            $opening_sections         = explode( '.', $opening_hours );
            
            foreach ( $opening_sections as $opening_section ) {
                $hour_parts      = array();
                $opening_section = explode( ':', $opening_section, 2 );

                /* 
                 * The $opening_section needs to contain 2 parts.
                 * The day and the hours, otherwise we ignore it. 
                 */
                if ( count( $opening_section ) == 2 ) {

                    /* 
                     * If it's set to closed, then we leave it empty.
                     * 
                     * Otherwise break up the different 
                     * opening hours at the space ( 10:00–17:00 19:00-21:00 ), 
                     * and replace the - with , before adding them to a new array.
                     */
                    if ( trim( $opening_section[1] ) != 'closed' ) {
                        $preg_params = array(
                            'pattern' => array(
                               '!\s+!',
                               '/\s*-\s*/'
                            ),
                            'replacement' => array(
                                ' ',
                                ','
                            )
                        );

                        /*
                         * Replace multiple spaces with a single space, make sure there are no 
                         * spaces before and after the -, replace it with a , and remove any start / end spaces.
                         */
                        $hour_sections = preg_replace( $preg_params['pattern'], $preg_params['replacement'], trim( $opening_section[1] ) );            
                        $hour_parts    = explode( ' ', $hour_sections );
                    }

                    $formatted_openings_hours[ trim( strtolower( $opening_section[0] ) ) ] = $hour_parts;    
                }
            }

           return $formatted_openings_hours; 
        }
        
        /**
         * Set a featured image for a store location,
         *
         * @since 1.0.0
         * @param integer $post_id   The id of current location
         * @param string  $image_url The Url of the featured image
         * @return boolean
         */
        public function set_featured_image( $post_id, $image_url ) {

            $tmp = download_url( $image_url );
            
            if ( !is_wp_error( $tmp ) ) {
                $file_array = array();
                $desc       = get_the_title( $post_id );

                // Set variables for storage and fix file filename for query strings.
                preg_match( '/[^\?]+\.(jpg|jpe|jpeg|gif|png)/i', $image_url, $matches );
                $file_array['name']     = basename( $matches[0] );
                $file_array['tmp_name'] = $tmp;

                // If error storing temporarily, unlink.
                if ( is_wp_error( $file_array['tmp_name'] ) ) {
                    @unlink( $file_array['tmp_name'] );
                    $file_array['tmp_name'] = '';
                }

                // do the validation and storage stuff.
                $attachment_id = media_handle_sideload( $file_array, $post_id, $desc );

                // If error storing permanently, unlink.
                if ( is_wp_error( $attachment_id ) ) {
                    @unlink( $file_array['tmp_name'] );
                } else {
                    set_post_thumbnail( $post_id, $attachment_id );
                }
            }
        }
        
        /**
         * Export the store data to a csv file.
         *
         * @since 1.0.0
         * @return void
         */
        public function export_csv() {

            check_admin_referer( 'wpsl-csv-export' );
            
            if ( !current_user_can( 'manage_wpsl_settings' ) ) {
                return;
            }
            
            ob_clean();
            
            $wp_field_map = $this->wp_post_field_map();
            $meta_fields  = $this->get_field_names( false );
            $export_file  = 'wpsl-export_' . date( 'Ymd' ) . '.csv';

            $args = array( 
                'post_type'      => 'wpsl_stores', 
                'post_status'    => 'any', 
                'posts_per_page' => -1
            );

            if ( isset( $_POST['wpsl_export'] ) && $_POST['wpsl_export'] ) {
                $export_filters = $_POST['wpsl_export'];

                foreach ( $export_filters as $filter_key => $filter_val ) {
                    if ( $filter_val ) {

                        // Make sure the export filter val is assigned to the correct key.
                        if ( in_array( str_replace( 'wpsl_', '', $filter_key ), $meta_fields ) ) {
                            $args['meta_query'][] = array(
                                'key'   => $filter_key,
                                'value' => $filter_val
                             );
                        } else if ( $filter_key == 'wpsl_category' ) {
                            $args['tax_query'][] = array(
                                'taxonomy' => 'wpsl_store_category',
                                'field'    => 'term_id',
                                'terms'    => absint( $filter_val )
                            );
                        }
                    }
                }

                $args = apply_filters( 'wpsl_csv_export_args' , $args, $export_filters );
            }
            
            $locations = get_posts( $args );
            
            echo '<pre>';
            print_r( $locations );
            echo '</pre>';
            
            exit();

            foreach ( $locations as $location ) {

                // Get the post data, like the name, id, author, permalink etc.
                foreach ( $wp_field_map as $csv_header => $wp_field ) {
                    $post_data[$csv_header] = $location->$wp_field;
                }

                $meta_data = get_post_custom( $location->ID );

                // Loop over the wpsl meta fields, and sort the meta data.
                foreach ( $meta_fields as $meta_field ) {
                    if ( isset( $meta_data['wpsl_' . $meta_field][0] ) ) {
                        $post_meta[$meta_field] = $meta_data['wpsl_' . $meta_field][0];
                    } else {
                        $post_meta[$meta_field] = '';
                    }
                }

                // Get the category names.
                $term_names = wp_get_object_terms( $location->ID, 'wpsl_store_category', array( 'fields' => 'names' ) );
                $term_count = count( $term_names );
                $terms      = '';

                if ( $term_count == 1 ) {
                    $terms = $term_names[0];
                } elseif ( $term_count > 1 ) {
                    $terms = implode( '|', $term_names );
                }
                
                $post_meta['category'] = $terms;
                
                // Get the ID of the featured image.
                $post_meta['image'] = get_post_thumbnail_id( $location->ID );

                // Format the opening hours so that they are readable.
                if ( isset( $post_meta['hours'] ) && $post_meta['hours'] ) {
                    $opening_hours = maybe_unserialize( $post_meta['hours'] );

                    if ( is_array( $opening_hours ) ) {
                        $days = '';

                        foreach ( $opening_hours as $day => $hour_sections ) {
                            $new_section   = '';
                            $section_count = count( $hour_sections );

                            if ( $section_count ) {
                                $section_end = ( $section_count > 1 ) ? ' ' : '';

                                foreach ( $hour_sections as $section ) {
                                    $new_section .= str_replace( ',', '-', $section ) . $section_end;
                                }

                                if ( $section_count > 1 ) {
                                    $new_section = rtrim( $new_section );
                                }
                            } else {
                                $new_section = 'closed';
                            }

                            $days .= $day . ': ' . $new_section . '. '; 
                        }
                        
                        $post_meta['hours'] = $days;
                    }
                }
                
                $location_data[] = array_merge( $post_data, $post_meta );
            }
            
            $this->parse_csv->output( $export_file, $location_data, array_keys( $location_data[0] ) );

            exit();
        }     
	}
}