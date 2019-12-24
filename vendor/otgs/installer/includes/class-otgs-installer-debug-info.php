<?php

class OTGS_Installer_Debug_Info {
	private $installer;

	/**
	 * @var OTGS_Products_Config_Db_Storage
	 */
	private $products_config_storage;

	public function __construct( WP_Installer $installer, OTGS_Products_Config_Db_Storage $products_config_storage ) {
		$this->installer = $installer;
		$this->products_config_storage = $products_config_storage;
	}

	public function add_hooks() {
		add_filter( 'icl_get_extra_debug_info', array( $this, 'add_installer_config_in_debug_information' ) );
	}

	/**
	 * @param array $data
	 *
	 * @return array
	 */
	public function add_installer_config_in_debug_information( $data ) {
		global $wp_installer_instances;

		$repositories_data = array();
		$repositories = $this->installer->get_repositories();
		$repository_settings = $this->installer->get_settings();
		$repository_settings = $repository_settings['repositories'];

		foreach ( $repositories as $repo_id => $repository ) {
			$repositories_data[ $repo_id ] = array(
				'api-url' => $repository['api-url'],
				'bucket-url' => $this->prepare_bucket_url( $repo_id ),
				'subscription' => isset( $repository_settings[ $repo_id ]['subscription'] ) ? $repository_settings[ $repo_id ]['subscription'] : '',
			);
		}

		$data['installer'] = array(
			'version'   => $this->installer->version(),
			'repositories' => $repositories_data,
			'instances' => $wp_installer_instances,
		);

		return $data;
	}

	private function prepare_bucket_url( $repo_id ) {
		$bucket_url = $this->products_config_storage->get_repository_products_url( $repo_id );
		return $bucket_url ? $bucket_url : 'not assigned';
	}
}