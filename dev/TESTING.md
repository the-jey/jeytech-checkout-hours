# Release verification

The runtime is tested from the extracted production archive, never from the development source tree. The archive builder refuses any languages directory or PO/POT/MO/PHP language catalog before creating a distributable ZIP.

Matrix:

* WordPress 7.1.2 / WooCommerce 11.1.2 / PHP 8.3 / HPOS.
* WordPress 7.1.2 / WooCommerce 11.1.2 / PHP 7.4 / post storage.
* WordPress 6.6.2 / WooCommerce 9.6.2 / PHP 7.4 / post storage.

`scenario-test.php` checks weekly boundaries, breaks, overnight shifts, Sunday wrap, spring gaps, both occurrences of autumn hours, fixed-offset timezones, invalid/overlapping schedules, disabled defaults, Settings API capability/nonce, live REST cache headers, direct checkout rejection, preserved carts, orders and managed stock, and external French language packs. Time fixtures use independently calculated UTC epochs. The production engine also keeps absolute UTC timestamps separate from local wall-clock comparisons, so an ambiguous autumn hour is never converted back into an instant.

`browser-test.mjs en` and `browser-test.mjs fr` submit real native classic and Blocks checkouts in closed/open/warning modes. The BACS gateway is subclassed only by the development MU fixture to count calls; no external payment or email is sent. Rejected submissions use managed-stock products and compare order IDs (including drafts), stock and gateway calls. Successful native submissions use a non-stock virtual product because Playground SQLite cannot execute WooCommerce's stock-reservation SQL. This limits positive stock-flow validation in this environment; the plugin does not replace WooCommerce stock processing. Cached open HTML is replayed after closing and native non-AJAX classic checkout is also tested with JavaScript disabled.

French test catalogs are copied separately to `wp-content/languages/plugins`; they are never shipped inside the plugin. `test:i18n` also verifies English fallback without a pack and restoration after a locale switch on the supported minimum.

Plugin Check includes all available static and experimental checks. Its runtime suite needs a second database unavailable in Playground SQLite; the native functional/browser suites provide the runtime checks here.

Development endpoints, gateway counters, mail interception, blueprints, screenshots and reports are excluded from the plugin ZIP. Never install the development MU fixture on a production shop.

## Native managed inventory, 28 September 2026

`browser-stock-mysql.mjs en|fr` additionally runs the exact unchanged 1.0.0 ZIP on WordPress 7.1.2 / WooCommerce 11.1.2 / native PHP 8.3.6 / MariaDB 10.11.14 / HPOS. Twelve real native classic/Blocks closed/open/warning scenarios pass in EN/FR. The same stock-managed virtual product is used for accepted and rejected orders. Eight accepted BACS orders each reduce inventory once and release the order reservation; reloading the receipt does not repeat the gateway or reduction. Four rejected orders preserve inventory, reservations, order IDs and gateway count. All mail is intercepted; no transfer is initiated.

Fixtures and the isolated native toolchain remain under `/home/jey/.local/state/jeytech/wordpress-mysql/`; the server-side MU helpers and unsigned state endpoint are localhost-only development fixtures excluded from the ZIP. Reports are in `results/browser-stock-mysql-en.json` and `results/browser-stock-mysql-fr.json`. This additional run resolves positive stock coverage on native PHP 8.3/HPOS; it does not add native PHP 7.4/post-storage inventory coverage.
