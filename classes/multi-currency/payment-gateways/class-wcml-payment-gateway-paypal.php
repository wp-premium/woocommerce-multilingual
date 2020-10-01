<?php

/**
 * Class WCML_Payment_Gateway_PayPal
 */
class WCML_Payment_Gateway_PayPal extends WCML_Payment_Gateway {

	const ID                   = 'paypal';
	const SUPPORTED_CURRENCIES = [
		'AUD',
		'BRL',
		'CAD',
		'MXN',
		'NZD',
		'HKD',
		'SGD',
		'USD',
		'EUR',
		'JPY',
		'TRY',
		'NOK',
		'CZK',
		'DKK',
		'HUF',
		'ILS',
		'MYR',
		'PHP',
		'PLN',
		'SEK',
		'CHF',
		'TWD',
		'THB',
		'GBP',
		'RMB',
		'RUB',
		'INR',
	];

	public function get_output_model() {
		return [
			'id'          => $this->get_id(),
			'title'       => $this->get_title(),
			'isSupported' => true,
			'settings'    => $this->get_currencies_details(),
			'tooltip'     => '',
			'strings'     => [
				'labelCurrency'       => __( 'Currency', 'woocommerce-multilingual' ),
				'labelPayPalEmail'    => __( 'PayPal Email', 'woocommerce-multilingual' ),
				'tooltipNotSupported' => __( 'This gateway does not support %s. To show this gateway please select another currency.', 'woocommerce-multilingual' ),
			],
		];
	}

	/**
	 * @param $currency
	 *
	 * @return bool
	 */
	public function is_valid_for_use( $currency ) {

		$filter_removed = remove_filter( 'woocommerce_paypal_supported_currencies', [ 'WCML_Payment_Gateway_PayPal', 'filter_supported_currencies' ] );

		$is_valid = in_array(
			$currency,
			apply_filters(
				'woocommerce_paypal_supported_currencies',
				self::SUPPORTED_CURRENCIES
			),
			true
		);

		if ( $filter_removed ) {
			add_filter( 'woocommerce_paypal_supported_currencies', [ 'WCML_Payment_Gateway_PayPal', 'filter_supported_currencies' ] );
		}

		return $is_valid;
	}

	/**
	 * @param array $active_currencies
	 *
	 * @return array
	 */
	public function get_currencies_details() {

		$currencies_details     = [];
		$default_currency       = wcml_get_woocommerce_currency_option();
		$woocommerce_currencies = get_woocommerce_currencies();

		foreach ( $woocommerce_currencies as $code => $currency ) {

			if ( $default_currency === $code ) {
				$currencies_details[ $code ]['value']    = $this->get_gateway()->settings['email'];
				$currencies_details[ $code ]['currency'] = $code;
				$currencies_details[ $code ]['isValid'] = $this->is_valid_for_use( $default_currency );
			} else {
				$currency_gateway_setting                = $this->get_setting( $code );
				$currencies_details[ $code ]['value']    = $currency_gateway_setting ? $currency_gateway_setting['value'] : '';
				$currencies_details[ $code ]['currency'] = $currency_gateway_setting ? $currency_gateway_setting['currency'] : $code;
				$currencies_details[ $code ]['isValid'] = $this->is_valid_for_use( $code );
			}
		}

		return $currencies_details;

	}

	public function add_hooks() {
		add_filter( 'woocommerce_paypal_args', [ $this, 'filter_paypal_args' ], 10, 2 );
	}

	/**
	 * @param array    $args
	 * @param WC_Order $order
	 *
	 * @return array
	 */
	public function filter_paypal_args( $args, $order ) {

		$order_data      = $order->get_data();
		$client_currency = $order_data['currency'];
		$gateway_setting = $this->get_setting( $client_currency );

		if ( $gateway_setting ) {

			if ( $gateway_setting['value'] ) {
				$args['business'] = $gateway_setting['value'];
			}

			if ( $client_currency !== $gateway_setting['currency'] ) {
				$args['currency_code'] = $gateway_setting['currency'];
				$cart_items            = WC()->cart->get_cart_contents();
				$item_id               = 1;

				foreach ( $cart_items as $item ) {
					$item_product_id              = $item['variation_id'] ?: $item['product_id'];
					$args[ 'amount_' . $item_id ] = $this->woocommerce_wpml->multi_currency->prices->get_product_price_in_currency( $item_product_id, $gateway_setting['currency'] );
					$item_id ++;
				}

				$args['shipping_1'] = $this->woocommerce_wpml->cart->get_cart_shipping_in_currency( $gateway_setting['currency'] );
			}
		}

		return $args;
	}

	/**
	 * Filter PayPal supported currencies before WC initialized it
	 *
	 * @param array $supported_currencies
	 *
	 * @return array
	 */
	public static function filter_supported_currencies( $supported_currencies ) {
		global $woocommerce_wpml;

		$client_currency = $woocommerce_wpml->multi_currency->get_client_currency();

		if ( ! in_array( $client_currency, self::SUPPORTED_CURRENCIES, true ) ) {
			$gateway_settings = get_option( self::OPTION_KEY . self::ID, [] );

			if ( $gateway_settings && isset( $gateway_settings[ $client_currency ] ) ) {

				if ( in_array( $gateway_settings[ $client_currency ]['currency'], self::SUPPORTED_CURRENCIES, true ) ) {
					$supported_currencies[] = $client_currency;
				}
			}
		}

		return $supported_currencies;
	}

}
