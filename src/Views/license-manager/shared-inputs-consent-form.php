<input type="hidden" name="scopes" value="update">
<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('wp_rest'); ?>">
<?php if (!isset($withoutClientId) || !$withoutClientId): ?>
    <input type="hidden" name="client_id" value="<?php echo $_GET['client_id'] ?? '' ?>">
<?php endif; ?>
<input type="hidden" name="state" value="<?php echo $_GET['state'] ?? '' ?>">
<input type="hidden" name="response_type" value="<?php echo $_GET['response_type'] ?? '' ?>">
<input type="hidden" name="approval_prompt" value="<?php echo $_GET['approval_prompt'] ?? '' ?>">
<input type="hidden" name="redirect_uri" value="<?php echo $_GET['redirect_uri'] ?? '' ?>">
<?php if (!isset($withoutActions)): ?>
    <button class="button button-primary" type="submit" name="action" value="allow"><?php _e('Allow', 'blockera-site-toolkit') ?></button>
    <button class="button button-secondary" type="submit" name="action" value="cancel"><?php _e('Cancel', 'blockera-site-toolkit'); ?></button>
<?php endif; ?>