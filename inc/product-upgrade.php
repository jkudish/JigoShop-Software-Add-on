<?php
	if (sizeof(jigoshop_cart::$cart_contents)>0) : 
		foreach (jigoshop_cart::$cart_contents as $item_id => $values) :
			$product_data = get_post_meta($item_id, 'product_data', true);
			$up_name = $product_data['upgradable_product'];
			$up_price = $product_data['up_price'];
			$version = $product_data['version'];
			if ($up_name && $up_price && $up_name != '' && $up_price != '') { ?>
				<div id="jgs_upgrade">
					<div class="form-row">
						<h2>Upgrade from <?php echo $up_name ?></h2>
						<p>If you have a valid <?php echo $up_name ?> license key, you can upgrade to the current version (<?php echo $version ?>). Please enter your old license key below and click upgrade now. The order information below will update once you complete this step.</p>
						<p><strong>Upgrade Price:</strong> $<?php echo $up_price ?></p>
					</div>
					<form id="jgs_upgrade_product" method="post">
						<div class="form-row">
							<p id="jgs_validation"></p>
							<label for="up_key"><?php echo $up_name ?> Key:</label> <input type="text" id="up_key" name="up_key"><br><br>
							<input type="hidden" name="action" value="jgs_update_product">
							<input type="hidden" name="item_id" value="<?php echo $item_id ?>">
						</div>
						<div class="form-row"><a href="#" id="jgs_no_thanks" class="button-alt">No Thanks</a> <div class="jgs_loader"><input type="submit" class="button-alt" name="upgrade_now" id="upgrade_now" value="Upgrade Now"></div></div>
					</form>
				</div>	
				<script type="text/javascript">
					jQuery(document).ready(function($){
							$('#jgs_no_thanks').click(function(e){
								e.preventDefault();
								$('#jgs_upgrade').slideUp('slow', function(){
									$('#jgs_upgrade').remove();
								});
							});
							$('#jgs_upgrade_product').submit(function(e){
								e.preventDefault();
								$('#jgs_validation').fadeIn();
								var load = $('#jgs_upgrade_product .jgs_loader');
								load.addClass('loading');
								var args = {};
								var inputs = $(this).serializeArray();
								$.each(inputs,function(i,input) { args[input['name']]=input['value']; });
								$.post("<?php echo admin_url('admin-ajax.php') ?>", args, function(response){
									load.removeClass('loading');
									if (response.success) { 
										$('#validation').fadeOut();
										$('#jgs_upgrade').slideUp('normal',function(){
											$('<h2 id="jgs_upgrade_applied">'+response.message+'</h2>').insertBefore('form.checkout').fadeIn('slow');
											var name = $('.shop_table tbody tr td:eq(0)');
											var old_name = name.html()
											name.html(old_name+' Upgrade');
											var price = $('.shop_table tbody tr td:eq(2), .shop_table tfoot tr:eq(0) td:eq(1), .shop_table tfoot tr:eq(1) td:eq(1)');
											price.html(response.price);
											$('<input type="hidden" name="product_update" value="'+response.key+'">').('#place_order')
										});						
									}
									else {
										if (response.success === false) {
											$('#jgs_validation').html(response.message).fadeIn();
										}
										else {
											$('#jgs_validation').html('An error has occurred, please try again.').fadeIn();
										}
									}	
								});
								return false; // prevent submit (redundant)
							});
						});	
				</script>  				
		<?php	}
		endforeach; 
	endif;	

	