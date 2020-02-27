<?php

namespace WCML\Block\Convert\Converter;

class ProductsByAttributes extends \WPML\PB\Gutenberg\ConvertIdsInBlock\Base {

	public function convert( array $block ) {
		if ( ! isset( $block['attrs']['attributes'] ) ) {
			return $block;
		}

		foreach ( $block['attrs']['attributes'] as $key => $attribute ) {
			if ( ! isset( $attribute['id'], $attribute['attr_slug'] ) ) {
				continue;
			}

			$block['attrs']['attributes'][ $key ]['id'] = self::convertIds( $attribute['id'], $attribute['attr_slug'], 'taxonomy' );
		}

		return $block;
	}
}
