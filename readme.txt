=== JeyTech Checkout Hours for WooCommerce ===
Contributors: jeytech
Tags: woocommerce, checkout, opening hours, weekly schedule, store hours
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Set weekly checkout hours while keeping your catalog and cart available. Supports classic checkout and Checkout Blocks.

== Description ==

JeyTech Checkout Hours for WooCommerce lets you choose when customers can place new orders. Products stay visible and shoppers can keep filling their cart outside your order hours.

Configure your schedule under **WooCommerce → Checkout Hours**. The plugin is disabled on installation and remains inactive until you save and enable a valid opening schedule.

* Multiple opening ranges per weekday, including lunch breaks.
* Overnight ranges and full days using 00:00–24:00.
* Your WordPress timezone, including daylight saving changes.
* Block checkout or accept orders with an outside-hours warning.
* Server-side protection for classic checkout and WooCommerce Checkout Blocks.
* Optional sitewide notice and `[jeytech_checkout_hours]` shortcode.
* Live status refresh for cached pages; final checkout is always checked on the server.
* Compatible with High-Performance Order Storage (HPOS).

Opening times are included and closing times are excluded. A closing time before opening continues into the following day. Overlapping ranges cannot be saved.

Warning mode accepts orders normally. It does not reserve pickup slots, delay payment or schedule fulfillment. Existing order payments, catalog browsing and cart changes are unaffected.

The plugin makes no external service requests and includes no tracking. The live status endpoint is a read-only endpoint on your own WordPress site. Its response must be excluded from any proxy or CDN cache that overrides no-store headers.

Requirements: WordPress 6.6 or later, WooCommerce 9.6 or later, PHP 7.4 or later.

== Installation ==

1. Install and activate WooCommerce.
2. Upload the plugin folder to `/wp-content/plugins/` or install it through WordPress.
3. Activate JeyTech Checkout Hours for WooCommerce.
4. Go to WooCommerce → Checkout Hours and check the store timezone.
5. Add opening ranges, choose the outside-hours mode, enable and save.

== Frequently Asked Questions ==

= Can shoppers still add products to their cart outside order hours? =

Yes. Only the placement of new orders is restricted in block mode. Your catalog and cart stay available.

= Does it support both WooCommerce checkouts? =

Yes. It validates classic checkout on the server and rejects a new Checkout Blocks order before the Store API creates or processes it.

= Which timezone is used? =

The timezone configured in Settings → General in WordPress. Choose a city timezone such as Europe/Paris if you want automatic daylight saving changes.

= How do I enter an overnight shift or a full day? =

Use 22:00–02:00 for an overnight range. It ends at 02:00 on the following day. Use 00:00–24:00 for a full day. Leave a day blank to close it.

= What happens during a daylight saving change? =

Ranges follow the local wall clock. A repeated hour follows the same schedule in both occurrences. A skipped hour has no real instants; the next opening is the first real instant inside an opening range.

= Does it work with a cached page or without JavaScript? =

Final checkout is protected on the server regardless of page caching or JavaScript. Live notices refresh from a noncached local endpoint with JavaScript. A theme must support wp_body_open for the optional sitewide banner.

= Does it block payment for an existing order? =

No. Existing order-payment pages and order-specific Store API routes remain available.

= Is the plugin translated? =

Source strings are English. Translations are delivered by WordPress.org language packs after community validation, without bundled catalogs or custom language loading.

== Screenshots ==

1. Weekly schedule with multiple ranges, overnight shifts and a live store-timezone preview.
2. Classic checkout outside order hours, with the cart preserved.
3. Checkout Blocks outside order hours, with server-side order protection.

== Changelog ==

= 1.0.0 =
* Initial release with weekly checkout hours, classic and Blocks protection, live notices and a shared timezone engine.
