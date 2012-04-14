<?php
/*
Plugin Name: JigoShop - Software Add-On
Plugin URI: https://github.com/jkudish/JigoShop-Software-Add-on/
Description: Extends JigoShop to a full-blown software shop, including license activation, license retrieval, activation e-mails and more
Version: 2.1.2
Author: Joachim Kudish
Author URI: http://jkudish.com
License: GPL v2
Text Domain: jigoshop-software
*/

/**
	* @version 2.1.2
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

if ( !class_exists( 'Jigoshop_Software' ) ) {
	class Jigoshop_Software {

		/**
		 * the product fields
		 * @var array
		 */
		static $product_fields;

		/**
		 * the order fields
		 * @var array
		 */
		static $order_fields;

	/**
		* class constructor
		* plugin activation, hooks & filters, etc..
		*
		* @since 1.0
		* @return void
		*/
		function __construct() {

			$this->define_constants();
			$this->define_fields();

			// set the right time zone from WP options
			if ( get_option( 'timezone_string' ) != '' ) {
				date_default_timezone_set( get_option( 'timezone_string' ) );
			}

			/**
			 * hooks
			 */

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

			add_action( 'admin_head', array( $this, 'filter_order_search' ) );


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
			add_filter( 'init', array( $this, 'init_output_buffer' ) );

			add_action( 'wp_print_styles', array( $this, 'print_styles' ) );
			add_action( 'wp_head', array( $this, 'redirect_away_from_cart' ) );
			add_action( 'wp_ajax_nopriv_jgs_checkout', array( $this, 'ajax_jgs_checkout' ) );
			add_action( 'wp_ajax_jgs_checkout', array( $this, 'ajax_jgs_checkout' ) );
			add_action( 'wp_ajax_nopriv_jgs_lost_license', array( $this, 'ajax_jgs_lost_license' ) );
			add_action( 'wp_ajax_jgs_lost_license', array( $this, 'ajax_jgs_lost_license' ) );

			// payment stuff
			add_action( 'thankyou_paypal', array( $this, 'post_paypal_payment' ) );

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
 			* defines the constants we need for the plugin
 			*
			* @since 1.3
			* @return void
			*/
		function define_constants() {
			if ( !defined( 'JIGOSHOP_SOFTWARE_PATH' ) ) define( 'JIGOSHOP_SOFTWARE_PATH', dirname( __FILE__ ) );
			if ( !defined( 'JIGOSHOP_SOFTWARE_SLUG' ) ) define( 'JIGOSHOP_SOFTWARE_SLUG', plugin_basename( __FILE__ ) );
			if ( !defined( 'JIGOSHOP_SOFTWARE_PROPER_NAME' ) ) define( 'JIGOSHOP_SOFTWARE_PROPER_NAME', 'jigoshop-software' );
			if ( !defined( 'JIGOSHOP_SOFTWARE_GITHUB_URL' ) ) define( 'JIGOSHOP_SOFTWARE_GITHUB_URL', 'https://github.com/jkudish/JigoShop-Software-Add-on' );
			if ( !defined( 'JIGOSHOP_SOFTWARE_GITHUB_ZIP_URL' ) ) define( 'JIGOSHOP_SOFTWARE_GITHUB_ZIP_URL', 'https://github.com/jkudish/JigoShop-Software-Add-on/zipball/master' );
			if ( !defined( 'JIGOSHOP_SOFTWARE_GITHUB_API_URL' ) ) define( 'JIGOSHOP_SOFTWARE_GITHUB_API_URL', 'https://api.github.com/repos/jkudish/JigoShop-Software-Add-on' );
			if ( !defined( 'JIGOSHOP_SOFTWARE_GITHUB_RAW_URL' ) ) define( 'JIGOSHOP_SOFTWARE_GITHUB_RAW_URL', 'https://raw.github.com/jkudish/JigoShop-Software-Add-on/master' );
			if ( !defined( 'JIGOSHOP_SOFTWARE_REQUIRES_WP' ) ) define( 'JIGOSHOP_SOFTWARE_REQUIRES_WP', '3.0' );
			if ( !defined( 'JIGOSHOP_SOFTWARE_TESTED_WP' ) ) define( 'JIGOSHOP_SOFTWARE_TESTED_WP', '3.3' );
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
				array( 'id' => 'is_software', 'label' => __( 'This product is Software', 'jigoshop-software' ), 'title' => __( 'This product is Software', 'jigoshop-software' ), 'placeholder' => '', 'type' => 'checkbox' ),
				array( 'id' => 'soft_product_id', 'label' => __( 'Product ID to use for API', 'jigoshop-software' ), 'title' => __( 'Product ID to use for API', 'jigoshop-software' ), 'placeholder' => __( 'ex: PRODUCT1', 'jigoshop-software' ), 'type' => 'text' ),
				array( 'id' => 'license_key_prefix', 'label' => __( 'Prefix for License Key', 'jigoshop-software' ), 'title' => __( 'Optional prefix for the license key', 'jigoshop-software' ), 'placeholder' => __( 'ex: SC-', 'jigoshop-software' ), 'type' => 'text' ),
				array( 'id' => 'secret_product_key', 'label' => __( 'Secret Product Key to use for API', 'jigoshop-software' ), 'title' => __( 'Secret Product Key to use  for API', 'jigoshop-software' ), 'placeholder' => __( 'any random string', 'jigoshop-software' ), 'type' => 'text' ),
				array( 'id' => 'version', 'label' => __( 'Version Number', 'jigoshop-software' ), 'title' => __( 'Version Number', 'jigoshop-software' ), 'placeholder' => __( 'ex: 1.0', 'jigoshop-software' ), 'type' => 'text' ),
				array( 'id' => 'activations', 'label' => __( 'Amount of activations possible', 'jigoshop-software' ), 'title' => __( 'Amount of activations possible', 'jigoshop-software' ), 'placeholder' => __( 'ex: 5', 'jigoshop-software' ), 'type' => 'text' ),
				array( 'id' => 'trial', 'label' => __( 'Trial Period (amount of days or hours)', 'jigoshop-software' ), 'title' => __( 'Trial Period (amount of days or hours)', 'jigoshop-software' ), 'placeholder' => __( 'ex: 15', 'jigoshop-software' ), 'type' => 'text' ),
				array( 'id' => 'trial_unit', 'label' => __( 'Trial Units', 'jigoshop-software' ), 'title' => __( 'Trial Units', 'jigoshop-software' ), 'type' => 'select', 'values' => array( 'days' => 'Days', 'hours' => 'Hours' ) ),
				array( 'id' => 'upgradable_product', 'label' => __( 'Upgradable Product Name', 'jigoshop-software' ), 'title' => __( 'Upgradable Product Name', 'jigoshop-software' ), 'placeholder' => '', 'type' => 'text' ),
				array( 'id' => 'up_license_keys', 'label' => __( 'Upgradable Product Keys', 'jigoshop-software' ), 'title' => __( 'Upgradable Product Keys', 'jigoshop-software' ), 'placeholder' => __( 'Comma separated list', 'jigoshop-software' ), 'type' => 'textarea' ),
				array( 'id' => 'used_license_keys', 'label' => __( 'Used Upgrade Keys', 'jigoshop-software' ), 'title' => __( 'Used Upgrade Keys', 'jigoshop-software' ), 'placeholder' => __( 'Comma separated list', 'jigoshop-software' ), 'type' => 'textarea' ),
				array( 'id' => 'up_price', 'label' => __( 'Upgrade Price ($)', 'jigoshop-software' ), 'title' => __( 'Upgrade Price ($)', 'jigoshop-software' ), 'placeholder' => __( 'ex: 1.00', 'jigoshop-software' ), 'type' => 'text' ),
				array( 'id' => 'paypal_name', 'label' => __( 'Paypal Name to show on transaction receipts', 'jigoshop-software' ), 'title' => __( 'Paypal Name to show on transaction receipts', 'jigoshop-software' ), 'placeholder' => __( 'ex: Google Inc.', 'jigoshop-software' ), 'type' => 'text' ),
			);

			$this->order_fields = array(
				array( 'id' => 'activation_email', 'label' => __( 'Activation Email', 'jigoshop-software' ), 'title' => __( 'Activation Email', 'jigoshop-software' ), 'placeholder' => '', 'type' => 'text' ),
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
				array( 'id' => 'upgrade_price', 'label' => __( 'Upgrade price ($)', 'jigoshop-software' ), 'title' => __( 'Upgrade price ($)', 'jigoshop-software' ), 'placeholder' => '', 'type' => 'text' ),
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
			if ( !is_plugin_active( 'jigoshop/jigoshop.php' ) ) {
				deactivate_plugins( plugin_basename( __FILE__ ) );
				wp_die( sprintf( _x( 'The JigoShop Software Add-On requires %s to be activated in order to work. Please activate %s first.', 'A link to JigoShop is provided in the placeholders', 'jigoshop-software' ), '<a href="http://jigoshop.com" target="_blank">JigoShop</a>', '<a href="http://jigoshop.com" target="_blank">JigoShop</a>' ) . '<a href="'.admin_url( 'plugins.php' ).'"> <br> &laquo; ' . _x( 'Go Back', 'Activation failed, so go back to the plugins page', 'jigoshop-software' ) . '</a>' );
			}

			// creates the lost license page with the right shortcode in it
			$lost_license_page_id = get_option( 'jigoshop_lost_license_page_id' );
			if ( !$lost_license_page_id || $lost_license_page_id == '' ) {
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
			if ( !$jigoshop_api_page_id || $jigoshop_api_page_id == '' ) {
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
		?>
			<li><a href="#software_data"><?php _e( 'Software', 'jigoshop-software' ); ?></a></li>
		<?php
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
		?>
			<div id="software_data" class="panel jigoshop_options_panel">
			<?php
				foreach ($this->product_fields as $field) :
					if ( $field['id'] == 'soft_product_id' ) $value = get_post_meta( $post->ID, 'soft_product_id', true );
					else @$value = ( $field['id'] == 'up_license_keys' || $field['id'] == 'used_license_keys' ) ? $this->un_array_ify_keys( $data[$field['id']] ) : $data[$field['id']];
					switch ($field['type']) :
						case 'text' :
							echo '<p class="form-field"><label for="'.$field['id'].'">'.$field['label'].'</label><input type="text" id="'.$field['id'].'" name="'.$field['id'].'" value="'.$value.'" placeholder="'.$field['placeholder'].'"/></p>';
						break;
						case 'number' :
							echo '<p class="form-field"><label for="'.$field['id'].'">'.$field['label'].'</label><input type="number" id="'.$field['id'].'" name="'.$field['id'].'" value="'.$value.'" placeholder="'.$field['placeholder'].'"/></p>';
						break;
						case 'textarea' :
							echo '<p class="form-field"><label for="'.$field['id'].'">'.$field['label'].'</label><textarea id="'.$field['id'].'" name="'.$field['id'].'" placeholder="'.$field['placeholder'].'">'.$value.'</textarea></p>';
						break;
						case 'checkbox' :
							$checked = ($value == 'on') ? ' checked=checked' : '';
							echo '<p class="form-field"><label for="'.$field['id'].'">'.$field['label'].'</label><input type="checkbox" id="'.$field['id'].'" name="'.$field['id'].'" value="on"'.$checked.'></p>';
						break;
						case 'select' :
							echo '<p class="form-field"><label for="'.$field['id'].'">'.$field['label'].'</label><select id="'.$field['id'].'" name="'.$field['id'].'">';
							foreach ($field['values'] as $k => $v) :
								$selected = ($value == $k) ? ' selected="selected"' : '';
								echo '<option value="'.$k.'"'.$selected.'>'.$v.'</option>';
							endforeach;
							echo '</select></p>';
						break;
					endswitch;
				endforeach;
				?>
			</div>
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
			$data = get_post_meta( $post->ID, 'product_data', true );
			foreach ( $this->product_fields as $field ) {
				if ( $field['id'] == 'up_license_keys' || $field['id'] == 'used_license_keys' ) {
					$data[$field['id']] = $this->array_ify_keys( $_POST[$field['id']] );
				} elseif ( $field['id'] == 'soft_product_id' ) {
					update_post_meta( $post->ID, 'soft_product_id', $_POST[$field['id']] );
				} else {
					$data[$field['id']] = esc_attr( $_POST[$field['id']] );
				}
			}
			update_post_meta( $post->ID, 'product_data', $data );
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
						foreach ($this->order_fields as $field) :
							if ( $field['id'] == 'activation_email' ) {
								$value = get_post_meta( $post->ID, 'activation_email', true );
							} elseif ( $field['id'] == 'transaction_id' ) {
								$value = get_post_meta( $post->ID, 'transaction_id', true );
							} elseif ( $field['id'] == 'old_order_id' ) {
								$value = get_post_meta( $post->ID, 'old_order_id', true );
							} elseif ( isset( $data[$field['id']] ) ) {
								$value = $data[$field['id']];
							} else {
								$value = null;
							}
							switch ($field['type']) :
								case 'text' :
									echo '<p class="form-field"><label for="'.$field['id'].'">'.$field['label'].'</label><input type="text" id="'.$field['id'].'" name="'.$field['id'].'" value="'.$value.'" placeholder="'.$field['placeholder'].'"/></p>';
								break;
								case 'number' :
									echo '<p class="form-field"><label for="'.$field['id'].'">'.$field['label'].'</label><input type="number" id="'.$field['id'].'" name="'.$field['id'].'" value="'.$value.'" placeholder="'.$field['placeholder'].'"/></p>';
								break;
								case 'textarea' :
									echo '<p class="form-field"><label for="'.$field['id'].'">'.$field['label'].'</label><textarea id="'.$field['id'].'" name="'.$field['id'].'" placeholder="'.$field['placeholder'].'">'.$value.'</textarea></p>';
								break;
								case 'checkbox' :
									$checked = ($value == 'on') ? ' checked=checked' : '';
									echo '<p class="form-field"><label for="'.$field['id'].'">'.$field['label'].'</label><input type="checkbox" id="'.$field['id'].'" name="'.$field['id'].'" value="on"'.$checked.'</p>';
								break;
							endswitch;
						endforeach;
						?>
					</div>
			</div>
		<?php
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
		  if ( is_array( $activations ) && count( $activations ) > 0 ) { ?>
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
			           <td><?php echo $activation['instance'] ?></td>
			           <td><?php echo ( $activation['active'] ) ? __( 'Activated', 'jigoshop-software' ) : __( 'Deactivated', 'jigoshop-software' ) ?></td>
			           <td><?php echo date( 'D j M Y', $activation['time'] ).' at '.date( 'h:ia T', $activation['time'] ) ?></td>
			           <td><?php echo $activation['version'] ?></td>
			           <td><?php echo ucwords( $activation['os'] ) ?></td>
		          </tr>
		        <?php endforeach; ?>
		      </tbody>
		    </table>
		  <?php } else { ?>
		    <p><?php _e( 'No activations yet', 'jigoshop-software' ) ?></p>
		  <? }
		}

		/**
 			* saves the data inputed into the order boxes
 			*
			* @see order_meta_box()
			* @since 1.0
			* @return void
			*/
		function order_save_data() {
			global $post, $wpdb;
			$data = get_post_meta($post->ID, 'order_data', true);
			foreach ( $this->order_fields as $field ) {
				if ( isset( $_POST[$field['id']] ) ) {
					if ( $field['id'] == 'activation_email' ) {
						update_post_meta( $post->ID, 'activation_email', $_POST['activation_email'] );
					} elseif ( $field['id'] == 'transaction_id' ) {
						update_post_meta( $post->ID, 'transaction_id', $_POST['transaction_id'] );
					} elseif ( $field['id'] == 'old_order_id' ) {
						update_post_meta( $post->ID, 'old_order_id', $_POST['old_order_id'] );
					} else {
						$data[$field['id']] = $wpdb->escape( $_POST[$field['id']] );
					}
				}
			}
			update_post_meta( $post->ID, 'order_data', $data );
			if ( isset( $_POST['resend_email'] ) ) {
				$this->process_email( $post->ID, 'completed_purchase' );
			}
		}

		/**
 			* displays the meta box which allows further actions to be taken
 			*
			* @since 1.7
			* @return void
			*/
		function order_further_actions_meta_box() { ?>
			<ul class="order_actions">
				<li><input type="submit" class="button button-primary" name="resend_email" value="<?php _e( 'Resend Email', 'jigoshop-software' ); ?>" /> &mdash; <?php _e( 'Resend Purchase Email' , 'jigoshop-software' ); ?></li>
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
			wp_register_style( 'jigoshop_software_backend', plugins_url( 'inc/back-end.css', __FILE__ ) );
			wp_enqueue_style( 'jigoshop_software_backend' );
    }


		/**
 			* filters search results on the orders page to allow search by email
			* will only do it when it's a valid email, otherwise will revert back to regular old search
			*
			* @since 1.2
			* @return void
			*/
		function filter_order_search() {
			global $pagenow, $wp_query;
			if ( $pagenow == 'edit.php' && $_GET['post_type'] == 'shop_order' && $wp_query->is_search === true && isset( $_GET['s'] ) && is_email( $_GET['s'] ) ) {
				query_posts(
								array(
									'post_type' => 'shop_order',
									'meta_query' => array(
										array(
											'key' => 'activation_email',
											'value' => $_GET['s'],
										),
									),
								)
				);
				add_filter( 'get_search_query', array( $this, 'get_search_query_when_order' ) );
			}
		}

		/**
 			* filters the "search results" subtitle on the orders page to show the e-mail address
			*
			* @since 1.2
			* @return string the current searched GET string
			*/
		function get_search_query_when_order() {
			return $_GET['s'];
		}

		/**
 			* registers the stats page & import page
			* @since 1.0
			* @return void
			*/
		function admin_menu() {
			add_submenu_page( 'jigoshop', __( 'Stats', 'jigoshop-software' ),  __( 'Stats', 'jigoshop-software' ) , 'manage_options', 'jgs_stats', array( $this, 'software_stats' ) );
			add_submenu_page( 'jigoshop', __( 'Import', 'jigoshop-software' ),  __( 'Import', 'jigoshop-software' ) , 'manage_options', 'jgs_import', array( $this, 'import_page' ) );
		}

		/**
 			* generate admin page with stats
 			*
			* @since 1.0
			* @return void
			*/
		function software_stats() {
			$options = $this->software_stats_options();
			if ( isset( $_POST['update_jgs_stats_options'] ) && wp_verify_nonce( $_POST['update_jgs_stats_options'], 'update_jgs_stats_options' ) ) {
				foreach ( array( 'from_date', 'to_date' ) as $key ) {
						$stamp = strtotime( esc_attr( $_POST[$key] ) );
						$options[$key] = $stamp;
					}
				update_option( 'jigoshop_software_stats_options', $options );
			}
			$options = $this->software_stats_options();
			$str_from_date = date( 'M d Y', $options['from_date'] );
			$str_to_date = date( 'M d Y', $options['to_date'] );
			$date_str = 'from ' . $str_from_date . ' to ' . $str_to_date;

			?>
			<div class="wrap jigoshop">
				<div class="icon32 jigoshop_icon" id="icon-jigoshop"><br/></div>
		    <h2><?php _e( 'Software Sales & Activations', 'jigoshop-software' ) ?></h2>

				<div class="metabox-holder" style="margin-top:25px">
					<div class="postbox-container" style="width:25%;">

						<div class="postbox">
							<h3><?php _e( 'Choose dates to show for stats', 'jigoshop-software' ) ?></h3>
							<div class="inside">
								<form method="post">
									<p>
										<label for="from_date"><?php _e( 'Start date', 'jigoshop-software' ); ?>:
											<input type="date" id="from_date" name="from_date" value="<?php echo date( 'Y-m-d', $options['from_date'] ) ?>">
										</label>
							 		</p>
									<p>
										<label for="to_date"><?php _e( 'End date', 'jigoshop-software' ); ?>:
											<input type="date" id="to_date" name="to_date" value="<?php echo date( 'Y-m-d', $options['to_date'] ) ?>">
										</label>
							 		</p>
									<?php wp_nonce_field( 'update_jgs_stats_options', 'update_jgs_stats_options' ); ?>
									<p><input type="submit" class="button-primary" name="submit" value="Submit"></p>
								</form>
							</div>
						</div>

					</div>

					<div class="postbox-container" style="width:65%; margin-left:25px">

						<div class="postbox">
							<h3><?php _e( 'Software Sales & Activations', 'jigoshop-software' ) ?> <?php echo $date_str ?></h3>
							<div class="inside">
								<div id="placeholder" style="width:100%; height:300px; position:relative; margin: 50px 0; max-width: 1000px"></div>
								<script type="text/javascript">
									/* <![CDATA[ */

									jQuery(function(){

									    <?php
											$args = array(
											    'posts_per_page'  => -1,
											    'orderby'         => 'post_date',
											    'order'           => 'DESC',
											    'post_type'       => 'shop_order',
											    'post_status'     => 'publish',
											    'suppress_filters' => false,
													'tax_query' => array(
														array(
															'taxonomy' => 'shop_order_status',
															'field' => 'slug',
															'terms' => 'completed',
														),
													),
											);
											$orders = get_posts( $args );
											$_activations = get_option( 'jigoshop_software_global_activations' );

											$order_counts = array();
											$order_amounts = array();
											$activations = array();

											// date ranges to use
											$options = $this->software_stats_options();
											$offset = get_option( 'gmt_offset' ) * 60 * 60; // put this in hours
							    		$first_day = $options['from_date'] + $offset;
							    		$last_day = $options['to_date'] + $offset;
											$up_to = floor( ( $last_day - $first_day ) / ( 60 * 60 * 24 ) );

											$count = 0;

											while ( $count < $up_to ) :

												$time = strtotime( date( 'Ymd', strtotime( '+ ' . $count . ' DAY', $first_day ) ) ) . '000';
												$order_counts[$time] = 0;
												$order_amounts[$time] = 0;
												$activations[$time] = 0;

												$count++;
											endwhile;

											if ($orders) :
												foreach ($orders as $order) :

													$order_data = &new jigoshop_order( $order->ID );

													if ($first_day < strtotime( $order->post_date ) && strtotime( $order->post_date ) < $last_day) :

														$time = strtotime( date( 'Ymd', strtotime( $order->post_date ) ) ) . '000';

														if ( isset( $order_counts[$time] ) ) :
															$order_counts[$time]++;
														else :
															$order_counts[$time] = 1;
														endif;

														if ( isset( $order_amounts[$time] ) ) :
															$order_amounts[$time] = $order_amounts[$time] + $order_data->items[0]['cost'];
														else :
															$order_amounts[$time] = (float) $order_data->items[0]['cost'];
														endif;

													endif;

												endforeach;
											endif;

											remove_filter( 'posts_where', 'orders_this_month' );

											foreach ($_activations as $activation) :

												$time = strtotime( date( 'Ymd', $activation['time'] ) ) . '000';
												if ( $first_day < $activation['time'] && $activation['time'] < $last_day ) :
													if ( isset( $activations[$time] ) ) $activations[$time]++;
													else $activations[$time] = 1;
												endif;

											endforeach;



										?>

									    var d = [
									    	<?php
									    		$values = array();
									    		foreach ( $order_counts as $key => $value ) $values[] = "[$key, $value]";
									    		echo implode( ',', $values );
									    	?>
										];

								    	for (var i = 0; i < d.length; ++i) d[i][0] += 60 * 60 * 1000;

								    	var d2 = [
									    	<?php
									    		$values = array();
									    		foreach ( $order_amounts as $key => $value ) $values[] = "[$key, $value]";
									    		echo implode( ',', $values );
									    	?>
								    	];

											var d3 = [
									    	<?php
									    		$values = array();
									    		foreach ( $activations as $key => $value ) $values[] = "[$key, $value]";
									    		echo implode( ',', $values );
									    	?>
											];
									    for (var i = 0; i < d2.length; ++i) d2[i][0] += 60 * 60 * 1000;

										var plot = jQuery.plot(jQuery("#placeholder"), [ { label: "<?php _e( 'Number of sales', 'jigoshop-software' ) ?>", data: d }, { label: "<?php _e( 'Sales amount', 'jigoshop-software' ) ?>", data: d2, yaxis: 2 },  { label: "<?php _e( 'Number of activations', 'jigoshop-software' ) ?>", data: d3 } ], {
											series: {
												lines: { show: true },
												points: { show: true }
											},
											grid: {
												show: true,
												aboveData: false,
												color: '#ccc',
												backgroundColor: '#fff',
												borderWidth: 2,
												borderColor: '#ccc',
												clickable: false,
												hoverable: true,
											},
											legend : {
												position: "nw",
											},
											xaxis: {
												mode: "time",
												timeformat: "%d %b",
												tickLength: 1,
												minTickSize: [1, "day"]
											},
														yaxes: [ { min: 0, tickSize: 1, tickDecimals: 0 }, { position: "right", min: 0, tickDecimals: 2 } ],
						               		colors: ["#21759B", "#ed8432"]
						             	});

										function showTooltip(x, y, contents) {
									        jQuery('<div id="tooltip">' + contents + '</div>').css( {
									            position: 'absolute',
									            display: 'none',
									            top: y + 5,
									            left: x + 5,
									            border: '1px solid #fdd',
									            padding: '2px',
									            'background-color': '#fee',
									            opacity: 0.80
									        }).appendTo("body").fadeIn(200);
									    }

									    var previousPoint = null;
									    jQuery("#placeholder").bind("plothover", function (event, pos, item) {
								            if (item) {
								                if (previousPoint != item.dataIndex) {
								                    previousPoint = item.dataIndex;

								                    jQuery("#tooltip").remove();

								                    if (item.series.label=="Number of sales" || item.series.label=="Number of activations") {

								                    	var y = item.datapoint[1];
								                    	showTooltip(item.pageX, item.pageY, item.series.label + " - " + y);

								                    } else {

								                    	var y = item.datapoint[1].toFixed(2);
								                    	showTooltip(item.pageX, item.pageY, item.series.label + " - <?php echo get_jigoshop_currency_symbol(); ?>" + y);

								                    }

								                }
								            }
								            else {
								                jQuery("#tooltip").remove();
								                previousPoint = null;
								            }
									    });

									});

									/* ]]> */
								</script>
							</div>
						</div>

						<div class="postbox">
							<h3>Activations <?php echo $date_str?></h3>
							<div class="inside">
								<?php
								$activations = get_option( 'jigoshop_software_global_activations' );
								$activations = ( is_array( $activations ) ) ? array_reverse( $activations ) : $activations;
								?>
						    <table id="activations-table" class="widefat" style="width: 100%; max-width: 1000px">
						      <thead>
						        <tr>
						          <th><?php _e( 'Product ID', 'jigoshop-software' ) ?></th>
						          <th><?php _e( 'Instance', 'jigoshop-software' ) ?></th>
						          <th><?php _e( 'Status', 'jigoshop-software' ) ?></th>
						          <th><?php _e( 'Date & Time', 'jigoshop-software' ) ?></th>
						          <th><?php _e( 'Version', 'jigoshop-software' ) ?></th>
						          <th><?php _e( 'Operating System', 'jigoshop-software' ) ?></th>
						        </tr>
						      </thead>
						      <tfoot>
						        <tr>
						          <th><?php _e( 'Product ID', 'jigoshop-software' ) ?></th>
						          <th><?php _e( 'Instance', 'jigoshop-software' ) ?></th>
						          <th><?php _e( 'Status', 'jigoshop-software' ) ?></th>
						          <th><?php _e( 'Date & Time', 'jigoshop-software' ) ?></th>
						          <th><?php _e( 'Version', 'jigoshop-software' ) ?></th>
						          <th><?php _e( 'Operating System', 'jigoshop-software' ) ?></th>
						        </tr>
						      </tfoot>
						      <tbody>
										<?php if ( is_array( $activations ) && count( $activations ) > 0 ) : ?>
							        <?php $i = 0; foreach ( $activations as $activation ) : $i++ ?>
												<?php if ( isset( $activation['active'] ) && $first_day < $activation['time'] && $activation['time'] < $last_day ) : ?>
									         <tr<?php if ( $i / 2 == 1 ) echo ' class="alternate"' ?>>
									           <td><?php echo $activation['product_id'] ?></td>
									           <td><?php echo $activation['instance'] ?></td>
									           <td><?php echo ( $activation['active'] ) ? 'Activated' : 'Deactivated' ?></td>
									           <td><?php echo date( 'D j M Y', $activation['time'] ) . ' at ' . date( 'h:ia T', $activation['time'] ) ?></td>
									           <td><?php echo $activation['version'] ?></td>
									           <td><?php echo ucwords( $activation['os'] ) ?></td>
								          </tr>
												<?php endif; ?>
							        <?php endforeach; ?>
										<?php else : ?>
											<tr><td colspan="6"> No activations yet</td></tr>
										<?php endif; ?>
						      </tbody>
						    </table>
							</div>
						</div>

					</div>
				</div>

			</div>
			<?php
		}

		/**
 			* save dashboard widget options
 			*
			* @since 1.0
			* @return array the dashboard options
			*/
		function software_stats_options() {
			$defaults = array( 'from_date' => time() - ( 30 * 24 * 60 * 60 ), 'to_date' => time() );
			if ( ( !$options = get_option( 'jigoshop_software_stats_options' ) ) || !is_array( $options ) )
				$options = array();
			return array_merge( $defaults, $options );
		}

/* =======================================
		filter add to cart & other jigoshop internal functions
==========================================*/

		/**
			* adds css to the front-end
			*
			* @since 1.0
			* @return void
			*/
		function print_styles() {
			wp_register_style( 'jigoshop_software', plugins_url( 'inc/front-end.css', __FILE__ ) );
			wp_enqueue_style( 'jigoshop_software' );
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
			* very hacky way to filter out the price sent to paypal when it's an upgrade, but the only way to do it w/out changing core jigoshop
			*
			* @see $this->init_output_buffer()
			* @todo find a better a way to do this
			* @since 1.0
			* @param string $buffer what's being sent to paypal
			* @return string $buffer what's being sent to paypal
			*/
		function jigoshop_software_filter_price_paypal( $buffer ) {
			if ( isset($_GET['order']) ) {
				$order_id = $_GET['order'];
				$data = get_post_meta( $order_id, 'order_data', true );
				$original_price = $data['original_price'];
				$correct_price = $data['order_total'];
				if ( $original_price ) {
					$buffer = str_replace( '"amount_1" value="' . $original_price . '"', '"amount_1" value="' . $correct_price . '"', $buffer );
				}
			}
			return $buffer;
		}

/* =======================================
		helper functions
==========================================*/

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
 			* checks if a key is a valid upgrade key for a particular product
			*
			* @since 1.0
			* @param string $key the key to validate
			* @param int $item_id the product to validate for
			* @return bool valid key or not
			*/
		function is_valid_upgrade_key( $key = null, $item_id = null ) {
			if ( $key && $item_id ) {
				$product_data = get_post_meta( $item_id, 'product_data', true );
				$_keys = (array) $product_data['up_license_keys'];
				if ( in_array( $key, $_keys ) ) return true;
				else return false;
			}
			return false;
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

			$no_js = ( isset( $_POST['no_js'] ) && $_POST['no_js'] == 'true' ) ? true : false;

			/**
			 * @todo use add_query_arg
			 */
			if ($no_js) wp_safe_redirect( jigoshop_cart::get_checkout_url() . '?no-js=true' );

			$item_id = esc_attr( $_POST['item_id'] );
			$qty = 1; // always 1 because it's a buy now situation not a cart situation
			$upgrade = false; // default

			// nonce verification
			if ( isset( $_POST['jgs_checkout_nonce'] ) && !wp_verify_nonce( $_POST['jgs_checkout_nonce'], 'jgs_checkout' ) ) $messages['nonce'] = __( 'An error has occurred, please try again', 'jigoshop-software' );

			if ( isset($_POST['up_key']) ) {
				$key = esc_attr( $_POST['up_key'] );
			} else {
				$key = null;
			}

			// email validation
			$email = strtolower( esc_attr( $_POST['jgs_email'] ) );
			if ( !$email || $email == '') $messages['email'] = __( 'Please enter your email', 'jigoshop-software');
			elseif ( !is_email( $email ) ) $messages['email'] = __( 'Please enter a valid email address', 'jigoshop-software');

			// key validation
			if ( $key && $key != '' && !$this->is_valid_upgrade_key( $key, $item_id ) ) $messages['key'] = __( 'The key you have entered is not valid, please try again or contact us if you need additional help', 'jigoshop-software' );

			// if there is no message, then validation passed
			if ( !$messages ) {
				if ( $this->is_valid_upgrade_key( $key, $item_id ) ) $upgrade = true;

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
				$price = ( $sale_price && $sale_price != '' ) ? $sale_price : $regular_price;

				if ( $upgrade ) {
					$order['is_upgrade'] = 'on';
					$order['upgrade_name'] = $product['upgradable_product'];
					$order['upgrade_price'] = $product['up_price'];
					$order['original_price'] = $price;
					$price = $order['upgrade_price'];

					// move the upgraded key to the used keys
					unset( $product['up_license_keys'][array_search( $key, $product['up_license_keys'] )] );
					$product['used_license_keys'][] = $key;
					update_post_meta( $item_id, 'product_data', $product );
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
				$order['license_key'] = ( !empty( $product['license_key_prefix'] ) ) ? $product['license_key_prefix'].$this->generate_license_key() : $this->generate_license_key();
				$order['activations_possible'] = $product['activations'];
				$order['remaining_activations'] = $product['activations'];
				$order['secret_product_key'] = $product['secret_product_key'];
				$order['paypal_name'] = $product['paypal_name'];
				$order['productid'] = get_post_meta( $item_id, 'soft_product_id', true );

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

				$_order = &new jigoshop_order( $order_id );

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
						$data['purchases'][$i]['license_key'] = $order_data['license_key'];
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
		function post_paypal_payment( $order_id ) {
			if ( isset( $_GET['tx'] ) ) {
				update_post_meta( $order_id, 'transaction_id', $_GET['tx'], true );
			}
		}

		/**
 			* sends out the completed order email
			*
			* @since 1.6
			* @param int $order_id the order id to process
			* @return void
			*/
		function completed_order( $order_id ) {
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
					$order = &new jigoshop_order( $order_id );

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
						$_order = &new jigoshop_order( $order_id );

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
	add_action( 'init', 'jigoshop_software_init' );
	function jigoshop_software_init() {
		global $jigoshopsoftware;
		$jigoshopsoftware = new Jigoshop_Software();
		ob_start( array( $jigoshopsoftware, 'jigoshop_software_filter_price_paypal' ) );
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
	);
	$github_updater = new wp_github_updater( $config );
}