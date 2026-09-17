# Agents — blockera-site-toolkit

WordPress plugin for blockera.ai: licensing, OAuth, downloads, Woo product release tooling. Package: `packages/global-packages/packages/site-toolkit`.

## Inspect

- Shared: [`packages/global-packages/packages/dev-tools/ai/index.md`](packages/global-packages/packages/dev-tools/ai/index.md)
- Product: [`.ai/index.md`](.ai/index.md)
- PHP HTTP: `packages/global-packages/packages/site-toolkit/php/Http/`, routes in `php/Routes/`
- Gutenberg routing is secondary; prefer toolkit PHP/JS and Woo/product APIs when the task is licensing or downloads.

## Constraints

- Active product **blockera-site-toolkit**. GP writes: `packages/global-packages/`.
- Changelog/README: [`…/ai/workflows/changelog-and-readme.md`](packages/global-packages/packages/dev-tools/ai/workflows/changelog-and-readme.md)
- Scripts from **this** root: `npm run test:e2e`, `test:js`, `test:unit:php` — [`…/ai/workflows/product-scripts-and-deps.md`](packages/global-packages/packages/dev-tools/ai/workflows/product-scripts-and-deps.md)

## Declared GP packages

<!-- generated:declared-gp-packages -->
Read [`.ai/declared-gp-packages.md`](.ai/declared-gp-packages.md) before changing PHPUnit, Jest, PHPCS, ESLint, Stylelint, or CI package filters. `project:bootstrap` rewrites that file from `package.json` `dependencies` and `composer.json` `require`. Do **not** add a GP package to those setups if it is missing from the generated list.
<!-- /generated:declared-gp-packages -->
