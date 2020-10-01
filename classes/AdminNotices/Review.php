<?php

namespace WCML\AdminNotices;

use WPML_Notices;
use IWPML_Backend_Action;
use IWPML_Frontend_Action;
use IWPML_DIC_Action;
use wpdb;
use SitePress;

class Review implements IWPML_Backend_Action, IWPML_Frontend_Action, IWPML_DIC_Action {

	const OPTION_NAME = 'wcml-rate-notice';

	/** @var WPML_Notices $wpmlNotices */
	private $wpmlNotices;

	/** @var wpdb $wpdb */
	private $wpdb;

	/** @var SitePress $sitepress */
	private $sitepress;

	/**
	 * Review constructor.
	 *
	 * @param WPML_Notices $wpmlNotices
	 * @param wpdb $wpdb
	 * @param SitePress $sitepress
	 */
	public function __construct( WPML_Notices $wpmlNotices, wpdb $wpdb, SitePress $sitepress ) {
		$this->wpmlNotices = $wpmlNotices;
		$this->wpdb        = $wpdb;
		$this->sitepress   = $sitepress;
	}

	/**
	 * add hooks
	 */
	public function add_hooks() {
		add_action( 'admin_notices', [ $this, 'addNotice' ] );
		add_action( 'woocommerce_after_order_object_save', [ $this, 'onNewOrder' ] );
	}

	/**
	 * add notice message
	 */
	public function addNotice() {

		if ( $this->shouldDisplayNotice() ) {
			$notice = $this->wpmlNotices->get_new_notice( 'wcml-rate', $this->getNoticeText(), 'wcml-admin-notices' );

			if ( $this->wpmlNotices->is_notice_dismissed( $notice ) ) {
				return;
			}

			$notice->set_css_class_types( 'info' );
			$notice->set_css_classes( [ 'otgs-notice-wcml-rating' ] );
			$notice->set_dismissible( true );

			$reviewLink   = 'https://wordpress.org/support/plugin/woocommerce-multilingual/reviews/?filter=5#new-post';
			$reviewButton = $this->wpmlNotices->get_new_notice_action( __( 'Review WooCommerce Multilingual', 'woocommerce-multilingual' ), $reviewLink, false, false, true );
			$notice->add_action( $reviewButton );

			$notice->set_restrict_to_screen_ids( $this->getRestrictedScreenIds() );
			$notice->add_capability_check( [ 'manage_options', 'wpml_manage_woocommerce_multilingual' ] );
			$this->wpmlNotices->add_notice( $notice );
		}
	}

	/**
	 * get screen ids to display notice
	 *
	 * @return array
	 */
	private function getRestrictedScreenIds() {
		return [
			'dashboard',
			'woocommerce_page_wpml-wcml',
			'woocommerce_page_wc-admin',
			'woocommerce_page_wc-reports',
			'woocommerce_page_wc-settings',
			'woocommerce_page_wc-status',
			'woocommerce_page_wc-addons',
			'edit-shop_order',
			'edit-shop_coupon',
			'edit-product',
		];
	}

	/**
	 * get notice text
	 *
	 * @return string
	 */
	private function getNoticeText() {
		$text = '<h2>';
		$text .= __( 'Congrats! You\'ve just earned some money using WooCommerce Multilingual.', 'woocommerce-multilingual' );
		$text .= '</h2>';

		$text .= '<p>';
		$text .= __( 'How do you feel getting your very first order in foreign language or currency?', 'woocommerce-multilingual' );
		$text .= '<br />';
		$text .= __( 'We for sure are super thrilled about your success! Will you help WCML improve and grow?', 'woocommerce-multilingual' );
		$text .= '</p>';

		$text .= '<p><strong>';
		$text .= __( 'Give us <span class="rating">5.0 <i class="otgs-ico-star"></i></span> review now.', 'woocommerce-multilingual' );
		$text .= '</strong></p>';

		return $text;
	}

	/**
	 * check if we should display notice
	 *
	 * @return bool
	 */
	private function shouldDisplayNotice() {
		return get_option( self::OPTION_NAME, false );
	}

	public function onNewOrder(){
		if ( !$this->shouldDisplayNotice() ) {
			$this->maybeAddOptionToShowNotice();
		}
	}

	/**
	 * maybe add option to show notice
	 */
	private function maybeAddOptionToShowNotice() {

		$ordersCountInSecondLanguageOrCurrency = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(p.ID) FROM {$this->wpdb->postmeta} as pm 
				INNER JOIN {$this->wpdb->posts} as p ON pm.post_id = p.ID 
				WHERE p.post_type = 'shop_order' AND ( 
 				( pm.meta_key = '_order_currency' AND pm.meta_value != %s ) 
 				OR 
 				( pm.meta_key = 'wpml_language' AND pm.meta_value != %s  ) )",
				wcml_get_woocommerce_currency_option(),
				$this->sitepress->get_default_language() )
		);

		if ( $ordersCountInSecondLanguageOrCurrency ) {
			add_option( self::OPTION_NAME, true );
		}
	}

}
