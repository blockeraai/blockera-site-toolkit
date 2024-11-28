<?php

use Blockera\Utils\View;
?>

</div>
<div class="blockera-consent-field blockera-consent-description">
    <?php _e('By clicking allow, you allow this site to use your information in accordance with their terms of service and privacy policies. You can remove this or any other site connected to your subscription in <a href="/license-manager">License Manager</a>.', 'blockera-site-toolkit'); ?>
</div>
<div class="blockera-consent-field">
    <?php View::load('license-manager.shared-inputs-consent-form', [], ['root-path' => $root_path]); ?>
</div>
</form>
</div>