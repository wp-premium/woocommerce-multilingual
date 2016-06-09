<?php

class WCML_Multi_Currency_Coupons{

    public function __construct() {

        add_action('woocommerce_coupon_loaded', array($this, 'filter_coupon_data'));

    }

    public function filter_coupon_data($coupon){

        // Alias compatibility
        if( isset( $coupon->amount ) && !isset( $coupon->coupon_amount ) ){
            $coupon->coupon_amount = $coupon->amount;
        }
        if( isset( $coupon->type ) && !isset( $coupon->discount_type ) ){
            $coupon->discount_type = $coupon->type;
        }
        //

        if($coupon->discount_type == 'fixed_cart' || $coupon->discount_type == 'fixed_product'){
            $coupon->coupon_amount = apply_filters('wcml_raw_price_amount', $coupon->coupon_amount);
        }

        $coupon->minimum_amount = apply_filters('wcml_raw_price_amount',  $coupon->minimum_amount);
        $coupon->maximum_amount = apply_filters('wcml_raw_price_amount',  $coupon->maximum_amount);

    }

}