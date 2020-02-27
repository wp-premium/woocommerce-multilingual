<?php

namespace WCML\Container;

class Config {

	public static function getSharedInstances() {
		global $woocommerce_wpml;

		return [
			$woocommerce_wpml,
		];
	}
}
