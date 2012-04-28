<?php

/**
	* This file renders the checkout page
	*
	* @since 1.0
	* @author Joachim Kudish <info@jkudish.com>
	*/

if ( !empty( $_GET['empty'] ) && $_GET['empty'] == true ) {
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
		$up_name = $product_data['upgradable_product'];
		$version = $product_data['version'];

		// prices
		$sale_price = $data['sale_price'][0];
		$regular_price = $data['regular_price'][0];
		$price = ($sale_price && $sale_price != '' && $sale_price != 0 && $sale_price != '0') ? $sale_price : $regular_price;
		$up_price = $product_data['up_price'];

		// prices format in US dollars
		setlocale( LC_MONETARY, 'en_US' );
		@$echo_price = money_format( '%(#10n', (float) $price );
		@$echo_up_price = money_format( '%(#10n', (float) $up_price );


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
						<p><?php _e( 'Please enter your email below, then click <em>purchase now</em> below to complete the purchase with Paypal.', 'jigoshop-software' ) ?></p>
						<p id="jgs_validation"<?php if (isset($_GET['no-js'])) echo ' class="not-hidden"' ?>><?php if (isset($_GET['no-js'])) _e( 'You need javascript in order to be able to checkout. Please enable javascript and try again.', 'jigoshop-software' ) ?></p>
					</div>
					<div class="form-row">
						<p><label for="jgs_email"><?php _e( 'Your email address', 'jigoshop-software' ) ?>:</label> <input type="text" id="jgs_email" name="jgs_email"></p>
					</div>

					<?php if ($up_name && $up_price && $up_name != '' && $up_price != '') : ?>
						<div class="form-row">
							<h2><?php _e( 'Upgrade from', 'jigoshop-software' ) ?> <?php echo $up_name ?>:</h2>
							<p><?php sprintf( __( 'If you have a valid %s license key, you can upgrade to the current version (%s). Please enter your old license key below and click upgrade now. The order information below will update once you complete this step.', 'jigoshop-software' ), $up_name, $version ) ?></p>
							<p><strong><?php _e( 'Upgrade Price', 'jigoshop-software' ) ?>:</strong> <?php echo $echo_up_price ?></p>
						</div>
						<div class="form-row">
							<label for="up_key"><?php echo $up_name ?> <?php _e( 'Key', 'jigoshop-software' ) ?>:</label> <input type="text" id="up_key" name="up_key"><br><br>
						</div>
					<?php endif; ?>
					<div class="form-row">
						<input type="hidden" name="action" value="jgs_checkout">
						<?php wp_nonce_field( 'jgs_checkout', 'jgs_checkout_nonce' ); ?>
						<input type="hidden" name="item_id" value="<?php echo $item_id ?>">
						<noscript><input type="hidden" name="no_js" value="true"></noscript>
						<div class="jgs_loader"><input type="submit" class="button-alt" name="jgs_purchase" id="jgs_purchase" value="Purchase Now"> <a class="button-alt" href="<? site_url('checkout') ?>?empty=true">Cancel Order</a></div>
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