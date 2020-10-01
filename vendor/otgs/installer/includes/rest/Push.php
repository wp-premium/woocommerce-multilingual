<?php

namespace OTGS\Installer\Rest;

use \WP_REST_Response;

class Push {

	const REFRESH_INTERVAL = 7200; //2 hours

	const REST_NAMESPACE = 'otgs/installer/v1';

	public static function register_routes() {
		register_rest_route(
			self::REST_NAMESPACE,
			'push/fetch-subscription',
			[
				'methods'  => 'GET',
				'callback' => self::class . '::fetch_subscription',
			]
		);
	}

	public static function fetch_subscription() {
		$installer    = OTGS_Installer();
		$last_refresh = $installer->get_last_subscriptions_refresh();

		if ( defined( 'OTGS_INSTALLER_OVERRIDE_SUB_LAST_REFRESH' ) ) {
			$last_refresh = constant( 'OTGS_INSTALLER_OVERRIDE_SUB_LAST_REFRESH' );
		}

		if ( time() - $last_refresh > self::REFRESH_INTERVAL
		) {
			$installer->refresh_subscriptions_data();

			return new WP_REST_Response( [ 'message' => 'OK' ], 200 );
		}

		return new WP_REST_Response( [ 'message' => 'OK' ], 403 );
	}
}
