<?php

/**
 * Class WCML_Currency_Switcher
 *
 * Main class
 */
class WCML_Currency_Switcher{

	private $woocommerce_wpml;
	private $sitepress;
	private $is_touch_screen;

	public function __construct( &$woocommerce_wpml, &$sitepress ) {

		$this->woocommerce_wpml = $woocommerce_wpml;
		$this->sitepress = $sitepress;

		add_action( 'init', array($this, 'init'), 5 );
	}

	public function init() {

		add_action( 'wcml_currency_switcher', array($this, 'wcml_currency_switcher') );
		//@deprecated 3.9
		add_action( 'currency_switcher', array($this, 'currency_switcher') );

		add_shortcode( 'currency_switcher', array($this, 'currency_switcher_shortcode') );

		// Built in currency switcher
		add_action( 'woocommerce_product_meta_start', array($this, 'show_currency_switcher') );

		add_action( 'pre_update_option_sidebars_widgets', array( $this, 'update_option_sidebars_widgets' ), 10, 2 );
	}

	public static function get_settings( $switcher_id ) {
		global $woocommerce_wpml;

		$wcml_settings = $woocommerce_wpml->get_settings();

		return isset( $wcml_settings[ 'currency_switchers' ][ $switcher_id ] ) ? $wcml_settings[ 'currency_switchers' ][ $switcher_id ] : array();
	}

	public function currency_switcher_shortcode( $atts ) {

		$atts = (array) $atts;

		ob_start();
		$this->wcml_currency_switcher( $atts );
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	public function wcml_currency_switcher( $args = array() ) {
		if ( is_page( wc_get_page_id( 'myaccount' ) ) ) {
			return '';
		}

		if( !isset( $args[ 'switcher_id' ] ) ){
			$args[ 'switcher_id' ] = 'product';
		}

		$wcml_settings = $this->woocommerce_wpml->get_settings();
		$multi_currency_object =& $this->woocommerce_wpml->multi_currency;
		$currency_switcher_settings = array();

		if( isset( $wcml_settings[ 'currency_switchers' ][ $args[ 'switcher_id' ] ] ) ){
			$currency_switcher_settings = $wcml_settings[ 'currency_switchers' ][ $args[ 'switcher_id' ] ];
		}

		if ( !isset( $args[ 'switcher_style' ] ) ) {
			$args[ 'switcher_style' ] = isset( $currency_switcher_settings[ 'switcher_style' ] ) ? $currency_switcher_settings[ 'switcher_style' ] : 'wcml-dropdown';
		}

		if ( !isset( $args[ 'format' ] ) ) {
			$args[ 'format' ] = isset( $currency_switcher_settings[ 'template' ] ) && '' !== $currency_switcher_settings[ 'template' ] ?
				$currency_switcher_settings[ 'template' ] : '%name% (%symbol%) - %code%';
		}

		if ( !isset( $args[ 'color_scheme' ] ) ) {
			$args[ 'color_scheme' ] = isset($currency_switcher_settings['color_scheme']) ? $currency_switcher_settings['color_scheme'] : array();
		}

		$preview = '';
		$show_currency_switcher = true;

		$display_custom_prices = isset( $wcml_settings[ 'display_custom_prices' ] ) && $wcml_settings[ 'display_custom_prices' ];
		$is_cart_or_checkout = is_page( wc_get_page_id( 'cart' ) ) || is_page( wc_get_page_id( 'checkout' ) );

		if ( $display_custom_prices ) {
			if( $is_cart_or_checkout ){
				$show_currency_switcher = false;
			}elseif( is_product() ){
				$current_product_id = get_post()->ID;
				$original_product_language = $this->woocommerce_wpml->products->get_original_product_language( $current_product_id );
				$use_custom_prices  = get_post_meta(
					apply_filters( 'translate_object_id', $current_product_id, get_post_type( $current_product_id ), true, $original_product_language ),
					'_wcml_custom_prices_status',
					true
				);

				if ( !$use_custom_prices )  $show_currency_switcher = false;
			}
		}

		if ( $show_currency_switcher ) {

			$currencies = isset($wcml_settings['currencies_order']) ?
				$wcml_settings['currencies_order'] :
				$multi_currency_object->get_currency_codes();

			if ( count($currencies) > 1) {
				if ( !is_admin() ) {
					foreach ( $currencies as $k => $currency ) {
						if ( $wcml_settings['currency_options'][$currency]['languages'][$this->sitepress->get_current_language()] != 1 ) {
							unset( $currencies[$k] );
						}
					}
				}

				$template = $this->woocommerce_wpml->cs_templates->get_template( $args[ 'switcher_style' ] );
				$template->set_model( $this->get_model_data( $args, $currencies ) );
				$preview = $template->get_view();
			} else{

				if( is_admin() ){
					$preview = '<i>' . esc_html__("You haven't added any secondary currencies.", 'woocommerce-multilingual') . '</i>';
				}else{
					$preview = '';
				}
			}
		}

		if ( !isset($args['echo']) || $args['echo'] ) {
			echo $preview;
		} else {
			return $preview;
		}
	}

	public function get_model_data( $args, $currencies ){

		$css_classes = $this->get_css_classes( array( $args[ 'switcher_style' ], $args[ 'switcher_id' ], 'wcml_currency_switcher' ) );

		$model = array(
			'css_classes' 	=> $css_classes,
			'format'        => isset( $args['format'] ) ? $args['format'] : '%name% (%symbol%) - %code%',
			'currencies'    => $currencies,
			'selected_currency' => $this->woocommerce_wpml->multi_currency->get_client_currency()
		);

		return $model;
	}

	public function get_css_classes( $classes = array() ){

		if ( $this->sitepress->is_rtl( $this->sitepress->get_current_language() ) ) {
			$classes[] = 'wcml-cs-rtl';
		}

		$classes = $this->add_user_agent_touch_device_classes( $classes );
		$classes = apply_filters( 'wcml_cs_template_css_classes', $classes );

		return implode( ' ', $classes );
	}

	public function add_user_agent_touch_device_classes( $classes ) {

		if ( is_null( $this->is_touch_screen ) ) {
			if ( ! class_exists( 'WPML_Mobile_Detect' ) ) {
				require_once ICL_PLUGIN_PATH . '/lib/mobile-detect.php';
			}
			$mobile_detect   = new WPML_Mobile_Detect();
			$this->is_touch_screen = $mobile_detect->isMobile() || $mobile_detect->isTablet();
		}

		if ( $this->is_touch_screen ) {
			$classes[] = 'wcml-cs-touch-device';
		}

		return $classes;
	}

	public function show_currency_switcher() {
		$settings = $this->woocommerce_wpml->get_settings();

		if ( is_product() && isset($settings['currency_switcher_product_visibility']) && $settings['currency_switcher_product_visibility'] === 1 ) {
			echo( do_shortcode( '[currency_switcher]' ) );
		}
	}

	/**
	 * @deprecated 3.9
	 */
	public function currency_switcher( $args = array() ){
		$this->wcml_currency_switcher( $args );
	}

	/**
	 * @return array
	 */
	public function get_registered_sidebars() {
		global $wp_registered_sidebars;

		return is_array( $wp_registered_sidebars ) ? $wp_registered_sidebars : array();
	}

	public function get_available_sidebars(){
		$sidebars = $this->get_registered_sidebars();
		$wcml_settings = $this->woocommerce_wpml->get_settings();

		foreach( $sidebars as $key => $sidebar ){
			if ( isset( $wcml_settings[ 'currency_switchers' ][ $sidebar[ 'id' ] ] ) ){
				unset( $sidebars[ $key ] );
			}
		}

		return $sidebars;
	}

	public function update_option_sidebars_widgets( $sidebars, $old_sidebars ) {

		foreach ( $sidebars as $sidebar => $widgets ) {
			if ( 'wp_inactive_widgets' === $sidebar ) {
				continue;
			}
			$found = false;
			if ( is_array( $widgets ) ) {
				foreach ( $widgets as $key => $widget_id ) {
					if ( strpos($widget_id, WCML_Currency_Switcher_Widget::SLUG ) === 0 ) {
						if ( $found ) { //Only one CS widget instance per sidebar
							unset($sidebars[$sidebar][$key]);
							continue;
						}
						$found = true;
					}
				}
			}

			$wcml_settings = $this->woocommerce_wpml->get_settings();
			if( $found && empty( $wcml_settings[ 'currency_switchers' ][ $sidebar ] ) ){
				$wcml_settings[ 'currency_switchers' ][ $sidebar ] = $this->get_switcher_default_settings();
			}elseif( !$found && isset( $wcml_settings[ 'currency_switchers' ][ $sidebar ] ) ){
				unset( $wcml_settings[ 'currency_switchers' ][ $sidebar ] );
			}

			$this->woocommerce_wpml->update_settings( $wcml_settings );
		}

		return $sidebars;
	}

	public function get_switcher_default_settings(){
		return array(
			'switcher_style' => 'wcml-dropdown',
			'widget_title'  =>  '',
			'template' => '%name% (%symbol%) - %code%',
			'color_scheme' => array(
				'font_current_normal'       => '',
				'font_current_hover'        => '',
				'background_current_normal' => '',
				'background_current_hover'  => '',
				'font_other_normal'         => '',
				'font_other_hover'          => '',
				'background_other_normal'   => '',
				'background_other_hover'    => '',
				'border_normal'             => ''
			)
		);
	}

}