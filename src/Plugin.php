<?php
/** Plugin bootstrap. @package JeyTech\CheckoutHours */
namespace JeyTech\CheckoutHours;

use JeyTech\CheckoutHours\Core\Requirements;
use JeyTech\CheckoutHours\Admin\SettingsPage;

defined( 'ABSPATH' ) || exit;

/** Connect the shared schedule to WordPress and both checkout implementations. */
final class Plugin {
 /** Initialize hooks without loading translation strings early. */
 public static function boot(): void {
  if ( ! Requirements::met() ) { add_action( 'admin_notices', array( Requirements::class, 'notice' ) ); return; }
  add_action( 'admin_init', array( Settings::class, 'register' ) );
  add_filter( 'option_page_capability_' . Settings::GROUP, array( Settings::class, 'capability' ) );
  add_action( 'admin_menu', array( SettingsPage::class, 'menu' ) );
  add_action( 'admin_enqueue_scripts', array( SettingsPage::class, 'assets' ) );
  add_action( 'woocommerce_after_checkout_validation', array( Checkout::class, 'validate' ), 10, 2 );
  add_filter( 'rest_pre_dispatch', array( Checkout::class, 'store_api' ), 10, 3 );
  add_action( 'woocommerce_before_checkout_form', array( Checkout::class, 'notice' ) );
  add_action( 'rest_api_init', array( Frontend::class, 'routes' ) );
  add_action( 'wp_enqueue_scripts', array( Frontend::class, 'assets' ) );
  add_action( 'wp_body_open', array( Frontend::class, 'banner' ) );
  add_shortcode( 'jeytech_checkout_hours', array( Frontend::class, 'shortcode' ) );
  add_action( 'wp_footer', array( Frontend::class, 'checkout_notice' ), 5 );
 }
}
