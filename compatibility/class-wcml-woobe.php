<?php

class WCML_Woobe {

	/**
	 * @var SitePress
	 */
	private $sitepress;

	/**
	 * @var WPML_Post_Translation
	 */
	private $post_translations;


	/**
	 * WCML_Woobe constructor.
	 *
	 * @param SitePress $sitepress
	 * @param WPML_Post_Translation $post_translations
	 */
	function __construct( SitePress $sitepress, WPML_Post_Translation $post_translations ) {
		$this->sitepress        = $sitepress;
		$this->post_translations = $post_translations;
	}

	public function add_hooks() {
		add_action( 'woobe_after_update_page_field', array( $this, 'replace_price_in_translations' ), 10, 5 );
	}

	/**
	 * Replaces product price for translation of given product.
	 *
	 * @param int|null        $product_id Product ID
	 * @param WC_Product|null $product    Product object
	 * @param string|null     $field_key  Key of processed custom field
	 * @param mixed|null      $value      Value of processed custom field
	 * @param string|null     $field_type Type of processed custom field
	 */
	public function replace_price_in_translations( $product_id = null, $product = null, $field_key = null, $value = null, $field_type = null ) {
		if ( $this->is_price_updated( $product_id, $field_key, $value )
			 && $this->is_field_set_to_copy( $field_key )
			) {
			$translations = $this->post_translations->get_element_translations( $product_id, false, true );
			if ( ! empty( $translations ) ) {
				foreach ( $translations as $translation ) {
					update_post_meta( $translation, '_' . $field_key, $value );
					update_post_meta( $translation, '_price', $value );
				}
			}
		}
	}

	/**
	 * Check if filter runs during the  bulk price update
	 *
	 * @param $product_id Product ID
	 * @param $field_key  Key of processed custom field
	 * @param $value      Value of processed custom field
	 *
	 * @return bool
	 */
	private function is_price_updated( $product_id, $field_key, $value ) {
		return is_numeric( $product_id )
		       && 'regular_price' === $field_key
		       && is_numeric( $value );
	}

	private function is_field_set_to_copy( $field_key ) {
		$settings = $this->sitepress->get_settings();
		$field_translation_setting = isset( $settings['translation-management']['custom_fields_translation']['_' . $field_key] ) ? $settings['translation-management']['custom_fields_translation']['_' . $field_key] : null;
		return $field_translation_setting === $this->sitepress->get_wp_api()->constant( 'WPML_COPY_CUSTOM_FIELD' );
	}
}
