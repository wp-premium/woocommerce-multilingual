<?php
/*
 *  Woo Variations table Compatibility class
 */

class WCML_Woo_Var_Table {

	/**
	 * @var string
	 */
	private $current_language;

	/**
	 * @param string $current_language
	 */
	public function __construct( $current_language ) {
		$this->current_language = $current_language;
	}

	public function add_hooks() {

		add_filter( 'vartable_add_to_cart_product_id', [ $this, 'filter_add_to_cart_product_id' ] );
	}

	public function filter_add_to_cart_product_id( $product_id ) {

		$product_id = apply_filters( 'translate_object_id', $product_id, get_post_type( $product_id ), true, $this->current_language );

		return $product_id;
	}

}
