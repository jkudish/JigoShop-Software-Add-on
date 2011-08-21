<?php
/*
Plugin Name: JigoShop - Software Add-On
Plugin URI: https://github.com/jkudish/JigoShop-Software-Add-on/
Description: Extends JigoShop to a full-blown software shop, including license activation, license retrieval, activation e-mails and more
Version: 1.0
Author: Joachim Kudish
Author URI: http://jkudish.com
License: GPL v3
*/

/**
	* @version 1.0
	* @author Joachim Kudish <info@jkudish.com>
	* @link http://jkudish.com
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
			array('id' => 'upgradable_product', 'label' => 'Upgradable Product Name:', 'title' => 'Upgradable Product Name', 'placeholder' => '', 'type' => 'text'),
			array('id' => 'up_license_keys', 'label' => 'Upgradable Product Keys:', 'title' => 'Upgradable Product Keys', 'placeholder' => 'Comma separated list', 'type' => 'textarea'),
			array('id' => 'up_price', 'label' => 'Upgrade Price ($):', 'title' => 'Upgrade Price ($)', 'placeholder' => 'ex: 1.00', 'type' => 'text'),
			array('id' => 'version', 'label' => 'Version Number:', 'title' => 'Version Number', 'placeholder' => 'ex: 1.0', 'type' => 'text'),
			array('id' => 'trial', 'label' => 'Trial Period (amount of days or hours):', 'title' => 'Trial Period (amount of days or hours)', 'placeholder' => 'ex: 15', 'type' => 'text'),
			array('id' => 'trial_unit', 'label' => 'Trial Units:', 'title' => 'Trial Units', 'type' => 'select', 'values' => array('days' => 'Days', 'hours' => 'Hours')),
			array('id' => 'activations', 'label' => 'Amount of activations possible:', 'title' => 'Amount of activations possible', 'placeholder' => 'ex: 5', 'type' => 'text'),
			array('id' => 'soft_product_id', 'label' => 'Product ID to use for API:', 'title' => 'Product ID to use for API', 'placeholder' => 'ex: SPARKBOOTH', 'type' => 'text'),
			array('id' => 'secret_product_key', 'label' => 'Secret Product Key to use for API:', 'title' => 'Secret Product Key to use  for API', 'placeholder' => 'any random string', 'type' => 'text'),
		);

		// define the order metadata fields used by this plugin		
		static $order_fields = array(
			array('id' => 'activation_email', 'label' => 'Activation Email:', 'title' => 'Activation Email', 'placeholder' => '', 'type' => 'text'),			
			array('id' => 'license_key', 'label' => 'License Key:', 'title' => 'License Key', 'placeholder' => '', 'type' => 'text'),
			array('id' => 'productid', 'label' => 'Product ID:', 'title' => 'Product ID', 'placeholder' => '', 'type' => 'text'),
			array('id' => 'activations_possible', 'label' => 'Max Activations Allowed:', 'title' => 'Max Activations Allowed', 'placeholder' => '', 'type' => 'text'),			
			array('id' => 'remaining_activations', 'label' => 'Remaining Activations:', 'title' => 'Remaining Activations', 'placeholder' => '', 'type' => 'text'),
			array('id' => 'secret_product_key', 'label' => 'Secret Product Key to use for API:', 'title' => 'Secret Product Key to use  for API', 'placeholder' => 'any random string', 'type' => 'text'),			
			array('id' => 'version', 'label' => 'Version:', 'title' => 'Version', 'placeholder' => '', 'type' => 'text'),
			array('id' => 'is_upgrade', 'label' => 'This is an upgrade if checked', 'title' => 'This is an upgrade if checked', 'placeholder' => '', 'type' => 'checkbox'),
			array('id' => 'upgrade_name', 'label' => 'Upgraded from:', 'title' => 'License Key', 'placeholder' => '', 'type' => 'text'),
			array('id' => 'upgrade_price', 'label' => 'Upgrade price ($):', 'title' => 'License Key', 'placeholder' => '', 'type' => 'text'),
			array('id' => 'original_price', 'label' => 'Original price ($):', 'title' => 'License Key', 'placeholder' => '', 'type' => 'text'),
		);
		
	
		function __construct() {
			
			// define constants
			define('JIGOSHOP_SOFTWARE_PATH', dirname(__FILE__));
		
			// activation hook
			register_activation_hook(__FILE__, array(&$this, 'activation'));
			
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
			
			// frontend stuff
			remove_action( 'simple_add_to_cart', 'jigoshop_simple_add_to_cart' ); 
			remove_action( 'virtual_add_to_cart', 'jigoshop_simple_add_to_cart' ); 
			remove_action( 'downloadable_add_to_cart', 'jigoshop_downloadable_add_to_cart' ); 
			remove_action( 'jigoshop_after_shop_loop_item', 'jigoshop_template_loop_add_to_cart', 10, 2);
			add_action( 'simple_add_to_cart', array(&$this, 'add_to_cart')); 
			add_action( 'virtual_add_to_cart', array(&$this, 'add_to_cart')); 
			add_action( 'downloadable_add_to_cart', array(&$this, 'add_to_cart')); 
			add_action( 'jigoshop_after_shop_loop_item', array(&$this, 'loop_add_to_cart'), 10, 2); 

			add_action( 'wp_print_styles', array(&$this, 'print_styles')); 
			add_action( 'wp_head', array(&$this, 'redirect_away_from_cart')); 
			add_action( 'wp_ajax_nopriv_jgs_checkout', array(&$this, 'ajax_jgs_checkout')); 
			add_action( 'wp_ajax_jgs_checkout', array(&$this, 'ajax_jgs_checkout')); 
			
			// filters
			add_filter('add_to_cart_redirect', array(&$this, 'add_to_cart_redirect'));
			add_filter('page_template', array(&$this, 'locate_api_template'), 10, 1);
			
		}
		
		/**
 			* activation()
 			* checks if the jigoshop plugin is running and disables this plugin if it's not (and displays a message)
			* @see register_activation_hook()
			* @link http://codex.wordpress.org/Function_Reference/register_activation_hook 
			* @since 1.0
			* @todo shortcode replacement / page creation
			*/
		function activation() {
			if (!is_plugin_active('jigoshop/jigoshop.php')) {
				deactivate_plugins(plugin_basename(__FILE__));				
				wp_die(__('The JigoShop Software Add-On requires <a href="http://jigoshop.com" target="_blank">JigoShop</a> to be activated in order to work. Please activate <a href="http://jigoshop.com" target="_blank">JigoShop</a> first. <a href="'.admin_url('plugins.php').'"> <br> &laquo; Go Back</a>', 'jigoshop'));
			}
		}

		/**
 			* print_styles()
 			* adds css to the front-end
			* @since 1.0
			*/	
    function print_styles() {
			wp_register_style('jigoshop_software', plugins_url( 'inc/front-end.css', __FILE__ ));
			wp_enqueue_style('jigoshop_software');
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
					else @$value = ($field['id'] == 'up_license_keys') ? $this->un_array_ify_keys($data[$field['id']]) : $data[$field['id']];					
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
				if ($field['id'] == 'up_license_keys') $data[$field['id']] = $this->array_ify_keys($_POST[$field['id']]);
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
			foreach (self::$order_fields as $field) {
				if ($field['id'] == 'activation_email') update_post_meta($post->ID, 'activation_email', $_POST[$field['id']]);
				else $data[$field['id']] = esc_attr( $_POST[$field['id']] );
			}	
			update_post_meta($post->ID, 'order_data', $data);			
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

/* =======================================
		filter add to cart & other jigoshop internal functions
==========================================*/

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
			global $wp_query;
			if ($wp_query->query_vars['pagename'] == 'api') { // todo make this better
				$template = JIGOSHOP_SOFTWARE_PATH.'/inc/api.php';
			}	
			return $template;
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
		ajax & email processing
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

			// $no_js = (isset($_POST['no_js']) && $_POST['no_js'] == 'true') ? true : false;
			// if ($no_js) wp_safe_redirect(jigoshop_cart::get_checkout_url().'?no-js=true');

			$item_id = esc_attr($_POST['item_id']);
			$qty = 1; // always 1 because it's a buy now situation not a cart situation
			$upgrade = false; // default
			
			// nonce verification
			if ( $_POST['jgs_checkout_nonce'] && !wp_verify_nonce($_POST['jgs_checkout_nonce'], 'jgs_checkout') ) $messages['nonce'] = 'An error has occurred2, please try again';
						
			if (isset($_POST['up_key'])) { 
				$key = esc_attr($_POST['up_key']);
				$upgrade = true;
			}	

			// email validation
			$email = esc_attr($_POST['jgs_email']);
			if (!$email || $email == '') $messages['email'] = 'Please enter your email';
			elseif (!is_email($email)) $messages['email'] = 'Please enter a valid email address';
			
			// key validation
			if ($upgrade && $key != '' && !$this->is_valid_upgrade_key($key, $item_id)) $messages['key'] = 'The key you have entered is not valid, please try again or contact us if you need additional help';			

			// if there is no message, then validation passed
			if(!$messages) {
			
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
				$order['productid'] = get_post_meta($item_id, 'soft_product_id', true);
				
				/*
					TODO add coupon support (long-term)
				*/
					
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
			* TODO!!!
			*/									
		function ajax_jgs_lost_license() {

			$messages = null; // reset in case this a second attempt	
			$success = null;
			$message = null;

			// $no_js = (isset($_POST['no_js']) && $_POST['no_js'] == 'true') ? true : false;
			// if ($no_js) wp_safe_redirect(jigoshop_cart::get_checkout_url().'?no-js=true');

			$item_id = esc_attr($_POST['item_id']);
			$qty = 1; // always 1 because it's a buy now situation not a cart situation
			$upgrade = false; // default
			
			// nonce verification
			if ( $_POST['jgs_checkout_nonce'] && !wp_verify_nonce($_POST['jgs_checkout_nonce'], 'jgs_checkout') ) $messages['nonce'] = 'An error has occurred2, please try again';
						
			if (isset($_POST['up_key'])) { 
				$key = esc_attr($_POST['up_key']);
				$upgrade = true;
			}	

			// email validation
			$email = esc_attr($_POST['jgs_email']);
			if (!$email || $email == '') $messages['email'] = 'Please enter your email';
			elseif (!is_email($email)) $messages['email'] = 'Please enter a valid email address';
			
			// key validation
			if ($upgrade && $key != '' && !$this->is_valid_upgrade_key($key, $item_id)) $messages['key'] = 'The key you have entered is not valid, please try again or contact us if you need additional help';			

			// if there is no message, then validation passed
			if(!$messages) {
			
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
				$order['productid'] = get_post_meta($item_id, 'soft_product_id', true);
				
				/*
					TODO add coupon support (long-term)
				*/
					
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
		
	} // end class
	
	add_action('init', 'initJigoShopSoftware');
	function initJigoShopSoftware() {
		global $jigoshopsoftware;
		$jigoshopsoftware = new jigoshop_software();
		include_once('inc/shortcodes.php');
	}

} // end class exists