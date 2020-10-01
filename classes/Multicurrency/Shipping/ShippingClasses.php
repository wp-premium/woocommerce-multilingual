<?php

namespace WCML\Multicurrency\Shipping;

class ShippingClasses {
	/**
	 * Adds shipping classes for currencies fields to shipping method wp-admin GUI.
	 *
	 * @param array               $field
	 * @param \WCML_Multi_Currency $wcmlMultiCurrency
	 *
	 * @return array
	 */
	public static function addFields( array $field, \WCML_Multi_Currency $wcmlMultiCurrency ) {
		$shippingClasses = WC()->shipping()->get_shipping_classes();
		if ( ! empty( $shippingClasses ) ) {
			foreach ( $wcmlMultiCurrency->get_currency_codes() as $currencyCode ) {
				if ( $wcmlMultiCurrency->get_default_currency() === $currencyCode ) {
					continue;
				}
				foreach ( $shippingClasses as $shippingClass ) {
					$classSourceLanguageCode = self::getSourceLanguageCode( $shippingClass );
					if ( $classSourceLanguageCode === null ) {
						$field = self::addShippingClassField( $field, $shippingClass, $currencyCode );
					} else {
						$field = self::askToSwitchLanguage( $field, $shippingClass, $classSourceLanguageCode );
					}
				}
				$field = self::addNoShippingClassField( $field, $currencyCode );
			}
		}
		return $field;
	}

	/**
	 * Returns source language of the shipping class which was created originally.
	 *
	 * @param WP_Term $shippingClass
	 *
	 * @return string|null
	 */
	protected static function getSourceLanguageCode( $shippingClass ) {
		$classLanguageDetails = apply_filters( 'wpml_element_language_details', false, [
			'element_id' => $shippingClass->term_id,
			'element_type' => $shippingClass->taxonomy,
		] );
		return isset( $classLanguageDetails->source_language_code ) ? $classLanguageDetails->source_language_code : null;
	}

	/**
	 * Adds field to the GUI which explains user should switch to the other language to provide the data.
	 *
	 * @param array   $field
	 * @param WP_Tern $shippingClass
	 * @param string  $classSourceLanguageCode
	 *
	 * @return array
	 */
	protected static function askToSwitchLanguage( $field, $shippingClass, $classSourceLanguageCode ) {
		$field[ 'wcml_ask_to_switch_language_' . $shippingClass->term_id ] = [
			'title' => '',
			'description' => sprintf( __( 'Your shipping class %s has been created in %s language. Please switch your language if you want to provide shipping costs in different currencies for this class.', 'woocommerce-multilingual' ),
								$shippingClass->name,
								$classSourceLanguageCode),
			'type' => 'title'
		];
		return $field;
	}

	protected static function addShippingClassField( $field, $shippingClass, $currencyCode ) {
		$field[ 'class_cost_' . $shippingClass->term_id . '_' . $currencyCode ] = [
			'title'             => sprintf( __( '"%s" shipping class cost in %s', 'woocommerce-multilingual' ), esc_html( $shippingClass->name ), esc_html( $currencyCode ) ),
			'type'              => 'text',
			'placeholder'       => __( 'N/A', 'woocommerce-multilingual' ),
			'class' => 'wcml-shipping-cost-currency'
		];
		return $field;
	}

	protected static function addNoShippingClassField( $field, $currencyCode ) {
		$field[ 'no_class_cost_' . $currencyCode ] = [
			'title'             => sprintf( __( 'No shipping class cost in %s', 'woocommerce-multilingual' ), esc_html( $currencyCode ) ),
			'type'              => 'text',
			'placeholder'       => __( 'N/A', 'woocommerce-multilingual' ),
			'default'           => '',
			'class' => 'wcml-shipping-cost-currency'
		];
		return $field;
	}
}