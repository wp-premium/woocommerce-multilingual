<?php

class WCML_WC_Subscriptions{

    function __construct(){

        add_action('init', array($this, 'init'),9);
        add_filter('wcml_variation_term_taxonomy_ids',array($this,'wcml_variation_term_taxonomy_ids'));
        add_filter('woocommerce_subscription_lengths', array($this, 'woocommerce_subscription_lengths'), 10, 2);
        
        // reenable coupons for subscriptions when multicurrency is on
        add_action('woocommerce_subscription_cart_after_grouping', array($this, 'woocommerce_subscription_cart_after_grouping'));
    }

    function init(){
        if( !is_admin() ){
            add_filter('woocommerce_subscriptions_product_sign_up_fee', array($this, 'product_price_filter'), 10, 2);                
        }
    }
    
    function product_price_filter($subscription_sign_up_fee, $product){
        
        $subscription_sign_up_fee = apply_filters('wcml_raw_price_amount', $subscription_sign_up_fee );
        
        return $subscription_sign_up_fee;
    }

    function wcml_variation_term_taxonomy_ids($get_variation_term_taxonomy_ids){
        global $wpdb;
        $get_variation_term_taxonomy_id = $wpdb->get_var("SELECT tt.term_taxonomy_id FROM $wpdb->terms AS t LEFT JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE t.slug = 'variable-subscription'");
        
        if(!empty($get_variation_term_taxonomy_id)){
            $get_variation_term_taxonomy_ids[] = $get_variation_term_taxonomy_id;    
        }
        
        return $get_variation_term_taxonomy_ids;
    }
    
    public function woocommerce_subscription_lengths($subscription_ranges, $subscription_period) {
        
        if (is_array($subscription_ranges)) {
            foreach ($subscription_ranges as $period => $ranges) {
                if (is_array($ranges)) {
                    foreach ($ranges as $range) {
                        if ($range == "9 months") {
                            $breakpoint = true;
                        }
                        $new_subscription_ranges[$period][] = apply_filters( 'wpml_translate_single_string', $range, 'wc_subscription_ranges', $range); 
                    }
                }
            }
        }
        
        return isset($new_subscription_ranges) ? $new_subscription_ranges : $subscription_ranges;
    }
    
    public function woocommerce_subscription_cart_after_grouping() {
        global $woocommerce_wpml;
        
        if( $woocommerce_wpml->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT ){
            remove_action('woocommerce_before_calculate_totals', 'WC_Subscriptions_Coupon::remove_coupons', 10);
        }
        
    }
}
