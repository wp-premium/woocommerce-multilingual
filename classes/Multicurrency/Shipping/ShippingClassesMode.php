<?php

namespace WCML\Multicurrency\Shipping;

interface ShippingClassesMode extends ShippingMode {
	/**
	 * @param array|object               $rate
	 * @param string                     $currency
	 * @param string                     $shippingClassKey
	 *
	 * @return int|mixed|string Shipping class cost for given currency.
	 */
	public function getShippingClassCostValue( $rate, $currency, $shippingClassKey );

	/**
	 * @param array|object               $rate
	 * @param string                     $currency
	 *
	 * @return int|mixed|string "No shipping class" cost for given currency.
	 */
	public function getNoShippingClassCostValue( $rate, $currency );
}
