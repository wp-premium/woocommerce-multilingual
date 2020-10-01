<?php

if ( ! function_exists( 'wcml_is_multi_currency_on' ) ) {
	/**
	 * It returns true if multi currency is enabled.
	 *
	 * @return bool
	 *
	 * @since 3.8.3
	 */
	function wcml_is_multi_currency_on() {
		global $woocommerce_wpml;

		if ( is_null( $woocommerce_wpml ) ) {
			return false;
		}

		return WCML_MULTI_CURRENCIES_INDEPENDENT === (int) $woocommerce_wpml->settings['enable_multi_currency'];
	}
}

if ( ! function_exists( 'wcml_price_custom_fields' ) ) {
	/**
	 * It returns a filtered array of price custom fields.
	 *
	 * @param int|\WP_Post $object_id The post, product ID or object extending "WP_Post".
	 *
	 * @return array
	 */
	function wcml_price_custom_fields( $object_id ) {
		$default_keys = [
			'_max_variation_price',
			'_max_variation_regular_price',
			'_max_variation_sale_price',
			'_min_variation_price',
			'_min_variation_regular_price',
			'_min_variation_sale_price',
			'_price',
			'_regular_price',
			'_sale_price',
		];

		/**
		 * See the following filter.
		 *
		 * @deprecated
		 * @see apply_filters( 'wcml_price_custom_fields', $filtered_keys, $object_id );
		 */
		$filtered_keys = apply_filters( 'wcml_price_custom_fields_filtered', $default_keys, $object_id );

		/**
		 * It filters the array of price custom fields.
		 *
		 * If the returned filter is not an array, it will be replaced with the original value.
		 *
		 * @param array       $default_keys Default unfiltered values.
		 * @param int|WP_Post $object_id    The post, product ID or object extending "WP_Post".
		 */
		$filtered_keys = apply_filters( 'wcml_price_custom_fields', $filtered_keys, $object_id );

		if ( ! is_array( $filtered_keys ) ) {
			$filtered_keys = $default_keys;
		}

		return $filtered_keys;
	}
}


if ( ! function_exists( 'wcml_get_woocommerce_currency_option' ) ) {
	/**
	 * It returns WooCommerce currency value from 'woocommerce_currency' option.
	 *
	 * @return mixed
	 *
	 * @since 4.6.6
	 */
	function wcml_get_woocommerce_currency_option() {
		return get_option( 'woocommerce_currency' );
	}
}

if ( ! function_exists( 'wcml_product_data_store_cpt' ) ) {
	/**
	 * It returns a single instance of the class.
	 *
	 * @return \WCML_Product_Data_Store_CPT
	 *
	 * @since 4.6.8
	 */
	function wcml_product_data_store_cpt() {
		return new WCML_Product_Data_Store_CPT();
	}
}

if ( ! function_exists( 'wcml_convert_price' ) ) {

	/**
	 * @since 4.9.0
	 *
	 * @param float|int   $price
	 * @param bool|string $currency_code
	 *
	 * @return float|int
	 */
	function wcml_convert_price( $price, $currency_code = false ) {
		/** @var woocommerce_wpml $woocommerce_wpml */
		global $woocommerce_wpml;

		return $woocommerce_wpml->multi_currency->prices->raw_price_filter( $price, $currency_code );
	}
}

if ( ! function_exists( 'wcml_safe_redirect' ) ) {

	/**
	 * @since 4.10.0
	 *
	 * @param string $location
	 * @param int    $status
	 *
	 * @return bool
	 */
	function wcml_safe_redirect( $location, $status = 302 ) {
		return wp_safe_redirect( $location, $status, 'WCML' ) && exit;
	};
}
