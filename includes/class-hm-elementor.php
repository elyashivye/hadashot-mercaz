<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the Elementor widget category and the widget itself.
 */
class HM_Elementor {

	const CATEGORY = 'hadashot-mercaz';

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_notices', array( $this, 'maybe_show_missing_elementor_notice' ) );
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
		add_action( 'elementor/editor/after_enqueue_styles', array( $this, 'enqueue_editor_styles' ) );
	}

	public function enqueue_editor_styles() {
		wp_enqueue_style( 'hm-updates-editor', HM_UPDATES_URL . 'assets/css/editor.css', array(), HM_UPDATES_VERSION );
	}

	public function is_elementor_active() {
		return did_action( 'elementor/loaded' ) || class_exists( '\Elementor\Plugin' );
	}

	public function maybe_show_missing_elementor_notice() {
		if ( $this->is_elementor_active() ) {
			return;
		}
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		echo '<div class="notice notice-warning"><p>' .
			esc_html__( 'התוסף "עדכוני חדשות מקצועיים" זקוק לתוסף Elementor פעיל כדי להציג את ווידג׳ט העדכונים.', 'hadashot-mercaz' ) .
			'</p></div>';
	}

	public function register_category( $elements_manager ) {
		$elements_manager->add_category(
			self::CATEGORY,
			array(
				'title' => __( 'חדשות מרכז', 'hadashot-mercaz' ),
				'icon'  => 'fa fa-newspaper',
			)
		);
	}

	public function register_widgets( $widgets_manager ) {
		if ( ! $this->is_elementor_active() ) {
			return;
		}
		require_once HM_UPDATES_DIR . 'elementor/class-hm-widget-updates.php';
		$widgets_manager->register( new \HM_Widget_Updates() );
	}
}
