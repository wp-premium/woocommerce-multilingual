<?php

class WCML_Mix_and_Match_Products {

	public function __construct() {
		add_action( 'updated_post_meta', [ $this, 'sync_mnm_data' ], 10, 4 );
	}

	public function sync_mnm_data( $meta_id, $post_id, $meta_key, $meta_value ) {
		if ( '_mnm_data' !== $meta_key ) {
			return;
		}

		global $sitepress, $woocommerce_wpml;

		$post = get_post( $post_id );

		// Skip auto-drafts, skip autosave.
		if ( 'auto-draft' === $post->post_status || isset( $_POST['autosave'] ) ) {
			return;
		}

		if ( 'product' === $post->post_type ) {
			remove_action( 'updated_post_meta', [ $this, 'sync_mnm_data' ], 10, 4 );

			if ( $woocommerce_wpml->products->is_original_product( $post_id ) ) {
				$original_product_id = $post_id;
			} else {
				$original_product_id = $this->woocommerce_wpml->products->get_original_product_id( $post_id );
			}

			$mnm_data             = maybe_unserialize( get_post_meta( $original_product_id, '_mnm_data', true ) );
			$product_trid         = $sitepress->get_element_trid( $original_product_id, 'post_product' );
			$product_translations = $sitepress->get_element_translations( $product_trid, 'post_product' );

			foreach ( $product_translations as $product_translation ) {
				if ( empty( $product_translation->original ) ) {
					foreach ( $mnm_data as $key => $mnm_element ) {

						$trnsl_prod                = apply_filters( 'translate_object_id', $key, 'product', true, $product_translation->language_code );
						$mnm_element['product_id'] = $trnsl_prod;
						$mnm_data[ $trnsl_prod ]   = $mnm_element;
						unset( $mnm_data[ $key ] );
					}

					update_post_meta( $product_translation->element_id, '_mnm_data', $mnm_data );
				}
			}

			add_action( 'updated_post_meta', [ $this, 'sync_mnm_data' ], 10, 4 );
		}
	}
}
