<?php
/** Server-side checkout protection. @package JeyTech\CheckoutHours */
namespace JeyTech\CheckoutHours;

defined( 'ABSPATH' ) || exit;

/** Enforce schedules before order creation or payment processing. */
final class Checkout {
 /** Classic checkout validation.
  * @param array     $data Validated checkout data.
  * @param \WP_Error $errors Native WooCommerce validation errors.
  */
 public static function validate( array $data, \WP_Error $errors ): void {
  $status = Status::get();
  if ( $status['blocked'] ) { $errors->add( 'jeytech_ch_closed', $status['message'] ); }
 }

 /** Stop only new Checkout Blocks POSTs, before the route touches an order.
  * @param mixed            $result Existing REST pre-dispatch result.
  * @param \WP_REST_Server  $server Server.
  * @param \WP_REST_Request $request Request.
  * @return mixed Existing result, null, or checkout error.
  */
 public static function store_api( $result, \WP_REST_Server $server, \WP_REST_Request $request ) {
  if ( null !== $result || 'POST' !== $request->get_method() || ! preg_match( '#^/wc/store/(?:v[0-9]+/)?checkout/?$#D', $request->get_route() ) ) { return $result; }
  $status = Status::get();
  return $status['blocked'] ? new \WP_Error( 'jeytech_ch_closed', $status['message'], array( 'status' => 403 ) ) : $result;
 }

 /** A warning or closure notice on native checkout pages. */
 public static function notice(): void {
  $status = Status::get();
  if ( $status['message'] ) { wc_print_notice( $status['message'], 'notice' ); }
 }
}
