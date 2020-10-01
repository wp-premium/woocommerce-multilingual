<?php

namespace WCML\Multicurrency\Shipping;

class ShippingModeProvider {

	private static function getClasses() {
		$collection =  wpml_collect( [
			'flat_rate'     => 'WCML\Multicurrency\Shipping\FlatRateShipping',
			'free_shipping' => 'WCML\Multicurrency\Shipping\FreeShipping',
			'local_pickup'  => 'WCML\Multicurrency\Shipping\LocalPickup',
		] );
		return $collection;
	}

	public static function getAll() {
		return self::getClasses()->map( function( $className ) {
			return self::make( $className );
		} );
	}

	/**
	 * @param string $shippingMode
	 * @return ShippingMode
	 */
	public static function get( $shippingMode ) {
        return self::make(
        	self::getClasses()->get( $shippingMode, 'WCML\Multicurrency\Shipping\UnsupportedShipping' )
        );
    }

	/**
	 * @param $className
	 * @return ShippingMode
	 */
    private static function make( $className ) {
	    return \WPML\Container\make( $className );
    }
}