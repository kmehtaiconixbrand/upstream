<?php
/**
 * WPSL CSV Admin class
 * 
 * @since  1.0.0
 * @author Tijmen Smit
 */

if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'WPSL_CSV_Admin' ) ) {

    class WPSL_CSV_Admin {
                
        /**
         * @since 1.0.0
         * @var WPSL_CSV_Import $import
         */
        public $import;
        
        /**
         * @since 1.0.0
         * @var WPSL_CSV_Export $export
         */
        public $export;

        /**
         * Class constructor
         */
		function __construct() {
            
            $this->init();

            add_action( 'init',                  array( $this, 'form_actions' ) );
            add_action( 'admin_menu',            array( $this, 'admin_menu' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );
		}
        
        /**
         * Init the required classes.
         *
         * @since 1.0.0
         * @return void
         */
        public function init() {
            $this->import = new WPSL_CSV_Import();
            $this->export = new WPSL_CSV_Export();
        }
        
        /**
         * Handle the different form actions.
         *
         * @since 1.0.0
         * @return void
         */
        public function form_actions() {
            if ( isset( $_POST['wpsl_action'] ) ) {
                do_action( 'wpsl_' . $_POST['wpsl_action'] );
            }
        }

        /**
         * Add the 'CSV Manager' sub menu to the 
         * existing WP Store Locator menu.
         * 
         * @since  1.0.0
         * @return void
         */        
        public function admin_menu() {
            add_submenu_page( 'edit.php?post_type=wpsl_stores', __( 'CSV Manager', 'wpsl-csv' ), __( 'CSV Manager', 'wpsl-csv' ), 'manage_wpsl_settings', 'wpsl_csv', array( $this, 'csv_page' ) );
        }
        
        /**
         * Add the required admin CSS.
         * 
         * @since  1.0.0
         * @return void
         */        
        public function admin_styles() {
            wp_enqueue_style( 'wpsl-csv-admin', plugins_url( '/css/style.css', __FILE__ ), false );
        }        
        
        /**
         * Show the CSV import / export template.
         * 
         * @since  1.0.0
         * @return void
         */        
        public function csv_page() {
            require_once( WPSL_CSV_PLUGIN_DIR . 'admin/templates/html-csv.php' );  
        }
    }

    new WPSL_CSV_Admin();
}