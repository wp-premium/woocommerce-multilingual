<?php

namespace WCML\PaymentGateways;

use IWPML_Backend_Action;
use IWPML_Frontend_Action;
use IWPML_DIC_Action;
use WCML\MultiCurrency\Geolocation;
use WCML\Utilities\Resources;
use WPML\FP\Maybe;
use WPML\FP\Obj;
use WPML\FP\Relation;


class Hooks implements IWPML_Backend_Action, IWPML_Frontend_Action, IWPML_DIC_Action {

	const OPTION_KEY = 'wcml_payment_gateways';
	/* took this priority from wcgcl but we could not recall the reason of this number.*/
	const PRIORITY = 1000;

	public function add_hooks() {

		if ( is_admin() ) {
			if ( $this->isWCGatewaysSettingsScreen() ) {
				add_action( 'woocommerce_update_options_checkout', [ $this, 'updateSettingsOnSave' ], self::PRIORITY );
				add_action( 'woocommerce_settings_checkout', [ $this, 'output' ], self::PRIORITY );
				add_action( 'admin_enqueue_scripts', [ $this, 'loadAssets' ] );
			}
			add_action( 'admin_notices', [ $this, 'maybeAddNotice'] );
		} else {
			add_filter( 'woocommerce_available_payment_gateways', [ $this, 'filterByCountry' ], self::PRIORITY );
		}
	}

	public function updateSettingsOnSave() {

		if ( isset( $_POST[ self::OPTION_KEY ] ) ) {

			$gatewaySettings = $_POST[ self::OPTION_KEY ];

			$settings = $this->getSettings();

			$gatewayId = filter_var( $gatewaySettings['ID'], FILTER_SANITIZE_STRING );

			$settings[ $gatewayId ]['mode'] = in_array( $gatewaySettings['mode'], [
				'all',
				'exclude',
				'include'
			], true ) ? $gatewaySettings['mode'] : 'all';

			$settings[ $gatewayId ]['countries'] = isset( $gatewaySettings['countries'] ) ? array_map( 'esc_attr', array_filter( explode( ',', filter_var( $gatewaySettings['countries'], FILTER_SANITIZE_STRING ) ) ) ) : [];

			$this->updateSettings( $settings );
		}
	}

	public function loadAssets() {
		$enqueue = Resources::enqueueApp( 'paymentGatewaysAdmin' );

		$gatewayId = sanitize_title( $_GET['section'] );

		$enqueue( [
			'name' => 'wcmlPaymentGateways',
			'data' => [
				'endpoint'     => self::OPTION_KEY,
				'gatewayId'    => $gatewayId,
				'allCountries' => $this->getAllCountries(),
				'strings'      => $this->getStrings(),
				'settings'     => $this->getGatewaySettings( $gatewayId ),
			],
		] );

		wp_register_style( 'wcml-payment-gateways', WCML_PLUGIN_URL . '/res/css/wcml-payment-gateways.css', [], WCML_VERSION );
		wp_enqueue_style( 'wcml-payment-gateways' );
	}

	/**
	 * @return array
	 */
	private function getStrings() {

		return [
			'labelAvailability'           => __( 'Country availability', 'woocommerce-multilingual' ),
			'labelAllCountries'           => __( 'All countries', 'woocommerce-multilingual' ),
			'labelAllCountriesExcept'     => __( 'All countries except', 'woocommerce-multilingual' ),
			'labelAllCountriesExceptDots' => __( 'All countries except...', 'woocommerce-multilingual' ),
			'labelSpecificCountries'      => __( 'Specific countries', 'woocommerce-multilingual' ),
			'tooltip'                     => __( 'Configure per country availability for this payment gateway', 'woocommerce-multilingual' ),
		];
	}

	/**
	 * @return array
	 */
	private function getAllCountries() {

		$buildCountry = function ( $label, $code ) {
			return (object) [
				'code'  => $code,
				'label' => html_entity_decode( $label ),
			];
		};

		return wpml_collect( WC()->countries->get_countries() )->map( $buildCountry )->values()->toArray();
	}

	public function output() {
		?><div id="wcml-payment-gateways"></div><?php
	}

	/**
	 * @param array $payment_gateways
	 *
	 * @return array
	 */
	public function filterByCountry( $payment_gateways ) {

		$customer_country = Geolocation::getUserCountry();

		if ( $customer_country ) {

			$ifExceptCountries = function ( $gateway ) use ( $customer_country ) {
				$gatewaySettings = $this->getGatewaySettings( $gateway->id );

				return $gatewaySettings['mode'] == 'exclude' && in_array( $customer_country, $gatewaySettings['countries'] );
			};

			$ifNotIncluded = function ( $gateway ) use ( $customer_country ) {
				$gatewaySettings = $this->getGatewaySettings( $gateway->id );

				return $gatewaySettings['mode'] == 'include' && ! in_array( $customer_country, $gatewaySettings['countries'] );
			};

			return wpml_collect( $payment_gateways )
				->reject( $ifExceptCountries )
				->reject( $ifNotIncluded )
				->toArray();
		}

		return $payment_gateways;
	}

	public function maybeAddNotice(){
		if( class_exists( 'WooCommerce_Gateways_Country_Limiter' ) ) {
			echo $this->getNoticeText();
		}
	}

	/**
	 * @return string
	 */
	private function getNoticeText(){

		$text = '<div id="message" class="updated error">';
		$text .= '<p>';
		$text .= __( 'We noticed that you\'re using WooCommerce Gateways Country Limiter plugin which is now integrated into WooCommerce Multilingual. Please remove it!', 'woocommerce-multilingual' );
		$text .= '</p>';
		$text .= '</div>';

		return $text;
	}

	/**
	 * @param string $gatewayId
	 *
	 * @return array
	 */
	private function getGatewaySettings( $gatewayId ) {
		return Maybe::fromNullable( get_option( self::OPTION_KEY, false ) )
		            ->map( Obj::prop( $gatewayId ) )
		            ->getOrElse( [ 'mode' => 'all', 'countries' => [] ] );
	}

	/**
	 * @param string $gatewayId
	 *
	 * @return array
	 */
	private function getSettings() {
		return get_option( self::OPTION_KEY, [] );
	}

	/**
	 * @param array $settings
	 *
	 * @return bool
	 */
	private function updateSettings( $settings ) {
		return update_option( self::OPTION_KEY, $settings );
	}

	/**
	 * @return bool
	 */
	private function isWCGatewaysSettingsScreen() {
		return Obj::prop( 'section', $_GET ) && Relation::equals( 'wc-settings', Obj::prop( 'page', $_GET ) ) && Relation::equals( 'checkout', Obj::prop( 'tab', $_GET ) );
	}

}
