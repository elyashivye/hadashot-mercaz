<?php
/**
 * Layout: horizontal scroll-snap carousel of cards. Expects $settings,
 * $widget, $widget_uid, $query in scope.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$enable_popup  = ! empty( $settings['enable_popup'] ) && 'yes' === $settings['enable_popup'];
$show_arrows   = ! empty( $settings['show_arrows'] ) && 'yes' === $settings['show_arrows'];
$show_dots     = ! empty( $settings['show_dots'] ) && 'yes' === $settings['show_dots'];
$autoplay      = ! empty( $settings['autoplay'] ) && 'yes' === $settings['autoplay'];
$autoplay_speed = ! empty( $settings['autoplay_speed'] ) ? absint( $settings['autoplay_speed'] ) : 4000;
$post_ids      = array();
$slide_count   = $query->post_count;
?>
<div class="hm-widget-root" data-hm-uid="<?php echo esc_attr( $widget_uid ); ?>">
	<div class="hm-updates-widget hm-layout-carousel">

		<?php if ( $query->have_posts() ) : ?>
			<div class="hm-carousel-wrap">
				<?php if ( $show_arrows && $slide_count > 1 ) : ?>
					<button type="button" class="hm-carousel-arrow hm-carousel-prev" aria-label="<?php esc_attr_e( 'הקודם', 'hadashot-mercaz' ); ?>">‹</button>
				<?php endif; ?>

				<div class="hm-carousel"
					data-autoplay="<?php echo $autoplay ? '1' : '0'; ?>"
					data-autoplay-speed="<?php echo esc_attr( $autoplay_speed ); ?>">
					<?php
					while ( $query->have_posts() ) :
						$query->the_post();
						$id         = get_the_ID();
						$post_ids[] = $id;
						?>
						<div class="hm-card hm-carousel-slide" data-hm-update="<?php echo esc_attr( $id ); ?>" role="button" tabindex="0">
							<?php $widget->render_card_content( $id, $settings ); ?>
						</div>
					<?php endwhile; ?>
				</div>

				<?php if ( $show_arrows && $slide_count > 1 ) : ?>
					<button type="button" class="hm-carousel-arrow hm-carousel-next" aria-label="<?php esc_attr_e( 'הבא', 'hadashot-mercaz' ); ?>">›</button>
				<?php endif; ?>
			</div>

			<?php if ( $show_dots && $slide_count > 1 ) : ?>
				<div class="hm-carousel-dots">
					<?php for ( $i = 0; $i < $slide_count; $i++ ) : ?>
						<button type="button" class="hm-carousel-dot <?php echo 0 === $i ? 'is-active' : ''; ?>" data-hm-dot-index="<?php echo esc_attr( $i ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'מעבר לעדכון %d', 'hadashot-mercaz' ), $i + 1 ) ); ?>"></button>
					<?php endfor; ?>
				</div>
			<?php endif; ?>
		<?php else : ?>
			<p class="hm-empty"><?php esc_html_e( 'אין עדכונים להצגה כרגע.', 'hadashot-mercaz' ); ?></p>
		<?php endif; ?>

	</div>

	<?php
	if ( $enable_popup && ! empty( $post_ids ) ) {
		$widget->render_popup_shell( $widget_uid );
		foreach ( $post_ids as $pid ) {
			$widget->render_popup_item( $pid, $settings );
		}
	}
	?>
</div>
