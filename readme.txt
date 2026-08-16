=== Blockera Site Toolkit ===
Contributors: blockeraai, aliaghdam, rezaelahidev
Tags: blockera, licenses, oauth, woocommerce, rest-api
Requires at least: 6.6
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.en.html

Powers Blockera account licensing, OAuth connect flows, secure downloads, and WooCommerce product release tooling on blockera.ai.

== Description ==

**Blockera Site Toolkit** is the site-side companion used on [blockera.ai](https://blockera.ai/) to manage product licensing, customer account experiences, and release delivery for Blockera products.

It is designed to run alongside **Blockera**, **Blockera Pro**, and **Blockera One**, sharing packages through the Blockera autoloader coordinator and registering itself in the shared products registry.

### What it does

#### OAuth connect & consent
- Pretty permalinks for `/authorize/` and `/consent-form/`
- Login gate for guests requesting authorization
- Consent UI so customers can approve connecting a client site to their Blockera account
- Integration with the OAuth2 authorization / resource server stack used by Blockera products

#### My Account → Licenses
- WooCommerce My Account **Licenses** endpoint (`/my-account/licenses/`)
- License list UI backed by orders / subscriptions data
- React-powered license manager for renew, upgrade, and related account actions

#### License & auth REST API (`auth/v1`)
- List and create licenses
- Delete, renew, and upgrade licenses
- Authenticated product plan lookup (`/products/allowed-plans`)
- Secure file download endpoint (`/download`) for entitled releases

#### Product release API (`release/v1`)
- Release version publishing for WooCommerce products
- Upload / sync of general and variation downloadable files for remote distribution

#### WooCommerce product admin
- Extra product and variation meta fields for Blockera release metadata
- Hooks into product save flows to keep downloads and version data in sync

#### Shared Blockera ecosystem
- Registers as `blockera-site-toolkit` in the `@blockera/products` registry
- Declares companions for Blockera, Blockera Pro, and Blockera One so shared PHP packages coordinate cleanly on the same site

### Requirements

- WordPress 6.6+
- PHP 7.4+
- [WooCommerce](https://wordpress.org/plugins/woocommerce/) (My Account licenses, product meta, and related flows)
- YITH WooCommerce Subscription (or compatible subscription data used by the licenses views), when license/subscription listing is required

### Quick links

- [Blockera](https://blockera.ai/)
- [Blockera Site Builder](https://blockera.ai/products/site-builder/)
- [Blockera community](https://community.blockera.ai/)

== Installation ==

= Automatic installation =

1. Upload the plugin ZIP via **Plugins → Add New → Upload Plugin**, or place the plugin folder under `wp-content/plugins/blockera-site-toolkit`.
2. Activate **Blockera Site Toolkit**.
3. Ensure WooCommerce (and subscription support, if needed) is active.
4. Visit **Settings → Permalinks** and save once so `/authorize/`, `/consent-form/`, and My Account rewrite endpoints flush correctly.

= Development install =

Follow the project getting-started guide: install npm and Composer dependencies, then build assets with `npm run build` (or `npm start` while developing).

== Frequently Asked Questions ==

= Is this a page builder like Blockera Site Builder? =

No. Blockera Site Toolkit powers site/account/license and release infrastructure for Blockera products. Design and block-editor features live in **Blockera** / **Blockera Pro** / **Blockera One**.

= Do I need WooCommerce? =

Yes for My Account licenses, product admin meta, and most commerce-related flows. OAuth rewrite endpoints can load without WooCommerce, but the intended production stack includes WooCommerce.

= Which REST namespaces does it register? =

- `auth/v1` — licenses, downloads, allowed plans
- `release/v1` — product release publishing

= Can it run next to Blockera and Blockera Pro? =

Yes. The plugin bootstraps the shared autoloader coordinator and lists Blockera, Blockera Pro, and Blockera One as companions so dependencies can be shared safely on the same WordPress site.

== Screenshots ==

1. My Account Licenses section for managing customer licenses.
2. OAuth consent form when connecting a client site.
3. WooCommerce product admin fields for Blockera release metadata.

== Changelog ==

= 1.0 =
* Initial public readme documenting OAuth, licenses, REST APIs, releases, and WooCommerce product tooling.
* Shared products registry registration and multi-product autoloader companions.
