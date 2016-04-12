<?php

class WCML_WC_MultiCurrency_W3TC{

    function __construct(){

        add_filter( 'init' , array( $this, 'init' ), 15 );

    }

    function init(){

        add_action( 'wcml_switch_currency', array( $this, 'flush_page_cache' ) );

    }

    function flush_page_cache(){
        w3_require_once( W3TC_LIB_W3_DIR . '/AdminActions/FlushActionsAdmin.php' );
        $flush = new W3_AdminActions_FlushActionsAdmin();
        $flush->flush_pgcache();
    }


}