<?php

/**
	* This file renders the checkout page
	*
	* @since 1.0
	* @author Joachim Kudish <info@jkudish.com>
	*/

if ( !empty( $_GET['empty'] ) && $_GET['empty'] == true ) {
	setcookie( 'jgs_upgrade_prefill', null, -1, '/' );
	jigoshop_cart::empty_cart();
	wp_redirect( site_url( '/checkout' ) ); exit;
}


if ( sizeof( jigoshop_cart::$cart_contents ) > 0 ) :
	foreach (jigoshop_cart::$cart_contents as $item => $values) :

		// data
		$item_id = $values['product_id'];
		$qty = 1; // force it
		$data = $values['data']->meta;
		$product_data = maybe_unserialize( $data['product_data'][0] );
		$version = $product_data['version'];

		// prices
		$sale_price = $data['sale_price'][0];
		$regular_price = $data['regular_price'][0];
		$price = ($sale_price && $sale_price != '' && $sale_price != 0 && $sale_price != '0') ? $sale_price : $regular_price;

		// upgrade
		if ( !empty( $product_data['is_upgrade'] ) ) {
			$is_upgrade = $product_data['is_upgrade'];
			$upgrade_from_id = $product_data['upgrade_from'];
			$upgrade_to_id = $product_data['upgrade_to'];
			$is_upgrade = ( $is_upgrade && !empty( $upgrade_from_id ) && !empty( $upgrade_to_id ) );

			global $jigoshopsoftware;
			$upgrade_prefill = $jigoshopsoftware->get_upgrade_prefill_from_cookie( $item_id );
		} else {
			$is_upgrade = false;
		}

		// prices format in US dollars
		setlocale( LC_MONETARY, 'en_US' );
		@$echo_price = money_format( '%(#10n', (float) $price );

		// prefill
		if ( ! empty( $_POST['jgs_email'] ) ) {
			$prefill_email = sanitize_email( $_POST['jgs_email'] );
		} elseif ( ! empty( $upgrade_prefill['email_address'] ) ) {
			$prefill_email = sanitize_email( $upgrade_prefill['email_address'] );
		} else {
			$prefill_email = '';
		}

		if ( ! empty( $_POST['up_key'] ) ) {
			$prefill_license_key = sanitize_key( $_POST['up_key'] );
		} elseif ( ! empty( $upgrade_prefill['license_key'] ) ) {
			$prefill_license_key = sanitize_key( $upgrade_prefill['license_key'] );
		} else {
			$prefill_license_key = '';
		}



	?>
			<div class="jgs_page" id="jgs_checkout">
				<form id="jgs_checkout_form" action="<?php echo admin_url( 'admin-ajax.php' ) ?>" method="post">
					<div class="form-row">
						<h2><?php _e( 'Purchasing', 'jigoshop-software' ) ?>: <?php echo get_the_title( $item_id ) ?></h2>
						<p>
							<strong><?php _e( 'Price', 'jigoshop-software' ) ?></strong>: <?php echo $echo_price ?><br>
							<strong><?php _e( 'Quantity', 'jigoshop-software' ) ?></strong>: <?php echo $qty ?><br>
							<strong><?php _e( 'Total', 'jigoshop-software' ) ?></strong>: <?php echo $echo_price ?>
						</p>

						<p id="jgs_validation"<?php if (isset($_GET['no-js'])) echo ' class="not-hidden"' ?>><?php if (isset($_GET['no-js'])) _e( 'You need javascript in order to be able to checkout. Please enable javascript and try again.', 'jigoshop-software' ) ?></p>

						<?php if ( $is_upgrade ) : ?>
							<p><?php printf( __( 'This upgrade to %s requires a %s license. Please enter your %s license email and key below, then click purchase now below to complete the purchase with credit card or PayPal.', 'jigoshop-software' ), get_the_title( $upgrade_to_id ), get_the_title( $upgrade_from_id ), get_the_title( $upgrade_from_id ) ); ?></p>
						<?php else : ?>
							<p><?php _e( 'Please enter your email below, then click <em>purchase now</em> below to complete the purchase with credit card or Paypal.', 'jigoshop-software' ) ?></p>
    						<p style="color: #EE0000; font-weight: bold;"><?php _e( 'Note: Your license key will be sent to the email address below, so check your email address carefully before purchasing.', 'jigoshop-software' ) ?></p>
						<?php endif; ?>

					</div>

					<div class="form-row">
						<label for="jgs_email"><?php _e( 'Your email address', 'jigoshop-software' ) ?>:</label> <input type="text" id="jgs_email" name="jgs_email" value="<?php echo esc_attr( $prefill_email ); ?>">
					</div>

					<?php if ( $is_upgrade ) : ?>
						<div class="form-row">
							<label for="up_key"><?php _e( 'Your license Key', 'jigoshop-software' ) ?>:</label> <input type="text" id="up_key" name="up_key" value="<?php echo esc_attr( $prefill_license_key ); ?>">
						</div>
					<?php endif; ?>

					<div class="form-row">
						<input type="hidden" name="action" value="jgs_checkout">
						<?php wp_nonce_field( 'jgs_checkout', 'jgs_checkout_nonce' ); ?>
						<input type="hidden" name="item_id" value="<?php echo $item_id ?>">
						<noscript><input type="hidden" name="no_js" value="true"></noscript>
						<div class="jgs_loader"><input type="submit" class="button-alt" name="jgs_purchase" id="jgs_purchase" value="Purchase Now"> <a class="button-alt" href="<?php echo site_url('checkout') ?>?empty=true">Cancel Order</a></div>
					</div>
				</form>
			</div>

			<script type="text/javascript">
			jQuery(document).ready(function($){
				$('#jgs_checkout_form').submit(function(e){
					e.preventDefault();
					$('#jgs_validation').fadeIn();
					var load = $('#jgs_checkout .jgs_loader');
					load.addClass('loading');
					var args = {};
					var inputs = $(this).serializeArray();
					$.each(inputs,function(i,input) { args[input['name']]=input['value']; });
					$.post("<?php echo admin_url( 'admin-ajax.php' ) ?>", args, function(response){
						load.removeClass('loading');
						if (response.success) {
							$('#jgs_validation').fadeOut();
							// redirect for payment
							if (response.result.redirect) {
								window.location = response.result.redirect;
							} else {
								$('#jgs_validation').html("<?php _e( 'An error has occurred, please refresh the page and try again.', 'jigoshop-software' ) ?>").fadeIn();
							}
						} else {
							if (response.success === false) {
								$('#jgs_validation').html(response.message).fadeIn();
							} else {
								$('#jgs_validation').html("<?php _e( 'An error has occurred, please try again.', 'jigoshop-software' ) ?>").fadeIn();
							}
						}
					});
					return false; // prevent submit (redundant)
				});
			});
			</script>
<?php
	endforeach;

else:
?>
	<div id="jgs_checkout">
		<div class="form-row">
			<h2><?php _x( 'You haven\'t bought anything yet. Go back to the', '...shop', 'jigoshop-software' ) ?> <a href="<?php echo site_url( '/shop' ) ?>"><?php _e( 'Shop', 'jigoshop-software' ) ?></a></h2>
		</div>
	</div>

<?php
endif;