<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Border;

/**
 * "עדכוני חדשות מקצועיים" Elementor widget.
 *
 * Supports four layouts (split / ticker / grid / carousel), all sourced
 * from the hm_update custom post type, with full styling and motion
 * controls exposed directly in the Elementor editor.
 */
class HM_Widget_Updates extends Widget_Base {

	public function get_name() {
		return 'hm-updates';
	}

	public function get_title() {
		return __( 'עדכוני חדשות מקצועיים', 'hadashot-mercaz' );
	}

	public function get_icon() {
		return 'eicon-post-list';
	}

	public function get_categories() {
		return array( HM_Elementor::CATEGORY );
	}

	public function get_keywords() {
		return array( 'news', 'updates', 'ticker', 'עדכונים', 'חדשות', 'טיקר' );
	}

	public function get_style_depends() {
		return array( 'hm-updates-frontend' );
	}

	public function get_script_depends() {
		return array( 'hm-updates-frontend' );
	}

	protected function register_controls() {
		$this->register_content_controls();
		$this->register_style_controls();
	}

	/* ------------------------------------------------------------------ */
	/*  CONTENT TAB                                                        */
	/* ------------------------------------------------------------------ */

	private function register_content_controls() {

		$this->start_controls_section(
			'section_data',
			array(
				'label' => __( 'תוכן ופריסה', 'hadashot-mercaz' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'layout',
			array(
				'label'   => __( 'פריסה', 'hadashot-mercaz' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'split',
				'options' => array(
					'split'    => __( 'פיצול: 2 תמונות + רשימה נעה', 'hadashot-mercaz' ),
					'ticker'   => __( 'טיקר / רשימה נעה', 'hadashot-mercaz' ),
					'grid'     => __( 'גריד כרטיסיות', 'hadashot-mercaz' ),
					'carousel' => __( 'קרוסלה', 'hadashot-mercaz' ),
				),
			)
		);

		$this->add_control(
			'categories',
			array(
				'label'       => __( 'סינון לפי קטגוריות', 'hadashot-mercaz' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => HM_Frontend::get_category_options(),
				'description' => __( 'השאירו ריק כדי להציג עדכונים מכל הקטגוריות.', 'hadashot-mercaz' ),
			)
		);

		$this->add_control(
			'posts_count',
			array(
				'label'   => __( 'מספר עדכונים להצגה', 'hadashot-mercaz' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 50,
				'default' => 10,
			)
		);

		$this->add_control(
			'order_by',
			array(
				'label'   => __( 'מיון לפי', 'hadashot-mercaz' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'date',
				'options' => array(
					'date'       => __( 'תאריך', 'hadashot-mercaz' ),
					'title'      => __( 'כותרת', 'hadashot-mercaz' ),
					'menu_order' => __( 'סדר ידני', 'hadashot-mercaz' ),
					'rand'       => __( 'אקראי', 'hadashot-mercaz' ),
				),
			)
		);

		$this->add_control(
			'order',
			array(
				'label'   => __( 'כיוון מיון', 'hadashot-mercaz' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'DESC',
				'options' => array(
					'DESC' => __( 'חדש לישן', 'hadashot-mercaz' ),
					'ASC'  => __( 'ישן לחדש', 'hadashot-mercaz' ),
				),
			)
		);

		$this->end_controls_section();

		/* -- Split layout media slots -- */
		$this->start_controls_section(
			'section_images',
			array(
				'label'     => __( 'משבצות מדיה (פריסת פיצול)', 'hadashot-mercaz' ),
				'tab'       => Controls_Manager::TAB_CONTENT,
				'condition' => array( 'layout' => 'split' ),
			)
		);

		$layout_sets  = class_exists( 'HM_Layout_Set' ) ? HM_Layout_Set::get_all() : array();
		$set_options  = array( '' => __( '— בחרו ערכת פריסה —', 'hadashot-mercaz' ) );
		foreach ( $layout_sets as $set ) {
			$set_options[ $set->ID ] = $set->post_title ? $set->post_title : ( '#' . $set->ID );
		}

		$this->add_control(
			'layout_set_id',
			array(
				'label'       => __( 'ערכת פריסה', 'hadashot-mercaz' ),
				'type'        => Controls_Manager::SELECT,
				'options'     => $set_options,
				'default'     => '',
				'description' => __( 'תוכן המשבצות (עמודת מדיה ימין/שמאל) מנוהל במסך "ערכות פריסה" בתפריט הניהול של וורדפרס. בחירה כאן תשלוף את אותו התוכן בדיוק — עדכון שם משתקף כאן מיד.', 'hadashot-mercaz' ),
			)
		);

		if ( empty( $layout_sets ) ) {
			$this->add_control(
				'layout_set_missing_notice',
				array(
					'type'    => Controls_Manager::RAW_HTML,
					'raw'     => sprintf(
						'<div class="hm-elementor-notice">%s</div><a class="hm-panel-slot-edit-link" href="%s" target="_blank" rel="noopener">%s</a>',
						esc_html__( 'עדיין לא נוצרה אף ערכת פריסה.', 'hadashot-mercaz' ),
						esc_url( admin_url( 'post-new.php?post_type=' . HM_LAYOUT_SET_POST_TYPE ) ),
						esc_html__( '+ יצירת ערכת פריסה חדשה', 'hadashot-mercaz' )
					),
					'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
				)
			);
		}

		foreach ( $layout_sets as $set ) {
			$this->add_control(
				'layout_set_preview_' . $set->ID,
				array(
					'type'      => Controls_Manager::RAW_HTML,
					'raw'       => HM_Layout_Set::render_admin_preview_html( $set->ID ),
					'condition' => array( 'layout_set_id' => (string) $set->ID ),
				)
			);
		}

		$this->end_controls_section();

		/* -- Motion settings: split + ticker -- */
		$this->start_controls_section(
			'section_motion',
			array(
				'label'     => __( 'הגדרות תזוזה', 'hadashot-mercaz' ),
				'tab'       => Controls_Manager::TAB_CONTENT,
				'condition' => array( 'layout' => array( 'split', 'ticker' ) ),
			)
		);

		$this->add_control(
			'item_duration',
			array(
				'label'       => __( 'קצב: שניות להצגת כל עדכון', 'hadashot-mercaz' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 1,
				'max'         => 15,
				'step'        => 1,
				'default'     => 4,
				'description' => __( 'ככל שהמספר גבוה יותר – הרשימה נעה לאט יותר.', 'hadashot-mercaz' ),
			)
		);

		$this->add_control(
			'scroll_direction',
			array(
				'label'   => __( 'כיוון תנועה', 'hadashot-mercaz' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'up',
				'options' => array(
					'up'   => __( 'למעלה', 'hadashot-mercaz' ),
					'down' => __( 'למטה', 'hadashot-mercaz' ),
				),
			)
		);

		$this->add_control(
			'pause_on_hover',
			array(
				'label'   => __( 'עצירה במעבר עכבר', 'hadashot-mercaz' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->end_controls_section();

		/* -- Grid settings -- */
		$this->start_controls_section(
			'section_grid',
			array(
				'label'     => __( 'הגדרות גריד', 'hadashot-mercaz' ),
				'tab'       => Controls_Manager::TAB_CONTENT,
				'condition' => array( 'layout' => 'grid' ),
			)
		);

		$this->add_responsive_control(
			'columns',
			array(
				'label'           => __( 'עמודות', 'hadashot-mercaz' ),
				'type'            => Controls_Manager::NUMBER,
				'min'             => 1,
				'max'             => 6,
				'default'         => 3,
				'tablet_default'  => 2,
				'mobile_default'  => 1,
				'selectors'       => array(
					'{{WRAPPER}} .hm-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
				),
			)
		);

		$this->end_controls_section();

		/* -- Carousel settings -- */
		$this->start_controls_section(
			'section_carousel',
			array(
				'label'     => __( 'הגדרות קרוסלה', 'hadashot-mercaz' ),
				'tab'       => Controls_Manager::TAB_CONTENT,
				'condition' => array( 'layout' => 'carousel' ),
			)
		);

		$this->add_responsive_control(
			'slides_to_show',
			array(
				'label'          => __( 'כרטיסיות מוצגות בו־זמנית', 'hadashot-mercaz' ),
				'type'           => Controls_Manager::NUMBER,
				'min'            => 1,
				'max'            => 5,
				'default'        => 3,
				'tablet_default' => 2,
				'mobile_default' => 1,
				'selectors'      => array(
					'{{WRAPPER}} .hm-carousel' => '--hm-slides: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'autoplay',
			array(
				'label'   => __( 'גלילה אוטומטית', 'hadashot-mercaz' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'autoplay_speed',
			array(
				'label'     => __( 'קצב גלילה אוטומטית (מילישניות)', 'hadashot-mercaz' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 1500,
				'max'       => 10000,
				'step'      => 500,
				'default'   => 4000,
				'condition' => array( 'autoplay' => 'yes' ),
			)
		);

		$this->add_control(
			'show_arrows',
			array(
				'label'   => __( 'הצגת חצים', 'hadashot-mercaz' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'show_dots',
			array(
				'label'   => __( 'הצגת נקודות ניווט', 'hadashot-mercaz' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->end_controls_section();

		/* -- Popup settings -- */
		$this->start_controls_section(
			'section_popup',
			array(
				'label' => __( 'פופ-אפ עדכון מלא', 'hadashot-mercaz' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'enable_popup',
			array(
				'label'   => __( 'פתיחת פופ-אפ בלחיצה', 'hadashot-mercaz' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'show_excerpt',
			array(
				'label'     => __( 'הצגת תקציר בכרטיסיות', 'hadashot-mercaz' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array( 'layout' => array( 'grid', 'carousel' ) ),
			)
		);

		$this->add_control(
			'excerpt_length',
			array(
				'label'     => __( 'אורך תקציר (מילים)', 'hadashot-mercaz' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 5,
				'max'       => 60,
				'default'   => 20,
				'condition' => array( 'layout' => array( 'grid', 'carousel' ), 'show_excerpt' => 'yes' ),
			)
		);

		$this->add_control(
			'show_date',
			array(
				'label'   => __( 'הצגת תאריך', 'hadashot-mercaz' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------ */
	/*  STYLE TAB                                                          */
	/* ------------------------------------------------------------------ */

	private function register_style_controls() {

		/* -- General container -- */
		$this->start_controls_section(
			'style_general',
			array(
				'label' => __( 'כללי', 'hadashot-mercaz' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'container_background',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .hm-updates-widget',
			)
		);

		$this->add_responsive_control(
			'container_padding',
			array(
				'label'      => __( 'רווח פנימי', 'hadashot-mercaz' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .hm-updates-widget' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'container_radius',
			array(
				'label'      => __( 'עיגול פינות', 'hadashot-mercaz' ),
				'type'       => Controls_Manager::SLIDER,
				'range'      => array( 'px' => array( 'max' => 60 ) ),
				'selectors'  => array(
					'{{WRAPPER}} .hm-updates-widget' => 'border-radius: {{SIZE}}{{UNIT}}; overflow: hidden;',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'container_shadow',
				'selector' => '{{WRAPPER}} .hm-updates-widget',
			)
		);

		$this->add_responsive_control(
			'split_gap',
			array(
				'label'     => __( 'מרווח בין העמודות (פריסת פיצול)', 'hadashot-mercaz' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'max' => 60 ) ),
				'default'   => array( 'unit' => 'px', 'size' => 16 ),
				'selectors' => array(
					'{{WRAPPER}} .hm-layout-split' => 'gap: {{SIZE}}{{UNIT}};',
				),
				'condition' => array( 'layout' => 'split' ),
			)
		);

		$this->end_controls_section();

		/* -- Images (split) -- */
		$this->start_controls_section(
			'style_images',
			array(
				'label'     => __( 'תמונות', 'hadashot-mercaz' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'layout' => 'split' ),
			)
		);

		$this->add_responsive_control(
			'split_image_height',
			array(
				'label'     => __( 'גובה משבצת', 'hadashot-mercaz' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 100, 'max' => 800 ) ),
				'default'   => array( 'unit' => 'px', 'size' => 360 ),
				'selectors' => array(
					'{{WRAPPER}} .hm-split-image img, {{WRAPPER}} .hm-split-image video' => 'height: {{SIZE}}{{UNIT}}; width: 100%; object-fit: cover;',
				),
			)
		);

		$this->add_control(
			'split_image_radius',
			array(
				'label'     => __( 'עיגול פינות משבצת', 'hadashot-mercaz' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'max' => 40 ) ),
				'selectors' => array(
					'{{WRAPPER}} .hm-split-image img, {{WRAPPER}} .hm-split-image video' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		/* -- List items (split + ticker) -- */
		$this->start_controls_section(
			'style_list',
			array(
				'label'     => __( 'רשימת כותרות', 'hadashot-mercaz' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'layout' => array( 'split', 'ticker' ) ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'list_typography',
				'selector' => '{{WRAPPER}} .hm-ticker-list .hm-update-title',
			)
		);

		$this->add_control(
			'list_color',
			array(
				'label'     => __( 'צבע כותרת', 'hadashot-mercaz' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .hm-ticker-list .hm-update-title' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'list_hover_color',
			array(
				'label'     => __( 'צבע כותרת בריחוף', 'hadashot-mercaz' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .hm-ticker-list .hm-update-title:hover' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'list_divider_color',
			array(
				'label'     => __( 'צבע קו מפריד', 'hadashot-mercaz' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .hm-ticker-list li' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'list_item_spacing',
			array(
				'label'     => __( 'רווח בין פריטים', 'hadashot-mercaz' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'max' => 40 ) ),
				'default'   => array( 'unit' => 'px', 'size' => 14 ),
				'selectors' => array(
					'{{WRAPPER}} .hm-ticker-list li' => 'padding-top: {{SIZE}}{{UNIT}}; padding-bottom: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		/* -- Cards (grid + carousel) -- */
		$this->start_controls_section(
			'style_cards',
			array(
				'label'     => __( 'כרטיסיות', 'hadashot-mercaz' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'layout' => array( 'grid', 'carousel' ) ),
			)
		);

		$this->add_responsive_control(
			'card_gap',
			array(
				'label'     => __( 'מרווח בין כרטיסיות', 'hadashot-mercaz' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'max' => 60 ) ),
				'default'   => array( 'unit' => 'px', 'size' => 20 ),
				'selectors' => array(
					'{{WRAPPER}} .hm-grid'     => 'gap: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .hm-carousel' => '--hm-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'card_background',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .hm-card',
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'card_border',
				'selector' => '{{WRAPPER}} .hm-card',
			)
		);

		$this->add_control(
			'card_radius',
			array(
				'label'     => __( 'עיגול פינות כרטיסייה', 'hadashot-mercaz' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'max' => 40 ) ),
				'default'   => array( 'unit' => 'px', 'size' => 12 ),
				'selectors' => array(
					'{{WRAPPER}} .hm-card' => 'border-radius: {{SIZE}}{{UNIT}}; overflow: hidden;',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'card_shadow',
				'selector' => '{{WRAPPER}} .hm-card',
			)
		);

		$this->add_responsive_control(
			'card_image_height',
			array(
				'label'     => __( 'גובה תמונת כרטיסייה', 'hadashot-mercaz' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 80, 'max' => 500 ) ),
				'default'   => array( 'unit' => 'px', 'size' => 180 ),
				'selectors' => array(
					'{{WRAPPER}} .hm-card-image img' => 'height: {{SIZE}}{{UNIT}}; width: 100%; object-fit: cover;',
				),
			)
		);

		$this->add_control(
			'heading_card_title',
			array(
				'label'     => __( 'כותרת', 'hadashot-mercaz' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'card_title_typography',
				'selector' => '{{WRAPPER}} .hm-card-title',
			)
		);

		$this->add_control(
			'card_title_color',
			array(
				'label'     => __( 'צבע כותרת', 'hadashot-mercaz' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .hm-card-title' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'heading_card_excerpt',
			array(
				'label'     => __( 'תקציר', 'hadashot-mercaz' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'card_excerpt_typography',
				'selector' => '{{WRAPPER}} .hm-card-excerpt',
			)
		);

		$this->add_control(
			'card_excerpt_color',
			array(
				'label'     => __( 'צבע תקציר', 'hadashot-mercaz' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .hm-card-excerpt' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'heading_card_meta',
			array(
				'label'     => __( 'תאריך ותגית קטגוריה', 'hadashot-mercaz' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'card_date_color',
			array(
				'label'     => __( 'צבע תאריך', 'hadashot-mercaz' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .hm-card-date' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'card_badge_bg',
			array(
				'label'     => __( 'רקע תגית קטגוריה', 'hadashot-mercaz' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .hm-card-category' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'card_badge_color',
			array(
				'label'     => __( 'צבע טקסט תגית קטגוריה', 'hadashot-mercaz' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .hm-card-category' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		/* -- Carousel arrows/dots -- */
		$this->start_controls_section(
			'style_carousel_nav',
			array(
				'label'     => __( 'חצים ונקודות ניווט', 'hadashot-mercaz' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'layout' => 'carousel' ),
			)
		);

		$this->add_control(
			'arrow_color',
			array(
				'label'     => __( 'צבע חצים', 'hadashot-mercaz' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .hm-carousel-arrow' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'arrow_bg_color',
			array(
				'label'     => __( 'רקע חצים', 'hadashot-mercaz' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .hm-carousel-arrow' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'dot_color',
			array(
				'label'     => __( 'צבע נקודות', 'hadashot-mercaz' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .hm-carousel-dot' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'dot_active_color',
			array(
				'label'     => __( 'צבע נקודה פעילה', 'hadashot-mercaz' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .hm-carousel-dot.is-active' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		/* -- Popup -- */
		$this->start_controls_section(
			'style_popup',
			array(
				'label'     => __( 'פופ-אפ', 'hadashot-mercaz' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'enable_popup' => 'yes' ),
			)
		);

		$this->add_control(
			'popup_overlay_color',
			array(
				'label'     => __( 'צבע רקע כהה', 'hadashot-mercaz' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(15, 18, 25, 0.75)',
				'selectors' => array(
					'{{WRAPPER}} .hm-popup-overlay' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'popup_box_background',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .hm-popup-box',
			)
		);

		$this->add_control(
			'popup_max_width',
			array(
				'label'     => __( 'רוחב מקסימלי', 'hadashot-mercaz' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 320, 'max' => 1200 ) ),
				'default'   => array( 'unit' => 'px', 'size' => 640 ),
				'selectors' => array(
					'{{WRAPPER}} .hm-popup-box' => 'max-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'popup_radius',
			array(
				'label'     => __( 'עיגול פינות', 'hadashot-mercaz' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'max' => 50 ) ),
				'default'   => array( 'unit' => 'px', 'size' => 16 ),
				'selectors' => array(
					'{{WRAPPER}} .hm-popup-box' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'heading_popup_title',
			array(
				'label'     => __( 'כותרת', 'hadashot-mercaz' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'popup_title_typography',
				'selector' => '{{WRAPPER}} .hm-popup-title',
			)
		);

		$this->add_control(
			'popup_title_color',
			array(
				'label'     => __( 'צבע כותרת', 'hadashot-mercaz' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .hm-popup-title' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'heading_popup_content',
			array(
				'label'     => __( 'תוכן', 'hadashot-mercaz' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'popup_content_typography',
				'selector' => '{{WRAPPER}} .hm-popup-content',
			)
		);

		$this->add_control(
			'popup_content_color',
			array(
				'label'     => __( 'צבע תוכן', 'hadashot-mercaz' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .hm-popup-content' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'heading_popup_close',
			array(
				'label'     => __( 'כפתור סגירה', 'hadashot-mercaz' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'popup_close_color',
			array(
				'label'     => __( 'צבע איקס', 'hadashot-mercaz' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .hm-popup-close' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'popup_close_bg',
			array(
				'label'     => __( 'רקע כפתור', 'hadashot-mercaz' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .hm-popup-close' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------ */
	/*  RENDER                                                             */
	/* ------------------------------------------------------------------ */

	protected function render() {
		$settings   = $this->get_settings_for_display();
		$layout     = ! empty( $settings['layout'] ) ? $settings['layout'] : 'split';
		$widget     = $this;
		$widget_uid = 'hm-' . $this->get_id();
		$query      = HM_Frontend::get_updates_query( $settings );

		$template = HM_UPDATES_DIR . 'templates/layout-' . $layout . '.php';

		if ( ! file_exists( $template ) ) {
			$template = HM_UPDATES_DIR . 'templates/layout-split.php';
		}

		include $template;

		wp_reset_postdata();
	}

	/* ------------------------------------------------------------------ */
	/*  SHARED TEMPLATE HELPERS                                           */
	/* ------------------------------------------------------------------ */

	/**
	 * Renders one split-layout media slot (right or left) from resolved
	 * HM_Layout_Set::get_slot() data — an image, an autoplaying video, or a
	 * click-to-play video (uploaded or embedded).
	 */
	public function render_media_slot( $slot, $side ) {
		if ( ! $slot ) {
			return;
		}

		$classes = array( 'hm-split-image', 'hm-split-image-' . $side );
		$link    = ! empty( $slot['link'] ) ? $slot['link'] : '';

		if ( 'image' === $slot['type'] ) {
			?>
			<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
				<?php if ( $link ) : ?><a href="<?php echo esc_url( $link ); ?>"><?php endif; ?>
				<img src="<?php echo esc_url( $slot['url'] ); ?>" alt="">
				<?php if ( $link ) : ?></a><?php endif; ?>
			</div>
			<?php
			return;
		}

		if ( ! empty( $slot['is_embed'] ) ) {
			$embed     = wp_oembed_get( $slot['url'] );
			$classes[] = 'hm-clicktoplay';
			?>
			<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-hm-video-slot="embed">
				<div class="hm-split-video-poster">
					<span class="hm-play-btn" aria-hidden="true">▶</span>
				</div>
				<?php if ( $embed ) : ?>
					<div class="hm-split-video-embed" hidden><?php echo $embed; ?></div>
				<?php endif; ?>
			</div>
			<?php
			return;
		}

		if ( $slot['autoplay'] ) {
			?>
			<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
				<?php if ( $link ) : ?><a href="<?php echo esc_url( $link ); ?>"><?php endif; ?>
				<video src="<?php echo esc_url( $slot['url'] ); ?>" autoplay muted loop playsinline></video>
				<?php if ( $link ) : ?></a><?php endif; ?>
			</div>
			<?php
			return;
		}

		$classes[] = 'hm-clicktoplay';
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-hm-video-slot="upload">
			<video src="<?php echo esc_url( $slot['url'] ); ?>" muted playsinline preload="metadata"></video>
			<span class="hm-play-btn" aria-hidden="true">▶</span>
		</div>
		<?php
	}

	public function render_popup_shell( $widget_uid ) {
		?>
		<div class="hm-popup-overlay" data-hm-popup-for="<?php echo esc_attr( $widget_uid ); ?>">
			<div class="hm-popup-box" role="dialog" aria-modal="true">
				<button type="button" class="hm-popup-close" aria-label="<?php esc_attr_e( 'סגירה', 'hadashot-mercaz' ); ?>">&times;</button>
				<div class="hm-popup-body"></div>
			</div>
		</div>
		<?php
	}

	public function render_popup_item( $post_id, $settings ) {
		$title = get_the_title( $post_id );
		$video = HM_Frontend::get_video_html( $post_id );
		$audio = HM_Frontend::get_audio_html( $post_id );
		$terms = get_the_terms( $post_id, HM_UPDATES_TAXONOMY );
		?>
		<script type="text/template" class="hm-popup-item" data-hm-update-id="<?php echo esc_attr( $post_id ); ?>">
			<div class="hm-popup-content-inner">
				<?php if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) : ?>
					<span class="hm-popup-category"><?php echo esc_html( $terms[0]->name ); ?></span>
				<?php endif; ?>
				<h2 class="hm-popup-title"><?php echo esc_html( $title ); ?></h2>
				<?php if ( ! empty( $settings['show_date'] ) && 'yes' === $settings['show_date'] ) : ?>
					<div class="hm-popup-date"><?php echo esc_html( get_the_date( '', $post_id ) ); ?></div>
				<?php endif; ?>
				<?php if ( has_post_thumbnail( $post_id ) ) : ?>
					<div class="hm-popup-image"><?php echo get_the_post_thumbnail( $post_id, 'large' ); ?></div>
				<?php endif; ?>
				<?php if ( $video ) : ?>
					<div class="hm-popup-media hm-popup-video"><?php echo $video; ?></div>
				<?php endif; ?>
				<?php if ( $audio ) : ?>
					<div class="hm-popup-media hm-popup-audio"><?php echo $audio; ?></div>
				<?php endif; ?>
				<div class="hm-popup-content"><?php echo HM_Frontend::get_rendered_content( $post_id ); ?></div>
			</div>
		</script>
		<?php
	}

	public function render_media_badges( $post_id ) {
		$has_audio = HM_Frontend::has_audio( $post_id );
		$has_video = HM_Frontend::has_video( $post_id );
		if ( ! $has_audio && ! $has_video ) {
			return;
		}
		echo '<span class="hm-card-badges">';
		if ( $has_video ) {
			echo '<span class="hm-media-icon hm-media-icon-video" title="' . esc_attr__( 'כולל וידאו', 'hadashot-mercaz' ) . '">▶</span>';
		}
		if ( $has_audio ) {
			echo '<span class="hm-media-icon hm-media-icon-audio" title="' . esc_attr__( 'כולל אודיו', 'hadashot-mercaz' ) . '">♪</span>';
		}
		echo '</span>';
	}

	public function render_category_badge( $post_id ) {
		$terms = get_the_terms( $post_id, HM_UPDATES_TAXONOMY );
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return;
		}
		echo '<span class="hm-card-category">' . esc_html( $terms[0]->name ) . '</span>';
	}

	/**
	 * Shared card markup used by both the grid and carousel layouts.
	 */
	public function render_card_content( $post_id, $settings ) {
		$show_excerpt = ! empty( $settings['show_excerpt'] ) && 'yes' === $settings['show_excerpt'];
		$show_date    = ! empty( $settings['show_date'] ) && 'yes' === $settings['show_date'];
		?>
		<?php if ( has_post_thumbnail( $post_id ) ) : ?>
			<div class="hm-card-image"><?php echo get_the_post_thumbnail( $post_id, 'medium_large' ); ?></div>
		<?php endif; ?>
		<div class="hm-card-body">
			<?php $this->render_category_badge( $post_id ); ?>
			<h3 class="hm-card-title">
				<?php echo esc_html( get_the_title( $post_id ) ); ?>
				<?php $this->render_media_badges( $post_id ); ?>
			</h3>
			<?php if ( $show_excerpt ) : ?>
				<p class="hm-card-excerpt"><?php echo esc_html( HM_Frontend::get_excerpt( $post_id, ! empty( $settings['excerpt_length'] ) ? absint( $settings['excerpt_length'] ) : 20 ) ); ?></p>
			<?php endif; ?>
			<?php if ( $show_date ) : ?>
				<div class="hm-card-date"><?php echo esc_html( get_the_date( '', $post_id ) ); ?></div>
			<?php endif; ?>
		</div>
		<?php
	}
}
