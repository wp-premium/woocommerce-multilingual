<?php

namespace WCML\Media\Wrapper;

use WCML\Media\Wrapper\Translatable;
use woocommerce_wpml;
use WPML_Element_Sync_Settings_Factory;

class Factory {

	/**
	 * @return IMedia
	 */
	public static function create( woocommerce_wpml $woocommerce_wpml ) {
		/**
		 * @var \SitePress $sitepress
		 * @var \wpdb      $wpdb
		 */
		global $sitepress, $wpdb;

		$settingsFactory = new WPML_Element_Sync_Settings_Factory();

		if ( $settingsFactory->create( 'post' )->is_sync( 'attachment' ) ) {
			return new Translatable( $woocommerce_wpml, $sitepress, $wpdb );
		}

		return new NonTranslatable();
	}
}
