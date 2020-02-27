<?php

namespace OTGS\Installer\AdminNotices;

use function OTGS\Installer\FP\partial;

class Loader {

	/**
	 * @param bool $isAjax
	 */
	public static function addHooks( $isAjax ) {
		add_action( 'current_screen', self::class . '::initDisplay' );
		if ( $isAjax ) {
			add_action( 'wp_ajax_installer_dismiss_nag', Dismissed::class . '::dismissNotice' );
		}
	}

	public static function initDisplay() {

		remove_action( 'current_screen', self::class . '::initDisplay' );

		/**
		 * Filter and return installer admin notices
		 *
		 * @param array - an associative array of messages keyed by repository
		 * eg.
		 * [ 'repo' => [ 'wpml' = [ 'message_id_1', 'message_id_2' ... ] ] ]
		 */
		$messages = apply_filters( 'otgs_installer_admin_notices', [] );

		if ( ! empty( $messages ) ) {

			/**
			 * Filter and return configuration of where messages should be displayed
			 *
			 * @param array - an associative array keyed by repository
			 * eg.
			 * [ 'repo' => [ 'wpml' => [
			 *    'message_id_1' => [
			 *        'screens' => [ 'plugins', 'dashboard', ... ],
			 *        'pages' => [ 'sitepress-multilingual-cms/menu/languages.php', ... ]
			 *    ],
			 *    ...
			 * ] ] ]
			 */
			$config = apply_filters( 'otgs_installer_admin_notices_config', [] );

			/**
			 * Filter and return callback functions for retrieving the text for each message
			 * The message id is passed to the callback function
			 *
			 * @param array - an associative array keyed by repository
			 * eg.
			 * [ 'repo' => [ 'wpml' => [
			 *     'message_id_1' => some_callback_function,
			 *     'message_id_2' => some_callback_function,
			 * ] ] ]
			 *
			 */
			$texts = apply_filters( 'otgs_installer_admin_notices_texts', [] );

			$dismissedNotices = self::refreshDismissed();

			( new Display(
				$messages,
				$config,
				new MessageTexts( $texts ),
				partial( Dismissed::class . '::isDismissed', $dismissedNotices )
			) )->addHooks();
		}
	}

	/**
	 * @return array
	 */
	private static function refreshDismissed() {
		$store = new Store();

		$dismissedMessages = $store->get( Dismissed::STORE_KEY, [] );
		$remainingMessages = Dismissed::clearExpired(
			$dismissedMessages,
			[ self::class, 'timeOut' ]
		);

		if ( $dismissedMessages !== $remainingMessages ) {
			$store->save( Dismissed::STORE_KEY, $remainingMessages );
		}

		return $remainingMessages;
	}

	/**
	 * @param int $start
	 * @param string $repo
	 * @param string $id
	 *
	 * @return bool
	 */
	public static function timeOut( $start, $repo, $id ) {
		/**
		 * Filters the default time that a notice stays dismissed for. The default is 2 months
		 *
		 * @param int $timeout
		 * @param string $repo
		 * @param string id - message id
		 * return a timestamp in seconds. eg WEEK_IN_SECONDS, etc
		 */
		$timeout = apply_filters(
			'otgs_installer_admin_notices_dismissed_time',
			2 * MONTH_IN_SECONDS,
			$repo,
			$id
		);

		return time() - $start > $timeout;
	}

}
