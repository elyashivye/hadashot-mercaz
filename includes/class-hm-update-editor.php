<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A fully custom "add/edit update" screen — replaces wp-admin's default
 * post.php / post-new.php entirely for the update post type. One exclusive
 * content type per update (text, audio, or video), no block editor, no
 * TinyMCE, no default WordPress postbox chrome.
 */
class HM_Update_Editor {

	const PAGE_SLUG = 'hm-update-editor';

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'register_page' ) );
		add_action( 'admin_init', array( $this, 'maybe_redirect' ) );
		add_action( 'admin_post_hm_save_update', array( $this, 'handle_save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	public static function get_new_url() {
		return admin_url( 'admin.php?page=' . self::PAGE_SLUG );
	}

	public static function get_edit_url( $post_id, $saved = false ) {
		$url = add_query_arg( array(
			'page'      => self::PAGE_SLUG,
			'update_id' => absint( $post_id ),
		), admin_url( 'admin.php' ) );
		if ( $saved ) {
			$url = add_query_arg( 'saved', '1', $url );
		}
		return $url;
	}

	public function register_page() {
		// Parent slug null: hidden from the admin menu, reached only via redirects and direct links.
		add_submenu_page(
			null,
			__( 'עדכון', 'hadashot-mercaz' ),
			__( 'עדכון', 'hadashot-mercaz' ),
			'edit_posts',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Anyone who lands on the default post editor for an update — a bookmark,
	 * a stale link, a list-table row action — is bounced to our screen
	 * instead, so the default WordPress editor is never actually seen.
	 */
	public function maybe_redirect() {
		global $pagenow;

		if ( 'post-new.php' === $pagenow && isset( $_GET['post_type'] ) && HM_UPDATES_POST_TYPE === $_GET['post_type'] ) {
			wp_safe_redirect( self::get_new_url() );
			exit;
		}

		if ( 'post.php' === $pagenow && isset( $_GET['action'], $_GET['post'] ) && 'edit' === $_GET['action'] ) {
			$post = get_post( absint( $_GET['post'] ) );
			if ( $post && HM_UPDATES_POST_TYPE === $post->post_type ) {
				wp_safe_redirect( self::get_edit_url( $post->ID ) );
				exit;
			}
		}
	}

	public function enqueue( $hook ) {
		if ( ! isset( $_GET['page'] ) || self::PAGE_SLUG !== $_GET['page'] ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style( 'hm-update-editor', HM_UPDATES_URL . 'assets/css/update-editor.css', array(), HM_UPDATES_VERSION );
		wp_enqueue_script( 'hm-update-editor', HM_UPDATES_URL . 'assets/js/update-editor.js', array( 'jquery' ), HM_UPDATES_VERSION, true );
		wp_localize_script( 'hm-update-editor', 'hmEditorL10n', array(
			'chooseImage' => __( 'בחירת תמונה', 'hadashot-mercaz' ),
			'chooseAudio' => __( 'בחירת קובץ שמע', 'hadashot-mercaz' ),
			'chooseVideo' => __( 'בחירת קובץ וידאו', 'hadashot-mercaz' ),
			'select'      => __( 'בחירה', 'hadashot-mercaz' ),
			'words'       => __( 'מילים', 'hadashot-mercaz' ),
		) );
	}

	public function handle_save() {
		if ( ! isset( $_POST['hm_update_editor_nonce'] ) || ! wp_verify_nonce( $_POST['hm_update_editor_nonce'], 'hm_save_update' ) ) {
			wp_die( esc_html__( 'הפעולה פגה תוקף, נסו שוב.', 'hadashot-mercaz' ) );
		}
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'אין לך הרשאה לבצע פעולה זו.', 'hadashot-mercaz' ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		if ( $post_id && ( ! get_post( $post_id ) || HM_UPDATES_POST_TYPE !== get_post_type( $post_id ) ) ) {
			$post_id = 0;
		}
		if ( $post_id && ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'אין לך הרשאה לערוך עדכון זה.', 'hadashot-mercaz' ) );
		}

		$title = isset( $_POST['hm_title'] ) ? sanitize_text_field( wp_unslash( $_POST['hm_title'] ) ) : '';
		if ( '' === trim( $title ) ) {
			$title = __( 'עדכון ללא כותרת', 'hadashot-mercaz' );
		}

		$type = isset( $_POST['hm_update_type'] ) ? sanitize_key( wp_unslash( $_POST['hm_update_type'] ) ) : 'text';
		if ( ! in_array( $type, array( 'text', 'image', 'audio', 'video' ), true ) ) {
			$type = 'text';
		}

		$content = isset( $_POST['hm_content'] ) ? wp_kses_post( wp_unslash( $_POST['hm_content'] ) ) : '';
		$excerpt = isset( $_POST['hm_excerpt'] ) ? sanitize_text_field( wp_unslash( $_POST['hm_excerpt'] ) ) : '';
		$status  = ( isset( $_POST['hm_status'] ) && 'draft' === $_POST['hm_status'] ) ? 'draft' : 'publish';

		$postarr = array(
			'post_type'    => HM_UPDATES_POST_TYPE,
			'post_title'   => $title,
			'post_content' => 'text' === $type ? $content : '',
			'post_excerpt' => $excerpt,
			'post_status'  => $status,
		);

		if ( $post_id ) {
			$postarr['ID'] = $post_id;
			$result        = wp_update_post( $postarr, true );
		} else {
			$result = wp_insert_post( $postarr, true );
		}

		if ( is_wp_error( $result ) ) {
			wp_die( esc_html( $result->get_error_message() ) );
		}

		$post_id = $result;

		update_post_meta( $post_id, '_hm_update_type', $type );

		if ( 'audio' !== $type && 'video' !== $type ) {
			delete_post_meta( $post_id, '_hm_media_source' );
			delete_post_meta( $post_id, '_hm_media_attachment_id' );
			delete_post_meta( $post_id, '_hm_media_embed_url' );
		} else {
			// Field names are namespaced per media type (hm_audio_*/hm_video_*)
			// so the two panels never collide on submit.
			$source_key     = "hm_{$type}_source";
			$attachment_key = "hm_{$type}_attachment_id";
			$embed_key      = "hm_{$type}_embed_url";

			$source = ( isset( $_POST[ $source_key ] ) && 'embed' === $_POST[ $source_key ] ) ? 'embed' : 'upload';
			update_post_meta( $post_id, '_hm_media_source', $source );
			update_post_meta( $post_id, '_hm_media_attachment_id', isset( $_POST[ $attachment_key ] ) ? absint( $_POST[ $attachment_key ] ) : 0 );
			update_post_meta( $post_id, '_hm_media_embed_url', isset( $_POST[ $embed_key ] ) ? esc_url_raw( wp_unslash( $_POST[ $embed_key ] ) ) : '' );
		}

		$thumb_id = isset( $_POST['hm_featured_image_id'] ) ? absint( $_POST['hm_featured_image_id'] ) : 0;
		if ( $thumb_id ) {
			set_post_thumbnail( $post_id, $thumb_id );
		} else {
			delete_post_thumbnail( $post_id );
		}

		$cats = isset( $_POST['hm_categories'] ) ? array_map( 'absint', (array) $_POST['hm_categories'] ) : array();
		wp_set_object_terms( $post_id, $cats, HM_UPDATES_TAXONOMY );

		wp_safe_redirect( self::get_edit_url( $post_id, true ) );
		exit;
	}

	public function render_page() {
		$post_id = isset( $_GET['update_id'] ) ? absint( $_GET['update_id'] ) : 0;
		$post    = $post_id ? get_post( $post_id ) : null;

		if ( $post_id && ( ! $post || HM_UPDATES_POST_TYPE !== $post->post_type ) ) {
			$post    = null;
			$post_id = 0;
		}
		if ( $post_id && ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'אין לך הרשאה לערוך עדכון זה.', 'hadashot-mercaz' ) );
		}

		$type          = $post_id ? HM_Frontend::get_update_type( $post_id ) : 'text';
		$source        = $post_id ? get_post_meta( $post_id, '_hm_media_source', true ) : '';
		$source        = $source ? $source : 'upload';
		$attachment_id = $post_id ? absint( get_post_meta( $post_id, '_hm_media_attachment_id', true ) ) : 0;
		$embed_url     = $post_id ? get_post_meta( $post_id, '_hm_media_embed_url', true ) : '';
		$thumb_id      = $post_id ? absint( get_post_thumbnail_id( $post_id ) ) : 0;
		$selected_cats = $post_id ? wp_get_post_terms( $post_id, HM_UPDATES_TAXONOMY, array( 'fields' => 'ids' ) ) : array();
		$all_cats      = get_terms( array( 'taxonomy' => HM_UPDATES_TAXONOMY, 'hide_empty' => false ) );
		$is_edit       = (bool) $post_id;
		$status        = $post_id ? get_post_status( $post_id ) : 'publish';

		$attachment_url = $attachment_id ? wp_get_attachment_url( $attachment_id ) : '';
		$attachment_name = $attachment_id ? basename( get_attached_file( $attachment_id ) ) : '';
		$thumb_url      = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'medium' ) : '';

		require HM_UPDATES_DIR . 'templates/admin-update-editor.php';
	}

	/**
	 * Source toggle (upload/embed) + picker + preview, shared by the audio
	 * and video panels — identical shape, different media type.
	 */
	public function render_media_panel( $media_type, $source, $attachment_id, $attachment_url, $attachment_name, $embed_url ) {
		$upload_label = 'audio' === $media_type ? __( 'בחירת קובץ שמע', 'hadashot-mercaz' ) : __( 'בחירת קובץ וידאו', 'hadashot-mercaz' );
		$embed_hint   = 'audio' === $media_type
			? __( 'קישור ל-Spotify, SoundCloud או קובץ mp3', 'hadashot-mercaz' )
			: __( 'קישור ל-YouTube, Vimeo או קובץ mp4', 'hadashot-mercaz' );
		?>
		<div class="hm-ed-source-toggle" data-media="<?php echo esc_attr( $media_type ); ?>">
			<label class="hm-ed-chip">
				<input type="radio" name="hm_<?php echo esc_attr( $media_type ); ?>_source" value="upload" <?php checked( $source, 'upload' ); ?>>
				<span><?php esc_html_e( 'קובץ מועלה', 'hadashot-mercaz' ); ?></span>
			</label>
			<label class="hm-ed-chip">
				<input type="radio" name="hm_<?php echo esc_attr( $media_type ); ?>_source" value="embed" <?php checked( $source, 'embed' ); ?>>
				<span><?php esc_html_e( 'קישור חיצוני', 'hadashot-mercaz' ); ?></span>
			</label>
		</div>

		<div class="hm-ed-source-pane" data-source-pane="upload" <?php echo 'upload' === $source ? '' : 'hidden'; ?>>
			<input type="hidden" name="hm_<?php echo esc_attr( $media_type ); ?>_attachment_id" class="hm-ed-attachment-id" value="<?php echo esc_attr( $attachment_id ); ?>">
			<div class="hm-ed-upload-row">
				<button type="button" class="hm-ed-btn hm-ed-btn-ghost hm-ed-btn-small hm-ed-choose-media" data-media="<?php echo esc_attr( $media_type ); ?>"><?php echo esc_html( $upload_label ); ?></button>
				<span class="hm-ed-filename"><?php echo esc_html( $attachment_name ); ?></span>
			</div>
			<?php if ( $attachment_url ) : ?>
				<div class="hm-ed-media-preview">
					<?php if ( 'audio' === $media_type ) : ?>
						<audio src="<?php echo esc_url( $attachment_url ); ?>" controls></audio>
					<?php else : ?>
						<video src="<?php echo esc_url( $attachment_url ); ?>" controls muted></video>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>

		<div class="hm-ed-source-pane" data-source-pane="embed" <?php echo 'embed' === $source ? '' : 'hidden'; ?>>
			<input type="url" class="hm-ed-input" name="hm_<?php echo esc_attr( $media_type ); ?>_embed_url" value="<?php echo esc_url( $embed_url ); ?>" placeholder="<?php echo esc_attr( $embed_hint ); ?>">
		</div>
		<?php
	}
}
