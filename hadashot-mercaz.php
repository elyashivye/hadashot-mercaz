<?php
/**
 * Plugin Name: חדשות מרכז – עדכוני חדשות מקצועיים
 * Description: ניהול עדכוני חדשות מקצועיים (טקסט, תמונה, אודיו ווידאו) עם ווידג'ט אלמנטור מלא עיצוב לתצוגה במגוון פריסות.
 * Version: 1.1.0
 * Author: Hadashot Mercaz
 * Text Domain: hadashot-mercaz
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HM_UPDATES_VERSION', '1.1.0' );
define( 'HM_UPDATES_FILE', __FILE__ );
define( 'HM_UPDATES_DIR', plugin_dir_path( __FILE__ ) );
define( 'HM_UPDATES_URL', plugin_dir_url( __FILE__ ) );
define( 'HM_UPDATES_POST_TYPE', 'hm_update' );
define( 'HM_UPDATES_TAXONOMY', 'hm_update_cat' );
define( 'HM_LAYOUT_SET_POST_TYPE', 'hm_layout_set' );

require_once HM_UPDATES_DIR . 'includes/class-hm-post-type.php';
require_once HM_UPDATES_DIR . 'includes/class-hm-layout-set.php';
require_once HM_UPDATES_DIR . 'includes/class-hm-metaboxes.php';
require_once HM_UPDATES_DIR . 'includes/class-hm-admin.php';
require_once HM_UPDATES_DIR . 'includes/class-hm-frontend.php';
require_once HM_UPDATES_DIR . 'includes/class-hm-elementor.php';

/**
 * Main plugin bootstrap.
 */
final class Hadashot_Mercaz_Updates {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'load_textdomain' ) );

		HM_Post_Type::instance();
		HM_Layout_Set::instance();
		HM_Metaboxes::instance();
		HM_Admin::instance();
		HM_Frontend::instance();
		HM_Elementor::instance();
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'hadashot-mercaz', false, dirname( plugin_basename( HM_UPDATES_FILE ) ) . '/languages' );
	}
}

function hadashot_mercaz_updates() {
	return Hadashot_Mercaz_Updates::instance();
}

register_activation_hook( __FILE__, function () {
	HM_Post_Type::instance()->register_post_type();
	HM_Post_Type::instance()->register_taxonomy();
	HM_Layout_Set::instance()->register_post_type();
	flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, function () {
	flush_rewrite_rules();
} );

hadashot_mercaz_updates();
