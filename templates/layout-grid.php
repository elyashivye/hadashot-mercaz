<?php
/**
 * Layout: responsive card grid. Expects $settings, $widget, $widget_uid,
 * $query in scope.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$enable_popup = ! empty( $settings['enable_popup'] ) && 'yes' === $settings['enable_popup'];
$post_ids     = array();
?>
<div class="hm-widget-root" data-hm-uid="<?php echo esc_attr( $widget_uid ); ?>">
	<div class="hm-updates-widget hm-layout-grid">

		<?php if ( $query->have_posts() ) : ?>
			<div class="hm-grid">
				<?php
				while ( $query->have_posts() ) :
					$query->the_post();
					$id         = get_the_ID();
					$post_ids[] = $id;
					?>
					<div class="hm-card" data-hm-update="<?php echo esc_attr( $id ); ?>" role="button" tabindex="0">
						<?php $widget->render_card_content( $id, $settings ); ?>
					</div>
				<?php endwhile; ?>
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
