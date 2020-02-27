<?php

class OTGS_Products_Manager {

	/**
	 * @var OTGS_Products_Bucket_Repository
	 */
	private $products_bucket_repository;

	/**
	 * @var OTGS_Products_Config_Db_Storage
	 */
	private $products_config_storage;

	/**
	 * @var OTGS_Products_Config_Xml
	 */
	private $products_config_xml;

	/**
	 * @var WP_Installer_Channels
	 */
	private $installer_channels;

	/**
	 * @var OTGS_Installer_Logger_Storage
	 */
	private $logger_storage;

	/**
	 * @param OTGS_Products_Config_Db_Storage $products_config_storage
	 * @param OTGS_Products_Bucket_Repository $products_bucket_repository
	 * @param OTGS_Products_Config_Xml $products_config_xml
	 * @param WP_Installer_Channels $installer_channels
	 * @param OTGS_Installer_Logger_Storage $logger_storage
	 */
	public function __construct(
		OTGS_Products_Config_Db_Storage $products_config_storage,
		OTGS_Products_Bucket_Repository $products_bucket_repository,
		OTGS_Products_Config_Xml $products_config_xml,
		WP_Installer_Channels $installer_channels,
		OTGS_Installer_Logger_Storage $logger_storage
	) {
		$this->products_config_storage    = $products_config_storage;
		$this->products_bucket_repository = $products_bucket_repository;
		$this->products_config_xml        = $products_config_xml;
		$this->installer_channels         = $installer_channels;
		$this->logger_storage             = $logger_storage;
	}

	/**
	 * @param string $repository_id
	 * @param string $site_key
	 * @param string $site_url
	 * @param bool $bypass_buckets
	 *
	 * @return string|null
	 */
	public function get_products_url( $repository_id, $site_key, $site_url, $bypass_buckets ) {
		$repo_id_upper = strtoupper( $repository_id );
		if ( defined( "OTGS_INSTALLER_{$repo_id_upper}_PRODUCTS" ) ) {
			return constant( "OTGS_INSTALLER_{$repo_id_upper}_PRODUCTS" );
		}

		if ( ! $bypass_buckets && $this->is_on_production_channel( $repository_id ) ) {
			$products_url = $this->get_products_url_from_local_config( $repository_id, $site_key );
			if ( $products_url ) {
				return $products_url;
			}

			if ( $site_key ) {
				$products_url = $this->get_products_url_from_otgs( $repository_id, $site_key, $site_url );
				if ( $products_url ) {
					return $products_url;
				}
			}
		}

		return $this->products_config_xml->get_repository_products_url( $repository_id );
	}

	/**
	 * @param string $repository_id
	 *
	 * @return bool
	 */
	private function is_on_production_channel( $repository_id ) {
		return $this->installer_channels->get_channel( $repository_id ) === WP_Installer_Channels::CHANNEL_PRODUCTION;
	}

	/**
	 * @param string $repository_id
	 * @param string $site_key
	 *
	 * @return string|null
	 */
	private function get_products_url_from_local_config( $repository_id, $site_key ) {
		$products_url = $this->products_config_storage->get_repository_products_url( $repository_id );

		if ( $products_url &&  !$site_key ) {
			$this->products_config_storage->clear_repository_products_url( $repository_id );
			return  null;
		}

		return $products_url;
	}

	/**
	 * @param string $repository_id
	 * @param string $site_key
	 * @param string $site_url
	 *
	 * @return string|null
	 */
	public function get_products_url_from_otgs( $repository_id, $site_key, $site_url ) {
		$products_url = null;
		try {
			$products_url = $this->products_bucket_repository->get_products_bucket_url( $repository_id, $site_key, $site_url );
			if ($products_url) {
				$this->products_config_storage->store_repository_products_url( $repository_id, $products_url);
			}
		} catch (Exception $exception) {
			$this->logger_storage->add( $this->prepare_log( $repository_id, $exception->getMessage() ) );
		}

		return $products_url;
	}

	/**
	 * @param string $repository_id
	 * @param string $message
	 *
	 * @return OTGS_Installer_Log
	 */
	private function prepare_log( $repository_id, $message ) {
		$message = sprintf(
			"Installer cannot contact our updates server to get information about the available products of %s and check for new versions. Error message: %s",
			$repository_id,
			$message
		);

		$log = new OTGS_Installer_Log();
		$log->set_component(OTGS_Installer_Logger_Storage::COMPONENT_PRODUCTS_URL);
		$log->set_response( $message );

		return $log;
	}

}
