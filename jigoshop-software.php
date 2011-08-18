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
	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	* GNU General Public License for more details.
	* 
	* You should have received a copy of the GNU General Public License
	* along with this program; if not, write to the Free Software
	* Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
	*/	

if (!class_exists('jigoshop_software')) {

	class jigoshop_software {
	
		function __construct() {
			
			// define constants
			define('JIGOSHOP_SOFTWARE_PATH', dirname(__FILE__));
		
			// activation hook
			register_activation_hook(__FILE__, array(&$this, 'activation'));
			
			/**
			 * hooks
			 */

			// backend stuff			
			add_action('product_write_panel_tabs', array(&$this, 'product_write_panel_tab'));
			add_action('product_write_panels', array(&$this, 'product_write_panel'));
			add_filter('process_product_meta', array(&$this, 'product_save_data'));
			
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


			// define the metadata fields used by this plugin
			$this->fields = array(
				array('id' => 'is_software', 'label' => 'This product is Software', 'title' => 'This product is Software', 'placeholder' => '', 'type' => 'checkbox'),
				array('id' => 'upgradable_product', 'label' => 'Upgradable Product Name:', 'title' => 'Upgradable Product Name', 'placeholder' => '', 'type' => 'text'),
				array('id' => 'up_license_keys', 'label' => 'Upgradable Product Keys:', 'title' => 'Upgradable Product Keys', 'placeholder' => 'Comma separated list', 'type' => 'textarea'),
				array('id' => 'up_price', 'label' => 'Upgrade Price ($):', 'title' => 'Upgrade Price ($)', 'placeholder' => 'ex: 1.00', 'type' => 'text'),
				array('id' => 'version', 'label' => 'Version Number:', 'title' => 'Version Number', 'placeholder' => 'ex: 1.0', 'type' => 'text'),
				array('id' => 'trial', 'label' => 'Trial (amount of days):', 'title' => 'Trial (amount of days)', 'placeholder' => 'ex: 15', 'type' => 'text'),
				array('id' => 'product_id', 'label' => 'Product ID:', 'title' => 'Product ID', 'placeholder' => 'Optional Product ID for activation', 'type' => 'text'),								
			);			
			
			
		}
		
		/**
 			* activation()
 			* checks if the jigoshop plugin is running and disables this plugin if it's not (and displays a message)
			* @see register_activation_hook()
			* @link http://codex.wordpress.org/Function_Reference/register_activation_hook 
			* @since 1.0
			*/
		function activation() {
			if (!is_plugin_active('jigoshop/jigoshop.php')) {
				deactivate_plugins(plugin_basename(__FILE__));				
				wp_die(__('The JigoShop Software Add-On requires <a href="http://jigoshop.com" target="_blank">JigoShop</a> to be activated in order to work. Please activate <a href="http://jigoshop.com" target="_blank">JigoShop</a> first. <a href="'.admin_url('plugins.php').'"> <br> &laquo; Go Back</a>', 'jigoshop'));
			}
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
 			* product_write_panels()
 			* adds the panel to the product interface
			* @since 1.0
			*/
		function product_write_panel() {
			global $post;
			$data = maybe_unserialize( get_post_meta($post->ID, 'product_data', true) );
		?>	
			<div id="software_data" class="panel jigoshop_options_panel">
			<?php 
				foreach ($this->fields as $field) : 
					$value = ($field['id'] == 'up_license_keys') ? $this->un_array_ify_keys($data[$field['id']]) : $data[$field['id']];
					
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
							if ($value == 'on') $checked = ' checked=checked';
							echo '<p class="form-field"><label for="'.$field['id'].'">'.$field['label'].'</label><input type="checkbox" id="'.$field['id'].'" name="'.$field['id'].'" value="on"'.$checked.'</p>';
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
			foreach ($this->fields as $field) {
				if ($field['id'] == 'up_license_keys') $data[$field['id']] = $this->array_ify_keys($_POST[$field['id']]);
				else $data[$field['id']] = esc_attr( $_POST[$field['id']] );
			}	
			return $data;
		}

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
 			* print_styles()
 			* adds css to the front-end
			* @since 1.0
			*/	
    function print_styles() {
			wp_register_style('jigoshop_software', plugins_url( 'inc/front-end.css', __FILE__ ));
			wp_enqueue_style('jigoshop_software');
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

		/**
 			* record_upgrade()
 			* record an upgrade into the jigoshop order system
			* @since 1.0
			*/							
		function record_upgrade() {
			
		}
		
		/**
 			* ajax_jgs_process_checkout()
 			* process the ajax request to checkout
			* @since 1.0
			*/									
		function ajax_jgs_checkout() {

			$messages = null; // reset in case this a second attempt	
			$success = null;
			$message = null;
	
			$item_id = esc_attr($_POST['item_id']);
			$key = esc_attr($_POST['up_key']);
			$email = esc_attr($_POST['jgs_email']);
						
			// nonce verification
			if ( $_POST['jgs_checkout_nonce'] && !wp_verify_nonce($_POST['jgs_checkout_nonce'], 'jgs_checkout') ) $messages['nonce'] = 'An error has occurred2, please try again';
			
			// email validation
			if (!$email || $email == '') $messages['email'] = 'Please enter your email';
			elseif (!is_email($email)) $messages['email'] = 'Please enter a valid email address';
			
			// key validation
			if ($key && $key != '' && !$this->is_valid_upgrade_key($key, $item_id)) $messages['key'] = 'The key you have entered is not valid, please try again or contact us if you need additional help';
			$upgrade = false; // todo
			$qty = 0; // todo

			// if there is no message, then validation passed
			if(!$messages) {
			
				$success = true;
																				
				$order_data = array(
					'post_type' => 'shop_order',
					'post_title' => 'Order &ndash; '.date('F j, Y @ h:i A'),
					'post_status' => 'publish',
					'post_author' => 1
				);
				
				$data = get_post_meta($item_id, 'product_data', true);
				$sale_price = $data['sale_price'];
				$regular_price = $data['regular_price'];
				$price = ($sale_price && $sale_price != '') ? $sale_price : $regular_price;
				$version = $data['version'];
				
				
				if ($upgrade) {
					$up_name = $data['upgradable_product'];
					$price = $data['up_price'];
				}	
								
				// Order meta data
				$data['billing_email'] = $email;
				$data['payment_method'] = 'paypal';
				$data['order_subtotal'] = $price*$qty;
				$data['order_shipping'] = 0;
				$data['order_discount'] = 0;
				$data['order_tax'] = 0;
				$data['order_shipping_tax']	= 0;
				$data['order_total'] = $data['order_subtotal'];
					
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
				update_post_meta( $order_id, 'order_data', $data );
				update_post_meta( $order_id, 'order_key', uniqid('order_') );
				update_post_meta( $order_id, 'order_items', $order_items );
				wp_set_object_terms( $order_id, 'pending', 'shop_order_status' );
			
				$order = &new jigoshop_order($order_id);
					
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
 			* generate_license_code()
 			* generates a unique id that is used as the license code
			* @since 1.0
			*/		
		function generate_license_code() {

			$uuid = sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
				mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
				mt_rand( 0, 0x0fff ) | 0x4000,
				mt_rand( 0, 0x3fff ) | 0x8000,
				mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ) );

			return $uuid;
		}		

		
	} // end class
	
	add_action('init', 'initJigoShopSoftware');
	function initJigoShopSoftware() {
		global $jigoshopsoftware;
		$jigoshopsoftware = new jigoshop_software();
		include_once('inc/shortcodes.php');
	}

} // end class exists