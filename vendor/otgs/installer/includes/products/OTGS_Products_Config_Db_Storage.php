<?php

class OTGS_Products_Config_Db_Storage {
	const PRODUCTS_CONFIG_KEY = 'otgs_installer_products_urls';

	/**
	 * @param string $repository_id
	 *
	 * @return string|null
	 */
	public function get_repository_products_url( $repository_id ) {
		$products = get_option( self::PRODUCTS_CONFIG_KEY, [] );
		return isset( $products[ $repository_id ] ) ? $products[$repository_id] : null;
	}

	/**
	 * @param string $repository_id
	 * @param string $repository_products_url
	 *
	 * @return bool
	 */
	public function store_repository_products_url( $repository_id, $repository_products_url ) {
		$products_config = get_option( self::PRODUCTS_CONFIG_KEY, [] );
		$products_config[ $repository_id ] = $repository_products_url;

		return update_option( self::PRODUCTS_CONFIG_KEY, $products_config, 'yes');
	}

	/**
	 * @param string $repository_id
	 *
	 * @return bool
	 */
	public function clear_repository_products_url( $repository_id ) {
		$products_config = get_option( self::PRODUCTS_CONFIG_KEY, [] );
		unset( $products_config[ $repository_id ] );

		return update_option( self::PRODUCTS_CONFIG_KEY, $products_config, 'yes');
	}
}
