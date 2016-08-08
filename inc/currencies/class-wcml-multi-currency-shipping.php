<?php

class WCML_Multi_Currency_Shipping{

    private $multi_currency;

    public function __construct( &$multi_currency ) {
        global $wpdb;

        $this->multi_currency =& $multi_currency;

        $rates = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}woocommerce_shipping_zone_methods WHERE method_id IN('flat_rate', 'local_pickup', 'free_shipping')" );
        foreach( $rates as $method ){
            $option_name = sprintf('woocommerce_%s_%d_settings', $method->method_id, $method->instance_id );
            add_filter('option_' . $option_name, array($this, 'convert_shipping_cost'));
        }

        add_filter( 'wcml_shipping_price_amount', array( $this, 'shipping_price_filter' ) ); // WCML filters
        add_filter( 'wcml_shipping_free_min_amount', array( $this, 'shipping_free_min_amount') ); // WCML filters

        // Before WooCommerce 2.6
        add_filter('option_woocommerce_free_shipping_settings', array( $this, 'adjust_min_amount_required' ) );

    }

    public function convert_shipping_cost( $settings ){

        if( isset($settings['cost']) ){
            $settings['cost'] = $this->multi_currency->prices->raw_price_filter($settings['cost'], $this->multi_currency->get_client_currency());
        }

        if( !empty( $settings['requires'] ) && $settings['requires'] == 'min_amount' ){
            $settings['min_amount'] = apply_filters( 'wcml_shipping_free_min_amount', $settings['min_amount'] );
        }

        return $settings;
    }

    public function shipping_price_filter($price) {

        $price = $this->multi_currency->prices->raw_price_filter($price, $this->multi_currency->get_client_currency());

        return $price;

    }

    public function shipping_free_min_amount($price) {

        $price = $this->multi_currency->prices->raw_price_filter($price, $this->multi_currency->get_client_currency());

        return $price;

    }

    // Before WooCommerce 2.6
    public function adjust_min_amount_required($options){

        if( !empty( $options['min_amount'] ) ){
            $options['min_amount'] = apply_filters( 'wcml_shipping_free_min_amount', $options['min_amount'] );
        }

        return $options;
    }

}