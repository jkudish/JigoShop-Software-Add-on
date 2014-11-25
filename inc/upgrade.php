<?php
/**
 * This file renders the upgrades page
 *
 * @since 2.6
 * @author Joachim Kudish <info@jkudish.com>
 */
?>

<h2 id="jgs_validation"></h2>
<div id="jgs_possible_products"></div>
<a id="jgs_another_upgrade" class="button-alt" href="#">Upgrade a different license key</a>

<div class="clear"></div>

<div class="jgs_page" id="jgs_upgrade">
	<form id="jgs_upgrade_form">
		<div class="form-row">
			<h2><?php _e( 'Upgrade', 'jigoshop-software' ) ?></h2>
			<p><?php _e( 'Enter the Email address and key of your license that you want to upgrade.', 'jigoshop-software' ) ?></p>
			<p><?php _e( 'If your email address has changed, please', 'jigoshop-software' ) ?> <a href="<?php echo site_url( '/contact' ) ?>"><?php _e( 'contact us', 'jigoshop-software' ) ?></a>.</p>
		</div>

		<div class="form-row">
			<p><label for="jgs_email"><?php _e( 'Your email address', 'jigoshop-software' ) ?>:</label> <input type="text" id="jgs_email" name="jgs_email"></p>
			<p><label for="jgs_license_key"><?php _e( 'Your license key', 'jigoshop-software' ) ?>:</label> <input type="text" id="jgs_license_key" name="jgs_license_key"></p>
		</div>

		<div class="form-row done">
			<input type="hidden" name="action" value="jgs_upgrade">
			<?php wp_nonce_field( 'jgs_upgrade', 'jgs_upgrade_nonce' ); ?>
			<div class="jgs_loader"><input type="submit" class="button-alt" name="jgs_upgrade_btn" id="jgs_upgrade_btn" value="Find Upgrades"></div>
		</div>
	</form>
</div>

<script type="text/javascript">
	jQuery(document).ready(function($){
		var $form = $('#jgs_upgrade_form' ),
			$validation = $( '#jgs_validation' ),
			$possible_products = $( '#jgs_possible_products' ),
			$another_upgrade = $( '#jgs_another_upgrade' );

		jgs_reset_form = function() {
			$validation.hide().html( '' ).removeClass( 'success' );
			$possible_products.hide().html( '' );
			$another_upgrade.hide();
			$form.show();
		};

		$form.on( 'submit', function(e){
			e.preventDefault();
			var $this = $(this),
				$load = $this.find( '.jgs_loader' );

			jgs_reset_form();
			$load.addClass('loading');

			$.post("<?php echo admin_url( 'admin-ajax.php' ) ?>", $(this).serialize(), function(response){
				$load.removeClass('loading');

				if (response.success) {
					$this.slideUp('slow', function(){
						$validation.html( response.data.success_message ).addClass('success').show('slow');
						$possible_products.html( response.data.possible_upgrade_products ).show();
						$another_upgrade.show();
					});
				} else {
					if (response.success === false) {
						$validation.html(response.data).show();
					} else {
						$validation.html('An error has occurred, please try again.').show();
					}
				}
			});

			return false; // prevent submit (redundant)
		});

		$another_upgrade.on( 'click', function(e){
			e.preventDefault();
			jgs_reset_form();
			$( '#jgs_email' ).val( '' );
			$( '#jgs_license_key' ).val( '' );
		});
	});
</script>