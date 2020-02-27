<?php

class WCML_LiteSpeed_Cache {

	public function add_hooks() {
		// LiteSpeed_Cache_API::vary is available since 2.6.
		if ( method_exists( 'LiteSpeed_Cache_API', 'v' ) && LiteSpeed_Cache_API::v( '2.6' ) ) {
			add_filter( 'wcml_client_currency', [ $this, 'apply_client_currency' ] );
			add_action( 'wcml_set_client_currency', [ $this, 'set_client_currency' ] );
		}
	}

	public function set_client_currency( $currency ) {
		$this->apply_client_currency( $currency );

		LiteSpeed_Cache_API::force_vary();
	}

	public function apply_client_currency( $currency ) {
		LiteSpeed_Cache_API::vary( 'wcml_currency', $currency, wcml_get_woocommerce_currency_option() );

		return $currency;
	}

}

