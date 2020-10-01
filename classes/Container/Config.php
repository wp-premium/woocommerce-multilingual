<?php

namespace WCML\Container;

class Config {

	public static function getSharedInstances() {
		global $woocommerce_wpml;

		return [
			$woocommerce_wpml,
		];
	}

	public static function getSharedClasses() {
		return [
			\WCML_Multi_Currency::class,
			\WCML_Currencies_Payment_Gateways::class,
		];
	}
}
