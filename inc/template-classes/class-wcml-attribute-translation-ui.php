<?php


class WCML_Attribute_Translation_UI extends WPML_Templates_Factory {

	private $woocommerce_wpml;

	public function __construct( &$woocommerce_wpml ){
		parent::__construct();

		$this->woocommerce_wpml = $woocommerce_wpml;
	}

	public function get_model() {

		$product_attributes = $this->woocommerce_wpml->attributes->get_translatable_attributes();
		$taxonomy = isset( $_GET['taxonomy'] ) ? $_GET['taxonomy'] : false;

		if( $product_attributes ){
			if( !empty($taxonomy) ){
				foreach( $product_attributes as $attribute ){
					if( $attribute->attribute_name == $taxonomy ){
						$selected_attribute = $attribute;
						break;
					}
				}
			}
			if( empty( $selected_attribute ) ){
				$selected_attribute = current( $product_attributes );
			}
		}else{
			$selected_attribute = false;
		}


		$selected_attribute_name = $selected_attribute ? 'pa_' . $selected_attribute->attribute_name : '';
		$WPML_Translate_Taxonomy =
			new WPML_Taxonomy_Translation(
				$selected_attribute_name,
				array(
					'taxonomy_selector'=> false
				)
			);
		ob_start();
		$WPML_Translate_Taxonomy->render();
		$translation_ui = ob_get_contents();
		ob_end_clean();

		$model = array(

			'attributes' => $product_attributes,
			'selected_attribute' => $selected_attribute,
			'strings' => array(
				'no_attributes' => __( 'There are no translatable product attributes defined', 'woocommerce-multilingual' ),
				'select_label'	=> __('Select the attribute to translate: ', 'woocommerce-multilingual'),
				'loading'       => __( 'Loading ...', 'woocommerce-multilingual' )
			),
			'translation_ui' => $translation_ui

		);

		return $model;
	}



	public function init_template_base_dir() {
		$this->template_paths = array(
			WCML_PLUGIN_PATH . '/templates/',
		);
	}

	public function get_template() {
		return 'attribute-translation.twig';
	}
}