<?php

/**
 * Class WCML_Product_Addons
 */
class WCML_Product_Addons {

	/**
	 * @var SitePress
	 */
	public $sitepress;

	/**
	 * WCML_Product_Addons constructor.
	 */
	function __construct( &$sitepress ) {
		$this->sitepress = $sitepress;
		add_filter( 'get_product_addons_product_terms', array( $this, 'addons_product_terms' ) );
		add_filter( 'get_product_addons_fields', array( $this, 'product_addons_filter' ), 10, 1 );

		add_action( 'updated_post_meta', array( $this, 'register_addons_strings' ), 10, 4 );
		add_action( 'added_post_meta', array( $this, 'register_addons_strings' ), 10, 4 );
		add_filter( 'get_post_metadata', array( $this, 'translate_addons_strings' ), 10, 4 );

		global $pagenow;
		if ( 'edit.php' === $pagenow &&
		    isset( $_GET['post_type'] ) &&
		    'product' === $_GET['post_type'] &&
		    isset( $_GET['page'] ) &&
		     'global_addons' === $_GET['page'] &&
		     ! isset( $_GET['edit'] )
		) {
			add_action( 'admin_notices', array( $this, 'inf_translate_strings' ) );
		}

		add_action( 'addons_panel_start', array( $this, 'inf_translate_strings' ) );

		if ( is_admin() ) {

			add_action( 'wcml_gui_additional_box_html', array( $this, 'custom_box_html' ), 10, 3 );
			add_filter( 'wcml_gui_additional_box_data', array( $this, 'custom_box_html_data' ), 10, 3 );
			add_action( 'wcml_update_extra_fields', array( $this, 'addons_update' ), 10, 3 );
		}
	}

	/**
	 * @param $meta_id
	 * @param $id
	 * @param $meta_key
	 * @param $addons
	 */
	function register_addons_strings( $meta_id, $id, $meta_key, $addons ) {
		if ( '_product_addons' === $meta_key && 'global_product_addon' === get_post_type( $id ) ) {
			foreach ( $addons as $addon ) {
				//register name
				do_action( 'wpml_register_single_string', 'wc_product_addons_strings', $id . '_addon_' . $addon['type'] . '_' . $addon['position'] . '_name', $addon['name'] );
				//register description
				do_action( 'wpml_register_single_string', 'wc_product_addons_strings', $id . '_addon_' . $addon['type'] . '_' . $addon['position'] . '_description', $addon['description'] );
				//register options labels
				foreach ( $addon['options'] as $key => $option ) {
					do_action( 'wpml_register_single_string', 'wc_product_addons_strings', $id . '_addon_' . $addon['type'] . '_' . $addon['position'] . '_option_label_' . $key, $option['label'] );
				}
			}
		}
	}

	/**
	 * @param $null
	 * @param $object_id
	 * @param $meta_key
	 * @param $single
	 *
	 * @return array
	 */
	function translate_addons_strings( $null, $object_id, $meta_key, $single ) {

		if ( '_product_addons' === $meta_key && 'global_product_addon' === get_post_type( $object_id ) ) {

			remove_filter( 'get_post_metadata', array( $this, 'translate_addons_strings' ), 10, 4 );
			$addons = get_post_meta( $object_id, $meta_key, true );
			add_filter( 'get_post_metadata', array( $this, 'translate_addons_strings' ), 10, 4 );

			if ( is_array( $addons ) ) {
				foreach ( $addons as $key => $addon ) {
					//register name
					$addons[ $key ]['name'] = apply_filters( 'wpml_translate_single_string', $addon['name'], 'wc_product_addons_strings', $object_id . '_addon_' . $addon['type'] . '_' . $addon['position'] . '_name' );
					//register description
					$addons[ $key ]['description'] = apply_filters( 'wpml_translate_single_string', $addon['description'], 'wc_product_addons_strings', $object_id . '_addon_' . $addon['type'] . '_' . $addon['position'] . '_description' );
					//register options labels
					foreach ( $addon['options'] as $opt_key => $option ) {
						$addons[ $key ]['options'][ $opt_key ]['label'] = apply_filters( 'wpml_translate_single_string', $option['label'], 'wc_product_addons_strings', $object_id . '_addon_' . $addon['type'] . '_' . $addon['position'] . '_option_label_' . $opt_key );
					}
				}
			}

			return array( 0 => $addons );
		}

		return $null;

	}

	/**
	 * @param $addons
	 *
	 * @return mixed
	 */
	function product_addons_filter( $addons ) {

		foreach ( $addons as $add_id => $addon ) {
			foreach ( $addon['options'] as $key => $option ) {
				//price filter
				$addons[ $add_id ]['options'][ $key ]['price']  = apply_filters( 'wcml_raw_price_amount', $option['price'] );
			}
		}

		return $addons;
	}


	/**
	 * @param $product_terms
	 *
	 * @return array
	 */
	function addons_product_terms( $product_terms ) {
		foreach ( $product_terms as $key => $product_term ) {
			$product_terms[ $key ] = apply_filters( 'translate_object_id', $product_term, 'product_cat', true, $this->sitepress->get_default_language() );
		}

		return $product_terms;
	}

	function inf_translate_strings() {
		$message = '<div><p class="icl_cyan_box">';
		$message .= sprintf( __( 'To translate Add-ons strings please save Add-ons and go to the <b><a href="%s">String Translation interface</a></b>', 'woocommerce-multilingual' ), admin_url( 'admin.php?page='.WPML_ST_FOLDER.'/menu/string-translation.php&context=wc_product_addons_strings' ) );
		$message .= '</p></div>';

		echo $message;
	}

	/**
	 * @param $obj
	 * @param $product_id
	 * @param $data
	 */
	function custom_box_html( $obj, $product_id, $data ) {

		$product_addons = maybe_unserialize( get_post_meta( $product_id, '_product_addons', true ) );

		if ( ! empty( $product_addons ) ) {
			foreach ( $product_addons as $addon_id => $product_addon ) {

				$addons_section = new WPML_Editor_UI_Field_Section( sprintf( __( 'Product Add-ons Group "%s"', 'woocommerce-multilingual' ), $product_addon['name'] ) );

				$group = new WPML_Editor_UI_Field_Group( '' , true );
				$addon_field = new WPML_Editor_UI_Single_Line_Field( 'addon_'.$addon_id.'_name', __( 'Name', 'woocommerce-multilingual' ), $data, false );
				$group->add_field( $addon_field );
				$addon_field = new WPML_Editor_UI_Single_Line_Field( 'addon_'.$addon_id.'_description' , __( 'Description', 'woocommerce-multilingual' ), $data, false );
				$group->add_field( $addon_field );

				$addons_section->add_field( $group );

				if ( ! empty( $product_addon['options'] ) ) {

					$labels_group = new WPML_Editor_UI_Field_Group( __( 'Options', 'woocommerce-multilingual' ) , true );

					foreach ( $product_addon['options'] as $option_id => $option ) {
						$option_label_field = new WPML_Editor_UI_Single_Line_Field( 'addon_'.$addon_id.'_option_'.$option_id.'_label', __( 'Label', 'woocommerce-multilingual' ), $data, false );
						$labels_group->add_field( $option_label_field );
					}
					$addons_section->add_field( $labels_group );
				}
				$obj->add_field( $addons_section );
			}
		}
	}

	/**
	 * @param $data
	 * @param $product_id
	 * @param $translation
	 *
	 * @return mixed
	 */
	function custom_box_html_data( $data, $product_id, $translation ) {

		$product_addons = maybe_unserialize( get_post_meta( $product_id, '_product_addons', true ) );

		if ( ! empty( $product_addons ) ) {
			foreach ( $product_addons as $addon_id => $product_addon ) {
				$data[ 'addon_' . $addon_id . '_name' ] = array( 'original' => $product_addon['name'] );
				$data[ 'addon_' . $addon_id . '_description' ] = array( 'original' => $product_addon['description'] );
				if ( ! empty( $product_addon['options'] ) ) {
					foreach ( $product_addon['options'] as $option_id => $option ) {
						$data[ 'addon_' . $addon_id . '_option_' . $option_id . '_label' ] = array( 'original' => $option['label'] );
					}
				}
			}

			if ( $translation ) {
				$transalted_product_addons = maybe_unserialize( get_post_meta( $translation->ID, '_product_addons', true ) );
				if ( ! empty( $transalted_product_addons ) ) {
					foreach ( $transalted_product_addons as $addon_id => $transalted_product_addon ) {
						$data[ 'addon_' . $addon_id . '_name' ]['translation'] = $transalted_product_addon['name'];
						$data[ 'addon_' . $addon_id . '_description' ]['translation'] = $transalted_product_addon['description'];
						if ( ! empty( $transalted_product_addon['options'] ) ) {
							foreach ( $transalted_product_addon['options'] as $option_id => $option ) {
								$data[ 'addon_' . $addon_id . '_option_' . $option_id . '_label' ]['translation'] = $option['label'];
							}
						}
					}
				}
			}
		}

		return $data;
	}

	/**
	 * @param $original_product_id
	 * @param $product_id
	 * @param $data
	 */
	function addons_update( $original_product_id, $product_id, $data ) {

		$product_addons = maybe_unserialize( get_post_meta( $product_id, '_product_addons', true ) );

		if ( ! empty( $product_addons ) ) {

			foreach ( $product_addons as $addon_id => $product_addon ) {

				$product_addons[ $addon_id ]['name'] = $data[ md5( 'addon_' . $addon_id . '_name' ) ];
				$product_addons[ $addon_id ]['description'] = $data[ md5( 'addon_' . $addon_id . '_description' ) ];

				if ( ! empty( $product_addon['options'] ) ) {

					foreach ( $product_addon['options'] as $option_id => $option ) {
						$product_addons[ $addon_id ]['options'][ $option_id ]['label'] = $data[ md5( 'addon_'.$addon_id.'_option_'.$option_id.'_label' ) ];
					}
				}
			}
		}

		update_post_meta( $product_id, '_product_addons', $product_addons );
	}
}
