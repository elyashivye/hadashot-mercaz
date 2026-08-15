<?php
/**
 * Custom "add/edit update" screen. Expects $post_id, $post, $type, $source,
 * $attachment_id, $attachment_url, $attachment_name, $embed_url, $thumb_id,
 * $thumb_url, $selected_cats, $all_cats, $is_edit, $status in scope
 * (required from HM_Update_Editor::render_page(), which shares $this).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="hm-ed-wrap">
<div class="hm-ed" dir="rtl">
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="hm-ed-form">
		<input type="hidden" name="action" value="hm_save_update">
		<input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ); ?>">
		<input type="hidden" name="hm_status" id="hm-ed-status-field" value="<?php echo esc_attr( $status ); ?>">
		<?php wp_nonce_field( 'hm_save_update', 'hm_update_editor_nonce' ); ?>

		<header class="hm-ed-bar">
			<div class="hm-ed-bar-start">
				<a class="hm-ed-back" href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . HM_UPDATES_POST_TYPE ) ); ?>">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15 5l-7 7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
					<?php esc_html_e( 'כל העדכונים', 'hadashot-mercaz' ); ?>
				</a>
				<span class="hm-ed-status-pill" data-status="<?php echo esc_attr( $status ); ?>">
					<?php echo 'publish' === $status ? esc_html__( 'פורסם', 'hadashot-mercaz' ) : esc_html__( 'טיוטה', 'hadashot-mercaz' ); ?>
				</span>
			</div>
			<div class="hm-ed-bar-end">
				<button type="submit" class="hm-ed-btn hm-ed-btn-ghost" data-set-status="draft">
					<?php esc_html_e( 'שמירה כטיוטה', 'hadashot-mercaz' ); ?>
				</button>
				<button type="submit" class="hm-ed-btn hm-ed-btn-primary" data-set-status="publish">
					<?php echo ( $is_edit && 'publish' === $status ) ? esc_html__( 'עדכון', 'hadashot-mercaz' ) : esc_html__( 'פרסום', 'hadashot-mercaz' ); ?>
				</button>
			</div>
		</header>

		<?php if ( isset( $_GET['saved'] ) ) : ?>
			<div class="hm-ed-toast"><?php esc_html_e( 'נשמר בהצלחה.', 'hadashot-mercaz' ); ?></div>
		<?php endif; ?>

		<div class="hm-ed-body">
			<main class="hm-ed-main">
				<input type="text" name="hm_title" class="hm-ed-title" placeholder="<?php esc_attr_e( 'כותרת העדכון', 'hadashot-mercaz' ); ?>" value="<?php echo esc_attr( $post ? $post->post_title : '' ); ?>" autocomplete="off">

				<div class="hm-ed-type-switch" role="radiogroup" aria-label="<?php esc_attr_e( 'סוג עדכון', 'hadashot-mercaz' ); ?>">
					<span class="hm-ed-type-indicator" aria-hidden="true"></span>

					<label class="hm-ed-type-option" data-type="text">
						<input type="radio" name="hm_update_type" value="text" <?php checked( $type, 'text' ); ?>>
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 6h14M5 12h14M5 18h9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
						<span><?php esc_html_e( 'טקסט', 'hadashot-mercaz' ); ?></span>
					</label>

					<label class="hm-ed-type-option" data-type="audio">
						<input type="radio" name="hm_update_type" value="audio" <?php checked( $type, 'audio' ); ?>>
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 18V5l10-2v13M9 18a3 3 0 11-6 0 3 3 0 016 0zM19 16a3 3 0 11-6 0 3 3 0 016 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
						<span><?php esc_html_e( 'אודיו', 'hadashot-mercaz' ); ?></span>
					</label>

					<label class="hm-ed-type-option" data-type="video">
						<input type="radio" name="hm_update_type" value="video" <?php checked( $type, 'video' ); ?>>
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6a2 2 0 012-2h8a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM16 10l5-3v10l-5-3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
						<span><?php esc_html_e( 'וידאו', 'hadashot-mercaz' ); ?></span>
					</label>
				</div>

				<div class="hm-ed-panel" data-panel="text" <?php echo 'text' === $type ? '' : 'hidden'; ?>>
					<textarea name="hm_content" class="hm-ed-textarea" placeholder="<?php esc_attr_e( 'כתבו כאן את תוכן העדכון…', 'hadashot-mercaz' ); ?>" rows="11"><?php echo esc_textarea( $post ? $post->post_content : '' ); ?></textarea>
					<div class="hm-ed-textarea-meta"><span id="hm-ed-word-count">0</span> <?php esc_html_e( 'מילים', 'hadashot-mercaz' ); ?></div>
				</div>

				<div class="hm-ed-panel" data-panel="audio" <?php echo 'audio' === $type ? '' : 'hidden'; ?>>
					<?php $this->render_media_panel( 'audio', 'audio' === $type ? $source : 'upload', 'audio' === $type ? $attachment_id : 0, 'audio' === $type ? $attachment_url : '', 'audio' === $type ? $attachment_name : '', 'audio' === $type ? $embed_url : '' ); ?>
				</div>

				<div class="hm-ed-panel" data-panel="video" <?php echo 'video' === $type ? '' : 'hidden'; ?>>
					<?php $this->render_media_panel( 'video', 'video' === $type ? $source : 'upload', 'video' === $type ? $attachment_id : 0, 'video' === $type ? $attachment_url : '', 'video' === $type ? $attachment_name : '', 'video' === $type ? $embed_url : '' ); ?>
				</div>

				<div class="hm-ed-field">
					<label for="hm-ed-excerpt"><?php esc_html_e( 'תיאור קצר (אופציונלי)', 'hadashot-mercaz' ); ?></label>
					<input type="text" id="hm-ed-excerpt" class="hm-ed-input" name="hm_excerpt" value="<?php echo esc_attr( $post ? $post->post_excerpt : '' ); ?>" placeholder="<?php esc_attr_e( 'שורת תקציר קצרה לרשימות ולכרטיסיות', 'hadashot-mercaz' ); ?>">
				</div>
			</main>

			<aside class="hm-ed-side">
				<div class="hm-ed-card">
					<h3><?php esc_html_e( 'תמונה ראשית', 'hadashot-mercaz' ); ?></h3>
					<div class="hm-ed-image-drop" id="hm-ed-featured-preview">
						<?php if ( $thumb_url ) : ?>
							<img src="<?php echo esc_url( $thumb_url ); ?>" alt="">
						<?php else : ?>
							<span class="hm-ed-image-drop-empty">
								<svg width="26" height="26" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 16l4.5-5 4 4L17 10l3 3M4 6h16v12H4V6z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
							</span>
						<?php endif; ?>
					</div>
					<input type="hidden" name="hm_featured_image_id" id="hm-ed-featured-id" value="<?php echo esc_attr( $thumb_id ); ?>">
					<div class="hm-ed-card-actions">
						<button type="button" class="hm-ed-btn hm-ed-btn-ghost hm-ed-btn-small" id="hm-ed-choose-featured"><?php esc_html_e( 'בחירת תמונה', 'hadashot-mercaz' ); ?></button>
						<button type="button" class="hm-ed-btn hm-ed-btn-text hm-ed-btn-small" id="hm-ed-remove-featured" <?php echo $thumb_url ? '' : 'hidden'; ?>><?php esc_html_e( 'הסרה', 'hadashot-mercaz' ); ?></button>
					</div>
				</div>

				<div class="hm-ed-card">
					<h3><?php esc_html_e( 'קטגוריות', 'hadashot-mercaz' ); ?></h3>
					<?php if ( empty( $all_cats ) || is_wp_error( $all_cats ) ) : ?>
						<p class="hm-ed-empty-note"><?php esc_html_e( 'אין עדיין קטגוריות.', 'hadashot-mercaz' ); ?></p>
					<?php else : ?>
						<div class="hm-ed-pills">
							<?php foreach ( $all_cats as $cat ) : ?>
								<label class="hm-ed-pill">
									<input type="checkbox" name="hm_categories[]" value="<?php echo esc_attr( $cat->term_id ); ?>" <?php checked( in_array( $cat->term_id, $selected_cats, true ) ); ?>>
									<span><?php echo esc_html( $cat->name ); ?></span>
								</label>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>

				<?php if ( $is_edit ) : ?>
					<div class="hm-ed-card hm-ed-card-muted">
						<h3><?php esc_html_e( 'קישור ציבורי', 'hadashot-mercaz' ); ?></h3>
						<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" target="_blank" rel="noopener" class="hm-ed-permalink"><?php echo esc_html( get_permalink( $post_id ) ); ?></a>
					</div>
				<?php endif; ?>
			</aside>
		</div>
	</form>
</div>
</div>
