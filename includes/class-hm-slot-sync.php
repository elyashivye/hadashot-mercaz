<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Single source of truth for split-layout media slot data: one real
 * database table, shared by the wp-admin "ערכת פריסה" screen and the
 * Elementor widget. Everything that reads or writes slot content goes
 * through this class, so there is exactly one storage/normalization path
 * for both editing surfaces to sync through.
 */
class HM_Slot_Sync {

	const DB_VERSION = '1.0';

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'plugins_loaded', array( __CLASS__, 'maybe_upgrade' ) );
		add_action( 'before_delete_post', array( $this, 'on_set_deleted' ) );
	}

	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'hm_layout_slots';
	}

	/**
	 * Creates (or updates) the table. Safe to call repeatedly — dbDelta()
	 * only ever applies the diff.
	 */
	public static function install() {
		global $wpdb;

		$table_name      = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			set_id BIGINT UNSIGNED NOT NULL,
			side VARCHAR(10) NOT NULL,
			type VARCHAR(10) NOT NULL DEFAULT 'image',
			image_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			video_type VARCHAR(10) NOT NULL DEFAULT 'upload',
			video_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			video_url TEXT NULL,
			autoplay TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
			link TEXT NULL,
			updated_at DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00',
			PRIMARY KEY  (id),
			UNIQUE KEY set_side (set_id, side)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		self::migrate_legacy_postmeta();

		update_option( 'hm_slot_sync_db_version', self::DB_VERSION );
	}

	/**
	 * One-time carry-over for sites that already had Layout Sets saved in
	 * postmeta by an earlier version of the plugin. Skips anything already
	 * present in the table, so it's safe to run more than once.
	 */
	private static function migrate_legacy_postmeta() {
		global $wpdb;

		if ( ! post_type_exists( HM_LAYOUT_SET_POST_TYPE ) ) {
			return;
		}

		$set_ids = get_posts( array(
			'post_type'      => HM_LAYOUT_SET_POST_TYPE,
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'fields'         => 'ids',
		) );

		foreach ( $set_ids as $set_id ) {
			foreach ( array( 'right', 'left' ) as $side ) {
				$already = $wpdb->get_var( $wpdb->prepare(
					'SELECT id FROM ' . self::table_name() . ' WHERE set_id = %d AND side = %s',
					$set_id,
					$side
				) );
				if ( $already ) {
					continue;
				}

				$legacy_type = get_post_meta( $set_id, "_hm_slot_{$side}_type", true );
				if ( ! $legacy_type ) {
					continue;
				}

				self::save_slot( $set_id, $side, array(
					'type'       => $legacy_type,
					'image_id'   => get_post_meta( $set_id, "_hm_slot_{$side}_image_id", true ),
					'video_type' => get_post_meta( $set_id, "_hm_slot_{$side}_video_type", true ),
					'video_id'   => get_post_meta( $set_id, "_hm_slot_{$side}_video_id", true ),
					'video_url'  => get_post_meta( $set_id, "_hm_slot_{$side}_video_url", true ),
					'autoplay'   => 'yes' === get_post_meta( $set_id, "_hm_slot_{$side}_autoplay", true ),
					'link'       => get_post_meta( $set_id, "_hm_slot_{$side}_link", true ),
				) );
			}
		}
	}

	public static function maybe_upgrade() {
		if ( get_option( 'hm_slot_sync_db_version' ) !== self::DB_VERSION ) {
			self::install();
		}
	}

	public function on_set_deleted( $post_id ) {
		if ( HM_LAYOUT_SET_POST_TYPE === get_post_type( $post_id ) ) {
			self::delete_set( $post_id );
		}
	}

	public static function delete_set( $set_id ) {
		global $wpdb;
		$wpdb->delete( self::table_name(), array( 'set_id' => absint( $set_id ) ), array( '%d' ) );
	}

	/**
	 * Normalizes and persists one slot's row — an upsert keyed on
	 * (set_id, side). This is the single write path: the wp-admin metabox
	 * and the Elementor after-save sync hook both call this, so there is
	 * never a second copy of the rules for what a "valid" slot looks like.
	 */
	public static function save_slot( $set_id, $side, array $values ) {
		global $wpdb;

		$set_id = absint( $set_id );
		if ( ! $set_id || ! in_array( $side, array( 'right', 'left' ), true ) ) {
			return;
		}

		$row = array(
			'set_id'     => $set_id,
			'side'       => $side,
			'type'       => 'video' === ( $values['type'] ?? '' ) ? 'video' : 'image',
			'image_id'   => absint( $values['image_id'] ?? 0 ),
			'video_type' => 'embed' === ( $values['video_type'] ?? '' ) ? 'embed' : 'upload',
			'video_id'   => absint( $values['video_id'] ?? 0 ),
			'video_url'  => esc_url_raw( wp_unslash( (string) ( $values['video_url'] ?? '' ) ) ),
			'autoplay'   => ! empty( $values['autoplay'] ) ? 1 : 0,
			'link'       => esc_url_raw( wp_unslash( (string) ( $values['link'] ?? '' ) ) ),
			'updated_at' => current_time( 'mysql' ),
		);

		// REPLACE INTO relies on the (set_id, side) unique key to upsert
		// in a single round trip — exactly one row per slot, always.
		$wpdb->replace( self::table_name(), $row );
	}

	/**
	 * Raw stored values for one slot, for re-populating the wp-admin form.
	 * Always returns a complete row (sane defaults when nothing is saved
	 * yet) rather than null, unlike get_slot().
	 */
	public static function get_raw( $set_id, $side ) {
		global $wpdb;
		$set_id = absint( $set_id );

		$defaults = array(
			'type'       => 'image',
			'image_id'   => 0,
			'video_type' => 'upload',
			'video_id'   => 0,
			'video_url'  => '',
			'autoplay'   => 0,
			'link'       => '',
		);

		if ( ! $set_id ) {
			return $defaults;
		}

		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT type, image_id, video_type, video_id, video_url, autoplay, link FROM ' . self::table_name() . ' WHERE set_id = %d AND side = %s',
				$set_id,
				$side
			),
			ARRAY_A
		);

		return $row ? wp_parse_args( $row, $defaults ) : $defaults;
	}

	/**
	 * Resolved slot data ready for rendering (image/video URL, autoplay,
	 * embed flag, link) — or null when the slot is empty. Always reads
	 * live from the table, so this is what makes both the public site and
	 * any fresh Elementor render reflect the latest wp-admin edit.
	 */
	public static function get_slot( $set_id, $side ) {
		$set_id = absint( $set_id );
		if ( ! $set_id ) {
			return null;
		}

		$raw = self::get_raw( $set_id, $side );

		$data = array(
			'type'     => $raw['type'],
			'autoplay' => false,
			'is_embed' => false,
			'link'     => $raw['link'],
			'url'      => '',
		);

		if ( 'image' === $raw['type'] ) {
			if ( ! $raw['image_id'] ) {
				return null;
			}
			$data['url'] = wp_get_attachment_image_url( $raw['image_id'], 'large' );
			return $data['url'] ? $data : null;
		}

		if ( 'upload' === $raw['video_type'] ) {
			if ( ! $raw['video_id'] ) {
				return null;
			}
			$data['url']      = wp_get_attachment_url( $raw['video_id'] );
			$data['autoplay'] = ! empty( $raw['autoplay'] );
			return $data['url'] ? $data : null;
		}

		if ( ! $raw['video_url'] ) {
			return null;
		}
		$data['url']      = $raw['video_url'];
		$data['is_embed'] = true;
		return $data;
	}
}
