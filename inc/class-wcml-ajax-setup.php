<?php


class WCML_Ajax_Setup {

	/**
	 * @var SitePress
	 */
	private $sitepress;

	public function __construct( SitePress $sitepress ) {

		$this->sitepress = $sitepress;
	}

	public function add_hooks() {
		add_action( 'init', [ $this, 'init' ] );
		add_action( 'woocommerce_ajax_get_endpoint', [ $this, 'add_language_to_endpoint' ] );
	}

	public function init() {
		add_filter( 'woocommerce_get_script_data', [ $this, 'add_language_parameter_to_ajax_url' ] );
		add_action( 'woocommerce_checkout_order_review', [ $this, 'add_hidden_language_field' ] );
	}

	public function add_hidden_language_field() {
		do_action( 'wpml_add_language_form_field' );
	}

	public function add_language_parameter_to_ajax_url( $woocommerce_params ) {

		if ( isset( $woocommerce_params['ajax_url'] ) && $this->sitepress->get_current_language() !== $this->sitepress->get_default_language() ) {
			$woocommerce_params['ajax_url'] = add_query_arg( 'lang', $this->sitepress->get_wp_api()->constant( 'ICL_LANGUAGE_CODE' ), $woocommerce_params['ajax_url'] );
		}

		return $woocommerce_params;
	}

	/**
	 * @param $endpoint string
	 *
	 * Adds a language parameter to the url when different domains for each language are used
	 *
	 * @return string
	 */
	public function add_language_to_endpoint( $endpoint ) {

		$is_per_domain = WPML_LANGUAGE_NEGOTIATION_TYPE_DOMAIN === (int) $this->sitepress->get_setting( 'language_negotiation_type' );
		if ( $is_per_domain && $this->sitepress->get_current_language() != $this->sitepress->get_default_language() ) {

			$endpoint = add_query_arg( 'lang', $this->sitepress->get_current_language(), remove_query_arg( 'lang', $endpoint ) );
			$endpoint = urldecode( $endpoint );

		}

		return $endpoint;
	}
}
