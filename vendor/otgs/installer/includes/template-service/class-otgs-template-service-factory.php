<?php

class OTGS_Template_Service_Factory
{
	/**
	 * @param string $template_dir
	 * @return OTGS_Php_Template_Service
	 */
	public static function create( $template_dir ) {
		return (new OTGS_Php_Template_Service_Loader( $template_dir ))->get_service();
	}
}
