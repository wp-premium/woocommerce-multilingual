<?php

namespace WCML\Multicurrency\Shipping;

class FreeShipping implements ShippingMode {
	use ShippingModeBase;
	use DefaultConversion;

	public function getFieldTitle( $currencyCode ) {
		if ( ! is_string( $currencyCode ) ) {
			$currencyCode = '';
		}
		return sprintf( esc_html_x( 'Minimal order amount in %s',
			'The label for the field with minimal order amount in additional currency. The currency symbol will be added in place of %s specifier.',
			'woocommerce-multilingual' ), $currencyCode );
	}

	public function getFieldDescription( $currencyCode ) {
		if ( ! is_string( $currencyCode ) ) {
			$currencyCode = '';
		}
		return sprintf( esc_html_x( 'The minimal order amount if customer choose %s as a purchase currency.',
			'The description for the field with minimal order amount in additional currency. The currency symbol will be added in place of %s specifier.',
			'woocommerce-multilingual' ), $currencyCode );
	}

	public function getMethodId() {
		return 'free_shipping';
	}

	/**
	 * Returns minimal amount key for given currency.
	 *
	 * @param string $currencyCode Currency code.
	 *
	 * @return string
	 */
	private function getMinimalOrderAmountKey( $currencyCode ) {
		return sprintf( 'min_amount_%s', $currencyCode );
	}

	public function getSettingsFormKey( $currencyCode ) {
		return $this->getMinimalOrderAmountKey( $currencyCode );
	}

	public function getMinimalOrderAmountValue( $amount, $shipping, $currency ) {
		if ( $this->isManualPricingEnabled( $shipping ) ) {
			$key = $this->getMinimalOrderAmountKey( $currency );
			if ( ! empty( $shipping[ $key ] ) ) {
				$amount = $shipping[ $key ];
			} else {
				$amount = $this->getValueFromDefaultCurrency( $amount, $shipping, $key, $currency );
			}
		}
		return $amount;
	}

	/**
	 * @see \WCML\Multicurrency\Shipping\ShippingMode::getShippingCostValue
	 *
	 * @param array|object $rate
	 * @param string       $currency
	 *
	 * @return int|mixed|string
	 */
	public function getShippingCostValue( $rate, $currency ) {
		if ( ! isset( $rate->cost ) ) {
			$rate->cost = 0;
		}
		return $rate->cost;
	}

	public function isManualPricingEnabled( $instance ) {
		return is_array( $instance ) && isset( $instance['wcml_shipping_costs'] ) && 'manual' === $instance['wcml_shipping_costs'];
	}
}