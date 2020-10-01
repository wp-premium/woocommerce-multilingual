<?php

/**
 * Class WCML_Payment_Gateway_Bacs
 */
class WCML_Payment_Gateway_Bacs extends WCML_Payment_Gateway {

	public function get_output_model() {
		return [
			'id'          => $this->get_id(),
			'title'       => $this->get_title(),
			'isSupported' => true,
			'settings'    => $this->get_settings(),
			'tooltip'     => __( 'Set the currency in which your customer will see the final price when they checkout. Choose which accounts they will see in their payment message.', 'woocommerce-multilingual' ),
			'strings'     => [
				'labelCurrency'    => __( 'Currency', 'woocommerce-multilingual' ),
				'labelBankAccount' => __( 'Bank Account', 'woocommerce-multilingual' ),
				'optionAll'        => __( 'All Accounts', 'woocommerce-multilingual' ),
				'optionAllIn'      => __( 'All in selected currency', 'woocommerce-multilingual' ),
			],
		];
	}

	public function add_hooks() {
		add_filter( 'woocommerce_bacs_accounts', [ $this, 'filter_bacs_accounts' ] );
	}

	public function filter_bacs_accounts( $accounts ) {

		$client_currency = $this->woocommerce_wpml->multi_currency->get_client_currency();
		$gateway_setting = $this->get_setting( $client_currency );

		$allowed_accounts = [];

		if ( $gateway_setting && 'all' !== $gateway_setting['value'] ) {

			if ( 'all_in' === $gateway_setting['value'] ) {
				$bacs_accounts_currencies = get_option( WCML_WC_Gateways::WCML_BACS_ACCOUNTS_CURRENCIES_OPTION, [] );
				foreach ( $bacs_accounts_currencies as $account_key => $currency ) {
					if ( $gateway_setting['currency'] === $currency ) {
						$allowed_accounts[] = $accounts[ $account_key ];
					}
				}
			} else {
				$allowed_accounts[] = $accounts[ $gateway_setting['value'] ];
			}
		}

		return $allowed_accounts ? $allowed_accounts : $accounts;
	}

}
