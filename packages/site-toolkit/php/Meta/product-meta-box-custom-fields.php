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
			<?php _e('Version:', 'blockera-site-toolkit'); ?>
		</label>
		<input type="text" id="product_version" name="product_version"
			value="<?php echo esc_attr($product_version); ?>" />
	</p>
	<p class="form-field">
		<label for="product_color">
			<?php _e('Color:', 'blockera-site-toolkit'); ?>
		</label>
		<input type="color" id="product_color" name="product_color"
			value="<?php echo esc_attr($product_color); ?>" />
	</p>
	<p class="form-field">
		<label for="product_is_activated_free_download">
			<?php _e('Activated Free Download?', 'blockera-site-toolkit'); ?>
		</label>
		<input type="checkbox" id="product_is_activated_free_download" name="product_is_activated_free_download" <?php echo $is_activated_free_download ? 'checked="checked"' : ''; ?> />
	</p>
	<p class="form-field">
		<label for="product_free_slug">
			<?php _e('Free Slug:', 'blockera-site-toolkit'); ?>
		</label>
		<input type="text" id="product_free_slug" name="product_free_slug" value="<?php echo esc_attr($product_free_slug); ?>" />
	</p>
	<div class="form-field downloadable_files">
		<label><?php esc_html_e('Downloadable files', 'blockera-site-toolkit'); ?></label>
		<table class="widefat">
			<thead>
				<tr>
					<th class="sort">&nbsp;</th>
					<th><?php esc_html_e('Name', 'blockera-site-toolkit'); ?></th>
					<th colspan="2"><?php esc_html_e('File URL', 'blockera-site-toolkit'); ?></th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			<tbody>
				<?php
				if ($product_downloadable_files) {
					foreach ($product_downloadable_files as $name => $fileData) {
						$fileUrl = $fileData['file'];
						include 'html-product-download.php';
					}
				}
				?>
			</tbody>
			<tfoot>
				<tr>
					<th colspan="5">
						<a href="#" class="button insert" data-row="<?php
							$file = array(
								'file' => '',
								'name' => ''
							);
							$disabled_download = false;
							ob_start();
							include 'html-product-download.php';
							echo esc_attr(ob_get_clean());
						?>"><?php esc_html_e('Add File', 'blockera-site-toolkit'); ?></a>
					</th>
				</tr>
			</tfoot>
		</table>
	</div>

	<script>
		jQuery(document).ready(function($) {
			// File uploads
			$('.downloadable_files').on('click', '.upload_file_button', function(e) {
				e.preventDefault();
				
				var $button = $(this);
				var file_frame = wp.media({
					title: $button.data('choose'),
					button: {
						text: $button.data('update')
					},
					multiple: false
				});

				file_frame.on('select', function() {
					var attachment = file_frame.state().get('selection').first().toJSON();
					$button.closest('tr').find('input.input_text[name="_blockera_file_urls[]"]').val(attachment.url);
				});

				file_frame.open();
			});

			// Add row
			$('.downloadable_files').on('click', 'a.insert', function(e) {
				e.preventDefault();
				
				var $tbody = $(this).closest('.downloadable_files').find('tbody');
				var $row = $($(this).data('row'));
				$tbody.append($row);
				
				return false;
			});

			// Delete row
			$('.downloadable_files').on('click', 'a.delete', function(e) {
				e.preventDefault();
				$(this).closest('tr').remove();
				return false;
			});

			// Reorder rows
			$('.downloadable_files tbody').sortable({
				items: 'tr',
				cursor: 'move',
				axis: 'y',
				handle: '.sort',
				scrollSensitivity: 40
			});
		});
	</script>
</div>