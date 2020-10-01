<?php

namespace WCML\Multicurrency\Shipping;

trait VariableCost {
	use DefaultConversion;

	/**
	 * @see \WCML\Multicurrency\Shipping\ShippingMode::getFieldTitle
	 *
	 * @param string $currencyCode
	 *
	 * return string
	 */
	public function getFieldTitle( $currencyCode ) {
		return sprintf( esc_html_x( 'Cost in %s',
			'The label for the field with shipping cost in additional currency. The currency symbol will be added in place of %s specifier.',
			'woocommerce-multilingual' ), $currencyCode );
	}

	private $wpOption = null;

	/**
	 * @see \WCML\Multicurrency\Shipping\ShippingMode::getFieldDescription
	 *
	 * @param string $currencyCode
	 *
	 * @return string
	 */
	public function getFieldDescription( $currencyCode ) {
		return sprintf( esc_html_x( 'The shipping cost if customer choose %s as a purchase currency.',
			'The description for the field with shipping cost in additional currency. The currency symbol will be added in place of %s specifier.',
			'woocommerce-multilingual' ), $currencyCode );
	}

	/**
	 * Returns cost key for given currency.
	 *
	 * @param string $currencyCode Currency code.
	 *
	 * @return string
	 */
	private function getCostKey( $currencyCode ) {
		return sprintf( 'cost_%s', $currencyCode );
	}

	private function getShippingClassCostKey( $shippingClassKey, $currency ) {
		return $this->replaceShippingClassId( $shippingClassKey ) . '_' . $currency;
	}

	private function getNoShippingClassCostKey( $currency ) {
		return 'no_class_cost_' . $currency;
	}

	/**
	 * @see \WCML\Multicurrency\Shipping\ShippingMode::getSettingsFormKey
	 *
	 * @param string $currencyCode
	 *
	 * @return string
	 */
	public function getSettingsFormKey( $currencyCode ) {
		return $this->getCostKey( $currencyCode );
	}

	/**
	 * @see \WCML\Multicurrency\Shipping\ShippingMode::getMinimalOrderAmountValue
	 *
	 * @param integer|float|string $amount   The value as saved for original language.
	 * @param array                $shipping The shipping metadata.
	 * @param string               $currency Currency code.
	 *
	 * @return mixed
	 */
	public function getMinimalOrderAmountValue( $amount, $shipping, $currency ) {
		return $amount;
	}

	/**
	 * @param array|object $rate
	 * @param string $currency
	 *
	 * @return int|mixed|string
	 * @see \WCML\Multicurrency\Shipping\ShippingMode::getShippingCostValue
	 *
	 */
	public function getShippingCostValue( $rate, $currency ) {
		$costName = $this->getCostKey( $currency );
		return $this->getCostValueForName( $rate, $currency, $costName, 'cost' );
	}

	/**
	 * @see \WCML\Multicurrency\Shipping\ShippingClassesMode::getShippingClassCostValue
	 *
	 * @param array|object $rate
	 * @param string       $currency
	 * @param string       $shippingClassKey
	 *
	 * @return int Shipping class cost for given currency.
	 */
	public function getShippingClassCostValue( $rate, $currency, $shippingClassKey ) {
		$costName = $this->getShippingClassCostKey( $shippingClassKey, $currency );
		return $this->getCostValueForName( $rate, $currency, $costName, $shippingClassKey );
	}

	/**
	 * @see \WCML\Multicurrency\Shipping\ShippingClassesMode::getNoShippingClassCostValue
	 *
	 * @param array|object $rate
	 * @param string       $currency
	 *
	 * @return int "No shipping class" cost for given currency.
	 */
	public function getNoShippingClassCostValue( $rate, $currency ) {
		$costName = $this->getNoShippingClassCostKey( $currency );
		return $this->getCostValueForName( $rate, $currency, $costName, 'no_class_cost' );
	}

	private function getCostValueForName( $rate, $currency, $costName, $rateField ) {
		if ( ! isset( $rate->$rateField ) ) {
			$rate->$rateField = 0;
		}
		if ( isset( $rate->instance_id ) ) {
			if ( $this->isManualPricingEnabled( $rate ) ) {
				$rateSettings = $this->getWpOption( $this->getMethodId(), $rate->instance_id );
				if ( ! empty( $rateSettings[ $costName ] ) ) {
					$rate->$rateField = $rateSettings[ $costName ];
				} else {
					$rate->$rateField = $this->getValueFromDefaultCurrency( $rate->$rateField, $rateSettings, $costName, $currency );
				}
			}
		}
		return $rate->$rateField;
	}

	/**
	 * @see \WCML\Multicurrency\Shipping\ShippingMode::isManualPricingEnabled
	 *
	 * @param \WC_Shipping_Rate $instance
	 *
	 * @return mixed
	 */
	public function isManualPricingEnabled( $instance ) {
		return self::isEnabled( $this->getWpOption( $this->getMethodId(), $instance->instance_id ) );
	}

	/**
	 * Returns shipping data from wp_options table.
	 *
	 * @param string $methodId
	 * @param int    $instanceId
	 *
	 * @return bool|mixed|void|null
	 */
	private function getWpOption( $methodId, $instanceId ) {
		if ( null === $this->wpOption ) {
			$optionName = sprintf( 'woocommerce_%s_%d_settings', $methodId, $instanceId );
			$this->wpOption = get_option( $optionName );
		}
		return $this->wpOption;
	}

	/**
	 * Extracts numeric shipping class ID from shipping class key.
	 *
	 * @param string $key
	 *
	 * @return false|string Class ID or false if not found.
	 */
	private function getShippingClassTermId( $key ) {
		if ( preg_match( '/^class_cost_(\d*)(_[A-Z]*)*$/', $key, $matches ) && isset( $matches[1] ) ) {
			return $matches[1];
		}
		return false;
	}
	/**
	 * @param string $shippingClassKey
	 *
	 * @return string
	 */
	private function replaceShippingClassId( $shippingClassKey ) {
		$termId         = $this->getShippingClassTermId( $shippingClassKey );
		if ( $termId ) {
			$termTrid = apply_filters( 'wpml_element_trid', null, $termId, 'tax_product_shipping_class' );
			$termTranslations = apply_filters( 'wpml_get_element_translations', null, $termTrid, 'tax_product_shipping_class' );
			if ( is_array( $termTranslations ) ) {
				foreach ( $termTranslations as $languageCode => $translation ) {
					if ( $translation->source_language_code === null ) {
						$originalTermId = $translation->element_id;
						break;
					}
				}
				$shippingClassKey = str_replace( $termId, $originalTermId, $shippingClassKey );
			}
		}
		return $shippingClassKey;
	}


	/**
	 * wrapper for getShippingClassTermId to avail testing private method.
	 *
	 * @param $key
	 *
	 * @return bool|false|string
	 */
	public function _testGetShippingClassTermId( $key ) {
		if ( ! isset( $_SERVER['SCRIPT_NAME'] ) || stristr( $_SERVER['SCRIPT_NAME'], 'phpunit' ) === false ) {
			die( "don't run this method directly outside phpunit env" );
		}
		return $this->getShippingClassTermId( $key );
	}
}
