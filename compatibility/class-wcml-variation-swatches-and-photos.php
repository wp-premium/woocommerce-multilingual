<?php

/**
 * Compatibility class for Variation Swatches and Photos plugin
 */
class WCML_Variation_Swatches_And_Photos {

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;

	public function __construct( woocommerce_wpml $woocommerce_wpml ) {
		$this->woocommerce_wpml = $woocommerce_wpml;
	}

	public function add_hooks() {
		add_action( 'wcml_after_sync_product_data', [ $this, 'sync_variation_swatches_and_photos' ], 10, 3 );
	}

	/**
	 * Synchronize Variation Swatches and Photos
	 *
	 * @param int    $original_product_id Original product ID.
	 * @param int    $translated_product_id Translated product ID.
	 * @param string $language
	 */
	public function sync_variation_swatches_and_photos( $original_product_id, $translated_product_id, $language ) {

		$swatch_options            = maybe_unserialize( get_post_meta( $original_product_id, '_swatch_type_options', true ) );
		$translated_swatch_options = $swatch_options;

		if ( $swatch_options ) {
			$original_product_attributes = $this->woocommerce_wpml->attributes->get_product_attributes( $original_product_id );

			wpml_collect( $original_product_attributes )->each(
				function ( $attribute, $attribute_key ) use ( $swatch_options, &$translated_swatch_options, $language, $original_product_id, $translated_product_id ) {
					$attribute_name_hash = md5( sanitize_title( $attribute['name'] ) );
					if ( $this->woocommerce_wpml->attributes->is_a_taxonomy( $attribute ) ) {
						$translated_swatch_options = $this->translate_taxonomy_attributes( $attribute_key, $attribute_name_hash, $swatch_options, $translated_swatch_options, $language );
					} else {
						$translated_swatch_options = $this->translate_custom_attributes( $attribute_key, $attribute, $attribute_name_hash, $swatch_options, $translated_swatch_options, $original_product_id, $translated_product_id );
					}
				}
			);

		}

		update_post_meta( $translated_product_id, '_swatch_type_options', $translated_swatch_options );
	}

	private function translate_taxonomy_attributes( $taxonomy, $attribute_name_hash, $swatch_options, $translated_swatch_options, $language ) {
		$attribute_terms = get_terms( [ 'taxonomy' => $taxonomy ] );

		wpml_collect( $attribute_terms )->each(
			function ( $term ) use ( $taxonomy, $attribute_name_hash, $swatch_options, &$translated_swatch_options, $language ) {
				$attribute_term_slug_md5   = md5( $term->slug );
				$translated_swatch_options = $this->translate_taxonomy_term( $term, $attribute_term_slug_md5, $taxonomy, $attribute_name_hash, $swatch_options, $translated_swatch_options, $language );
			}
		);

		return $translated_swatch_options;
	}

	private function translate_taxonomy_term( $term, $attribute_term_slug_md5, $taxonomy, $attribute_name_hash, $swatch_options, $translated_swatch_options, $language ) {
		wpml_collect( $swatch_options[ $attribute_name_hash ]['attributes'] )->each(
			function ( $swatch_attribute, $swatch_attribute_key ) use ( $term, $attribute_term_slug_md5, $taxonomy, $attribute_name_hash, &$translated_swatch_options, $language ) {
				if ( $attribute_term_slug_md5 === $swatch_attribute_key ) {
					$translated_term = $this->woocommerce_wpml->terms->wcml_get_translated_term( $term->term_id, $taxonomy, $language );
					$translated_swatch_options[ $attribute_name_hash ]['attributes'][ md5( $translated_term->slug ) ] = $swatch_attribute;
					unset( $translated_swatch_options[ $attribute_name_hash ]['attributes'][ $swatch_attribute_key ] );
				}
			}
		);

		return $translated_swatch_options;
	}

	private function translate_custom_attributes( $attribute_key, $attribute, $attribute_name_hash, $swatch_options, $translated_swatch_options, $original_product_id, $translated_product_id ) {
		$attribute_values = explode( '|', $attribute['value'] );

		wpml_collect( $attribute_values )->each(
			function ( $attribute_value ) use ( $attribute_key, $attribute, $attribute_name_hash, $swatch_options, &$translated_swatch_options, $original_product_id, $translated_product_id ) {
				$attribute_value           = trim( $attribute_value, ' ' );
				$attribute_value_md5       = md5( sanitize_title( strtolower( $attribute_value ) ) );
				$translated_swatch_options = $this->translate_custom_attribute_value( $attribute_key, $attribute_value, $attribute_value_md5, $attribute_name_hash, $swatch_options, $translated_swatch_options, $original_product_id, $translated_product_id );
			}
		);

		return $translated_swatch_options;
	}

	private function translate_custom_attribute_value( $attribute_key, $attribute_value, $attribute_value_md5, $attribute_name_hash, $swatch_options, $translated_swatch_options, $original_product_id, $translated_product_id ) {
		wpml_collect( $swatch_options[ $attribute_name_hash ]['attributes'] )->each(
			function ( $swatch_attribute, $swatch_attribute_key ) use ( $attribute_key, $attribute_value, $attribute_value_md5, $attribute_name_hash, &$translated_swatch_options, $original_product_id, $translated_product_id ) {
				if ( $attribute_value_md5 === $swatch_attribute_key ) {
					$translated_attribute_value = $this->woocommerce_wpml->attributes->get_custom_attr_translation( $original_product_id, $translated_product_id, $attribute_key, $attribute_value );
					$translated_swatch_options[ $attribute_name_hash ]['attributes'][ md5( sanitize_title( strtolower( $translated_attribute_value ) ) ) ] = $swatch_attribute;
					unset( $translated_swatch_options[ $attribute_name_hash ]['attributes'][ $swatch_attribute_key ] );
				}
			}
		);

		return $translated_swatch_options;
	}

}
