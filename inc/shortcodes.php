<?php
/**
	* This file registers shortcodes used by the plugin
	* @since 1.0
	* @author Joachim Kudish <info@jkudish.com>
	*/

/**
	* jigoshop_software_checkout()
	* shortcode used for the checkout page
	* @since 1.0
	*/
function jigoshop_software_checkout( $atts ) {

	if (!defined('JIGOSHOP_CHECKOUT')) define('JIGOSHOP_CHECKOUT', true);
			
	include_once('checkout.php');
	
}
add_shortcode('jigoshop_software_checkout', 'jigoshop_software_checkout');


/**
	* jigoshop_software_checkout()
	* shortcode used for the lost license page
	* @since 1.0
	*/
function jigoshop_software_lost_license( $atts ) {
			
	include_once('lost-license.php');
	
}
add_shortcode('jigoshop_software_lost_license', 'jigoshop_software_checkout');
