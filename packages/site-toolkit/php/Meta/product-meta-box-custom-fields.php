<div class="options_group woocommerce_options_panel">
	<p class="form-field">
		<label for="product_id">
			<?php _e('Product ID:', 'blockera-site-toolkit'); ?>
		</label>
		<input type="text" id="product_id" name="product_id"
			value="<?php echo esc_attr($product_id); ?>" />
	</p>
	<p class="form-field">
		<label for="product_version">
			<?php _e('Product Version:', 'blockera-site-toolkit'); ?>
		</label>
		<input type="text" id="product_version" name="product_version"
			value="<?php echo esc_attr($product_version); ?>" />
	</p>
	<p class="form-field">
		<label for="product_color">
			<?php _e('Product Color:', 'blockera-site-toolkit'); ?>
		</label>
		<input type="color" id="product_color" name="product_color"
			value="<?php echo esc_attr($product_color); ?>" />
	</p>
	<p class="form-field">
		<label for="product_download_file">
			<?php _e('Product Downloadable File:', 'blockera-site-toolkit'); ?>
		</label>
		<input type="text" class="short" id="product_download_file" name="product_download_file" value="<?php echo esc_attr($product_download_file); ?>" />
		<button type="button" class="button upload_file_button" data-choose="<?php esc_attr_e('Choose file', 'blockera-site-toolkit'); ?>" data-update="<?php esc_attr_e('Insert file URL', 'blockera-site-toolkit'); ?>">
			<?php echo esc_html__('Choose file', 'blockera-site-toolkit'); ?>
		</button>
		<script>
			jQuery(document).ready(function($) {
				$('.upload_file_button').on('click', function(e) {
					e.preventDefault();
					var button = $(this);
					var fileFrame = wp.media({
						title: button.data('choose'),
						button: {
							text: button.data('update')
						},
						multiple: false
					});

					fileFrame.on('select', function() {
						var attachment = fileFrame.state().get('selection').first().toJSON();
						$('#product_download_file').val(attachment.url);
					});

					fileFrame.open();
				});
			});
		</script>
	</p>
</div>