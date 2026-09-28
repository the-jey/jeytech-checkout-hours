<?php
/** Local-only browser fixture with intercepted mail and a counted offline gateway. */
defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/lib.php';
wp_mkdir_p( WPMU_PLUGIN_DIR );
file_put_contents( WPMU_PLUGIN_DIR . '/ch-demo.php', <<<'PHP'
<?php
add_filter( 'pre_wp_mail', '__return_true' );
add_action( 'plugins_loaded', function () {
 if ( ! class_exists( 'WC_Payment_Gateway' ) ) { return; }
 class JeyTech_CH_Demo_Gateway extends WC_Gateway_BACS {
  public function process_payment( $order_id ) {
   update_option( 'jeytech_ch_gateway_calls', (int) get_option( 'jeytech_ch_gateway_calls', 0 ) + 1 );
   return parent::process_payment( $order_id );
  }
 }
 add_filter( 'woocommerce_payment_gateways', function ( $gateways ) {
  foreach ( $gateways as $key => $gateway ) { if ( 'WC_Gateway_BACS' === $gateway ) { $gateways[$key] = 'JeyTech_CH_Demo_Gateway'; } }
  return $gateways;
 } );
});
add_action( 'rest_api_init', function () {
 require_once '/wordpress/wp-content/ch-dev/lib.php';
 register_rest_route( 'jeytech-ch-dev/v1', '/state', array(
  'methods' => array( 'GET', 'POST' ), 'permission_callback' => '__return_true',
  'callback' => function ( $request ) {
   if ( 'POST' === $request->get_method() ) {
    $state = $request->get_param( 'state' );
    if ( ! in_array( $state, array( 'closed', 'open', 'warn', 'disabled' ), true ) ) { return new WP_Error( 'invalid', 'Invalid fixture', array( 'status' => 400 ) ); }
    update_option( \JeyTech\CheckoutHours\Settings::OPTION, jeytech_ch_dev_settings( $state ) );
   }
   return jeytech_ch_dev_metrics();
  }
 ) );
});
PHP
);
$fr = 'fr_FR' === get_locale(); $tag = $fr ? 'fr' : 'en';
update_option( 'blogname', $fr ? 'Boutique de démonstration JeyTech' : 'JeyTech demo shop' );
update_option( 'timezone_string', 'Europe/Paris' );
update_option( 'date_format', $fr ? 'j F Y' : 'F j, Y' );
update_option( 'time_format', $fr ? 'H\\hi' : 'g:i a' );
update_option( 'woocommerce_price_decimal_sep', $fr ? ',' : '.' );
update_option( 'woocommerce_price_thousand_sep', $fr ? ' ' : ',' );
update_option( 'woocommerce_currency_pos', $fr ? 'right_space' : 'left' );
update_option( 'woocommerce_currency', 'EUR' );
update_option( 'woocommerce_default_country', 'FR' );
update_option( 'woocommerce_store_address', '12 rue de la Démo' );
update_option( 'woocommerce_store_city', 'Paris' );
update_option( 'woocommerce_store_postcode', '75001' );
update_option( 'woocommerce_enable_guest_checkout', 'yes' );
update_option( 'woocommerce_coming_soon', 'no' );
update_option( 'woocommerce_enable_signup_and_login_from_checkout', 'no' );
update_option( 'woocommerce_bacs_settings', array( 'enabled' => 'yes', 'title' => $fr ? 'Virement bancaire' : 'Bank transfer', 'description' => $fr ? 'Paiement de démonstration, sans transfert.' : 'Demo payment; no transfer is sent.' ) );
WC_Install::create_pages();
update_option( 'permalink_structure', '/%postname%/' ); flush_rewrite_rules();
$product = new WC_Product_Simple(); $product->set_name( $fr ? 'Carnet de démonstration JeyTech' : 'JeyTech demo notebook' ); $product->set_regular_price( '19.90' ); $product->set_virtual( true ); $product->set_manage_stock( true ); $product->set_stock_quantity( 100 ); $product->set_status( 'publish' ); $product->save();
update_option( 'jeytech_ch_demo_product', $product->get_id() );
$positive = new WC_Product_Simple(); $positive->set_name( $fr ? 'Carnet de démonstration JeyTech' : 'JeyTech demo notebook' ); $positive->set_regular_price( '19.90' ); $positive->set_virtual( true ); $positive->set_manage_stock( false ); $positive->set_status( 'publish' ); $positive->save();
update_option( \JeyTech\CheckoutHours\Settings::OPTION, jeytech_ch_dev_settings( 'closed' ) );
$classic = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $fr ? 'Commande classique' : 'Classic checkout', 'post_name' => 'classic-checkout', 'post_content' => '[woocommerce_checkout]' ) );
$shortcode = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Live hours', 'post_name' => 'hours', 'post_content' => '[jeytech_checkout_hours]' ) );
file_put_contents( __DIR__ . '/.demo-' . $tag . '.json', wp_json_encode( array( 'locale' => get_locale(), 'productId' => $product->get_id(), 'positiveProductId' => $positive->get_id(), 'classicCheckout' => get_permalink( $classic ), 'checkout' => wc_get_checkout_url(), 'shortcode' => get_permalink( $shortcode ), 'versions' => array( 'wp' => get_bloginfo( 'version' ), 'wc' => WC_VERSION, 'php' => PHP_VERSION ), 'storage' => get_option( 'woocommerce_custom_orders_table_enabled' ) ), JSON_UNESCAPED_UNICODE ) );
