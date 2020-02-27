<?php

namespace OTGS\Installer\AdminNotices;

class PageConfig extends Config {

	/**
	 * @param array $messages
	 *
	 * @return bool
	 */
	public function isAnyMessageOnPage( array $messages ) {
		if ( ! isset( $_GET['page'] ) ) {
			return false;
		}

		return $this->hasItem( $messages, $_GET['page'], 'pages' );
	}

	/**
	 * @param string $repo
	 * @param string $id
	 *
	 * @return bool
	 */
	public function shouldShowMessage( $repo, $id ) {
		if ( ! isset( $_GET['page'] ) ) {
			return false;
		}
		if ( isset( $this->config['repo'][ $repo ][ $id ]['pages'] ) ) {
			return in_array( $_GET['page'], $this->config['repo'][ $repo ][ $id ]['pages'] );
		}

		return false;
	}
}
