<?php

class OTGS_Products_Bucket_Repository_Factory {

	/**
	 * @return OTGS_Products_Bucket_Repository
	 */
	public static function create( $api_urls ) {
		return new OTGS_Products_Bucket_Repository(
			$api_urls
		);
	}
}