<?php

class WCML_Multi_Currency_Shipping{

    private $multi_currency;

    public function __construct( &$multi_currency ) {

        $this->multi_currency =& $multi_currency;

        add_filter('woocommerce_package_rates', array($this, 'shipping_taxes_filter'));

        add_filter( 'wcml_shipping_price_amount', array($this, 'shipping_price_filter') ); // WCML filters
        add_filter( 'wcml_shipping_free_min_amount', array($this, 'shipping_free_min_amount') ); // WCML filters

        add_filter('option_woocommerce_free_shipping_settings', array($this, 'adjust_min_amount_required'));
    }

    public function shipping_taxes_filter($methods){

        global $woocommerce;
        $woocommerce->shipping->load_shipping_methods();
        $shipping_methods = $woocommerce->shipping->get_shipping_methods();

        foreach($methods as $k => $method){

            // exceptions
            $is_old_table_rate = defined('TABLE_RATE_SHIPPING_VERSION' ) &&
                                 version_compare( TABLE_RATE_SHIPPING_VERSION, '3.0', '<' ) &&
                                 preg_match('/^table_rate-[0-9]+ : [0-9]+$/', $k);

            if(
                isset($shipping_methods[$method->id]) &&
                isset($shipping_methods[$method->id]->settings['type']) &&
                $shipping_methods[$method->id]->settings['type'] == 'percent'
                || $is_old_table_rate
            ){
                continue;
            }

            foreach($method->taxes as $j => $tax){

                $methods[$k]->taxes[$j] = apply_filters('wcml_shipping_price_amount', $methods[$k]->taxes[$j]);

            }

            if($methods[$k]->cost){

                if( isset($shipping_methods[$method->id]) && preg_match('/percent/', $shipping_methods[$method->id]->settings['cost']) ){
                    continue;
                }

                $methods[$k]->cost = apply_filters('wcml_shipping_price_amount', $methods[$k]->cost);
            }

        }

        return $methods;
    }

    public function shipping_price_filter($price) {

        $price = $this->multi_currency->prices->raw_price_filter($price, $this->multi_currency->get_client_currency());

        return $price;

    }

    public function shipping_free_min_amount($price) {

        $price = $this->multi_currency->prices->raw_price_filter($price, $this->multi_currency->get_client_currency());

        return $price;

    }

    public function adjust_min_amount_required($options){

        if(!empty($options['min_amount'])){
            $options['min_amount'] = apply_filters('wcml_shipping_free_min_amount', $options['min_amount']);
        }

        return $options;
    }

}