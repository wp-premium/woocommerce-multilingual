<?php

namespace OTGS\Installer\AdminNotices;

class Dismissed {
	const STORE_KEY = 'dismissed';

	/**
	 * @param array $dismissedNotices
	 * @param string $repo
	 * @param string $id
	 *
	 * @return bool
	 */
	public static function isDismissed( array $dismissedNotices, $repo, $id ) {
		return isset( $dismissedNotices['repo'][ $repo ][ $id ] );
	}

	/**
	 * @param array $dismissedNotices
	 * @param callable $timeOut - int -> string -> string -> bool
	 *
	 * @return mixed
	 */
	public static function clearExpired( array $dismissedNotices, callable $timeOut ) {
		if ( isset( $dismissedNotices['repo'] ) ) {

			foreach ( $dismissedNotices['repo'] as $repo => $ids ) {
				foreach ( $ids as $id => $dismissedTimeStamp ) {
					if ( $timeOut( $dismissedTimeStamp, $repo, $id ) ) {
						unset ( $dismissedNotices['repo'][ $repo ][ $id ] );
					}
				}
			}
		}

		return $dismissedNotices;
	}

	public static function dismissNotice() {
		$data = filter_var_array( $_POST, [
			'repository' => FILTER_SANITIZE_STRING,
			'noticeId'   => FILTER_SANITIZE_STRING,
		] );

		$store = new Store();

		$dismissed = $store->get( self::STORE_KEY, [] );

		$dismissed['repo'][ $data['repository'] ][ $data['noticeId'] ] = time();

		$store->save( self::STORE_KEY, $dismissed );

		wp_send_json_success( [] );
	}

}
