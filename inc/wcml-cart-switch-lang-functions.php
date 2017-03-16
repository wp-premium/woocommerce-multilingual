<?php

class WCML_Cart_Switch_Lang_Functions{

    private $lang_from;
    private $lang_to;


    function __construct(){

        add_action( 'wp_footer', array( $this, 'wcml_language_switch_dialog' ) );
        add_action( 'wp_loaded', array( $this, 'wcml_language_force_switch' ) );
        add_action( 'wcml_user_switch_language', array( $this, 'language_has_switched' ), 10 , 2 );

    }

    public function language_has_switched( $lang_from, $lang_to ){

        $settings = get_option( '_wcml_settings' );

        if(
            !isset( $_GET[ 'force_switch' ] ) &&
            $lang_from != $lang_to &&
            !empty( $settings ) &&
            $settings[ 'cart_sync' ][ 'lang_switch' ] == WCML_CART_CLEAR
        ){
            $this->lang_from = $lang_from;
            $this->lang_to = $lang_to;
        }
    }

    function wcml_language_force_switch(){
        global $woocommerce_wpml, $woocommerce;

        if( isset( $_GET[ 'force_switch' ] ) && $_GET[ 'force_switch' ] == true ){
            $woocommerce_wpml->cart->empty_cart_if_needed( 'lang_switch' );
            $woocommerce->session->set( 'wcml_switched_type', 'lang_switch' );
        }
    }

    function wcml_language_switch_dialog( ){
        global $woocommerce_wpml, $sitepress, $wp;

        $dependencies = new WCML_Dependencies;

        if( $dependencies->check() ){

            $current_url = home_url( add_query_arg( array(), $wp->request ) );
            $request_url = add_query_arg( 'force_switch', 0, $sitepress->convert_url( $current_url, $this->lang_from ) );

            $cart_for_session = false;
            if( isset( WC()->cart ) ){
                $cart_for_session = WC()->cart->get_cart_for_session();
            }

            if( $this->lang_from && $this->lang_to && $request_url && !empty( $cart_for_session ) ) {

                $force_cart_url = add_query_arg( 'force_switch', 1, $current_url );

                $new_language_details = $sitepress->get_language_details( $this->lang_to );
                $current_language_details = $sitepress->get_language_details( $this->lang_from );
                $dialog_title = __( 'Switching language?', 'woocommerce-multilingual' );
                $confirmation_message = esc_html ( sprintf(
                	__( "You've switched language and there are items in the cart. If you keep the %s language, the cart will be emptied and you will have to add the items again to the cart.", 'woocommerce-multilingual' ),
	                $new_language_details[ 'display_name' ]
                ) );
                $stay_in = sprintf( __( 'Stay in %s', 'woocommerce-multilingual' ), $new_language_details[ 'display_name' ] );
                $switch_to = sprintf( __( 'Switch back to %s', 'woocommerce-multilingual' ), $current_language_details[ 'display_name' ] );

                $woocommerce_wpml->cart->cart_alert( $dialog_title, $confirmation_message, $stay_in, $switch_to, $force_cart_url, $request_url, true );
            }

        }

    }

}