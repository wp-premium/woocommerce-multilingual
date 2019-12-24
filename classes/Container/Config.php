<?php

namespace WCML\Container;

class Config {

	static public function getSharedInstances() {
		global $woocommerce_wpml;

		return [
			$woocommerce_wpml,
		];
	}
}
