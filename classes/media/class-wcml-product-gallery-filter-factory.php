<?php

class WCML_Product_Gallery_Filter_Factory implements IWPML_Frontend_Action_Loader {

	public function create() {
		global $sitepress, $woocommerce_wpml;

		if ( $woocommerce_wpml->get_setting( 'sync_media', true ) ) {
			return new WCML_Product_Gallery_Filter( new WPML_Translation_Element_Factory( $sitepress ) );
		}
	}

}
