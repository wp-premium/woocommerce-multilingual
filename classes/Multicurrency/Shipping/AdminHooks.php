<?php

namespace WCML\Multicurrency\Shipping;

use IWPML_Action;
use WCML_Multi_Currency;

class AdminHooks implements IWPML_Action {
	const WCML_SHIPPING_COSTS = 'wcml_shipping_costs';

	/** @var WCML_Multi_Currency */
	private $wcmlMultiCurrency;

	/**
	 * AdminHooks constructor.
	 *
	 * @param \WCML_Multi_Currency $wcmlMultiCurrency
	 */
	public function __construct( \WCML_Multi_Currency $wcmlMultiCurrency ) {
		$this->wcmlMultiCurrency = $wcmlMultiCurrency;
	}

	/**
	 * Registers hooks.
	 */
	public function add_hooks() {
		ShippingModeProvider::getAll()->each( function( ShippingMode $shippingMode ) {
			add_filter(
				'woocommerce_shipping_instance_form_fields_' . $shippingMode->getMethodId(),
				$this->addCurrencyShippingFields( $shippingMode ),
				10,
				1
			);
		}
		);
		add_action( 'admin_enqueue_scripts', [ $this, 'loadJs' ] );
	}

	public function addCurrencyShippingFields( ShippingMode $shippingMode ) {
		return function( array $field ) use ( $shippingMode ) {
			return $this->addCurrencyShippingFieldsToShippingMethodForm( $field, $shippingMode );
		};
	}

	/**
	 * Adds fields to display screen for shipping method.
	 *
	 * Adds two kind of fields:
	 * - The select field to enable/disable shipping costs in other currencies.
	 * @see \AdminHooks::add_enable_field
	 * - The input field for each registered currency to provide shipping costs.
	 * @see \AdminHooks::add_currencies_fields
	 *
	 * @param array $field
	 *
	 * @return array
	 */
	private function addCurrencyShippingFieldsToShippingMethodForm( array $field, ShippingMode $shippingMode ) {
		$field = $this->addTitleField( $field );
		$field = $this->addEnableField( $field );
		$field = $this->addCurrenciesFields( $field, $shippingMode );
		if ( $shippingMode instanceof ShippingClassesMode ) {
			$field = ShippingClasses::addFields( $field, $this->wcmlMultiCurrency );
		}
		return $field;
	}

	private function addTitleField( $field ) {
		$field[ 'wcml_shipping_costs_title' ] = [
			'title'       => __( 'Costs and values in custom currencies', 'woocommerce-multilingual' ),
			'type'        => 'title',
			'default'     => '',
			'description' => __( 'Woocommerce Multilingual by default will multiply all your costs and values defined above by currency exchange rates. If you don\'t want this and you prefer static values instead, you can define them here.', 'woocommerce-multilingual' ),
		];
		return $field;
	}

	/**
	 * Adds select field to enable/disable shipping costs in other currencies.
	 *
	 * @param array $field
	 *
	 * @return array
	 */
	private function addEnableField( array $field ) {
		$enable_field                                 = [
			'title' => esc_html__( 'Enable costs in custom currencies', 'woocommerce-multilingual' ),
			'type' => 'select',
			'class' => 'wcml-enable-shipping-custom-currency',
			'default' => 'auto',
			'options' => [
				'auto' => esc_html__( 'Calculate shipping costs in other currencies automatically', 'woocommerce-multilingual' ),
				'manual' => esc_html__( 'Set shipping costs in other currencies manually', 'woocommerce-multilingual' )
			]
		];
		$field[ self::WCML_SHIPPING_COSTS ] = $enable_field;

		return $field;
	}

	/**
	 * Adds input field for each registered currency to provide shipping costs.
	 *
	 * @param array $field
	 *
	 * @return array
	 */
	public function addCurrenciesFields( array $field, ShippingMode $shippingMode ) {
		foreach ( $this->wcmlMultiCurrency->get_currency_codes() as $currencyCode ) {
			if ( $this->wcmlMultiCurrency->get_default_currency() === $currencyCode ) {
				continue;
			}
			$field = $this->getCurrencyField( $field, $currencyCode, $shippingMode );
		}
		return $field;
	}

	/**
	 * Adds one field for given currency.
	 *
	 * @param array  $field
	 * @param string $currencyCode
	 *
	 * @return mixed
	 */
	protected function getCurrencyField( $field, $currencyCode, ShippingMode $shippingMode ) {
		$fieldKey = $shippingMode->getSettingsFormKey( $currencyCode );
		if ( $fieldKey ) {
			$fieldValue = [
				'title' => $shippingMode->getFieldTitle( $currencyCode ),
				'type' => 'text',
				'description' => $shippingMode->getFieldDescription( $currencyCode ),
				'default' => '0',
				'desc_tip' => true,
				'class' => 'wcml-shipping-cost-currency'
			];

			$field[ $fieldKey] = $fieldValue;
		}
		return $field;
	}

	/**
	 * Enqueues script responsible for JS actions on shipping fields.
	 */
	public function loadJs() {
		wp_enqueue_script(
			'wcml-admin-shipping-currency-selector',
			constant( 'WCML_PLUGIN_URL' ) . '/dist/js/multicurrencyShippingAdmin/app.js',
			[],
			constant( 'WCML_VERSION' ),
			true
		);
	}
}