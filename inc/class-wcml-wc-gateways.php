<?php

use WPML\Collect\Support\Collection;
use WPML\FP\Fns;

class WCML_WC_Gateways {

	const WCML_BACS_ACCOUNTS_CURRENCIES_OPTION = 'wcml_bacs_accounts_currencies';
	const STRINGS_CONTEXT                      = 'admin_texts_woocommerce_gateways';
	private $current_language;

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var  Sitepress */
	private $sitepress;

	/**
	 * WCML_WC_Gateways constructor.
	 *
	 * @param woocommerce_wpml $woocommerce_wpml
	 * @param SitePress        $sitepress
	 */
	public function __construct( woocommerce_wpml $woocommerce_wpml, SitePress $sitepress ) {
		$this->sitepress        = $sitepress;
		$this->woocommerce_wpml = $woocommerce_wpml;

		$this->current_language = $this->sitepress->get_current_language();
		if ( 'all' === $this->current_language ) {
			$this->current_language = $this->sitepress->get_default_language();
		}
	}

	public function add_hooks() {
		add_action( 'init', [ $this, 'on_init_hooks' ], 11 );
		add_filter( 'woocommerce_payment_gateways', Fns::withoutRecursion( Fns::identity(), [ $this, 'loaded_woocommerce_payment_gateways' ] ) );
	}

	public function on_init_hooks() {
		global $pagenow;

		add_filter( 'woocommerce_gateway_title', [ $this, 'translate_gateway_title' ], 10, 2 );
		add_filter( 'woocommerce_gateway_description', [ $this, 'translate_gateway_description' ], 10, 2 );

		if ( is_admin() && 'admin.php' === $pagenow && isset( $_GET['page'] ) && 'wc-settings' === $_GET['page'] && isset( $_GET['tab'] ) && 'checkout' === $_GET['tab'] ) {
			add_action( 'admin_footer', [ $this, 'show_language_links_for_gateways' ] );
			if ( isset( $_GET['section'] ) && 'bacs' === $_GET['section'] && wcml_is_multi_currency_on() ) {
				$this->set_bacs_gateway_currency();
				add_action( 'admin_footer', [ $this, 'append_currency_selector_to_bacs_account_settings' ] );
			}
		}
	}

	public function loaded_woocommerce_payment_gateways( $load_gateways ) {

		foreach ( $load_gateways as $key => $gateway ) {

			$load_gateway = is_string( $gateway ) ? new $gateway() : $gateway;

			$this->register_gateway_settings_strings( $load_gateway->id, $load_gateway->settings );
			$this->payment_gateways_filters( $load_gateway );
			$load_gateways[ $key ] = $load_gateway;
		}

		return $load_gateways;
	}

	/**
	 * @param string $gateway_id
	 * @param array  $settings
	 */
	public function register_gateway_settings_strings( $gateway_id, $settings ) {
		if ( isset( $settings['enabled'] ) && 'yes' === $settings['enabled'] ) {
			foreach ( $this->get_gateway_text_keys_to_translate() as $text_key ) {
				if ( isset( $settings[ $text_key ] ) && ! $this->get_gateway_string_id( $settings[ $text_key ], $gateway_id, $text_key ) ) {
					$language = $this->gateway_setting_language( $settings[ $text_key ], $gateway_id, $text_key );
					icl_register_string( self::STRINGS_CONTEXT, $gateway_id . '_gateway_' . $text_key, $settings[ $text_key ], false, $language );
				}
			}
		}
	}

	public function payment_gateways_filters( $gateway ) {

		if ( isset( $gateway->id ) ) {
			$gateway_id = $gateway->id;
			$this->translate_gateway_strings( $gateway );
		}

	}

	public function translate_gateway_strings( $gateway ) {

		if ( isset( $gateway->enabled ) && $gateway->enabled !== 'no' ) {

			if ( isset( $gateway->instructions ) ) {
				$gateway->instructions = $this->translate_gateway_instructions( $gateway->instructions, $gateway->id );
			}

			if ( isset( $gateway->description ) ) {
				$gateway->description = $this->translate_gateway_description( $gateway->description, $gateway->id );
			}

			if ( isset( $gateway->title ) ) {
				$gateway->title = $this->translate_gateway_title( $gateway->title, $gateway->id );
			}
		}

		return $gateway;

	}

	public function translate_gateway_title( $title, $gateway_id ) {
		return $this->get_translated_gateway_string( $title, $gateway_id, 'title' );
	}

	public function translate_gateway_description( $description, $gateway_id ) {
		return $this->get_translated_gateway_string( $description, $gateway_id, 'description' );
	}

	public function translate_gateway_instructions( $instructions, $gateway_id ) {
		return $this->get_translated_gateway_string( $instructions, $gateway_id, 'instructions' );
	}

	public function get_translated_gateway_string( $string, $gateway_id, $name ) {
		$translated_string = apply_filters(
			'wpml_translate_single_string',
			$string,
			self::STRINGS_CONTEXT,
			$gateway_id . '_gateway_' . $name,
			$this->get_current_gateway_language()
		);

		if ( $translated_string === $string ) {
			$translated_string = __( $string, 'woocommerce' );
			if ( 'cheque' === $gateway_id && $translated_string === $string && 'title' === $name ) {
				$translated_string = _x( $string, 'Check payment method', 'woocommerce' );
			}
		}

		return $translated_string;
	}

	/**
	 * @return string
	 */
	private function get_current_gateway_language() {

		$postData = wpml_collect( $_POST );
		if ( $postData->isNotEmpty() ) {
			if ( $this->is_user_order_note( $postData ) ) {
				$current_gateway_language = get_post_meta( $postData->get( 'post_id' ), 'wpml_language', true );
			} elseif ( $this->is_refund_line_item( $postData ) ) {
				$current_gateway_language = get_post_meta( $postData->get( 'order_id' ), 'wpml_language', true );
			} else {
				$current_gateway_language = $this->get_order_action_gateway_language( $postData );
			}
		} else {
			$current_gateway_language = $this->get_order_ajax_action_gateway_language();
		}

		/**
		 * Filters the current gateway language
		 *
		 * @since 4.9.0
		 *
		 * @param string $current_gateway_language
		 */
		return apply_filters( 'wcml_current_gateway_language', $current_gateway_language );
	}

	/**
	 * @param Collection $postData
	 *
	 * @return bool
	 */
	private function is_user_order_note( Collection $postData ) {
		return 'woocommerce_add_order_note' === $postData->get( 'action' ) && 'customer' === $postData->get( 'note_type' );
	}

	/**
	 * @param Collection $postData
	 *
	 * @return bool
	 */
	private function is_refund_line_item( Collection $postData ){
	    return 'woocommerce_refund_line_items' === $postData->get( 'action' );
    }


	/**
	 * @param Collection $postData
	 *
	 * @return string
	 */
	private function get_order_action_gateway_language( Collection $postData ) {

		if ( $postData->get( 'post_ID' ) ) {

			$is_saving_new_order = wpml_collect( [
					'auto-draft',
					'draft'
				] )->contains( $postData->get( 'post_status' ) )
			                       && 'editpost' === $postData->get( 'action' )
			                       && $postData->get( 'save' );
			if ( $is_saving_new_order && isset( $_COOKIE[ WCML_Orders::DASHBOARD_COOKIE_NAME ] ) ) {
				return $_COOKIE[ WCML_Orders::DASHBOARD_COOKIE_NAME ];
			}

			$is_order_emails_status       = wpml_collect( [
				'wc-completed',
				'wc-processing',
				'wc-refunded',
				'wc-on-hold'
			] )->contains( $postData->get( 'order_status' ) );

			$is_send_order_details_action = 'send_order_details' === $postData->get( 'wc_order_action' );
			if ( $is_order_emails_status || $is_send_order_details_action ) {
				return get_post_meta( $postData->get( 'post_ID' ), 'wpml_language', true );
			}
		}

		return $this->current_language;
	}

	/**
	 * @return string
	 */
	private function get_order_ajax_action_gateway_language(){

		$getData = wpml_collect( $_GET );
		if ( $getData->isNotEmpty() ) {
			$is_order_ajax_action = 'woocommerce_mark_order_status' === $getData->get( 'action' ) && wpml_collect( [
					'completed',
					'processing'
				] )->contains( $getData->get( 'status' ) );
			if ( $is_order_ajax_action && $getData->get( 'order_id' ) ) {
				return get_post_meta( $getData->get( 'order_id' ), 'wpml_language', true );
			}
		}

		return $this->current_language;
    }

	public function show_language_links_for_gateways() {

		$text_keys = $this->get_gateway_text_keys_to_translate();

		$wc_payment_gateways = WC_Payment_Gateways::instance();

		foreach ( $wc_payment_gateways->payment_gateways() as $payment_gateway ) {

			if ( isset( $_GET['section'] ) && $_GET['section'] == $payment_gateway->id ) {

				foreach ( $text_keys as $text_key ) {

					if ( isset( $payment_gateway->settings[ $text_key ] ) ) {
						$setting_value = $payment_gateway->settings[ $text_key ];
					} elseif ( $text_key === 'instructions' ) {
						$setting_value = $payment_gateway->description;
					} else {
						$setting_value = $payment_gateway->$text_key;
					}

					$input_name     = $payment_gateway->plugin_id . $payment_gateway->id . '_' . $text_key;
					$gateway_option = $payment_gateway->plugin_id . $payment_gateway->id . '_settings';

					$lang_selector = new WPML_Simple_Language_Selector( $this->sitepress );
					$language      = $this->gateway_setting_language( $setting_value, $payment_gateway->id, $text_key );

					$lang_selector->render(
						[
							'id'                 => $gateway_option . '_' . $text_key . '_language_selector',
							'name'               => 'wcml_lang-' . $gateway_option . '-' . $text_key,
							'selected'           => $language,
							'show_please_select' => false,
							'echo'               => true,
							'style'              => 'width: 18%;float: left;margin-top: 3px;',
						]
					);

					$st_page = admin_url( 'admin.php?page=' . WPML_ST_FOLDER . '/menu/string-translation.php&context=' . self::STRINGS_CONTEXT . '&search=' . esc_attr( preg_replace( "/[\n\r]/", '', $setting_value ) ) );
					?>
					<script>
						var input = jQuery('#<?php echo esc_js( $input_name ); ?>');
						if ( input.length > 0 ) {
							input.parent().append('<div class="translation_controls"></div>');
							input.parent().find('.translation_controls').append('<a href="<?php echo $st_page; ?>" style="margin-left: 10px"><?php _e( 'translations', 'woocommerce-multilingual' ); ?></a>');
							jQuery('#<?php echo $gateway_option . '_' . $text_key . '_language_selector'; ?>').prependTo( input.parent().find('.translation_controls') );
						}else{
							jQuery('#<?php echo $gateway_option . '_' . $text_key . '_language_selector'; ?>').remove();
						}
					</script>
					<?php
				}
			}
		}
	}

	private function gateway_setting_language( $setting_value, $gateway_id, $text_key ) {

		if ( $this->get_gateway_string_id( $setting_value, $gateway_id, $text_key ) ) {
			return $this->woocommerce_wpml->strings->get_string_language( $setting_value, self::STRINGS_CONTEXT, $gateway_id . '_gateway_' . $text_key );
		} else {
			return $this->sitepress->get_default_language();
		}

	}

	private function get_gateway_string_id( $value, $gateway_id, $name ) {
		return icl_get_string_id( $value, self::STRINGS_CONTEXT, $gateway_id . '_gateway_' . $name );
	}

	public function set_bacs_gateway_currency() {
		foreach ( $_POST as $key => $value ) {

			if ( '_enabled' === substr( $key, -8 ) ) {
				$gateway = str_replace( '_enabled', '', $key );
			}
		}

		if ( isset( $gateway ) ) {
			if ( 'woocommerce_bacs' === $gateway && isset( $_POST['bacs-currency'] ) ) {
				update_option( self::WCML_BACS_ACCOUNTS_CURRENCIES_OPTION, filter_var_array( $_POST['bacs-currency'], FILTER_SANITIZE_STRING ) );
			}
		}

	}

	public function get_gateway_text_keys_to_translate() {

		$text_keys = [
			'title',
			'description',
			'instructions',
		];

		return apply_filters( 'wcml_gateway_text_keys_to_translate', $text_keys );
	}

	public function append_currency_selector_to_bacs_account_settings() {

		$template_loader        = new WPML_Twig_Template_Loader( [ $this->sitepress->get_wp_api()->constant( 'WCML_PLUGIN_PATH' ) . '/templates/multi-currency/' ] );
		$currencies_dropdown_ui = new WCML_Currencies_Dropdown_UI( $template_loader );

		list( $default_dropdown, $currencies_output ) = $this->get_dropdown( $currencies_dropdown_ui );

		wp_enqueue_script( 'wcml-bacs-accounts-currencies', WCML_PLUGIN_URL . '/res/js/bacs-accounts-currencies' . WCML_JS_MIN . '.js', [ 'jquery' ], WCML_VERSION, true );
		wp_localize_script(
			'wcml-bacs-accounts-currencies',
			'wcml_data',
			[
				'currencies_dropdown' => $currencies_output,
				'label'               => __( 'Currency', 'woocommerce-multilingual' ),
				'default_dropdown'    => $default_dropdown,
			]
		);
	}

	/**
	 * @param WCML_Currencies_Dropdown_UI $currencies_dropdown_ui
	 *
	 * @return array
	 */
	public function get_dropdown( $currencies_dropdown_ui ) {

		$bacs_settings            = get_option( 'woocommerce_bacs_accounts', [] );
		$active_currencies        = $this->woocommerce_wpml->multi_currency->get_currency_codes();
		$default_currency         = wcml_get_woocommerce_currency_option();
		$bacs_accounts_currencies = get_option( self::WCML_BACS_ACCOUNTS_CURRENCIES_OPTION, [] );
		$currencies_output        = [];

		$default_dropdown = $currencies_dropdown_ui->get( $active_currencies, $default_currency );

		if ( $bacs_settings ) {
			foreach ( $bacs_settings as $id => $account_settings ) {
				$currencies_output[ $id ] = isset( $bacs_accounts_currencies[ $id ] ) ? $currencies_dropdown_ui->get( $active_currencies, $bacs_accounts_currencies[ $id ] ) : $default_dropdown;
			}
		} else {
			$currencies_output[] = $default_dropdown;
		}

		return [ $default_dropdown, $currencies_output ];
	}
}
