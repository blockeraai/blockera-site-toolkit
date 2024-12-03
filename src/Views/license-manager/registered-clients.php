<div class="blockera-consent-container">
    <h1><?php _e('Activated your clients', 'blockera-site-toolkit'); ?></h1>

    <div class="blockera-user-clients">
        <?php foreach ($clients as $client) : ?>
            <div class="blockera-client-wrapper">
                <h3>
                    <code contenteditable="true"><?php echo $client['domain']; ?></code>
                    <button class="blockera-client-delete-button">
                        <?php _e('Delete', 'blockera-site-toolkit'); ?>
                    </button>
                </h3>
                <h5>
                    <strong><?php _e('Subscription:', 'blockera-site-toolkit'); ?></strong>
                    <code>
                        <?php
                        $subscription = ywsbs_get_subscription($client['subscription_id']);
                        $status = $subscription_statuses[$subscription->get_status()];
                        echo sprintf('%s - %s', $subscription->get_number(), $subscription->get('product_name'));
                        ?>
                    </code>
                    <span class="blockera-client-subscription-<?php echo $status ?>"><?php echo $status ?></span>
                </h5>
                <h5>
                    <strong><?php _e('Client ID:', 'blockera-site-toolkit'); ?></strong>
                    <code><?php echo $client['client_id']; ?></code>
                </h5>
                <h5>
                    <strong><?php _e('Client Secret:', 'blockera-site-toolkit'); ?></strong>
                    <code><?php echo $client['client_secret']; ?></code>
                </h5>
            </div>
        <?php endforeach; ?>
    </div>
</div>