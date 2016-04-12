<?php

class WCML_WooCommerce_Rest_API_Support{


    function __construct(){
        add_action( 'parse_request', array( $this, 'use_canonical_home_url' ), -10 );
        add_action( 'init', array( $this, 'init' ) );

    }

    function init(){
        global $sitepress,$sitepress_settings;

        //remove rewrite rules filtering for PayPal IPN url
        if( strstr($_SERVER['REQUEST_URI'],'WC_Gateway_Paypal') && $sitepress_settings[ 'urls' ][ 'directory_for_default_language' ] ) {
            remove_filter('option_rewrite_rules', array($sitepress, 'rewrite_rules_filter'));
        }

    }

    // Use url without the language parameter. Needed for the signature match.
    public function use_canonical_home_url(){
        global $wp;

        if(!empty($wp->query_vars['wc-api-version'])) {
            global $wpml_url_filters;
            remove_filter('home_url', array($wpml_url_filters, 'home_url_filter'), -10, 2);

        }



    }

}

?>