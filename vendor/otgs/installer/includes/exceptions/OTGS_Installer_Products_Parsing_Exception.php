<?php

class OTGS_Installer_Products_Parsing_Exception extends Exception {
	const RESPONSE_PARSING_ERROR_MESSAGE = 'Error in response parsing from %s.';

	public static function createForResponse( $products_url ) {
		return new OTGS_Installer_Products_Parsing_Exception(
			sprintf(
				self::RESPONSE_PARSING_ERROR_MESSAGE,
				$products_url
			)
		);
	}
}