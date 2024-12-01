<?php

use Blockera\Utils\View;

$root_path = trailingslashit(dirname(__DIR__));

View::load('license-manager.form-start', compact('url', 'whoIs'), ['root-path' => $root_path]);

foreach ($subscriptions as $subscription_post): ?>
    <?php
    $subscription_id = is_numeric($subscription_post) ? $subscription_post : $subscription_post->ID;
    $subscription = ywsbs_get_subscription($subscription_id);
    $subscription_name = sprintf('%s - %s', $subscription->get_number(), $subscription->get('product_name'));
    $subscription_status = $subscription_statuses[$subscription->get_status()];
    $next_payment_due_date = (! in_array($subscription_status, array('paused', 'cancelled'), true) && $subscription->get('payment_due_date')) ? date_i18n(wc_date_format(), $subscription->get('payment_due_date')) : '<span class="empty-date">-</span>';
    $start_date = ($subscription->get('start_date')) ? date_i18n(wc_date_format(), $subscription->get('start_date')) : '<div class="empty-date">-</div>';
    $end_date = ($subscription->get('end_date')) ? date_i18n(wc_date_format(), $subscription->get('end_date')) : false;
    $end_date = ! $end_date && ($subscription->get('expired_date')) ? date_i18n(wc_date_format(), $subscription->get('expired_date')) : '<div class="empty-date">-</div>';
    $tooltip = empty($subscription->get('post_content')) ? $subscription->get('post_content') : $subscription->get('post_excerpt');
    ?>
    <div class="blockera-consent-field">
        <label for="<?php echo esc_attr($subscription_id) ?>">
            <span>
                <input name="subscription_id" id="<?php echo esc_attr($subscription_id) ?>" type="radio" value="<?php echo esc_attr($subscription_id) ?>">
                <span><?php echo $subscription_name ?></span>
            </span>
            <span title="<?php echo esc_attr($tooltip); ?>">
                <?php View::load('icons.circle-info', [], ['root-path' => $root_path]); ?>
            </span>
        </label>
    </div>
<?php endforeach;

View::load('license-manager.form-end', compact('root_path'), ['root-path' => $root_path]);
