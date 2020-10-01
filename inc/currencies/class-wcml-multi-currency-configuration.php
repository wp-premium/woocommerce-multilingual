<?php

use WCML\Multicurrency\UI\Hooks;
use WCML\MultiCurrency\Geolocation;
use WPML\FP\Obj;

class WCML_Multi_Currency_Configuration {


	/**
	 * @var WCML_Multi_Currency
	 */
	private static $multi_currency;
	/**
	 * @var woocommerce_wpml
	 */
	private static $woocommerce_wpml;

	public static function set_up( WCML_Multi_Currency $multi_currency, woocommerce_wpml $woocommerce_wpml ) {

		self::$multi_currency   = $multi_currency;
		self::$woocommerce_wpml = $woocommerce_wpml;

		if ( isset( $_POST['action'] ) && $_POST['action'] === 'save-mc-options' ) {
			self::save_configuration();
		}

		self::set_prices_config();

		self::add_hooks();
	}

	public static function add_hooks(){
		if ( is_ajax() ) {

			add_action( 'wp_ajax_legacy_update_custom_rates', [ __CLASS__, 'legacy_update_custom_rates' ] );
			add_action( 'wp_ajax_legacy_remove_custom_rates', [ __CLASS__, 'legacy_remove_custom_rates' ] );

			add_action( 'wp_ajax_wcml_save_currency', [ __CLASS__, 'save_currency' ] );
			add_action( 'wp_ajax_wcml_delete_currency', [ __CLASS__, 'delete_currency' ] );
			add_action( 'wp_ajax_wcml_update_currency_lang', [ __CLASS__, 'update_currency_lang' ] );
			add_action( 'wp_ajax_wcml_update_default_currency', [ __CLASS__, 'update_default_currency_ajax' ] );
			add_action( 'wp_ajax_wcml_set_currency_mode', [ __CLASS__, 'set_currency_mode' ] );
			add_action( 'wp_ajax_wcml_set_max_mind_key', [ __CLASS__, 'set_max_mind_key' ] );
		}
	}

	public static function save_configuration() {
		// @todo Cover by tests, required for wcml-3037.
		if ( check_admin_referer( 'wcml_mc_options', 'wcml_nonce' ) ) {

			$wcml_settings = self::$woocommerce_wpml->settings;

			$wcml_settings['enable_multi_currency'] = isset( $_POST['multi_currency'] ) ? intval( $_POST['multi_currency'] ) : 0;
			$wcml_settings['display_custom_prices'] = isset( $_POST['display_custom_prices'] ) ? intval( $_POST['display_custom_prices'] ) : 0;
			$wcml_settings['currency_mode'] = filter_input( INPUT_POST, 'currency_mode', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

			// update default currency settings
			if ( $wcml_settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT ) {

				$options = [
					'woocommerce_currency_pos'       => 'position',
					'woocommerce_price_thousand_sep' => 'thousand_sep',
					'woocommerce_price_decimal_sep'  => 'decimal_sep',
					'woocommerce_price_num_decimals' => 'num_decimals',
				];

				$woocommerce_currency = wcml_get_woocommerce_currency_option();

				foreach ( $options as $wc_key => $key ) {
					$wcml_settings['currency_options'][ $woocommerce_currency ][ $key ] = get_option( $wc_key, true );
				}

				if ( ! isset( $wcml_settings['currency_options'][ $woocommerce_currency ]['location_mode'] ) ) {
					$wcml_settings['currency_options'][ $woocommerce_currency ]['location_mode'] = 'all';
				}
			}

			$wcml_settings['currency_switcher_product_visibility'] = isset( $_POST['currency_switcher_product_visibility'] ) ? intval( $_POST['currency_switcher_product_visibility'] ) : 0;
			$wcml_settings['currency_switcher_additional_css']     = isset( $_POST['currency_switcher_additional_css'] ) ? sanitize_text_field( $_POST['currency_switcher_additional_css'] ) : '';

			self::$woocommerce_wpml->update_settings( $wcml_settings );

			do_action( 'wcml_saved_mc_options', $_POST );

			$message = [
				'id'            => 'wcml-settings-saved',
				'text'          => __( 'Your settings have been saved.', 'woocommerce-multilingual' ),
				'group'         => 'wcml-multi-currency',
				'admin_notice'  => true,
				'limit_to_page' => true,
				'classes'       => [ 'updated', 'notice', 'notice-success' ],
				'show_once'     => true,
			];
			ICL_AdminNotifier::add_message( $message );

			$wpml_admin_notices = wpml_get_admin_notices();
			$wpml_admin_notices->remove_notice( 'wcml-save-multi-currency-options', 'wcml-fixerio-api-key-required' );
		}

	}

	public static function add_currency( $currency_code ) {
		global $sitepress;

		$settings = self::$woocommerce_wpml->get_settings();

		$active_languages    = $sitepress->get_active_languages();
		$return['languages'] = '';
		foreach ( $active_languages as $language ) {
			if ( ! isset( $settings['currency_options'][ $currency_code ]['languages'][ $language['code'] ] ) ) {
				$settings['currency_options'][ $currency_code ]['languages'][ $language['code'] ] = 1;
			}
		}
		$settings['currency_options'][ $currency_code ]['rate']    = (float) filter_input( INPUT_POST, 'currency_value', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
		$settings['currency_options'][ $currency_code ]['updated'] = date( 'Y-m-d H:i:s' );

		$wc_currency = wcml_get_woocommerce_currency_option();
		if ( ! isset( $settings['currencies_order'] ) ) {
			$settings['currencies_order'][] = $wc_currency;
		}

		$settings['currencies_order'][] = $currency_code;

		self::$woocommerce_wpml->update_settings( $settings );
		self::$multi_currency->init_currencies();

	}

	public static function save_currency() {
		self::verify_nonce();
		$data = self::get_data();

		$wc_currency   = wcml_get_woocommerce_currency_option();

		$options = $data['currency_options'];

		$currency_code = $options['code'];

		if ( isset( $options['gatewaysSettings'] ) ) {

			$payment_gateways = self::$multi_currency->currencies_payment_gateways->get_gateways();

			foreach ( $options['gatewaysSettings'] as $code => $gateways_settings ) {
				if ( isset( $payment_gateways[ $code ] ) ) {
					$payment_gateways[ $code ]->save_setting( $currency_code, $gateways_settings );
				}
			}

			self::$multi_currency->currencies_payment_gateways->set_enabled( $currency_code, $options['gatewaysEnabled'] );
		}

		if ( isset( $options['countries'] ) ) {
			$options['countries'] = wc_string_to_array( $options['countries'], ',' );
		}

		if ( $wc_currency !== $currency_code ) {
			$options['thousand_sep'] = wc_format_option_price_separators( null, null, $options['thousand_sep'] );
			$options['decimal_sep']  = wc_format_option_price_separators( null, null, $options['decimal_sep'] );

			if ( ! isset( self::$multi_currency->currencies[ $currency_code ] ) ) {
				self::add_currency( $currency_code );
			}

			$changed      = false;
			$rate_changed = false;
			foreach ( self::$multi_currency->currencies[ $currency_code ] as $key => $value ) {

				if ( isset( $options[ $key ] ) && $options[ $key ] != $value ) {
					if ( $key === 'rate' ) {
						$previous_rate = self::$multi_currency->currencies[ $currency_code ][ $key ];
						$rate_changed  = true;
					}
					self::$multi_currency->currencies[ $currency_code ][ $key ] = $options[ $key ];
					$changed                                                    = true;
				}
			}

			if ( $changed ) {
				if ( $rate_changed ) {
					self::$multi_currency->currencies[ $currency_code ]['previous_rate'] = $previous_rate;
					self::$multi_currency->currencies[ $currency_code ]['updated']       = date( 'Y-m-d H:i:s' );
				}
			}
		} else {
			self::$multi_currency->currencies[ $currency_code ]['countries']     = $options['countries'];
			self::$multi_currency->currencies[ $currency_code ]['location_mode'] = $options['location_mode'];
		}

		self::$woocommerce_wpml->update_setting( 'currency_options', self::$multi_currency->currencies );

		wp_send_json_success( [
			'formattedLastRateUpdate' => Hooks::formatLastRateUpdate(
				Obj::path( [ $currency_code, 'updated' ], self::$multi_currency->currencies )
			),
		] );
	}

	public static function delete_currency() {
		self::verify_nonce();
		$data = self::get_data();

		self::$multi_currency->delete_currency_by_code( $data['code'] );
		wp_send_json_success();
	}

	public static function update_currency_lang() {
		self::verify_nonce();
		$data = self::get_data();

		$settings = self::$woocommerce_wpml->get_settings();
		$settings['currency_options'][ $data['code'] ]['languages'][ $data['lang'] ] = (int) $data['value'];

		self::$woocommerce_wpml->update_settings( $settings );
		wp_send_json_success();
	}

	private static function verify_nonce() {
		$nonce = filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		if ( ! wp_verify_nonce( $nonce,  WCML\Multicurrency\UI\Hooks::HANDLE ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}
	}

	private static function get_data() {
		return json_decode( stripslashes( $_POST['data'] ), true );
	}

	public static function update_default_currency_ajax() {
		self::verify_nonce();
		self::update_default_currency();
		wp_send_json_success();
	}

	public static function update_default_currency() {
		global $woocommerce;

		$data = self::get_data();

		if ( ! empty( $woocommerce->session ) &&
		     $data['lang'] == $woocommerce->session->get( 'client_currency_language' ) &&
		     $data['code'] !== 'location' ) {
			$woocommerce->session->set( 'client_currency', $data['code'] );
		}

		self::$woocommerce_wpml->settings['default_currencies'][ $data['lang'] ] = $data['code'];
		self::$woocommerce_wpml->update_settings();

	}

	public static function currency_options_update_default_currency( $settings, $current_currency, $new_currency ) {

		// When the default WooCommerce currency is updated, if it existed as a secondary currency, remove it
		if ( isset( $settings['currency_options'][ $current_currency ] ) ) {
			$currency_settings                             = $settings['currency_options'][ $current_currency ];
			$settings['currency_options'][ $new_currency ] = $currency_settings;
			$settings                                      = self::$woocommerce_wpml->multi_currency->delete_currency_by_code( $current_currency, $settings, false );
		}

		$message_id   = 'wcml-woocommerce-default-currency-changed';
		$message_args = [
			'id'           => $message_id,
			'text'         => sprintf(
				__(
					'The default currency was changed. In order to show accurate prices in all currencies, you need to update the exchange rates under the %1$sMulti-currency%2$s configuration.',
					'woocommerce-multilingual'
				),
				'<a href="' . admin_url( 'admin.php?page=wpml-wcml&tab=multi-currency' ) . '">',
				'</a>'
			),
			'type'         => 'warning',
			'group'        => 'wcml-multi-currency',
			'admin_notice' => true,
			'hide'         => true,
		];

		ICL_AdminNotifier::remove_message( $message_id ); // clear any previous instances
		ICL_AdminNotifier::add_message( $message_args );

		return $settings;
	}

	public static function legacy_update_custom_rates() {

		$nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'legacy_update_custom_rates' ) ) {
			die( 'Invalid nonce' );
		}
		foreach ( $_POST['posts'] as $post_id => $rates ) {
			update_post_meta( $post_id, '_custom_conversion_rate', $rates );
		}

		echo json_encode( [] );
		exit;
	}

	public static function legacy_remove_custom_rates() {

		$nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'legacy_remove_custom_rates' ) ) {
			echo json_encode( [ 'error' => __( 'Invalid nonce', 'woocommerce-multilingual' ) ] );
			die();
		}

		delete_post_meta( $_POST['post_id'], '_custom_conversion_rate' );
		echo json_encode( [] );

		exit;
	}

	public static function set_prices_config() {
		global $iclTranslationManagement, $sitepress_settings, $sitepress;

		$wpml_settings = $sitepress->get_settings();

		if ( ! isset( $wpml_settings['translation-management'] ) ||
			! isset( $iclTranslationManagement ) ||
			! ( $iclTranslationManagement instanceof TranslationManagement ) ) {
			return;
		}

		$keys = [
			'_regular_price',
			'_sale_price',
			'_price',
			'_min_variation_regular_price',
			'_min_variation_sale_price',
			'_min_variation_price',
			'_max_variation_regular_price',
			'_max_variation_sale_price',
			'_max_variation_price',
			'_sale_price_dates_from',
			'_sale_price_dates_to',
			'_wcml_schedule',
		];
		$save = false;

		foreach ( $keys as $key ) {
			$iclTranslationManagement->settings['custom_fields_readonly_config'][] = $key;
			if ( ! isset( $sitepress_settings['translation-management']['custom_fields_translation'][ $key ] ) ||
				$wpml_settings['translation-management']['custom_fields_translation'][ $key ] != WPML_COPY_CUSTOM_FIELD ) {
				$wpml_settings['translation-management']['custom_fields_translation'][ $key ] = WPML_COPY_CUSTOM_FIELD;
				$save = true;
			}

			if ( ! empty( self::$multi_currency ) ) {
				foreach ( self::$multi_currency->get_currency_codes() as $code ) {
					$new_key = $key . '_' . $code;
					$iclTranslationManagement->settings['custom_fields_readonly_config'][] = $new_key;

					if ( ! isset( $sitepress_settings['translation-management']['custom_fields_translation'][ $new_key ] ) ||
						$wpml_settings['translation-management']['custom_fields_translation'][ $new_key ] != WPML_IGNORE_CUSTOM_FIELD ) {
						$wpml_settings['translation-management']['custom_fields_translation'][ $new_key ] = WPML_IGNORE_CUSTOM_FIELD;
						$save = true;
					}
				}
			}
		}

		if ( $save ) {
			$sitepress->save_settings( $wpml_settings );
		}
	}

	public static function set_currency_mode() {
		self::verify_nonce();
		$data = self::get_data();

		self::$woocommerce_wpml->settings['currency_mode'] = $data['mode'];
		self::$woocommerce_wpml->update_settings();

		wp_send_json_success();
	}

	public static function set_max_mind_key() {
		self::verify_nonce();
		$data = self::get_data();

		if ( isset( WC()->integrations ) ) {
			$integrations = WC()->integrations->get_integrations();

			if ( isset( $integrations['maxmind_geolocation'] ) ) {
				try {
					$integrations['maxmind_geolocation']->validate_license_key_field( 'license_key', $data['MaxMindKey'] );
					$integrations['maxmind_geolocation']->update_option( 'license_key', $data['MaxMindKey'] );
					wp_send_json_success();
				} catch ( Exception $e ) {
					wp_send_json_error( $e->getMessage() );
				}
			}
		}
	}

}
