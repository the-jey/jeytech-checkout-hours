<?php
/** Settings API integration. @package JeyTech\CheckoutHours */
namespace JeyTech\CheckoutHours;

defined( 'ABSPATH' ) || exit;

/** A single validated option, changed only through the WordPress Settings API. */
final class Settings {
 const OPTION = 'jeytech_ch_settings';
 const GROUP = 'jeytech_ch';

 /** Safe installation defaults: checkout remains available until explicitly enabled. */
 public static function defaults(): array {
  return array( 'enabled' => false, 'mode' => 'block', 'banner' => false, 'schedule' => array_fill( 1, 7, array() ) );
 }

 /** Get saved settings with defaults. */
 public static function get(): array {
  $saved = get_option( self::OPTION, array() );
  return array_merge( self::defaults(), is_array( $saved ) ? $saved : array() );
 }

 /** Register the option without exposing it in REST. */
 public static function register(): void {
  register_setting( self::GROUP, self::OPTION, array( 'type' => 'array', 'sanitize_callback' => array( self::class, 'sanitize' ), 'default' => self::defaults(), 'show_in_rest' => false ) );
 }

 /** Authorize store managers and administrators, as for the submenu. */
 public static function capability(): string { return 'manage_woocommerce'; }

 /** Validate the whole input; failed saves retain the previous configuration.
  * @param mixed $input Submitted settings, already unslashed by WordPress.
  */
 public static function sanitize( $input ): array {
  $old = self::get();
  if ( ! is_array( $input ) ) { return $old; }
  $days = Schedule::normalize( $input['schedule'] ?? array() );
  if ( is_wp_error( $days ) ) { add_settings_error( self::OPTION, 'invalid_schedule', $days->get_error_message() ); return $old; }
  $enabled = ! empty( $input['enabled'] );
  $mode = $input['mode'] ?? 'block';
  if ( ! in_array( $mode, array( 'block', 'warn' ), true ) ) {
   add_settings_error( self::OPTION, 'invalid_mode', __( 'Choose a valid closed-hours mode.', 'jeytech-checkout-hours' ) ); return $old;
  }
  if ( $enabled && ! Schedule::intervals( $days ) ) {
   add_settings_error( self::OPTION, 'empty_schedule', __( 'Add at least one opening range before enabling checkout hours.', 'jeytech-checkout-hours' ) ); return $old;
  }
  return array( 'enabled' => $enabled, 'mode' => $mode, 'banner' => ! empty( $input['banner'] ), 'schedule' => $days );
 }
}
