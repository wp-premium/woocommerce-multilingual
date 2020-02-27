<?php

class WCML_Setup_Ready_UI extends WCML_Templates_Factory {

	private $woocommerce_wpml;

	/**
	 * WCML_Setup_Ready_UI constructor.
	 *
	 * @param woocommerce_wpml $woocommerce_wpml
	 */
	public function __construct( $woocommerce_wpml ) {
		// @todo Cover by tests, required for wcml-3037.
		parent::__construct();

		$this->woocommerce_wpml = $woocommerce_wpml;
	}

	public function get_model() {

		$translated_attributes        = $this->woocommerce_wpml->attributes->get_translatable_attributes();
		$untranslated_attribute_terms = [];
		foreach ( $translated_attributes as $attribute ) {
			if ( ! $this->woocommerce_wpml->terms->is_fully_translated( 'pa_' . $attribute->attribute_name ) ) {
				$untranslated_attribute_terms[] = '<strong>' . $attribute->attribute_label . '</strong>';
			}
		}

		$untranslated_categories       = ! $this->woocommerce_wpml->terms->is_fully_translated( 'product_cat' );
		$untranslated_tags             = ! $this->woocommerce_wpml->terms->is_fully_translated( 'product_tag' );
		$untranslated_shipping_classes = ! $this->woocommerce_wpml->terms->is_fully_translated( 'product_shipping_class' );

		$model = [
			'strings'      => [
				'step_id'      => 'ready_step',
				'heading'      => __( 'Setup Complete', 'woocommerce-multilingual' ),
				'description1' => __( 'Your multilingual shop is almost ready. Next, you should go to the different tabs in %1$sWooCommerce &raquo; WooCommerce Multilingual%2$s admin and do the final setup.', 'woocommerce-multilingual' ),
				'description2' => __( "For your convenience, we've marked items that require your attention with a notice icon. You can see a list of everything that you should complete in the %1\$sStatus%2\$s tab.", 'woocommerce-multilingual' ),
				'continue'     => __( 'Close setup', 'woocommerce-multilingual' ),
			],
			'continue_url' => admin_url( 'admin.php?page=wpml-wcml&tab=status&src=setup' ),
		];

		return $model;

	}

	protected function init_template_base_dir() {
		$this->template_paths = [
			WCML_PLUGIN_PATH . '/templates/',
		];
	}

	public function get_template() {
		return '/setup/ready.twig';
	}


}
