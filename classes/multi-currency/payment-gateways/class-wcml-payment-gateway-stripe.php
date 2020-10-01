<?php

/**
 * Class WCML_Payment_Gateway_Stripe
 */
class WCML_Payment_Gateway_Stripe extends WCML_Payment_Gateway {

	const ID = 'stripe';

	public function get_output_model() {
		return [
			'id'          => $this->get_id(),
			'title'       => $this->get_title(),
			'isSupported' => true,
			'settings'    => $this->get_currencies_details(),
			'tooltip'     => '',
			'strings'     => [
				'labelCurrency'           => __( 'Currency', 'woocommerce-multilingual' ),
				'labelLivePublishableKey' => __( 'Live Publishable Key', 'woocommerce-multilingual' ),
				'labelLiveSecretKey'      => __( 'Live Secret Key', 'woocommerce-multilingual' ),
			],
		];
	}

	public function add_hooks() {
		add_filter( 'woocommerce_stripe_request_body', [ $this, 'filter_request_body' ] );
	}

	public function filter_request_body( $request ) {

		$client_currency = $this->woocommerce_wpml->multi_currency->get_client_currency();
		$gateway_setting = $this->get_setting( strtoupper( $request['currency'] ) );

		if ( $gateway_setting ) {

			if ( $client_currency !== $gateway_setting['currency'] ) {
				$request['currency'] = strtolower( $gateway_setting['currency'] );
				$request['amount']   = WC_Stripe_Helper::get_stripe_amount( $this->woocommerce_wpml->cart->get_cart_total_in_currency( $gateway_setting['currency'] ), $gateway_setting['currency'] );
			}
		}

		return $request;
	}

	/**
	 * @return array
	 */
	public function get_currencies_details() {
		$currencies_details = [];
		$default_currency   = wcml_get_woocommerce_currency_option();
		$active_currencies  = get_woocommerce_currencies();

		foreach ( $active_currencies as $code => $currency ) {

			if ( $default_currency === $code ) {
				$currencies_details[ $code ]['publishable_key'] = $this->get_gateway()->settings['publishable_key'];
				$currencies_details[ $code ]['secret_key']      = $this->get_gateway()->settings['secret_key'];
			} else {
				$currency_gateway_setting                       = $this->get_setting( $code );
				$currencies_details[ $code ]['publishable_key'] = $currency_gateway_setting ? $currency_gateway_setting['publishable_key'] : '';
				$currencies_details[ $code ]['secret_key']      = $currency_gateway_setting ? $currency_gateway_setting['secret_key'] : '';
			}
		}

		return $currencies_details;

	}

	/**
	 * Filter Stripe settings before WC initialized them
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public static function filter_stripe_settings( $settings ) {
		if ( is_admin() ) {
			return $settings;
		}

		global $woocommerce_wpml;

		$client_currency  = $woocommerce_wpml->multi_currency->get_client_currency();
		$gateway_settings = get_option( self::OPTION_KEY . self::ID, [] );

		if ( $gateway_settings && isset( $gateway_settings[ $client_currency ] ) ) {
			$gateway_setting = $gateway_settings[ $client_currency ];
			if ( $gateway_setting['publishable_key'] && $gateway_setting['secret_key'] ) {
				if ( 'yes' === $settings['testmode'] ) {
					$settings['test_publishable_key'] = $gateway_setting['publishable_key'];
					$settings['test_secret_key']      = $gateway_setting['secret_key'];
				} else {
					$settings['publishable_key'] = $gateway_setting['publishable_key'];
					$settings['secret_key']      = $gateway_setting['secret_key'];
				}
			}
		}

		return $settings;
	}

}
