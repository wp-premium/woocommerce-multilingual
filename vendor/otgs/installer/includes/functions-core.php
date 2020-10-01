<?php
/**
 * @deprecated
 */
function WP_Installer(){
	return WP_Installer::instance();
}

function OTGS_Installer(){
	return WP_Installer::instance();
}

function WP_Installer_Channels(){
	return WP_Installer_Channels::instance();
}

function otgs_installer_get_logger_storage() {
	static $logger_storage;
	if ( ! $logger_storage ) {
		$logger_storage = new OTGS_Installer_Logger_Storage( new OTGS_Installer_Log_Factory() );
	}

	return $logger_storage;
}

function get_OTGS_Installer_Factory() {
	static $installer_factory;

	if ( ! $installer_factory ) {
		$installer_factory = new OTGS_Installer_Factory( WP_Installer() );
	}

	return $installer_factory;
}