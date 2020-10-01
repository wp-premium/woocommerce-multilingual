<?php

use WPML\Core\Twig_SimpleFunction;

class WCML_Multi_Currency_UI extends WCML_Templates_Factory {

	/**
	 * @var woocommerce_wpml
	 */
	private $woocommerce_wpml;
	/**
	 * @var SitePress
	 */
	private $sitepress;
	/**
	 * @var array
	 */
	private $currencies;
	/**
	 * @var array
	 */
	private $wc_currencies;
	/**
	 * @var string
	 */
	private $wc_currency;

	/** @var WCML_Tracking_Link */
	private $tracking_link;

	/**
	 * WCML_Multi_Currency_UI constructor.
	 *
	 * @param woocommerce_wpml $woocommerce_wpml
	 * @param SitePress        $sitepress
	 */
	public function __construct( $woocommerce_wpml, $sitepress ) {
		// @todo Cover by tests, required for wcml-3037.
		$functions = [
			new Twig_SimpleFunction( 'get_flag_url', [ $this, 'get_flag_url' ] ),
			new Twig_SimpleFunction( 'get_currency_symbol', [ $this, 'get_currency_symbol' ] ),
			new Twig_SimpleFunction( 'wp_do_action', [ $this, 'wp_do_action' ] ),
			new Twig_SimpleFunction( 'get_weekday', [ $this, 'get_weekday' ] ),
		];

		parent::__construct( $functions );
		$this->woocommerce_wpml = $woocommerce_wpml;
		$this->sitepress        = $sitepress;

		$this->currencies    = $this->woocommerce_wpml->multi_currency->get_currencies( true );
		$this->wc_currencies = get_woocommerce_currencies();
		$this->wc_currency   = wcml_get_woocommerce_currency_option();

		$this->load_curency_switcher_option_boxes();

		$this->tracking_link = new WCML_Tracking_Link();
	}

	public function get_model() {
		$exchange_rates_ui = new WCML_Exchange_Rates_UI( $this->woocommerce_wpml );

		$model = [
			'strings'                => [
				'headers'             => [
					'enable_disable' => __( 'Enable/disable', 'woocommerce-multilingual' ),
					'currencies'     => __( 'Currencies', 'woocommerce-multilingual' ),
				],
				'settings'            => __( 'Settings', 'woocommerce-multilingual' ),
			],
			'currencies'             => $this->currencies,
			'wc_currency'            => $this->wc_currency,
			'wc_currencies'          => $this->wc_currencies,

			'active_languages'       => $this->sitepress->get_active_languages(),

			'multi_currency_on'      => $this->woocommerce_wpml->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT && isset( $this->woocommerce_wpml->settings['currency_mode'] ) && $this->woocommerce_wpml->settings['currency_mode'],

			'wc_currency_empty_warn' => sprintf(
				__(
					'The multi-currency mode cannot be enabled as a specific currency was not set. Go to the %1$sWooCommerce settings%2$s page and select the default currency for your store.',
					'woocommerce-multilingual'
				),
				'<a href="' . admin_url( 'admin.php?page=wc-settings' ) . '">',
				'</a>'
			),
			'wcml_settings'          => $this->woocommerce_wpml->settings,
			'form'                   => [
				'action'                     => $_SERVER['REQUEST_URI'],
				'nonce'                      => wp_nonce_field( 'wcml_mc_options', 'wcml_nonce', true, false ),
				'multi_currency_option'      => WCML_MULTI_CURRENCIES_INDEPENDENT,
				'mco_disabled'               => empty( $wc_currency ),
				'label_mco'                  => __( 'Enable the multi-currency mode', 'woocommerce-multilingual' ),
				'label_mco_learn_url'        => $this->tracking_link->generate( 'https://wpml.org/documentation/related-projects/woocommerce-multilingual/multi-currency-support-woocommerce/', 'multi-currency-support-woocommerce', 'documentation' ),
				'label_mco_learn_txt'        => __( 'Learn more', 'woocommerce-multilingual' ),
				'custom_prices_select'       => [
					'checked' => $this->woocommerce_wpml->settings['display_custom_prices'] == 1,
					'label'   => __( 'Show only products with custom prices in secondary currencies', 'woocommerce-multilingual' ),
					'tip'     => __( 'When this option is on, when you switch to a secondary currency on the front end, only the products with custom prices in that currency are being displayed. Products with prices determined based on the exchange rate are hidden.', 'woocommerce-multilingual' ),
				],
				'submit'                     => __( 'Save changes', 'woocommerce-multilingual' ),
				'navigate_warn'              => __( 'The changes you made will be lost if you navigate away from this page.', 'woocommerce-multilingual' ),
				'cur_lang_warn'              => __( 'At least one currency must be enabled for this language!', 'woocommerce-multilingual' ),

			],

			'currency_switcher'      => [
				'headers'                   => [
					'main'           => __( 'Currency switcher options', 'woocommerce-multilingual' ),
					'main_desc'      => __( 'All currency switchers in your site are affected by the settings in this section.', 'woocommerce-multilingual' ),
					'order'          => __( 'Order of currencies', 'woocommerce-multilingual' ),
					'additional_css' => __( 'Additional CSS', 'woocommerce-multilingual' ),
					'widget'         => __( 'Widget Currency Switcher', 'woocommerce-multilingual' ),
					'product_page'   => __( 'Product page Currency Switcher', 'woocommerce-multilingual' ),
					'preview'        => __( 'Preview', 'woocommerce-multilingual' ),
					'position'       => __( 'Position', 'woocommerce-multilingual' ),
					'actions'        => __( 'Actions', 'woocommerce-multilingual' ),
					'action'         => __( 'Action', 'woocommerce-multilingual' ),
					'delete'         => __( 'Delete', 'woocommerce-multilingual' ),
					'edit'           => __( 'Edit currency switcher', 'woocommerce-multilingual' ),
					'add_widget'     => __( 'Add a new currency switcher to a widget area', 'woocommerce-multilingual' ),
				],
				'preview'                   => $this->get_currency_switchers_preview(),
				'widget_currency_switchers' => $this->widget_currency_switchers(),
				'available_sidebars'        => $this->woocommerce_wpml->multi_currency->currency_switcher->get_available_sidebars(),
				'preview_text'              => __( 'Currency switcher preview', 'woocommerce-multilingual' ),
				'order'                     => ! isset( $this->woocommerce_wpml->settings['currencies_order'] ) ?
										$this->woocommerce_wpml->multi_currency->get_currency_codes() :
										$this->woocommerce_wpml->settings['currencies_order'],
				'order_nonce'               => wp_create_nonce( 'set_currencies_order_nonce' ),
				'delete_nonce'              => wp_create_nonce( 'delete_currency_switcher' ),
				'order_tip'                 => __( 'Drag and drop the currencies to change their order', 'woocommerce-multilingual' ),
				'visibility_label'          => __( 'Show a currency selector on the product page template', 'woocommerce-multilingual' ),
				'visibility_on'             => isset( $this->woocommerce_wpml->settings['currency_switcher_product_visibility'] ) ?
										$this->woocommerce_wpml->settings['currency_switcher_product_visibility'] : 1,
				'additional_css'            => isset( $this->woocommerce_wpml->settings['currency_switcher_additional_css'] ) ?
										$this->woocommerce_wpml->settings['currency_switcher_additional_css'] : '',
			],
			'exchange_rates'         => $exchange_rates_ui->get_model(),
		];

		return $model;

	}

	protected function init_template_base_dir() {
		$this->template_paths = [
			WCML_PLUGIN_PATH . '/templates/multi-currency/',
		];
	}

	public function get_template() {
		return 'multi-currency.twig';
	}

	public function get_flag_url( $code ) {
		return $this->sitepress->get_flag_url( $code );
	}

	public function get_currency_symbol( $code ) {
		return get_woocommerce_currency_symbol( $code );
	}

	public function load_curency_switcher_option_boxes() {

		$wcml_settings      = $this->woocommerce_wpml->get_settings();
		$currency_switchers = isset( $wcml_settings['currency_switchers'] ) ? $wcml_settings['currency_switchers'] : [];

		// add empty dialog for new sidebar currency switcher
		$currency_switchers['new_widget'] = [
			'switcher_style'     => 'wcml-dropdown',
			'widget_title'       => '',
			'switcher_templates' => $this->woocommerce_wpml->cs_templates->get_templates(),
			'template'           => '%name% (%symbol%) - %code%',
			'color_scheme'       => [
				'font_current_normal'       => '',
				'font_current_hover'        => '',
				'background_current_normal' => '',
				'background_current_hover'  => '',
				'font_other_normal'         => '',
				'font_other_hover'          => '',
				'background_other_normal'   => '',
				'background_other_hover'    => '',
				'border_normal'             => '',
			],
		];

		if ( ! isset( $currency_switchers['product'] ) ) {
			$currency_switchers['product'] = $currency_switchers['new_widget'];
		}

		$widget_currency_switchers = $this->widget_currency_switchers();

		foreach ( $currency_switchers as $switcher_id => $currency_switcher ) {

			if ( 'new_widget' !== $switcher_id && ! $this->woocommerce_wpml->cs_properties->is_currency_switcher_active( $switcher_id, $wcml_settings ) ) {
				continue;
			}

			if ( $switcher_id == 'product' ) {
				$dialog_title = __( 'Edit Product Currency Switcher', 'woocommerce-multilingual' );
			} elseif ( $switcher_id == 'new_widget' ) {
				$dialog_title = __( 'New Widget Area Currency Switcher', 'woocommerce-multilingual' );
			} else {
				$dialog_title = sprintf( __( 'Edit %s Currency Switcher', 'woocommerce-multilingual' ), $widget_currency_switchers[ $switcher_id ]['name'] );
			}

			$args = [
				'title'              => $dialog_title,
				'currency_switcher'  => $switcher_id,
				'switcher_style'     => $currency_switcher['switcher_style'],
				'widget_title'       => $currency_switcher['widget_title'],
				'switcher_templates' => $this->woocommerce_wpml->cs_templates->get_templates(),
				'template'           => $currency_switcher['template'],
				'template_default'   => '%name% (%symbol%) - %code%',
				'options'            => $currency_switcher['color_scheme'],
			];

			new WCML_Currency_Switcher_Options_Dialog( $args, $this->woocommerce_wpml );
		}
	}

	public function get_currency_switchers_preview() {
		$preview = [
			'product' => $this->woocommerce_wpml->multi_currency->currency_switcher->wcml_currency_switcher(
				[
					'switcher_id' => 'product',
					'echo'        => false,
				]
			),
		];

		foreach ( $this->widget_currency_switchers() as $switcher ) {
			$preview[ $switcher['id'] ] = $this->woocommerce_wpml->multi_currency->currency_switcher->wcml_currency_switcher(
				[
					'switcher_id' => $switcher['id'],
					'echo'        => false,
				]
			);
		}

		return $preview;
	}

	public function wp_do_action( $hook ) {
		do_action( $hook );
	}

	public function get_weekday( $day_index ) {
		global $wp_locale;
		return $wp_locale->get_weekday( $day_index );
	}

	public function widget_currency_switchers() {
		$wcml_settings      = $this->woocommerce_wpml->get_settings();
		$currency_switchers = isset( $wcml_settings['currency_switchers'] ) ? $wcml_settings['currency_switchers'] : [];
		$sidebars           = $this->woocommerce_wpml->multi_currency->currency_switcher->get_registered_sidebars();
		foreach ( $sidebars as $key => $sidebar ) {
			if ( ! isset( $currency_switchers[ $key ] ) ) {
				unset( $sidebars[ $key ] );
			}
		}

		return $sidebars;
	}

}
