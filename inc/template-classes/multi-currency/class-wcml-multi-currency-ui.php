<?php

class WCML_Multi_Currency_UI extends WPML_Templates_Factory {

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

    function __construct( &$woocommerce_wpml, &$sitepress ){

        $functions = array(
            new Twig_SimpleFunction( 'get_flag_url', array( $this, 'get_flag_url' ) ),
            new Twig_SimpleFunction( 'get_rate', array( $this, 'get_rate' ) ),
            new Twig_SimpleFunction( 'is_currency_on', array( $this, 'is_currency_on' ) ),
            new Twig_SimpleFunction( 'get_language_currency', array( $this, 'get_language_currency' ) ),
            new Twig_SimpleFunction( 'get_currency_symbol', array( $this, 'get_currency_symbol' ) ),
            new Twig_SimpleFunction( 'get_currency_name', array( $this, 'get_currency_name' ) ),
            new Twig_SimpleFunction( 'wp_do_action', array( $this, 'wp_do_action' ) )
        );

        parent::__construct( $functions );
        $this->woocommerce_wpml = $woocommerce_wpml;
        $this->sitepress = $sitepress;

        $this->currencies       = $this->woocommerce_wpml->multi_currency->get_currencies();
        $this->wc_currencies    = get_woocommerce_currencies();
        $this->wc_currency      = get_option( 'woocommerce_currency' );

        $this->load_custom_currency_option_boxes();

    }

    public function get_model(){

        $currencies_positions = array();
        foreach ( $this->currencies as $code => $currency ){
            $currencies_positions[$code] = $this->price_position_format( $currency['position'], $code );
        }


        $model = array(
            'strings' => array(
                'headers' => array(
                    'enable_disable'    => __( 'Enable/disable', 'woocommerce-multilingual' ),
                    'currencies'        => __( 'Currencies', 'woocommerce-multilingual' ),
                ),
                'add_currency_button'   => __( 'Add currency', 'woocommerce-multilingual' ),
                'currencies_table' => array(
                    'head_currency'     => __('Currency', 'woocommerce-multilingual'),
                    'head_rate'         => __('Rate', 'woocommerce-multilingual'),
                    'default'           => __( 'default', 'woocommerce-multilingual' ),
                    'edit'              => __( 'Edit', 'woocommerce-multilingual' ),
                    'default_currency'  => __( 'Default currency', 'woocommerce-multilingual' ),
                    'default_cur_tip'   => __( 'Switch to this currency when switching language in the front-end', 'woocommerce-multilingual' ),
                    'keep_currency'     => __( 'Keep', 'woocommerce-multilingual' ),
                    'delete'            => __( 'Delete', 'woocommerce-multilingual' ),
                    'help_title'        => __( 'Currencies to display for each language', 'woocommerce-multilingual' ),
                    'enable_for'        => __('Enable %s for %s', 'woocommerce-multilingual'),
                    'disable_for'       => __('Disable %s for %s', 'woocommerce-multilingual')
                )

            ),
            'currencies'            => $this->currencies,
            'currencies_positions'  => $currencies_positions,
            'wc_currency'           => $this->wc_currency,
            'wc_currencies'         => $this->wc_currencies,
            'positioned_price'      => sprintf( __( ' (%s)', 'woocommerce-multilingual' ), $this->get_positioned_price( $this->wc_currency ) ) ,

            'active_languages'      => $this->sitepress->get_active_languages(),

            'multi_currency_on'     => $this->woocommerce_wpml->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT,

            'wc_currency_empty_warn' => sprintf(__('The multi-currency mode cannot be enabled as a specific currency was not set. Go to the %sWooCommerce settings%s page and select the default currency for your store.',
                                        'woocommerce-multilingual'), '<a href="' . admin_url('admin.php?page=wc-settings') . '">', '</a>'),
            'wcml_settings' => $this->woocommerce_wpml->settings,
            'form' => array(
                'action'                    => $_SERVER['REQUEST_URI'],
                'nonce'                     => wp_nonce_field( 'wcml_mc_options', 'wcml_nonce', true, false ),
                'save_currency_nonce'       => wp_create_nonce( 'save_currency' ),
                'del_currency_nonce'        => wp_create_nonce( 'wcml_delete_currency' ),
                'multi_currency_option'     => WCML_MULTI_CURRENCIES_INDEPENDENT,
                'mco_disabled'              => empty($wc_currency),
                'label_mco'                 => __( "Enable the multi-currency mode", 'woocommerce-multilingual' ),
                'label_mco_learn_url'       => WCML_Links::generate_tracking_link( 'https://wpml.org/documentation/related-projects/woocommerce-multilingual/multi-currency-support-woocommerce/', 'multi-currency-support-woocommerce', 'documentation' ),
                'label_mco_learn_txt'       => __( 'Learn more', 'woocommerce-multilingual' ),
                'update_currency_lang_nonce'=> wp_create_nonce( 'wcml_update_currency_lang' ),
                'wpdate_default_cur_nonce'  => wp_create_nonce( 'wcml_update_default_currency' ),
                'custom_prices_select'      => array(
                    'checked'   => $this->woocommerce_wpml->settings['display_custom_prices'] == 1,
                    'label'     => __( 'Show only products with custom prices in secondary currencies', 'woocommerce-multilingual' ),
                    'tip'       => __( 'When this option is on, when you switch to a secondary currency on the front end, only the products with custom prices in that currency are being displayed. Products with prices determined based on the exchange rate are hidden.', 'woocommerce-multilingual' )
                ),
                'submit'        => __( 'Save changes', 'woocommerce-multilingual' ),
                'navigate_warn' => __( 'The changes you made will be lost if you navigate away from this page.', 'woocommerce-multilingual' ),
                'cur_lang_warn' => __( 'At least one currency must be enabled for this language!', 'woocommerce-multilingual' )

            ),

            'currency_switcher' => array(
                'headers' => array(
                    'main'            => __('Currency switcher options', 'woocommerce-multilingual'),
                    'style'           => __('Currency switcher style', 'woocommerce-multilingual'),
                    'order'           => __( 'Currency order', 'woocommerce-multilingual' ),
                    'parameters'      => __( 'Available parameters', 'woocommerce-multilingual' ),
                    'parameters_list' => '%code%, %symbol%, %name%, %subtotal%',
                    'template'        => __( 'Template for currency switcher', 'woocommerce-multilingual' ),
                    'visibility'      => __('Visibility', 'woocommerce-multilingual')
                ),
                'preview_nonce' => wp_create_nonce( 'wcml_currencies_switcher_preview' ),
                    'preview'       => $this->woocommerce_wpml->multi_currency->currency_switcher->wcml_currency_switcher( array('echo' => false) ),
                'preview_text'  => __( 'Currency switcher preview', 'woocommerce-multilingual' ),
                'style'         => isset($this->woocommerce_wpml->settings['currency_switcher_style']) ? $this->woocommerce_wpml->settings['currency_switcher_style'] : false,
                'options' => array(
                    'dropdown'      => __('Drop-down menu', 'woocommerce-multilingual'),
                    'list'          => __('List of currencies', 'woocommerce-multilingual'),
                    'vertical'      => __('Vertical', 'woocommerce-multilingual'),
                    'horizontal'    => __('Horizontal', 'woocommerce-multilingual'),
                    'allowed_tags'  => __('Allowed HTML tags: <img> <span> <u> <strong> <em>', 'woocommerce-multilingual')
                ),
                'orientation'       => isset($this->woocommerce_wpml->settings['wcml_curr_sel_orientation']) ?
                                        $this->woocommerce_wpml->settings['wcml_curr_sel_orientation'] : 'vertical',
                'order'             => !isset( $this->woocommerce_wpml->settings['currencies_order'] ) ?
                                        $this->woocommerce_wpml->multi_currency->get_currency_codes() :
                                        $this->woocommerce_wpml->settings['currencies_order'],
                'order_nonce'       => wp_create_nonce( 'set_currencies_order_nonce' ),
                'order_tip'         => __( 'Drag the currencies to change their order', 'woocommerce-multilingual' ),
                'parameters_tip'    => __( '%name%, %symbol%, %code%', 'woocommerce-multilingual' ),
                'template'          => isset($this->woocommerce_wpml->settings['wcml_curr_template']) ?
                                        $this->woocommerce_wpml->settings['wcml_curr_template'] : '',
                'template_tip'      => __( 'Default: %name% (%symbol%) - %code%', 'woocommerce-multilingual' ),
                'template_default'  => '%name% (%symbol%) - %code%',
                'visibility_label'  => __('Show a currency selector on the product page template', 'woocommerce-multilingual'),
                'visibility_on'     => isset($this->woocommerce_wpml->settings['currency_switcher_product_visibility']) ?
                                        $this->woocommerce_wpml->settings['currency_switcher_product_visibility']:1
            )
        );

        return $model;

    }

    protected function init_template_base_dir() {
        $this->template_paths = array(
            WCML_PLUGIN_PATH . '/templates/multi-currency/',
        );
    }

    public function get_template() {
        return 'multi-currency.twig';
    }

    protected function get_positioned_price( $wc_currency ){

        $woocommerce_currency_pos = get_option( 'woocommerce_currency_pos' );
        $positioned_price = '';

        switch ( $woocommerce_currency_pos ) {
            case 'left':
                $positioned_price = sprintf( '%s99.99', get_woocommerce_currency_symbol( $wc_currency ) );
                break;
            case 'right':
                $positioned_price = sprintf( '99.99%s', get_woocommerce_currency_symbol( $wc_currency ) );
                break;
            case 'left_space':
                $positioned_price = sprintf( '%s 99.99', get_woocommerce_currency_symbol( $wc_currency ) );
                break;
            case 'right_space':
                $positioned_price = sprintf( '99.99 %s', get_woocommerce_currency_symbol( $wc_currency ) );
                break;
        }

        return $positioned_price;

    }

    protected function price_position_format( $position, $code ){

        $positioned_price = '';
        switch ( $position ) {
            case 'left':
                $positioned_price = sprintf( '%s99.99', get_woocommerce_currency_symbol( $code ) );
                break;
            case 'right':
                $positioned_price = sprintf( '99.99%s', get_woocommerce_currency_symbol( $code ) );
                break;
            case 'left_space':
                $positioned_price = sprintf( '%s 99.99', get_woocommerce_currency_symbol( $code ) );
                break;
            case 'right_space':
                $positioned_price = sprintf( '99.99 %s', get_woocommerce_currency_symbol( $code ) );
                break;
        }

        return $positioned_price;

    }

    public function get_flag_url( $code ){
        return $this->sitepress->get_flag_url( $code );
    }

    public function get_rate($wc_currency, $rate, $code){
        return sprintf( '1 %s = %s %s', $wc_currency, $rate, $code );
    }

    public function is_currency_on($currency, $language) {
        return $this->woocommerce_wpml->settings['currency_options'][ $currency ]['languages'][ $language ];
    }

    public function get_language_currency( $language ) {
        return $this->woocommerce_wpml->settings['default_currencies'][ $language ];
    }

    public function get_currency_symbol( $code ) {
        return get_woocommerce_currency_symbol( $code );
    }
    public function get_currency_name( $code ){
        return $this->wc_currencies[$code];
    }


    public function load_custom_currency_option_boxes(){

        $args = array(
            'title'             => __('Add new currency', 'woocommerce-multilingual'),
            'default_currency'  => $this->wc_currency,
            'currencies'        => $this->currencies,
            'wc_currencies'     => $this->wc_currencies,
            'currency_code'     => '',
            'currency_name'     => '',
            'currency_symbol'   => '',
            'currency'          => array(
                'rate' => 1,
                'position'              => 'left',
                'thousand_sep'          => ',',
                'decimal_sep'           => '.',
                'num_decimals'          => 2,
                'rounding'              => 'disabled',
                'rounding_increment'    => 1,
                'auto_subtract'         => 0,
                'updated'               => 0
            ),
            'current_currency'  => current( array_diff( array_keys( $this->wc_currencies ), array_keys( $this->currencies ), array ( $this->wc_currency ) ) )
        );

        new WCML_Custom_Currency_Options($args, $this->woocommerce_wpml);

        foreach($this->currencies as $code => $currency){
            $args['currency_code'] 		= $code;
            $args['currency_name'] 		= $args['wc_currencies'][$args['currency_code']];
            $args['currency_symbol'] 	= get_woocommerce_currency_symbol( $args['currency_code'] );
            $args['currency']			= $currency;
            $args['title'] = sprintf( __( 'Update settings for %s', 'woocommerce-multilingual' ), $args['currency_name'] . ' (' . $args['currency_symbol'] . ')' );

            $args['current_currency'] = $args['currency_code'];

            new WCML_Custom_Currency_Options($args, $this->woocommerce_wpml);

        }


    }

    public function wp_do_action( $hook ){
        do_action( $hook );
    }

}