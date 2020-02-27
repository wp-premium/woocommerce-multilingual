<?php

class WCML_Widgets {

	private $woocommerce_wpml;

	/**
	 * WCML_Widgets constructor.
	 *
	 * @param woocommerce_wpml $woocommerce_wpml
	 */
	public function __construct( $woocommerce_wpml ) {
		// @todo Cover by tests, required for wcml-3037.
		$this->woocommerce_wpml = $woocommerce_wpml;

		add_action( 'widgets_init', [ $this, 'register_widgets' ] );
	}

	public function register_widgets() {

		if ( $this->woocommerce_wpml->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT ) {
			register_widget( 'WCML_Currency_Switcher_Widget' );
		}

		if ( $this->woocommerce_wpml->settings['cart_sync']['currency_switch'] == WCML_CART_CLEAR || $this->woocommerce_wpml->settings['cart_sync']['lang_switch'] == WCML_CART_CLEAR ) {
			register_widget( 'WCML_Cart_Removed_Items_Widget' );
		}

	}

}
