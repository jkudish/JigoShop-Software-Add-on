<?php
function jigoshop_software_checkout( $atts ) {

	if (!defined('JIGOSHOP_CHECKOUT')) define('JIGOSHOP_CHECKOUT', true);
			
	include_once('checkout.php');
	
}
add_shortcode('jigoshop_software_checkout', 'jigoshop_software_checkout');
