# Site toolkit architecture

Backend toolkit for blockera.ai (not the Site Builder editor). Namespace `BlockeraAI\SiteToolkit`. Bootstrap: `packages/global-packages/packages/site-toolkit/php/Setup.php`, routes `php/Routes/` (web + `api/`), controllers under `php/Http/Controller/`, repositories under `php/Repositories/`.

Front-end account UI: `packages/global-packages/packages/site-toolkit/js/` (my-account, licenses, downloads).

## Tests

From this plugin root: `npm run test:e2e`, `test:js` (`--passWithNoTests` in this product), `test:unit:php`, `test:snapshots:php`. Package allow-list: [declared-gp-packages.md](declared-gp-packages.md).

## GP

The product package lives in `packages/global-packages` with the shared libraries. Do not duplicate GP architecture docs here.
