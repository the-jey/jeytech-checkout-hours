# JeyTech Checkout Hours for WooCommerce

Free weekly checkout schedule. Catalog and cart stay usable outside order hours.

Version 1.0.0 is complete and tested. The [English](https://jeytech.app/plugins/checkout-hours) and [French](https://jeytech.app/fr/plugins/checkout-hours) product pages are live. WordPress.org submission is pending the completion of the existing Order Transfer QR review; this plugin is not published in the directory yet.

Validation: 219 functional checks, 18 native EN/FR checkout browser scenarios, native external French pack and English fallback, 15 language-packaging scenarios, and Plugin Check's static/experimental checks with zero errors or warnings. See [test scope and environment limitations](dev/TESTING.md), [release manifest](dev/release-manifest.json) and [implementation status](dev/IMPLEMENTATION.md).

## Development

`npm ci`, `npm run build`, `npm test`, `npm run test:legacy`, `npm run test:minimum`, `npm run test:i18n`, `npm run check`. The French development catalog is installed separately in WordPress’s native language-pack directory. WordPress.org archives refuse any translation catalogs from the first build.

## Release

Follow `../FREE_RELEASE_CHECKLIST.md`. Weekly store-wide Free functionality only; Pro is a separate future project. No tracking or external services. GPL-2.0-or-later.
