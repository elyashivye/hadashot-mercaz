<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend asset registration and shared rendering helpers used by the
 * Elementor widget templates.
 */
class HM_Frontend {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	public function register_assets() {
		wp_register_style( 'hm-updates-frontend', HM_UPDATES_URL . 'assets/css/frontend.css', array(), HM_UPDATES_VERSION );
		wp_register_script( 'hm-updates-frontend', HM_UPDATES_URL . 'assets/js/frontend.js', array( 'elementor-frontend' ), HM_UPDATES_VERSION, true );
	}

	public static function enqueue_assets() {
		wp_enqueue_style( 'hm-updates-frontend' );
		wp_enqueue_script( 'hm-updates-frontend' );
	}

	/**
	 * Build a WP_Query for updates based on Elementor widget settings.
	 */
	public static function get_updates_query( array $settings ) {
		$args = array(
			'post_type'      => HM_UPDATES_POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => ! empty( $settings['posts_count'] ) ? absint( $settings['posts_count'] ) : 10,
			'orderby'        => ! empty( $settings['order_by'] ) ? $settings['order_by'] : 'date',
			'order'          => ! empty( $settings['order'] ) ? $settings['order'] : 'DESC',
			'ignore_sticky_posts' => true,
		);

		if ( ! empty( $settings['categories'] ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => HM_UPDATES_TAXONOMY,
					'field'    => 'term_id',
					'terms'    => array_map( 'absint', (array) $settings['categories'] ),
				),
			);
		}

		return new WP_Query( $args );
	}

	public static function get_category_options() {
		$terms   = get_terms( array( 'taxonomy' => HM_UPDATES_TAXONOMY, 'hide_empty' => false ) );
		$options = array();
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$options[ $term->term_id ] = $term->name;
			}
		}
		return $options;
	}

	/**
	 * An update is exclusively text, audio, or video — never a combination.
	 */
	public static function get_update_type( $post_id ) {
		$type = get_post_meta( $post_id, '_hm_update_type', true );
		return in_array( $type, array( 'text', 'audio', 'video' ), true ) ? $type : 'text';
	}

	public static function has_audio( $post_id ) {
		return 'audio' === self::get_update_type( $post_id );
	}

	public static function has_video( $post_id ) {
		return 'video' === self::get_update_type( $post_id );
	}

	/**
	 * Renders the update's audio or video player, whichever its exclusive
	 * type is. Returns an empty string for text updates or empty media.
	 */
	public static function get_media_html( $post_id ) {
		$type = self::get_update_type( $post_id );
		if ( 'audio' !== $type && 'video' !== $type ) {
			return '';
		}

		$source = get_post_meta( $post_id, '_hm_media_source', true );

		if ( 'upload' === $source ) {
			$attachment_id = absint( get_post_meta( $post_id, '_hm_media_attachment_id', true ) );
			if ( ! $attachment_id ) {
				return '';
			}
			$url = wp_get_attachment_url( $attachment_id );
			return 'audio' === $type
				? wp_audio_shortcode( array( 'src' => $url ) )
				: wp_video_shortcode( array( 'src' => $url ) );
		}

		if ( 'embed' === $source ) {
			$url = get_post_meta( $post_id, '_hm_media_embed_url', true );
			if ( ! $url ) {
				return '';
			}
			$embed = wp_oembed_get( $url, array( 'width' => 640 ) );
			if ( $embed ) {
				return '<div class="hm-embed hm-embed-' . esc_attr( $type ) . '">' . $embed . '</div>';
			}
			return 'audio' === $type
				? wp_audio_shortcode( array( 'src' => $url ) )
				: wp_video_shortcode( array( 'src' => $url ) );
		}

		return '';
	}

	public static function get_excerpt( $post_id, $length = 20 ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return '';
		}
		$text = $post->post_excerpt ? $post->post_excerpt : $post->post_content;
		$text = wp_strip_all_tags( strip_shortcodes( $text ) );
		return wp_trim_words( $text, $length, '…' );
	}

	/**
	 * Full rendered content for the popup, run through the standard
	 * `the_content` pipeline so Gutenberg blocks, shortcodes, and (when
	 * enabled for this post type) Elementor-built content all render
	 * exactly as they would on a normal singular view.
	 */
	public static function get_rendered_content( $post_id ) {
		$post_obj = get_post( $post_id );
		if ( ! $post_obj ) {
			return '';
		}

		global $post;
		$original_post = $post;

		$post = $post_obj;
		setup_postdata( $post );
		$content = apply_filters( 'the_content', $post_obj->post_content );

		if ( $original_post ) {
			$post = $original_post;
			setup_postdata( $post );
		} else {
			wp_reset_postdata();
		}

		return $content;
	}
}
