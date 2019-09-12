<?php

class WCML_Coupons {

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var Sitepress */
	private $sitepress;

	public function __construct( woocommerce_wpml $woocommerce_wpml, SitePress $sitepress ) {
		$this->woocommerce_wpml = $woocommerce_wpml;
		$this->sitepress        = $sitepress;
	}

	public function add_hooks() {

		add_action( 'woocommerce_coupon_loaded', array( $this, 'wcml_coupon_loaded' ) );
		add_action( 'admin_init', array( $this, 'icl_adjust_terms_filtering' ) );

		add_filter( 'woocommerce_coupon_is_valid_for_product', array( $this, 'is_valid_for_product' ), 10, 4 );
	}

	private function apply_translated_product_ids( array $product_ids, WC_Coupon $coupon, $coupon_setter_method ) {
		if ( ! method_exists( $coupon, $coupon_setter_method ) ) {
			return;
		}

		$translated_product_ids = wpml_collect( $product_ids )
			->map( function ( $product_id ) {
				return $this->sitepress->get_object_id( $product_id, get_post_type( $product_id ) );
			} )
			->filter();

		if ( $translated_product_ids->count() ) {
			$coupon->$coupon_setter_method( $translated_product_ids->toArray() );
		}
	}

	private function apply_translated_product_category_ids( array $category_ids, WC_Coupon $coupon, $coupon_setter_method ) {
		if ( ! method_exists( $coupon, $coupon_setter_method ) ) {
			return;
		}

		$translated_category_ids = wpml_collect( $category_ids )
			->map( function ( $category_id ) {
				return $this->sitepress->get_object_id( $category_id, 'product_cat' );
			} )
			->filter();

		if ( $translated_category_ids->count() ) {
			$coupon->$coupon_setter_method( $translated_category_ids->toArray() );
		}
	}

	/**
	 * @param WC_Coupon $coupon Coupon object.
	 */
	public function wcml_coupon_loaded( WC_Coupon $coupon ) {

		$this->apply_translated_product_ids( $coupon->get_product_ids(), $coupon, 'set_product_ids' );
		$this->apply_translated_product_ids( $coupon->get_excluded_product_ids(), $coupon, 'set_excluded_product_ids' );

		$this->apply_translated_product_category_ids( $coupon->get_product_categories(), $coupon, 'set_product_categories' );
		$this->apply_translated_product_category_ids( $coupon->get_excluded_product_categories(), $coupon, 'set_excluded_product_categories' );
	}

	public function icl_adjust_terms_filtering() {
		if ( is_admin() && isset( $_GET['action'] ) && $_GET['action'] == 'woocommerce_json_search_products_and_variations' ) {
			global $icl_adjust_id_url_filter_off;
			$icl_adjust_id_url_filter_off = true;
		}
	}

	/**
	 * @param bool $valid
	 * @param WC_Product $product
	 * @param WC_Coupon $object
	 * @param array $values
	 *
	 * @return bool
	 */
	public function is_valid_for_product( $valid, $product, $object, $values ) {

		$product_id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
		$translated_product_id = apply_filters( 'translate_object_id', $product_id, 'product', false, $this->sitepress->get_current_language() );

		if ( $translated_product_id && $product_id !== $translated_product_id ) {

			remove_filter( 'woocommerce_coupon_is_valid_for_product', array( $this, 'is_valid_for_product' ), 10 );

			$valid = $object->is_valid_for_product( wc_get_product( $translated_product_id ), $values );

			add_filter( 'woocommerce_coupon_is_valid_for_product', array( $this, 'is_valid_for_product' ), 10, 4 );

		}

		return $valid;

	}

}
