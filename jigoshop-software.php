<?php
/*
Plugin Name: JigoShop - Software Add-On
Plugin URI: https://github.com/jkudish/JigoShop-Software-Add-on/
Description: Extends JigoShop to a full-blown software shop, including license activation, license retrieval, activation e-mails and more
Version: 1.9
Author: Joachim Kudish
Author URI: http://jkudish.com
License: GPL v3
*/

/**
	* @version 1.9
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

if (!class_exists('jigoshop_software')) {

	class jigoshop_software {

		// define the product metadata fields used by this plugin
		static $product_fields = array(
			array('id' => 'is_software', 'label' => 'This product is Software', 'title' => 'This product is Software', 'placeholder' => '', 'type' => 'checkbox'),
			array('id' => 'soft_product_id', 'label' => 'Product ID to use for API:', 'title' => 'Product ID to use for API', 'placeholder' => 'ex: PRODUCT1', 'type' => 'text'),
			array('id' => 'secret_product_key', 'label' => 'Secret Product Key to use for API:', 'title' => 'Secret Product Key to use  for API', 'placeholder' => 'any random string', 'type' => 'text'),
			array('id' => 'version', 'label' => 'Version Number:', 'title' => 'Version Number', 'placeholder' => 'ex: 1.0', 'type' => 'text'),
			array('id' => 'activations', 'label' => 'Amount of activations possible:', 'title' => 'Amount of activations possible', 'placeholder' => 'ex: 5', 'type' => 'text'),
			array('id' => 'trial', 'label' => 'Trial Period (amount of days or hours):', 'title' => 'Trial Period (amount of days or hours)', 'placeholder' => 'ex: 15', 'type' => 'text'),
			array('id' => 'trial_unit', 'label' => 'Trial Units:', 'title' => 'Trial Units', 'type' => 'select', 'values' => array('days' => 'Days', 'hours' => 'Hours')),
			array('id' => 'upgradable_product', 'label' => 'Upgradable Product Name:', 'title' => 'Upgradable Product Name', 'placeholder' => '', 'type' => 'text'),
			array('id' => 'up_license_keys', 'label' => 'Upgradable Product Keys:', 'title' => 'Upgradable Product Keys', 'placeholder' => 'Comma separated list', 'type' => 'textarea'),
			array('id' => 'used_license_keys', 'label' => 'Used Upgrade Keys:', 'title' => 'Used Upgrade Keys', 'placeholder' => 'Comma separated list', 'type' => 'textarea'),
			array('id' => 'up_price', 'label' => 'Upgrade Price ($):', 'title' => 'Upgrade Price ($)', 'placeholder' => 'ex: 1.00', 'type' => 'text'),
			array('id' => 'paypal_name', 'label' => 'Paypal Name to show on transaction receipts:', 'title' => 'Paypal Name to show on transaction receipts', 'placeholder' => 'ex: Google Inc.', 'type' => 'text'),
		);

		// define the order metadata fields used by this plugin
		static $order_fields = array(
			array('id' => 'activation_email', 'label' => 'Activation Email:', 'title' => 'Activation Email', 'placeholder' => '', 'type' => 'text'),
			array('id' => 'paypal_name', 'label' => 'Paypal Name to show on transaction receipts:', 'title' => 'Paypal Name to show on transaction receipts', 'placeholder' => 'ex: Google Inc.', 'type' => 'text'),
			array('id' => 'transaction_id', 'label' => 'Transaction ID:', 'title' => 'Transaction ID', 'placeholder' => '', 'type' => 'text'),
			array('id' => 'license_key', 'label' => 'License Key:', 'title' => 'License Key', 'placeholder' => '', 'type' => 'text'),
			array('id' => 'productid', 'label' => 'Product ID:', 'title' => 'Product ID', 'placeholder' => '', 'type' => 'text'),
			array('id' => 'activations_possible', 'label' => 'Max Activations Allowed:', 'title' => 'Max Activations Allowed', 'placeholder' => '', 'type' => 'text'),
			array('id' => 'remaining_activations', 'label' => 'Remaining Activations:', 'title' => 'Remaining Activations', 'placeholder' => '', 'type' => 'text'),
			array('id' => 'secret_product_key', 'label' => 'Secret Product Key to use for API:', 'title' => 'Secret Product Key to use for API', 'placeholder' => 'any random string', 'type' => 'text'),
			array('id' => 'version', 'label' => 'Version:', 'title' => 'Version', 'placeholder' => '', 'type' => 'text'),
			array('id' => 'old_order_id', 'label' => 'Legacy order ID:', 'title' => 'Legacy order ID', 'placeholder' => '', 'type' => 'text'),
			array('id' => 'is_upgrade', 'label' => 'This is an upgrade if checked', 'title' => 'This is an upgrade if checked', 'placeholder' => '', 'type' => 'checkbox'),
			array('id' => 'upgrade_name', 'label' => 'Upgraded from:', 'title' => 'License Key', 'placeholder' => '', 'type' => 'text'),
			array('id' => 'upgrade_price', 'label' => 'Upgrade price ($):', 'title' => 'License Key', 'placeholder' => '', 'type' => 'text'),
			array('id' => 'original_price', 'label' => 'Original price ($):', 'title' => 'License Key', 'placeholder' => '', 'type' => 'text'),
		);

	/**
		* __construct()
		* plugin activation, hooks & filters, etc..
		* @since 1.0
		*/
		function __construct() {

			$this->define_constants();

			// set the right time zone from WP options
			@date_default_timezone_set(get_option('timezone_string'));

			/**
			 * hooks
			 */

			// backend stuff
			add_action('product_write_panel_tabs', array(&$this, 'product_write_panel_tab'));
			add_action('product_write_panels', array(&$this, 'product_write_panel'));
			add_filter('process_product_meta', array(&$this, 'product_save_data'));
			add_action('add_meta_boxes', array(&$this, 'add_meta_boxes'));
			add_action('jigoshop_process_shop_order_meta', array(&$this, 'order_save_data'), 1, 2);
			add_action('admin_print_styles', array(&$this, 'admin_print_styles'));
			add_action('admin_menu', array(&$this, 'admin_menu'));
			add_action('wp_ajax_nopriv_jgs_import', array(&$this, 'import_ajax'));
			add_action('wp_ajax_jgs_import', array(&$this, 'import_ajax'));
			add_action('wp_ajax_nopriv_jgs_do_import', array(&$this, 'import'));
			add_action('wp_ajax_jgs_do_import', array(&$this, 'import'));

			add_action('admin_head', array(&$this, 'filter_order_search'));


			// frontend stuff
			remove_action('simple_add_to_cart', 'jigoshop_simple_add_to_cart');
			remove_action('virtual_add_to_cart', 'jigoshop_simple_add_to_cart');
			remove_action('downloadable_add_to_cart', 'jigoshop_downloadable_add_to_cart');
			add_action( 'grouped_add_to_cart', 'jigoshop_grouped_add_to_cart' );
			remove_action('jigoshop_after_shop_loop_item', 'jigoshop_template_loop_add_to_cart', 10, 2);
			add_action('simple_add_to_cart', array(&$this, 'add_to_cart'));
			add_action('virtual_add_to_cart', array(&$this, 'add_to_cart'));
			add_action('downloadable_add_to_cart', array(&$this, 'add_to_cart'));
			add_action('grouped_add_to_cart', array(&$this, 'add_to_cart'));
			add_action('jigoshop_after_shop_loop_item', array(&$this, 'loop_add_to_cart'), 10, 2);
			add_filter('init', array(&$this, 'init_output_buffer'));

			add_action( 'wp_print_styles', array(&$this, 'print_styles'));
			add_action( 'wp_head', array(&$this, 'redirect_away_from_cart'));
			add_action( 'wp_ajax_nopriv_jgs_checkout', array(&$this, 'ajax_jgs_checkout'));
			add_action( 'wp_ajax_jgs_checkout', array(&$this, 'ajax_jgs_checkout'));
			add_action( 'wp_ajax_nopriv_jgs_lost_license', array(&$this, 'ajax_jgs_lost_license'));
			add_action( 'wp_ajax_jgs_lost_license', array(&$this, 'ajax_jgs_lost_license'));

			// payment stuff
			add_action('thankyou_paypal', array(&$this, 'post_paypal_payment'));

			// email stuff
			remove_action('order_status_pending_to_processing', 'jigoshop_new_order_notification');
			remove_action('order_status_pending_to_completed', 'jigoshop_new_order_notification');
			remove_action('order_status_pending_to_on-hold', 'jigoshop_new_order_notification');
			remove_action('order_status_completed', 'jigoshop_completed_order_customer_notification');
			remove_action('order_status_pending_to_processing', 'jigoshop_processing_order_customer_notification');
			remove_action('order_status_pending_to_on-hold', 'jigoshop_processing_order_customer_notification');
			remove_action('order_status_completed', 'jigoshop_completed_order_customer_notification');
			add_action('order_status_completed', array(&$this, 'completed_order'));

			// filters
			add_filter('add_to_cart_redirect', array(&$this, 'add_to_cart_redirect'));
			add_filter('page_template', array(&$this, 'locate_api_template'), 10, 1);

		}

		/**
 			* define_constants()
 			* defines the constants we need for the plugin
			* @since 1.3
			*/
		function define_constants() {
			if (!defined('JIGOSHOP_SOFTWARE_PATH')) define('JIGOSHOP_SOFTWARE_PATH', dirname(__FILE__));
			if (!defined('JIGOSHOP_SOFTWARE_SLUG')) define('JIGOSHOP_SOFTWARE_SLUG', plugin_basename(__FILE__));
			if (!defined('JIGOSHOP_SOFTWARE_PROPER_NAME')) define('JIGOSHOP_SOFTWARE_PROPER_NAME', 'jigoshop-software');
			if (!defined('JIGOSHOP_SOFTWARE_GITHUB_URL')) define('JIGOSHOP_SOFTWARE_GITHUB_URL', 'https://github.com/jkudish/JigoShop-Software-Add-on');
			if (!defined('JIGOSHOP_SOFTWARE_GITHUB_ZIP_URL')) define('JIGOSHOP_SOFTWARE_GITHUB_ZIP_URL', 'https://github.com/jkudish/JigoShop-Software-Add-on/zipball/master');
			if (!defined('JIGOSHOP_SOFTWARE_GITHUB_API_URL')) define('JIGOSHOP_SOFTWARE_GITHUB_API_URL', 'https://api.github.com/repos/jkudish/JigoShop-Software-Add-on');
			if (!defined('JIGOSHOP_SOFTWARE_GITHUB_RAW_URL')) define('JIGOSHOP_SOFTWARE_GITHUB_RAW_URL', 'https://raw.github.com/jkudish/JigoShop-Software-Add-on/master');
			if (!defined('JIGOSHOP_SOFTWARE_REQUIRES_WP')) define('JIGOSHOP_SOFTWARE_REQUIRES_WP', '3.0');
			if (!defined('JIGOSHOP_SOFTWARE_TESTED_WP')) define('JIGOSHOP_SOFTWARE_TESTED_WP', '3.3');
		}

		/**
 			* activation()
 			* runs various functions when the plugin first activates
			* @see register_activation_hook()
			* @link http://codex.wordpress.org/Function_Reference/register_activation_hook
			* @since 1.0
			*/
		function activation() {

			// checks if the jigoshop plugin is running and disables this plugin if it's not (and displays a message)
			if (!is_plugin_active('jigoshop/jigoshop.php')) {
				deactivate_plugins(plugin_basename(__FILE__));
				wp_die(__('The JigoShop Software Add-On requires <a href="http://jigoshop.com" target="_blank">JigoShop</a> to be activated in order to work. Please activate <a href="http://jigoshop.com" target="_blank">JigoShop</a> first. <a href="'.admin_url('plugins.php').'"> <br> &laquo; Go Back</a>', 'jigoshop'));
			}

			// creates the lost license page with the right shortcode in it
			$lost_license_page_id = get_option('jigoshop_lost_license_page_id');
			if (!$lost_license_page_id || $lost_license_page_id == '') {
				$lost_license_page = array(
					'post_title' => 'Lost License',
					'post_content' => '[jigoshop_software_lost_license]',
					'post_status' => 'publish',
					'post_type' => 'page',
				);
				$lost_license_page_id = wp_insert_post($lost_license_page);
				update_option('jigoshop_lost_license_page_id', $lost_license_page_id);
			}

			// creates the API page
			$jigoshop_api_page_id = get_option('jigoshop_api_page_id');
			if (!$jigoshop_api_page_id || $jigoshop_api_page_id == '') {
				$api_page = array(
					'post_title' => 'API',
					'post_content' => '',
					'post_status' => 'publish',
					'post_type' => 'page',
				);
				$jigoshop_api_page_id = wp_insert_post($api_page);
				update_option('jigoshop_api_page_id', $jigoshop_api_page_id);
			}

		}

/* =======================================
		meta boxes
==========================================*/

		/**
 			* add_meta_boxes()
 			* registers meta boxes
			* @since 1.0
			*/
		function add_meta_boxes() {
			add_meta_box('jigoshop-software-order-data', __('Software Purchase Details', 'jigoshop'), array('jigoshop_software', 'order_meta_box'), 'shop_order', 'normal', 'high' );
			add_meta_box('jigoshop-software-activation-data', __('Activations', 'jigoshop'), array('jigoshop_software', 'activation_meta_box'), 'shop_order', 'normal', 'high' );
			add_meta_box('jigoshop-software-further-actions', __('Further Actions', 'jigoshop'), array('jigoshop_software', 'order_further_actions_meta_box'), 'shop_order', 'side', 'low' );
		}

		/**
 			* product_write_panel_tab()
 			* adds a new tab to the product interface
			* @since 1.0
			*/
		function product_write_panel_tab() {
		?>
			<li><a href="#software_data"><?php _e('Software', 'jigoshop'); ?></a></li>
		<?php
		}

		/**
 			* product_write_panel()
 			* adds the panel to the product interface
			* @since 1.0
			*/
		function product_write_panel() {
			global $post;
			$data = maybe_unserialize( get_post_meta($post->ID, 'product_data', true) );
		?>
			<div id="software_data" class="panel jigoshop_options_panel">
			<?php
				foreach (self::$product_fields as $field) :
					if ($field['id'] == 'soft_product_id') $value = get_post_meta($post->ID, 'soft_product_id', true);
					else @$value = ($field['id'] == 'up_license_keys' || $field['id'] == 'used_license_keys') ? $this->un_array_ify_keys($data[$field['id']]) : $data[$field['id']];
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
 			* product_save_data()
 			* saves the data inputed into the product boxes
			* @see product_write_panel()
			* @since 1.0
			*/
		function product_save_data($data) {
			global $post;
			foreach (self::$product_fields as $field) {
				if ($field['id'] == 'up_license_keys' || $field['id'] == 'used_license_keys') $data[$field['id']] = $this->array_ify_keys($_POST[$field['id']]);
				elseif ($field['id'] == 'soft_product_id') update_post_meta($post->ID, 'soft_product_id', $_POST[$field['id']]);
				else $data[$field['id']] = esc_attr( $_POST[$field['id']] );
			}
			return $data;
		}

		/**
 			* order_meta_box()
 			* adds meta fields to the order screens
			* @since 1.0
			*/
		function order_meta_box() {
			global $post;
			$data = (array) maybe_unserialize( get_post_meta($post->ID, 'order_data', true) );
		?>
			<div class="panel-wrap jigoshop">
				<div id="order_software_data" class="panel jigoshop_options_panel">
					<?php
						foreach (self::$order_fields as $field) :
							@$value = ($field['id'] == 'activation_email') ? get_post_meta($post->ID, 'activation_email', true) : $data[$field['id']];
							@$value = ($field['id'] == 'transaction_id') ? get_post_meta($post->ID, 'transaction_id', true) : $value;
							@$value = ($field['id'] == 'old_order_id') ? get_post_meta($post->ID, 'old_order_id', true) : $value;
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
 			* activation_meta_box()
 			* adds activations meta box
			* @since 1.0
			*/
		function activation_meta_box($post) {
		  $activations = get_post_meta($post->ID, 'activations', true);
		  if (is_array($activations) && count($activations) > 0) { ?>
		    <table id="activations-table" class="widefat">
		      <thead>
		        <tr>
		          <th>Instance</th>
		          <th>Status</th>
		          <th>Date & Time</th>
		          <th>Version</th>
		          <th>Operating System</th>
		        </tr>
		      </thead>
		      <tfoot>
		        <tr>
		          <th>Instance</th>
		          <th>Status</th>
		          <th>Date & Time</th>
		          <th>Version</th>
		          <th>Operating System</th>
		        </tr>
		      </tfoot>
		      <tbody>
		        <?php $i = 0; foreach ($activations as $activation) : $i++ ?>
			         <tr<?php if ($i/2 == 1) echo ' class="alternate"' ?>>
			           <td><?php echo $activation['instance'] ?></td>
			           <td><?php echo ($activation['active']) ? 'Activated' : 'Deactivated' ?></td>
			           <td><?php echo date('D j M Y', $activation['time']).' at '.date('h:ia T', $activation['time']) ?></td>
			           <td><?php echo $activation['version'] ?></td>
			           <td><?php echo ucwords($activation['os']) ?></td>
		          </tr>
		        <?php endforeach; ?>
		      </tbody>
		    </table>
		  <?php } else { ?>
		    <p>No activations yet</p>
		  <? }
		}

		/**
 			* order_save_data()
 			* saves the data inputed into the order boxes
			* @see order_meta_box()
			* @since 1.0
			*/
		function order_save_data() {
			global $post;
			$data = get_post_meta($post->ID, 'order_data', true);
			foreach (self::$order_fields as $field) {
				if (isset($_POST[$field['id']])) {
					if ($field['id'] == 'activation_email') update_post_meta($post->ID, 'activation_email', $_POST[$field['id']]);
					else $data[$field['id']] = esc_attr( $_POST[$field['id']] );
				}
			}
			update_post_meta($post->ID, 'order_data', $data);
			if (isset($_POST['resend_email'])) {
				$this->process_email($post->ID, 'completed_purchase');
			}
		}

		/**
 			* order_further_actions_meta_box()
 			* displays the meta box which allows further actions to be taken
			* @since 1.7
			*/
		function order_further_actions_meta_box() { ?>
			<ul class="order_actions">
				<li><input type="submit" class="button button-primary" name="resend_email" value="<?php _e('Resend Email', 'jigoshop'); ?>" /> <?php _e('- Resend Purchase Email .', 'jigoshop'); ?></li>
			</ul>
			<?php
		}

		/**
 			* admin_print_styles()
 			* adds css to the back-end
			* @since 1.0
			*/
    function admin_print_styles() {
			wp_register_style('jigoshop_software_backend', plugins_url( 'inc/back-end.css', __FILE__ ));
			wp_enqueue_style('jigoshop_software_backend');
    }


		/**
 			* filter_order_search()
 			* filters search results on the orders page to allow search by email
			* will only do it when it's a valid email, otherwise will revert back to regular old search
			* @since 1.2
			*/
		function filter_order_search() {
			global $pagenow, $wp_query;
			if ($pagenow == 'edit.php' && $_GET['post_type'] == 'shop_order' && $wp_query->is_search === true && isset($_GET['s']) && is_email($_GET['s'])) {
				query_posts(array(
					'post_type' => 'shop_order',
					'meta_query' => array(
						array(
							'key' => 'activation_email',
							'value' => $_GET['s'],
						)
					)
				));
				add_filter('get_search_query', array(&$this, 'get_search_query_when_order'));
			}
		}

		/**
 			* get_search_query_when_order()
 			* filters the "search results" subtitle on the orders page to show the e-mail address
			* @since 1.2
			*/
		function get_search_query_when_order() {
			return $_GET['s'];
		}

		/**
 			* admin_menu()
 			* registers the stats page & import page
			* @since 1.0
			*/
		function admin_menu() {
			add_submenu_page('jigoshop', __('Stats', 'jigoshop'),  __('Stats', 'jigoshop') , 'manage_options', 'jgs_stats', array(&$this, 'software_stats'));
			add_submenu_page('jigoshop', __('Import', 'jigoshop'),  __('Import', 'jigoshop') , 'manage_options', 'jgs_import', array(&$this, 'import_page'));
		}

		/**
 			* software_stats()
 			* admin page with stats
			* @since 1.0
			*/
		function software_stats() {
			$options = $this->software_stats_options();
			if (isset($_POST) && @wp_verify_nonce($_POST['update_jgs_stats_options'], 'update_jgs_stats_options')) {
				foreach ( array( 'from_date', 'to_date' ) as $key ) {
						$stamp = strtotime(esc_attr($_POST[$key]));
						$options[$key] = $stamp;
					}
				update_option( 'jigoshop_software_stats_options', $options );
			}
			$options = $this->software_stats_options();
			$str_from_date = date('M d Y', $options['from_date']);
			$str_to_date = date('M d Y', $options['to_date']);
			$date_str = 'from '.$str_from_date.' to '.$str_to_date;

			?>
			<div class="wrap jigoshop">
				<div class="icon32 jigoshop_icon" id="icon-jigoshop"><br/></div>
		    <h2><?php _e('Software Sales & Activations','jigoshop') ?></h2>

				<div class="metabox-holder" style="margin-top:25px">
					<div class="postbox-container" style="width:25%;">

						<div class="postbox">
							<h3>Choose dates to show for stats</h3>
							<div class="inside">
								<form method="post">
									<p>
										<label for="from_date"><?php _e('Start date', 'jigoshop' ); ?>:
											<input type="date" id="from_date" name="from_date" value="<?php echo date('Y-m-d', $options['from_date']) ?>">
										</label>
							 		</p>
									<p>
										<label for="to_date"><?php _e('End date', 'jigoshop' ); ?>:
											<input type="date" id="to_date" name="to_date" value="<?php echo date('Y-m-d', $options['to_date']) ?>">
										</label>
							 		</p>
									<? wp_nonce_field('update_jgs_stats_options', 'update_jgs_stats_options'); ?>
									<p><input type="submit" class="button-primary" name="submit" value="Submit"></p>
								</form>
							</div>
						</div>

					</div>

					<div class="postbox-container" style="width:65%; margin-left:25px">

						<div class="postbox">
							<h3>Software Sales & Activations <?php echo $date_str?></h3>
							<div class="inside">
								<div id="placeholder" style="width:100%; height:300px; position:relative; margin: 50px 0; max-width: 1000px"></div>
								<script type="text/javascript">
									/* <![CDATA[ */

									jQuery(function(){

									    <?php
											$args = array(
											    'numberposts'     => -1,
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
														)
													),
											);
											$orders = get_posts( $args );
											$_activations = get_option('jigoshop_software_global_activations');

											$order_counts = array();
											$order_amounts = array();
											$activations = array();

											// date ranges to use
											$options = $this->software_stats_options();
											$offset = get_option('gmt_offset')*60*60; // put this in hours
							    		$first_day = $options['from_date']+$offset;
							    		$last_day = $options['to_date']+$offset;
											$up_to = floor(($last_day - $first_day) / (60*60*24));

											$count = 0;

											while ($count < $up_to) :

												$time = strtotime(date('Ymd', strtotime('+ '.$count.' DAY', $first_day))).'000';
												$order_counts[$time] = 0;
												$order_amounts[$time] = 0;
												$activations[$time] = 0;

												$count++;
											endwhile;

											if ($orders) :
												foreach ($orders as $order) :

													$order_data = &new jigoshop_order($order->ID);

													if ($first_day < strtotime($order->post_date) && strtotime($order->post_date) < $last_day) :

														$time = strtotime(date('Ymd', strtotime($order->post_date))).'000';

														if (isset($order_counts[$time])) :
															$order_counts[$time]++;
														else :
															$order_counts[$time] = 1;
														endif;

														if (isset($order_amounts[$time])) :
															$order_amounts[$time] = $order_amounts[$time] + $order_data->items[0]['cost'];
														else :
															$order_amounts[$time] = (float) $order_data->items[0]['cost'];
														endif;

													endif;

												endforeach;
											endif;

											remove_filter( 'posts_where', 'orders_this_month' );

											foreach ($_activations as $activation) :

												$time = strtotime(date('Ymd', $activation['time'])).'000';
												if ($first_day < $activation['time'] && $activation['time'] < $last_day) :
													if (isset($activations[$time])) $activations[$time]++;
													else $activations[$time] = 1;
												endif;

											endforeach;



										?>

									    var d = [
									    	<?php
									    		$values = array();
									    		foreach ($order_counts as $key => $value) $values[] = "[$key, $value]";
									    		echo implode(',', $values);
									    	?>
										];

								    	for (var i = 0; i < d.length; ++i) d[i][0] += 60 * 60 * 1000;

								    	var d2 = [
									    	<?php
									    		$values = array();
									    		foreach ($order_amounts as $key => $value) $values[] = "[$key, $value]";
									    		echo implode(',', $values);
									    	?>
								    	];

											var d3 = [
									    	<?php
									    		$values = array();
									    		foreach ($activations as $key => $value) $values[] = "[$key, $value]";
									    		echo implode(',', $values);
									    	?>
											];
									    for (var i = 0; i < d2.length; ++i) d2[i][0] += 60 * 60 * 1000;

										var plot = jQuery.plot(jQuery("#placeholder"), [ { label: "Number of sales", data: d }, { label: "Sales amount", data: d2, yaxis: 2 },  { label: "Number of activations", data: d3 } ], {
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
								<?php $activations = array_reverse(get_option('jigoshop_software_global_activations')); ?>
						    <table id="activations-table" class="widefat" style="width: 100%; max-width: 1000px">
						      <thead>
						        <tr>
						          <th>Product ID</th>
						          <th>Instance</th>
						          <th>Status</th>
						          <th>Date & Time</th>
						          <th>Version</th>
						          <th>Operating System</th>
						        </tr>
						      </thead>
						      <tfoot>
						        <tr>
						          <th>Product ID</th>
						          <th>Instance</th>
						          <th>Status</th>
						          <th>Date & Time</th>
						          <th>Version</th>
						          <th>Operating System</th>
						        </tr>
						      </tfoot>
						      <tbody>
										<?php if (is_array($activations) && count($activations) > 0) : ?>
							        <?php $i = 0; foreach ($activations as $activation) : $i++ ?>
												<?php if (isset($activation['active']) && $first_day < $activation['time'] && $activation['time'] < $last_day) : ?>
									         <tr<?php if ($i/2 == 1) echo ' class="alternate"' ?>>
									           <td><?php echo $activation['product_id'] ?></td>
									           <td><?php echo $activation['instance'] ?></td>
									           <td><?php echo ($activation['active']) ? 'Activated' : 'Deactivated' ?></td>
									           <td><?php echo date('D j M Y', $activation['time']).' at '.date('h:ia T', $activation['time']) ?></td>
									           <td><?php echo $activation['version'] ?></td>
									           <td><?php echo ucwords($activation['os']) ?></td>
								          </tr>
												<?php endif; ?>
							        <?php endforeach; ?>
										<?php else : ?>
											<tr>No activations yet</tr>
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
 			* software_stats_options()
 			* dashboard widget options
			* @since 1.0
			*/
		function software_stats_options() {
			$defaults = array( 'from_date' => time()-(30 * 24 * 60 * 60), 'to_date' => time());
			if ( ( !$options = get_option( 'jigoshop_software_stats_options' ) ) || !is_array($options) )
				$options = array();
			return array_merge( $defaults, $options );
		}

/* =======================================
		filter add to cart & other jigoshop internal functions
==========================================*/

		/**
			* print_styles()
			* adds css to the front-end
			* @since 1.0
			*/
		function print_styles() {
			wp_register_style('jigoshop_software', plugins_url( 'inc/front-end.css', __FILE__ ));
			wp_enqueue_style('jigoshop_software');
		}

		/**
 			* add_to_cart()
 			* replace the default jigoshop add to cart button
			* @see downloadable_add_to_cart() from jigoshop
			* @since 1.0
			*/
		function add_to_cart() {
			global $_product; $availability = $_product->get_availability();
			if ($availability['availability']) : ?><p class="stock <?php echo $availability['class'] ?>"><?php echo $availability['availability']; ?></p><?php endif; ?>
			<form action="<?php echo $_product->add_to_cart_url(); ?>" class="cart" method="post">
				<button type="submit" class="button-alt"><?php _e('Buy Now', 'jigoshop'); ?></button>
				<?php do_action('jigoshop_add_to_cart_form'); ?>
			</form>
		<?php
		}

		/**
 			* loop_add_to_cart()
 			* replace the default jigoshop add to cart button
			* @see jigoshop_template_loop_add_to_cart()
			* @since 1.0
			*/
		function loop_add_to_cart($post, $_product) {
			?><a href="<?php echo $_product->add_to_cart_url(); ?>" class="button"><?php _e('Buy Now', 'jigoshop'); ?></a><?php
		}

		/**
 			* add_to_cart_redirect()
 			* redirect the user to checkout after they've clicked "buy now"
			* @see jigoshop_add_to_cart_action()
			* @since 1.0
			*/
		function add_to_cart_redirect() {
			return jigoshop_cart::get_checkout_url();
		}

		/**
 			* locate_api_template()
 			* filters the template for the api page so that it just does the json stuff
			* @since 1.0
			*/
		function locate_api_template($template) {
			global $post;
			if ($post->ID == get_option('jigoshop_api_page_id')) {
				$template = JIGOSHOP_SOFTWARE_PATH.'/inc/api.php';
			}
			return $template;
		}

		/**
			* jigoshop_software_filter_price_paypal()
			* very hack way to filter out the price sent to paypal when it's an upgrade, but the only way to do it w/out changing core jigoshop
			* @see $this->init_output_buffer()
			* @param $buffer (string) the original buffer output
			* @return $buffer (string) the filtered buffer output
			* @todo find a better a way to do this
			* @since 1.0
			*/
		function jigoshop_software_filter_price_paypal($buffer) {
			if (isset($_GET['order'])) {
				$order_id = $_GET['order'];
				$data = get_post_meta($order_id, 'order_data', true);
				$original_price = $data['original_price'];
				$correct_price = $data['order_total'];
				if ($original_price) {
					$buffer = str_replace('"amount_1" value="'.$original_price.'"', '"amount_1" value="'.$correct_price.'"', $buffer);
				}
			}
			return $buffer;
		}

/* =======================================
		helper functions
==========================================*/

		/**
 			* array_ify_keys()
 			* transforms a comma separated list of license keys into an array in order to store in the DB
			* @param (string) $keys, a comma separated list of keys
			* @since 1.0
			*/
		function array_ify_keys($keys = null) {
			$keys = esc_attr($keys);
			if (is_string($keys)) {
				$keys_array = explode(',', $keys);
				return $keys_array;
			}
			return false;
		}

		/**
 			* un_array_ify_keys()
 			* transforms an array of license keys into a comma separated list in order to display it
			* @param (array) $keys, the array of keys
			* @since 1.0
			*/
		function un_array_ify_keys($keys = null) {
			$i = 0;
			$keys_string = '';
			if (is_array($keys)) {
				foreach ($keys as $key) { $i++;
					if ($i != 1) $keys_string .= ',';
					$keys_string .= $key;
				}
				$keys_string = ltrim($keys_string, ','); // filter out a comma if there is one in the first character
				$keys_string = ltrim($keys_string, ' '); // filter out a space if there is one in the first character
				return $keys_string;
			}
			return false;
		}

		/**
 			* is_valid_upgrade_key()
 			* checks if a key is a valid upgrade key for a particular product
			* @param (string) $key, the key to validate
			* @param (int) $item_id, the product to validate for
			* @since 1.0
			*/
		function is_valid_upgrade_key($key = null, $item_id = null) {
			if ($key && $item_id) {
				$product_data = get_post_meta($item_id, 'product_data', true);
				$_keys = (array) $product_data['up_license_keys'];
				if (in_array($key, $_keys)) return true;
				else return false;
			}
			return false;
		}

		/**
 			* generate_license_key()
 			* generates a unique id that is used as the license code
			* @since 1.0
			*/
		function generate_license_key() {

			$uuid = sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
				mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
				mt_rand( 0, 0x0fff ) | 0x4000,
				mt_rand( 0, 0x3fff ) | 0x8000,
				mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ) );

			return $uuid;
		}

		/**
 			* redirect_away_from_cart()
 			* redirect the user away from cart page, either to checkout or to home
			* @since 1.0
			*/
		function redirect_away_from_cart() {
			if (is_cart()) {
				$redirect = (isset($_SESSION['cart'])) ? jigoshop_cart::get_checkout_url() : site_url();
				wp_safe_redirect($redirect); exit;
			}
		}

/* =======================================
		ajax, payment & email processing
==========================================*/

		/**
 			* ajax_jgs_checkout()
 			* process the ajax request to checkout
			* @since 1.0
			*/
		function ajax_jgs_checkout() {

			$messages = null; // reset in case this a second attempt
			$success = null;
			$message = null;

			$no_js = (isset($_POST['no_js']) && $_POST['no_js'] == 'true') ? true : false;
			if ($no_js) wp_safe_redirect(jigoshop_cart::get_checkout_url().'?no-js=trues');

			$item_id = esc_attr($_POST['item_id']);
			$qty = 1; // always 1 because it's a buy now situation not a cart situation
			$upgrade = false; // default

			// nonce verification
			if ( isset($_POST['jgs_checkout_nonce']) && !wp_verify_nonce($_POST['jgs_checkout_nonce'], 'jgs_checkout') ) $messages['nonce'] = 'An error has occurred, please try again';

			$key = esc_attr($_POST['up_key']);

			// email validation
			$email = strtolower(esc_attr($_POST['jgs_email']));
			if (!$email || $email == '') $messages['email'] = 'Please enter your email';
			elseif (!is_email($email)) $messages['email'] = 'Please enter a valid email address';

			// key validation
			if ($key != '' && !$this->is_valid_upgrade_key($key, $item_id)) $messages['key'] = 'The key you have entered is not valid, please try again or contact us if you need additional help';

			// if there is no message, then validation passed
			if(!$messages) {

				if ($this->is_valid_upgrade_key($key, $item_id)) $upgrade = true;

				$success = true;

				$order_data = array(
					'post_type' => 'shop_order',
					'post_title' => 'Order &ndash; '.date('F j, Y @ h:i A'),
					'post_status' => 'publish',
					'post_author' => 1
				);

				$product = get_post_meta($item_id, 'product_data', true);
				$sale_price = $product['sale_price'];
				$regular_price = $product['regular_price'];
				$price = ($sale_price && $sale_price != '') ? $sale_price : $regular_price;

				if ($upgrade) {
					$order['is_upgrade'] = 'on';
					$order['upgrade_name'] = $product['upgradable_product'];
					$order['upgrade_price'] = $product['up_price'];
					$order['original_price'] = $price;
					$price = $order['upgrade_price'];
					// move the upgraded key to the used keys
					unset($product['up_license_keys'][array_search($key, $product['up_license_keys'])]);
					$product['used_license_keys'][] = $key;
					update_post_meta($item_id, 'product_data', $product);
				}

				// Order meta data [from jigoshop]
				$order['billing_email'] = $email;
				$order['payment_method'] = 'paypal';
				$order['order_subtotal'] = $price*$qty;
				$order['order_shipping'] = 0;
				$order['order_discount'] = 0;
				$order['order_tax'] = 0;
				$order['order_shipping_tax']	= 0;
				$order['order_total'] = $order['order_subtotal'];

				// activation stuff
				$order['version'] = $product['version'];
				$order['license_key'] = $this->generate_license_key();
				$order['activations_possible'] = $product['activations'];
				$order['remaining_activations'] = $product['activations'];
				$order['secret_product_key'] = $product['secret_product_key'];
				$order['paypal_name'] = $product['paypal_name'];
				$order['productid'] = get_post_meta($item_id, 'soft_product_id', true);

				$order_items = array();

				$order_items[] = array(
			 		'id' 		=> $item_id,
			 		'name' 		=> get_the_title($item_id),
			 		'qty' 		=> (int) $qty,
			 		'cost' 		=> $price,
			 		'taxrate' 	=> 0
			 	);

				$order_id = wp_insert_post( $order_data );

				// Update post meta
				update_post_meta( $order_id, 'order_data', $order );
				update_post_meta( $order_id, 'activation_email', $email );
				update_post_meta( $order_id, 'activations', array() ); // store an empty array for use later
				update_post_meta( $order_id, 'order_key', uniqid('order_') );
				update_post_meta( $order_id, 'order_items', $order_items );
				wp_set_object_terms( $order_id, 'pending', 'shop_order_status' );

				$_order = &new jigoshop_order($order_id);

				// Inserted successfully
				do_action('jigoshop_new_order', $order_id);

				// Store Order ID in session
				$_SESSION['order_awaiting_payment'] = $order_id;

				// Process Payment
				$available_gateways = jigoshop_payment_gateways::get_available_payment_gateways();
				$result = $available_gateways['paypal']->process_payment( $order_id );

			} else {
				// building a message string from all of the $messages above
				$message = '';
				foreach ($messages as $k => $m) {
					$message .= $m.'<br>';
				}
				$success = false;
				$result = null;
			}

			header( "Content-Type: application/json" );
			$response = json_encode( array(
				'success' => $success,
				'message' => $message,
				'result' => $result,
			));
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

			$no_js = (isset($_POST['no_js']) && $_POST['no_js'] == 'true') ? true : false;
			if ($no_js) wp_safe_redirect(get_permalink(get_option('jigoshop_lost_license_page_id')).'?no-js=true');

			// nonce verification
			if ( $_POST['jgs_lost_license_nonce'] && !wp_verify_nonce($_POST['jgs_lost_license_nonce'], 'jgs_lost_license') ) $messages['nonce'] = 'An error has occurred, please try again';

			// email validation
			$email = esc_attr($_POST['jgs_email']);
			if (!$email || $email == '') $messages['email'] = 'Please enter your email';
			elseif (!is_email($email)) $messages['email'] = 'Please enter a valid email address';
			else {
				$_orders = get_posts(array(
					'post_type' => 'shop_order',
					'posts_per_page' => -1,
					'meta_query' => array(
						array(
							'key' => 'activation_email',
							'value' => $email
						)
					)
				));
				if (!is_array($_orders) || count($_orders) < 1) {
					$messages['email'] = 'There are no purchase records for this email address. Please try again. If you think there is a mistake, please contact us.';
				}
			}

			// if there is no message, then validation passed
			if(!$messages) {

				$data['email'] = $email;

				// loop through the orders
				$i = 0;
				foreach ($_orders as $order) {
					$order_status = wp_get_post_terms($order->ID, 'shop_order_status');
					$order_status = $order_status[0]->slug;
					// make sure it's a completed order
					if ($order_status == 'completed') {
						$i++;
						$order_data = get_post_meta($order->ID, 'order_data', true);
						$order_items = get_post_meta($order->ID, 'order_items', true);
						$data['purchases'][$i]['product'] = $order_items[0]['name'];
						$data['purchases'][$i]['price'] = $order_items[0]['cost'];
						$data['purchases'][$i]['date'] = get_the_time('l, F j Y', $order->ID);
						$data['purchases'][$i]['activation_email'] = get_post_meta($order->ID, 'activation_email', true);
						$data['purchases'][$i]['license_key'] = $order_data['license_key'];
						$data['purchases'][$i]['order_total'] = $order_items[0]['cost'];
						$data['purchases'][$i]['remaining_activations'] = $order_data['remaining_activations'];
						$data['purchases'][$i]['activations_possible'] = $order_data['activations_possible'];
					}
				}

				// are there completed orders ?
				if (isset($data['purchases']) && is_array($data['purchases']) && count($data['purchases']) > 0) {
					$success = true;
					$this->process_email($data, 'lost_license');
					$message = 'Your request has been accepted. You should receive an email shortly with all of your purchase history.';
				} else {
					$success = false;
					$message = 'Your purchases are not completed. If you think there is a mistake, please contact us.';
				}

			} else {
				// building a message string from all of the $messages above
				$message = '';
				foreach ($messages as $k => $m) {
					$message .= $m.'<br>';
				}
				$success = false;
				$result = null;
			}

			header( "Content-Type: application/json" );
			$response = json_encode( array(
				'success' => $success,
				'message' => $message,
			));
			echo $response;
			exit;
		}

		/**
 			* post_paypal_payment()
 			* processes the order post payment
			* @param $order_id (string), the order id to process
			* @since 1.6
			*/
		function post_paypal_payment($order_id) {
			if (isset($_GET['tx'])) {
				update_post_meta($order_id, 'transaction_id', $_GET['tx'], true);
			}
		}

		/**
 			* completed_order()
 			* sends out the completed order email
			* @param $order_id (string), the order id to process
			* @since 1.6
			*/
		function completed_order($order_id) {
			$this->process_email($order_id, 'completed_purchase');
		}


		/**
 			* process_email()
 			* process emails and send them out
			* @since 1.0
			*/
		function process_email( $data, $type ) {

			// switch based on the hook that was fired
			switch ($type) :

				case 'completed_purchase' :

					$order_id = $data;
					$order = &new jigoshop_order( $order_id );

					$date = date('l, F j Y', time());
					$data = get_post_meta($order_id, 'order_data', true);
					$products = get_post_meta($order_id, 'order_items', true);
					$product = $products[0]['name'];
					$price = $products[0]['cost'];
					$email = get_post_meta($order_id, 'activation_email', true);
					$total = $price;
					$max_activations = $data['activations_possible'];
					$license_key = $data['license_key'];
					$paypal_name = $data['paypal_name'];

					$send_to = get_post_meta($order_id, 'activation_email', true);
					$subject = $product.' '.__('Purchase Confirmation','jigoshop');
					$message = file_get_contents(JIGOSHOP_SOFTWARE_PATH.'/inc/email-purchase.txt');
					$message = str_replace('{date}', $date, $message);
					$message = str_replace('{product}', $product, $message);
					$message = str_replace('{license_key}', $license_key, $message);
					$message = str_replace('{price}', $price, $message);
					$message = str_replace('{email}', $email, $message);
					$message = str_replace('{total}', $total, $message);
					$message = str_replace('{max_activations}', $max_activations, $message);
					$message = str_replace('{paypal_name}', $paypal_name, $message);

				break;

				case 'lost_license' :

					$subject = __('Recovered Licenses','jigoshop');
					$send_to = $data['email'];
					$message = file_get_contents(JIGOSHOP_SOFTWARE_PATH.'/inc/email-lost-license.txt');
					$orders = '';

					$i = 0;
					foreach ($data['purchases'] as $purchase) { $i++;
						$orders .=
						'====================================================================='."\n"
						.'Order '.$i.''."\n"
						.'====================================================================='."\n\n"
						.'Item: '.$purchase['product']."\n"
						.'Item Price: $'.$purchase['price']."\n"
						.'Purchase date: '.$purchase['date']."\n\n"
						.'Account Name: '.$purchase['activation_email']."\n"
						.'License Key: '.$purchase['license_key']."\n"
						.'Transaction Total: $'.$purchase['order_total'].' via paypal'."\n"
						.'Currency: USD'."\n"
						.'Activations: '.$purchase['remaining_activations'].' out of '.$purchase['activations_possible'].' activations remaining'."\n\n\n";
					}

				$message = str_replace('{orders}', $orders, $message);

				break;

				case 'new_activation' :

					$subject = $data['product'].' '.__('Activation Confirmation','jigoshop');
					$send_to = $data['email'];
					$message = file_get_contents(JIGOSHOP_SOFTWARE_PATH.'/inc/email-activation.txt');
					$date = date('l, F j Y', time());
					$message = str_replace('{date}', $date, $message);
					$message = str_replace('{remaining_activations}', $data['remaining_activations'], $message);
					$message = str_replace('{activations_possible}', $data['activations_possible'], $message);
					$message = str_replace('{product}', $data['product'], $message);

				break;

			endswitch;

			$message = str_replace('{site_url}', site_url(), $message);
			$headers = 'From: '.get_bloginfo('name').' <'.get_bloginfo('admin_email').'>' . "\r\n";
			wp_mail($send_to, $subject, $message, $headers);

		}

/* =======================================
		import
==========================================*/

	/**
		* import_page()
		* creates a backend page for the importer
		* @since 1.1
		*/
		function import_page() { ?>
			<div class="wrap jigoshop">
				<div class="icon32 icon32-jigoshop-debug" id="icon-jigoshop"><br/></div>
	    	<h2><?php _e('Import','jigoshop') ?></h2>

				<div class="metabox-holder" style="margin-top:25px">
					<div class="postbox-container" style="width:700px;">

						<div class="postbox">
							<h3>Enter the path/filename of the import file</h3>
							<div class="inside">
								<p><strong>Recommended:</strong> back up the database before proceeding</p>
								<form id="jgs_import" action="<?php echo admin_url('admin-ajax.php') ?>" method="post">
									<?php $value = (isset($_POST['import_path']) && $_POST['import_path'] != '') ? esc_attr($_POST['import_path']) : 'wp-content/plugins/jigoshop-software/inc/import.php' ?>
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
										$.post("<?php echo admin_url('admin-ajax.php') ?>", args, function(response){
											if (response.success) {
												$('#jgs_import_feedback').addClass('doing_import').html('Importing '+response.total_count+' records. Please be patient.').fadeIn();
												args['action']='jgs_do_import';
												args['import']=response.import;
												$.post("<?php echo admin_url('admin-ajax.php') ?>", args, function(resp){
													if (resp.success) {
														load.removeClass('loading');
														$('#jgs_import_feedback').fadeOut('normal', function(){
															$(this).html('All Records Imported!').fadeIn();
															$('#jgs_import_feedback_done').html(resp.feedback).fadeIn();
														});
													} else {
														load.removeClass('loading');
														$('#jgs_import_feedback').fadeOut('normal', function(){
															$(this).removeClass('doing_import').html('An error has occurred and the import did not complete, please refresh the page and try again.').fadeIn();
														});
													}
												});
											} else {
												if (response.success === false) {
													load.removeClass('loading');
													$('#jgs_import_feedback').html(response.message).fadeIn();
												} else {
													load.removeClass('loading');
													$('#jgs_import_feedback').html('An error has occurred, please refresh the page and try again.').fadeIn();
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


			$import_path = esc_attr($_POST['import_path']);
			$file_path = ABSPATH.$import_path;
			if (is_file($file_path)) {
				include_once($file_path);
				if (!is_array($import)) $messages['missing_array'] = 'This file doesn\'t contain an $import array, please try again.';
			} else {
				$messages['missing_file'] = 'This file doesn\'t exist or doesn\'t have read permissions, please try again.';
			}

			// if there is no message, then validation passed
			if(!$messages) {

				$total_count = count($import);
				$success = true;

				header( "Content-Type: application/json" );
				$response = json_encode( array(
					'success' => $success,
					'total_count' => $total_count,
					'import' => $import,
				));
				echo $response;
				exit;

			} else {
				// building a message string from all of the $messages above
				$message = '';
				foreach ($messages as $k => $m) {
					$message .= $m.'<br>';
				}
				$success = false;
				$result = null;
			}

			header( "Content-Type: application/json" );
			$response = json_encode( array(
				'success' => $success,
				'message' => $message,
			));
			echo $response;
			exit;


		}


		/**
			* import()
			* import routine
			* @since 1.1
			*/
		function import($import = null) {

			if (!$import) {
				$import = $_POST['import'];
			}

			$failures = array();
			$duplicate = array();
			$succesful = array();

			foreach ($import as $imp) {

				// gather the fields
				$date = strtotime($imp['purchase_time']);
				$email = (is_email($imp['email'])) ? strtolower($imp['email']) : '';
				$price = $imp['amount'];
				$product_id = $imp['product_id'];
				$license_key = $imp['license_key'];
				$payment_type = $imp['payment_type'];
				$old_order_id = $imp['order_id'];

				// double check this order doesn't exist already
				$_duplicate = get_posts( array('post_type' => 'shop_order', 'meta_query' => array( array( 'key' => 'old_order_id', 'value' => $old_order_id ) ) ));
				$_duplicate = @$_duplicate[0];
				if (is_object($_duplicate)) {
					$duplicate[] = $old_order_id;
				} else {
					// fetch the product & associated meta information
					$_item_id = get_posts( array('post_type' => 'product', 'meta_query' => array( array( 'key' => 'soft_product_id', 'value' => $product_id ) ) ));
					$item_id = @$_item_id[0];
					if (is_object($item_id)) {
						$item_id = $item_id->ID;
						$product = get_post_meta($item_id, 'product_data', true);
						$order_items = array();
						$order_items[] = array(
							'id' 		=> $item_id,
							'name' 		=> get_the_title($item_id),
							'qty' 		=> (int) 1,
							'cost' 		=> $price,
							'taxrate' 	=> 0,
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

					$order['version'] = "n/a";
					$order['license_key'] = $license_key;
					$order['activations_possible'] = 3;
					$order['remaining_activations'] = 3;
					$order['secret_product_key'] = @$product['secret_product_key'];
					$order['paypal_name'] = @$product['paypal_name'];
					$order['productid'] = $product_id;

					$order_data = array(
						'post_type' => 'shop_order',
						'post_title' => 'Order &ndash; '.date('F j, Y @ h:i A', $date),
						'post_status' => 'publish',
						'post_author' => 1,
						'post_date' => date('Y-m-d H:i:s', $date),
					);

					$order_id = wp_insert_post( $order_data );

					if (is_wp_error($order_id)) {
						$failures[] = $old_order_id;
					} else {
						$_order = &new jigoshop_order($order_id);

						// Update post meta
						update_post_meta( $order_id, 'order_data', $order );
						update_post_meta( $order_id, 'old_order_id', $old_order_id );
						update_post_meta( $order_id, 'activation_email', $email );
						update_post_meta( $order_id, 'activations', array() ); // store an empty array for use later
						update_post_meta( $order_id, 'order_key', uniqid('order_') );
						update_post_meta( $order_id, 'order_items', $order_items );
						wp_set_object_terms( $order_id, 'completed', 'shop_order_status' );

						$succesful[] = $order_id;

					}
				}
			}

			foreach ($failures as $fail) {
				$feedback[] = 'The following record failed to import: ORDER ID = '.$fail;
			}

			foreach ($duplicate as $dupe) {
				$feedback[] = 'The following record was a duplicate: ORDER ID = '.$dupe;
			}

			$feedback[] = count($succesful).' records successfully imported.';

			$feedback_string = '<ul>';
			foreach ($feedback as $fdb) {
				$feedback_string .= '<li>'.$fdb.'</li>';
			}
			$feedback_string .= '<ul>';

			header( "Content-Type: application/json" );
			$response = json_encode( array(
				'success' => true,
				'feedback' => $feedback_string,
			));
			echo $response;
			exit;

		}

	} // end class

	add_action('init', 'initJigoShopSoftware');
	function initJigoShopSoftware() {
		global $jigoshopsoftware;
		$jigoshopsoftware = new jigoshop_software();
		ob_start(array(&$jigoshopsoftware, 'jigoshop_software_filter_price_paypal'));
		include_once('inc/shortcodes.php');
	}

} // end class exists

/**
 	* run the plugin activation hook
	* @since 1.0
	*/
register_activation_hook(__FILE__, array('jigoshop_software', 'activation'));
jigoshop_software::define_constants();

if (is_admin()) {
	include_once('inc/_updater.php');
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
	new wp_github_updater($config);
}