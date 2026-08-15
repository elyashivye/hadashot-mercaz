<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the "עדכון" custom post type and its category taxonomy.
 */
class HM_Post_Type {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_taxonomy' ) );
	}

	public function register_post_type() {
		$labels = array(
			'name'                  => __( 'עדכונים מקצועיים', 'hadashot-mercaz' ),
			'singular_name'         => __( 'עדכון', 'hadashot-mercaz' ),
			'menu_name'             => __( 'עדכוני חדשות', 'hadashot-mercaz' ),
			'name_admin_bar'        => __( 'עדכון', 'hadashot-mercaz' ),
			'add_new'               => __( 'הוספת עדכון', 'hadashot-mercaz' ),
			'add_new_item'          => __( 'הוספת עדכון חדש', 'hadashot-mercaz' ),
			'edit_item'             => __( 'עריכת עדכון', 'hadashot-mercaz' ),
			'new_item'              => __( 'עדכון חדש', 'hadashot-mercaz' ),
			'view_item'             => __( 'צפייה בעדכון', 'hadashot-mercaz' ),
			'view_items'            => __( 'צפייה בעדכונים', 'hadashot-mercaz' ),
			'search_items'          => __( 'חיפוש עדכונים', 'hadashot-mercaz' ),
			'not_found'             => __( 'לא נמצאו עדכונים', 'hadashot-mercaz' ),
			'not_found_in_trash'    => __( 'לא נמצאו עדכונים באשפה', 'hadashot-mercaz' ),
			'all_items'             => __( 'כל העדכונים', 'hadashot-mercaz' ),
			'archives'              => __( 'ארכיון עדכונים', 'hadashot-mercaz' ),
			'featured_image'        => __( 'תמונה ראשית', 'hadashot-mercaz' ),
			'set_featured_image'    => __( 'הגדרת תמונה ראשית', 'hadashot-mercaz' ),
			'remove_featured_image' => __( 'הסרת תמונה ראשית', 'hadashot-mercaz' ),
			'use_featured_image'    => __( 'השתמש כתמונה ראשית', 'hadashot-mercaz' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_nav_menus'  => true,
			'show_in_admin_bar'  => true,
			'show_in_rest'       => true,
			'menu_position'      => 25,
			'menu_icon'          => 'dashicons-megaphone',
			'capability_type'    => 'post',
			'hierarchical'       => false,
			'has_archive'        => true,
			'rewrite'            => array( 'slug' => 'updates' ),
			'query_var'          => true,
			'supports'           => array( 'title', 'editor', 'thumbnail' ),
		);

		register_post_type( HM_UPDATES_POST_TYPE, $args );
	}

	public function register_taxonomy() {
		$labels = array(
			'name'              => __( 'קטגוריות עדכון', 'hadashot-mercaz' ),
			'singular_name'     => __( 'קטגוריית עדכון', 'hadashot-mercaz' ),
			'search_items'      => __( 'חיפוש קטגוריות', 'hadashot-mercaz' ),
			'all_items'         => __( 'כל הקטגוריות', 'hadashot-mercaz' ),
			'parent_item'       => __( 'קטגוריית אב', 'hadashot-mercaz' ),
			'parent_item_colon' => __( 'קטגוריית אב:', 'hadashot-mercaz' ),
			'edit_item'         => __( 'עריכת קטגוריה', 'hadashot-mercaz' ),
			'update_item'       => __( 'עדכון קטגוריה', 'hadashot-mercaz' ),
			'add_new_item'      => __( 'הוספת קטגוריה חדשה', 'hadashot-mercaz' ),
			'new_item_name'     => __( 'שם קטגוריה חדשה', 'hadashot-mercaz' ),
			'menu_name'         => __( 'קטגוריות', 'hadashot-mercaz' ),
		);

		$args = array(
			'labels'            => $labels,
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'update-category' ),
		);

		register_taxonomy( HM_UPDATES_TAXONOMY, array( HM_UPDATES_POST_TYPE ), $args );
	}
}
