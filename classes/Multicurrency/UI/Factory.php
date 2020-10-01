<?php

namespace WCML\Multicurrency\UI;

use function WPML\Container\make;

class Factory implements \IWPML_Backend_Action_Loader, \IWPML_Deferred_Action_Loader {

	public function get_load_action() {
		return 'init';
	}

	/**
	 * @return \IWPML_Action|null
	 */
	public function create() {
		/** @var \woocommerce_wpml $woocommerce_wpml */
		global $woocommerce_wpml;

		if ( self::isMultiCurrencySettings() ) {
			return make(
				Hooks::class,
				[
					':wcmlSettings' => $woocommerce_wpml->settings,
				]
			);
		}

		return null;
	}

	/**
	 * @return bool
	 */
	public static function isMultiCurrencySettings() {
		return isset( $_GET['page'], $_GET['tab'] )
			&& 'wpml-wcml' === $_GET['page']
			&& 'multi-currency' === $_GET['tab'];
	}
}