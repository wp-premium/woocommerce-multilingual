<?php

namespace WCML\AdminDashboard;

use SitePress;
use wpdb;
use IWPML_DIC_Action;
use IWPML_Backend_Action;

class Hooks implements IWPML_Backend_Action, IWPML_DIC_Action {

	/** @var SitePress $sitepress */
	private $sitepress;

	/** @var wpdb $wpdb */
	private $wpdb;

	public function __construct( SitePress $sitepress, wpdb $wpdb ) {
		$this->sitepress = $sitepress;
		$this->wpdb      = $wpdb;
	}

	public function add_hooks() {
		add_action( 'wp_dashboard_setup', [ $this, 'clearStockTransients' ] );
		add_filter( 'woocommerce_status_widget_low_in_stock_count_query', [ $this, 'addLanguageQuery' ] );
		add_filter( 'woocommerce_status_widget_out_of_stock_count_query', [ $this, 'addLanguageQuery' ] );
	}

	public function clearStockTransients() {
		delete_transient( 'wc_outofstock_count' );
		delete_transient( 'wc_low_stock_count' );
	}

	/**
	 * @param string $query
	 *
	 * @return string
	 */
	public function addLanguageQuery( $query ) {

		$currentLanguage = $this->sitepress->get_current_language();

		if ( $currentLanguage !== 'all' ) {
			$languageQuery = $this->wpdb->prepare(
				" INNER JOIN {$this->wpdb->prefix}icl_translations AS t
                ON posts.ID = t.element_id AND t.element_type IN ( 'post_product', 'post_product_variation' )
                WHERE t.language_code = %s AND ",
				$currentLanguage );

			return str_replace( 'WHERE', $languageQuery, $query );
		}

		return $query;
	}
}
