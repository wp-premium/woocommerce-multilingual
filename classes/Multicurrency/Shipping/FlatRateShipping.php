<?php

namespace WCML\Multicurrency\Shipping;

class FlatRateShipping implements ShippingClassesMode {
	use ShippingModeBase;
	use VariableCost;

	public function getMethodId() {
		return 'flat_rate';
	}
}