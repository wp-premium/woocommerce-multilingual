<?php

use WPML\Collect\Support\Collection;

/**
 * Class WCML_WC_Shortcode_Product_Category
 *
 * @since 4.2.2
 */
class WCML_WC_Shortcode_Product_Category {

	/**
	 * @var SitePress
	 */
	private $sitepress;

	/**
	 * WCML_WC_Shortcode_Product_Category constructor.
	 *
	 * @param SitePress $sitepress
	 */
	public function __construct( SitePress $sitepress ) {
		$this->sitepress = $sitepress;
	}

	public function add_hooks() {
		add_filter( 'woocommerce_shortcode_products_query', [ $this, 'translate_category' ], 10, 2 );
	}


	/**
	 * @param array $args
	 * @param array $atts
	 *
	 * @return array
	 */
	public function translate_category( $args, $atts = null ) {

		if ( $this->sitepress->get_default_language() !== $this->sitepress->get_current_language() ) {

			if ( isset( $args['product_cat'] ) ) {
				$args = $this->translate_categories_using_simple_tax_query( $args );
			} elseif ( ! empty( $atts['category'] ) && isset( $args['tax_query'] ) ) {
				$getProductCategoryObject = function ( $slugOrId ) {
					if ( is_numeric( $slugOrId ) ) {
						return get_term( $slugOrId, 'product_cat' );
					}

					return get_term_by( 'slug', $slugOrId, 'product_cat' );
				};

				$categories = wpml_collect( explode( ',', $atts['category'] ) )
					->map(
						function( $slugOrId ) {
								return trim( $slugOrId ); }
					)
					->filter()
					->map( $getProductCategoryObject );

				$args = $this->replace_category_in_query_arguments( $args, $categories );
			}
		}

		return $args;
	}

	/**
	 * @param array      $args
	 * @param Collection $terms
	 *
	 * @return array
	 */
	private function replace_category_in_query_arguments( array $args, Collection $terms ) {

		foreach ( $args['tax_query'] as $i => $tax_query ) {
			$args['tax_query'][ $i ] = [];
			if ( ! is_int( key( $tax_query ) ) ) {
				$tax_query = [ $tax_query ];
			}
			foreach ( $tax_query as $j => $condition ) {
				if ( 'product_cat' === $condition['taxonomy'] ) {
					$condition['terms'] = $terms->pluck( $condition['field'] )->toArray();
				}
				$args['tax_query'][ $i ][] = $condition;
			}
		}

		return $args;
	}

	/**
	 * @param $args array
	 *
	 * @return array
	 */
	public function translate_categories_using_simple_tax_query( $args ) {

		$category_slugs = array_map( 'trim', explode( ',', $args['product_cat'] ) );

		$filter_exists         = remove_filter( 'terms_clauses', [ $this->sitepress, 'terms_clauses' ], 10 );
		$categories_translated = get_terms(
			[
				'slug'     => $category_slugs,
				'taxonomy' => 'product_cat',
			]
		);
		if ( $filter_exists ) {
			add_filter( 'terms_clauses', [ $this->sitepress, 'terms_clauses' ], 10, 3 );
		}

		$category_slugs_translated = [];
		foreach ( $categories_translated as $category_translated ) {
			$category_slugs_translated[] = $category_translated->slug;
		}
		$args['product_cat'] = implode( ',', $category_slugs_translated );

		return $args;
	}


}
