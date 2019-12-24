<?php

class OTGS_Products_Manager_Factory {

	/**
	 * @param OTGS_Products_Config_Xml $repositories_config
	 * @param OTGS_Installer_Logger_Storage $logger_storage
	 *
	 * @return OTGS_Products_Manager
	 */
	public static function create( OTGS_Products_Config_Xml $repositories_config, OTGS_Installer_Logger_Storage $logger_storage ) {
		return new OTGS_Products_Manager(
			new OTGS_Products_Config_Db_Storage(),
			OTGS_Products_Bucket_Repository_Factory::create( $repositories_config->get_products_api_urls() ),
			$repositories_config,
			WP_Installer_Channels(),
			$logger_storage
		);
	}
}