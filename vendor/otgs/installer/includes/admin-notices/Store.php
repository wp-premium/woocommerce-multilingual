<?php

namespace OTGS\Installer\AdminNotices;

class Store {
	const ADMIN_NOTICES_OPTION = 'otgs_installer_admin_notices';

	/**
	 * @param string $key
	 * @param $data
	 */
	public function save( $key, $data ) {
		$current = get_option( self::ADMIN_NOTICES_OPTION, [] );
		$current[ $key ] = $data;
		update_option( self::ADMIN_NOTICES_OPTION, $current, 'no' );
	}

	/**
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function get( $key, $default ) {
		$current = get_option( self::ADMIN_NOTICES_OPTION, [] );
		return isset( $current[$key] ) ? $current[$key] : $default;
	}

}
