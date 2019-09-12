<?php

class OTGS_Installer_Support_Template_Factory {

	private $installer_path;

	public function __construct( $installer_path ) {
		$this->installer_path = $installer_path;
	}

	/**
	 * @return OTGS_Installer_Support_Template
	 */
	public function create() {
		$template_service = OTGS_Template_Service_Factory::create(
			$this->installer_path . '/templates/php/support/'
		);
		$instances_factory = new OTGS_Installer_Instances_Factory();

		return new OTGS_Installer_Support_Template(
			$template_service,
			new OTGS_Installer_Logger_Storage( new OTGS_Installer_Log_Factory() ),
			new OTGS_Installer_Requirements(),
			$instances_factory->create()
		);
	}
}