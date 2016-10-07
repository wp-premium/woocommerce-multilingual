<?php

/**
 * Class WCML_Table_Rate_Shipping
 */
class WCML_Table_Rate_Shipping {

	/**
	 * @var SitePress
	 */
	public $sitepress;

	/**
	 * @var woocommerce_wpml
	 */
	public $woocommerce_wpml;

	/**
	 * WCML_Table_Rate_Shipping constructor.
	 *
	 * @param $sitepress
	 * @param $woocommerce_wpml
	 */
	function __construct( &$sitepress, &$woocommerce_wpml ) {
		$this->sitepress = $sitepress;
		$this->woocommerce_wpml = $woocommerce_wpml;
		add_action( 'init', array( $this, 'init' ), 9 );
		add_filter( 'get_the_terms',array( $this, 'shipping_class_id_in_default_language' ), 10, 3 );

		if( wcml_is_multi_currency_on() ){
			add_filter( 'woocommerce_table_rate_query_rates_args', array( $this, 'filter_query_rates_args' ) );
		}

	}

	/**
	 * Register shipping labels for string translation.
	 */
	public function init() {
		// Register shipping label
		if (
			isset( $_GET[ 'page' ] ) &&
			(
				$_GET[ 'page' ] === 'shipping_zones' ||
				(
					$_GET['page'] == 'wc-settings' &&
					isset( $_GET[ 'tab' ] ) &&
					$_GET['tab'] == 'shipping'
				)
			) &&
			isset( $_POST[ 'shipping_label' ] ) &&
			isset( $_POST[ 'woocommerce_table_rate_title' ] )
		) {

			do_action( 'wpml_register_single_string', 'woocommerce', sanitize_text_field( $_POST[ 'woocommerce_table_rate_title' ] ) . '_shipping_method_title', sanitize_text_field( $_POST[ 'woocommerce_table_rate_title' ] ) );
			$shipping_labels = array_map( 'woocommerce_clean', $_POST[ 'shipping_label' ] );
			foreach ( $shipping_labels as $key => $shipping_label ) {
				$rate_key = isset( $_GET[ 'instance_id' ] ) ? 'table_rate'.$_GET[ 'instance_id' ].$_POST[ 'rate_id' ][ $key ] : $shipping_label;
				do_action( 'wpml_register_single_string', 'woocommerce', $rate_key. '_shipping_method_title', $shipping_label );
			}
		}
	}

	/**
	 * @param $terms
	 * @param $post_id
	 * @param $taxonomy
	 *
	 * @return mixed
	 */
	public function shipping_class_id_in_default_language( $terms, $post_id, $taxonomy ) {
		global $icl_adjust_id_url_filter_off;
		if ( 'product_shipping_class' === $taxonomy ) {

			foreach ( $terms as $k => $term ) {
				$shipping_class_id = apply_filters( 'translate_object_id', $term->term_id, 'product_shipping_class', false, $this->sitepress->get_default_language() );

				$icl_adjust_id_url_filter = $icl_adjust_id_url_filter_off;
				$icl_adjust_id_url_filter_off = true;

				$terms[ $k ] = get_term( $shipping_class_id,  'product_shipping_class' );

				$icl_adjust_id_url_filter_off = $icl_adjust_id_url_filter;
			}
		}

		return $terms;
	}

	/**
	 * It's not possible to filter rate_min and rate_max so we use the original price to compare against these values
	 */
	public function filter_query_rates_args( $args ){

		if( isset( $args['price'] ) && get_option( 'woocommerce_currency') != $this->woocommerce_wpml->multi_currency->get_client_currency() ){
			$args['price'] = $this->woocommerce_wpml->multi_currency->prices->unconvert_price_amount( $args['price'] );
		}

		return $args;
	}


}
