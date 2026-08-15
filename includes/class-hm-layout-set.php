<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * "ערכת פריסה" – a reusable pair of right/left media slots for the split
 * layout. Managed entirely from wp-admin with a visual preview; the
 * Elementor widget only ever *selects* a set, so there is a single source
 * of truth for what's inside each slot.
 */
class HM_Layout_Set {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_' . HM_LAYOUT_SET_POST_TYPE, array( $this, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_filter( 'manage_' . HM_LAYOUT_SET_POST_TYPE . '_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_' . HM_LAYOUT_SET_POST_TYPE . '_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_filter( 'use_block_editor_for_post_type', array( $this, 'disable_block_editor' ), 10, 2 );
		add_filter( 'enter_title_here', array( $this, 'title_placeholder' ), 10, 2 );
	}

	public function register_post_type() {
		$labels = array(
			'name'               => __( 'ערכות פריסה', 'hadashot-mercaz' ),
			'singular_name'      => __( 'ערכת פריסה', 'hadashot-mercaz' ),
			'menu_name'          => __( 'ערכות פריסה', 'hadashot-mercaz' ),
			'add_new'            => __( 'הוספת ערכה', 'hadashot-mercaz' ),
			'add_new_item'       => __( 'הוספת ערכת פריסה', 'hadashot-mercaz' ),
			'edit_item'          => __( 'עריכת ערכת פריסה', 'hadashot-mercaz' ),
			'new_item'           => __( 'ערכת פריסה חדשה', 'hadashot-mercaz' ),
			'view_item'          => __( 'צפייה בערכה', 'hadashot-mercaz' ),
			'search_items'       => __( 'חיפוש ערכות', 'hadashot-mercaz' ),
			'not_found'          => __( 'לא נמצאו ערכות פריסה', 'hadashot-mercaz' ),
			'not_found_in_trash' => __( 'לא נמצאו ערכות באשפה', 'hadashot-mercaz' ),
			'all_items'          => __( 'ערכות פריסה', 'hadashot-mercaz' ),
		);

		register_post_type( HM_LAYOUT_SET_POST_TYPE, array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => 'edit.php?post_type=' . HM_UPDATES_POST_TYPE,
			'show_in_admin_bar'  => false,
			'show_in_rest'       => false,
			'has_archive'        => false,
			'rewrite'            => false,
			'query_var'          => false,
			'capability_type'    => 'post',
			'supports'           => array( 'title' ),
		) );
	}

	public function disable_block_editor( $use_block_editor, $post_type ) {
		if ( HM_LAYOUT_SET_POST_TYPE === $post_type ) {
			return false;
		}
		return $use_block_editor;
	}

	public function title_placeholder( $placeholder, $post ) {
		if ( $post && HM_LAYOUT_SET_POST_TYPE === $post->post_type ) {
			return __( 'שם הערכה, למשל: עמוד הבית', 'hadashot-mercaz' );
		}
		return $placeholder;
	}

	public function enqueue( $hook ) {
		global $post_type;
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || HM_LAYOUT_SET_POST_TYPE !== $post_type ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style( 'hm-admin', HM_UPDATES_URL . 'assets/css/admin.css', array(), HM_UPDATES_VERSION );
		wp_enqueue_style( 'hm-admin-layout-set', HM_UPDATES_URL . 'assets/css/admin-layout-set.css', array( 'hm-admin' ), HM_UPDATES_VERSION );
		wp_enqueue_script( 'hm-admin-layout-set', HM_UPDATES_URL . 'assets/js/admin-layout-set.js', array( 'jquery' ), HM_UPDATES_VERSION, true );
		wp_localize_script( 'hm-admin-layout-set', 'hmSlotL10n', array(
			'chooseImage' => __( 'בחירת תמונה', 'hadashot-mercaz' ),
			'chooseVideo' => __( 'בחירת קובץ וידאו', 'hadashot-mercaz' ),
			'select'      => __( 'בחירה', 'hadashot-mercaz' ),
		) );
	}

	public function add_meta_boxes() {
		add_meta_box(
			'hm_layout_set_slots',
			__( '🖼️ עיצוב משבצות המדיה', 'hadashot-mercaz' ),
			array( $this, 'render' ),
			HM_LAYOUT_SET_POST_TYPE,
			'normal',
			'high'
		);
	}

	public function render( $post ) {
		wp_nonce_field( 'hm_layout_set_save', 'hm_layout_set_nonce' );
		?>
		<p class="description">
			<?php esc_html_e( 'זו תצוגה מקדימה של פריסת ה"פיצול": משבצת מדיה ימנית, רשימת העדכונים הנעה (מוצגת אוטומטית), ומשבצת מדיה שמאלית. הערכה הזו ניתנת לבחירה בכל ווידג׳ט אלמנטור מסוג "פיצול".', 'hadashot-mercaz' ); ?>
		</p>
		<div class="hm-set-editor">
			<?php
			$this->render_slot( $post->ID, 'right', __( 'עמודת מדיה ימין', 'hadashot-mercaz' ) );
			?>
			<div class="hm-set-slot hm-set-slot-center">
				<div class="hm-set-slot-label"><?php esc_html_e( 'רשימת עדכונים', 'hadashot-mercaz' ); ?></div>
				<div class="hm-set-center-visual">
					<span></span><span></span><span></span><span></span>
				</div>
				<p class="hm-set-center-note"><?php esc_html_e( 'מוצגת אוטומטית מהעדכונים שנבחרו בהגדרות הווידג׳ט', 'hadashot-mercaz' ); ?></p>
			</div>
			<?php
			$this->render_slot( $post->ID, 'left', __( 'עמודת מדיה שמאל', 'hadashot-mercaz' ) );
			?>
		</div>
		<?php
	}

	private function render_slot( $post_id, $side, $label ) {
		$type       = get_post_meta( $post_id, "_hm_slot_{$side}_type", true );
		$type       = $type ? $type : 'image';
		$image_id   = absint( get_post_meta( $post_id, "_hm_slot_{$side}_image_id", true ) );
		$video_type = get_post_meta( $post_id, "_hm_slot_{$side}_video_type", true );
		$video_type = $video_type ? $video_type : 'upload';
		$video_id   = absint( get_post_meta( $post_id, "_hm_slot_{$side}_video_id", true ) );
		$video_url  = get_post_meta( $post_id, "_hm_slot_{$side}_video_url", true );
		$autoplay   = 'yes' === get_post_meta( $post_id, "_hm_slot_{$side}_autoplay", true );
		$link       = get_post_meta( $post_id, "_hm_slot_{$side}_link", true );
		?>
		<div class="hm-set-slot" data-slot="<?php echo esc_attr( $side ); ?>">
			<div class="hm-set-slot-label"><?php echo esc_html( $label ); ?></div>

			<div class="hm-set-slot-preview" data-preview="<?php echo esc_attr( $side ); ?>">
				<?php if ( 'image' === $type && $image_id ) : ?>
					<?php echo wp_get_attachment_image( $image_id, 'medium' ); ?>
				<?php elseif ( 'video' === $type && $video_id ) : ?>
					<video src="<?php echo esc_url( wp_get_attachment_url( $video_id ) ); ?>" controls muted></video>
				<?php elseif ( 'video' === $type && $video_url ) : ?>
					<div class="hm-set-slot-embed-note">🔗 <?php echo esc_html( $video_url ); ?></div>
				<?php else : ?>
					<div class="hm-set-slot-empty">📷 <?php esc_html_e( 'לחצו על "בחירה" להעלאה', 'hadashot-mercaz' ); ?></div>
				<?php endif; ?>
			</div>

			<div class="hm-radio-group hm-set-type-toggle">
				<label><input type="radio" name="hm_slot_<?php echo esc_attr( $side ); ?>_type" value="image" <?php checked( $type, 'image' ); ?>> <?php esc_html_e( 'תמונה', 'hadashot-mercaz' ); ?></label>
				<label><input type="radio" name="hm_slot_<?php echo esc_attr( $side ); ?>_type" value="video" <?php checked( $type, 'video' ); ?>> <?php esc_html_e( 'וידאו', 'hadashot-mercaz' ); ?></label>
			</div>

			<div class="hm-set-image-fields" style="<?php echo 'image' === $type ? '' : 'display:none;'; ?>">
				<input type="hidden" class="hm-attachment-id" name="hm_slot_<?php echo esc_attr( $side ); ?>_image_id" value="<?php echo esc_attr( $image_id ); ?>">
				<button type="button" class="button hm-select-slot-media" data-side="<?php echo esc_attr( $side ); ?>" data-media="image"><?php esc_html_e( 'בחירת תמונה', 'hadashot-mercaz' ); ?></button>
			</div>

			<div class="hm-set-video-fields" style="<?php echo 'video' === $type ? '' : 'display:none;'; ?>">
				<div class="hm-radio-group hm-set-video-type-toggle">
					<label><input type="radio" name="hm_slot_<?php echo esc_attr( $side ); ?>_video_type" value="upload" <?php checked( $video_type, 'upload' ); ?>> <?php esc_html_e( 'קובץ מועלה', 'hadashot-mercaz' ); ?></label>
					<label><input type="radio" name="hm_slot_<?php echo esc_attr( $side ); ?>_video_type" value="embed" <?php checked( $video_type, 'embed' ); ?>> <?php esc_html_e( 'קישור חיצוני', 'hadashot-mercaz' ); ?></label>
				</div>

				<div class="hm-set-video-upload" style="<?php echo 'upload' === $video_type ? '' : 'display:none;'; ?>">
					<input type="hidden" class="hm-attachment-id" name="hm_slot_<?php echo esc_attr( $side ); ?>_video_id" value="<?php echo esc_attr( $video_id ); ?>">
					<button type="button" class="button hm-select-slot-media" data-side="<?php echo esc_attr( $side ); ?>" data-media="video"><?php esc_html_e( 'בחירת קובץ וידאו', 'hadashot-mercaz' ); ?></button>
				</div>

				<div class="hm-set-video-embed" style="<?php echo 'embed' === $video_type ? '' : 'display:none;'; ?>">
					<input type="url" class="widefat" name="hm_slot_<?php echo esc_attr( $side ); ?>_video_url" value="<?php echo esc_url( $video_url ); ?>" placeholder="https://www.youtube.com/watch?v=...">
				</div>

				<label class="hm-set-autoplay" style="<?php echo 'upload' === $video_type ? '' : 'display:none;'; ?>">
					<input type="checkbox" name="hm_slot_<?php echo esc_attr( $side ); ?>_autoplay" value="yes" <?php checked( $autoplay ); ?>>
					<?php esc_html_e( 'הפעלה אוטומטית (ללא קול, בלולאה)', 'hadashot-mercaz' ); ?>
				</label>
				<p class="description hm-set-autoplay-note" style="<?php echo 'embed' === $video_type ? '' : 'display:none;'; ?>">
					<?php esc_html_e( 'קישורים חיצוניים תמיד מופעלים בלחיצת המשתמש בלבד.', 'hadashot-mercaz' ); ?>
				</p>
			</div>

			<input type="url" class="widefat hm-set-link-field" name="hm_slot_<?php echo esc_attr( $side ); ?>_link" value="<?php echo esc_url( $link ); ?>" placeholder="<?php esc_attr_e( 'קישור בלחיצה (אופציונלי)', 'hadashot-mercaz' ); ?>">
		</div>
		<?php
	}

	public function save( $post_id ) {
		if ( ! isset( $_POST['hm_layout_set_nonce'] ) || ! wp_verify_nonce( $_POST['hm_layout_set_nonce'], 'hm_layout_set_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		foreach ( array( 'right', 'left' ) as $side ) {
			update_post_meta( $post_id, "_hm_slot_{$side}_type", isset( $_POST[ "hm_slot_{$side}_type" ] ) ? sanitize_key( $_POST[ "hm_slot_{$side}_type" ] ) : 'image' );
			update_post_meta( $post_id, "_hm_slot_{$side}_image_id", isset( $_POST[ "hm_slot_{$side}_image_id" ] ) ? absint( $_POST[ "hm_slot_{$side}_image_id" ] ) : 0 );
			update_post_meta( $post_id, "_hm_slot_{$side}_video_type", isset( $_POST[ "hm_slot_{$side}_video_type" ] ) ? sanitize_key( $_POST[ "hm_slot_{$side}_video_type" ] ) : 'upload' );
			update_post_meta( $post_id, "_hm_slot_{$side}_video_id", isset( $_POST[ "hm_slot_{$side}_video_id" ] ) ? absint( $_POST[ "hm_slot_{$side}_video_id" ] ) : 0 );
			update_post_meta( $post_id, "_hm_slot_{$side}_video_url", isset( $_POST[ "hm_slot_{$side}_video_url" ] ) ? esc_url_raw( $_POST[ "hm_slot_{$side}_video_url" ] ) : '' );
			update_post_meta( $post_id, "_hm_slot_{$side}_autoplay", isset( $_POST[ "hm_slot_{$side}_autoplay" ] ) ? 'yes' : 'no' );
			update_post_meta( $post_id, "_hm_slot_{$side}_link", isset( $_POST[ "hm_slot_{$side}_link" ] ) ? esc_url_raw( $_POST[ "hm_slot_{$side}_link" ] ) : '' );
		}
	}

	public function columns( $columns ) {
		$new_columns = array();
		foreach ( $columns as $key => $label ) {
			$new_columns[ $key ] = $label;
			if ( 'title' === $key ) {
				$new_columns['hm_slots_preview'] = __( 'משבצות', 'hadashot-mercaz' );
			}
		}
		return $new_columns;
	}

	public function render_column( $column, $post_id ) {
		if ( 'hm_slots_preview' !== $column ) {
			return;
		}
		echo '<div class="hm-media-badges">';
		foreach ( array( 'right' => __( 'ימין', 'hadashot-mercaz' ), 'left' => __( 'שמאל', 'hadashot-mercaz' ) ) as $side => $side_label ) {
			$slot = self::get_slot( $post_id, $side );
			if ( ! $slot ) {
				printf( '<span class="hm-badge-empty" title="%s">—</span>', esc_attr( $side_label ) );
				continue;
			}
			$icon = 'video' === $slot['type'] ? 'dashicons-format-video' : 'dashicons-format-image';
			printf(
				'<span class="hm-badge hm-badge-video dashicons %s" title="%s"></span>',
				esc_attr( $icon ),
				esc_attr( $side_label . ': ' . ( 'video' === $slot['type'] ? __( 'וידאו', 'hadashot-mercaz' ) : __( 'תמונה', 'hadashot-mercaz' ) ) )
			);
		}
		echo '</div>';
	}

	/* ------------------------------------------------------------------ */
	/*  Public helpers consumed by the Elementor widget / templates       */
	/* ------------------------------------------------------------------ */

	public static function get_all() {
		return get_posts( array(
			'post_type'      => HM_LAYOUT_SET_POST_TYPE,
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'post_status'    => 'publish',
		) );
	}

	/**
	 * Resolved slot data ready for rendering, or null when the slot is empty.
	 */
	public static function get_slot( $set_id, $side ) {
		$set_id = absint( $set_id );
		if ( ! $set_id ) {
			return null;
		}

		$type = get_post_meta( $set_id, "_hm_slot_{$side}_type", true );
		$type = $type ? $type : 'image';

		$data = array(
			'type'     => $type,
			'autoplay' => false,
			'is_embed' => false,
			'link'     => get_post_meta( $set_id, "_hm_slot_{$side}_link", true ),
			'url'      => '',
		);

		if ( 'image' === $type ) {
			$image_id = absint( get_post_meta( $set_id, "_hm_slot_{$side}_image_id", true ) );
			if ( ! $image_id ) {
				return null;
			}
			$data['url'] = wp_get_attachment_image_url( $image_id, 'large' );
			return $data['url'] ? $data : null;
		}

		$video_type = get_post_meta( $set_id, "_hm_slot_{$side}_video_type", true );

		if ( 'upload' === $video_type ) {
			$video_id = absint( get_post_meta( $set_id, "_hm_slot_{$side}_video_id", true ) );
			if ( ! $video_id ) {
				return null;
			}
			$data['url']      = wp_get_attachment_url( $video_id );
			$data['autoplay'] = 'yes' === get_post_meta( $set_id, "_hm_slot_{$side}_autoplay", true );
			return $data['url'] ? $data : null;
		}

		$url = get_post_meta( $set_id, "_hm_slot_{$side}_video_url", true );
		if ( ! $url ) {
			return null;
		}
		$data['url']      = $url;
		$data['is_embed'] = true;
		return $data;
	}

	public static function render_admin_preview_html( $set_id ) {
		$right     = self::get_slot( $set_id, 'right' );
		$left      = self::get_slot( $set_id, 'left' );
		$edit_url  = get_edit_post_link( $set_id );

		ob_start();
		?>
		<div class="hm-panel-slot-cards">
			<?php foreach ( array( 'right' => array( __( 'עמודת מדיה ימין', 'hadashot-mercaz' ), $right ), 'left' => array( __( 'עמודת מדיה שמאל', 'hadashot-mercaz' ), $left ) ) as $side => $pair ) : ?>
				<?php list( $label, $slot ) = $pair; ?>
				<div class="hm-panel-slot-card">
					<div class="hm-panel-slot-card-title"><?php echo esc_html( $label ); ?></div>
					<div class="hm-panel-slot-card-body">
						<?php if ( ! $slot ) : ?>
							<span class="hm-panel-slot-empty"><?php esc_html_e( 'ריק', 'hadashot-mercaz' ); ?></span>
						<?php elseif ( 'image' === $slot['type'] ) : ?>
							<img src="<?php echo esc_url( $slot['url'] ); ?>" alt="">
							<span><?php esc_html_e( 'תמונה', 'hadashot-mercaz' ); ?></span>
						<?php else : ?>
							<span class="hm-panel-slot-video-icon">▶</span>
							<span>
								<?php
								if ( $slot['is_embed'] ) {
									esc_html_e( 'וידאו (קישור) — בלחיצה', 'hadashot-mercaz' );
								} elseif ( $slot['autoplay'] ) {
									esc_html_e( 'וידאו — אוטומטי', 'hadashot-mercaz' );
								} else {
									esc_html_e( 'וידאו — בלחיצה', 'hadashot-mercaz' );
								}
								?>
							</span>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
			<a class="hm-panel-slot-edit-link" href="<?php echo esc_url( $edit_url ); ?>" target="_blank" rel="noopener">
				<?php esc_html_e( '✎ עריכת תוכן הערכה', 'hadashot-mercaz' ); ?>
			</a>
		</div>
		<?php
		return ob_get_clean();
	}
}
