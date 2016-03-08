<?php
/**
	* This file renders the activation notification form page
	*
	* @since 2.7
	* @author Anton Iancu <anton.iancu@gmail.com>
	*/
?>

<div class="jgs_page" id="jgs_activation_subscribe">
	<form id="jgs_activation_subscribe" action="<?php echo admin_url( 'admin-ajax.php' ) ?>" method="post">
		<div class="form-row">
			<p><?php _e( 'Please enter your order email and license, or your order ID to enable receiving a notification email each time you activate Sparkbooth.', 'jigoshop-software' ) ?></p>
			<p><?php _e( 'If your email address has changed, please', 'jigoshop-software' ) ?> <a href="<?php echo site_url( '/contact' ) ?>"><?php _e( 'contact us', 'jigoshop-software' ) ?></a>.</p>
			<p id="jgs_validation"<?php if ( isset( $_GET['no-js'] ) ) echo ' class="not-hidden"' ?>><?php if ( isset( $_GET['no-js'] ) ) _e( 'You need javascript in order to be able to checkout. Please enable javascript and try again.', 'jigoshop-software' ) ?></p>
		</div>
		<div class="form-row done">
			<p><label for="jgs_email"><?php _e( 'Your email address', 'jigoshop-software' ) ?>:</label> <input type="text" id="jgs_email" name="jgs_email"></p>
			<p><label for="jgs_license"><?php _e( 'Your Sparkbooth license', 'jigoshop-software' ) ?>:</label> <input type="text" id="jgs_license" name="jgs_license"></p>
		</div>
		<div class="form-row done">
			<p>
				<strong>OR</strong>
			</p>
		</div>
		<div class="form-row done">
			<p><label for="jgs_order_id"><?php _e( 'Your order ID', 'jigoshop-software' ) ?>:</label> <input type="text" id="jgs_order_id" name="jgs_order_id"></p>
		</div>
		<div class="form-row done">
			<input type="hidden" name="action" value="jgs_lost_license">
			<?php //wp_nonce_field( 'jgs_lost_license', 'jgs_lost_license_nonce' ); ?>
			<noscript><input type="hidden" name="no_js" value="true"></noscript>
			<div class="jgs_loader"><input type="submit" class="button-alt" name="jgs_lost_license_btn" id="jgs_lost_license_btn" value="Enable Activation Notifications"></div>
		</div>
	</form>
</div>

<script type="text/javascript">
	jQuery(document).ready(function($){
		$('#jgs_lost_license_form').submit(function(e){
			e.preventDefault();
			var load = $('#jgs_lost_license .jgs_loader');
			if (!load.hasClass('loading')) {
				$('#jgs_validation').fadeIn();
				$('#jgs_validation').hide();
				load.addClass('loading');
				var args = {};
				var inputs = $(this).serializeArray();
				$.each(inputs,function(i,input) { args[input['name']]=input['value']; });
				$.post("<?php echo admin_url( 'admin-ajax.php' ) ?>", args, function(response){
					load.removeClass('loading');
					if (response.success) {
						$('.done').slideUp('slow', function(){
							$('#jgs_validation').html(response.message).addClass('success').slideDown('slow');
						});
					} else {
						if (response.success === false) {
							$('#jgs_validation').html(response.message).fadeIn();
						} else {
							$('#jgs_validation').html('An error has occurred, please try again.').fadeIn();
						}
					}
				});
			}
			return false; // prevent submit (redundant)
		});
	});
</script>
