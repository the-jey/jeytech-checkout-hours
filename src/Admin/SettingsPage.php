<?php
/** Store manager schedule UI. @package JeyTech\CheckoutHours */
namespace JeyTech\CheckoutHours\Admin;

use JeyTech\CheckoutHours\Settings;
use JeyTech\CheckoutHours\Status;

defined( 'ABSPATH' ) || exit;

/** A scoped weekly editor with the same live status as checkout. */
final class SettingsPage {
 const SLUG = 'jeytech-checkout-hours';

 /** Register the WooCommerce submenu. */
 public static function menu(): void {
  add_submenu_page( 'woocommerce', __( 'Checkout Hours', 'jeytech-checkout-hours' ), __( 'Checkout Hours', 'jeytech-checkout-hours' ), 'manage_woocommerce', self::SLUG, array( self::class, 'render' ) );
 }

 /** Load editor assets only on this page.
  * @param string $hook Admin page hook.
  */
 public static function assets( string $hook ): void {
  if ( 'woocommerce_page_' . self::SLUG !== $hook ) { return; }
  wp_enqueue_style( 'jeytech-ch-admin', plugins_url( 'assets/admin.css', JEYTECH_CH_FILE ), array(), JEYTECH_CH_VERSION );
  wp_enqueue_script( 'jeytech-ch-admin', plugins_url( 'assets/admin.js', JEYTECH_CH_FILE ), array(), JEYTECH_CH_VERSION, true );
 }

 /** Localized ISO weekday labels. */
 public static function days(): array {
  return array( 1 => __( 'Monday', 'jeytech-checkout-hours' ), 2 => __( 'Tuesday', 'jeytech-checkout-hours' ), 3 => __( 'Wednesday', 'jeytech-checkout-hours' ), 4 => __( 'Thursday', 'jeytech-checkout-hours' ), 5 => __( 'Friday', 'jeytech-checkout-hours' ), 6 => __( 'Saturday', 'jeytech-checkout-hours' ), 7 => __( 'Sunday', 'jeytech-checkout-hours' ) );
 }

 /** One range, also reused in the native HTML template.
  * @param int    $day ISO day.
  * @param string $index Stable row key.
  * @param array  $row Start and end.
  */
 private static function row( int $day, string $index, array $row ): void {
  $name = Settings::OPTION . '[schedule][' . $day . '][' . $index . ']';
  echo '<div class="jeytech-ch-range"><label><span class="screen-reader-text">' . esc_html__( 'Opening time', 'jeytech-checkout-hours' ) . '</span><input type="text" inputmode="numeric" class="jeytech-ch-start" placeholder="09:00" maxlength="5" pattern="([01][0-9]|2[0-3]):[0-5][0-9]" name="' . esc_attr( $name . '[start]' ) . '" value="' . esc_attr( $row['start'] ?? '' ) . '"></label><span aria-hidden="true">→</span><label><span class="screen-reader-text">' . esc_html__( 'Closing time', 'jeytech-checkout-hours' ) . '</span><input type="text" inputmode="numeric" class="jeytech-ch-end" placeholder="17:00" maxlength="5" pattern="(([01][0-9]|2[0-3]):[0-5][0-9]|24:00)" name="' . esc_attr( $name . '[end]' ) . '" value="' . esc_attr( $row['end'] ?? '' ) . '"></label><button type="button" class="jeytech-ch-remove" aria-label="' . esc_attr__( 'Remove time range', 'jeytech-checkout-hours' ) . '">×</button></div>';
 }

 /** Render the protected native Settings API form. */
 public static function render(): void {
  if ( ! current_user_can( 'manage_woocommerce' ) ) { return; }
  $settings = Settings::get();
  $status = Status::get( $settings );
  $state = ! $status['enabled'] ? __( 'Disabled', 'jeytech-checkout-hours' ) : ( $status['open'] ? __( 'Checkout open', 'jeytech-checkout-hours' ) : ( $status['blocked'] ? __( 'Checkout closed', 'jeytech-checkout-hours' ) : __( 'Warning only', 'jeytech-checkout-hours' ) ) );
  ?>
  <div class="wrap jeytech-ch-admin">
   <div class="jeytech-ch-heading"><div><p class="jeytech-ch-eyebrow">JEYTECH / WOOCOMMERCE</p><h1><?php esc_html_e( 'Checkout Hours', 'jeytech-checkout-hours' ); ?></h1><p><?php esc_html_e( 'Accept orders on your schedule. Keep your catalog and cart available.', 'jeytech-checkout-hours' ); ?></p></div><span class="jeytech-ch-version">v<?php echo esc_html( JEYTECH_CH_VERSION ); ?></span></div>
   <?php settings_errors( Settings::OPTION ); ?>
   <form action="options.php" method="post">
    <?php settings_fields( Settings::GROUP ); ?>
    <div class="jeytech-ch-layout">
     <div class="jeytech-ch-main">
      <section class="jeytech-ch-panel">
       <h2><?php esc_html_e( 'Order hours', 'jeytech-checkout-hours' ); ?></h2>
       <label class="jeytech-ch-toggle"><input type="checkbox" name="<?php echo esc_attr( Settings::OPTION ); ?>[enabled]" value="1" <?php checked( $settings['enabled'] ); ?>><strong><?php esc_html_e( 'Enable checkout hours', 'jeytech-checkout-hours' ); ?></strong></label>
       <p class="jeytech-ch-help"><?php esc_html_e( 'Disabled on installation. Add an opening range before enabling.', 'jeytech-checkout-hours' ); ?></p>
       <label class="jeytech-ch-label" for="jeytech-ch-mode"><?php esc_html_e( 'Outside order hours', 'jeytech-checkout-hours' ); ?></label>
       <select id="jeytech-ch-mode" name="<?php echo esc_attr( Settings::OPTION ); ?>[mode]"><option value="block" <?php selected( $settings['mode'], 'block' ); ?>><?php esc_html_e( 'Block checkout', 'jeytech-checkout-hours' ); ?></option><option value="warn" <?php selected( $settings['mode'], 'warn' ); ?>><?php esc_html_e( 'Allow orders with a warning', 'jeytech-checkout-hours' ); ?></option></select>
       <p class="jeytech-ch-help"><?php esc_html_e( 'Warning mode accepts orders normally; it does not schedule their processing.', 'jeytech-checkout-hours' ); ?></p>
       <label class="jeytech-ch-toggle"><input type="checkbox" name="<?php echo esc_attr( Settings::OPTION ); ?>[banner]" value="1" <?php checked( $settings['banner'] ); ?>><?php esc_html_e( 'Show a sitewide closed-hours notice', 'jeytech-checkout-hours' ); ?></label>
      </section>
      <section class="jeytech-ch-panel">
       <h2><?php esc_html_e( 'Weekly schedule', 'jeytech-checkout-hours' ); ?></h2>
       <p class="jeytech-ch-help"><?php esc_html_e( 'Use 24-hour HH:MM times. Blank days are closed. Add ranges for a lunch break. A closing time before opening continues into the next day.', 'jeytech-checkout-hours' ); ?></p>
       <?php foreach ( self::days() as $day => $label ) : ?>
        <div class="jeytech-ch-day" data-day="<?php echo esc_attr( $day ); ?>">
         <div class="jeytech-ch-day-title"><strong><?php echo esc_html( $label ); ?></strong><button type="button" class="jeytech-ch-full"><?php esc_html_e( '24 hours', 'jeytech-checkout-hours' ); ?></button></div>
         <div class="jeytech-ch-ranges"><?php foreach ( $settings['schedule'][ $day ] ?: array( array() ) as $index => $row ) { self::row( $day, (string) $index, $row ); } ?></div>
         <button type="button" class="jeytech-ch-add"><?php esc_html_e( '+ Add range', 'jeytech-checkout-hours' ); ?></button>
         <template><?php self::row( $day, '__INDEX__', array() ); ?></template>
        </div>
       <?php endforeach; ?>
       <p class="jeytech-ch-help"><?php esc_html_e( 'Opening times are included; closing times are excluded. Overlapping ranges cannot be saved.', 'jeytech-checkout-hours' ); ?></p>
      </section>
      <?php submit_button( __( 'Save checkout hours', 'jeytech-checkout-hours' ) ); ?>
     </div>
     <aside class="jeytech-ch-sidebar">
      <section class="jeytech-ch-panel"><p class="jeytech-ch-eyebrow"><?php esc_html_e( 'LIVE STATUS', 'jeytech-checkout-hours' ); ?></p><h2 class="jeytech-ch-state"><?php echo esc_html( $state ); ?></h2><p><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ); ?></p><p class="jeytech-ch-help"><?php esc_html_e( 'Store timezone', 'jeytech-checkout-hours' ); ?>: <strong><?php echo esc_html( wp_timezone_string() ); ?></strong></p><?php if ( $status['message'] ) : ?><p><?php echo esc_html( $status['message'] ); ?></p><?php endif; ?><p class="jeytech-ch-help"><?php esc_html_e( 'This preview uses the saved schedule. Save your changes to update it.', 'jeytech-checkout-hours' ); ?></p></section>
      <section class="jeytech-ch-panel"><h2><?php esc_html_e( 'Storefront display', 'jeytech-checkout-hours' ); ?></h2><p><?php esc_html_e( 'Add the live status anywhere with this shortcode:', 'jeytech-checkout-hours' ); ?></p><code>[jeytech_checkout_hours]</code><p class="jeytech-ch-help"><?php esc_html_e( 'The live notice refreshes with JavaScript. Checkout is protected on the server even without it.', 'jeytech-checkout-hours' ); ?></p><p class="jeytech-ch-help"><?php esc_html_e( 'Catalog browsing and adding to cart remain available in every mode.', 'jeytech-checkout-hours' ); ?></p></section>
     </aside>
    </div>
   </form>
  </div>
  <?php
 }
}
