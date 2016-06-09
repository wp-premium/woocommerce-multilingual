<?php

class WCML_WC_Shipping{

    private $current_language;

    function __construct( &$sitepress ){

        add_filter('woocommerce_package_rates', array($this, 'translate_shipping_methods_in_package'));
        add_action('woocommerce_tax_rate_added', array($this, 'register_tax_rate_label_string'), 10, 2 );
        add_filter('woocommerce_rate_label',array($this,'translate_woocommerce_rate_label'));

        $this->shipping_methods_filters();
        add_action('wp_ajax_woocommerce_shipping_zone_methods_save_settings', array( $this, 'save_shipping_zone_method_from_ajax'), 9 );

        $this->current_language = $sitepress->get_current_language();
        if( $this->current_language == 'all' ){
            $this->current_language = $sitepress->get_default_language();
        }
    }

    function shipping_methods_filters(){

        $shipping_methods = WC()->shipping->get_shipping_methods();

        foreach ( $shipping_methods as $shipping_method ) {

            if( isset( $shipping_method->id ) ){
                $shipping_method_id = $shipping_method->id;
            }else{
                continue;
            }

            if( ( defined('WC_VERSION') && version_compare( WC_VERSION , '2.6', '<' ) ) ){
                add_filter( 'woocommerce_settings_api_sanitized_fields_'.$shipping_method_id, array( $this, 'register_shipping_strings' ) );
            }else{
                add_filter( 'woocommerce_shipping_' . $shipping_method_id . '_instance_settings_values', array( $this, 'register_zone_shipping_strings' ),9,2 );
            }

            add_filter( 'option_woocommerce_'.$shipping_method_id.'_settings', array( $this, 'translate_shipping_strings' ), 9, 2 );
        }
    }

    function save_shipping_zone_method_from_ajax(){
        foreach( $_POST['data'] as $key => $value ){
            if( strstr( $key, '_title' ) ){
                $shipping_id = str_replace( 'woocommerce_', '', $key );
                $shipping_id = str_replace( '_title', '', $shipping_id );
                $this->register_shipping_title( $shipping_id.$_POST['instance_id'], $value );
                break;
  	        }
  	    }
  	}

  	function register_zone_shipping_strings( $instance_settings, $object ){
        if( !empty( $instance_settings['title'] ) ){
            $this->register_shipping_title( $object->id.$object->instance_id, $instance_settings['title'] );
        }

        return $instance_settings;
    }

    function register_shipping_strings( $fields ){
        $shipping = WC_Shipping::instance();

        foreach( $shipping->get_shipping_methods() as $shipping_method ){
            if( isset( $_POST['woocommerce_'.$shipping_method->id.'_enabled'] ) ){
                $shipping_method_id = $shipping_method->id;
                break;
            }
        }

        if( isset( $shipping_method_id ) ){
            $this->register_shipping_title( $shipping_method_id, $fields['title'] );
        }

        return $fields;
    }

    function register_shipping_title( $shipping_method_id, $title ){
        do_action( 'wpml_register_single_string', 'woocommerce', $shipping_method_id .'_shipping_method_title', $title );
    }

    function translate_shipping_strings( $value, $option = false ){

        if( $option && isset( $value['enabled']) && $value['enabled'] == 'no' ){
            return $value;
        }

        $shipping_id = str_replace( 'woocommerce_', '', $option );
        $shipping_id = str_replace( '_settings', '', $shipping_id );

        if( isset( $value['title'] ) ){
            $value['title'] = $this->translate_shipping_method_title( $value['title'], $shipping_id );
        }

        return $value;
    }

    function translate_shipping_methods_in_package( $available_methods ){

        foreach($available_methods as $key => $method){
            $method->label =  $this->translate_shipping_method_title( $method->label, $key );
        }

        return $available_methods;
    }

    function translate_shipping_method_title( $title, $shipping_id ) {
        $shipping_id = str_replace( ':', '', $shipping_id );
        $title = apply_filters( 'wpml_translate_single_string', $title, 'woocommerce', $shipping_id .'_shipping_method_title', $this->current_language );

        return $title;
    }

    function translate_woocommerce_rate_label( $label ){

        $label = apply_filters( 'wpml_translate_single_string', $label, 'woocommerce taxes', $label );

        return $label;
    }

    function register_tax_rate_label_string( $id, $tax_rate ){

        if( !empty( $tax_rate['tax_rate_name'] ) ){
            do_action('wpml_register_single_string', 'woocommerce taxes', $tax_rate['tax_rate_name'] , $tax_rate['tax_rate_name'] );
        }

    }

}