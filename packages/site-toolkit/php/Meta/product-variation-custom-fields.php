<div class="variation-custom-fields" style="display: none;">
	<div class="max-domains"></div>
</div>
<script type="text/javascript">
	jQuery(document).ready(function($) {
		$('form.variations_form').on('show_variation', function(event, variation) {
			if (variation.max_domains) {
				$('.variation-custom-fields .max-domains')
					.html('Maximum Domains: ' + variation.max_domains);
			}
			$('.variation-custom-fields').show();
		});
		$('form.variations_form').on('hide_variation', function() {
			$('.variation-custom-fields').hide();
		});
	});
</script>