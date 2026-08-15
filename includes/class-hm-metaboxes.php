<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Audio / video meta box for the update post type.
 */
class HM_Metaboxes {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_' . HM_UPDATES_POST_TYPE, array( $this, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	public function enqueue( $hook ) {
		global $post_type;
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || HM_UPDATES_POST_TYPE !== $post_type ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style( 'hm-admin', HM_UPDATES_URL . 'assets/css/admin.css', array(), HM_UPDATES_VERSION );
		wp_enqueue_script( 'hm-admin', HM_UPDATES_URL . 'assets/js/admin.js', array( 'jquery' ), HM_UPDATES_VERSION, true );
		wp_localize_script( 'hm-admin', 'hmMediaL10n', array(
			'chooseAudio' => __( 'בחירת קובץ שמע', 'hadashot-mercaz' ),
			'chooseVideo' => __( 'בחירת קובץ וידאו', 'hadashot-mercaz' ),
			'select'      => __( 'בחירה', 'hadashot-mercaz' ),
		) );
	}

	public function add_meta_boxes() {
		add_meta_box(
			'hm_media_meta',
			__( '🎧 מדיה: אודיו ווידאו', 'hadashot-mercaz' ),
			array( $this, 'render' ),
			HM_UPDATES_POST_TYPE,
			'normal',
			'high'
		);
	}

	public function render( $post ) {
		wp_nonce_field( 'hm_media_meta_save', 'hm_media_meta_nonce' );

		$audio_type  = get_post_meta( $post->ID, '_hm_audio_type', true ) ?: 'none';
		$audio_id    = get_post_meta( $post->ID, '_hm_audio_attachment_id', true );
		$audio_url   = get_post_meta( $post->ID, '_hm_audio_embed_url', true );
		$video_type  = get_post_meta( $post->ID, '_hm_video_type', true ) ?: 'none';
		$video_id    = get_post_meta( $post->ID, '_hm_video_attachment_id', true );
		$video_url   = get_post_meta( $post->ID, '_hm_video_embed_url', true );

		$audio_filename = $audio_id ? basename( get_attached_file( $audio_id ) ) : '';
		$video_filename = $video_id ? basename( get_attached_file( $video_id ) ) : '';
		?>
		<div class="hm-metabox">
			<div class="hm-media-block" data-media="audio">
				<h3><?php esc_html_e( 'עדכון אודיו', 'hadashot-mercaz' ); ?></h3>
				<div class="hm-radio-group">
					<label><input type="radio" name="hm_audio_type" value="none" <?php checked( $audio_type, 'none' ); ?>> <?php esc_html_e( 'ללא', 'hadashot-mercaz' ); ?></label>
					<label><input type="radio" name="hm_audio_type" value="upload" <?php checked( $audio_type, 'upload' ); ?>> <?php esc_html_e( 'קובץ מועלה', 'hadashot-mercaz' ); ?></label>
					<label><input type="radio" name="hm_audio_type" value="embed" <?php checked( $audio_type, 'embed' ); ?>> <?php esc_html_e( 'קישור חיצוני', 'hadashot-mercaz' ); ?></label>
				</div>

				<div class="hm-media-field hm-media-upload" style="<?php echo 'upload' === $audio_type ? '' : 'display:none;'; ?>">
					<input type="hidden" name="hm_audio_attachment_id" value="<?php echo esc_attr( $audio_id ); ?>" class="hm-attachment-id">
					<button type="button" class="button hm-select-media" data-target="audio"><?php esc_html_e( 'בחירת קובץ שמע מהמדיה', 'hadashot-mercaz' ); ?></button>
					<span class="hm-filename"><?php echo esc_html( $audio_filename ); ?></span>
				</div>

				<div class="hm-media-field hm-media-embed" style="<?php echo 'embed' === $audio_type ? '' : 'display:none;'; ?>">
					<input type="url" class="widefat" name="hm_audio_embed_url" placeholder="https://open.spotify.com/... או קישור לקובץ mp3" value="<?php echo esc_url( $audio_url ); ?>">
				</div>
			</div>

			<hr>

			<div class="hm-media-block" data-media="video">
				<h3><?php esc_html_e( 'עדכון וידאו', 'hadashot-mercaz' ); ?></h3>
				<div class="hm-radio-group">
					<label><input type="radio" name="hm_video_type" value="none" <?php checked( $video_type, 'none' ); ?>> <?php esc_html_e( 'ללא', 'hadashot-mercaz' ); ?></label>
					<label><input type="radio" name="hm_video_type" value="upload" <?php checked( $video_type, 'upload' ); ?>> <?php esc_html_e( 'קובץ מועלה', 'hadashot-mercaz' ); ?></label>
					<label><input type="radio" name="hm_video_type" value="embed" <?php checked( $video_type, 'embed' ); ?>> <?php esc_html_e( 'קישור חיצוני (YouTube וכו׳)', 'hadashot-mercaz' ); ?></label>
				</div>

				<div class="hm-media-field hm-media-upload" style="<?php echo 'upload' === $video_type ? '' : 'display:none;'; ?>">
					<input type="hidden" name="hm_video_attachment_id" value="<?php echo esc_attr( $video_id ); ?>" class="hm-attachment-id">
					<button type="button" class="button hm-select-media" data-target="video"><?php esc_html_e( 'בחירת קובץ וידאו מהמדיה', 'hadashot-mercaz' ); ?></button>
					<span class="hm-filename"><?php echo esc_html( $video_filename ); ?></span>
				</div>

				<div class="hm-media-field hm-media-embed" style="<?php echo 'embed' === $video_type ? '' : 'display:none;'; ?>">
					<input type="url" class="widefat" name="hm_video_embed_url" placeholder="https://www.youtube.com/watch?v=..." value="<?php echo esc_url( $video_url ); ?>">
				</div>
			</div>
		</div>
		<?php
	}

	public function save( $post_id ) {
		if ( ! isset( $_POST['hm_media_meta_nonce'] ) || ! wp_verify_nonce( $_POST['hm_media_meta_nonce'], 'hm_media_meta_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$audio_type = isset( $_POST['hm_audio_type'] ) ? sanitize_key( $_POST['hm_audio_type'] ) : 'none';
		update_post_meta( $post_id, '_hm_audio_type', $audio_type );
		update_post_meta( $post_id, '_hm_audio_attachment_id', isset( $_POST['hm_audio_attachment_id'] ) ? absint( $_POST['hm_audio_attachment_id'] ) : 0 );
		update_post_meta( $post_id, '_hm_audio_embed_url', isset( $_POST['hm_audio_embed_url'] ) ? esc_url_raw( $_POST['hm_audio_embed_url'] ) : '' );

		$video_type = isset( $_POST['hm_video_type'] ) ? sanitize_key( $_POST['hm_video_type'] ) : 'none';
		update_post_meta( $post_id, '_hm_video_type', $video_type );
		update_post_meta( $post_id, '_hm_video_attachment_id', isset( $_POST['hm_video_attachment_id'] ) ? absint( $_POST['hm_video_attachment_id'] ) : 0 );
		update_post_meta( $post_id, '_hm_video_embed_url', isset( $_POST['hm_video_embed_url'] ) ? esc_url_raw( $_POST['hm_video_embed_url'] ) : '' );
	}
}
