<?php
/** Optional storefront status display. @package JeyTech\CheckoutHours */
namespace JeyTech\CheckoutHours;

defined( 'ABSPATH' ) || exit;

/** Informational live display; checkout enforcement never depends on JavaScript. */
final class Frontend {
 /** Register an unauthenticated read-only status endpoint. */
 public static function routes(): void {
  register_rest_route( 'jeytech-checkout-hours/v1', '/status', array( 'methods' => 'GET', 'permission_callback' => '__return_true', 'callback' => array( self::class, 'response' ) ) );
 }

 /** Return uncacheable live public state.
  * @return \WP_REST_Response Status response.
  */
 public static function response(): \WP_REST_Response {
  $response = new \WP_REST_Response( Status::get() );
  $response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );
  $response->header( 'Expires', '0' );
  return $response;
 }

 /** Load scoped assets only when used by the banner, shortcode or checkout. */
 public static function assets(): void {
  if ( ! Settings::get()['enabled'] ) { return; }
  $post = get_post();
  if ( Settings::get()['banner'] || is_checkout() || ( $post && has_shortcode( $post->post_content, 'jeytech_checkout_hours' ) ) ) { self::enqueue(); }
 }

 /** Enqueue assets; this is also safe when a shortcode is rendered late. */
 private static function enqueue(): void {
  wp_enqueue_style( 'jeytech-ch-status', plugins_url( 'assets/status.css', JEYTECH_CH_FILE ), array(), JEYTECH_CH_VERSION );
  wp_enqueue_script( 'jeytech-ch-status', plugins_url( 'assets/status.js', JEYTECH_CH_FILE ), array(), JEYTECH_CH_VERSION, true );
 }

 /** Cached pages receive a neutral placeholder; JavaScript fetches current status. */
 public static function shortcode(): string {
  if ( ! Settings::get()['enabled'] ) { return ''; }
  self::enqueue();
  return '<div class="jeytech-ch-status" data-jeytech-ch-status data-url="' . esc_url( rest_url( 'jeytech-checkout-hours/v1/status' ) ) . '" role="status" aria-live="polite" hidden></div>';
 }

 /** Render the optional sitewide notice once. */
 public static function banner(): void {
  if ( Settings::get()['banner'] ) { echo self::shortcode(); } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shortcode escapes its complete markup.
 }

 /** Emit the notice outside the Blocks tree so native hydration stays intact.
  * The display script moves it before the checkout after the page is loaded.
  */
 public static function checkout_notice(): void {
  $post = get_post();
  if ( ! is_checkout() || ! $post || ! has_block( 'woocommerce/checkout', $post->post_content ) || ! Settings::get()['enabled'] ) { return; }
  echo str_replace( 'data-jeytech-ch-status ', 'data-jeytech-ch-checkout data-jeytech-ch-status ', self::shortcode() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shortcode escapes its complete markup.
 }
}
