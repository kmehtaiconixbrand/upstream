<?php
/**
 * Handle WPSL CSV Export
 *
 * @since  1.0.0
 * @author Tijmen Smit
 */

if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'WPSL_CSV_Export' ) ) {
    
	class WPSL_CSV_Export {
        
        /**
         * @since 1.0.0
         * @var WPSL_Parse_CSV $parse_csv
         */
        public $parse_csv;
        
        /**
         * Class constructor
         */
        function __construct() {

            $this->parse_csv = new WPSL_ParseCSV();
            
            add_action( 'wpsl_csv_export', array( $this, 'export' ) );
        }

        /**
         * Export the store data to a CSV file.
         *
         * @since 1.0.0
         * @return void
         */
        public function export() {

            global $wpsl_admin; // From the WPSL plugin.

            check_admin_referer( 'wpsl_csv_export', 'wpsl_csv_export_nonce' );

            if ( !current_user_can( 'manage_wpsl_settings' ) ) {
                return;
            }

            $wp_field_map   = wpsl_wp_post_field_map();
            $meta_fields    = wpsl_get_field_names( false );
            $export_filters = isset( $_POST['wpsl_export_filters'] ) ? $_POST['wpsl_export_filters'] : '';
            
            // Include the values of any selected export filters in the file name.
            $export_file = 'wpsl-export-' . $this->generate_name_section( $export_filters ) . date( 'Ymd' ) . '.csv';         
            
            // Get the location data that we need to export, and matches with the selected export filters.
            $locations = $this->get_locations( $meta_fields, $export_filters );

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

                if ( isset( $post_meta['hours'] ) && $post_meta['hours'] ) {
                    
                     // Make sure the opening hours are not serialized.
                    $post_meta['hours'] = $this->make_hours_readable( $post_meta['hours'] );
                }
                
                $location_data[] = array_merge( $post_data, $post_meta );
            }
            
            if ( isset( $location_data ) && $location_data ) {
                $this->parse_csv->output( $export_file, $location_data, array_keys( $location_data[0] ) );
                exit();
            } else {
                $error_msg = __( 'No locations found that match the selected export filters.', 'wpsl-csv' );
                $wpsl_admin->notices->save( 'error', $error_msg );
                
                wp_redirect( admin_url( 'edit.php?post_type=wpsl_stores&page=wpsl_csv&section=export' ) );
                exit();
            }
        }  
        
        /**
         * Generate the middle part of the CSV export filename.
         * 
         * This is based on the values of the selected export filters.
         *
         * @since 1.0.0
         * @param array   $export_filters Selected export filters.
         * @return string $name_section   The values from the different export filters merged together, 
         *                                or empty if no filters where selected.
         */        
        public function generate_name_section( $export_filters ) {
            
            $name_parts = array();
            
            foreach ( $export_filters as $filter_key => $filter_val ) {

                if ( $filter_val ) {
                    if ( $filter_key == 'wpsl_category' ) {
                        $term_data    = get_term_by( 'id', $filter_val, 'wpsl_store_category' ); 
                        $name_parts[] = $term_data->name;
                    } else {
                        $name_parts[] = $filter_val;
                    }
                }
            }
            
            if ( $name_parts ) {
                $name_section = str_replace( ' ', '_', strtolower( implode( '-', $name_parts ) ) ) . '-'; 
            } else {
                $name_section = '';
            }
            
            return $name_section;
        }
        
        /**
         * Get the requested store location data.
         *
         * @since 1.0.0
         * @param array  $meta_fields    WPSL post meta fields.
         * @param array  $export_filters Selected export filters.
         * @return array $locations      The collected store locations.
         */
        public function get_locations( $meta_fields, $export_filters ) {
            
            $args = array(
                'post_type'      => 'wpsl_stores', 
                'post_status'    => array( 'publish', 'pending', 'draft', 'future', 'private', 'inherit' ), 
                'posts_per_page' => -1
            );

            // Check if we need to apply any export filters.
            if ( $export_filters ) {

                foreach ( $export_filters as $filter_key => $filter_val ) {
                    if ( $filter_val ) {

                        // Make sure the export filter val is assigned to the correct $args key.
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

            return $locations;
        }
                
        /**
         * Get the total WPSL Location count.
         *
         * @since 1.0.0
         * @return int $total_count The wpsl_stores post count.
         */        
        public function get_export_count() {

            // List the post statuses we need to include.
            $post_statuses = array(
                'publish',
                'future',
                'draft',
                'pending',
                'private',
                'inherit'
            );

            $count_posts = wp_count_posts( 'wpsl_stores' ); 
            $total_count = 0;

            // Count the different post statuses to get the total amount of locations.
            foreach ( $post_statuses as $post_status ) {
                if ( isset( $count_posts->{$post_status} ) ) {
                    $total_count = $total_count + $count_posts->{$post_status};
                }
            }
            
            return $total_count;
        }
        
        /**
         * Create the different filter options for the CSV export.
         *
         * @since 1.0.0
         * @return string $filter The HTML to show the different filter options for the CSV export.
         */
        public function filter_options() {

            $export_meta_filters = apply_filters( 'wpsl_csv_export_meta_filters', array(
                    'wpsl_city'    => __( 'City', 'wpsl-csv' ),
                    'wpsl_country' => __( 'Country', 'wpsl-csv' )
                )
            );

            $filter = '';
            $filter .= $this->category_filter();

            foreach ( $export_meta_filters as $meta_filter => $meta_title ) {
                $meta_list = $this->get_unique_wpsl_meta_values( $meta_filter );

                if ( $meta_list ) {
                    $filter .= '<tr valign="top">';
                    $filter .= '<th scope="row">';
                    $filter .= '<label for="' . esc_attr( $meta_filter ) . '">' . esc_attr( $meta_title ) . '</label>';
                    $filter .= '</th>';
                    $filter .= '<td>';
                    $filter .= '<select id="' . esc_attr( $meta_filter ) . '" autocomplete="off" name="wpsl_export_filters[' . esc_attr( $meta_filter ) . ']">';
                    $filter .= '<option value="0">' . __( 'Any' , 'wpsl-csv' ) . '</option>';

                    foreach ( $meta_list as $list ) {
                        $filter .= '<option value="' . esc_attr( $list ) . '">' . esc_html( $list ) . '</option>';
                    }

                    $filter .= '</select>';
                    $filter .= '</td>'; 
                    $filter .= '</tr>';
                }
            }

            return $filter;
        }

        /**
         * Create the category dropdown filter.
         *
         * @since 1.0.0
         * @return string $category The HTML to show a list of WPSL categories, or empty if no categories exist.
         */
        public function category_filter() {

            $category = '';
            $terms    = get_terms( 'wpsl_store_category' );

            do_action( 'wpsl_before_export_terms' );

            if ( count( $terms ) > 0 ) {
                $category = '<tr valign="top">';
                $category .= '<th scope="row">';
                $category .= '<label for="wpsl_category">'. __( 'Category', 'wpsl-csv' ) . '</label>';
                $category .= '</th>';
                $category .= '<td>';

                $category .= '<select id="wpsl_category" autocomplete="off" name="wpsl_export_filters[wpsl_category]">';
                $category .= '<option value="0">'. __( 'Any' , 'wpsl-csv' ) .'</option>';

                foreach ( $terms as $term ) {
                   $category .= '<option value="' . $term->term_id . '">' . $term->name . '</option>';
                }    

                $category .= '</select>';
                $category .= '</td>';
                $category .= '</tr>';
            }

            do_action( 'wpsl_after_export_terms' );

            return $category;
        }

        /**
         * Get the unique meta values for the provided WPSL meta key.
         *
         * @since 1.0.0
         * @param string $key    The WPSL meta key.
         * @return array $result The unique values for the provided meta $key.
         */
         public function get_unique_wpsl_meta_values( $key ) {

            global $wpdb;

            $sql = "SELECT DISTINCT pm.meta_value
                               FROM {$wpdb->postmeta} pm
                          LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id 
                              WHERE pm.meta_key = %s 
                                AND p.post_type = 'wpsl_stores'
                                AND p.post_status NOT IN ( 'trash', 'auto-draft' )
                           ORDER BY pm.meta_value ASC";

            $result = $wpdb->get_col( $wpdb->prepare( $sql, $key ) );

            return $result;
        }
        
        /**
         * Make sure the opening hours are in a readable form.
         * 
         * If they are set through a textarea ( users who upgraded from
         * 1.x to 2.x can do this ), then the text is already in a normal readable form. 
         * 
         * But if they are set through dropdowns, then the data is serialized
         * which makes it unreadable in the CSV export. To fix this we unserialize it, 
         * and change the structure to the expected import format.
         * 
         * @since 1.0.0
         * @param string $post_meta_hours The post_meta_hours from the db.
         * @return array $post_meta_hours The unique values for the provided $key.
         */        
        public function make_hours_readable( $post_meta_hours ) {
            
            $opening_hours = maybe_unserialize( $post_meta_hours );

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

                $post_meta_hours = $days;
            }
            
            return $post_meta_hours;
        }
    }
}