<?php

class WCML_Currencies{

    private $woocommerce_wpml;

    public function __construct( &$woocommerce_wpml ) {
        $this->woocommerce_wpml =& $woocommerce_wpml;

	    if( is_admin() && wcml_is_multi_currency_on() ){
		    add_action( 'update_option_woocommerce_currency', array( $this, 'update_default_currency' ), 10, 2 );
	    }

    }

    public function update_default_currency( $old_value, $new_value ){

	    $this->woocommerce_wpml->multi_currency = new WCML_Multi_Currency();
	    $WCML_Multi_Currency_Install = new WCML_Multi_Currency_Install( $this->woocommerce_wpml->multi_currency, $this->woocommerce_wpml );
	    $WCML_Multi_Currency_Install->set_default_currencies_languages( $old_value, $new_value );

    }

}