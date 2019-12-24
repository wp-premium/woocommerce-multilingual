<?php

class WCML_Multi_Currency_Reports {

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var Sitepress */
	private $sitepress;
	/** @var wpdb */
	private $wpdb;
	/** @var WPML_WP_Cache */
	private $wpml_cache;

	/** @var string $reports_currency */
	protected $reports_currency;

	/**
	 * WCML_Multi_Currency_Reports constructor.
	 *
	 * @param woocommerce_wpml $woocommerce_wpml
	 * @param SitePress $sitepress
	 * @param wpdb $wpdb
	 * @param WPML_WP_Cache $wpml_cache
	 */
	public function __construct( woocommerce_wpml $woocommerce_wpml, Sitepress $sitepress, wpdb $wpdb, $wpml_cache = null ) {

		$this->woocommerce_wpml = $woocommerce_wpml;
		$this->sitepress        = $sitepress;
		$this->wpdb             = $wpdb;

		$cache_group      = 'WCML_Multi_Currency_Reports';
		$this->wpml_cache = $wpml_cache;
		if ( null === $wpml_cache ) {
			$this->wpml_cache = new WPML_WP_Cache( $cache_group );
		}

	}

	public function add_hooks() {
		if ( is_admin() ) {
			add_filter( 'init', array( $this, 'reports_init' ) );
			add_action( 'wp_ajax_wcml_reports_set_currency', array( $this, 'set_reports_currency' ) );

			add_action( 'wc_reports_tabs', array( $this, 'reports_currency_selector' ) );

			if ( current_user_can( 'view_woocommerce_reports' ) ||
			     current_user_can( 'manage_woocommerce' ) ||
			     current_user_can( 'publish_shop_orders' )
			) {

				add_filter( 'woocommerce_dashboard_status_widget_sales_query', array(
					$this,
					'filter_dashboard_status_widget_sales_query'
				) );
				add_filter( 'woocommerce_dashboard_status_widget_top_seller_query', array(
					$this,
					'filter_dashboard_status_widget_sales_query'
				) );
			}

			add_action( 'current_screen', array( $this, 'admin_screen_loaded' ), 10, 1 );
		}
	}

	public function admin_screen_loaded( $screen ) {

		if ( $screen->id === 'dashboard' ) {
			add_filter( 'woocommerce_reports_get_order_report_query', array(
				$this,
				'filter_dashboard_status_widget_sales_query'
			) ); // woocommerce 2.6
		}

	}

	public function reports_init() {
		if ( isset( $_GET['page'] ) && 'wc-reports' === $_GET['page'] ) { //wc-reports - 2.1.x, woocommerce_reports 2.0.x

			add_filter( 'woocommerce_reports_get_order_report_query', array( $this, 'admin_reports_query_filter' ) );

			$wcml_reports_set_currency_nonce = wp_create_nonce( 'reports_set_currency' );

			wc_enqueue_js( "
                jQuery('#dropdown_shop_report_currency').on('change', function(){
                    jQuery('#dropdown_shop_report_currency_chosen').after('&nbsp;' + icl_ajxloaderimg);
                    jQuery('#dropdown_shop_report_currency_chosen a.chosen-single').css('color', '#aaa');
                    jQuery.ajax({
                        url: ajaxurl,
                        type: 'post',
                        data: {
                            action: 'wcml_reports_set_currency',
                            currency: jQuery('#dropdown_shop_report_currency').val(),
                            wcml_nonce: '" . $wcml_reports_set_currency_nonce . "'
                            },
                        success: function( response ){
                            if(typeof response.error !== 'undefined'){
                                alert(response.error);
                            }else{
                               window.location = window.location.href;
                            }
                        }
                    })
                });
            " );

			$this->reports_currency = isset( $_COOKIE['_wcml_reports_currency'] ) ? $_COOKIE['_wcml_reports_currency'] : wcml_get_woocommerce_currency_option();

			// Validation.
			$this->reports_currency = $this->woocommerce_wpml->multi_currency->get_currency_code();

			add_filter( 'woocommerce_currency_symbol', array( $this, '_set_reports_currency_symbol' ) );
		}
	}

	public function admin_reports_query_filter( $query ) {

		$query['join']  .= " LEFT JOIN {$this->wpdb->postmeta} AS meta_order_currency ON meta_order_currency.post_id = posts.ID ";
		$query['where'] .= sprintf( " AND meta_order_currency.meta_key='_order_currency' AND meta_order_currency.meta_value = '%s' ",
			$this->reports_currency );

		return $query;
	}

	public function _set_reports_currency_symbol( $currency ) {
		static $no_recur = false;
		if ( ! empty( $this->reports_currency ) && empty( $no_recur ) ) {
			$no_recur = true;
			$currency = get_woocommerce_currency_symbol( $this->reports_currency );
			$no_recur = false;
		}

		return $currency;
	}

	public function set_reports_currency() {

		$nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'reports_set_currency' ) ) {
			echo json_encode( array( 'error' => __( 'Invalid nonce', 'woocommerce-multilingual' ) ) );
			die();
		}

		$cookie_name = '_wcml_reports_currency';
		// @todo uncomment or delete when #wpmlcore-5796 is resolved
		// do_action( 'wpsc_add_cookie', $cookie_name );
		setcookie( $cookie_name, filter_input( INPUT_POST, 'currency', FILTER_SANITIZE_FULL_SPECIAL_CHARS ),
			time() + 86400, COOKIEPATH, COOKIE_DOMAIN );

		exit;

	}

	public function reports_currency_selector() {
		$currency_codes = $this->woocommerce_wpml->multi_currency->get_currency_codes();
		$currencies     = get_woocommerce_currencies();

		// Remove filter temporary.
		remove_filter( 'woocommerce_currency_symbol', array( $this, '_set_reports_currency_symbol' ) );
		?>
        <select id="dropdown_shop_report_currency" style="margin-left:5px;">
			<?php if ( empty( $currency_codes ) ): ?>
                <option value=""><?php _e( 'Currency - no orders found', 'woocommerce-multilingual' ) ?></option>
			<?php else: ?>
				<?php foreach ( $currency_codes as $currency ): ?>
                    <option value="<?php echo $currency ?>" <?php selected( $currency, $this->reports_currency ); ?>>
						<?php printf( "%s (%s)", $currencies[ $currency ], get_woocommerce_currency_symbol( $currency ) ) ?>
                    </option>
				<?php endforeach; ?>
			<?php endif; ?>
        </select>
		<?php

		// Add filter back.
		add_filter( 'woocommerce_currency_symbol', array( $this, '_set_reports_currency_symbol' ) );
	}

	/*
	* Filter WC dashboard status query
	*
	* @param string $query Query to filter
	*
	* @return string
	*/
	public function filter_dashboard_status_widget_sales_query( $query ) {

		$currency = $this->woocommerce_wpml->multi_currency->admin_currency_selector->get_cookie_dashboard_currency();

		$query['join'] .= " INNER JOIN {$this->wpdb->postmeta} AS currency_postmeta ON posts.ID = currency_postmeta.post_id";
		$query['where'] .= $this->wpdb->prepare( " AND currency_postmeta.meta_key = '_order_currency' AND currency_postmeta.meta_value = %s", $currency );

		return $query;
	}

}
