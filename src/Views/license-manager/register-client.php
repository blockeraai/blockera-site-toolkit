<?php

use Blockera\Utils\View;
?>
<div class="blockera-consent-container">
    <h1><?php _e('Add Client', 'blockera-site-toolkit'); ?></h1>

    <form action="<?php echo home_url('my-account/license-manager/add'); ?>" method="post" class="blockera-consent-form">
        <div class="blockera-consent-field">
            <label for="subscription_id">
                <span>
                    <span><?php _e('Subscription:', 'blockera-site-toolkit') ?></span>
                    <select name="subscription_id" id="subscription_id">
                        <?php foreach ($subscriptions as $subscription_post):
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
                            <option value="<?php echo $subscription_id; ?>"><?php echo $subscription_name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </span>
            </label>
        </div>
        <div class="blockera-consent-field">
            <label for="domain">
                <span>
                    <span><?php _e('Client URL:', 'blockera-site-toolkit') ?></span>
                    <input name="domain" id="domain" type="text">
                </span>
                <span title="Tooltip Content">
                    <?php View::load('icons.circle-info', [], ['root-path' => $root_path]); ?>
                </span>
            </label>
        </div>

        <?php
        View::load('license-manager.shared-inputs-consent-form', [
            'withoutActions' => true,
        ], ['root-path' => $root_path]);
        ?>

        <button class="button button-primary" type="submit" name="action" value="add"><?php _e('Add', 'blockera-site-toolkit') ?></button>
    </form>
</div>