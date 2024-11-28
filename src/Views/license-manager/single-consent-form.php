<?php

use Blockera\Utils\View;

$root_path = trailingslashit(dirname(__DIR__));

$subscription = ywsbs_get_subscription($subscription_id);
$tooltip = empty($subscription->get('post_content')) ? $subscription->get('post_content') : $subscription->get('post_excerpt');
$subscription_name = $subscription->get('product_name');

View::load('license-manager.form-start', compact('url', 'whoIs'), ['root-path' => $root_path]); ?>
<div class="blockera-consent-field active">
    <label for="<?php echo esc_attr($subscription_id) ?>">
        <span><?php echo $subscription_name ?></span>
        <span title="<?php echo esc_attr($tooltip); ?>">
            <?php View::load('icons.circle-info', [], ['root-path' => $root_path]); ?>
        </span>
        <input name="subscription" id="<?php echo esc_attr($subscription_id) ?>" type="hidden" value="<?php echo esc_attr($subscription_id) ?>">
    </label>
</div>
<?php

View::load('license-manager.form-end', compact('root_path'), ['root-path' => $root_path]);
