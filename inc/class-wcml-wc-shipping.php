<?php

class WCML_WC_Shipping {

	const STRINGS_CONTEXT = 'admin_texts_woocommerce_shipping';

	private $current_language;
	/** @var Sitepress */
	private $sitepress;
	/** @var WCML_WC_Strings */
	private $wcmlStrings;

	/**
	 * WCML_WC_Shipping constructor.
	 *
	 * @param SitePress       $sitepress
	 * @param WCML_WC_Strings $wcmlStrings
	 */
	public function __construct( SitePress $sitepress, WCML_WC_Strings $wcmlStrings ) {

		$this->sitepress   = $sitepress;
		$this->wcmlStrings = $wcmlStrings;

		$this->current_language = $this->sitepress->get_current_language();
		if ( $this->current_language == 'all' ) {
			$this->current_language = $this->sitepress->get_default_language();
		}

	}

	public function add_hooks() {

		add_action( 'woocommerce_tax_rate_added', [ $this, 'register_tax_rate_label_string' ], 10, 2 );
		add_action( 'wp_ajax_woocommerce_shipping_zone_methods_save_settings', [ $this, 'save_shipping_zone_method_from_ajax' ], 9 );
		add_action( 'icl_save_term_translation', [ $this, 'sync_class_costs_for_new_shipping_classes' ], 100, 2 );
		add_action( 'wp_ajax_woocommerce_shipping_zone_methods_save_settings', [ $this, 'update_woocommerce_shipping_settings_for_class_costs_from_ajax' ], 9 );

		add_filter( 'woocommerce_package_rates', [ $this, 'translate_shipping_methods_in_package' ] );
		add_filter( 'woocommerce_rate_label', [ $this, 'translate_woocommerce_rate_label' ] );
		add_filter( 'pre_update_option_woocommerce_flat_rate_settings', [ $this, 'update_woocommerce_shipping_settings_for_class_costs' ] );
		add_filter( 'pre_update_option_woocommerce_international_delivery_settings', [ $this, 'update_woocommerce_shipping_settings_for_class_costs' ] );
		add_filter( 'woocommerce_shipping_flat_rate_instance_option', [ $this, 'get_original_shipping_class_rate' ], 10, 3 );

		$this->shipping_methods_filters();
	}

	public function shipping_methods_filters() {

		$shipping_methods = WC()->shipping->get_shipping_methods();

		foreach ( $shipping_methods as $shipping_method ) {

			if ( isset( $shipping_method->id ) ) {
				$shipping_method_id = $shipping_method->id;
			} else {
				continue;
			}

			add_filter(
				'woocommerce_shipping_' . $shipping_method_id . '_instance_settings_values',
				[
					$this,
					'register_zone_shipping_strings',
				],
				9,
				2
			);
			add_filter(
				'option_woocommerce_' . $shipping_method_id . '_settings',
				[
					$this,
					'translate_shipping_strings',
				],
				9,
				2
			);
		}
	}

	public function save_shipping_zone_method_from_ajax() {
		foreach ( $_POST['data'] as $key => $value ) {
			if ( strstr( $key, '_title' ) ) {
				$shipping_id = str_replace( 'woocommerce_', '', $key );
				$shipping_id = str_replace( '_title', '', $shipping_id );
				$this->register_shipping_title( $shipping_id . $_POST['instance_id'], $value );
				break;
			}
		}
	}

	public function register_zone_shipping_strings( $instance_settings, $object ) {

		if ( ! empty( $instance_settings['title'] ) ) {
			$this->register_shipping_title( $object->id . $object->instance_id, $instance_settings['title'] );

			$instance_settings = $this->sync_flat_rate_class_cost( $object->get_post_data(), $instance_settings );
		}

		return $instance_settings;
	}

	public function register_shipping_title( $shipping_method_id, $title ) {
		do_action( 'wpml_register_single_string', self::STRINGS_CONTEXT, $shipping_method_id . '_shipping_method_title', $title );
	}

	public function translate_shipping_strings( $value, $option = false ) {

		if ( $option && isset( $value['enabled'] ) && $value['enabled'] == 'no' ) {
			return $value;
		}

		$shipping_id = str_replace( 'woocommerce_', '', $option );
		$shipping_id = str_replace( '_settings', '', $shipping_id );

		if ( isset( $value['title'] ) ) {
			$value['title'] = $this->translate_shipping_method_title( $value['title'], $shipping_id );
		}

		return $value;
	}

	public function translate_shipping_methods_in_package( $available_methods ) {

		foreach ( $available_methods as $key => $method ) {
			/**
			 * @since 4.6.5
			 */
			if ( apply_filters( 'wcml_translate_shipping_method_in_package', true, $key, $method ) ) {
				$available_methods[ $key ]->label = $this->translate_shipping_method_title( $method->label, $key );
			}
		}

		return apply_filters( 'wcml_translated_package_rates', $available_methods );
	}

	/**
	 * @param string      $title
	 * @param string      $shipping_id
	 * @param string|bool $language
	 *
	 * @return string
	 */
	public function translate_shipping_method_title( $title, $shipping_id, $language = false ) {

		if ( is_admin() && did_action( 'admin_init' ) && did_action( 'current_screen' ) ) {
			$screen        = get_current_screen();
			$is_edit_order = $screen->id === 'shop_order';
		} else {
			$is_edit_order = false;
		}

		if ( ! is_admin() || $is_edit_order ) {

			$shipping_id = str_replace( ':', '', $shipping_id );

			$translated_title = apply_filters(
				'wpml_translate_single_string',
				$title,
				self::STRINGS_CONTEXT,
				$shipping_id . '_shipping_method_title',
				$language ? $language : $this->current_language
			);

			return $translated_title ?: $title;
		}

		return $title;
	}

	public function translate_woocommerce_rate_label( $label ) {

		$label = apply_filters( 'wpml_translate_single_string', $label, 'woocommerce taxes', $label );

		return $label;
	}

	public function register_tax_rate_label_string( $id, $tax_rate ) {

		if ( ! empty( $tax_rate['tax_rate_name'] ) ) {
			do_action( 'wpml_register_single_string', 'woocommerce taxes', $tax_rate['tax_rate_name'], $tax_rate['tax_rate_name'] );
		}

	}

	public function sync_class_costs_for_new_shipping_classes( $original_tax, $result ) {
		// update flat rate options for shipping classes.
		if ( $original_tax->taxonomy == 'product_shipping_class' ) {

			$settings = get_option( 'woocommerce_flat_rate_settings' );
			if ( is_array( $settings ) ) {
				update_option( 'woocommerce_flat_rate_settings', $this->update_woocommerce_shipping_settings_for_class_costs( $settings ) );
			}

			$settings = get_option( 'woocommerce_international_delivery_settings' );
			if ( is_array( $settings ) ) {
				update_option( 'woocommerce_international_delivery_settings', $this->update_woocommerce_shipping_settings_for_class_costs( $settings ) );
			}
		}
	}

	public function update_woocommerce_shipping_settings_for_class_costs( $settings ) {
		remove_filter( 'get_term', [ $this->sitepress, 'get_term_adjust_id' ], 1 );
		foreach ( $settings as $setting_key => $value ) {

			if ( substr( $setting_key, 0, 11 ) == 'class_cost_' ) {

				$shipp_class_key = substr( $setting_key, 11 );

				if ( is_numeric( $shipp_class_key ) ) {
					$shipp_class = get_term( $shipp_class_key, 'product_shipping_class' );
				} else {
					$shipp_class = get_term_by( 'slug', $shipp_class_key, 'product_shipping_class' );
				}
				$trid = $this->sitepress->get_element_trid( $shipp_class->term_taxonomy_id, 'tax_product_shipping_class' );

				$translations = $this->sitepress->get_element_translations( $trid, 'tax_product_shipping_class' );

				foreach ( $translations as $translation ) {

					$tr_shipp_class = get_term_by( 'term_taxonomy_id', $translation->element_id, 'product_shipping_class' );

					if ( is_numeric( $shipp_class_key ) ) {
						$settings[ 'class_cost_' . $tr_shipp_class->term_id ] = $value;
					} else {
						$settings[ 'class_cost_' . $tr_shipp_class->slug ] = $value;
					}
				}
			}
		}
		add_filter( 'get_term', [ $this->sitepress, 'get_term_adjust_id' ], 1 );

		return $settings;
	}

	public function update_woocommerce_shipping_settings_for_class_costs_from_ajax() {

		if ( isset( $_POST['data']['woocommerce_flat_rate_type'] ) && $_POST['data']['woocommerce_flat_rate_type'] == 'class' ) {

			$flat_rate_setting_id = 'woocommerce_flat_rate_' . $_POST['data']['instance_id'] . '_settings';
			$settings             = get_option( $flat_rate_setting_id, true );

			$settings = $this->sync_flat_rate_class_cost( $_POST['data'], $settings );

			update_option( $flat_rate_setting_id, $settings );
		}
	}

	/**
	 * @param array $data
	 * @param array $inst_settings
	 *
	 * @return array|mixed
	 */
	public function sync_flat_rate_class_cost( $data, $inst_settings ) {

		$settings = [];
		foreach ( $data as $key => $value ) {
			if ( 0 === strpos( $key, 'woocommerce_flat_rate_class_cost_' ) ) {
				$limit                              = strlen( 'woocommerce_flat_rate_' );
				$settings[ substr( $key, $limit ) ] = stripslashes( $value );
			}
		}

		$updated_costs_settings = $this->update_woocommerce_shipping_settings_for_class_costs( $settings );

		$inst_settings = is_array( $inst_settings ) ? array_merge( $inst_settings, $updated_costs_settings ) : $updated_costs_settings;

		return $inst_settings;
	}

	/**
	 * @param string             $rate
	 * @param string             $class_name
	 * @param WC_Shipping_Method $shipping_method
	 *
	 * @return string
	 */
	public function get_original_shipping_class_rate( $rate, $class_name, $shipping_method ) {
		if ( ! $rate && 'class_cost_' === substr( $class_name, 0, 11 ) ) {
			$original_class_id = $this->sitepress->term_translations()->get_original_element( substr( $class_name, 11 ) );
			if ( $original_class_id && isset( $shipping_method->instance_settings[ 'class_cost_' . $original_class_id ] ) ) {
				return $shipping_method->instance_settings[ 'class_cost_' . $original_class_id ];
			}
		}

		return $rate;
	}

}
