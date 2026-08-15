<?php
/**
 * Layout: a simple full-width scrolling headline list (with thumbnails).
 * Expects $settings, $widget, $widget_uid, $query in scope.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$item_duration   = ! empty( $settings['item_duration'] ) ? floatval( $settings['item_duration'] ) : 4;
$direction_class = ( ! empty( $settings['scroll_direction'] ) && 'down' === $settings['scroll_direction'] ) ? 'hm-dir-down' : 'hm-dir-up';
$pause_class     = ( ! empty( $settings['pause_on_hover'] ) && 'yes' === $settings['pause_on_hover'] ) ? 'hm-pause-hover' : '';
$enable_popup    = ! empty( $settings['enable_popup'] ) && 'yes' === $settings['enable_popup'];
$post_ids        = array();
$total_duration  = $item_duration * max( 1, $query->post_count );
?>
<div class="hm-widget-root" data-hm-uid="<?php echo esc_attr( $widget_uid ); ?>">
	<div class="hm-updates-widget hm-layout-ticker">

		<?php if ( $query->have_posts() ) : ?>
			<div class="hm-ticker-track <?php echo esc_attr( $direction_class ); ?> <?php echo esc_attr( $pause_class ); ?>" style="--hm-duration: <?php echo esc_attr( $total_duration ); ?>s;">
				<?php for ( $rep = 0; $rep < 2; $rep++ ) : ?>
					<ul class="hm-ticker-list" <?php echo $rep ? 'aria-hidden="true"' : ''; ?>>
						<?php
						$query->rewind_posts();
						while ( $query->have_posts() ) :
							$query->the_post();
							$id = get_the_ID();
							if ( 0 === $rep ) {
								$post_ids[] = $id;
							}
							?>
							<li>
								<?php if ( 0 === $rep ) : ?>
									<button type="button" class="hm-update-title hm-update-title-with-thumb" data-hm-update="<?php echo esc_attr( $id ); ?>">
										<?php if ( has_post_thumbnail( $id ) ) : ?>
											<span class="hm-ticker-thumb"><?php echo get_the_post_thumbnail( $id, 'thumbnail' ); ?></span>
										<?php endif; ?>
										<span><?php echo esc_html( get_the_title() ); ?></span>
										<?php $widget->render_media_badges( $id ); ?>
									</button>
								<?php else : ?>
									<span class="hm-update-title hm-update-title-with-thumb" aria-hidden="true">
										<?php if ( has_post_thumbnail( $id ) ) : ?>
											<span class="hm-ticker-thumb"><?php echo get_the_post_thumbnail( $id, 'thumbnail' ); ?></span>
										<?php endif; ?>
										<span><?php echo esc_html( get_the_title() ); ?></span>
									</span>
								<?php endif; ?>
							</li>
						<?php endwhile; ?>
					</ul>
				<?php endfor; ?>
			</div>
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
