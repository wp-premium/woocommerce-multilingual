<?php

namespace OTGS\Installer\AdminNotices\Notices;

use OTGS\Installer\AdminNotices\Store;
use OTGS\Installer\AdminNotices\ToolsetConfig;
use OTGS\Installer\AdminNotices\WPMLConfig;
use OTGS\Installer\Collection;
use function OTGS\Installer\FP\partial;

class Account {

	const NOT_REGISTERED         = 'not-registered';
	const EXPIRED                = 'expired';
	const REFUNDED               = 'refunded';
	const GET_FIRST_INSTALL_TIME = 'get_first_install_time';

	public static function addHooks( \WP_Installer $installer ) {
		add_filter( 'otgs_installer_admin_notices_config', self::class . '::config', 10, 1 );
		add_filter( 'otgs_installer_admin_notices_texts', self::class . '::texts', 10, 1 );
		add_filter(
			'otgs_installer_admin_notices',
			partial( self::class . '::getCurrentNotices', $installer )
		);
	}

	/**
	 * @param \WP_Installer $installer
	 * @param array $initialNotices
	 *
	 * @return array
	 */
	public static function getCurrentNotices( \WP_Installer $installer, array $initialNotices ) {

		$config = $installer->get_site_key_nags_config();

		$addNoticesForType = function ( Collection $notices, array $data ) use ( $installer, $config ) {
			list( $type, $fn ) = $data;
			$addNotice  = partial( self::class . '::addNotice', $type );
			$shouldShow = partial( [ self::class, $fn ], $installer );

			return $notices->mergeRecursive( Collection::of( $config )
			                                           ->filter( $shouldShow )
			                                           ->pluck( 'repository_id' )
			                                           ->reduce( $addNotice, [] ) );
		};

		$noticeTypes = [
			self::NOT_REGISTERED => 'shouldShowNotRegistered',
			self::EXPIRED        => 'shouldShowExpired',
			self::REFUNDED       => 'shouldShowRefunded'
		];

		return collection::of( $noticeTypes )
		                 ->entities()
		                 ->reduce( $addNoticesForType, Collection::of( $initialNotices ) )
		                 ->get();

	}

	/**
	 * @param string $noticeId
	 * @param array $notices
	 * @param string $repoId
	 *
	 * @return array
	 */
	public static function addNotice( $noticeId, array $notices, $repoId ) {
		return array_merge_recursive( $notices, [ 'repo' => [ $repoId => [ $noticeId ] ] ] );
	}

	/**
	 * @param \WP_Installer $installer
	 * @param array $nag
	 *
	 * @return bool
	 */
	public static function shouldShowNotRegistered( \WP_Installer $installer, array $nag ) {
		$shouldShow = ! self::isDevelopmentSite( $installer->get_installer_site_url( $nag['repository_id'] ) ) &&
		              ! $installer->repository_has_subscription( $nag['repository_id'] ) &&
		              ( isset( $nag['condition_cb'] ) ? $nag['condition_cb']() : true );

		if ( $shouldShow ) {
			$shouldShow = ! self::maybeDelayOneWeekOnNewInstalls( $nag['repository_id'] );
		}

		return $shouldShow;
	}

	/**
	 * @param \WP_Installer $installer
	 * @param array $nag
	 *
	 * @return bool
	 */
	public static function shouldShowExpired( \WP_Installer $installer, array $nag ) {
		return $installer->repository_has_expired_subscription( $nag['repository_id'], 30 * DAY_IN_SECONDS );
	}

	/**
	 * @param \WP_Installer $installer
	 * @param array $nag
	 *
	 * @return bool
	 */
	public static function shouldShowRefunded( \WP_Installer $installer, array $nag ) {
		return $installer->repository_has_refunded_subscription( $nag['repository_id'] );
	}

	public static function config( array $initialConfig ) {
		return self::pages( self::screens( $initialConfig ) );
	}

	public static function pages( array $initialPages ) {
		$wpmlPages    = [ 'pages' => WPMLConfig::pages() ];
		$toolsetPages = [ 'pages' => ToolsetConfig::pages() ];

		return array_merge_recursive( $initialPages, [
			'repo' => [
				'wpml'    => [
					Account::NOT_REGISTERED => $wpmlPages,
					Account::EXPIRED        => $wpmlPages,
					Account::REFUNDED       => $wpmlPages,
				],
				'toolset' => [
					Account::NOT_REGISTERED => $toolsetPages,
					Account::EXPIRED        => $toolsetPages,
					Account::REFUNDED       => $toolsetPages,
				],
			]
		] );
	}

	public static function screens( array $screens ) {
		$config = [
			Account::NOT_REGISTERED => [ 'screens' => [ 'plugins' ] ],
			Account::EXPIRED        => [ 'screens' => [ 'plugins' ] ],
			Account::REFUNDED       => [ 'screens' => [ 'plugins', 'dashboard' ] ],
		];

		return array_merge_recursive( $screens, [
			'repo' => [
				'wpml'    => $config,
				'toolset' => $config,
			]
		] );
	}

	public static function texts( array $initialTexts ) {
		return array_merge( $initialTexts, [
			'repo' => [
				'wpml'    => [
					Account::NOT_REGISTERED => WPMLTexts::class . '::notRegistered',
					Account::EXPIRED        => WPMLTexts::class . '::expired',
					Account::REFUNDED       => WPMLTexts::class . '::refunded',
				],
				'toolset' => [
					Account::NOT_REGISTERED => ToolsetTexts::class . '::notRegistered',
					Account::EXPIRED        => ToolsetTexts::class . '::expired',
					Account::REFUNDED       => ToolsetTexts::class . '::refunded',
				],
			]
		] );
	}

	private static function isDevelopmentSite( $url ) {
		$endsWith = function ( $haystack, $needle ) {
			return substr_compare( $haystack, $needle, - strlen( $needle ) ) === 0;
		};

		$host = parse_url( $url, PHP_URL_HOST );

		return $endsWith( $host, '.dev' ) ||
		       $endsWith( $host, '.local' ) ||
		       $endsWith( $host, '.test' );
	}

	private static function maybeDelayOneWeekOnNewInstalls( $repo ) {
		$store       = new Store();
		$installTime = $store->get( self::GET_FIRST_INSTALL_TIME, [] );
		if ( ! isset( $installTime[ $repo ] ) ) {
			$installTime[ $repo ] = time();
			$store->save( self::GET_FIRST_INSTALL_TIME, $installTime );
		}

		return time() - $installTime[ $repo ] < WEEK_IN_SECONDS;
	}
}
