<?php

class WCML_Multi_Currency_Table_Rate_Shipping{

    public static function set_up(){
        // table rate shipping support
        if(defined('TABLE_RATE_SHIPPING_VERSION')){
            add_filter('woocommerce_table_rate_query_rates', array(__CLASS__, 'table_rate_shipping_rates'));
            add_filter('woocommerce_table_rate_instance_settings', array(__CLASS__, 'table_rate_instance_settings'));
        }
    }

    public static function table_rate_shipping_rates($rates){

        foreach($rates as $k => $rate){

            $rates[$k]->rate_cost                   = apply_filters('wcml_shipping_price_amount', $rates[$k]->rate_cost);
            $rates[$k]->rate_cost_per_item          = apply_filters('wcml_shipping_price_amount', $rates[$k]->rate_cost_per_item);
            $rates[$k]->rate_cost_per_weight_unit   = apply_filters('wcml_shipping_price_amount', $rates[$k]->rate_cost_per_weight_unit);

        }

        return $rates;
    }

    public static function table_rate_instance_settings($settings){

        if(is_numeric($settings['handling_fee'])){
            $settings['handling_fee'] = apply_filters('wcml_shipping_price_amount', $settings['handling_fee']);
        }
        $settings['min_cost'] = apply_filters('wcml_shipping_price_amount', $settings['min_cost']);

        return $settings;
    }


}