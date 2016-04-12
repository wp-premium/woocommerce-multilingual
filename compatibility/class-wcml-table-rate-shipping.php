<?php

class WCML_Table_Rate_Shipping{

    function __construct(){

        add_action('init', array($this, 'init'),9);
    }

    function init(){
        global $pagenow;

        //register shipping label
        if($pagenow == 'admin.php' && isset($_GET['page']) && $_GET['page']=='shipping_zones' && isset( $_POST['shipping_label'] ) && isset( $_POST['woocommerce_table_rate_title'] )){
            do_action('wpml_register_single_string', 'woocommerce', $_POST['woocommerce_table_rate_title'] .'_shipping_method_title', $_POST['woocommerce_table_rate_title']);
            $shipping_labels = array_map( 'woocommerce_clean', $_POST['shipping_label'] );
            foreach($shipping_labels as $shipping_label){
                do_action('wpml_register_single_string', 'woocommerce', $shipping_label .'_shipping_method_title', $shipping_label);
            }
        }
        
    }

}
