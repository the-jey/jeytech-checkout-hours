# Checkout Hours — approved implementation

Name: JeyTech Checkout Hours for WooCommerce. Slug: jeytech-checkout-hours. Version 1.0.0.

User approved weekly schedule, checkout-only enforcement, block/warning modes, default disabled, native translations, tested ZIP, published EN/FR site and WordPress.org submission when plugin #4 review permits.

- [x] Free runtime/admin and shared timezone engine
- [x] Exact ZIP HPOS/classic/minimum/Blocks and EN/FR tests (219 runtime checks; 18 native browser scenarios)
- [x] Packaging guard 15 scenarios and Plugin Check (0 errors / 0 warnings)
- [x] Original assets and real screenshots, including native French date/time/price formatting
- [ ] Public GitHub repository
- [ ] EN/FR site Sandbox + Live, preserving commerce
- [ ] Recheck #4 and submit #5 when eligible

## Runtime decisions and current checks

The final time engine takes absolute Unix timestamps and a store timezone, avoiding a PHP 7.4 ambiguity when rebuilding an instant from a repeated local autumn hour. HPOS, posts and minimum suites each passed 73 checks before the last display change. Final rechecks are running.

Do not prepend markup to the WooCommerce checkout block: this prevented its native hydration in the browser. The notice now comes from wp_footer and is moved outside the block by status.js. Native browser suites EN/FR are running against this correction.

Open/warn native positive tests use a non-stock virtual product because Playground SQLite rejects WooCommerce stock reservation SQL. Rejected classic submissions and direct Store API tests use managed stock, with no gateway call or stock loss. This limitation is documented in TESTING.md.

Site source pages, catalog/home/header/footer and EN/FR texts are written. AI hero and WordPress branding exist. Site has not been deployed yet; waiting for final native screenshots. Pre-deploy CF snapshots: jeytech-app/outputs/checkout-hours-2026-09-28.
