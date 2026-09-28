<?php
/** Runtime requirements. @package JeyTech\CheckoutHours */
namespace JeyTech\CheckoutHours\Core;

defined( 'ABSPATH' ) || exit;

/** Fail gracefully when WooCommerce is missing or too old. */
final class Requirements {
 const MIN_WC_VERSION = '9.6';

 /** Whether WooCommerce is supported. */
 public static function met(): bool {
  return defined( 'WC_VERSION' ) && version_compare( WC_VERSION, self::MIN_WC_VERSION, '>=' );
 }

 /** Display a requirements notice to administrators. */
 public static function notice(): void {
  if ( ! current_user_can( 'activate_plugins' ) ) { return; }
  printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( sprintf(
   /* translators: %s: minimum WooCommerce version. */
   __( 'JeyTech Checkout Hours needs WooCommerce %s or later to run.', 'jeytech-checkout-hours' ), self::MIN_WC_VERSION
  ) ) );
 }
}
