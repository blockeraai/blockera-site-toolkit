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
</div>