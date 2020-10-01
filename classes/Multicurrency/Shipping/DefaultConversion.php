<?php

namespace WCML\Multicurrency\Shipping;

trait DefaultConversion {
	/**
	 * Try to get cost/amount from options array for default currency.
	 *
	 * @param float|int $cost         Cost to filter.
	 * @param array     $rateSettings Options array.
	 * @param string    $costName     Cost key with currency code appended.
	 * @param string    $currencyCode Currency code.
	 *
	 * @return float|int
	 */
	public function getValueFromDefaultCurrency( $cost, $rateSettings, $costName, $currencyCode ) {
		if ( preg_match( '/(.*)_' . $currencyCode . '$/', $costName, $matches ) ) {
			$defaultCostName = $matches[1];
			if ( ! empty( $rateSettings[ $defaultCostName ] ) ) {
				$cost = wcml_convert_price( $rateSettings[ $defaultCostName ], $currencyCode );
			}
		}
		return $cost;
	}
}
