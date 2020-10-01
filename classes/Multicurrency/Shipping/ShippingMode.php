<?php

namespace WCML\Multicurrency\Shipping;

interface ShippingMode {
	/**
	 * Returns shipping method id (shipping option key).
	 *
	 * @return string
	 */
	public function getMethodId();

	/**
	 * Returns field title.
	 *
	 * This value is visible on shipping method configuration screen, on the left.
	 *
	 * @param string $currencyCode
	 *
	 * @return string
	 */
	public function getFieldTitle( $currencyCode );

	/**
	 * Returns field description.
	 *
	 * This value is visible on shipping method configuration screen, when mouse over
	 * the question mark icon, next to field title.
	 *
	 * @param string $currencyCode
	 *
	 * @return string
	 */
	public function getFieldDescription( $currencyCode );

	/**
	 * Return the key which will be used in shipping method configuration form.
	 *
	 * @param string $currencyCode
	 *
	 * @return string|null
	 */
	public function getSettingsFormKey( $currencyCode );

	/**
	 * If shipping mode has minimal order amount, recalculate and return its value.
	 *
	 * @param integer|float|string $amount   The value as saved for original language.
	 * @param array                $shipping The shipping metadata.
	 * @param string               $currency Currency code.
	 *
	 * @return integer|float|string
	 */
	public function getMinimalOrderAmountValue( $amount, $shipping, $currency );

	/**
	 * If shipping mode has custom cost, recalculate and return its value.
	 *
	 * @param array|object $rate Shipping rate metadata.
	 * @param string $currency Currency code.
	 *
	 * @return integer|float|string
	 */
	public function getShippingCostValue( $rate, $currency );

	/**
	 * Checks if the instance of the shipping method has enabled manual pricing.
	 *
	 * @param array|object $instance Currently processed instance of the shipping method.
	 *
	 * @return bool
	 */
	public function isManualPricingEnabled( $instance );

}