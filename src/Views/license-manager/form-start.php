<div class="blockera-consent-container">
    <h1 class="blockera-consent-welcome"><?php _e(sprintf('Welcome <strong>%s</strong>', wp_get_current_user()->display_name), 'blockera-site-toolkit'); ?></h1>
    <p class="blockera-consent-link">
        <a href="<?php echo esc_attr($url) ?>"><?php echo $whoIs; ?></a>
        <span>
            <?php _e(' wants to connect to your account, allow it to do this?', 'blockera-site-toolkit'); ?>
        </span>
    </p>
    <form action="<?php echo rest_url('auth/v1/license-manager/create'); ?>" method="post" class="blockera-consent-form">
        <div class="blockera-consent-fields">