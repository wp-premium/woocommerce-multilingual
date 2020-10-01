<?php

namespace WCML\Multicurrency\UI;

use WCML\MultiCurrency\Geolocation;
use WCML\Utilities\Resources;
use WPML\Collect\Support\Collection;
use WPML\FP\Fns;
use WPML\FP\Obj;
use function WPML\FP\curryN;

class Hooks implements \IWPML_Action {

	const HANDLE = 'wcml-multicurrency-options';

	/** @var \WCML_Multi_Currency $multiCurrency */
	private $multiCurrency;

	/** @var \WCML_Currencies_Payment_Gateways $currenciesPaymentGateways */
	private $currenciesPaymentGateways;

	/** @var \SitePress $sitepress */
	private $sitepress;

	/** @var array $wcmlSettings */
	private $wcmlSettings;

	public function __construct(
		\WCML_Multi_Currency $multiCurrency,
		\WCML_Currencies_Payment_Gateways $currenciesPaymentGateways,
		\SitePress $sitepress,
		array $wcmlSettings
	) {
		$this->multiCurrency             = $multiCurrency;
		$this->currenciesPaymentGateways = $currenciesPaymentGateways;
		$this->sitepress                 = $sitepress;
		$this->wcmlSettings              = $wcmlSettings;
	}

	public function add_hooks() {
		add_action( 'admin_enqueue_scripts', [ $this, 'loadAssets' ] );
	}

	public function loadAssets() {
		$gateways = $this->getGateways();
		$enqueue  = Resources::enqueueApp( 'multicurrencyOptions' );

		$enqueue( [
			'name' => 'wcmlMultiCurrency',
			'data' => [
				'endpoint'         => self::HANDLE,
				'activeCurrencies' => $this->getActiveCurrencies( $gateways ),
				'allCurrencies'    => $this->getAllCurrencies(),
				'allCountries'     => $this->getAllCountries(),
				'languages'        => $this->getLanguages(),
				'gateways'         => $gateways->toArray(),
				'strings'          => $this->getStrings(),
				'mode'             => $this->getMode(),
				'maxMindKeyExist'  => $this->checkMaxMindKeyExist(),
			],
		] );
	}

	/**
	 * @param Collection $gateways
	 *
	 * @return array
	 */
	private function getActiveCurrencies( Collection $gateways ) {
		$defaultCurrency = wcml_get_woocommerce_currency_option();

		$addCode = function( $currency, $code ) use ( $defaultCurrency ) {
			return array_merge( $currency, [ 'code' => $code ] );
		};

		$buildActiveCurrency = function( $currency ) use ( $defaultCurrency ) {
			return [
				'isDefault'       => $currency['code'] === $defaultCurrency,
				'languages'       => array_map( 'intval', $currency['languages'] ),
				'gatewaysEnabled' => $this->currenciesPaymentGateways->is_enabled( $currency['code'] ),
			];
		};

		$addGatewaysSettings = function( $currency ) use ( $gateways ) {
			$addSettingsForGateway = curryN( 2, function( $code, array $gateway ) {
				return [ $gateway['id'] => Obj::pathOr( [], [ 'settings', $code ], $gateway ) ];
			} );

			return [ 'gatewaysSettings' => $gateways->mapWithKeys( $addSettingsForGateway( $currency['code'] ) )->toArray()  ];
		};


		$addFormattedLastRateUpdate = function( $currency ) {
			return [
				'formattedLastRateUpdate' => isset( $currency['updated'] )
					? self::formatLastRateUpdate( $currency['updated'] )
					: null
			];
		};

		$merge = function( $fn ) { return Fns::converge( 'array_merge', [ $fn, Fns::identity() ]  ); };

		return wpml_collect( $this->multiCurrency->get_currencies( true ) )
			->map( $addCode )
			->map( $merge( $buildActiveCurrency ) )
			->map( $merge( $addFormattedLastRateUpdate ) )
			->map( $merge( $addGatewaysSettings ) )
			->values()
			->toArray();
	}

	/**
	 * @return array
	 */
	private function getAllCurrencies() {
		$buildCurrency = function( $label, $code ) {
			return (object) [
				'code'   => $code,
				'label'  => html_entity_decode( $label ),
				'symbol' => html_entity_decode( get_woocommerce_currency_symbol( $code ) ),
			];
		};

		return wpml_collect( get_woocommerce_currencies() )->map( $buildCurrency )->values()->toArray();
	}

	/**
	 * @return array
	 */
	private function getLanguages() {
		$buildLanguage = function( $data ) {
			return (object) [
				'code'            => $data['code'],
				'displayName'     => $data['display_name'],
				'flagUrl'         => $this->sitepress->get_flag_url( $data['code'] ),
				'defaultCurrency' => isset( $this->wcmlSettings['default_currencies'][ $data['code'] ] )
					? $this->wcmlSettings['default_currencies'][ $data['code'] ]
					: false,
			];
		};

		return wpml_collect( $this->sitepress->get_active_languages() )
			->map( $buildLanguage )
			->values()
			->toArray();
	}

	/**
	 * @return Collection
	 */
	private function getGateways() {
		$isSupported = function( \WCML_Payment_Gateway $gateway ) {
			return ! $gateway instanceof \WCML_Not_Supported_Payment_Gateway;
		};

		$buildGateway = function( \WCML_Payment_Gateway $gateway ) {
			return $gateway->get_output_model();
		};

		return wpml_collect( $this->currenciesPaymentGateways->get_gateways() )
			->prioritize( $isSupported )
			->map( $buildGateway )
			->values();
	}

	/**
	 * @return array
	 */
	private function getStrings() {
		$trackingLink = new \WCML_Tracking_Link();

		return [
			'labelCurrencies'                => __( 'Currencies', 'woocommerce-multilingual' ),
			'labelCurrency'                  => __( 'Currency', 'woocommerce-multilingual' ),
			'labelAddCurrency'               => __( 'Add currency', 'woocommerce-multilingual' ),
			'labelRate'                      => __( 'Rate', 'woocommerce-multilingual' ),
			'labelDefault'                   => __( 'default', 'woocommerce-multilingual' ),
			'labelEdit'                      => __( 'Edit', 'woocommerce-multilingual' ),
			'labelDefaultCurrency'           => __( 'Default currency', 'woocommerce-multilingual' ),
			'tooltipDefaultCurrency'         => __( 'Switch to this currency when switching language in the front-end', 'woocommerce-multilingual' ),
			'labelKeep'                      => __( 'Keep', 'woocommerce-multilingual' ),
			'labelDelete'                    => __( 'Delete', 'woocommerce-multilingual' ),
			'labelCurrenciesToDisplay'       => __( 'Currencies to display for each language', 'woocommerce-multilingual' ),
			'placeholderEnableFor'           => __( 'Enable %1$s for %2$s', 'woocommerce-multilingual' ),
			'placeholderDisableFor'          => __( 'Disable %1$s for %2$s', 'woocommerce-multilingual' ),
			'labelSettings'                  => __( 'Settings', 'woocommerce-multilingual' ),
			'labelAddNewCurrency'            => __( 'Add new currency', 'woocommerce-multilingual' ),
			'placeholderCurrencySettingsFor' => __( 'Currency settings for %s', 'woocommerce-multilingual' ),
			'labelSelectCurrency'            => __( 'Select currency', 'woocommerce-multilingual' ),
			'labelExchangeRate'              => __( 'Exchange Rate', 'woocommerce-multilingual' ),
			'labelOnlyNumeric'               => __( 'Only numeric', 'woocommerce-multilingual' ),
			'placeholderPreviousRate'        => __( '(previous value: %s)', 'woocommerce-multilingual' ),
			'labelCurrencyPreview'           => __( 'Currency Preview', 'woocommerce-multilingual' ),
			'labelPosition'                  => __( 'Currency Position', 'woocommerce-multilingual' ),
			'optionLeft'                     => __( 'Left', 'woocommerce-multilingual' ),
			'optionRight'                    => __( 'Right', 'woocommerce-multilingual' ),
			'optionLeftSpace'                => __( 'Left with space', 'woocommerce-multilingual' ),
			'optionRightSpace'               => __( 'Right with space', 'woocommerce-multilingual' ),
			'labelThousandSep'               => __( 'Thousand Separator', 'woocommerce-multilingual' ),
			'labelDecimalSep'                => __( 'Decimal Separator', 'woocommerce-multilingual' ),
			'labelNumDecimals'               => __( 'Number of Decimals', 'woocommerce-multilingual' ),
			'labelRounding'                  => __( 'Rounding to the nearest integer', 'woocommerce-multilingual' ),
			'optionDisabled'                 => __( 'Disabled', 'woocommerce-multilingual' ),
			'optionUp'                       => __( 'Up', 'woocommerce-multilingual' ),
			'optionDown'                     => __( 'Down', 'woocommerce-multilingual' ),
			'optionNearest'                  => __( 'Nearest', 'woocommerce-multilingual' ),
			'labelIncrement'                 => __( 'Increment for nearest integer', 'woocommerce-multilingual' ),
			'tooltipIncrement'               => sprintf( __( 'The resulting price will be an increment of this value after initial rounding.%se.g.:', 'woocommerce-multilingual' ), '<br>' ) . '<br />' .
			                                    __( '1454.07 &raquo; 1454 when set to 1', 'woocommerce-multilingual' ) . '<br />' .
			                                    __( '1454.07 &raquo; 1450 when set to 10', 'woocommerce-multilingual' ) . '<br />' .
			                                    __( '1454.07 &raquo; 1500 when set to 100', 'woocommerce-multilingual' ) . '<br />',
			'tooltipRounding'                => sprintf( __( 'Round the converted price to the closest integer. %se.g. 15.78 becomes 16.00', 'woocommerce-multilingual' ), '<br />' ),
			'tooltipAutosubtract'            => __( 'The value to be subtracted from the amount obtained previously.', 'woocommerce-multilingual' ) . '<br /><br />' .
			                                    __( 'For 1454.07, when the increment for the nearest integer is 100 and the auto-subtract amount is 1, the resulting amount is 1499.', 'woocommerce-multilingual' ),
			'labelAutosubtract'              => __( 'Autosubtract amount', 'woocommerce-multilingual' ),
			'labelPaymentGateways'           => __( 'Payment Gateways', 'woocommerce-multilingual' ),
			'placeholderCustomSettings'      => __( 'Custom settings for %s', 'woocommerce-multilingual' ),
			'linkUrlLearn'                   => $trackingLink->generate( 'https://wpml.org/?page_id=290080#payment-gateways-settings', 'payment-gateways-settings', 'documentation' ),
			'linkLabelLearn'                 => __( 'Learn more', 'woocommerce-multilingual' ),
			'errorInvalidNumber'             => __( 'Please enter a valid number', 'woocommerce-multilingual' ),
			'labelCancel'                    => __( 'Cancel', 'woocommerce-multilingual' ),
			'labelSave'                      => __( 'Save', 'woocommerce-multilingual' ),
			'labelAvailability'              => __( 'Currency available in', 'woocommerce-multilingual' ),
			'labelAllCountries'              => __( 'All countries', 'woocommerce-multilingual' ),
			'labelAllCountriesExcept'        => __( 'All countries except: ', 'woocommerce-multilingual' ),
			'labelAllCountriesExceptDots'    => __( 'All countries except...', 'woocommerce-multilingual' ),
			'labelSpecificCountries'         => __( 'Specific countries', 'woocommerce-multilingual' ),
			'labelModeSelect'                => __( 'Show currencies based on', 'woocommerce-multilingual' ),
			'labelChooseOption'              => __( 'Choose Option', 'woocommerce-multilingual' ),
			'labelSiteLanguage'              => __( 'Site Language', 'woocommerce-multilingual' ),
			'labelClientLocation'            => __( 'Client Location', 'woocommerce-multilingual' ),
			'labelLocationBased'             => __( 'Location based', 'woocommerce-multilingual' ),
			'maxMindDescription'             => __( 'WooCommerce uses integration with MaxMind Geolocation in order to determine the correct location for the customer. You need to generate a free MaxMind Geolocation licence key.', 'woocommerce-multilingual' ),
			'maxMindSuccess'                 => __( 'Great! Now you can use Geolocation to determine default currency for chosen languages. You can edit that key in ', 'woocommerce-multilingual' ),
			'maxMindSettingLink'             => admin_url( 'admin.php?page=wc-settings&tab=integration' ),
			'maxMindSettingLinkText'         => __( 'WooCommerce settings page.', 'woocommerce-multilingual' ),
			'maxMindLabel'                   => __( 'MaxMind Licence Key', 'woocommerce-multilingual' ),
			'apply'                          => __( 'Apply', 'woocommerce-multilingual' ),
			'maxMindDoc'                     => __( 'You can read how to generate one in ', 'woocommerce-multilingual' ),
			'maxMindDocLink'                 => 'https://docs.woocommerce.com/document/maxmind-geolocation-integration/',
			'maxMindDocLinkText'             => __( 'MaxMind Geolocation Integration documentation.', 'woocommerce-multilingual' ),
		];
	}

	/**
	 * @return array
	 */
	private function getAllCountries() {

		$buildCountry = function( $label, $code ) {
			return (object) [
				'code'   => $code,
				'label'  => html_entity_decode( $label ),
			];
		};

		return wpml_collect( WC()->countries->get_countries() )->map( $buildCountry )->values()->toArray();
	}

	/**
	 * @return string
	 */
	private function getMode(){
		return isset( $this->wcmlSettings['currency_mode'] ) ? $this->wcmlSettings['currency_mode'] : '';
	}

	/**
	 * @return bool
	 */
	private function checkMaxMindKeyExist(){
		$integrations = WC()->integrations->get_integrations();

		return isset( $integrations['maxmind_geolocation'] ) ? (bool)$integrations['maxmind_geolocation']->get_option( 'license_key' ) : false ;
	}

	/**
	 * @param string $lastRateUpdate
	 *
	 * @return string|null
	 */
	public static function formatLastRateUpdate( $lastRateUpdate ) {
		return $lastRateUpdate
			? sprintf(
				__( 'Set on %s', 'woocommerce-multilingual' ),
				date( 'F j, Y g:i a', strtotime( $lastRateUpdate ) )
			)
			: null;
	}
}
