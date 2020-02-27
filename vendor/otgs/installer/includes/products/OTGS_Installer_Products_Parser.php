<?php

class OTGS_Installer_Products_Parser {

	/**
	 * @var OTGS_Installer_Logger_Storage
	 */
	private $logger;

	/**
	 * @var WP_Installer_Channels
	 */
	private $installerChannels;

	private $product_notices = array();

	public function __construct( WP_Installer_Channels $installerChannels, OTGS_Installer_Logger_Storage $logger ) {
		$this->logger = $logger;
		$this->installerChannels = $installerChannels;
	}

	/**
	 * @param string $products_url
	 * @param string $repository_id
	 * @param string $response
	 *
	 * @return array
	 * @throws OTGS_Installer_Products_Parsing_Exception
	 */
	public function get_products_from_response( $products_url, $repository_id, $response ) {
		$products = $this->parse_products_response( $products_url, $response );
		$products = $this->validate_products_plugins( $products_url, $products );

		$products['downloads'] = $this->prepare_products_downloads( $repository_id, $products );
		return $products;
	}

	/**
	 * @param string $products_url
	 * @param string $response
	 *
	 * @return array
	 * @throws OTGS_Installer_Products_Parsing_Exception
	 */
	private function parse_products_response( $products_url, $response ) {
		$body = wp_remote_retrieve_body( $response );
		if ( $body ) {
			$json = json_decode( $body, true );

			if ( $json ) {
				return $json;
			}
		}

		throw OTGS_Installer_Products_Parsing_Exception::createForResponse( $products_url );
	}

	/**
	 * @param string $products_url
	 * @param array $products
	 *
	 * @throws OTGS_Installer_Products_Parsing_Exception
	 */
	private function validate_products_plugins( $products_url, $products ) {
		if ( is_array( $products ) ) {
			foreach ( $products['downloads']['plugins'] as $product_id => $product ) {
				if ( empty( $product['slug'] )
				     || empty($product['name'])
				     || empty($product['version'])
				     || empty($product['date'])
				     || empty($product['url'])
				     || empty($product['basename'])
				) {
					$this->handle_product_parsing_error( $products_url, $product_id );
					throw OTGS_Installer_Products_Parsing_Exception::createForResponse( $products_url );
				}
			}
		}

		return $products;
	}

	/**
	 * @param string $products_url
	 * @param string $product_id
	 */
	private function handle_product_parsing_error( $products_url, $product_id ) {
		$error = sprintf( __( 'Information about versions of %s are invalid. It may be a temporary communication problem, please check for updates again.', 'installer' ), $product_id );
		$this->store_log( $products_url, $error );
		$this->product_notices[] = $error;
	}

	/**
	 * @param string $url
	 * @param string $response
	 */
	private function store_log( $url, $response ) {
		$log = new OTGS_Installer_Log();
		$log->set_request_url( $url )
		    ->set_component( OTGS_Installer_Logger_Storage::COMPONENT_PRODUCTS_PARSING )
		    ->set_response( $response );

		$this->logger->add( $log );
	}

	/**
	 * @param string $repository_id
	 * @param string $products
	 *
	 * @return array
	 */
	private function prepare_products_downloads( $repository_id, $products ) {
		$downloads = $this->installerChannels->filter_downloads_by_channel( $repository_id, $products['downloads'] );
		$downloads = $this->add_release_notes( $downloads );

		return $downloads;
	}

	/**
	 * @param array $products_downloads
	 *
	 * @return array
	 */
	private function add_release_notes( $products_downloads ) {
		foreach ( $products_downloads as $kind => $downloads ) {
			foreach ( $downloads as $slug => $download ) {
				$start = strpos( $download['changelog'], '<h4>' . $download['version'] . '</h4>' );
				if ( $start !== false ) {
					$start += strlen( $download['version'] ) + 9;
					$end   = strpos( $download['changelog'], '<h4>', 4 );
					if ( $end ) {
						$release_notes = substr( $download['changelog'], $start, $end - $start );
					} else {
						$release_notes = substr( $download['changelog'], $start );
					}
				}
				$products_downloads[ $kind ][ $slug ]['release-notes'] = ! empty( $release_notes ) ? $release_notes : '';
			}
		}

		return $products_downloads;
	}

	/**
	 * @return array
	 */
	public function get_product_notices() {
		return $this->product_notices;
	}
}