<?php

class WCML_Multi_Currency_Install {

	/**
	 * The \WCML_Multi_Currency instance.
	 *
	 * @var \WCML_Multi_Currency
	 */
	private $multi_currency;
	/**
	 * The \woocommerce_wpml instance.
	 *
	 * @var \woocommerce_wpml
	 */
	private $woocommerce_wpml;

	/**
	 * WCML_Multi_Currency_Install constructor.
	 *
	 * @param \WCML_Multi_Currency $multi_currency   And instance of \WCML_Multi_Currency.
	 * @param \woocommerce_wpml    $woocommerce_wpml And instance of \woocommerce_wpml.
	 */
	public function __construct( WCML_Multi_Currency $multi_currency, woocommerce_wpml $woocommerce_wpml ) {

		$this->multi_currency   = $multi_currency;
		$this->woocommerce_wpml = $woocommerce_wpml;

		$wcml_settings = $this->woocommerce_wpml->get_settings();

		if ( empty( $wcml_settings['multi_currency']['set_up'] ) ) {
			$wcml_settings['multi_currency']['set_up'] = 1;
			$this->woocommerce_wpml->update_settings( $wcml_settings );

			$this->set_default_currencies_languages();
		}
	}

	/**
	 * It sets the default currency for each language.
	 *
	 * @param bool|string $old_value The value of the option before the update.
	 * @param bool|string $new_value The new value of the option.
	 */
	public function set_default_currencies_languages( $old_value = false, $new_value = false ) {
		global $sitepress;

		$settings         = $this->woocommerce_wpml->get_settings();
		$active_languages = $sitepress->get_active_languages();
		$wc_currency      = $new_value ? $new_value : wcml_get_woocommerce_currency_option();

		if ( $old_value !== $new_value ) {
			$settings = WCML_Multi_Currency_Configuration::currency_options_update_default_currency( $settings, $old_value, $new_value );
		}

		foreach ( $this->multi_currency->get_currency_codes() as $code ) {
			if ( $code === $old_value ) {
				continue;
			}
			foreach ( $active_languages as $language ) {
				if ( ! isset( $settings['currency_options'][ $code ]['languages'][ $language['code'] ] ) ) {
					$settings['currency_options'][ $code ]['languages'][ $language['code'] ] = 1;
				}
			}
		}

		foreach ( $active_languages as $language ) {
			if ( ! isset( $settings['default_currencies'][ $language['code'] ] ) ) {
				$settings['default_currencies'][ $language['code'] ] = false;
			}

			if ( ! isset( $settings['currency_options'][ $wc_currency ]['languages'][ $language['code'] ] ) ) {
				$settings['currency_options'][ $wc_currency ]['languages'][ $language['code'] ] = 1;
			}
		}

		$this->woocommerce_wpml->update_settings( $settings );

	}

}
