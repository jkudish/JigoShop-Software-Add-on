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

if (!class_exists('JigoShopSoftware')) {

	class JigoShopSoftware {
	
		function __construct() {
			
			// define constants
			define('JIGOSHOP_SOFTWARE_PATH', dirname(__FILE__));
		
			// activation hook
			register_activation_hook(__FILE__, array(&$this, 'activation'));
			
			// hooks
			add_action('product_write_panel_tabs', array(&$this, 'product_write_panel_tab'));
			add_action('product_write_panels', array(&$this, 'product_write_panel'));
			add_filter('process_product_meta', array(&$this, 'product_save_data'));
			remove_action( 'downloadable_add_to_cart', 'jigoshop_downloadable_add_to_cart' ); 
			add_action( 'downloadable_add_to_cart', array(&$this, 'downloadable_add_to_cart')); 
			
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
					switch ($field['type']) :
						case 'text' :
							echo '<p class="form-field"><label for="'.$field['id'].'">'.$field['label'].'</label><input type="text" id="'.$field['id'].'" name="'.$field['id'].'" value="'.$data[$field['id']].'" placeholder="'.$field['placeholder'].'"/></p>';
						break;
						case 'number' :
							echo '<p class="form-field"><label for="'.$field['id'].'">'.$field['label'].'</label><input type="number" id="'.$field['id'].'" name="'.$field['id'].'" value="'.$data[$field['id']].'" placeholder="'.$field['placeholder'].'"/></p>';
						break;						
						case 'textarea' :
							echo '<p class="form-field"><label for="'.$field['id'].'">'.$field['label'].'</label><textarea id="'.$field['id'].'" name="'.$field['id'].'" placeholder="'.$field['placeholder'].'">'.$data[$field['id']].'</textarea></p>';
						break;					
						case 'checkbox' :
							if ($data[$field['id']] == 'on') $checked = ' checked=checked';
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
		function product_save_data( $data ) {		
			foreach ($this->fields as $field) {
				$data[$field['id']] = esc_attr( $_POST[$field['id']] );
			}	
			return $data;
		}

		/**
 			* downloadable_add_to_cart()
 			* replace the default jigoshop add to cart button
			* @see downloadable_add_to_cart() from jigoshop
			* @since 1.0
			*/	
		function downloadable_add_to_cart() {
			global $_product; $availability = $_product->get_availability();
			if ($availability['availability']) : ?><p class="stock <?php echo $availability['class'] ?>"><?php echo $availability['availability']; ?></p><?php endif; ?>						
			<form action="<?php echo $_product->add_to_cart_url(); ?>" class="cart" method="post">
				<button type="submit" class="button-alt"><?php _e('Buy Now', 'jigoshop'); ?></button>
				<?php do_action('jigoshop_add_to_cart_form'); ?>
			</form>	
		<?php
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

	} // end class
	
	add_action('init', 'initJigoShopSoftware');
	function initJigoShopSoftware() {
		global $jigoshopsoftware;
		$jigoshopsoftware = new JigoShopSoftware();
	}

} // end class exists