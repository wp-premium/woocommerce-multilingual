<?php

/**
 * Class WCML_Screen_Options
 */
class WCML_Products_Screen_Options extends WPML_Templates_Factory {

	/**
	 * @var SitePress
	 */
	private $sitepress;

	/**
	 * WCML_Screen_Options constructor.
	 *
	 * @param $sitepress
	 */
	public function __construct( &$sitepress ) {
		parent::__construct();
		$this->sitepress = $sitepress;
	}

	/**
	 * Setup hooks.
	 */
	public function init() {
		add_filter( 'default_hidden_columns',      array( $this, 'filter_screen_options' ), 10, 2 );
		add_filter( 'wpml_hide_management_column', array( $this, 'sitepress_screen_option_filter' ), 10, 2 );
		add_action( 'admin_init',                  array( $this, 'save_translation_controls' ), 10, 1 );
		add_action( 'admin_notices',               array( $this, 'product_page_admin_notices' ), 10 );
		add_action( 'wp_ajax_dismiss-notice',      array( $this, 'dismiss_notice_permanently' ), 10 );
		add_action( 'wp_ajax_hidden-columns',      array( $this, 'dismiss_notice_on_screen_option_change' ), 0 );
	}

	/**
	 * Hide management column by default for products.
	 *
	 * @param $is_visible
	 * @param $post_type
	 *
	 * @return bool
	 */
	public function sitepress_screen_option_filter( $is_visible, $post_type ) {
		if ( 'product' === $post_type ) {
			$is_visible = false;
		}

		return $is_visible;
	}

	/**
	 * Set default option for translations management column.
	 *
	 * @param $hidden
	 * @param $screen
	 *
	 * @return array
	 */
	public function filter_screen_options( $hidden, $screen ) {
		if ( 'edit-product' === $screen->id ) {
			$hidden[] = 'icl_translations';
		}
		return $hidden;
	}

	/**
	 * Save user options for management column.
	 */
	public function save_translation_controls() {
		if ( isset( $_GET['translation_controls'] )
		     && isset( $_GET['nonce'] )
		     && wp_verify_nonce( $_GET['nonce'], 'enable_translation_controls' )
		) {
			$user = get_current_user_id();
			$hidden_columns = get_user_meta( $user, 'manageedit-productcolumnshidden', true );
			if ( ! is_array( $hidden_columns ) ) {
				$hidden_columns = array();
			}
			if ( 0 === (int) $_GET['translation_controls'] ) {
				$hidden_columns[] = 'icl_translations';
			} else {
				$tr_control_index = array_search( 'icl_translations', $hidden_columns );
				if ( false !== $tr_control_index ) {
					unset( $hidden_columns[ $tr_control_index ] );
				}
			}

			update_user_meta( $user, 'manageedit-productcolumnshidden', $hidden_columns );
			$this->sitepress->get_wp_api()->wp_safe_redirect( admin_url( 'edit.php?post_type=product' ), 301 );
		}
	}

	/**
	 * Display admin notice for translation management column.
	 */
	public function product_page_admin_notices() {
		$current_screen = get_current_screen();
		if ( 'edit-product' === $current_screen->id && $this->has_products() ) {
			$this->show();
		}
	}

	/**
	 * Get model for view.
	 *
	 * @return array
	 */
	public function get_model() {
		$translate_url = esc_url_raw( admin_url( 'admin.php?page=wpml-wcml' ) );
		$nonce         = wp_create_nonce( 'enable_translation_controls' );
		$button_url    = esc_url_raw( admin_url( 'edit.php?post_type=product&translation_controls=0&nonce=' . $nonce ) );
		$button_text   = __( 'Disable translation controls',  'woocommerce-multilingual' );
		$first_line    = __( 'Translation controls are enabled.', 'woocommerce-multilingual' );
		$second_line   = sprintf( __( "Disabling the translation controls will make this page load faster.\nThe best place to translate products is in WPML-&gt;WooCommerce Multilingual %sproducts translation dashboard%s.", 'woocommerce-multilingual' ), '<a href="' . $translate_url . '">', '</a>' );
		$show_notice   = ( 1 === (int) get_user_meta( get_current_user_id(), 'screen-option-enabled-notice-dismissed', true ) ) ? false : true;
		$div_id        = 'enabled';
		if ( method_exists( $this->sitepress, 'show_management_column_content' ) && false === $this->sitepress->show_management_column_content( 'product' ) ) {
			$button_url = admin_url( 'edit.php?post_type=product&translation_controls=1&nonce=' . $nonce );
			$button_text = __( 'Enable translation controls anyway',  'woocommerce-multilingual' );
			$first_line    = __( 'Translation controls are disabled.', 'woocommerce-multilingual' );
			$second_line   = sprintf( __( "Enabling the translation controls in this page can increase the load time for this admin screen.\n The best place to translate products is in WPML-&gt;WooCommerce Multilingual %sproducts translation dashboard%s.", 'woocommerce-multilingual' ), '<a href="' . $translate_url . '">', '</a>' );
			$show_notice   = ( 1 === (int) get_user_meta( get_current_user_id(), 'screen-option-disabled-notice-dismissed', true ) ) ? false : true;
			$div_id        = 'disabled';
		}
		$model = array(
			'first_line'   => $first_line,
			'second_line'  => $second_line,
			'button_url'   => $button_url,
			'button_text'  => $button_text,
			'show_notice'  => $show_notice,
			'div_id'       => $div_id,
		);

		return $model;
	}

	/**
	 * Get template directory path.
	 */
	protected function init_template_base_dir() {
		$this->template_paths = array(
			WCML_PLUGIN_PATH . '/templates/products-list/',
		);
	}

	/**
	 * Get template file name.
	 *
	 * @return string
	 */
	public function get_template() {
		return 'admin-notice.twig';
	}

	public function dismiss_notice_permanently() {
		if ( defined( 'DOING_AJAX' )
		     && DOING_AJAX
		     && isset( $_POST['nonce'] )
		     && wp_verify_nonce( $_POST['nonce'], 'products-screen-option-action' )
		     && isset( $_POST['dismiss_notice'] )
		) {
			$user = get_current_user_id();
			if ( 'enabled' === $_POST['dismiss_notice'] ) {
				update_user_meta( $user, 'screen-option-enabled-notice-dismissed', 1 );
			}

			if ( 'disabled' === $_POST['dismiss_notice'] ) {
				update_user_meta( $user, 'screen-option-disabled-notice-dismissed', 1 );
			}
		}
	}

	/**
	 * Dismiss notices when screen option is updated manually.`
	 */
	public function dismiss_notice_on_screen_option_change() {
		if ( defined( 'DOING_AJAX' )
		     && DOING_AJAX
		     && isset( $_POST['page'] )
		     && 'edit-product' === $_POST['page']
		     && check_ajax_referer( 'screen-options-nonce', 'screenoptionnonce' )
		     && isset( $_POST['hidden'] )
		     && '' === $_POST['hidden']
		) {
			$user = get_current_user_id();
			update_user_meta( $user, 'screen-option-enabled-notice-dismissed', 1 );
			update_user_meta( $user, 'screen-option-disabled-notice-dismissed', 1 );
		}
	}

	/**
	 * Check if there is at least on product added.
	 *
	 * @return bool
	 */
	public function has_products() {
		$has_products = false;
		$args = array(
			'post_type'              => 'product',
			'posts_per_page'         => 1,
			'no_found_rows'          => true,
			'fields'                 => 'ids',
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'suppress_filters'       => false,
		);
		$query = new WP_Query( $args );
		if ( $query->have_posts() ) {
			$has_products = true;
		}
		wp_reset_postdata();

		return $has_products;
	}
}
