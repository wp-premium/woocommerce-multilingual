<?php

namespace WCML\Multicurrency\Shipping;

class ShippingHooksFactory implements \IWPML_Deferred_Action_Loader, \IWPML_Backend_Action_Loader, \IWPML_Frontend_Action_Loader {

	public function get_load_action() {
		return 'init';
	}

	public function create() {
		/** @var \woocommerce_wpml $woocommerce_wpml */
		global $woocommerce_wpml;
		$hooks = [];

		if ( wcml_is_multi_currency_on()
		     && $this->hasAdditionalCurrencyDefined()
		) {
			if ( $this->isShippingPageRequest() || $this->isAjaxOnShippingPageRequest() ) {
				$hooks[] = new AdminHooks( $woocommerce_wpml->get_multi_currency() );
			} else {
				$hooks[] = new FrontEndHooks( $woocommerce_wpml->get_multi_currency() );
			}
		}

		return $hooks;
	}

	/**
	 * Does user defined at least one additional currency in WCML.
	 *
	 * @return bool
	 */
	private function hasAdditionalCurrencyDefined() {
		/** @var \woocommerce_wpml $woocommerce_wpml */
		global $woocommerce_wpml;

		$available_currencies = $woocommerce_wpml->get_multi_currency()->get_currency_codes();

		return is_array( $available_currencies ) && count( $available_currencies ) > 1;
	}

	private function isShippingPageRequest() {
		return isset( $_GET['page'], $_GET['tab'] ) && 'wc-settings' === $_GET['page'] && 'shipping' === $_GET['tab']
		       || isset( $_GET['action'] ) && 'woocommerce_shipping_zone_methods_save_settings' === $_GET['action'];
	}

	private function isAjaxOnShippingPageRequest() {
		if ( ! isset( $_GET ) ) {
			return false;
		}
		$getData = wpml_collect( $_GET );
		$shippingActions = [ 'woocommerce_shipping_zone_add_method', 'woocommerce_shipping_zone_methods_save_changes' ];

		return is_ajax() && wpml_collect( $shippingActions )->containsStrict( $getData->get( 'action' ) );
	}
}
