<?php
/**
 * Plugin Name:          JeyTech Checkout Hours for WooCommerce
 * Description:          Set weekly checkout hours while keeping your catalog and cart available. Supports classic checkout and Checkout Blocks.
 * Version:              1.0.0
 * Requires at least:    6.6
 * Requires PHP:         7.4
 * Requires Plugins:     woocommerce
 * Author:               JeyTech
 * Author URI:           https://jeytech.app
 * License:              GPL-2.0-or-later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:          jeytech-checkout-hours
 * WC requires at least: 9.6
 * WC tested up to:      11.1
 *
 * @package JeyTech\CheckoutHours
 */

defined( 'ABSPATH' ) || exit;

define( 'JEYTECH_CH_VERSION', '1.0.0' );
define( 'JEYTECH_CH_FILE', __FILE__ );
define( 'JEYTECH_CH_PATH', plugin_dir_path( __FILE__ ) );
require_once JEYTECH_CH_PATH . 'src/Core/Autoloader.php';
\JeyTech\CheckoutHours\Core\Autoloader::register( 'JeyTech\\CheckoutHours\\', JEYTECH_CH_PATH . 'src/' );
add_action( 'before_woocommerce_init', array( \JeyTech\CheckoutHours\Core\Compat::class, 'declare_compatibility' ) );
add_action( 'plugins_loaded', array( \JeyTech\CheckoutHours\Plugin::class, 'boot' ) );
