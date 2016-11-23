<?php

class WCML_WC_Subscriptions{

    private $new_subscription = false;

    function __construct(){

        add_action('init', array($this, 'init'),9);
        add_filter('wcml_variation_term_taxonomy_ids',array($this,'wcml_variation_term_taxonomy_ids'));
        add_filter('woocommerce_subscription_lengths', array($this, 'woocommerce_subscription_lengths'), 10, 2);

        add_filter('wcml_register_endpoints_query_vars', array($this, 'register_endpoint' ), 10, 3 );
        add_filter('wcml_endpoint_permalink_filter', array($this, 'endpoint_permalink_filter'), 10, 2);

        //custom prices
        add_filter( 'wcml_custom_prices_fields', array( $this, 'set_prices_fields' ), 10, 2 );
        add_filter( 'wcml_custom_prices_strings', array( $this, 'set_labels_for_prices_fields' ), 10, 2 );
        add_filter( 'wcml_custom_prices_fields_labels', array( $this, 'set_labels_for_prices_fields' ), 10, 2 );
        add_filter( 'wcml_update_custom_prices_values', array( $this, 'update_custom_prices_values' ), 10 ,3 );
        add_action( 'wcml_after_custom_prices_block', array( $this, 'new_subscription_prices_block') );

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

    function set_prices_fields( $fields, $product_id ){
        if( $this->is_subscriptions_product( $product_id ) || $this->new_subscription ){
            $fields[] = '_subscription_sign_up_fee';
        }

        return $fields;

    }

    function set_labels_for_prices_fields( $labels, $product_id ){

        if( $this->is_subscriptions_product( $product_id ) || $this->new_subscription ){
            $labels[ '_regular_price' ] = __( 'Subscription Price', 'woocommerce-multilingual' );
            $labels[ '_subscription_sign_up_fee' ] = __( 'Sign-up Fee', 'woocommerce-multilingual' );
        }

        return $labels;

    }

    function update_custom_prices_values( $prices, $code, $variation_id = false ){

        if( isset( $_POST[ '_custom_subscription_sign_up_fee' ][ $code ]  ) ){
            $prices[ '_subscription_sign_up_fee' ] = wc_format_decimal( $_POST[ '_custom_subscription_sign_up_fee' ][ $code ] );
        }

        if( $variation_id && isset( $_POST[ '_custom_variation_subscription_sign_up_fee' ][ $code ][ $variation_id ]  ) ){
            $prices[ '_subscription_sign_up_fee' ] = wc_format_decimal( $_POST[ '_custom__custom_variation_subscription_sign_up_fee' ][ $code ][ $variation_id ] );
        }

        return $prices;

    }

    function is_subscriptions_product( $product_id ){
        global $wpdb;
        $get_variation_term_taxonomy_ids = $wpdb->get_col("SELECT tt.term_taxonomy_id FROM $wpdb->terms AS t LEFT JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE t.slug IN ( 'subscription', 'variable-subscription' ) AND tt.taxonomy = 'product_type'");

        if( get_post_type( $product_id ) == 'product_variation' ){
            $product_id = wp_get_post_parent_id( $product_id );
        }

        $is_subscriptions_product = $wpdb->get_var($wpdb->prepare("SELECT count(object_id) FROM $wpdb->term_relationships WHERE object_id = %d AND term_taxonomy_id IN (".join(',',$get_variation_term_taxonomy_ids).")",$product_id));
        return $is_subscriptions_product;
    }

    function new_subscription_prices_block( $product_id ){
        global $woocommerce_wpml;
        if( $product_id == 'new' ){
            $this->new_subscription = true;
            echo '<div class="wcml_prices_if_subscription" style="display: none">';
            $custom_prices_ui = new WCML_Custom_Prices_UI( $woocommerce_wpml, 'new' );
            $custom_prices_ui->show();
            echo '</div>';
            ?>
            <script>
                jQuery(document).ready(function($) {
                    jQuery('.wcml_prices_if_subscription .wcml_custom_prices_input').attr('name', '_wcml_custom_prices[new_subscription]').attr( 'id', '_wcml_custom_prices[new_subscription]');
                    jQuery('.wcml_prices_if_subscription .wcml_custom_prices_options_block>label').attr('for', '_wcml_custom_prices[new_subscription]');
                    jQuery('.wcml_prices_if_subscription .wcml_schedule_input').each( function(){
                        jQuery(this).attr('name', jQuery(this).attr('name')+'_subscription');
                    });

                    jQuery('.options_group>.wcml_custom_prices_block .wcml_custom_prices_input:first-child').click();
                    jQuery('.options_group>.wcml_custom_prices_block .wcml_schedule_options .wcml_schedule_input:first-child').click();

                    jQuery(document).on('change', 'select#product-type', function () {
                        if (jQuery(this).val() == 'subscription') {
                            jQuery('.wcml_prices_if_subscription').show();
                            jQuery('.options_group>.wcml_custom_prices_block').hide();
                        } else if (jQuery(this).val() != 'variable-subscription') {
                            jQuery('.wcml_prices_if_subscription').hide();
                            jQuery('.options_group>.wcml_custom_prices_block').show();
                        }
                    });

                    jQuery(document).on('click', '#publish', function () {
                        if ( jQuery('.wcml_prices_if_subscription').is( ':visible' ) ) {
                            jQuery('.options_group>.wcml_custom_prices_block').remove();
                            jQuery('.wcml_prices_if_subscription .wcml_custom_prices_input').attr('name', '_wcml_custom_prices[new]');
                            jQuery('.wcml_prices_if_subscription .wcml_schedule_input').each( function(){
                                jQuery(this).attr('name', jQuery(this).attr('name').replace('_subscription','') );
                            });
                        }else{
                            jQuery('.wcml_prices_if_subscription').remove();
                        }
                    });
                });
            </script>
        <?php
        }
    }

    function register_endpoint( $query_vars, $wc_vars, $obj ){

        $query_vars[ 'view-subscription' ] = $obj->get_endpoint_translation( 'view-subscription',  isset( $wc_vars['view-subscription'] ) ? $wc_vars['view-subscription'] : 'view-subscription' );
        return $query_vars;
    }

    function endpoint_permalink_filter( $endpoint, $key ){

        if( $key == 'view-subscription' ){
            return 'view-subscription';
        }

        return $endpoint;
    }
}
