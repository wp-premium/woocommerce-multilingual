<?php


class WCML_Composite_Products extends WCML_Compatibility_Helper{

	private $tp;

	function __construct() {
		add_filter( 'woocommerce_composite_component_default_option', array($this, 'woocommerce_composite_component_default_option'), 10, 3 );
		add_filter( 'wcml_cart_contents', array($this, 'wpml_composites_compat'), 11, 4 );
		add_filter( 'woocommerce_composite_component_options_query_args', array($this, 'wpml_composites_transients_cache_per_language'), 10, 3 );
		add_action( 'wcml_before_sync_product', array( $this, 'sync_composite_data_across_translations'), 10, 2 );

		if( is_admin() ){		

			add_action( 'wcml_gui_additional_box_html', array( $this, 'custom_box_html' ), 10, 3 );
			add_filter( 'wcml_gui_additional_box_data', array( $this, 'custom_box_html_data' ), 10, 4 );
			add_action( 'wcml_update_extra_fields', array( $this, 'components_update' ), 10, 3 );
			add_filter( 'woocommerce_json_search_found_products', array( $this, 'woocommerce_json_search_found_products' ) );

			$this->tp = new WPML_Element_Translation_Package();

			add_filter( 'wpml_tm_translation_job_data', array( $this, 'append_composite_data_translation_package' ), 10, 2 );
			add_action( 'wpml_translation_job_saved',   array( $this, 'save_composite_data_translation' ), 10, 3 );
			//lock fields on translations pages
			add_filter( 'wcml_js_lock_fields_input_names', array( $this, 'wcml_js_lock_fields_input_names' ) );
			add_filter( 'wcml_js_lock_fields_ids', array( $this, 'wcml_js_lock_fields_ids' ) );
			add_filter( 'wcml_after_load_lock_fields_js', array( $this, 'localize_lock_fields_js' ) );
			add_action( 'init', array( $this, 'load_assets' ) );
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

	function sync_composite_data_across_translations(  $original_product_id, $current_product_id ){
		global $woocommerce_wpml, $sitepress;

		if( $this->get_product_type( $original_product_id ) == 'composite' ){

			$product = new WC_Product_Composite( $original_product_id );
			$composite_data = $product->get_composite_data();

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
		}

	}

	function custom_box_html( $obj, $product_id, $data ){

		if( $this->get_product_type( $product_id ) == 'composite' ){

			$product = new WC_Product_Composite( $product_id );
			$composite_data = $product->get_composite_data();

			$composite_section = new WPML_Editor_UI_Field_Section( __( 'Composite Products', 'woocommerce-multilingual' ) );
			end( $composite_data );
			$last_key = key( $composite_data );
			$divider = true;
			foreach( $composite_data as $component_id => $component ) {
				if( $component_id ==  $last_key ){
					$divider = false;
				}
				$group = new WPML_Editor_UI_Field_Group( '', $divider );
				$composite_field = new WPML_Editor_UI_Single_Line_Field( 'composite_'.$component_id.'_title', __( 'Name', 'woocommerce-multilingual' ), $data, false );
				$group->add_field( $composite_field );
				$composite_field = new WPML_Editor_UI_Single_Line_Field( 'composite_'.$component_id.'_description' , __( 'Description', 'woocommerce-multilingual' ), $data, false );
				$group->add_field( $composite_field );
				$composite_section->add_field( $group );

			}

			if( $composite_data ){
				$obj->add_field( $composite_section );
			}

		}

	}

	function custom_box_html_data( $data, $product_id, $translation, $lang ){

		if( $this->get_product_type( $product_id ) == 'composite' ){

			$product = new WC_Product_Composite( $product_id );
			$composite_data = $product->get_composite_data();

			foreach( $composite_data as $component_id => $component ) {

				$data['composite_'.$component_id.'_title'] = array( 'original' =>
					isset( $composite_data[$component_id]['title'] ) ? $composite_data[$component_id]['title'] : '' );

				$data['composite_'.$component_id.'_description'] = array( 'original' =>
					isset( $composite_data[$component_id]['description'] ) ? $composite_data[$component_id]['description'] : '' );

			}

			if( $translation ){
				$translated_product = new WC_Product_Composite( $translation->ID );
				$translated_composite_data = $translated_product->get_composite_data();

				foreach( $composite_data as $component_id => $component ){

					$data['composite_'.$component_id.'_title'][ 'translation' ] =
						isset( $translated_composite_data[$component_id]['title'] ) ? $translated_composite_data[$component_id]['title'] : '';

					$data['composite_'.$component_id.'_description'][ 'translation' ] =
						isset( $translated_composite_data[$component_id]['description'] ) ? $translated_composite_data[$component_id]['description'] : '';

				}
			}

		}

		return $data;
	}

    function components_update( $original_product_id, $product_id, $data ){

		$product = new WC_Product_Composite( $original_product_id );

		$composite_data = $product->get_composite_data();

		foreach( $composite_data as $component_id => $component ) {

			if(!empty($data[ md5( 'composite_'.$component_id.'_title' ) ] ) ){
				$composite_data[$component_id]['title'] = $data[ md5( 'composite_'.$component_id.'_title' ) ];
			}

			if(!empty($data[ md5( 'composite_'.$component_id.'_description' ) ])) {
				$composite_data[$component_id]['description'] = $data[ md5( 'composite_'.$component_id.'_description' ) ];
			}

		}

		update_post_meta( $product_id, '_bto_data', $composite_data );


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

	function wcml_js_lock_fields_input_names( $names ){

		$names[] = '_base_regular_price';
		$names[] = '_base_sale_price';
		$names[] = 'bto_style';

		return $names;
	}

	function wcml_js_lock_fields_ids( $names ){

		$names[] = '_per_product_pricing_bto';
		$names[] = '_per_product_shipping_bto';
		$names[] = '_bto_hide_shop_price';

		return $names;
	}

	function localize_lock_fields_js(){
		wp_localize_script( 'wcml-composite-js', 'lock_settings' , array( 'lock_fields' => 1 ) );
	}

	function load_assets( ){
		global $pagenow;

		if( ( $pagenow == 'post.php' && isset( $_GET[ 'post' ] ) && wc_get_product( $_GET[ 'post' ] )->product_type == 'composite' ) || $pagenow == 'post-new.php' ){
			wp_register_script( 'wcml-composite-js', WCML_PLUGIN_URL . '/compatibility/res/js/wcml-composite.js', array( 'jquery' ), WCML_VERSION );
			wp_enqueue_script( 'wcml-composite-js' );

		}

	}

	function woocommerce_json_search_found_products( $found_products ){
		global $wpml_post_translations, $sitepress;

		foreach( $found_products as $id => $product_name ){
			if( $wpml_post_translations->get_element_lang_code ( $id ) != $sitepress->get_current_language() ){
				unset( $found_products[ $id ] );
			}
		}

		return $found_products;
	}
}
