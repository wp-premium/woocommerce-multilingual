<?php

class WCML_Custom_Currency_Options extends WPML_Templates_Factory {

    private $woocommerce_wpml;
    private $args;

    function __construct( &$args, &$woocommerce_wpml ){

        $functions = array(
            new Twig_SimpleFunction( 'get_currency_symbol', array( $this, 'get_currency_symbol' ) ),
        );

        parent::__construct( $functions );
        $this->woocommerce_wpml = $woocommerce_wpml;
        $this->args = $args;

        add_action( 'wcml_before_multi_currency_ui', array($this, 'render') );
    }

    public function get_model(){

        $currencies_not_used = array_diff( array_keys( $this->args['wc_currencies'] ),
            array_keys( $this->args['currencies'] ), array( $this->args['default_currency'] ) );
        $current_currency = empty($this->args['currency_code']) ? current( $currencies_not_used ) : $this->args['currency_code'];

        $model = array(

            'args' => $this->args,
            'form' => array(
                'select'        => __( 'Select currency', 'woocommerce-multilingual' ),
                'rate'   => array(
                    'label'         => __( 'Exchange Rate', 'woocommerce-multilingual' ),
                    'only_numeric'  => __( 'Only numeric', 'woocommerce-multilingual' ),
                    'set_on'        => empty($this->args['currency']['updated'] ) ? '' :
                                        sprintf( __( 'Set on %s', 'woocommerce-multilingual' ),
                                            date( 'F j, Y, H:i', strtotime( $this->args['currency']['updated'] ) ) )
                ),
                'preview' => array(
                    'label' => __( 'Currency Preview', 'woocommerce-multilingual' ),
                    'value' => $this->get_price_preview( $current_currency )
                ),
                'position' => array(
                    'label'         => __( 'Currency Position', 'woocommerce-multilingual' ),
                    'left'          => __( 'Left', 'woocommerce-multilingual' ),
                    'right'         => __( 'Right', 'woocommerce-multilingual' ),
                    'left_space'    => __( 'Left with space', 'woocommerce-multilingual' ),
                    'right_space'   => __( 'Right with space', 'woocommerce-multilingual' ),
                ),
                'thousand_sep'      => array(
                    'label' => __( 'Thousand Separator', 'woocommerce-multilingual' )
                ),
                'decimal_sep'       => array(
                    'label' =>__( 'Decimal Separator', 'woocommerce-multilingual' )
                ),
                'num_decimals'      => array(
                    'label' => __( 'Number of Decimals', 'woocommerce-multilingual' ),
                    'only_numeric'  => __( 'Only numeric', 'woocommerce-multilingual' )
                ),
                'rounding'          => array(
                    'label'     => __( 'Rounding to the nearest integer', 'woocommerce-multilingual' ),
                    'disabled'  => __( 'Disabled', 'woocommerce-multilingual' ),
                    'up'        => __( 'Up', 'woocommerce-multilingual' ),
                    'down'      => __( 'Down', 'woocommerce-multilingual' ),
                    'nearest'   => __( 'Nearest', 'woocommerce-multilingual' ),
                    'increment' => __( 'Increment for nearest integer', 'woocommerce-multilingual' )
                ),
                'autosubtract'      => array(
                    'label' => __( 'Autosubtract amount', 'woocommerce-multilingual' ),
                    'only_numeric'  => __( 'Only numeric', 'woocommerce-multilingual' )
                ),

                'cancel' => __( 'Cancel', 'woocommerce-multilingual' ),
                'save'   => __( 'Save', 'woocommerce-multilingual' )


            ),
            'current_currency' => $current_currency


        );

        return $model;
    }

    public function render(){
        echo $this->get_view();
    }

    protected function init_template_base_dir() {
        $this->template_paths = array(
            WCML_PLUGIN_PATH . '/templates/multi-currency/',
        );
    }

    public function get_template() {
        return 'custom-currency-options.twig';
    }

    public function get_currency_symbol( $code ) {
        return get_woocommerce_currency_symbol( $code );
    }

    public function get_price_preview( $currency ){

        if( isset( $this->args['currencies'][$currency] ) ) {

            $this->current_currency_for_preview =& $currency;

            add_filter( 'option_woocommerce_currency_pos', array($this, 'filter_currency_pos') );

            $args = array(
                'currency' => $currency,
                'decimal_separator' => $this->args['currencies'][$currency]['decimal_sep'],
                'thousand_separator' => $this->args['currencies'][$currency]['thousand_sep'],
                'decimals' => $this->args['currencies'][$currency]['num_decimals'],
                'price_format' => get_woocommerce_price_format()
            );
            $price = wc_price( '1234.56', $args );

            remove_filter( 'option_woocommerce_currency_pos', array($this, 'filter_currency_pos') );

            unset($this->current_currency_for_preview);

        } else {
            $args = array(
                'currency' => $currency,
                'price_format' => get_woocommerce_price_format()
            );
            $price = wc_price( '1234.56', $args );
        }

        return $price;
    }

    public function filter_currency_pos( $value ){

        if( isset( $this->args['currencies'][ $this->current_currency_for_preview ]['position'] ) ){
            $value = $this->args['currencies'][ $this->current_currency_for_preview ]['position'];
        }

        return $value;

    }


}