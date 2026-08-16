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
		add_action( 'elementor/document/after_save', array( $this, 'sync_layout_sets_on_save' ), 10, 2 );
	}

	/**
	 * Whenever an Elementor page is saved, mirror any linked split-layout
	 * widget's live slot settings into its Layout Set post — the accordion
	 * in the widget panel is the editing surface, this is what keeps other
	 * pages using the same set (and the wp-admin screen) in sync.
	 */
	public function sync_layout_sets_on_save( $document, $data ) {
		if ( empty( $data['elements'] ) || ! is_array( $data['elements'] ) || ! class_exists( 'HM_Layout_Set' ) ) {
			return;
		}
		$this->walk_elements_for_sync( $data['elements'] );
	}

	private function walk_elements_for_sync( array $elements ) {
		foreach ( $elements as $element ) {
			if ( ! empty( $element['widgetType'] ) && 'hm-updates' === $element['widgetType'] && ! empty( $element['settings'] ) ) {
				$this->maybe_sync_widget_settings( $element['settings'] );
			}
			if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
				$this->walk_elements_for_sync( $element['elements'] );
			}
		}
	}

	private function maybe_sync_widget_settings( array $settings ) {
		if ( empty( $settings['layout'] ) || 'split' !== $settings['layout'] ) {
			return;
		}
		$set_id = ! empty( $settings['layout_set_id'] ) ? absint( $settings['layout_set_id'] ) : 0;
		if ( ! $set_id || HM_LAYOUT_SET_POST_TYPE !== get_post_type( $set_id ) ) {
			return;
		}
		foreach ( array( 'right', 'left' ) as $side ) {
			HM_Layout_Set::save_slot( $set_id, $side, $this->extract_slot_values( $settings, $side ) );
		}
	}

	private function extract_slot_values( array $settings, $side ) {
		return array(
			'type'       => ! empty( $settings[ "slot_{$side}_type" ] ) ? $settings[ "slot_{$side}_type" ] : 'image',
			'image_id'   => ! empty( $settings[ "slot_{$side}_image" ]['id'] ) ? $settings[ "slot_{$side}_image" ]['id'] : 0,
			'video_type' => ! empty( $settings[ "slot_{$side}_video_source" ] ) ? $settings[ "slot_{$side}_video_source" ] : 'upload',
			'video_id'   => ! empty( $settings[ "slot_{$side}_video" ]['id'] ) ? $settings[ "slot_{$side}_video" ]['id'] : 0,
			'video_url'  => ! empty( $settings[ "slot_{$side}_video_embed_url" ] ) ? $settings[ "slot_{$side}_video_embed_url" ] : '',
			'autoplay'   => ! empty( $settings[ "slot_{$side}_autoplay" ] ) && 'yes' === $settings[ "slot_{$side}_autoplay" ],
			'link'       => ! empty( $settings[ "slot_{$side}_link" ]['url'] ) ? $settings[ "slot_{$side}_link" ]['url'] : '',
		);
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
