<?php
/*
Plugin Name: JigoShop - Software Add-On
Plugin URI: https://github.com/jkudish/JigoShop-Software-Add-on/
Description: Extends JigoShop to a full-blown software shop, including license activation, license retrieval, activation e-mails and more
Version: 2.6
Author: Joachim Kudish
Author URI: http://jkudish.com
License: GPL v2
Text Domain: jigoshop-software
*/

/**
 * @version 2.6
 * @author Joachim Kudish <info@jkudish.com>
 * @link http://jkudish.com
 * @uses JigoShop @link http://jigoshop.com
 * @uses WordPress Github Plugin Updater @link https://github.com/jkudish/WordPress-GitHub-Plugin-Updater
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright (c) 2011, Joachim Kudish
 *
 * GNU General Public License, Free Software Foundation
 *	<http://creativecommons.org/licenses/GPL/2.0/>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

if ( ! class_exists( 'Jigoshop_Software' ) ) {
	class Jigoshop_Software {

		/**
		 * the product fields
		 * @var array
		 */
		public $product_fields;

		/**
		 * the order fields
		 * @var array
		 */
		public $order_fields;

		/*
		 * helpers used for the upgrades pages
		 */
		private $looking_for_upgrades;
		private $possible_upgrades_found ;
		private $upgrade_error ;

		/**
		 * class constructor
		 * plugin activation, hooks & filters, etc..
		 *
		 * @since 1.0
		 * @return void
		 */
		function __construct() {

			$this->define_constants();

			/**
			 * hooks
			 */
			add_action( 'init', array( $this, 'set_timezone' ) );
			add_action( 'init', array( $this, 'load_lang' ) );

			// backend stuff
			add_action( 'product_write_panel_tabs', array( $this, 'product_write_panel_tab' ) );
			add_action( 'product_write_panels', array( $this, 'product_write_panel' ) );
			add_filter( 'jigoshop_process_product_meta', array( $this, 'product_save_data' ) );
			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
			add_action( 'jigoshop_process_shop_order_meta', array( $this, 'order_save_data' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue' ) );
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			add_action( 'wp_ajax_nopriv_jgs_import', array( $this, 'import_ajax' ) );
			add_action( 'wp_ajax_jgs_import', array( $this, 'import_ajax' ) );
			add_action( 'wp_ajax_nopriv_jgs_do_import', array( $this, 'import' ) );
			add_action( 'wp_ajax_jgs_do_import', array( $this, 'import' ) );
			add_action( 'get_search_query', array( $this, 'order_get_search_query' ) );

			// frontend stuff
			remove_action( 'simple_add_to_cart', 'jigoshop_simple_add_to_cart' );
			remove_action( 'virtual_add_to_cart', 'jigoshop_simple_add_to_cart' );
			remove_action( 'downloadable_add_to_cart', 'jigoshop_downloadable_add_to_cart' );
			add_action( 'grouped_add_to_cart', 'jigoshop_grouped_add_to_cart' );
			remove_action( 'jigoshop_after_shop_loop_item', 'jigoshop_template_loop_add_to_cart', 10, 2 );
			add_action( 'simple_add_to_cart', array( $this, 'add_to_cart' ) );
			add_action( 'virtual_add_to_cart', array( $this, 'add_to_cart' ) );
			add_action( 'downloadable_add_to_cart', array( $this, 'add_to_cart' ) );
			add_action( 'grouped_add_to_cart', array( $this, 'add_to_cart' ) );
			add_action( 'jigoshop_after_shop_loop_item', array( $this, 'loop_add_to_cart' ), 10, 2 );

			add_action( 'wp_enqueue_scripts', array( $this, 'frontend_enqueue' ) );
			add_action( 'wp_head', array( $this, 'redirect_away_from_cart' ) );
			add_action( 'wp_ajax_nopriv_jgs_checkout', array( $this, 'ajax_jgs_checkout' ) );
			add_action( 'wp_ajax_jgs_checkout', array( $this, 'ajax_jgs_checkout' ) );
			add_action( 'wp_ajax_nopriv_jgs_lost_license', array( $this, 'ajax_jgs_lost_license' ) );
			add_action( 'wp_ajax_jgs_lost_license', array( $this, 'ajax_jgs_lost_license' ) );
			add_action( 'wp_ajax_nopriv_jgs_upgrade', array( $this, 'ajax_jgs_upgrade' ) );
			add_action( 'wp_ajax_jgs_upgrade', array( $this, 'ajax_jgs_upgrade' ) );

			// payment stuff
			add_action( 'init', array( $this, 'init_actions' ), 1 );
			add_action( 'valid-paypal-standard-ipn-request', array( $this, 'post_paypal_payment' ) );
			add_action( 'order_status_cancelled', array( $this, 'cancel_order' ) );

			// email stuff
			remove_action( 'order_status_pending_to_processing', 'jigoshop_new_order_notification' );
			remove_action( 'order_status_pending_to_completed', 'jigoshop_new_order_notification' );
			remove_action( 'order_status_pending_to_on-hold', 'jigoshop_new_order_notification' );
			remove_action( 'order_status_completed', 'jigoshop_completed_order_customer_notification' );
			remove_action( 'order_status_pending_to_processing', 'jigoshop_processing_order_customer_notification' );
			remove_action( 'order_status_pending_to_on-hold', 'jigoshop_processing_order_customer_notification' );
			remove_action( 'order_status_completed', 'jigoshop_completed_order_customer_notification' );
			add_action( 'order_status_completed', array( $this, 'completed_order' ) );

			// filters
			add_filter( 'add_to_cart_redirect', array( $this, 'add_to_cart_redirect' ) );
			add_filter( 'page_template', array( $this, 'locate_api_template' ), 10, 1 );
		}

		/**
		 * set the correct timezone from the WP options
		 *
		 * @since 2.4
		 * @return void
		 */
		function set_timezone() {
			$timezone = get_option( 'timezone_string' );
			if ( ! empty( $timezone ) )
				date_default_timezone_set( $timezone );
		}

		/**
		 * defines the constants we need for the plugin
		 *
		 * @since 1.3
		 * @return void
		 */
		function define_constants() {
			if ( ! defined( 'JIGOSHOP_SOFTWARE_PATH' ) )
				define( 'JIGOSHOP_SOFTWARE_PATH', dirname( __FILE__ ) );
			if ( ! defined( 'JIGOSHOP_SOFTWARE_SLUG' ) )
				define( 'JIGOSHOP_SOFTWARE_SLUG', plugin_basename( __FILE__ ) );
			if ( ! defined( 'JIGOSHOP_SOFTWARE_VERSION' ) )
				define( 'JIGOSHOP_SOFTWARE_VERSION', 2.5 );
			if ( ! defined( 'JIGOSHOP_SOFTWARE_PROPER_NAME' ) )
				define( 'JIGOSHOP_SOFTWARE_PROPER_NAME', 'jigoshop-software' );
			if ( ! defined( 'JIGOSHOP_SOFTWARE_GITHUB_URL' ) )
				define( 'JIGOSHOP_SOFTWARE_GITHUB_URL', 'https://github.com/jkudish/JigoShop-Software-Add-on' );
			if ( ! defined( 'JIGOSHOP_SOFTWARE_GITHUB_ZIP_URL' ) )
				define( 'JIGOSHOP_SOFTWARE_GITHUB_ZIP_URL', 'https://github.com/jkudish/JigoShop-Software-Add-on/zipball/master' );
			if ( ! defined( 'JIGOSHOP_SOFTWARE_GITHUB_API_URL' ) )
				define( 'JIGOSHOP_SOFTWARE_GITHUB_API_URL', 'https://api.github.com/repos/jkudish/JigoShop-Software-Add-on' );
			if ( ! defined( 'JIGOSHOP_SOFTWARE_GITHUB_RAW_URL' ) )
				define( 'JIGOSHOP_SOFTWARE_GITHUB_RAW_URL', 'https://raw.github.com/jkudish/JigoShop-Software-Add-on/master' );
			if ( ! defined( 'JIGOSHOP_SOFTWARE_REQUIRES_WP' ) )
				define( 'JIGOSHOP_SOFTWARE_REQUIRES_WP', '3.3' );
			if ( ! defined( 'JIGOSHOP_SOFTWARE_TESTED_WP' ) )
				define( 'JIGOSHOP_SOFTWARE_TESTED_WP', '3.4.2' );
		}

		/**
		 * defines the fields used in the plugin
		 *
		 * @since 2.1
		 * @return void
		 */
		function define_fields() {
			// define the product metadata fields used by this plugin
			$this->product_fields = array(
				array( 'id' => 'is_software', 'label' => __( 'This product is Software', 'jigoshop-software' ), 'title' => __( 'This product is Software', 'jigoshop-software' ), 'placeholder' => '', 'type' => 'checkbox', 'never_hide' => true ),
				array( 'id' => 'is_upgrade', 'label' => __( 'This product is solely an upgrade', 'jigoshop-software' ), 'title' => __( 'This product is solely an upgrade', 'jigoshop-software' ), 'placeholder' => '', 'type' => 'checkbox', 'never_hide' => true ),
				array( 'id' => 'soft_product_id', 'label' => __( 'Product ID to use for API', 'jigoshop-software' ), 'title' => __( 'Product ID to use for API', 'jigoshop-software' ), 'placeholder' => __( 'ex: PRODUCT1', 'jigoshop-software' ), 'type' => 'text' ),
				array( 'id' => 'license_key_prefix', 'label' => __( 'Prefix for License Key', 'jigoshop-software' ), 'title' => __( 'Optional prefix for the license key', 'jigoshop-software' ), 'placeholder' => __( 'ex: SC-', 'jigoshop-software' ), 'type' => 'text' ),
				array( 'id' => 'secret_product_key', 'label' => __( 'Secret Product Key to use for API', 'jigoshop-software' ), 'title' => __( 'Secret Product Key to use  for API', 'jigoshop-software' ), 'placeholder' => __( 'any random string', 'jigoshop-software' ), 'type' => 'text' ),
				array( 'id' => 'version', 'label' => __( 'Version Number', 'jigoshop-software' ), 'title' => __( 'Version Number', 'jigoshop-software' ), 'placeholder' => __( 'ex: 1.0', 'jigoshop-software' ), 'type' => 'text' ),
				array( 'id' => 'activations', 'label' => __( 'Amount of activations possible', 'jigoshop-software' ), 'title' => __( 'Amount of activations possible', 'jigoshop-software' ), 'placeholder' => __( 'ex: 5', 'jigoshop-software' ), 'type' => 'text' ),
				array( 'id' => 'trial', 'label' => __( 'Trial Period (amount of days or hours)', 'jigoshop-software' ), 'title' => __( 'Trial Period (amount of days or hours)', 'jigoshop-software' ), 'placeholder' => __( 'ex: 15', 'jigoshop-software' ), 'type' => 'text' ),
				array( 'id' => 'trial_unit', 'label' => __( 'Trial Units', 'jigoshop-software' ), 'title' => __( 'Trial Units', 'jigoshop-software' ), 'type' => 'select', 'values' => array( 'days' => 'Days', 'hours' => 'Hours' ) ),
				array( 'id' => 'upgrade_from', 'label' => __( 'Upgrade from', 'jigoshop-software' ), 'title' => __( 'Upgrade from', 'jigoshop-software' ), 'placeholder' => '', 'type' => 'select', 'values' => $this->get_product_upgrade_dropdown(), 'upgrade_field' => true ),
				array( 'id' => 'upgrade_to', 'label' => __( 'Upgrade to', 'jigoshop-software' ), 'title' => __( 'Upgrade to', 'jigoshop-software' ), 'placeholder' => '', 'type' => 'select', 'values' => $this->get_product_upgrade_dropdown(), 'upgrade_field' => true ),
				array( 'id' => 'upgrade_date_since', 'label' => __( 'Upgrade Date Threshold', 'jigoshop-software' ), 'title' => __( 'Original purchase must have occurred on or after the following date', 'jigoshop-software' ), 'placeholder' => 'ex: 2012-06-01', 'type' => 'text', 'upgrade_field' => true ),
				array( 'id' => 'paypal_name', 'label' => __( 'Paypal Name to show on transaction receipts', 'jigoshop-software' ), 'title' => __( 'Paypal Name to show on transaction receipts', 'jigoshop-software' ), 'placeholder' => __( 'ex: Google Inc.', 'jigoshop-software' ), 'type' => 'text' ),
			);

			$this->order_fields = array(
				array( 'id' => 'activation_email', 'label' => __( 'Activation Email', 'jigoshop-software' ), 'title' => __( 'Activation Email', 'jigoshop-software' ), 'placeholder' => '', 'type' => 'text' ),
				array( 'id' => 'activation_email_optin', 'label' => __( 'Receive Activation Emails', 'jigoshop-software' ), 'title' => __( 'Receive Activation Emails', 'jigoshop-software' ), 'placeholder' => '', 'type' => 'checkbox' ),
				array( 'id' => 'license_key', 'label' => __( 'License Key', 'jigoshop-software' ), 'title' => __( 'License Key', 'jigoshop-software' ), 'placeholder' => '', 'type' => 'text' ),
				array( 'id' => 'paypal_name', 'label' => __( 'Paypal Name to show on transaction receipts', 'jigoshop-software' ), 'title' => __( 'Paypal Name to show on transaction receipts', 'jigoshop-software' ), 'placeholder' => __( 'ex: Google Inc.', 'jigoshop-software' ), 'type' => 'text' ),
				array( 'id' => 'transaction_id', 'label' => __( 'Transaction ID', 'jigoshop-software' ), 'title' => __( 'Transaction ID', 'jigoshop-software' ), 'placeholder' => '', 'type' => 'text' ),
				array( 'id' => 'productid', 'label' => __( 'Product ID', 'jigoshop-software' ), 'title' => __( 'Product ID', 'jigoshop-software' ), 'placeholder' => '', 'type' => 'text' ),
				array( 'id' => 'activations_possible', 'label' => __( 'Max Activations Allowed', 'jigoshop-software' ), 'title' => __( 'Max Activations Allowed', 'jigoshop-software' ), 'placeholder' => '', 'type' => 'text' ),
				array( 'id' => 'remaining_activations', 'label' => __( 'Remaining Activations', 'jigoshop-software' ), 'title' => __( 'Remaining Activations', 'jigoshop-software' ), 'placeholder' => '', 'type' => 'text' ),
				array( 'id' => 'secret_product_key', 'label' => __( 'Secret Product Key to use for API', 'jigoshop-software' ), 'title' => __( 'Secret Product Key to use for API', 'jigoshop-software' ), 'placeholder' => __( 'any random string', 'jigoshop-software' ), 'type' => 'text' ),
				array( 'id' => 'version', 'label' => __( 'Version', 'jigoshop-software' ), 'title' => __( 'Version', 'jigoshop-software' ), 'placeholder' => '', 'type' => 'text' ),
				array( 'id' => 'old_order_id', 'label' => __( 'Legacy order ID', 'jigoshop-software' ), 'title' => __( 'Legacy order ID', 'jigoshop-software' ), 'placeholder' => '', 'type' => 'text' ),
				array( 'id' => 'is_upgrade', 'label' => __( 'This is an upgrade if checked', 'jigoshop-software' ), 'title' => __( 'This is an upgrade if checked', 'jigoshop-software' ), 'placeholder' => '', 'type' => 'checkbox' ),
				array( 'id' => 'upgrade_name', 'label' => __( 'Upgraded from', 'jigoshop-software' ), 'title' => __( 'Upgraded from', 'jigoshop-software' ), 'placeholder' => '', 'type' => 'text' ),
				array( 'id' => 'upgraded_via', 'label' => __( 'Upgraded Using', 'jigoshop-software' ), 'title' => __( 'Upgraded Using', 'jigoshop-software' ), 'placeholder' => '', 'type' => 'text' ),
				array( 'id' => 'upgraded_to', 'label' => __( 'Upgraded To', 'jigoshop-software' ), 'title' => __( 'Upgraded To', 'jigoshop-software' ), 'placeholder' => '', 'type' => 'text' ),
				array( 'id' => 'upgrade_price', 'label' => __( 'Upgrade price ($)', 'jigoshop-software' ), 'title' => __( 'Upgrade price ($)', 'jigoshop-software' ), 'placeholder' => '', 'type' => 'text' ),
				array( 'id' => 'upgraded_from_order_id', 'label' => __( 'Order ID of the original purchase', 'jigoshop-software' ), 'title' => __( 'Order ID of the original purchase', 'jigoshop-software' ), 'placeholder' => '', 'type' => 'text' ),
				array( 'id' => 'has_been_upgraded', 'label' => __( 'This order has been used as an upgrade to another upgrade if checked', 'jigoshop-software' ), 'title' => __( 'This order has been used as an upgrade to another upgrade if checked', 'jigoshop-software' ), 'placeholder' => '', 'type' => 'checkbox' ),
				array( 'id' => 'upgraded_to_order_id', 'label' => __( 'Order ID of the upgrade purchase', 'jigoshop-software' ), 'title' => __( 'Order ID of the upgrade purchase', 'jigoshop-software' ), 'placeholder' => '', 'type' => 'text' ),
				array( 'id' => 'original_price', 'label' => __( 'Original price ($)', 'jigoshop-software' ), 'title' => __( 'Original price ($)', 'jigoshop-software' ), 'placeholder' => '', 'type' => 'text' ),
			);

		}

		function load_lang() {
			load_plugin_textdomain( 'jigoshop-software', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}

		/**
		 * runs various functions when the plugin first activates
		 *
		 * @see register_activation_hook()
		 * @link http://codex.wordpress.org/Function_Reference/register_activation_hook
		 * @since 1.0
		 * @return void
		 */
		function activation() {

			// checks if the jigoshop plugin is running and disables this plugin if it's not (and displays a message)
			if ( ! is_plugin_active( 'jigoshop/jigoshop.php' ) ) {
				deactivate_plugins( plugin_basename( __FILE__ ) );
				wp_die( sprintf( _x( 'The JigoShop Software Add-On requires %s to be activated in order to work. Please activate %s first.', 'A link to JigoShop is provided in the placeholders', 'jigoshop-software' ), '<a href="http://jigoshop.com" target="_blank">JigoShop</a>', '<a href="http://jigoshop.com" target="_blank">JigoShop</a>' ) . '<a href="'. esc_url( admin_url( 'plugins.php' ) ) . '"> <br> &laquo; ' . _x( 'Go Back', 'Activation failed, so go back to the plugins page', 'jigoshop-software' ) . '</a>' );
			}

			// creates the lost license page with the right shortcode in it
			$lost_license_page_id = get_option( 'jigoshop_lost_license_page_id' );
			if ( empty(  $lost_license_page_id ) ) {
				$lost_license_page = array(
					'post_title' => _x( 'Lost License', 'title of a page', 'jigoshop-software' ),
					'post_content' => '[jigoshop_software_lost_license]',
					'post_status' => 'publish',
					'post_type' => 'page',
				);
				$lost_license_page_id = wp_insert_post( $lost_license_page );
				update_option( 'jigoshop_lost_license_page_id', $lost_license_page_id );
			}

			// creates the API page
			$jigoshop_api_page_id = get_option( 'jigoshop_api_page_id' );
			if ( empty( $jigoshop_api_page_id ) ) {
				$api_page = array(
					'post_title' => _x( 'API', 'title of a page', 'jigoshop-software' ),
					'post_content' => '',
					'post_status' => 'publish',
					'post_type' => 'page',
				);
				$jigoshop_api_page_id = wp_insert_post( $api_page );
				update_option( 'jigoshop_api_page_id', $jigoshop_api_page_id );
			}

		}

		/* =======================================
				meta boxes
		==========================================*/

		/**
		 * gets array of values for the upgrade products dropdown
		 *
		 * @since 2.2
		 * @return array the array of product IDs and product Names
		 */
		function get_product_upgrade_dropdown( $get_the_upgrades = false ) {
			$transient_key = ( $get_the_upgrades ) ? 'jigoshop_software_get_products_which_are_upgrades' : 'jigoshop_software_get_product_upgrade_dropdown';
			$return = get_transient( $transient_key );
			if ( empty( $return ) ) {
				$query_args = array(
					'post_type' => 'product',
					'posts_per_page' => -1,
					'post__not_in' => array( get_queried_object_id() ),
				);
				$products = get_posts( $query_args );
				$return = array( 0 => 'none' );
				if ( !empty( $products ) ) {
					foreach ( $products as $product ) {
						$data = get_post_meta( $product->ID, 'product_data', true );
						if ( $get_the_upgrades && ! empty( $data['is_upgrade'] ) ) {
							$return[$product->ID] = $product->post_title;
						} elseif ( ! $get_the_upgrades && empty( $data['is_upgrade'] ) ) {
							$return[$product->ID] = $product->post_title;
						}
					}
				}
				wp_reset_query();
				set_transient( $transient_key, $return );
			}
			return $return;
		}

		/**
		 * registers meta boxes
		 *
		 * @since 1.0
		 * @return void
		 */
		function add_meta_boxes() {
			add_meta_box( 'jigoshop-software-order-data', __( 'Software Purchase Details', 'jigoshop-software' ), array( $this, 'order_meta_box' ), 'shop_order', 'normal', 'high' );
			add_meta_box( 'jigoshop-software-activation-data', __( 'Activations', 'jigoshop-software' ), array( $this, 'activation_meta_box' ), 'shop_order', 'normal', 'high' );
			add_meta_box( 'jigoshop-software-further-actions', __( 'Further Actions', 'jigoshop-software' ), array( $this, 'order_further_actions_meta_box' ), 'shop_order', 'side', 'low' );
		}

		/**
		 * adds a new tab to the product interface
		 *
		 * @since 1.0
		 * @return void
		 */
		function product_write_panel_tab() {
			printf( '<li><a href="#software_data">%s</a></li>', __( 'Software', 'jigoshop-software' ) );
		}

		/**
		 * adds the panel to the product interface
		 *
		 * @since 1.0
		 * @return void
		 */
		function product_write_panel() {
			global $post;
			$data = get_post_meta( $post->ID, 'product_data', true );
			$this->define_fields();
			echo '<div id="software_data" class="panel jigoshop_options_panel">';
			foreach ( $this->product_fields as $field) {

				// determine what the value should be
				if ( ! empty( $field['id'] ) && 'soft_product_id' == $field['id'] )
					$value = get_post_meta( $post->ID, 'soft_product_id', true );
				elseif ( ! empty( $field['id'] ) && in_array( $field['id'], array( 'up_license_keys', 'used_license_keys' ) ) )
					$value = $this->un_array_ify_keys( $data[$field['id']] );
				else
					$value = $data[$field['id']];

				$field_classes = array( 'form-field', 'jgs-product-field' );
				if ( ! empty( $field['upgrade_field'] ) && $field['upgrade_field'] )
					$field_classes[] = 'jgs-upgrade-field';
				if ( ! empty( $field['never_hide'] ) && $field['never_hide'] )
					$field_classes[] = 'jgs-never-hide';

				$this->admin_field_helper( $field, $value, $field_classes );

			}
			echo '</div>';
			?>
			<script>
				(function($) {
					function upgrade_checkboxes() {
						if ( $( '#is_upgrade' ).prop( 'checked' ) ) {
							$( '.jgs-upgrade-field' ).show();
							$( '.jgs-product-field' ).not( '.jgs-never-hide' ).not( '.jgs-upgrade-field' ).hide();
						} else {
							$( '.jgs-upgrade-field' ).hide();
							$( '.jgs-product-field' ).show();
						}
					}
					upgrade_checkboxes();
					$( '#is_upgrade' ).change( function(){ upgrade_checkboxes() });
				})(jQuery);
			</script>
		<?php
		}

		/**
		 * saves the data inputed into the product boxes into a serialized array
		 *
		 * @since 2.1
		 * @return void
		 */
		function product_save_data() {
			global $post;
			$this->define_fields();
			delete_transient( 'jigoshop_software_get_product_upgrade_dropdown' );
			delete_transient( 'jigoshop_software_get_products_which_are_upgrades' );
			$data = get_post_meta( $post->ID, 'product_data', true );
			foreach ( $this->product_fields as $field ) {
				if ( in_array( $field['id'], array( 'up_license_keys', 'used_license_keys' ) ) ) {
					$data[$field['id']] = $this->array_ify_keys( strip_tags( $_POST[$field['id']] ) );
				} elseif ( 'soft_product_id' == $field['id'] ) {
					update_post_meta( $post->ID, 'soft_product_id', sanitize_text_field( $_POST[$field['id']] ) );
				} else {
					$data[$field['id']] = sanitize_text_field( $_POST[$field['id']] );
				}
			}
			update_post_meta( $post->ID, 'product_data', $data );
			$this->get_product_upgrade_dropdown();
		}

		/**
		 * adds meta fields to the order screens
		 *
		 * @since 1.0
		 * @return void
		 */
		function order_meta_box() {
			global $post;
			$data = (array) get_post_meta( $post->ID, 'order_data', true );
			$this->define_fields();
			?>
			<div class="panel-wrap jigoshop">
				<div id="order_software_data" class="panel jigoshop_options_panel">
					<?php
					foreach ( $this->order_fields as $field ) {
						if ( 'activation_email' == $field['id'] )
							$value = get_post_meta( $post->ID, 'activation_email', true );
						elseif ( 'transaction_id' == $field['id'] )
							$value = get_post_meta( $post->ID, 'transaction_id', true );
						elseif ( 'old_order_id' == $field['id'] )
							$value = get_post_meta( $post->ID, 'old_order_id', true );
						elseif ( isset( $data[$field['id']] ) )
							$value = $data[$field['id']];
						else
							$value = null;
						$this->admin_field_helper( $field, $value, array( 'form-field', 'jgs-order-field' ) );
					}
					?>
				</div>
			</div>
		<?php
		}

		/**
 		 * admin helper to build out fields used inside meta boxes
		 * helps reduce code duplication
		 * echos/prints the field
		 *
		 * @param $field, field object to build
		 * @param $value, the current value of the field
		 * @return void
		 */
		function admin_field_helper( $field, $value, $field_classes ) {
			printf( '<p class="%s">', esc_attr( implode( ' ', array_map( 'sanitize_html_class', $field_classes ) ) ) );
			printf( '<label for="%s">%s</label>', esc_attr( $field['id'] ), esc_html( $field['label'] ) );

			switch ( $field['type'] ) {
				case 'text' :
				case 'number' :
					printf( '<input type="%s" id="%s" name="%s" value="%s" placeholder="%s"/>', esc_attr( $field['type'] ), esc_attr( $field['id'] ), esc_attr( $field['id'] ), esc_attr( $value ), esc_attr( $field['placeholder'] ) );
					break;
				case 'textarea' :
					printf( '<textarea id="%s" name="%s" placeholder="%s">%s</textarea>', esc_attr( $field['id'] ), esc_attr( $field['id'] ), esc_attr( $field['placeholder'] ), esc_textarea( $value ) );
					break;
				case 'checkbox' :
					printf( '<input type="checkbox" id="%s" name="%s" value="on"%s', esc_attr( $field['id'] ), esc_attr( $field['id'] ), checked( $value, 'on', false ) );
					break;
				case 'select' :
					printf( '<select id="%s" name="%s">', esc_attr( $field['id'] ), esc_attr( $field['id'] ) );
					foreach ( $field['values'] as $value_to_save => $value_nice_name )
						printf( '<option value="%s"%s>%s</option>', esc_attr( $value_to_save ), selected( $value_to_save, $value, false ), esc_html( $value_nice_name ) );
					echo '</select>';
					break;
			}

			echo '</p>';
		}

		/**
		 * adds activations meta box
		 *
		 * @since 1.0
		 * @param object $post the current post object
		 * @return void
		 */
		function activation_meta_box( $post ) {
			$activations = get_post_meta( $post->ID, 'activations', true );
			if ( ! empty( $activations ) ) : ?>
				<table id="activations-table" class="widefat">
					<thead>
						<tr>
							<th><?php _e( 'Instance', 'jigoshop-software' ) ?></th>
							<th><?php _e( 'Status', 'jigoshop-software' ) ?></th>
							<th><?php _e( 'Date & Time', 'jigoshop-software' ) ?></th>
							<th><?php _e( 'Version', 'jigoshop-software' ) ?></th>
							<th><?php _e( 'Operating System', 'jigoshop-software' ) ?></th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<th><?php _e( 'Instance', 'jigoshop-software' ) ?></th>
							<th><?php _e( 'Status', 'jigoshop-software' ) ?></th>
							<th><?php _e( 'Date & Time', 'jigoshop-software' ) ?></th>
							<th><?php _e( 'Version', 'jigoshop-software' ) ?></th>
							<th><?php _e( 'Operating System', 'jigoshop-software' ) ?></th>
						</tr>
					</tfoot>
					<tbody>
						<?php $i = 0; foreach ($activations as $activation) : $i++ ?>
							<tr<?php if ( $i / 2 == 1 ) echo ' class="alternate"' ?>>
								<td><?php echo esc_html( $activation['instance'] ); ?></td>
								<td><?php echo ( $activation['active'] ) ? __( 'Activated', 'jigoshop-software' ) : __( 'Deactivated', 'jigoshop-software' ) ?></td>
								<td><?php echo esc_html( sprintf( _x( '%s at %s', 'date and time of the activation', 'jigoshop-software' ), date( 'D j M Y', $activation['time'] ), date( 'h:ia T', $activation['time'] ) ) ); ?></td>
								<td><?php echo esc_html( $activation['version'] ); ?></td>
								<td><?php echo esc_html( ucwords( $activation['os'] ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p><?php _e( 'No activations yet', 'jigoshop-software' ) ?></p>
			<?php endif;
		}

		/**
		 * saves the data inputed into the order boxes
		 *
		 * @see order_meta_box()
		 * @since 1.0
		 * @return void
		 */
		function order_save_data() {
			global $post;
			$this->define_fields();
			$data = get_post_meta( $post->ID, 'order_data', true );
			foreach ( $this->order_fields as $field ) {
				if ( isset( $_POST[$field['id']] ) ) {
					if ( $field['id'] == 'activation_email' ) {
						update_post_meta( $post->ID, 'activation_email', sanitize_text_field( $_POST['activation_email'] ) );
					} elseif ( $field['id'] == 'transaction_id' ) {
						update_post_meta( $post->ID, 'transaction_id', sanitize_text_field( $_POST['transaction_id'] ) );
					} elseif ( $field['id'] == 'old_order_id' ) {
						update_post_meta( $post->ID, 'old_order_id', sanitize_text_field( $_POST['old_order_id'] ) );
					} else {
						$data[$field['id']] = ( is_array( $_POST[$field['id']] ) ) ? array_map( 'sanitize_text_field', $_POST[$field['id']] ) : sanitize_text_field( $_POST[$field['id']] );
					}
				}
			}

			if ( empty( $_POST['is_upgrade'] ) )
				unset( $data['is_upgrade'] );

			if ( empty( $_POST['has_been_upgraded'] ) )
				unset( $data['has_been_upgraded'] );

			update_post_meta( $post->ID, 'order_data', $data );
			if ( isset( $_POST['resend_email'] ) )
				$this->process_email( $post->ID, 'completed_purchase' );

		}

		/**
		 * displays the meta box which allows further actions to be taken
		 *
		 * @since 1.7
		 * @return void
		 */
		function order_further_actions_meta_box() { ?>
			<ul class="order_actions">
				<li><input type="submit" class="button button-primary" name="resend_email" value="<?php esc_attr_e( 'Resend Email', 'jigoshop-software' ); ?>" /> &mdash; <?php _e( 'Resend Purchase Email' , 'jigoshop-software' ); ?></li>
			</ul>
		<?php
		}

		/**
		 * adds css to the back-end
		 *
		 * @since 2.1
		 * @return void
		 */
		function admin_enqueue() {
			wp_enqueue_style( 'jigoshop_software_backend', plugins_url( 'inc/back-end.css', __FILE__ ), array(), JIGOSHOP_SOFTWARE_VERSION );
		}

		/**
 		 * filter the text displayed when searching for orders
		 *
		 * @since 2.4
		 * @ret
		 */
		function order_get_search_query( $search_query ) {
			global $pagenow;
			if ( 'edit.php' != $pagenow && 'shop_order' != get_post_type() )
				return $search_query;

			$search_query = esc_html( $_GET['s'] );
			return $search_query;
		}

		/**
		 * registers the import page
		 * @since 1.0
		 * @return void
		 */
		function admin_menu() {
			add_submenu_page( 'jigoshop', __( 'Import', 'jigoshop-software' ),  __( 'Import', 'jigoshop-software' ) , 'manage_options', 'jgs_import', array( $this, 'import_page' ) );
		}


		/* =======================================
				filter add to cart & other jigoshop internal functions
		==========================================*/

		/**
		 * enqueue scripts and styles on the frontend
		 *
		 * @since 2.3
		 * @return void
		 */
		function frontend_enqueue() {
			wp_enqueue_style( 'jigoshop_software', plugins_url( 'inc/front-end.css', __FILE__ ), array(), JIGOSHOP_SOFTWARE_VERSION );
		}

		/**
		 * replace the default jigoshop add to cart button
		 *
		 * @see downloadable_add_to_cart()
		 * @since 1.0
		 * @return void
		 */
		function add_to_cart() {
			global $_product; $availability = $_product->get_availability();
			if ($availability['availability']) : ?><p class="stock <?php echo $availability['class'] ?>"><?php echo $availability['availability']; ?></p><?php endif; ?>
			<form action="<?php echo $_product->add_to_cart_url(); ?>" class="cart" method="post">
				<button type="submit" class="button-alt"><?php _e( 'Buy Now', 'jigoshop-software' ); ?></button>
				<?php do_action( 'jigoshop_add_to_cart_form' ); ?>
			</form>
		<?php
		}

		/**
		 * replace the default jigoshop add to cart button
		 *
		 * @see jigoshop_template_loop_add_to_cart()
		 * @since 1.0
		 * @param object $post the current post object
		 * @param object $_product the current product object
		 * @return void
		 */
		function loop_add_to_cart( $post, $_product ) {
			?><a href="<?php echo $_product->add_to_cart_url(); ?>" class="button"><?php _e( 'Buy Now' , 'jigoshop-software' ); ?></a><?php
		}

		/**
		 * empty the cart when an order is cancelled
		 *
		 * @param int the order id
		 * @return void
		 */
		function cancel_order( $order_id ) {
			jigoshop_cart::empty_cart();
		}

		/**
		 * redirect the user to checkout after they've clicked "buy now"
		 *
		 * @see jigoshop_add_to_cart_action()
		 * @since 1.0
		 * @return void
		 */
		function add_to_cart_redirect() {
			return jigoshop_cart::get_checkout_url();
		}

		/**
		 * filters the template for the api page so that it just does the json stuff
		 *
		 * @since 1.0
		 * @param string $template the template file
		 * @return string filtered template file location
		 */
		function locate_api_template( $template ) {
			global $post;
			if ( isset( $post->ID ) && get_option( 'jigoshop_api_page_id' ) == $post->ID )
				return JIGOSHOP_SOFTWARE_PATH . '/inc/api.php';
		}

		/**
		 * runs an output buffer on the price sent to paypal when upgrading
		 * empties the cart before something is added to it
		 *
		 * @see jigoshop_software_filter_price_paypal
		 * @since 2.1.4
		 * @return void
		 */
		function init_actions() {

			if ( !empty( $_GET['add-to-cart'] ) && jigoshop::verify_nonce( 'add_to_cart', '_GET' ) ) {
				jigoshop_cart::empty_cart();
			}

			if ( isset( $_GET['order'] ) && isset( $_GET['key'] ) ) {
				ob_start( array( $this, 'filter_price_paypal' ) );
			}

		}

		/**
		 * very hacky way to filter out the price sent to paypal when it's an upgrade, but the only way to do it w/out changing core jigoshop
		 *
		 * @see $this->init_output_buffer()
		 * @todo find a better a way to do this
		 * @since 1.0
		 * @param string $buffer what's being sent to paypal
		 * @return string $buffer what's being sent to paypal
		 */
		function filter_price_paypal( $buffer ) {
			$order_id = $_GET['order'];
			$data = get_post_meta( $order_id, 'order_data', true );
			if ( ! empty( $data['original_price'] ) ) {
				$original_price = number_format( $data['original_price'], 2 );
				$correct_price = number_format( $data['order_total'], 2 );
				if ( ! empty( $original_price ) ) {
					$buffer = str_replace( '"amount_1" value="' . $original_price . '"', '"amount_1" value="' . $correct_price . '"', $buffer );
				}
			}
			return $buffer;
		}

		/**
		 * process the ajax request for the upgrade page
		 *
		 * @since 2.6
		 * @return void
		 */
		function ajax_jgs_upgrade() {
			$messages = null; // reset in case this a second attempt
			$success = null;
			$message = null;

			// nonce verification
			if ( empty( $_POST['jgs_upgrade_nonce'] ) || ! wp_verify_nonce( $_POST['jgs_upgrade_nonce'], 'jgs_upgrade' ) ) {
				wp_send_json_error( __( 'An error has occurred, please try again.', 'jigoshop-software' ) );
				return;
			}

			// email validation
			if ( empty( $_POST['jgs_email'] ) || ! is_email( $_POST['jgs_email'] ) ) {
				wp_send_json_error( __( 'Please enter a valid email address.', 'jigoshop-software' ) );
				return;
			}

			if ( empty( $_POST['jgs_license_key'] ) ) {
				wp_send_json_error( __( 'Please enter a license key.', 'jigoshop-software' ) );
				return;
			}

			$license_key = sanitize_key( $_POST['jgs_license_key'] );
			$email_address = sanitize_email( $_POST['jgs_email'] );
			$possible_upgrade_ids = $this->get_possible_upgrades_for_order( $license_key, $email_address );
			if ( is_wp_error( $possible_upgrade_ids ) ) {
				wp_send_json_error( $possible_upgrade_ids->get_error_message() );
				return;
			} elseif ( empty( $possible_upgrade_ids ) ) {
				wp_send_json_error( __( 'No possible upgrades found with the provided details.' ) );
				return;
			}

			$this->set_upgrade_cookie( $possible_upgrade_ids, $license_key, $email_address );

			wp_send_json_success( array(
				'success_message' => sprintf( __( 'Here are the possible upgrades for license key %s. Please press "Buy Now" for the upgrade you want.', 'jigoshop-software' ), esc_html( $_POST['jgs_license_key'] ) ),
				'possible_upgrade_products' => do_shortcode( '[products ids="' . implode( ',', $possible_upgrade_ids ) . '"]' ),
			) );
		}

		/**
		 * set a cookie for pre-filling the upgrade screen
		 *
		 * @param $possible_upgrade_ids
		 * @param $license_key
		 * @param $email_address
		 */
		function set_upgrade_cookie( $possible_upgrade_ids, $license_key, $email_address ) {
			if ( ! empty( $_COOKIE['jgs_upgrade_prefill'] ) ) {
				$cookie = json_decode( $_COOKIE['jgs_upgrade_prefill'] );
			}

			if ( empty( $cookie ) || ! is_array( $cookie ) ) {
				$cookie = array();
			}

			foreach( $possible_upgrade_ids as $upgrade_id ) {
				$cookie[$upgrade_id] = array(
					'license_key' => $license_key,
					'email_address' => $email_address,
				);
			}

			$cookie = json_encode( $cookie );
			$expire = time() + DAY_IN_SECONDS;
			setcookie( 'jgs_upgrade_prefill', $cookie, $expire, '/' );
		}

		/**
		 * set a cookie for pre-filling the upgrade screen
		 *
		 * @param $possible_upgrade_ids
		 * @param $license_key
		 * @param $email_address
		 */
		function get_upgrade_prefill_from_cookie( $upgrade_id ) {
			if ( empty( $_COOKIE['jgs_upgrade_prefill'] ) )
				return false;

			$cookie = json_decode( stripslashes( $_COOKIE['jgs_upgrade_prefill'] ) );
			if ( empty( $cookie ) || ! is_object( $cookie ) )
				return false;

			if ( empty( $cookie->{$upgrade_id} ) )
				return false;

			$prefill_values = (array) $cookie->{$upgrade_id};
			return $prefill_values;
		}

		/**
		 * transforms a comma separated list of license keys into an array in order to store in the DB
		 *
		 * @since 1.0
		 * @param string $keys a comma separated list of keys
		 * @return array $keys_array an array of keys
		 */
		function array_ify_keys( $keys = null ) {
			$keys = esc_attr( $keys );
			if ( is_string( $keys ) ) {
				$keys_array = explode( ',', $keys );
				return $keys_array;
			}
			return false;
		}

		/**
		 * transforms an array of license keys into a comma separated list in order to display it
		 *
		 * @since 1.0
		 * @param array $keys the array of keys
		 * @return string $keys_string the string of keys
		 */
		function un_array_ify_keys( $keys = null ) {
			$i = 0;
			$keys_string = '';
			if ( is_array( $keys ) ) {
				foreach ( $keys as $key ) {
					$i++;
					if ( $i != 1 ) $keys_string .= ',';
					$keys_string .= $key;
				}
				$keys_string = ltrim( $keys_string, ',' ); // filter out a comma if there is one in the first character
				$keys_string = ltrim( $keys_string, ' ' ); // filter out a space if there is one in the first character
				return $keys_string;
			}
			return false;
		}

		/**
		 * checks if a key is a valid key for a particular product
		 *
		 * @since 2.2
		 * @param string $license_key the key to validate
		 * @param string $email_address the email address asssociated with the purchase
		 * @param int $product_id the product to validate for
		 * @param string $date date after which purchase must have occurred
		 * @param bool $return_order_id if true, the function will return the order ID that matches the license key, instead of just a bool
		 * @param bool $validate_product_id if true, the function will validate the provided product ID for the license key
		 * @param bool $return_details if true, the function will return details about the order, such as the product ID that matches this license key, note that $return_order_id takes presedence
		 * @return bool|int valid key or not|order_id if $return_order_id is set to true
		 */
		function is_valid_license_key( $license_key = null, $email_address = null, $product_id = null, $date = null, $return_order_id = false, $validate_product_id = true, $return_details = false ) {

			if ( empty( $license_key ) || empty( $email_address ) || ( $validate_product_id && empty( $product_id ) ) )
				return false;

			$orders = get_posts(
				array(
					'post_type' => 'shop_order',
					'posts_per_page' => -1,
					'meta_query' => array(
						array(
							'key' => 'activation_email',
							'value' => $email_address,
						),
					),
				)
			);

			if ( !empty( $orders ) ) {
				foreach ( $orders as $order ) {
					$data = get_post_meta( $order->ID, 'order_data', true );
					if ( ( ! $validate_product_id || ( isset( $data['productid'] ) && $data['productid'] == $product_id ) ) && isset( $data['license_key'] ) && $data['license_key'] == $license_key ) {
						// we have a match, let's make sure it's a completed sale
						$order_status = wp_get_post_terms( $order->ID, 'shop_order_status' );
						$order_status = $order_status[0]->slug;
						if ( $order_status == 'completed' ) {
							// let's make sure the date is within the threshold
							if ( empty( $date ) || get_the_time( 'U', $order->ID ) >= strtotime( $date ) ) {
								// finaly let's make sure it hasn't already been upgraded
								if ( empty( $data['has_been_upgraded'] ) || 'on' != $data['has_been_upgraded'] ) {
									if ( $return_order_id ) {
										return $order->ID;
									} elseif ( $return_details ) {
										return array(
											'order_id' => $order->ID,
											'order_data' => $data,
										);
									} else {
										return true;
									}
								}
							}
						}
					}
				}
			}

			return false;
		}

		/**
		 * checks if the given product is an upgrade for another product
		 *
		 * @since 2.2
		 * @param $product_id the potential upgrade product
		 * @return bool
		 */
		function is_upgradeable_product( $product_id ) {

			$data = get_post_meta( $product_id, 'product_data', true );
			return ( !empty( $data['is_upgrade'] ) && $data['is_upgrade'] );

		}

		/**
		 * returns the ID of the product for which the given product is an upgrade from
		 *
		 * @since 2.2
		 * @param $product_id the potential upgrade product
		 * @return bool
		 */
		function get_upgrade_from_product_id( $product_id ) {

			if ( !$this->is_upgradeable_product( $product_id ) )
				return false;

			$data = get_post_meta( $product_id, 'product_data', true );
			if ( empty( $data['upgrade_from'] ) ) {
				return false;
			} else {
				return $data['upgrade_from'];
			}

		}

		/**
		 * returns the ID of the product for which the given product is an upgrade to
		 *
		 * @since 2.2
		 * @param $product_id the potential upgrade product
		 * @return bool
		 */
		function get_upgrade_to_product_id( $product_id ) {

			if ( !$this->is_upgradeable_product( $product_id ) )
				return false;

			$data = get_post_meta( $product_id, 'product_data', true );
			if ( empty( $data['upgrade_to'] ) ) {
				return false;
			} else {
				return $data['upgrade_to'];
			}

		}

		/**
		 * returns the PRODUCTID of the product for which the given product is an upgrade from
		 *
		 * @since 2.2
		 * @param $product_id the upgraded product
		 * @return bool
		 */
		function get_upgrade_from_product_productid( $product_id ) {

			if ( !$this->is_upgradeable_product( $product_id ) )
				return false;

			$product_id = $this->get_upgrade_from_product_id( $product_id );
			return get_post_meta( $product_id, 'soft_product_id', true );

		}

		/**
		 * returns the date since when the product was upgraded
		 *
		 * @since 2.2
		 * @param $product_id the upgraded product
		 * @return bool
		 */
		function get_upgrade_date_threshold( $product_id ) {

			if ( !$this->is_upgradeable_product( $product_id ) )
				return false;

			$data = get_post_meta( $product_id, 'product_data', true );
			if ( empty( $data['upgrade_date_since'] ) ) {
				return false;
			} else {
				return $data['upgrade_date_since'];
			}

		}

		/**
		 * checks if the given order is an upgrade from another order
		 *
		 * @since 2.3
		 * @param $order_id the order ID
		 * @return bool
		 */
		function is_upgrade_order( $order_id ) {

			$data = get_post_meta( $order_id, 'order_data', true );
			return ( !empty( $data['is_upgrade'] ) && $data['is_upgrade'] );

		}

		/**
		 * given an order that is an upgrade from another order,
		 * gets the original order ID
		 *
		 * @since 2.3
		 * @param $order_id the upgrade order ID
		 * @return bool
		 */
		function get_upgrade_order_orginal_order_id( $order_id ) {

			if ( ! $this->is_upgrade_order( $order_id ) )
				return false;

			$data = get_post_meta( $order_id, 'order_data', true );
			if ( empty( $data['upgraded_from_order_id'] ) ) {
				return false;
			} else {
				return $data['upgraded_from_order_id'];
			}
		}

		/**
		 * given a soft_product_id (e.g. those used for the API)
		 * returns the product's real post ID
		 *
		 * @since 2.6
		 * @param $productid the soft_product_id
		 * @return mixed false if not found or int the post ID
		 */
		function get_product_post_id_from_api_productid( $productid ) {
			$_prod = get_posts(
				array(
					'post_type' => 'product',
					'posts_per_page' => 1,
					'meta_query' => array(
						'relation' => 'OR',
						array(
							'key' => 'soft_product_id',
							'value' => $productid,
						),
					),
				)
			);

			if ( is_array( $_prod ) && count( $_prod ) == 1 )
				return $_prod[0]->ID;

			return false;
		}

		/**
		 * checks if the given order has been upgraded
		 *
		 * @since 2.3
		 * @param $order_id the order ID
		 * @return bool
		 */
		function order_has_been_upgraded( $order_id ) {

			$data = get_post_meta( $order_id, 'order_data', true );
			return ( ! empty( $data['has_been_upgraded'] ) && 'on' == $data['has_been_upgraded'] );

		}

		/**
		 * finds possible upgrades for the given order (license key + email address)
		 *
		 * @since 2.6
		 * @param $license_key string the license key to check for
		 * @param $email_address string the matching email address
		 * @return array possible upgrades or WP_Error in case of an error
		 */
		function get_possible_upgrades_for_order( $license_key, $email_address ) {
			$possible_upgrades = array();
			$order = $this->is_valid_license_key( $license_key, $email_address, null, null, false, false, true );
			$order_id = $order['order_id'];

			if ( empty( $order_id ) )
				return new WP_Error( '500', __( 'No order found with the provided details.' ) );

			if ( $this->order_has_been_upgraded( $order_id ) )
				return new WP_Error( '500', __( 'This order has already been upgraded' ) );

			$product_id = $order['order_data']['productid'];
			$product_post_id = $this->get_product_post_id_from_api_productid( $product_id );

			$all_upgradeable_products =  $this->get_product_upgrade_dropdown( true );
			foreach ( $all_upgradeable_products as $upgrade_id => $upgrade_name ) {
				if ( $this->get_upgrade_from_product_id( $upgrade_id ) == $product_post_id ) {
					$possible_upgrades[] = $upgrade_id;
				}
			}

			return $possible_upgrades;
		}

		/**
		 * generates a unique id that is used as the license code
		 *
		 * @since 1.0
		 * @return string the unique ID
		 */
		function generate_license_key() {

			return sprintf(
				'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
				mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
				mt_rand( 0, 0x0fff ) | 0x4000,
				mt_rand( 0, 0x3fff ) | 0x8000,
				mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
			);

		}

		/**
		 * redirect the user away from cart page, either to checkout or to home
		 *
		 * @since 1.0
		 * @return void
		 */
		function redirect_away_from_cart() {
			if ( is_cart() ) {
				$redirect = ( isset( $_SESSION['cart'] ) ) ? jigoshop_cart::get_checkout_url() : site_url();
				wp_safe_redirect( $redirect ); exit;
			}
		}

		/* =======================================
				ajax, payment & email processing
		==========================================*/

		/**
		 * process the ajax request to checkout
		 *
		 * @since 1.0
		 * @return void
		 */
		function ajax_jgs_checkout() {

			$messages = null; // reset in case this a second attempt
			$success = null;
			$message = null;

			$no_js = ( isset( $_POST['no_js'] ) && $_POST['no_js'] == 'true' );

			if ( $no_js ) {
				wp_safe_redirect( add_query_arg( 'no_js', 'true', jigoshop_cart::get_checkout_url() ) );
				exit;
			}

			$item_id = esc_attr( $_POST['item_id'] );
			$qty = 1; // always 1
			$upgrade = false; // default

			// nonce verification
			if ( isset( $_POST['jgs_checkout_nonce'] ) && !wp_verify_nonce( $_POST['jgs_checkout_nonce'], 'jgs_checkout' ) ) {
				$messages['nonce'] = __( 'An error has occurred, please try again', 'jigoshop-software' );
			}

			// email validation
			if ( empty( $_POST['jgs_email'] ) ) {
				$messages['email'] = __( 'Please enter your email', 'jigoshop-software');
			} else {
				$email = strtolower( esc_attr( $_POST['jgs_email'] ) );
				if ( !is_email( $email ) ) {
					$messages['email'] = __( 'Please enter a valid email address', 'jigoshop-software');
				}
			}

			// upgrade product
			if ( $this->is_upgradeable_product( $item_id ) ) {
				$upgrade = true;
				$key = esc_attr( $_POST['up_key'] );
				$upgrade_from_id = $this->get_upgrade_from_product_id( $item_id );
				$upgrade_from_product_id = $this->get_upgrade_from_product_productid( $item_id );
				$upgrade_to_id = $this->get_upgrade_to_product_id( $item_id );
				$upgrade_date_threshold = $this->get_upgrade_date_threshold( $item_id );
			}

			// key validation
			if ( $upgrade && !empty( $email ) && ( empty( $key ) || ! $this->is_valid_license_key( $key, $email, $upgrade_from_product_id, $upgrade_date_threshold ) ) ) {
				$original_order_id = $this->is_valid_license_key( $key, $email, $upgrade_from_product_id, $upgrade_date_threshold, true );
				if ( ! empty( $original_order_id ) && $this->order_has_been_upgraded( $original_order_id ) ) {
					$messages['key'] = __( 'The key you have entered has already been upgraded, please try again or contact us if you need additional help', 'jigoshop-software' );
				} else {
					$messages['key'] = __( 'The key you have entered is not valid, please try again or contact us if you need additional help', 'jigoshop-software' );
				}
			}

			// if there is no message, then validation passed
			if ( empty( $messages ) ) {

				$success = true;

				$order_data = array(
					'post_type' => 'shop_order',
					'post_title' => 'Order &ndash; ' . date( 'F j, Y @ h:i A' ),
					'post_status' => 'publish',
					'post_author' => 1,
				);

				$product = get_post_meta( $item_id, 'product_data', true );
				$sale_price = get_post_meta( $item_id, 'sale_price', true );
				$regular_price = get_post_meta( $item_id, 'regular_price', true );
				$price = ( !empty( $sale_price ) ) ? $sale_price : $regular_price;

				if ( $upgrade ) {
					$order['is_upgrade'] = 'on';
					$order['upgrade_name'] = get_the_title( $upgrade_from_id );
					$order['upgraded_via'] = get_the_title( $item_id );
					$order['upgraded_to'] = get_the_title( $upgrade_to_id );
					$order['upgrade_price'] = $price;
					$order['upgraded_from_order_id'] = $this->is_valid_license_key( $key, $email, $upgrade_from_product_id, $upgrade_date_threshold, true );
					$product = get_post_meta( $upgrade_to_id, 'product_data', true );
				}

				// Order meta data [from jigoshop]
				$order['billing_email'] = $email;
				$order['payment_method'] = 'paypal';
				$order['order_subtotal'] = $price * $qty;
				$order['order_shipping'] = 0;
				$order['order_discount'] = 0;
				$order['order_tax'] = 0;
				$order['order_shipping_tax']	= 0;
				$order['order_total'] = $order['order_subtotal'];

				// activation stuff
				$order['version'] = $product['version'];
				$order['license_key'] = ( !empty( $product['license_key_prefix'] ) ) ? strtolower( $product['license_key_prefix'] . $this->generate_license_key() ) : $this->generate_license_key();
				$order['activations_possible'] = $product['activations'];
				$order['remaining_activations'] = $product['activations'];
				$order['secret_product_key'] = $product['secret_product_key'];
				$order['paypal_name'] = $product['paypal_name'];
				$order['productid'] = ( $upgrade ) ? get_post_meta( $upgrade_to_id, 'soft_product_id', true ) : get_post_meta( $item_id, 'soft_product_id', true );

				$order_items = array();

				$order_items[] = array(
					'id' 			=> $item_id,
					'name' 		=> get_the_title( $item_id ),
					'qty' 		=> (int) $qty,
					'cost' 		=> $price,
					'taxrate' => 0,
				);

				$order_id = wp_insert_post( $order_data );

				// Update post meta
				update_post_meta( $order_id, 'order_data', $order );
				update_post_meta( $order_id, 'activation_email', $email );
				update_post_meta( $order_id, 'activations', array() ); // store an empty array for use later
				update_post_meta( $order_id, 'order_key', uniqid( 'order_' ) );
				update_post_meta( $order_id, 'order_items', $order_items );
				wp_set_object_terms( $order_id, 'pending', 'shop_order_status' );

				$_order = new jigoshop_order( $order_id );

				// Inserted successfully
				do_action( 'jigoshop_new_order', $order_id );

				// Store Order ID in session
				$_SESSION['order_awaiting_payment'] = $order_id;

				// Process Payment
				$available_gateways = jigoshop_payment_gateways::get_available_payment_gateways();
				$result = $available_gateways['paypal']->process_payment( $order_id );
			} else {
				// building a message string from all of the $messages above
				$message = '';
				foreach ( $messages as $k => $m ) {
					$message .= $m.'<br>';
				}
				$success = false;
				$result = null;
			}

			header( 'Content-Type: application/json' );
			$response = json_encode(
				array(
					'success' => $success,
					'message' => $message,
					'result' => $result,
				)
			);
			echo $response;
			exit;
		}

		/**
		 * ajax_jgs_lost_license()
		 * process the ajax request for a lost license request
		 * @since 1.0
		 */
		function ajax_jgs_lost_license() {

			$messages = null; // reset in case this a second attempt
			$success = null;
			$message = null;

			$no_js = ( isset( $_POST['no_js'] ) && $_POST['no_js'] == 'true' ) ? true : false;

			/**
			 * @todo  use add_query_arg
			 */
			if ( $no_js ) wp_safe_redirect( get_permalink( get_option( 'jigoshop_lost_license_page_id' ) ) . '?no-js=true' );

			// nonce verification
			if ( $_POST['jgs_lost_license_nonce'] && !wp_verify_nonce( $_POST['jgs_lost_license_nonce'], 'jgs_lost_license' ) ) $messages['nonce'] = __( 'An error has occurred, please try again', 'jigoshop-software' );

			// email validation
			$email = esc_attr( $_POST['jgs_email'] );
			if ( !$email || $email == '' ) $messages['email'] = __( 'Please enter your email', 'jigoshop-software' );
			elseif ( !is_email( $email ) ) $messages['email'] = __( 'Please enter a valid email address', 'jigoshop-software' );
			else {
				$_orders = get_posts(
					array(
						'post_type' => 'shop_order',
						'posts_per_page' => -1,
						'meta_query' => array(
							array(
								'key' => 'activation_email',
								'value' => $email,
							),
						),
					)
				);
				if ( !is_array( $_orders ) || count( $_orders ) < 1 ) {
					$messages['email'] = __( 'There are no purchase records for this email address. Please try again. If you think there is a mistake, please contact us.', 'jigoshop-software' );
				}
			}

			// if there is no message, then validation passed
			if ( !$messages ) {
				$data['email'] = $email;

				// loop through the orders
				$i = 0;
				foreach ( $_orders as $order ) {
					$order_status = wp_get_post_terms( $order->ID, 'shop_order_status' );
					$order_status = $order_status[0]->slug;
					// make sure it's a completed order
					if ( $order_status == 'completed' ) {
						$i++;
						$order_data = get_post_meta( $order->ID, 'order_data', true );
						$order_items = get_post_meta( $order->ID, 'order_items', true );
						$data['purchases'][$i]['product'] = $order_items[0]['name'];
						$data['purchases'][$i]['price'] = $order_items[0]['cost'];
						$data['purchases'][$i]['date'] = get_the_time( 'l, F j Y', $order->ID );
						$data['purchases'][$i]['activation_email'] = get_post_meta( $order->ID, 'activation_email', true );
						$data['purchases'][$i]['license_key'] = ( $this->order_has_been_upgraded( $order->ID ) ) ? __( 'Upgraded and Deactivated', 'jigoshop-software' ) : $order_data['license_key'];
						$data['purchases'][$i]['order_total'] = $order_items[0]['cost'];
						$data['purchases'][$i]['remaining_activations'] = $order_data['remaining_activations'];
						$data['purchases'][$i]['activations_possible'] = $order_data['activations_possible'];
					}
				}

				// are there completed orders ?
				if ( isset( $data['purchases'] ) && is_array( $data['purchases'] ) && count( $data['purchases'] ) > 0 ) {
					$success = true;
					$this->process_email( $data, 'lost_license' );
					$message = __( 'Your request has been accepted. You should receive an email shortly with all of your purchase history.', 'jigoshop-software' );
				} else {
					$success = false;
					$message = __( 'Your purchases are not completed. If you think there is a mistake, please contact us.', 'jigoshop-software' );
				}
			} else {
				// building a message string from all of the $messages above
				$message = '';
				foreach ( $messages as $k => $m ) {
					$message .= $m.'<br>';
				}
				$success = false;
				$result = null;
			}

			header( 'Content-Type: application/json' );
			$response = json_encode(
				array(
					'success' => $success,
					'message' => $message,
				)
			);
			echo $response;
			exit;
		}

		/**
		 * processes the order post payment
		 *
		 * @since 1.6
		 * @param int $order_id the order id to process
		 * @return void
		 */
		function post_paypal_payment( $post_data ) {
			if ( ! empty( $post_data['transaction_subject'] ) && ! empty ( $post_data['txn_id'] ) ) {
				update_post_meta( absint( $post_data['transaction_subject'] ), 'transaction_id', $post_data['txn_id'], true );
			}
		}

		/**
		 * sends out the completed order email
		 * & empties the cart
		 *
		 * @since 1.6
		 * @param int $order_id the order id to process
		 * @return void
		 */
		function completed_order( $order_id ) {
			jigoshop_cart::empty_cart();

			// if this is an upgrade to a previous order, mark the previous order as having been upgraded
			if ( $this->is_upgrade_order( $order_id ) ) {
				$original_order_id = $this->get_upgrade_order_orginal_order_id( $order_id );
				$data = get_post_meta( $original_order_id, 'order_data', true );
				$data['has_been_upgraded'] = 'on';
				$data['upgraded_to_order_id'] = $order_id;
				update_post_meta( $original_order_id, 'order_data', $data );
			}

			$this->process_email( $order_id, 'completed_purchase' );
		}


		/**
		 * process emails and send them out
		 *
		 * @since 1.0
		 * @return void
		 */
		function process_email( $data, $type ) {

			// switch based on the hook that was fired
			switch ($type) :

				case 'completed_purchase' :

					$order_id = $data;
					$order = new jigoshop_order( $order_id );

					$date = date( 'l, F j Y', time() );
					$data = get_post_meta( $order_id, 'order_data', true );
					$products = get_post_meta( $order_id, 'order_items', true );
					$product = $products[0]['name'];
					$price = $products[0]['cost'];
					$email = get_post_meta( $order_id, 'activation_email', true );
					$total = $price;
					$max_activations = $data['activations_possible'];
					$license_key = $data['license_key'];
					$paypal_name = $data['paypal_name'];

					$send_to = get_post_meta( $order_id, 'activation_email', true );
					$subject = $product . ' ' . __( 'Purchase Confirmation','jigoshop-software' );
					$message = file_get_contents( JIGOSHOP_SOFTWARE_PATH . '/inc/email-purchase.txt' );
					$message = str_replace( '{date}', $date, $message );
					$message = str_replace( '{product}', $product, $message );
					$message = str_replace( '{license_key}', $license_key, $message );
					$message = str_replace( '{price}', $price, $message );
					$message = str_replace( '{email}', $email, $message );
					$message = str_replace( '{total}', $total, $message );
					$message = str_replace( '{max_activations}', $max_activations, $message );
					$message = str_replace( '{paypal_name}', $paypal_name, $message );

					break;

				case 'lost_license' :

					$subject = __( 'Recovered Licenses', 'jigoshop-software' );
					$send_to = $data['email'];
					$message = file_get_contents( JIGOSHOP_SOFTWARE_PATH . '/inc/email-lost-license.txt' );
					$orders = '';

					$i = 0;
					foreach ( $data['purchases'] as $purchase ) {
						$i++;
						$orders .=
							'====================================================================='."\n"
								.__( 'Order', 'jigoshop-software' ) . ' '.$i.''."\n"
								.'====================================================================='."\n\n"
								.__( 'Item', 'jigoshop-software' ) . ': '.$purchase['product']."\n"
								.__( 'Item Price', 'jigoshop-software' ) . ': $'.$purchase['price']."\n"
								.__( 'Purchase date', 'jigoshop-software' ) . ': '.$purchase['date']."\n\n"
								.__( 'Account Name', 'jigoshop-software' ) . ': '.$purchase['activation_email']."\n"
								.__( 'License Key', 'jigoshop-software' ) . ': '.$purchase['license_key']."\n"
								.__( 'Transaction Total', 'jigoshop-software' ) . ': $' . $purchase['order_total'] . ' ' . __( 'via paypal', 'jigoshop-software' ) . "\n"
								.__( 'Currency', 'jigoshop-software' ) . ': USD'."\n"
								.__( 'Activations', 'jigoshop-software' ) . ': ' . $purchase['remaining_activations'] . ' ' . __( 'out of', 'jigoshop-software' ) . ' ' . $purchase['activations_possible'] . ' ' . __( 'activations remaining', 'jigoshop-software' ) . "\n\n\n";
					}

					$message = str_replace( '{orders}', $orders, $message );

					break;

				case 'new_activation' :

					$subject = $data['product'] . ' ' . __( 'Activation Confirmation', 'jigoshop-software' );
					$send_to = $data['email'];
					$message = file_get_contents( JIGOSHOP_SOFTWARE_PATH . '/inc/email-activation.txt' );
					$date = date( 'l, F j Y', time() );
					$message = str_replace( '{date}', $date, $message );
					$message = str_replace( '{remaining_activations}', $data['remaining_activations'], $message );
					$message = str_replace( '{activations_possible}', $data['activations_possible'], $message );
					$message = str_replace( '{product}', $data['product'], $message );

					break;

			endswitch;

			$message = str_replace( '{site_url}', site_url(), $message );
			$headers = 'From: ' . get_bloginfo( 'name' ) . ' <' . get_bloginfo( 'admin_email' ) . '>' . "\r\n";
			wp_mail( $send_to, $subject, $message, $headers );

		}

		/* =======================================
				import
		==========================================*/

		/**
		 * creates a backend page for the importer
		 *
		 * @since 1.1
		 * @return void
		 */
		function import_page() { ?>
			<div class="wrap jigoshop">
				<div class="icon32 icon32-jigoshop-debug" id="icon-jigoshop"><br/></div>
				<h2><?php _e( 'Import', 'jigoshop-software' ) ?></h2>

				<div class="metabox-holder" style="margin-top:25px">
					<div class="postbox-container" style="width:700px;">

						<div class="postbox">
							<h3><?php _e( 'Enter the path/filename of the import file', 'jigoshop-software' ) ?></h3>
							<div class="inside">
								<p><strong><?php _e( 'Recommended', 'jigoshop-software' ) ?>:</strong> <?php _e( 'back up the database before proceeding', 'jigoshop-software' ) ?></p>
								<form id="jgs_import" action="<?php echo admin_url( 'admin-ajax.php' ) ?>" method="post">
									<?php $value = ( isset( $_POST['import_path'] ) && $_POST['import_path'] != '' ) ? esc_attr( $_POST['import_path'] ) : 'wp-content/plugins/jigoshop-software/inc/import.php' ?>
									<p><?php echo ABSPATH ?><input type="text" style="width: 350px" id="import_path" name="import_path" value="<?php echo $value ?>"></p>
									<? wp_nonce_field('jgs_import', 'jgs_import'); ?>
									<input type="hidden" name="action" value="jgs_import">
									<p><div class="jgs_loader"><input type="submit" class="button-primary" name="submit" value="Begin Import"></div></p>
									<p id="jgs_import_feedback"></p>
									<p id="jgs_import_feedback_done"></p>
								</form>
								<script>
									jQuery(document).ready(function($){
										$('#jgs_import').submit(function(e){
											e.preventDefault();
											var load = $('#jgs_import .jgs_loader');
											load.addClass('loading');
											var args = {};
											var inputs = $(this).serializeArray();
											$.ajaxSetup({timeout: 0}); // make the timeout be 0
											$.each(inputs,function(i,input) { args[input['name']]=input['value']; });
											$.post("<?php echo admin_url( 'admin-ajax.php' ) ?>", args, function(response){
												if (response.success) {
													$('#jgs_import_feedback').addClass('doing_import').html("<?php _e( 'Importing', 'jigoshop-software' ) ?>" + response.total_count + " <?php _e( 'records. Please be patient.', 'jigoshop-software' ) ?>").fadeIn();
													args['action']='jgs_do_import';
													args['import']=response.import;
													$.post("<?php echo admin_url( 'admin-ajax.php' ) ?>", args, function(resp){
														if (resp.success) {
															load.removeClass('loading');
															$('#jgs_import_feedback').fadeOut('normal', function(){
																$(this).html("<?php _e( 'All Records Imported!', 'jigoshop-software' ) ?>").fadeIn();
																$('#jgs_import_feedback_done').html(resp.feedback).fadeIn();
															});
														} else {
															load.removeClass('loading');
															$('#jgs_import_feedback').fadeOut('normal', function(){
																$(this).removeClass('doing_import').html("<?php _e( 'An error has occurred and the import did not complete, please refresh the page and try again.', 'jigoshop-software' ) ?>").fadeIn();
															});
														}
													});
												} else {
													if (response.success === false) {
														load.removeClass('loading');
														$('#jgs_import_feedback').html(response.message).fadeIn();
													} else {
														load.removeClass('loading');
														$('#jgs_import_feedback').html("<?php _e( 'An error has occurred, please refresh the page and try again.', 'jigoshop-software' ) ?>").fadeIn();
													}
												}
											});
											return false; // prevent submit (redundant)
										});
									});
								</script>
							</div>
						</div>
					</div>
				</div>

			</div>
		<?php
		}

		/**
		 * import_ajax()
		 * ajax import step 1
		 * @since 1.1
		 */
		function import_ajax() {

			$success = false;
			$messages = null;
			$total_count = 0;
			$import = null;


			$import_path = esc_attr( $_POST['import_path'] );
			$file_path = ABSPATH . $import_path;
			if ( is_file( $file_path ) ) {
				include_once( $file_path );
				if ( !is_array( $import ) ) $messages['missing_array'] = __( 'This file doesn\'t contain an $import array, please try again.', 'jigoshop-software' );
			} else {
				$messages['missing_file'] = __( 'This file doesn\'t exist or doesn\'t have read permissions, please try again.', 'jigoshop-software' );
			}

			// if there is no message, then validation passed
			if ( !$messages ) {
				$total_count = count( $import );
				$success = true;

				header( 'Content-Type: application/json' );
				$response = json_encode(
					array(
						'success' => $success,
						'total_count' => $total_count,
						'import' => $import,
					)
				);
				echo $response;
				exit;
			} else {
				// building a message string from all of the $messages above
				$message = '';
				foreach ( $messages as $k => $m ) {
					$message .= $m.'<br>';
				}
				$success = false;
				$result = null;
			}

			header( 'Content-Type: application/json' );
			$response = json_encode(
				array(
					'success' => $success,
					'message' => $message,
				)
			);
			echo $response;
			exit;


		}


		/**
		 * import()
		 * import routine
		 * @since 1.1
		 * @todo  {@internal} params
		 */
		function import( $import = null ) {

			if ( !$import ) {
				$import = $_POST['import'];
			}

			$failures = array();
			$duplicate = array();
			$succesful = array();

			foreach ( $import as $imp ) {
				// gather the fields
				$date = strtotime( $imp['purchase_time'] );
				$email = ( is_email( $imp['email'] ) ) ? strtolower( $imp['email'] ) : '';
				$price = $imp['amount'];
				$product_id = $imp['product_id'];
				$license_key = $imp['license_key'];
				$payment_type = $imp['payment_type'];
				$old_order_id = $imp['order_id'];

				// double check this order doesn't exist already
				$_duplicate = get_posts( array( 'post_type' => 'shop_order', 'meta_query' => array( array( 'key' => 'old_order_id', 'value' => $old_order_id ) ) ) );
				$_duplicate = @$_duplicate[0];
				if ( is_object( $_duplicate ) ) {
					$duplicate[] = $old_order_id;
				} else {
					// fetch the product & associated meta information
					$_item_id = get_posts( array( 'post_type' => 'product', 'meta_query' => array( array( 'key' => 'soft_product_id', 'value' => $product_id ) ) ) );
					$item_id = @$_item_id[0];
					if ( is_object( $item_id ) ) {
						$item_id = $item_id->ID;
						$product = get_post_meta( $item_id, 'product_data', true );
						$order_items = array();
						$order_items[] = array(
							'id' 			=> $item_id,
							'name' 		=> get_the_title( $item_id ),
							'qty' 		=> (int) 1,
							'cost' 		=> $price,
							'taxrate' => 0,
						);
					} else {
						$order_items = array();
						$order_items[] = array(
							'id' 		=> 'n/a',
							'name' 		=> $product_id,
							'qty' 		=> (int) 1,
							'cost' 		=> $price,
							'taxrate' 	=> 0,
						);
						$product = null;
					}

					// payment type
					if ($payment_type == 'PP - PayPal' || $payment_type == 'PP- PayPal') $payment_type = 'paypal';
					if ($payment_type == 'CH - Credit Card' || $payment_type == 'CH- Credit Card') $payment_type = 'credit card';

					// Order meta data [from jigoshop]
					$order['billing_email'] = $email;
					$order['payment_method'] = $payment_type;
					$order['order_subtotal'] = $price;
					$order['order_shipping'] = 0;
					$order['order_discount'] = 0;
					$order['order_tax'] = 0;
					$order['order_shipping_tax']	= 0;
					$order['order_total'] = $order['order_subtotal'];

					$order['version'] = 'n/a';
					$order['license_key'] = $license_key;
					$order['activations_possible'] = 3;
					$order['remaining_activations'] = 3;
					$order['secret_product_key'] = @$product['secret_product_key'];
					$order['paypal_name'] = @$product['paypal_name'];
					$order['productid'] = $product_id;

					$order_data = array(
						'post_type' => 'shop_order',
						'post_title' => 'Order &ndash; ' . date( 'F j, Y @ h:i A', $date ),
						'post_status' => 'publish',
						'post_author' => 1,
						'post_date' => date( 'Y-m-d H:i:s', $date ),
					);

					$order_id = wp_insert_post( $order_data );

					if ( is_wp_error( $order_id ) ) {
						$failures[] = $old_order_id;
					} else {
						$_order = new jigoshop_order( $order_id );

						// Update post meta
						update_post_meta( $order_id, 'order_data', $order );
						update_post_meta( $order_id, 'old_order_id', $old_order_id );
						update_post_meta( $order_id, 'activation_email', $email );
						update_post_meta( $order_id, 'activations', array() ); // store an empty array for use later
						update_post_meta( $order_id, 'order_key', uniqid( 'order_' ) );
						update_post_meta( $order_id, 'order_items', $order_items );
						wp_set_object_terms( $order_id, 'completed', 'shop_order_status' );

						$succesful[] = $order_id;
					}
				}
			}

			foreach ( $failures as $fail ) {
				$feedback[] = __( 'The following record failed to import', 'jigoshop-software' ) . ': ORDER ID = ' . $fail;
			}

			foreach ( $duplicate as $dupe ) {
				$feedback[] = __( 'The following record was a duplicate', 'jigoshop-software' ) . ': ORDER ID = ' . $dupe;
			}

			$feedback[] = count( $succesful ) . ' ' . __( 'records successfully imported.', 'jigoshop-software' );

			$feedback_string = '<ul>';
			foreach ( $feedback as $fdb ) {
				$feedback_string .= '<li>' . $fdb . '</li>';
			}
			$feedback_string .= '<ul>';

			header( 'Content-Type: application/json' );
			$response = json_encode(
				array(
					'success' => true,
					'feedback' => $feedback_string,
				)
			);
			echo $response;
			exit;

		}

	} // end class

	/**
	 * init the class
	 *
	 * @since 1.0
	 * @return void
	 */
	add_action( 'plugins_loaded', 'jigoshop_software_init', 1 );
	function jigoshop_software_init() {
		global $jigoshopsoftware;
		$jigoshopsoftware = new Jigoshop_Software();
		include_once( 'inc/shortcodes.php' );
	}

} // end class exists

/**
 * run the plugin activation hook
 *
 * @since 1.0
 */
register_activation_hook( __FILE__, array( 'jigoshop_software', 'activation' ) );
jigoshop_software::define_constants();

if ( is_admin() ) {
	include_once( 'inc/_updater.php' );
	$config = array(
		'slug' => JIGOSHOP_SOFTWARE_SLUG,
		'proper_folder_name' => JIGOSHOP_SOFTWARE_PROPER_NAME,
		'api_url' => JIGOSHOP_SOFTWARE_GITHUB_API_URL,
		'raw_url' => JIGOSHOP_SOFTWARE_GITHUB_RAW_URL,
		'github_url' => JIGOSHOP_SOFTWARE_GITHUB_URL,
		'zip_url' => JIGOSHOP_SOFTWARE_GITHUB_ZIP_URL,
		'requires' => JIGOSHOP_SOFTWARE_REQUIRES_WP,
		'tested' => JIGOSHOP_SOFTWARE_TESTED_WP,
		'readme' => 'README.md',
	);
	$github_updater = new wp_github_updater( $config );
}
