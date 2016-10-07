<?php

class WCML_Multi_Currency_Shipping{

    /**
     * @var WCML_Multi_Currency
     */
    private $multi_currency;

    public function __construct( &$multi_currency ) {
        global $wpdb;

        $this->multi_currency =& $multi_currency;

        // shipping method cost settings
        $rates = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}woocommerce_shipping_zone_methods WHERE method_id IN('flat_rate', 'local_pickup', 'free_shipping')" );
        foreach( $rates as $method ){
            $option_name = sprintf('woocommerce_%s_%d_settings', $method->method_id, $method->instance_id );
            add_filter('option_' . $option_name, array($this, 'convert_shipping_method_cost_settings'));
        }

        // Used for table rate shipping compatibility class
        add_filter( 'wcml_shipping_price_amount', array( $this, 'shipping_price_filter' ) ); // WCML filters
        add_filter( 'wcml_shipping_free_min_amount', array( $this, 'shipping_free_min_amount') ); // WCML filters

        add_filter( 'woocommerce_evaluate_shipping_cost_args', array( $this, 'woocommerce_evaluate_shipping_cost_args') );

        add_filter( 'woocommerce_shipping_packages', array( $this, 'convert_shipping_taxes'), 10 );

        // Before WooCommerce 2.6
        add_filter( 'option_woocommerce_free_shipping_settings', array( $this, 'adjust_min_amount_required' ) );

	    add_filter( 'woocommerce_package_rates', array($this, 'convert_shipping_costs_in_package_rates'), 10, 2 );

    }

    public function convert_shipping_costs_in_package_rates( $rates, $package ){

	    $client_currency = $this->multi_currency->get_client_currency();
	    foreach( $rates as $rate_id => $rate ){
	    	if( isset( $rate->cost ) && $rate->cost ){
			    $rate->cost = $this->multi_currency->prices->raw_price_filter( $rate->cost, $client_currency);
		    }
	    }

    	return $rates;
    }

    public function convert_shipping_method_cost_settings( $settings ){

        $has_free_shipping_coupon = false;
        if ( $coupons = WC()->cart->get_coupons() ) {
            foreach ( $coupons as $code => $coupon ) {

                if (
                    $coupon->is_valid() &&
                    (
                        //backward compatibility for WC < 2.7
                        method_exists( $coupon, 'get_free_shipping' ) ?
                            $coupon->get_free_shipping() :
                            $coupon->enable_free_shipping()
                    )
                ) {
                    $has_free_shipping_coupon = true;
                }
            }
        }

        if( !empty( $settings['requires'] ) ){

            if(
                $settings['requires'] == 'min_amount' ||
                $settings['requires'] == 'either' ||
                $settings['requires'] == 'both' && $has_free_shipping_coupon
            ){
                $settings['min_amount'] = apply_filters( 'wcml_shipping_free_min_amount', $settings['min_amount'] );
            }
        }

        return $settings;
    }

    /**
     * @param $args
     * @param $sum
     * @param $method
     * @return array
     *
     * When using [cost] in the shipping class costs, we need to use the not-converted cart total
     * It will be converted as part of the total cost
     *
     */
    public function woocommerce_evaluate_shipping_cost_args( $args ){

        $args['cost'] = $this->multi_currency->prices->unconvert_price_amount( $args['cost'] );

        return $args;
    }

    public function convert_shipping_taxes( $packages ){

        foreach( $packages as $package_id => $package ){
            foreach( $package['rates'] as $rate_id => $rate  ){
                foreach( $rate->taxes as $tax_id => $tax){

                    $packages[$package_id]['rates'][$rate_id]->taxes[$tax_id] =
                        $this->multi_currency->prices->raw_price_filter( $tax );

                }
            }
        }

        return $packages;
    }

    public function shipping_price_filter($price) {

        $price = $this->multi_currency->prices->raw_price_filter($price, $this->multi_currency->get_client_currency());

        return $price;

    }

    public function shipping_free_min_amount($price) {

        $price = $this->multi_currency->prices->raw_price_filter( $price, $this->multi_currency->get_client_currency() );

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