<?php

namespace WCML\Email\OrderItems;

use IWPML_Backend_Action;
use IWPML_DIC_Action;
use IWPML_Frontend_Action;
use SitePress;
use WCML_Attributes;
use WCML_Terms;

class Hooks implements IWPML_Backend_Action, IWPML_Frontend_Action, IWPML_DIC_Action {

	/** @var SitePress */
	private $sitepress;

	/** @var WCML_Terms */
	private $wcmlTerms;

	/** @var WCML_Attributes */
	private $wcmlAttributes;

	public function __construct( SitePress $sitepress, WCML_Terms $wcmlTerms, WCML_Attributes $wcmlAttributes ) {
		$this->sitepress      = $sitepress;
		$this->wcmlTerms      = $wcmlTerms;
		$this->wcmlAttributes = $wcmlAttributes;
	}

	public function add_hooks() {
		if ( ! $this->isMarkingStatusForShopOrder() ) {
			add_filter( 'woocommerce_order_items_meta_get_formatted', [ $this, 'filterFormattedItems' ], 10, 2 );
		}
	}

	private function isMarkingStatusForShopOrder() {
		return filter_input( INPUT_GET, 'post_type' ) === 'shop_order'
		       && filter_input( INPUT_GET, 'action' ) === 'woocommerce_mark_order_status';
	}

	/**
	 * @param array  $formattedMeta
	 * @param object $object  Should be an instance of \WC_Order_Item_Meta (but not explicitly defined)
	 *
	 * @return array
	 */
	function filterFormattedItems( array $formattedMeta, $object ) {

		if ( isset( $object->product->variation_id ) ) {

			$currentProductVariationId = $this->sitepress->get_object_id( $object->product->variation_id, 'product_variation' );

			if ( ! is_null( $currentProductVariationId ) ) {

				foreach ( $formattedMeta as $key => $formattedItem ) {

					if ( substr( $formattedItem['key'], 0, 3 ) ) {

						$attribute = wc_sanitize_taxonomy_name( $formattedItem['key'] );

						if ( taxonomy_exists( $attribute ) ) {
							$attributeTerm    = get_term_by( 'name', $formattedMeta[ $key ]['value'], $attribute );
							$translatedTermId = $this->sitepress->get_object_id( $attributeTerm->term_id, $attribute );

							if ( $translatedTermId ) {
								$translatedTerm                 = $this->wcmlTerms->wcml_get_term_by_id( $translatedTermId, $attribute );
								$formattedMeta[ $key ]['value'] = $translatedTerm->name;
							}

						} else {
							$customAttrTranslation = $this->wcmlAttributes->get_custom_attribute_translation( $object->product->id, $formattedItem['key'], [ 'is_taxonomy' => false ], $this->sitepress->get_current_language() );

							if ( false !== $customAttrTranslation ) {
								$formattedMeta[ $key ]['label'] = $customAttrTranslation['name'];
							}
						}
					}
				}
			}
		}

		return $formattedMeta;
	}
}
