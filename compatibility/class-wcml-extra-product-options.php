<?php

class WCML_Extra_Product_Options {

	public function __construct() {

		add_action( 'tm_before_extra_product_options', [ $this, 'inf_translate_product_page_strings' ] );
		add_action( 'tm_before_price_rules', [ $this, 'inf_translate_strings' ] );
	}

	public function inf_translate_strings() {
		if ( isset( $_GET['page'] ) && 'tm-global-epo' === $_GET['page'] ) {
			$this->inf_message( 'Options Form' );
		}
	}

	public function inf_translate_product_page_strings() {
		$this->inf_message( 'Product' );
	}

	public function inf_message( $text ) {
		$message  = '<div><p class="icl_cyan_box">';
		$message .= sprintf( __( 'To translate Extra Options strings please save %1$s and go to the <b><a href="%2$s">String Translation interface</a></b>', 'woocommerce-multilingual' ), esc_html( $text ), admin_url( 'admin.php?page=' . WPML_ST_FOLDER . '/menu/string-translation.php&context=wc_extra_product_options' ) );
		$message .= '</p></div>';

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $message;
	}
}
