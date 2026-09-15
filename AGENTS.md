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
- Skip Pro overlay packages (`*-pro`) and One overlay packages (`*-one`) in this product’s tests and lint. They may appear on disk after a GP bump.
