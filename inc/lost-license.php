<?php

/**
	* This file renders the lost license page
	* @since 1.0
	* @author Joachim Kudish <info@jkudish.com>
	*/
?>	
<div id="jgs_lost_license">
	<form id="jjgs_lost_licenseform" action="<?php echo admin_url('admin-ajax.php') ?>" method="post">
		<div class="form-row">
			<h2>Lost License</h2>		
			<p>Please tell us the email address used during the purchase. Your licnse along with the order receipt will be sent by email</p>
			<p>If your email address has changed, please <a href="<?php echo site_url('/contact') ?>">contact us</a></p>
			<p id="jgs_validation"<?php if (isset($_GET['no-js'])) echo ' class="not-hidden"' ?>><?php if (isset($_GET['no-js'])) echo 'You need javascript in order to be able to checkout. Please enable javascript and try again.'?></p>
		</div>	
		<div class="form-row">
			<p><label for="jgs_email">Your email address:</label> <input type="text" id="jgs_email" name="jgs_email"></p>
		</div>
		<div class="form-row">
			<input type="hidden" name="action" value="jgs_checkout">
			<?php wp_nonce_field('jgs_checkout', 'jgs_checkout_nonce'); ?>
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
			$.post("<?php echo admin_url('admin-ajax.php') ?>", args, function(response){
				load.removeClass('loading');
				if (response.success) { 
					$('#jgs_validation').fadeOut();
					// redirect for payment
			if (response.result.redirect) {
			window.location = response.result.redirect;
			} else {
			$('#jgs_validation').html('An error has occurred, please refresh the page and try again.').fadeIn();
			}
			} else {
			if (response.success === false) {
			$('#jgs_validation').html(response.message).fadeIn();
			} else {
			$('#jgs_validation').html('An error has occurred, please try again.').fadeIn();
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
<h2>You haven't bought anything yet. Go back to the <a href="<?php echo site_url('/shop') ?>">Shop</a></h2>	
</div>				
</div>	

<?php
endif;