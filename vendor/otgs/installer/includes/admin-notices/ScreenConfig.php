<?php

namespace OTGS\Installer\AdminNotices;

class ScreenConfig extends Config {

	/**
	 * @param array $messages
	 *
	 * @return bool
	 */
	public function isAnyMessageOnPage( array $messages ) {
		$currentScreen = get_current_screen();

		if ( ! $currentScreen instanceof \WP_Screen ) {
			return false;
		}

		return $this->hasItem( $messages, $currentScreen->id, 'screens' );
	}

	/**
	 * @param string $repo
	 * @param string $id
	 *
	 * @return bool
	 */
	public function shouldShowMessage( $repo, $id ) {
		$currentScreen = get_current_screen();

		if ( $currentScreen instanceof \WP_Screen ) {
			if ( isset( $this->config['repo'][ $repo ][ $id ]['screens'] ) ) {
				return in_array( $currentScreen->id, $this->config['repo'][ $repo ][ $id ]['screens'] );
			}
		}

		return false;
	}

}
