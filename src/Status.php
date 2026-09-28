<?php
/** Shared live status. @package JeyTech\CheckoutHours */
namespace JeyTech\CheckoutHours;

defined( 'ABSPATH' ) || exit;

/** The same authoritative state is used by checkout, previews and the public endpoint. */
final class Status {
 /** Compute status using WordPress's configured timezone.
  * @param array|null              $settings Optional settings for previews.
  * @param int|null $timestamp Optional absolute instant for pure engine tests.
  */
 public static function get( ?array $settings = null, ?int $timestamp = null ): array {
  $settings = $settings ?? Settings::get();
  $state = Schedule::evaluate( $settings['schedule'], $timestamp ?? time(), wp_timezone() );
  $enabled = ! empty( $settings['enabled'] );
  $next = $state['next'];
  $message = '';
  if ( $enabled && ! $state['open'] ) {
   $message = 'warn' === $settings['mode']
    ? __( 'We are outside our order hours. You can still place an order.', 'jeytech-checkout-hours' )
    : __( 'Checkout is currently closed. You can continue browsing and filling your cart.', 'jeytech-checkout-hours' );
   if ( $next ) {
    $message .= ' ' . sprintf(
     /* translators: %s: localized next opening date and time in the store timezone. */
     __( 'Next opening: %s.', 'jeytech-checkout-hours' ),
     wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $next, wp_timezone() )
    );
   }
  }
  return array( 'enabled' => $enabled, 'open' => $state['open'], 'blocked' => $enabled && ! $state['open'] && 'block' === $settings['mode'], 'mode' => $settings['mode'], 'timezone' => wp_timezone_string(), 'nextOpening' => $next ? wp_date( DATE_ATOM, $next, wp_timezone() ) : null, 'message' => $message );
 }
}
