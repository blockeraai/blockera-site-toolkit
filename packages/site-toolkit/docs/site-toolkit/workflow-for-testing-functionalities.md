## Workflow for testing functionalities

### Testing the license manager

1. Create a new product in the Woocommerce store as a subscription product [[YITH WooCommerce Subscription docs](https://docs.yithemes.com/yith-woocommerce-subscription/)].
2. Back to the Blockera product website, go to the Activate Pro License page [[For example](https://blockera.test/wp-admin/admin.php?page=blockera-settings-account)].
3. Click on the "Activate License" button.
4. After redirecting to the Blockera Site Toolkit consent page, Choose your subscription plan and click on the "Connect" button.
5. After redirecting to the Blockera product website, You should see the license details.

### Clearing the licenses data to retry for testing

1. Remove the ["blockera_api_user_info","blockera_api_client_info"] from the user meta.
2. Go to the Blockera API website and do the instructions at here [[Migration docs](https://github.com/blockeraai/api/blob/master/docs/migration.md)]
3. Go to the blockera product website and try to delete the related license data from the database.
