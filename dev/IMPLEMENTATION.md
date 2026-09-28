# Checkout Hours — approved implementation

Name: JeyTech Checkout Hours for WooCommerce. Slug: jeytech-checkout-hours. Version 1.0.0.

User approved weekly schedule, checkout-only enforcement, block/warning modes, default disabled, native translations, tested ZIP, published EN/FR site and WordPress.org submission when plugin #4 review permits.

- [x] Free runtime/admin and shared timezone engine
- [x] Exact ZIP HPOS/classic/minimum/Blocks and EN/FR tests (219 runtime checks; 18 native browser scenarios)
- [x] Packaging guard 15 scenarios and Plugin Check (0 errors / 0 warnings)
- [x] Original assets and real screenshots, including native French date/time/price formatting
- [x] Public GitHub repository, implementation and release proofs pushed
- [x] EN/FR site Sandbox + Live, preserving commerce
- [x] Authenticated final recheck of #4: still Being Reviewed at 13:49 UTC / 15:49 Paris
- [ ] Submit #5 once #4 is approved; package is ready, no submission attempted

## Final validation and implementation decisions

The final time engine takes absolute Unix timestamps and a store timezone, avoiding a PHP 7.4 ambiguity when rebuilding an instant from a repeated local autumn hour. HPOS, posts and minimum suites each passed 73 checks on the final production files: 219 checks, no failures. Independently calculated UTC fixtures cover spring gaps and both occurrences of autumn hours.

Do not prepend markup to the WooCommerce checkout block: this prevented its native hydration in the browser. The notice now comes from wp_footer and is moved outside the block by status.js. Both native browser suites passed all nine scenarios after this correction, including classic and Blocks closed/open/warning modes, replayed cached open HTML after closure, and classic checkout without JavaScript.

Open/warn native positive tests use a non-stock virtual product because Playground SQLite rejects WooCommerce stock reservation SQL. Rejected native submissions and direct Store API tests use managed stock, with no gateway call or stock loss. Plugin Check's static and experimental checks report zero errors and warnings; its separate runtime suite requires a second database unavailable here. Scope and limitations are documented in [TESTING.md](TESTING.md).

The WordPress.org ZIP contains 17 production files, identical to the tested mounted runtime, SHA-256 `7a5d962ca3224700ae817530cbdc17172780574908e7a1118790fc0eeb758c49`. The first-build packaging guard passed 15 clean/contaminated scenarios and is registered in the shared harness. No catalogs are bundled. Native French language-pack installation, English fallback and locale restoration passed on the minimum supported stack. All 51 French strings remain in Git; public pack validation follows WordPress.org publication.

## Published site and source

[Public GitHub](https://github.com/the-jey/jeytech-checkout-hours), [English page](https://jeytech.app/plugins/checkout-hours), [French page](https://jeytech.app/fr/plugins/checkout-hours).

Original shop/clock hero, code-created WordPress branding and six genuine EN/FR screenshots are integrated. French screenshots use native French date, hour and price formats. The catalog, home, navigation, footer and sitemap include the new Free plugin. Svelte Check reports zero errors and warnings. Existing Pro guide checks passed.

Sandbox `ad2d7f5f-f43c-465a-b7bc-f045cec68f22` and Live `9f3a7e38-78b8-4e94-b81b-23b92e330579` each passed eight desktop/mobile page checks plus the EN/FR sitemap check. Deployments used `--keep-vars`; 28 Sandbox and 27 Live binding configurations available through the API remained identical. Existing site changes were preserved. Proofs: [site/completion.json](site/completion.json) and the other JSON reports under `site/`; private logs and full-page captures remain in `jeytech-app/outputs/checkout-hours-2026-09-28`.

## WordPress.org handoff

The authenticated portal still confirms that the #4 review reply was received and the Plugins Team must act. Do not resubmit #4 or ask the user to send its reply again. #5 is prepared, not submitted; the official FAQ generally permits one pending submission at a time. Recheck the name and slug at the actual submission, publish #4 through its SVN once approved, then upload the tested #5 ZIP and verify the assigned slug and downloaded submission. See [submission-status.json](submission-status.json).

No Pro product, price or sale was added for Checkout Hours.

## Additional native database verification

On 28 September 2026 the unchanged production ZIP passed 12 additional real native EN/FR classic/Blocks inventory scenarios on PHP 8.3.6 / MariaDB 10.11.14 / HPOS. Eight positive stock-managed orders and four rejected orders were checked, including reservation release and idempotent receipt reload. See [TESTING.md](TESTING.md) and the `results/browser-stock-mysql-*.json` proofs. No production code or archive changed.
