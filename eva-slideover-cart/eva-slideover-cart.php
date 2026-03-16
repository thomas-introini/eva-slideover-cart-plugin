<?php
/**
 * Plugin Name:       Eva Slideover Cart
 * Plugin URI:        https://github.com/your-org/eva-slideover-cart
 * Description:       A UI-only slideover (drawer) cart for WooCommerce. Replaces the theme mini-cart without touching WooCommerce checkout/order logic.
 * Version:           1.1.1
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Thomas Introini
 * Author URI:        https://example.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       eva-slideover-cart
 * Domain Path:       /languages
 * WC requires at least: 7.0
 * WC tested up to:   10.0
 */

defined( 'ABSPATH' ) || exit;

define( 'EVA_SC_VERSION', '1.1.1' );
define( 'EVA_SC_PATH', plugin_dir_path( __FILE__ ) );
define( 'EVA_SC_URL', plugin_dir_url( __FILE__ ) );
define( 'EVA_SC_BASENAME', plugin_basename( __FILE__ ) );

require_once EVA_SC_PATH . 'includes/helpers.php';
require_once EVA_SC_PATH . 'includes/class-free-shipping.php';
require_once EVA_SC_PATH . 'includes/class-settings.php';
require_once EVA_SC_PATH . 'includes/class-assets.php';
require_once EVA_SC_PATH . 'includes/class-fragments.php';
require_once EVA_SC_PATH . 'includes/class-ajax.php';
require_once EVA_SC_PATH . 'includes/class-render.php';
require_once EVA_SC_PATH . 'includes/class-plugin.php';

EVA_SC_Plugin::instance();
