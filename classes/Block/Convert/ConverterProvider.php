<?php

namespace WCML\Block\Convert;

use WCML\Block\Convert\Converter\ProductsByAttributes;
use WPML\PB\Gutenberg\ConvertIdsInBlock\Base;
use WPML\PB\Gutenberg\ConvertIdsInBlock\Composite;
use WPML\PB\Gutenberg\ConvertIdsInBlock\NullConvert;
use WPML\PB\Gutenberg\ConvertIdsInBlock\BlockAttributes;
use WPML\PB\Gutenberg\ConvertIdsInBlock\TagAttributes;

class ConverterProvider {

	/**
	 * @param string $blockName
	 *
	 * @return \WPML\PB\Gutenberg\ConvertIdsInBlock\Base
	 */
	public static function get( $blockName ) {
		switch ( $blockName ) {
			case 'woocommerce/product-category':
				$converter = new BlockAttributes(
					[
						[
							'name' => 'categories',
							'slug' => 'product_cat',
							'type' => 'taxonomy',
						],
					]
				);
				break;

			case 'woocommerce/featured-category':
				$converter = new BlockAttributes(
					[
						[
							'name' => 'categoryId',
							'slug' => 'product_cat',
							'type' => 'taxonomy',
						],
					]
				);
				break;

			case 'woocommerce/featured-product':
				$converter = new BlockAttributes(
					[
						[
							'name' => 'productId',
							'slug' => 'product',
							'type' => 'post'
						],
					]
				);
				break;

			case 'woocommerce/handpicked-products':
				$converter = new BlockAttributes(
					[
						[
							'name' => 'products',
							'slug' => 'product',
							'type' => 'post',
						],
					]
				);
				break;

			case 'woocommerce/product-tag':
				$converter = new BlockAttributes(
					[
						[
							'name' => 'tags',
							'slug' => 'product_tag',
							'type' => 'taxonomy',
						],
					]
				);
				break;

			case 'woocommerce/reviews-by-product':
				$converter = new Composite(
					[
						new BlockAttributes(
							[
								[
									'name' => 'productId',
									'slug' => 'product',
									'type' => 'post',
								],
							]
						),
						new TagAttributes(
							[
								[
									'xpath' => '//*[contains(@class, "wp-block-woocommerce-reviews-by-product")]/@data-product-id',
									'slug'  => 'product',
									'type'  => 'post',
								],
							]
						),
					]
				);
				break;

			case 'woocommerce/reviews-by-category':
				$converter = new Composite(
					[
						new BlockAttributes(
							[
								[
									'name' => 'categoryIds',
									'slug' => 'product_cat',
									'type' => 'taxonomy',
								],
							]
						),
						new TagAttributes(
							[
								[
									'xpath' => '//*[contains(@class, "wp-block-woocommerce-reviews-by-category")]/@data-category-ids',
									'slug'  => 'product_cat',
									'type'  => 'taxonomy',
								],
							]
						),
					]
				);
				break;

			case 'woocommerce/products-by-attribute':
				$converter = new ProductsByAttributes();
				break;

			default:
				$converter = new Base();
		}

		return $converter;
	}
}
