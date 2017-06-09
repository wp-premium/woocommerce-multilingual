<?php

class WCML_Taxonomy_Translation_Link_Filters{

	/**
	 * @var WCML_Attributes
	 */
	private $wcml_attributes;

	public function __construct( $wcml_attributes ) {
		$this->wcml_attributes = $wcml_attributes;
	}

	public function add_filters(){
		add_filter( 'wpml_notice_text_taxonomy-term-help-notices', array( $this, 'override_translation_notice_text' ), 10, 2 );

	}

	/**
	 * @param string text
	 * @param WPML_Notice $notice
	 *
	 * @return string
	 */
	public function override_translation_notice_text( $text, $notice ) {

		$taxonomy = get_taxonomy( $notice->get_id() );
		if ( false !== $taxonomy ) {

			$link = sprintf(
				'<a href="%s">%s</a>',
				$this->get_screen_url( $taxonomy->name ),
				sprintf ( __( '%s translation', 'woocommerce-multilingual' ), $taxonomy->labels->singular_name )
			);

			$text = sprintf(
				esc_html__( 'Translating %s? Use the %s table for easier translation.', 'woocommerce-multilingual' ),
				$taxonomy->labels->name,
				$link
			);
		}

		return $text;
	}

	/**
	 * @param string $taxonomy
	 *
	 * @return string
	 */
	public function get_screen_url( $taxonomy = '' ){

		$url = false;

		$built_in_taxonomies = array( 'product_cat', 'product_tag', 'product_shipping_class' );
		if( in_array( $taxonomy, $built_in_taxonomies ) ){

			$url = add_query_arg( array( 'tab' => $taxonomy ), admin_url( 'admin.php?page=wpml-wcml' ) ) ;

		} else {

			$attributes = $this->wcml_attributes->get_translatable_attributes();
			$translatable_attributes = array();
			foreach( $attributes as $attribute ){
				$translatable_attributes[] = 'pa_' . $attribute->attribute_name;
			}

			if( in_array( $taxonomy, $translatable_attributes ) ) {

				$url = add_query_arg(
					array( 'taxonomy' => $taxonomy ),
					admin_url( 'admin.php?page=wpml-wcml&tab=product-attributes' )
				);

			}else{

				$custom_taxonomies = get_object_taxonomies( 'product', 'objects' );

				$translatable_taxonomies = array();
				foreach( $custom_taxonomies as $product_taxonomy_name => $product_taxonomy_object ){
					if( is_taxonomy_translated( $product_taxonomy_name ) ){
						$translatable_taxonomies[] = $product_taxonomy_name;
					}
				}

				if ( in_array( $taxonomy, $translatable_taxonomies ) ) {

					$url = add_query_arg(
						array( 'taxonomy' => $taxonomy ),
						admin_url( 'admin.php?page=wpml-wcml&tab=custom-taxonomies' )
					);

				}

			}

		}

		return $url;
	}

}