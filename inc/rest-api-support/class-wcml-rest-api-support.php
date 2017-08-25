<?php

class WCML_REST_API_Support{

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var Sitepress */
	private $sitepress;

	function __construct( &$woocommerce_wpml, &$sitepress ) {
		$this->woocommerce_wpml =& $woocommerce_wpml;
		$this->sitepress        =& $sitepress;
	}

	/**
	 * Adding hooks
	 */
	public function initialize(){
				
		$this->prevent_default_lang_url_redirect();

		add_action( 'rest_api_init', array( $this, 'set_language_for_request' ) );

		add_action( 'parse_query', array($this, 'auto_adjust_included_ids') );

		// Products
		add_action( 'woocommerce_rest_prepare_product_object', array( $this, 'append_product_language_and_translations' ) );
		add_action( 'woocommerce_rest_prepare_product_object', array( $this, 'append_product_secondary_prices' ) );
		add_filter( 'woocommerce_rest_product_query', array( $this, 'filter_products_query' ), 10, 2 );
		add_action( 'woocommerce_rest_insert_product_object', array( $this, 'set_product_language' ), 10, 2 );
		add_action( 'woocommerce_rest_insert_product_object', array( $this, 'set_product_custom_prices' ), 10, 2 );
		add_action( 'woocommerce_rest_insert_product_object', array( $this, 'copy_custom_fields_from_original' ), 10, 1 );

		add_action( 'woocommerce_rest_prepare_product_object', array( $this, 'copy_product_custom_fields' ), 10 , 3 );

		// Orders
		add_filter( 'woocommerce_rest_shop_order_object_query', array( $this, 'filter_orders_by_language' ), 20, 2 );
		add_action( 'woocommerce_rest_prepare_shop_order_object', array( $this, 'filter_order_items_by_language'), 10, 3 );
		add_action( 'woocommerce_rest_insert_shop_order_object' , array( $this, 'set_order_language' ), 10, 2 );
		add_action( 'woocommerce_rest_insert_shop_order_object' , array( $this, 'set_order_currency' ), 10, 2 );

		// Terms
		add_action( 'woocommerce_rest_product_cat_query', array($this, 'filter_terms_query' ), 10, 2 );
		add_action( 'woocommerce_rest_product_tag_query', array($this, 'filter_terms_query' ), 10, 2 );

	}

	/**
	 * Check if is request to the WooCommerce REST API.
	 *
	 * @return bool
	 */
	public static function is_rest_api_request(){

		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		$rest_prefix = trailingslashit( rest_get_url_prefix() );
		// Check if WooCommerce endpoint.
		$woocommerce = ( false !== strpos( $_SERVER['REQUEST_URI'], $rest_prefix . 'wc/' ) );

		return apply_filters( 'woocommerce_rest_is_request_to_rest_api', $woocommerce );

	}

	/**
	 * @return int
	 * Returns the version number of the API used for the current request
	 */
	public static function get_api_request_version(){
		$version = 0;
		$rest_prefix = trailingslashit( rest_get_url_prefix() );
		if( preg_match( "@" . $rest_prefix . "wc/v([0-9]+)/@", $_SERVER['REQUEST_URI'], $matches ) ){
			$version = intval($matches[1]);
		}
		return $version;
	}

	/**
	 * @param WP_REST_Server $wp_rest_server
	 * enforces the language of request as the current language to be able to filter items by language
	 */
	public function set_language_for_request( $wp_rest_server ){
		if( isset( $_GET['lang'] )  ){
			$request_language = $_GET['lang'];
			$active_languages = $this->sitepress->get_active_languages();
			if( isset( $active_languages[ $request_language ] ) ){
				$this->sitepress->switch_lang( $request_language );
			}
		}
	}

	/**
	 * Prevent WPML redirection when using the default language as a parameter in the url
	 */
	private function prevent_default_lang_url_redirect(){
		$exp = explode( '?', $_SERVER['REQUEST_URI'] );
		if ( ! empty( $exp[1] ) ) {
			parse_str( $exp[1], $vars );
			if ( isset($vars['lang']) && $vars['lang'] === $this->sitepress->get_default_language() ) {
				unset( $vars['lang'] );
				$_SERVER['REQUEST_URI'] = $exp[0] . '?' . http_build_query( $vars );
			}
		}
	}

	// Use url without the language parameter. Needed for the signature match.
	public static function remove_wpml_global_url_filters(){
		global $wpml_url_filters;
		remove_filter( 'home_url', array( $wpml_url_filters, 'home_url_filter' ), - 10 );
	}

	/**
	 * When lang=all don't filter products by language
	 *
	 * @param array $args
	 * @param WP_REST_Request $request
	 *
	 * @return array
	 */
	public function filter_products_query( $args, $request ){
		$data = $request->get_params();
		if( isset( $data['lang'] ) && $data['lang'] === 'all' ){
			global $wpml_query_filter;
			remove_filter( 'posts_join', array( $wpml_query_filter, 'posts_join_filter' ), 10 );
			remove_filter( 'posts_where', array( $wpml_query_filter, 'posts_where_filter' ), 10 );
		}
		return $args;
	}

	/**
	 * When lang=all don't filter terms by language
	 *
	 * @param array $args
	 * @param WP_REST_Request $request
	 *
	 * @return array
	 * @throws WC_REST_Exception
	 */
	public function filter_terms_query( $args, $request ) {

		$data = $request->get_params();

		if ( isset( $data['lang'] ) ) {

			$active_languages = $this->sitepress->get_active_languages();

			if ( $data['lang'] === 'all' ) {
				remove_filter( 'terms_clauses', array( $this->sitepress, 'terms_clauses' ), 10, 4 );
				remove_filter( 'get_term', array( $this->sitepress, 'get_term_adjust_id' ), 1, 1 );
			} elseif ( ! isset( $active_languages[ $data['lang'] ] ) ) {
				throw new WC_REST_Exception(
					'404',
					sprintf( __( 'Invalid language parameter: %s', 'woocommerce-multilingual' ),
					$data['lang'] ),
					'404'
				);
			}

		}

		return $args;
	}

	/**
	 * @param WP_Query $wp_query
	 */
	public function auto_adjust_included_ids( $wp_query ){
		$lang = $wp_query->get('lang');
		$include = $wp_query->get('post__in');
		if( empty( $lang ) && !empty( $include ) ){
			$filtered_include = array();
			foreach( $include as $id ){
				$filtered_include[] = apply_filters( 'translate_object_id', $id, get_post_type($id), true );
			}
			$wp_query->set( 'post__in' , $filtered_include );
		}
	}

	/**
	 * Appends the language and translation information to the get_product response
	 *
	 * @param $product_data
	 *
	 * @return WP_REST_Response
	 */
	public function append_product_language_and_translations( $product_data ){

		$product_data->data['translations'] = array();

		$trid = $this->sitepress->get_element_trid( $product_data->data['id'], 'post_product' );

		if( $trid ) {
			$translations = $this->sitepress->get_element_translations( $trid, 'post_product' );
			foreach ( $translations as $translation ) {
				if ( $translation->element_id == $product_data->data['id'] ) {
					$product_language = $translation->language_code;
				} else {
					$product_data->data['translations'][ $translation->language_code ] = $translation->element_id;
				}
			}

			$product_data->data['lang'] = $product_language;
		}

		return $product_data;
	}

	/**
	 * Appends the secondary prices information to the get_product response
	 *
	 * @param $product_data
	 *
	 * @return WP_REST_Response
	 */
	public function append_product_secondary_prices( $product_data ){

		if( !empty($this->woocommerce_wpml->multi_currency) && !empty($this->woocommerce_wpml->settings['currencies_order']) ){

			$product_data->data['multi-currency-prices'] = array();

			$custom_prices_on = get_post_meta( $product_data->data['id'], '_wcml_custom_prices_status', true);

			foreach( $this->woocommerce_wpml->settings['currencies_order'] as $currency ){

				if( $currency != get_option('woocommerce_currency') ){

					if( $custom_prices_on ){

						$custom_prices = (array) $this->woocommerce_wpml->multi_currency->custom_prices->get_product_custom_prices( $product_data->data['id'], $currency );
						foreach( $custom_prices as $key => $price){
							$product_data->data['multi-currency-prices'][$currency][ preg_replace('#^_#', '', $key) ] = $price;

						}

					} else {
						$product_data->data['multi-currency-prices'][$currency]['regular_price'] =
							$this->woocommerce_wpml->multi_currency->prices->raw_price_filter( $product_data->data['regular_price'], $currency );
						if( !empty($product_data->data['sale_price']) ){
							$product_data->data['multi-currency-prices'][$currency]['sale_price'] =
								$this->woocommerce_wpml->multi_currency->prices->raw_price_filter( $product_data->data['sale_price'], $currency );
						}
					}

				}

			}

		}

		return $product_data;
	}

	/**
	 * Sets the product information according to the provided language
	 *
	 * @param object $product
	 * @param WP_REST_Request $request
	 *
	 * @throws WC_REST_Exception
	 *
	 */
	public function set_product_language( $product, $request ){

		$data = $request->get_params();

		if( isset( $data['lang'] )){
			$active_languages = $this->sitepress->get_active_languages();
			if( !isset( $active_languages[$data['lang']] ) ){
				throw new WC_REST_Exception( '404', sprintf( __( 'Invalid language parameter: %s', 'woocommerce-multilingual' ), $data['lang'] ), '404' );
			}
			if( isset( $data['translation_of'] ) ){
				$trid = $this->sitepress->get_element_trid( $data['translation_of'], 'post_product' );
				if( empty($trid) ){
					throw new WC_REST_Exception( '404', sprintf( __( 'Source product id not found: %s', 'woocommerce-multilingual' ), $data['translation_of'] ), '404' );
				}
			}else{
				$trid = null;
			}

			$this->sitepress->set_element_language_details( $product->get_id(), 'post_product', $trid, $data['lang'] );
			wpml_tm_save_post( $product->get_id(), get_post( $product->get_id() ), ICL_TM_COMPLETE );
		}else{
			if( isset( $data['translation_of'] ) ){
				throw new WC_REST_Exception( '404', __( 'Using "translation_of" requires providing a "lang" parameter too', 'woocommerce-multilingual' ), '404' );
			}
		}

	}

	/**
	 * Sets custom prices in secondary currencies for products
	 *
	 * @param object $product
	 * @param WP_REST_Request $request
	 *
	 * @throws WC_API_Exception
	 *
	 */
	public function set_product_custom_prices( $product, $request ){

		$data = $request->get_params();

		if( !empty( $this->woocommerce_wpml->multi_currency )  ){

			if( !empty( $data['custom_prices'] ) ){

				$original_post_id = $this->sitepress->get_original_element_id_filter('', $product->get_id(), 'post_product' );

				update_post_meta( $original_post_id, '_wcml_custom_prices_status', 1);

				foreach( $data['custom_prices'] as $currency => $prices ){

					$prices_uscore = array();
					foreach( $prices as $k => $p){
						$prices_uscore['_' . $k] = $p;
					}
					$this->woocommerce_wpml->multi_currency->custom_prices->update_custom_prices( $original_post_id, $prices_uscore, $currency );

				}

			}
		}

	}

	/**
	 * @param WC_Product $product
	 */
	public function copy_custom_fields_from_original( WC_Product $product ){
		$original_post_id = $this->sitepress->get_original_element_id_filter('', $product->get_id(), 'post_product' );

		if( $original_post_id !== $product->get_id() ){
			$this->sitepress->copy_custom_fields( $original_post_id, $product->get_id() );
		}
	}

	/**
	 * @param WP_REST_Response $response
	 * @param mixed $object
	 * @param WP_REST_Request $request
	 *
	 * Copy custom fields explicitly
	 *
	 * @return WP_REST_Response
	 */
	public function copy_product_custom_fields($response, $object, $request){
		global $wpdb;
		global $wpml_post_translations;

		$data = $request->get_params();

		if( isset( $data['id'] ) ) {
			$translations = $wpml_post_translations->get_element_translations( $data['id'], false, true );
			foreach ( $translations as $translation_id ) {
				$this->sitepress->copy_custom_fields( $data['id'], $translation_id );
			}
		}

		return $response;
	}

	public function filter_orders_by_language( $args, $request ){

		$lang = $request->get_param( 'lang' );

		if( !is_null( $lang ) && $lang !== 'all' ){

			$args['meta_query'][] = array(
				'key'   => 'wpml_language',
				'value' => strval( $lang )
			);

		}

		return $args;
	}

	/**
	 * Filters the items of an order according to a given languages
	 *
	 * @param WP_REST_Response $response
	 * @param WC_Order $order
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */

	public function filter_order_items_by_language( $response, $order, $request ){

		$lang = get_query_var('lang');
		$order_lang = get_post_meta( $order->get_id(), 'wpml_language', true );

		if( $order_lang != $lang || 1 ){

			foreach( $response->data['line_items'] as $k => $item ){

				global $wpdb;
				$sql = "SELECT meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE order_item_id=%d AND meta_key='_product_id'";
				$product_id = $wpdb->get_var( $wpdb->prepare( $sql, $item['id']) );

				if( $product_id ){

					$translated_product_id = apply_filters( 'translate_object_id', $product_id, 'product', true, $lang );

					if( $translated_product_id ){
						$translated_product = get_post( $translated_product_id );
						$response->data['line_items'][$k]['product_id'] = $translated_product_id;
						if( $translated_product->post_type == 'product_variation' ){
							$post_parent = get_post( $translated_product->post_parent );
							$post_name = $post_parent->post_title;
						} else {
							$post_name = $translated_product->post_title;
						}
						$response->data['line_items'][$k]['name'] = $post_name;
					}

				}

			}

		}

		return $response;
	}

	/**
	 * Sets the language for a new order
	 *
	 * @param WC_Order $order
	 * @param WP_REST_Request $request
	 *
	 * @throws WC_REST_Exception
	 */
	public function set_order_language( $order, $request ){

		$data = $request->get_params();
		if( isset( $data['lang'] ) ){
			$order_id = $order->get_id();
			$active_languages = $this->sitepress->get_active_languages();
			if( !isset( $active_languages[$data['lang']] ) ){
				throw new WC_REST_Exception( '404', sprintf( __( 'Invalid language parameter: %s' ), $data['lang'] ), '404' );
			}

			update_post_meta( $order_id, 'wpml_language', $data['lang'] );

		}

	}

	/**
	 * @param WC_Order $order
	 * @param WP_REST_Request $request
	 *
	 * @throws WC_REST_Exception
	 */
	public function set_order_currency( $order, $request ) {
		$data = $request->get_params();
		if ( isset( $data['currency'] ) ) {
			$order_id   = $order->get_id();
			$currencies = get_woocommerce_currencies();
			if ( ! isset( $currencies[ $data['currency'] ] ) ) {
				throw new WC_REST_Exception( '404', sprintf( __( 'Invalid currency parameter: %s' ), $data['currency'] ), '404' );
			}
			update_post_meta( $order_id, '_order_currency', $data['currency'] );
		}
	}

}