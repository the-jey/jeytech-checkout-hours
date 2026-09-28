<?php
/** Development fixtures; never part of the release ZIP. */
defined( 'ABSPATH' ) || exit;

function jeytech_ch_dev_settings( $state ): array {
 $days = array_fill( 1, 7, array() );
 if ( 'open' === $state ) {
  $days = array_fill( 1, 7, array( array( 'start' => '00:00', 'end' => '24:00' ) ) );
 } else {
  $tomorrow = (int) current_datetime()->modify( '+1 day' )->format( 'N' );
  $days[$tomorrow] = array( array( 'start' => '09:00', 'end' => '12:00' ), array( 'start' => '14:00', 'end' => '18:00' ) );
  $night = $tomorrow % 7 + 1;
  $days[$night] = array( array( 'start' => '22:00', 'end' => '02:00' ) );
 }
 return array( 'enabled' => 'disabled' !== $state, 'mode' => 'warn' === $state ? 'warn' : 'block', 'banner' => true, 'schedule' => $days );
}

function jeytech_ch_dev_metrics(): array {
 $orders = wc_get_orders( array( 'limit' => -1, 'return' => 'ids', 'status' => array_merge( array_keys( wc_get_order_statuses() ), array( 'wc-checkout-draft' ) ) ) );
 $product = wc_get_product( get_option( 'jeytech_ch_demo_product' ) );
 $gateways = WC()->payment_gateways()->payment_gateways();
 return array( 'orders' => $orders, 'stock' => $product ? $product->get_stock_quantity() : null, 'gatewayCalls' => (int) get_option( 'jeytech_ch_gateway_calls', 0 ), 'gatewayClass' => isset( $gateways['bacs'] ) ? get_class( $gateways['bacs'] ) : null, 'state' => \JeyTech\CheckoutHours\Status::get(), 'settings' => \JeyTech\CheckoutHours\Settings::get() );
}
