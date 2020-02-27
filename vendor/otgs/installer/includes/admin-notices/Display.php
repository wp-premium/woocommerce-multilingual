<?php

namespace OTGS\Installer\AdminNotices;

class Display {

	/**
	 * @var array
	 */
	private $currentNotices;
	/**
	 * @var PageConfig
	 */
	private $pageConfig;
	/**
	 * @var MessageTexts
	 */
	private $messageTexts;
	/**
	 * @var callable - string -> string -> bool
	 */
	private $isDismissed;
	/**
	 * @var ScreenConfig
	 */
	private $screenConfig;

	public function __construct(
		array $currentNotices,
		array $config,
		MessageTexts $messageTexts,
		callable $isDismissed
	) {
		$this->currentNotices = $currentNotices;
		$this->pageConfig     = new PageConfig( $config );
		$this->screenConfig   = new ScreenConfig( $config );
		$this->messageTexts   = $messageTexts;
		$this->isDismissed    = $isDismissed;
	}

	public function addHooks() {
		if ( ! empty( $this->currentNotices ) && $this->isRelevantOnPage() ) {
			add_action( 'admin_notices', [ $this, 'addNotices' ] );
			add_action( 'admin_enqueue_scripts', [ $this, 'addScripts' ] );
		}
	}

	public function addNotices() {
		foreach ( $this->currentNotices['repo'] as $repo => $ids ) {
			foreach ( $ids as $id ) {
				if ( $this->pageConfig->shouldShowMessage( $repo, $id ) || $this->screenConfig->shouldShowMessage( $repo, $id ) ) {
					$this->displayNotice( $repo, $id );
				}
			}
		}
	}

	/**
	 * @return bool
	 */
	private function isRelevantOnPage() {
		return $this->pageConfig->isAnyMessageOnPage( $this->currentNotices ) ||
		       $this->screenConfig->isAnyMessageOnPage( $this->currentNotices );
	}

	/**
	 * @param string $repo
	 * @param string $ids
	 */
	private function displayNotice( $repo, $id ) {
		if ( ! call_user_func( $this->isDismissed, $repo, $id ) ) {
			$html = $this->messageTexts->get( $repo, $id );
			if ( $html ) {
				echo $html;
			}
		}
	}

	public function addScripts() {
		$installer = WP_Installer();
		wp_enqueue_style( 'installer-admin-notices', $installer->res_url() . '/res/css/admin-notices.css', array(), $installer->version() );
	}
}

