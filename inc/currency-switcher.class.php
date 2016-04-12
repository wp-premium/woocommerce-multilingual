<?php
  
// Our case:
// customize display currencies
//     
  
class WCML_CurrencySwitcher{

    function __construct(){
        
        add_action('init', array($this, 'init'), 5);

    }
    
    function init(){        

        add_action('wp_ajax_wcml_currencies_order', array($this,'wcml_currencies_order'));
        add_action('wp_ajax_wcml_currencies_switcher_preview', array($this,'wcml_currencies_switcher_preview'));
    }

    function wcml_currencies_order(){
        $nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if(!$nonce || !wp_verify_nonce($nonce, 'set_currencies_order_nonce')){
            die('Invalid nonce');
        }
        global $woocommerce_wpml;

        $woocommerce_wpml->settings['currencies_order'] = explode(';', $_POST['order']);
        $woocommerce_wpml->update_settings();
        echo json_encode(array('message' => __('Currencies order updated', 'woocommerce-multilingual')));
        die;
    }

    function wcml_currencies_switcher_preview(){
        $nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if(!$nonce || !wp_verify_nonce($nonce, 'wcml_currencies_switcher_preview')){
            die('Invalid nonce');
        }
        global $woocommerce_wpml;

        if( !isset($woocommerce_wpml->multi_currency_support) ){
            require_once WCML_PLUGIN_PATH . '/inc/multi-currency-support.class.php';
            $woocommerce_wpml->multi_currency_support = new WCML_Multi_Currency_Support;
        }

        echo $woocommerce_wpml->multi_currency_support->currency_switcher(array('format' => $_POST['template']?$_POST['template']:'%name% (%symbol%) - %code%','switcher_style' => $_POST['switcher_type'],'orientation'=> $_POST['orientation']));

        die();
    }

}