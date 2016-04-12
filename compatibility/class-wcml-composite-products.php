<?php


class WCML_Composite_Products extends WCML_Compatibility_Helper{

	private $tp;

	function __construct() {
		add_filter( 'woocommerce_composite_component_default_option', array($this, 'woocommerce_composite_component_default_option'), 10, 3 );
		add_filter( 'wcml_cart_contents', array($this, 'wpml_composites_compat'), 11, 4 );
		add_filter( 'woocommerce_composite_component_options_query_args', array($this, 'wpml_composites_transients_cache_per_language'), 10, 3 );
		add_action( 'updated_post_meta', array( $this, 'sync_composite_data_across_translations'), 10, 4 );

		if( is_admin() ){
			add_filter( 'wcml_gui_additional_box', array( $this, 'custom_box_html'), 10, 3 );
			add_action('wcml_extra_titles',array($this,'product_editor_title'),10,1);
			add_action('wcml_update_extra_fields',array($this,'components_update'),10,2);

			$this->tp = new WPML_Element_Translation_Package();

			add_filter( 'wpml_tm_translation_job_data', array( $this, 'append_composite_data_translation_package' ), 10, 2 );
			add_action( 'wpml_translation_job_saved',   array( $this, 'save_composite_data_translation' ), 10, 3 );

		}
	}

	function woocommerce_composite_component_default_option($selected_value, $component_id, $object) {

		if( !empty( $selected_value ) )
			$selected_value = apply_filters( 'wpml_object_id', $selected_value, 'product', true );


		return $selected_value;
	}
	
	function wpml_composites_compat( $new_cart_data, $cart_contents, $key, $new_key ) {

		if ( isset( $cart_contents[ $key ][ 'composite_children' ] ) || isset( $cart_contents[ $key ][ 'composite_parent' ] ) ) {

			$buff = $new_cart_data[ $new_key ];

			unset( $new_cart_data[ $new_key ] );

			$new_cart_data[ $key ] = $buff;
		}

		return $new_cart_data;
	}

	function wpml_composites_transients_cache_per_language( $args, $query_args, $component_data ) {

		$args[ 'wpml_lang' ] = apply_filters( 'wpml_current_language', NULL );

		return $args;
	}

	function sync_composite_data_across_translations( $meta_id, $post_id, $meta_key, $composite_data ){


		if( $meta_key != '_bto_data' )
			return false;


		global $sitepress, $woocommerce_wpml;

		$post = get_post( $post_id );

		// skip auto-drafts // skip autosave
		if ( $post->post_status == 'auto-draft' || isset( $_POST[ 'autosave' ] ) ) {
			return;
		}

		if( $post->post_type == 'product' ) {

			if( $this->get_product_type( $post_id ) == 'composite' ) {

				remove_action( 'updated_post_meta', array( $this, 'sync_composite_data_across_translations'), 10, 4 );

				if ( $woocommerce_wpml->products->is_original_product( $post_id ) ) {

					$original_product_id = $post_id;

				} else {

					$original_product_language = $woocommerce_wpml->products->get_original_product_language( $post_id );
					$original_product_id = apply_filters( 'translate_object_id', $post_id, 'product', true, $original_product_language );

				}

				$product = new WC_Product_Composite( $original_product_id );

				$product_trid = $sitepress->get_element_trid( $original_product_id, 'post_product' );
				$product_translations = $sitepress->get_element_translations( $product_trid, 'post_product' );

				foreach ( $product_translations as $product_translation ) {

					if ( empty($product_translation->original) ) {

						$translated_product = new WC_Product_Composite( $product_translation->element_id );
						$translated_composite_data = $translated_product->get_composite_data();

						foreach ( $composite_data as $component_id => $component ) {

							if( isset( $translated_composite_data[$component_id]['title'] ) ){
								$composite_data[$component_id]['title'] =  $translated_composite_data[$component_id]['title'];
							}

							if( isset( $translated_composite_data[$component_id]['description'] ) ){
								$composite_data[$component_id]['description'] =  $translated_composite_data[$component_id]['description'];
							}

							if ( $component['query_type'] == 'product_ids' ) {

								foreach ( $component['assigned_ids'] as $idx => $assigned_id ) {
									$composite_data[$component_id]['assigned_ids'][$idx] =
										apply_filters( 'translate_object_id', $assigned_id, 'product', true, $product_translation->language_code );
								}

							} elseif( $component['query_type'] == 'category_ids' ){

								foreach ( $component['assigned_category_ids'] as $idx => $assigned_id ) {
									$composite_data[$component_id]['assigned_category_ids'][$idx] =
										apply_filters( 'translate_object_id', $assigned_id, 'product_cat', true, $product_translation->language_code );

								}

							}

						}

						update_post_meta( $product_translation->element_id, '_bto_data', $composite_data );

					}

				}

				add_action( 'updated_post_meta', array( $this, 'sync_composite_data_across_translations'), 10, 4 );

			}

		}

	}

	function custom_box_html($product_id,$lang, $is_duplicate_product = false){
		global $woocommerce_wpml, $sitepress;

		$original_product_language = $woocommerce_wpml->products->get_original_product_language( $product_id );

		if( $this->get_product_type( $product_id ) == 'composite' ){

			$product = new WC_Product_Composite( $product_id );
			$composite_data = $product->get_composite_data();

			if( $original_product_language != $lang ){
				$product_trid = $sitepress->get_element_trid( $product_id, 'post_product' );
				$product_translations = $sitepress->get_element_translations( $product_trid, 'post_product' );
				if( isset($product_translations[$lang]) ){
					$translated_product = new WC_Product_Composite( $product_translations[$lang]->element_id );
					$translated_composite_data = $translated_product->get_composite_data();
				}

				foreach( $composite_data as $component_id => $component ){

					$template_data['wc_composite_components']['components'][$component_id]['title'] =
						isset( $translated_composite_data[$component_id]['title'] ) ? $translated_composite_data[$component_id]['title'] : '';

					$template_data['wc_composite_components']['components'][$component_id]['description'] =
						isset( $translated_composite_data[$component_id]['description'] ) ? $translated_composite_data[$component_id]['description'] : '';

				}

			}else{

				foreach( $composite_data as $component_id => $component ) {

					$template_data['wc_composite_components']['components'][$component_id]['title'] =
						isset( $composite_data[$component_id]['title'] ) ? $composite_data[$component_id]['title'] : '';

					$template_data['wc_composite_components']['components'][$component_id]['description'] =
						isset( $composite_data[$component_id]['description'] ) ? $composite_data[$component_id]['description'] : '';

				}

			}

			$template_data['wc_composite_components']['_is_original'] = $original_product_language == $lang;

			include WCML_PLUGIN_PATH . '/compatibility/templates/woocommerce-composite-products.php';
		}

	}

	function product_editor_title( $product_id ){

		if( $this->get_product_type( $product_id ) == 'composite' ) {
			printf( '<th scope="col">%s</h>', __( 'Components', 'woocommerce-multilingual' ) );
		}

	}

    function components_update( $product_id, $data ){

		$this->sync_composite_data_across_translations( $product_id );

		$product = new WC_Product_Composite( $product_id );

		$composite_data = $product->get_composite_data();

		if(!empty($data['wc_composite_component'])){
			foreach($data['wc_composite_component'] as $component_id => $component){

				if(!empty($component['title'])){
					$composite_data[$component_id]['title'] = $component['title'];
				}

				if(!empty($component['description'])) {
					$composite_data[$component_id]['description'] = $component['description'];
				}


			}

			update_post_meta( $product_id, '_bto_data', $composite_data );

		}
	}

	function append_composite_data_translation_package( $package, $post ){

		if( $post->post_type == 'product' ) {

			$composite_data = get_post_meta( $post->ID, '_bto_data', true );

			if( $composite_data ){

				$fields = array( 'title', 'description' );

				foreach( $composite_data as $component_id => $component ){

					foreach( $fields as $field ) {
						if ( !empty($component[$field]) ) {

							$package['contents']['wc_composite:' . $component_id . ':' . $field] = array(
								'translate' => 1,
								'data' => $this->tp->encode_field_data( $component[$field], 'base64' ),
								'format' => 'base64'
							);

						}
					}

				}

			}

		}

		return $package;

	}

	function save_composite_data_translation( $post_id, $data, $job ){


		$translated_composite_data = array();
		foreach( $data as $value){

			if( preg_match( '/wc_composite:([0-9]+):(.+)/', $value['field_type'], $matches ) ){

				$component_id = $matches[1];
				$field        = $matches[2];

				$translated_composite_data[$component_id][$field] = $value['data'];

			}

		}

		if( $translated_composite_data ){

			$composite_data = get_post_meta( $job->original_doc_id, '_bto_data', true );


			foreach ( $composite_data as $component_id => $component ) {

				if( isset( $translated_composite_data[$component_id]['title'] ) ){
					$composite_data[$component_id]['title'] =  $translated_composite_data[$component_id]['title'];
				}

				if( isset( $translated_composite_data[$component_id]['description'] ) ){
					$composite_data[$component_id]['description'] =  $translated_composite_data[$component_id]['description'];
				}

				if ( $component['query_type'] == 'product_ids' ) {

					foreach ( $component['assigned_ids'] as $idx => $assigned_id ) {
						$composite_data[$component_id]['assigned_ids'][$idx] =
							apply_filters( 'translate_object_id', $assigned_id, 'product', true, $job->language_code );
					}

				} elseif( $component['query_type'] == 'category_ids' ){

					foreach ( $component['assigned_category_ids'] as $idx => $assigned_id ) {
						$composite_data[$component_id]['assigned_category_ids'][$idx] =
							apply_filters( 'translate_object_id', $assigned_id, 'product_cat', true, $job->language_code );

					}

				}

			}

		}

		update_post_meta( $post_id, '_bto_data', $composite_data );

	}
}
