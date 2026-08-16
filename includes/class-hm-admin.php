<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin UI: list table columns, dashboard widget, help screen, styling.
 * Creating/editing an update itself happens on HM_Update_Editor's custom
 * screen, not on the default post.php.
 */
class HM_Admin {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_filter( 'manage_' . HM_UPDATES_POST_TYPE . '_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_' . HM_UPDATES_POST_TYPE . '_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_action( 'wp_dashboard_setup', array( $this, 'dashboard_widget' ) );
		add_action( 'admin_menu', array( $this, 'add_help_page' ) );
	}

	public function enqueue( $hook ) {
		$screen = get_current_screen();
		if ( $screen && ( HM_UPDATES_POST_TYPE === $screen->post_type || 'dashboard' === $screen->id || false !== strpos( $screen->id, 'hm-updates-guide' ) ) ) {
			wp_enqueue_style( 'hm-admin', HM_UPDATES_URL . 'assets/css/admin.css', array(), HM_UPDATES_VERSION );
		}
	}

	public function columns( $columns ) {
		$new_columns = array();
		foreach ( $columns as $key => $label ) {
			$new_columns[ $key ] = $label;
			if ( 'title' === $key ) {
				$new_columns['hm_thumb']  = __( 'תמונה', 'hadashot-mercaz' );
			}
		}
		$new_columns['hm_type'] = __( 'סוג', 'hadashot-mercaz' );
		unset( $new_columns['date'] );
		$new_columns['date'] = __( 'תאריך', 'hadashot-mercaz' );
		return $new_columns;
	}

	public function render_column( $column, $post_id ) {
		switch ( $column ) {
			case 'hm_thumb':
				if ( has_post_thumbnail( $post_id ) ) {
					echo get_the_post_thumbnail( $post_id, array( 50, 50 ), array( 'style' => 'border-radius:6px;object-fit:cover;' ) );
				} else {
					echo '<span class="hm-no-thumb dashicons dashicons-format-image"></span>';
				}
				break;

			case 'hm_type':
				$type = HM_Frontend::get_update_type( $post_id );
				$map  = array(
					'text'  => array( 'dashicons-text-page', __( 'טקסט', 'hadashot-mercaz' ), 'hm-badge-text' ),
					'image' => array( 'dashicons-format-image', __( 'תמונה', 'hadashot-mercaz' ), 'hm-badge-image' ),
					'audio' => array( 'dashicons-format-audio', __( 'אודיו', 'hadashot-mercaz' ), 'hm-badge-audio' ),
					'video' => array( 'dashicons-format-video', __( 'וידאו', 'hadashot-mercaz' ), 'hm-badge-video' ),
				);
				$info = isset( $map[ $type ] ) ? $map[ $type ] : $map['text'];
				printf(
					'<span class="hm-badge %s dashicons %s" title="%s"></span> %s',
					esc_attr( $info[2] ),
					esc_attr( $info[0] ),
					esc_attr( $info[1] ),
					esc_html( $info[1] )
				);
				break;
		}
	}

	public function dashboard_widget() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}
		wp_add_dashboard_widget( 'hm_updates_dashboard', __( 'עדכוני חדשות אחרונים', 'hadashot-mercaz' ), array( $this, 'render_dashboard_widget' ) );
	}

	public function render_dashboard_widget() {
		$updates = get_posts( array(
			'post_type'      => HM_UPDATES_POST_TYPE,
			'posts_per_page' => 5,
			'post_status'    => array( 'publish', 'draft', 'pending' ),
		) );

		echo '<div class="hm-dashboard-widget">';
		if ( empty( $updates ) ) {
			echo '<p>' . esc_html__( 'עדיין אין עדכונים. בואו ניצור את הראשון!', 'hadashot-mercaz' ) . '</p>';
		} else {
			echo '<ul class="hm-dashboard-list">';
			foreach ( $updates as $update ) {
				printf(
					'<li><a href="%s">%s</a><span class="hm-dash-date">%s</span></li>',
					esc_url( HM_Update_Editor::get_edit_url( $update->ID ) ),
					esc_html( get_the_title( $update ) ),
					esc_html( get_the_date( '', $update ) )
				);
			}
			echo '</ul>';
		}
		printf(
			'<p class="hm-dashboard-actions"><a class="button button-primary" href="%s">%s</a> <a class="button" href="%s">%s</a></p>',
			esc_url( HM_Update_Editor::get_new_url() ),
			esc_html__( 'עדכון חדש', 'hadashot-mercaz' ),
			esc_url( admin_url( 'edit.php?post_type=' . HM_UPDATES_POST_TYPE ) ),
			esc_html__( 'לכל העדכונים', 'hadashot-mercaz' )
		);
		echo '</div>';
	}

	public function add_help_page() {
		add_submenu_page(
			'edit.php?post_type=' . HM_UPDATES_POST_TYPE,
			__( 'מדריך שימוש', 'hadashot-mercaz' ),
			__( 'מדריך שימוש', 'hadashot-mercaz' ),
			'edit_posts',
			'hm-updates-guide',
			array( $this, 'render_help_page' )
		);
	}

	public function render_help_page() {
		?>
		<div class="wrap hm-guide-wrap">
			<h1><?php esc_html_e( 'מדריך שימוש – עדכוני חדשות מקצועיים', 'hadashot-mercaz' ); ?></h1>

			<div class="hm-guide-grid">
				<div class="hm-guide-card">
					<div class="hm-guide-icon dashicons dashicons-megaphone"></div>
					<h2><?php esc_html_e( '1. יצירת עדכונים', 'hadashot-mercaz' ); ?></h2>
					<p><?php esc_html_e( 'עברו ל"כל העדכונים" ולחצו על "הוספת עדכון". בוחרים סוג עדכון אחד — טקסט, אודיו או וידאו — ומזינים רק את מה שרלוונטי. מסך קליל אחד, בלי בונה עמודים ובלי בלוקים.', 'hadashot-mercaz' ); ?></p>
				</div>
				<div class="hm-guide-card">
					<div class="hm-guide-icon dashicons dashicons-category"></div>
					<h2><?php esc_html_e( '2. קטגוריות', 'hadashot-mercaz' ); ?></h2>
					<p><?php esc_html_e( 'ניתן לסווג עדכונים לקטגוריות (בדומה לקטגוריות פוסטים), וכך לבחור בעורך אלמנטור אילו קטגוריות להציג בכל ווידג׳ט.', 'hadashot-mercaz' ); ?></p>
				</div>
				<div class="hm-guide-card">
					<div class="hm-guide-icon dashicons dashicons-images-alt2"></div>
					<h2><?php esc_html_e( '3. ערכות פריסה (למשבצות פריסת הפיצול)', 'hadashot-mercaz' ); ?></h2>
					<p><?php esc_html_e( 'תחת "ערכות פריסה" תראו תצוגה מקדימה אמיתית של הפריסה: עמודת מדיה ימין, רשימת העדכונים באמצע, עמודת מדיה שמאל. בכל עמודה בוחרים תמונה או וידאו, ולווידאו קובעים הפעלה אוטומטית או בלחיצה בלבד. אותה ערכה נבחרת מתוך הווידג׳ט באלמנטור, כך שאין כפילות – עדכון כאן משתקף שם מיד.', 'hadashot-mercaz' ); ?></p>
				</div>
				<div class="hm-guide-card">
					<div class="hm-guide-icon dashicons dashicons-admin-links"></div>
					<h2><?php esc_html_e( '4. הטמעת ווידג\'ט התצוגה', 'hadashot-mercaz' ); ?></h2>
					<p><?php esc_html_e( 'פתחו עמוד לעריכה באלמנטור, חפשו את הווידג׳ט "עדכוני חדשות מקצועיים" בקטגוריית "חדשות מרכז", וגררו אותו לעמוד.', 'hadashot-mercaz' ); ?></p>
				</div>
				<div class="hm-guide-card">
					<div class="hm-guide-icon dashicons dashicons-layout"></div>
					<h2><?php esc_html_e( '5. בחירת פריסה', 'hadashot-mercaz' ); ?></h2>
					<p><?php esc_html_e( 'בלשונית "תוכן" בוחרים פריסה: פיצול עם ערכת פריסה ורשימה נעה, טיקר/רשימה, גריד כרטיסיות או קרוסלה. לכל פריסה יש הגדרות ייעודיות, ובכל כרטיס בפאנל כתוב בדיוק מה עורכים.', 'hadashot-mercaz' ); ?></p>
				</div>
				<div class="hm-guide-card">
					<div class="hm-guide-icon dashicons dashicons-controls-play"></div>
					<h2><?php esc_html_e( '6. קצב הגלילה', 'hadashot-mercaz' ); ?></h2>
					<p><?php esc_html_e( 'תחת "הגדרות תזוזה" קובעים כמה שניות כל עדכון מוצג, כיוון התנועה, והאם לעצור בעת מעבר עכבר.', 'hadashot-mercaz' ); ?></p>
				</div>
				<div class="hm-guide-card">
					<div class="hm-guide-icon dashicons dashicons-admin-appearance"></div>
					<h2><?php esc_html_e( '7. עיצוב מלא', 'hadashot-mercaz' ); ?></h2>
					<p><?php esc_html_e( 'כל הצבעים, הפונטים, המרווחים והעיצוב של הפופ-אפ נשלטים מלשונית "עיצוב" בווידג׳ט עצמו – ללא צורך בקוד.', 'hadashot-mercaz' ); ?></p>
				</div>
			</div>
		</div>
		<?php
	}
}
