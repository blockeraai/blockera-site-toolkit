# Site Toolkit E2E specs

Cypress specs for `@blockera/site-toolkit` (JS UI + PHP routes/views/REST).

## Naming

Follow the toolkit consumer convention (see `list-e2e-test-categories-toolkit.js`):

```text
{feature}.toolkit.{category}.e2e.cy.js
```

Examples:

| File | Category |
| --- | --- |
| `plugin.toolkit.bootstrap.e2e.cy.js` | `bootstrap` |
| `rest-routes.toolkit.api.e2e.cy.js` | `api` |
| `licenses.toolkit.my-account.e2e.cy.js` | `my-account` |
| `authorize.toolkit.oauth.e2e.cy.js` | `oauth` |
| `product-meta.toolkit.admin.e2e.cy.js` | `admin` |

CI runs one matrix job per category and loads `.github/wp-env-configs/{category}.json` when present (falls back to `base.json`).

## Categories

- **bootstrap** — plugin activation, rewrite entry, REST namespaces
- **api** — auth/release REST registration + permission gates
- **my-account** — `/my-account/licenses` PHP view + React mount (needs WooCommerce)
- **oauth** — `/authorize` + `/consent-form` flows
- **admin** — WooCommerce product admin meta hooks

## Local run

```bash
npm run env:start
npm run build
npm run test:e2e -- --spec 'packages/site-toolkit/**/*.toolkit.api.e2e.cy.js'
```
