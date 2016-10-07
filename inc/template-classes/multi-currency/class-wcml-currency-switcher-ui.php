<?php

class WCML_Currency_Switcher_UI extends WPML_Templates_Factory {

    /**
     * @var woocommerce_wpml
     */
    private $woocommerce_wpml;
    /**
     * @var array
     */
    private $args;
    /**
     * @var array
     */
    private $currencies;

    function __construct( &$args, &$woocommerce_wpml, $currencies ){

        $this->woocommerce_wpml =& $woocommerce_wpml;
        $this->args             = $args;
        $this->currencies       = $currencies;

        $functions = array(
            new Twig_SimpleFunction( 'get_formatted_price', array( $this, 'get_formatted_price' ) )
        );

        parent::__construct( $functions );

    }

    public function get_model(){

        $model = array(

            'style'         => isset( $this->args['switcher_style'] ) ? $this->args['switcher_style'] : 'dropdown',
            'orientation'   => isset( $this->args['orientation'] ) && $this->args['orientation'] === 'horizontal' ?
                                'curr_list_horizontal' : 'curr_list_vertical',
            'format'        => $this->args['format'],

            'currencies'    => $this->currencies,

            'selected_currency' => $this->woocommerce_wpml->multi_currency->get_client_currency()

        );


        return $model;
    }

    public function get_formatted_price( $currency, $format ){

        $wc_currencies = get_woocommerce_currencies();
        if( preg_match( '#%subtotal%#', $format ) ){ // include cart total
            $cart_object =& $this->woocommerce_wpml->cart;
        }
        $wcml_settings  =  $this->woocommerce_wpml->get_settings();
        $multi_currency =& $this->woocommerce_wpml->multi_currency;

        if( preg_match( '#%subtotal%#', $format ) ) { // include cart total
            if( !is_admin() ){

                $multi_currency->set_client_currency( $currency );
                $cart_object->woocommerce_calculate_totals( WC()->cart, $currency );
                $cart_subtotal = WC()->cart->get_cart_subtotal();

            }else{
                switch( $wcml_settings['currency_options'][$currency]['position'] ){
                    case 'left' :
                        $price_format = '%1$s%2$s';
                        break;
                    case 'right' :
                        $price_format = '%2$s%1$s';
                        break;
                    case 'left_space' :
                        $price_format = '%1$s&nbsp;%2$s';
                        break;
                    case 'right_space' :
                        $price_format = '%2$s&nbsp;%1$s';
                        break;
                }
                $cart_subtotal = wc_price('1234.56',
                    array(
                        'currency' => $currency,
                        'decimal_separator' => $wcml_settings['currency_options'][$currency]['decimal_sep'],
                        'thousand_separator' => $wcml_settings['currency_options'][$currency]['thousand_sep'],
                        'decimals' => $wcml_settings['currency_options'][$currency]['num_decimals'],
                        'price_format' => $price_format
                    )
                );
            }
        }else{
            $cart_subtotal = false;
        }

        $currency_format = preg_replace( array('#%name%#', '#%symbol%#', '#%code%#', '#%subtotal%#'),
            array(
                $wc_currencies[$currency],
                get_woocommerce_currency_symbol( $currency ),
                $currency,
                $cart_subtotal

            ), $format );

        if( preg_match( '#%subtotal%#', $format )  && !is_admin() ) { // include cart total
            $multi_currency->set_client_currency( $multi_currency->get_client_currency() );
        }

        return $currency_format;
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
        return 'currency-switcher.twig';
    }



}