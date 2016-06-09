<?php

class WCML_Dynamic_Pricing{

    function __construct(){
        if(!is_admin()){
            add_filter('wc_dynamic_pricing_load_modules', array($this, 'filter_price'));
            add_filter('woocommerce_dynamic_pricing_is_applied_to', array($this, 'woocommerce_dynamic_pricing_is_applied_to'),10,5);
            add_filter('woocommerce_dynamic_pricing_get_rule_amount',array($this,'woocommerce_dynamic_pricing_get_rule_amount'),10,4);
            add_filter('dynamic_pricing_product_rules',array($this,'dynamic_pricing_product_rules'));
            add_filter('translate_cart_subtotal_exception',array($this,'translate_cart_subtotal_exception'),10,2);

        }
    }

    function filter_price($modules){

        foreach($modules as $mod_key=>$module){
            if(isset($module->available_rulesets)){
                $available_rulesets = $module->available_rulesets;
                foreach($available_rulesets as $rule_key=>$available_ruleset){
                    $rules =  $available_ruleset['rules'];

                    if($rules){
                        foreach($rules as $r_key=>$rule){
                            if($rule['type'] == 'fixed_product'){
                                $rules[$r_key]['amount'] =  apply_filters('wcml_raw_price_amount', $rule['amount']);
                            }
                        }
                        $modules[$mod_key]->available_rulesets[$rule_key]['rules'] = $rules;

                    }
                }

            }
        }

        return $modules;
    }


    function woocommerce_dynamic_pricing_is_applied_to($process_discounts, $_product, $module_id, $obj,$cat_id){
        if($cat_id && isset($obj->available_rulesets) && count($obj->available_rulesets) > 0){
            global $sitepress;
            $cat_id = apply_filters( 'translate_object_id',$cat_id,'product_cat',true,$sitepress->get_current_language());
            $process_discounts = is_object_in_term($_product->id, 'product_cat', $cat_id);
        }

        return $process_discounts;
    }


    function woocommerce_dynamic_pricing_get_rule_amount($amount, $rule, $cart_item, $obj){

        if($rule['type'] == 'price_discount' || $rule['type'] == 'fixed_price'){
            $amount = apply_filters('wcml_raw_price_amount',$amount);
        }

        return $amount;

    }


    function dynamic_pricing_product_rules($rules){
        if(is_array($rules)){
            foreach($rules as $r_key=>$rule){
                foreach($rule['rules'] as $key=>$product_rule){
                    if($product_rule['type'] == 'price_discount' || $product_rule['type'] == 'fixed_price'){
                        $rules[$r_key]['rules'][$key]['amount'] =  apply_filters('wcml_raw_price_amount', $product_rule['amount']);
                    }
                }
            }
        }
        return $rules;
    }

    function translate_cart_subtotal_exception( $value, $cart ){
        return true;
    }

}
