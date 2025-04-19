<tr>
	<td class="sort"></td>
	<td class="file_name">
		<input type="text" class="input_text" placeholder="<?php esc_attr_e( 'File name', 'blockera-site-toolkit' ); ?>" name="_blockera_file_names[]" value="<?php echo esc_attr( $name ); ?>" />
	</td>
	<td class="file_version">
		<input type="text" class="input_text" placeholder="<?php esc_attr_e( 'Version', 'blockera-site-toolkit' ); ?>" name="_blockera_versions[]" value="<?php echo esc_attr( $version ); ?>" />
	</td>
	<td class="file_url">
		<input type="text" class="input_text" placeholder="<?php esc_attr_e( 'http://', 'blockera-site-toolkit' ); ?>" name="_blockera_file_urls[]" value="<?php echo esc_attr( $fileUrl ); ?>" />
	</td>
	<td class="file_url_choose" width="1%"><a href="#" class="button upload_file_button" data-choose="<?php esc_attr_e( 'Choose file', 'blockera-site-toolkit' ); ?>" data-update="<?php esc_attr_e( 'Insert file URL', 'blockera-site-toolkit' ); ?>"><?php echo esc_html__( 'Choose file', 'blockera-site-toolkit' ); ?></a></td>
	<td width="1%"><a href="#" class="delete"><?php esc_html_e( 'Delete', 'blockera-site-toolkit' ); ?></a></td>
</tr>