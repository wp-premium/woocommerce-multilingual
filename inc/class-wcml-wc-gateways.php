<?php

class WCML_WC_Gateways{

    private $current_language;

    function __construct( &$sitepress ){

        add_action( 'init', array( $this, 'init' ), 11 );

        $this->current_language = $sitepress->get_current_language();
        if( $this->current_language == 'all' ){
            $this->current_language = $sitepress->get_default_language();
        }

        add_filter( 'woocommerce_payment_gateways', array( $this, 'loaded_woocommerce_payment_gateways' ) );

    }

    function init(){

        add_filter('woocommerce_gateway_title', array($this, 'translate_gateway_title'), 10, 2);
        add_filter('woocommerce_gateway_description', array($this, 'translate_gateway_description'), 10, 2);

    }


    function loaded_woocommerce_payment_gateways( $load_gateways ){

        foreach( $load_gateways as $key => $gateway ){

            $load_gateway = is_string( $gateway ) ? new $gateway() : $gateway;
            $this->payment_gateways_filters( $load_gateway );
            $load_gateways[ $key ] = $load_gateway;

        }

        return $load_gateways;
    }

    function payment_gateways_filters( $gateway ){

        if( isset( $gateway->id ) ){
            $gateway_id = $gateway->id;

            add_filter( 'woocommerce_settings_api_sanitized_fields_'.$gateway_id, array( $this, 'register_gateway_strings' ) );
            $this->translate_gateway_strings( $gateway );
        }

    }

    function register_gateway_strings( $fields ){

        $wc_payment_gateways = WC_Payment_Gateways::instance();

        foreach( $wc_payment_gateways->payment_gateways() as $gateway ){
            if( isset( $_POST['woocommerce_'.$gateway->id.'_enabled'] ) || isset( $_POST[ $gateway->id.'_enabled'] ) ){
                $gateway_id = $gateway->id;
                break;
            }
        }

        if( isset( $gateway_id ) ){
            do_action('wpml_register_single_string', 'woocommerce', $gateway_id .'_gateway_title', $fields['title'] );

            if( isset( $fields['description'] ) ) {
                do_action('wpml_register_single_string', 'woocommerce', $gateway_id . '_gateway_description', $fields['description']);
            }

            if( isset( $fields['instructions'] ) ){
                do_action('wpml_register_single_string', 'woocommerce', $gateway_id .'_gateway_instructions', $fields['instructions']  );
            }
        }

        return $fields;
    }


    function translate_gateway_strings( $gateway ){

        if( isset( $gateway->enabled ) && $gateway->enabled != 'no' ){

            if( isset( $gateway->instructions ) ){
                $gateway->instructions = $this->translate_gateway_instructions( $gateway->instructions, $gateway->id );
            }

            if( isset( $gateway->description ) ){
                $gateway->description = $this->translate_gateway_description( $gateway->description, $gateway->id );
            }

            if( isset( $gateway->title ) ){
                $gateway->title = $this->translate_gateway_title( $gateway->title, $gateway->id );
            }
        }

        return $gateway;

    }

    function translate_gateway_title( $title, $gateway_id, $language = false ) {
        $title = apply_filters( 'wpml_translate_single_string', $title, 'woocommerce', $gateway_id .'_gateway_title', $language ? $language : $this->current_language );
        return $title;
    }

    function translate_gateway_description( $description, $gateway_id) {
        $description = apply_filters( 'wpml_translate_single_string', $description, 'woocommerce', $gateway_id . '_gateway_description', $this->current_language );
        return $description;
    }

    function translate_gateway_instructions( $instructions, $gateway_id ){
        $instructions = apply_filters( 'wpml_translate_single_string', $instructions, 'woocommerce', $gateway_id . '_gateway_instructions', $this->current_language );
        return $instructions;
    }


}