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

			// define the metadata fields used by this plugin
			$this->fields = array(
				array('id' => 'upgradable_product', 'label' => 'Upgradable Product Name', 'title' => 'Upgradable Product Name', 'placeholder' => '', 'type' => 'text'),
				array('id' => '', 'label' => '', 'title' => '', 'placeholder' => '', 'type' => ''),
				array('id' => '', 'label' => '', 'title' => '', 'placeholder' => '', 'type' => ''),
				array('id' => '', 'label' => '', 'title' => '', 'placeholder' => '', 'type' => ''),
				array('id' => '', 'label' => '', 'title' => '', 'placeholder' => '', 'type' => ''),
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
							echo '<p class="form-field"><label for="'.$field['id'].'">'.$field['label'].'</label><input type="text" id="'.$field['id'].'" name="'.$field['id'].'" value="'.$data[$field['id']].'" /></p>';
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
		function product_save_data() {		
			foreach ($this->fields as $field) {
				$data[$field['id']] = esc_attr( $_POST[$field['id']] );
			}	
			return $data;
		}
	
		
		
	
	}

	global $jigoshopsoftware;
	$jigoshopsoftware = new JigoShopSoftware();

} // end class exists