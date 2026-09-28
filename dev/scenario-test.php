<?php
/** Meaningful integration and timezone tests against the final production files. */
use JeyTech\CheckoutHours\Schedule;
use JeyTech\CheckoutHours\Settings;
use JeyTech\CheckoutHours\Status;
use JeyTech\CheckoutHours\Checkout;
use JeyTech\CheckoutHours\Frontend;
use JeyTech\CheckoutHours\Admin\SettingsPage;

defined( 'ABSPATH' ) || exit;
$matrix = get_option( 'jeytech_ch_test_matrix', 'hpos' );
$lines = array(); $failures = 0;
$check = static function ( $name, $pass ) use ( &$lines, &$failures ) { $lines[] = ( $pass ? 'PASS  ' : 'FAIL  ' ) . $name; if ( ! $pass ) { ++$failures; } };
$capture = static function ( $fn ) { ob_start(); $fn(); return ob_get_clean(); };
$schedule = static function ( $day, $rows ) { return array_replace( array_fill( 1, 7, array() ), array( $day => $rows ) ); };
$range = static function ( $a, $b ) { return array( 'start' => $a, 'end' => $b ); };
$instants = json_decode( file_get_contents( __DIR__ . '/time-fixtures.json' ), true );
$evaluate = static function ( $days, $instant, $zone = 'Europe/Paris' ) use ( $instants ) {
 // Fixed UTC epochs keep the schedule tests independent of local time parsing.
 $timezone = new DateTimeZone( $zone );
 $state = Schedule::evaluate( $days, $instants[$instant], $timezone );
 if ( null !== $state['next'] ) { $state['next'] = ( new DateTimeImmutable( '@' . $state['next'] ) )->setTimezone( $timezone ); }
 return $state;
};
try {
 add_filter( 'pre_wp_mail', '__return_true' );
 foreach ( WC()->mailer()->get_emails() as $email ) { add_filter( 'woocommerce_email_enabled_' . $email->id, '__return_false' ); }
 update_option( 'timezone_string', 'Europe/Paris' );
 $check( 'Default installation does not restrict checkout', ! Status::get()['blocked'] && ! Settings::get()['enabled'] );
 $week = $schedule( 1, array( $range( '09:00', '12:00' ), $range( '14:00', '17:00' ) ) );
 foreach ( array( '08:59:59' => false, '09:00:00' => true, '11:59:59' => true, '12:00:00' => false, '13:59:59' => false, '14:00:00' => true, '16:59:59' => true, '17:00:00' => false ) as $time => $open ) {
  $check( 'Opening/closing/lunch boundary ' . $time, $open === $evaluate( $week, '2026-09-28T' . $time . '+02:00' )['open'] );
 }
 $check( 'Lunch next opening is same-day 14:00', '2026-09-28T14:00:00+02:00' === $evaluate( $week, '2026-09-28T12:00:00+02:00' )['next']->format( DATE_ATOM ) );
 $check( 'Closing next opening wraps to next Monday', '2026-10-05T09:00:00+02:00' === $evaluate( $week, '2026-09-28T17:00:00+02:00' )['next']->format( DATE_ATOM ) );
 $night = $schedule( 7, array( $range( '22:00', '02:00' ) ) );
 foreach ( array( '2026-09-27T21:59:59+02:00' => false, '2026-09-27T22:00:00+02:00' => true, '2026-09-28T00:00:00+02:00' => true, '2026-09-28T01:59:59+02:00' => true, '2026-09-28T02:00:00+02:00' => false ) as $time => $open ) { $check( 'Sunday overnight/week wrap ' . $time, $open === $evaluate( $night, $time )['open'] ); }
 $full = $schedule( 1, array( $range( '00:00', '24:00' ) ) );
 $check( '24-hour day includes midnight and last second', $evaluate( $full, '2026-09-28T00:00:00+02:00' )['open'] && $evaluate( $full, '2026-09-28T23:59:59+02:00' )['open'] );
 $check( '24-hour day closes exactly at following midnight', ! $evaluate( $full, '2026-09-29T00:00:00+02:00' )['open'] );
 $dst = $schedule( 7, array( $range( '02:30', '04:00' ) ) );
 $spring = $evaluate( $dst, '2026-03-29T01:59:59+01:00' );
 $check( 'DST gap next opening is first real 03:00 instant', '2026-03-29T03:00:00+02:00' === $spring['next']->format( DATE_ATOM ) );
 $check( 'DST gap remains open after skipped opening', $evaluate( $dst, '2026-03-29T03:15:00+02:00' )['open'] );
 $skipped = $evaluate( $schedule( 7, array( $range( '02:00', '02:30' ) ) ), '2026-03-29T01:59:59+01:00' );
 $check( 'Entire skipped range waits for next real Sunday', '2026-04-05T02:00:00+02:00' === $skipped['next']->format( DATE_ATOM ) );
 $check( 'DST repeated first and second hours follow same wall-clock schedule', $evaluate( $dst, '2026-10-25T02:45:00+02:00' )['open'] && $evaluate( $dst, '2026-10-25T02:45:00+01:00' )['open'] );
 $fold = $evaluate( $dst, '2026-10-25T02:15:00+01:00' );
 $check( 'DST next opening resolves second 02:30 occurrence', '2026-10-25T02:30:00+01:00' === $fold['next']->format( DATE_ATOM ) );
 $rewind = $evaluate( $schedule( 7, array( $range( '02:00', '02:30' ) ) ), '2026-10-25T02:40:00+02:00' );
 $check( 'DST clock rewind itself can reopen a range (actual ' . $rewind['next']->format( DATE_ATOM ) . ')', '2026-10-25T02:00:00+01:00' === $rewind['next']->format( DATE_ATOM ) );
 $fixed = $evaluate( $week, '2026-09-28T08:30:00+05:30', '+05:30' );
 $check( 'Fixed-offset timezone without transition table works', '2026-09-28T09:00:00+05:30' === $fixed['next']->format( DATE_ATOM ) );
 $check( 'Empty disabled schedule has no opening', null === $evaluate( array_fill( 1, 7, array() ), '2026-09-28T08:00:00+02:00' )['next'] );
 foreach ( array( array( $range( '24:00', '02:00' ) ), array( $range( '09:60', '12:00' ) ), array( $range( '9:00', '12:00' ) ), array( $range( '09:00', '09:00' ) ), array( $range( '09:00', '' ) ), array( $range( '<script>', '12:00' ) ), array_fill( 0, 13, $range( '09:00', '10:00' ) ) ) as $i => $bad ) { $check( 'Invalid/malformed schedule rejected ' . $i, is_wp_error( Schedule::normalize( $schedule( 1, $bad ) ) ) ); }
 $check( 'Malformed whole schedule rejected', is_wp_error( Schedule::normalize( 'bad' ) ) );
 $check( 'Overlapping daily ranges rejected', is_wp_error( Schedule::normalize( $schedule( 1, array( $range( '09:00', '12:00' ), $range( '11:00', '14:00' ) ) ) ) ) );
 $overlap = $night; $overlap[1] = array( $range( '01:00', '03:00' ) );
 $check( 'Overlapping Sunday/Monday overnight rejected', is_wp_error( Schedule::normalize( $overlap ) ) );
 $check( 'Adjacent ranges accepted', ! is_wp_error( Schedule::normalize( $schedule( 1, array( $range( '09:00', '12:00' ), $range( '12:00', '17:00' ) ) ) ) ) );
 $check( 'Enabled empty schedule cannot be saved', Settings::get() === Settings::sanitize( array_merge( Settings::defaults(), array( 'enabled' => true ) ) ) );
 $tomorrow = (int) current_datetime()->modify( '+1 day' )->format( 'N' );
 $closed = array( 'enabled' => true, 'mode' => 'block', 'banner' => true, 'schedule' => $schedule( $tomorrow, array( $range( '09:00', '17:00' ) ) ) );
 update_option( Settings::OPTION, $closed );
 $check( 'Shared live state blocks only closed block mode', Status::get()['blocked'] && ! Status::get()['open'] && null !== Status::get()['nextOpening'] );
 $check( 'Invalid save retains previous valid configuration', $closed === Settings::sanitize( array_merge( $closed, array( 'schedule' => $overlap ) ) ) );
 $check( 'Invalid mode retains previous configuration', $closed === Settings::sanitize( array_merge( $closed, array( 'mode' => 'arbitrary' ) ) ) );
 $check( 'Repeated Settings API sanitization is stable', $closed === Settings::sanitize( Settings::sanitize( $closed ) ) );
 if ( ! WC()->session ) { WC()->initialize_session(); }
 if ( ! WC()->cart ) { WC()->initialize_cart(); }
 $product = new WC_Product_Simple(); $product->set_name( 'Checkout Hours test product' ); $product->set_regular_price( '19.90' ); $product->set_virtual( true ); $product->set_manage_stock( true ); $product->set_stock_quantity( 100 ); $product->set_status( 'publish' ); $product->save();
 $key = WC()->cart->add_to_cart( $product->get_id(), 2 );
 $check( 'Closed hours still allow adding to cart', (bool) $key && 2 === WC()->cart->get_cart_contents_count() );
 WC()->cart->set_quantity( $key, 3 );
 $check( 'Closed hours still allow changing cart quantity', 3 === WC()->cart->get_cart_contents_count() );
 $stock = wc_get_product( $product->get_id() )->get_stock_quantity();
 $orders = wc_get_orders( array( 'limit' => -1, 'return' => 'ids', 'status' => array_keys( wc_get_order_statuses() ) ) );
 $error = new WP_Error(); do_action( 'woocommerce_after_checkout_validation', array(), $error );
 $check( 'Native classic validation hook rejects closed checkout', in_array( 'jeytech_ch_closed', $error->get_error_codes(), true ) );
 $server = rest_get_server();
 foreach ( array( '/wc/store/v1/checkout', '/wc/store/checkout', '/wc/store/v1/checkout/' ) as $route ) {
  $response = rest_do_request( new WP_REST_Request( 'POST', $route ) );
  $check( 'Direct Store API closed order rejected before route processing ' . $route, 403 === $response->get_status() && 'jeytech_ch_closed' === $response->get_data()['code'] );
 }
 $check( 'Rejected checkouts leave orders and stock untouched', $stock === wc_get_product( $product->get_id() )->get_stock_quantity() && $orders === wc_get_orders( array( 'limit' => -1, 'return' => 'ids', 'status' => array_keys( wc_get_order_statuses() ) ) ) );
 $check( 'Rejected checkout preserves cart', 3 === WC()->cart->get_cart_contents_count() );
 foreach ( array( array( 'GET', '/wc/store/v1/checkout' ), array( 'PUT', '/wc/store/v1/checkout' ), array( 'POST', '/wc/store/v1/checkout/123' ), array( 'POST', '/wc/store/v1/cart/add-item' ), array( 'POST', '/wc/store/v1/cart/update-item' ) ) as $request ) {
  $check( 'Existing-order/cart/read routes untouched ' . implode( ' ', $request ), null === Checkout::store_api( null, $server, new WP_REST_Request( $request[0], $request[1] ) ) );
 }
 $existing = new WP_Error( 'existing', 'Existing error' );
 $check( 'Earlier REST errors are preserved', $existing === Checkout::store_api( $existing, $server, new WP_REST_Request( 'POST', '/wc/store/v1/checkout' ) ) );
 $response = rest_do_request( new WP_REST_Request( 'GET', '/jeytech-checkout-hours/v1/status' ) );
 $check( 'Public endpoint returns live state without authentication', 200 === $response->get_status() && true === $response->get_data()['blocked'] );
 $check( 'Status endpoint forbids caching', false !== strpos( $response->get_headers()['Cache-Control'], 'no-store' ) );
 $check( 'Status endpoint does not expose editable settings', ! isset( $response->get_data()['schedule'] ) && ! isset( $response->get_data()['banner'] ) );
 $mutation = rest_do_request( new WP_REST_Request( 'POST', '/jeytech-checkout-hours/v1/status' ) );
 $check( 'Status endpoint is read-only', 404 === $mutation->get_status() && $closed === Settings::get() );
 $markup = do_shortcode( '[jeytech_checkout_hours]' );
 $check( 'Cached shortcode contains neutral live placeholder', false !== strpos( $markup, 'data-jeytech-ch-status' ) && false === strpos( $markup, 'Next opening:' ) && false !== strpos( $markup, 'hidden' ) );
 $warn = array_merge( $closed, array( 'mode' => 'warn' ) ); update_option( Settings::OPTION, $warn );
 $warning = Status::get(); $error = new WP_Error(); Checkout::validate( array(), $error );
 $check( 'Warning mode allows native classic orders and supplies notice', ! $error->has_errors() && ! $warning['blocked'] && false !== strpos( $warning['message'], 'You can still place an order' ) );
 $check( 'Warning mode passes Store API new orders', null === Checkout::store_api( null, $server, new WP_REST_Request( 'POST', '/wc/store/v1/checkout' ) ) );
 $always = array_merge( $closed, array( 'schedule' => array_fill( 1, 7, array( $range( '00:00', '24:00' ) ) ) ) ); update_option( Settings::OPTION, $always );
 $check( 'Open mode allows server checkout', ! Status::get()['blocked'] && Status::get()['open'] && null === Checkout::store_api( null, $server, new WP_REST_Request( 'POST', '/wc/store/v1/checkout' ) ) );
 $disabled = array_merge( $closed, array( 'enabled' => false ) ); update_option( Settings::OPTION, $disabled );
 $check( 'Disabled mode hides notices and allows checkout', ! Status::get()['blocked'] && '' === Status::get()['message'] && '' === Frontend::shortcode() );
 $customer = wp_create_user( 'ch_test_customer', 'local-test-password', 'customer@example.test' ); wp_set_current_user( $customer );
 $check( 'Customer cannot access schedule administration', ! current_user_can( 'manage_woocommerce' ) && '' === $capture( array( SettingsPage::class, 'render' ) ) );
 wp_set_current_user( 1 ); Settings::register();
 $form = $capture( array( SettingsPage::class, 'render' ) );
 $check( 'Settings form uses nonce and WooCommerce capability', false !== strpos( $form, '_wpnonce' ) && 'manage_woocommerce' === apply_filters( 'option_page_capability_' . Settings::GROUP, 'manage_options' ) );
 $check( 'Forged settings nonce rejected by native verifier', false === wp_verify_nonce( 'forged', Settings::GROUP . '-options' ) );
 SettingsPage::assets( 'dashboard' ); $check( 'Admin styles are scoped to plugin screen', ! wp_style_is( 'jeytech-ch-admin', 'enqueued' ) );
 SettingsPage::assets( 'woocommerce_page_jeytech-checkout-hours' ); $check( 'Own admin screen loads scoped assets', wp_style_is( 'jeytech-ch-admin', 'enqueued' ) );
 update_option( Settings::OPTION, $closed );
 $switched = switch_to_locale( 'fr_FR' );
 $check( 'Native external French pack translates editor and checkout', $switched && 'Horaires de commande' === __( 'Checkout Hours', 'jeytech-checkout-hours' ) && false !== strpos( Status::get()['message'], 'Les commandes sont actuellement fermées' ) );
 if ( $switched ) { restore_previous_locale(); }
 $check( 'Native English restoration works', 'Checkout Hours' === __( 'Checkout Hours', 'jeytech-checkout-hours' ) );
 $check( 'Actual HPOS/post storage matches selected matrix', 'hpos' === $matrix ? 'yes' === get_option( 'woocommerce_custom_orders_table_enabled' ) : 'no' === get_option( 'woocommerce_custom_orders_table_enabled' ) );
} catch ( Throwable $error ) { $check( 'Exception: ' . $error->getMessage() . ' at ' . $error->getFile() . ':' . $error->getLine(), false ); }
$lines[] = 'Versions: WordPress ' . get_bloginfo( 'version' ) . ', WooCommerce ' . WC_VERSION . ', PHP ' . PHP_VERSION . ', matrix ' . $matrix;
$lines[] = 'Result: ' . count( array_filter( $lines, static function ( $line ) { return 0 === strpos( $line, 'PASS' ); } ) ) . ' passed, ' . $failures . ' failed. No email or external payment.';
file_put_contents( __DIR__ . '/.test-output-' . $matrix . '.txt', implode( "\n", $lines ) . "\n" );
file_put_contents( __DIR__ . '/results/test-' . $matrix . '.txt', implode( "\n", $lines ) . "\n" );
echo implode( "\n", $lines ) . "\n";
