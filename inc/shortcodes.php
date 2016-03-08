<?php
/**
	* This file registers shortcodes used by the plugin
	*
	* @since 1.0
	* @author Joachim Kudish <info@jkudish.com>
	*/

remove_shortcode( 'jigoshop_checkout', 'jigoshop_checkout' );
add_shortcode( 'jigoshop_checkout', 'jigoshop_software_checkout' );
add_shortcode( 'jigoshop_software_checkout', 'jigoshop_software_checkout' );
add_shortcode( 'jigoshop_software_lost_license', 'jigoshop_software_lost_license' );
add_shortcode( 'jigoshop_software_upgrade', 'jigoshop_software_upgrade' );
add_shortcode( 'jigoshop_software_activiation_notification_subscribe', 'jigoshop_software_activation_notification_subscribe' );
add_shortcode( 'jigoshop_software_activiation_notification_unsubscribe', 'jigoshop_software_activation_notification_unsubscribe' );


/**
	* shortcode used for the checkout page
	*
	* @since 1.0
	*/
function jigoshop_software_checkout( $atts ) {

	if ( !defined( 'JIGOSHOP_CHECKOUT' ) ) define( 'JIGOSHOP_CHECKOUT', true );

	include_once( 'checkout.php' );

}

/**
	* shortcode used for the lost license page
	*
	* @since 1.0
	*/
function jigoshop_software_lost_license( $atts ) {

	include_once( 'lost-license.php' );

}


/**
 * shortcode used for the upgrade page
 *
 * @since 1.0
 */
function jigoshop_software_upgrade( $atts ) {

	include_once( 'upgrade.php' );

}

/**
 * shortcode used for the Activation Notification Unsubscribe confirmation page
 *
 * @since 2.7
 */
function jigoshop_software_activation_notification_unsubscribe( $atts ) {

	include_once( 'activation-notification-unsubscribe.php' );

}

/**
 * shortcode used for the Activation Notification Subscribe form page
 *
 * @since 2.7
 */
function jigoshop_software_activation_notification_subscribe( $atts ) {

	include_once( 'activation-notification-subscribe.php' );

}
