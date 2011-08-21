<?php

/**
	* This file renders the checkout page
	* @since 1.0
	* @author Joachim Kudish <info@jkudish.com>
	*/
	
if (!empty($_GET['empty']) && $_GET['empty'] == true) {
	jigoshop_cart::empty_cart();
	wp_redirect(site_url('/checkout')); exit;
}

if ( sizeof(jigoshop_cart::$cart_contents) > 0 ) : 
	foreach (jigoshop_cart::$cart_contents as $item_id => $values) :
		
		$qty = 1; // force it
		$data = $values['data']->data;
		$sale_price = $data['sale_price'];
		$regular_price = $data['regular_price'];
		$price = ($sale_price && $sale_price != '') ? $sale_price : $regular_price;
		$up_name = $data['upgradable_product'];
		$up_price = $data['up_price'];
		$version = $data['version'];
		
	?>				
			<div class="jgs_page" id="jgs_checkout">
				<form id="jgs_checkout_form" action="<?php echo admin_url('admin-ajax.php') ?>" method="post">
					<div class="form-row">
						<h2>Purchasing: <?php echo get_the_title($item_id) ?></h2>		
						<p>
							<strong>Price</strong>: $<?php echo $price ?><br>
							<strong>Quantity</strong>: <?php echo $qty ?><br>
							<strong>Total</strong>: $<?php echo $qty*$price ?>
						</p>	
						<p>Please enter your email below, then click <em>purchase now</em> below to complete the purchase with Paypal.</p>					
						<p id="jgs_validation"<?php if (isset($_GET['no-js'])) echo ' class="not-hidden"' ?>><?php if (isset($_GET['no-js'])) echo 'You need javascript in order to be able to checkout. Please enable javascript and try again.'?></p>
					</div>	
					<div class="form-row">
						<p><label for="jgs_email">Your email address:</label> <input type="text" id="jgs_email" name="jgs_email"></p>
					</div>
				
					<?php if ($up_name && $up_price && $up_name != '' && $up_price != '') : ?>				
						<div class="form-row">
							<h2>Upgrade from <?php echo $up_name ?>:</h2>
							<p>If you have a valid <?php echo $up_name ?> license key, you can upgrade to the current version (<?php echo $version ?>). Please enter your old license key below and click upgrade now. The order information below will update once you complete this step.</p>
							<p><strong>Upgrade Price:</strong> $<?php echo $up_price ?></p>
						</div>
						<div class="form-row">						
							<label for="up_key"><?php echo $up_name ?> Key:</label> <input type="text" id="up_key" name="up_key"><br><br>
						</div>
					<?php endif; ?>	
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