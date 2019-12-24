<?php

class WCML_Emails {

	private $order_id = false;
	private $locale = false;
	private $admin_language = false;
	/** @var WCML_WC_Strings */
	private $wcmlStrings;
	/** @var Sitepress */
	private $sitepress;
	/** @var \WC_Emails $wcEmails */
	private $wcEmails;
	/** @var wpdb */
	private $wpdb;

	function __construct( WCML_WC_Strings $wcmlStrings, SitePress $sitepress, WC_Emails $wcEmails, wpdb $wpdb ) {
		$this->wcmlStrings = $wcmlStrings;
		$this->sitepress   = $sitepress;
		$this->wcEmails    = $wcEmails;
		$this->wpdb        = $wpdb;
	}

	function add_hooks() {
		//wrappers for email's header
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			add_action( 'woocommerce_order_status_completed_notification', array(
				$this,
				'email_heading_completed'
			), 9 );
			add_action( 'woocommerce_order_status_changed', array( $this, 'comments_language' ), 10 );
		}

		add_action( 'woocommerce_new_customer_note_notification', array( $this, 'email_heading_note' ), 9 );

		add_action( 'wp_ajax_woocommerce_mark_order_status', array( $this, 'email_refresh_in_ajax' ), 9 );

		foreach ( array( 'pending', 'failed', 'cancelled', 'on-hold' ) as $state ) {
			add_action( 'woocommerce_order_status_' . $state . '_to_processing_notification', array(
				$this,
				'email_heading_processing'
			), 9 );

			add_action( 'woocommerce_order_status_' . $state . '_to_processing_notification', array(
				$this,
				'refresh_email_lang'
			), 9 );
		}

		foreach ( array( 'pending', 'failed', 'cancelled' ) as $state ) {
			add_action( 'woocommerce_order_status_' . $state . '_to_on-hold_notification', array(
				$this,
				'email_heading_on_hold'
			), 9 );
		}

		//wrappers for email's body
		add_action( 'woocommerce_before_resend_order_emails', array( $this, 'email_header' ) );
		add_action( 'woocommerce_after_resend_order_email', array( $this, 'email_footer' ) );

		//filter string language before for emails
		add_filter( 'icl_current_string_language', array( $this, 'icl_current_string_language' ), 10, 2 );

		//change order status
		add_action( 'woocommerce_order_status_completed', array( $this, 'refresh_email_lang_complete' ), 9 );

		add_action( 'woocommerce_order_status_pending_to_on-hold_notification', array(
			$this,
			'refresh_email_lang'
		), 9 );
		add_action( 'woocommerce_new_customer_note', array( $this, 'refresh_email_lang' ), 9 );

		foreach ( array( 'pending', 'failed' ) as $from_state ) {
			foreach ( array( 'processing', 'completed', 'on-hold' ) as $to_state ) {
				add_action( 'woocommerce_order_status_' . $from_state . '_to_' . $to_state . '_notification', array(
					$this,
					'new_order_admin_email'
				), 9 );
			}
		}

		add_action( 'woocommerce_before_resend_order_emails', array( $this, 'backend_new_order_admin_email' ), 9 );

		add_filter( 'icl_st_admin_string_return_cached', array( $this, 'admin_string_return_cached' ), 10, 2 );

		add_filter( 'plugin_locale', array( $this, 'set_locale_for_emails' ), 10, 2 );
		add_filter( 'woocommerce_countries', array( $this, 'translate_woocommerce_countries' ) );

		add_filter( 'woocommerce_allow_send_queued_transactional_email', array(
			$this,
			'send_queued_transactional_email'
		), 10, 3 );

		add_action( 'woocommerce_order_partially_refunded_notification', array( $this, 'refresh_email_lang' ), 9 );
		add_action( 'woocommerce_order_fully_refunded_notification', array( $this, 'refresh_email_lang' ), 9 );
		add_filter( 'woocommerce_email_get_option', array( $this, 'filter_refund_emails_strings' ), 10, 4 );

		add_filter( 'woocommerce_email_setup_locale', '__return_false' );
		add_filter( 'woocommerce_email_restore_locale', '__return_false' );


		add_filter( 'woocommerce_email_heading_new_order', array( $this, 'new_order_email_heading' ) );
		add_filter( 'woocommerce_email_subject_new_order', array( $this, 'new_order_email_subject' ) );

		add_filter( 'woocommerce_email_heading_customer_on_hold_order', array(
			$this,
			'customer_on_hold_order_heading'
		) );
		add_filter( 'woocommerce_email_subject_customer_on_hold_order', array(
			$this,
			'customer_on_hold_order_subject'
		) );

		add_filter( 'woocommerce_email_heading_customer_processing_order', array(
			$this,
			'customer_processing_order_heading'
		) );
		add_filter( 'woocommerce_email_subject_customer_processing_order', array(
			$this,
			'customer_processing_order_subject'
		) );

		add_action( 'woocommerce_low_stock_notification', array( $this, 'low_stock_admin_notification' ), 9 );
		add_action( 'woocommerce_no_stock_notification', array( $this, 'no_stock_admin_notification' ), 9 );
	}

	function email_refresh_in_ajax() {
		if ( isset( $_GET['order_id'] ) ) {
			$this->refresh_email_lang( $_GET['order_id'] );

			if ( isset( $_GET['status'] ) && 'completed' === $_GET['status'] ) {
				$this->email_heading_completed( $_GET['order_id'], true );
			}

			return true;
		}
	}

	function refresh_email_lang_complete( $order_id ) {

		$this->order_id = $order_id;
		$this->refresh_email_lang( $order_id );
		$this->email_heading_completed( $order_id, true );
	}

	/**
	 * Translate WooCommerce emails.
	 *
	 * @global type $sitepress
	 * @global type $order_id
	 * @return type
	 */
	function email_header( $order ) {

		if ( is_array( $order ) ) {
			$order = $order['order_id'];
		} elseif ( is_object( $order ) ) {
			$order = method_exists( 'WC_Order', 'get_id' ) ? $order->get_id() : $order->id;
		}

		$this->refresh_email_lang( $order );
	}


	function refresh_email_lang( $order_id ) {

		if ( is_array( $order_id ) ) {
			if ( isset( $order_id['order_id'] ) ) {
				$order_id = $order_id['order_id'];
			} else {
				return;
			}
		}

		$lang = get_post_meta( $order_id, 'wpml_language', true );
		if ( ! empty( $lang ) ) {
			$this->change_email_language( $lang );
		}
	}

	/**
	 * After email translation switch language to default.
	 */
	function email_footer() {
		$this->sitepress->switch_lang( $this->sitepress->get_default_language() );
	}

	public function comments_language() {

		if ( is_admin() && false !== $this->admin_language ) {
			$this->change_email_language( $this->admin_language );
		} else {
			$this->change_email_language( $this->wcmlStrings->get_domain_language( 'woocommerce' ) );
		}
	}


	function email_heading_completed( $order_id, $no_checking = false ) {
		$email = $this->getEmailObject( 'WC_Email_Customer_Completed_Order', $no_checking );

		if ( $email ) {
			$translate = $this->getTranslatorFor(
				'admin_texts_woocommerce_customer_completed_order_settings',
				'[woocommerce_customer_completed_order_settings]'
			);

			$email->heading              = $translate( 'heading' );
			$email->subject              = $translate( 'subject' );
			$email->heading_downloadable = $translate( 'heading_downloadable' );
			$email->subject_downloadable = $translate( 'subject_downloadable' );
			$original_enabled_state      = $email->enabled;
			$email->enabled              = false;
			$email->trigger( $order_id );
			$email->enabled              = $original_enabled_state;
		}
	}

	function email_heading_processing( $order_id ) {
		$this->translate_email_headings( $order_id, 'WC_Email_Customer_Processing_Order', 'woocommerce_customer_processing_order_settings' );
	}

	public function customer_processing_order_heading( $heading ) {
		return $this->get_translated_order_strings( 'heading', $heading, 'WC_Email_Customer_Processing_Order' );
	}

	public function customer_processing_order_subject( $subject ) {
		return $this->get_translated_order_strings( 'subject', $subject, 'WC_Email_Customer_Processing_Order' );
	}


	public function email_heading_on_hold( $order_id ) {
		$this->translate_email_headings( $order_id, 'WC_Email_Customer_On_Hold_Order', 'woocommerce_customer_on_hold_order_settings' );
	}

	/**
	 * @param int|string $order_id
	 * @param string $class_name
	 * @param string $string_name
	 */
	private function translate_email_headings( $order_id, $class_name, $string_name ) {
		$email = $this->getEmailObject( $class_name );

		if ( $email ) {
			$translate = $this->getTranslatorFor(
				'admin_texts_' . $string_name,
				'[' . $string_name . ']',
				$order_id
			);

			$email->heading         = $translate( 'heading' );
			$email->subject         = $translate( 'subject' );
			$original_enabled_state = $email->enabled;
			$email->enabled         = false;
			$email->trigger( $order_id );
			$email->enabled = $original_enabled_state;
		}
	}

	public function customer_on_hold_order_heading( $heading ) {
		return $this->get_translated_order_strings( 'heading', $heading, 'WC_Email_Customer_On_Hold_Order' );
	}

	public function customer_on_hold_order_subject( $subject ) {
		return $this->get_translated_order_strings( 'subject', $subject, 'WC_Email_Customer_On_Hold_Order' );
	}

	function email_heading_note( $args ) {
		$email = $this->getEmailObject( 'WC_Email_Customer_Note' );

		if ( $email ) {
			$translate = $this->getTranslatorFor(
				'admin_texts_woocommerce_customer_note_settings',
				'[woocommerce_customer_note_settings]'
			);

			$email->heading         = $translate( 'heading' );
			$email->subject         = $translate( 'subject' );
			$original_enabled_state = $email->enabled;
			$email->enabled         = false;
			$email->trigger( $args );
			$email->enabled = $original_enabled_state;
		}
	}

	public function filter_refund_emails_strings( $value, $object, $old_value, $key ) {

		if ( in_array( $key, array(
				'subject_partial',
				'subject_full',
				'heading_partial',
				'heading_full'
			) ) && $object->object ) {
			$translated_value = $this->get_refund_email_translated_string( $key, $object );
		}

		return ! empty( $translated_value ) ? $translated_value : $value;
	}

	public function get_refund_email_translated_string( $key, $object ) {

		return $this->wcml_get_translated_email_string( 'admin_texts_woocommerce_customer_refunded_order_settings',
			'[woocommerce_customer_refunded_order_settings]' . $key, $object->object->get_id() );

	}

	function new_order_admin_email( $order_id ) {
		$email = $this->getEmailObject( 'WC_Email_New_Order', true );

		if ( $email ) {
			$recipients = explode( ',', $email->get_recipient() );
			foreach ( $recipients as $recipient ) {
				/**
				 * Filter new order admin email language for recipient
				 *
				 * @since 4.7.0
				 *
				 * @param string $admin_language Admin language
				 * @param string $recipient Admin email
				 * @param int $order_id Order ID
				 */
				$admin_language = apply_filters( 'wcml_new_order_admin_email_language', $this->get_admin_language_by_email( $recipient ), $recipient, $order_id );

				$this->change_email_language( $admin_language );

				$translate = $this->getTranslatorFor(
					'admin_texts_woocommerce_new_order_settings',
					'[woocommerce_new_order_settings]',
					$order_id,
					$admin_language
				);

				$email->heading   = $translate( 'heading' );
				$email->subject   = $translate( 'subject' );
				$email->recipient = $recipient;

				$email->trigger( $order_id );
			}
			$email->enabled = false;
			$this->refresh_email_lang( $order_id );
		}
	}

	/**
	 * @param string $recipient
	 *
	 * @return string
	 */
	private function get_admin_language_by_email( $recipient ){
		$user = get_user_by( 'email', $recipient );
		if ( $user ) {
			return $this->sitepress->get_user_admin_language( $user->ID, true );
		} else {
			return $this->sitepress->get_default_language();
		}
	}

	public function new_order_email_heading( $heading ) {
		return $this->get_translated_order_strings( 'heading', $heading, 'WC_Email_New_Order' );
	}

	public function new_order_email_subject( $subject ) {
		return $this->get_translated_order_strings( 'subject', $subject, 'WC_Email_New_Order' );
	}

	/**
	 * @param string $type
	 * @param string $string
	 * @param string $class_name
	 *
	 * @return string
	 */
	private function get_translated_order_strings( $type, $string, $class_name ) {
		$email = $this->getEmailObject( $class_name );

		if ( 'heading' === $type ) {
			$translated_string = $email->heading;
		} elseif ( 'subject' === $type ) {
			$translated_string = $email->subject;
		} else {
			return $string;
		}

		return $translated_string ? $email->format_string( $translated_string ) : $string;
	}

	public function backend_new_order_admin_email( $order_id ) {
		if ( isset( $_POST['wc_order_action'] ) && in_array( $_POST['wc_order_action'], array(
				'send_email_new_order',
				'send_order_details_admin'
			) ) ) {
			$this->new_order_admin_email( $order_id );
		}
	}

	function change_email_language( $lang ) {
		if ( ! $this->admin_language ) {
			$this->admin_language = $this->sitepress->get_user_admin_language( get_current_user_id(), true );
		}

		$this->sitepress->switch_lang( $lang, true );
		$this->locale = $this->sitepress->get_locale( $lang );
	}

	function admin_string_return_cached( $value, $option ) {
		if ( in_array( $option, array( 'woocommerce_email_from_address', 'woocommerce_email_from_name' ) ) ) {
			return false;
		}

		return $value;
	}

	function wcml_get_translated_email_string( $context, $name, $order_id = false, $language_code = null ) {

		if ( $order_id && ! $language_code ) {
			$order_language = get_post_meta( $order_id, 'wpml_language', true );
			if ( $order_language ) {
				$language_code = $order_language;
			}
		}
		$result = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT value FROM {$this->wpdb->prefix}icl_strings WHERE context = %s AND name = %s ", $context, $name ) );

		return apply_filters( 'wpml_translate_single_string', $result, $context, $name, $language_code );
	}

	function icl_current_string_language( $current_language, $name ) {
		$order_id = false;

		if ( isset( $_POST['action'] ) && $_POST['action'] == 'editpost' && isset( $_POST['post_type'] ) && $_POST['post_type'] == 'shop_order' && isset( $_POST['wc_order_action'] ) && $_POST['wc_order_action'] != 'send_email_new_order' ) {
			$order_id = filter_input( INPUT_POST, 'post_ID', FILTER_SANITIZE_NUMBER_INT );
		} elseif ( isset( $_POST['action'] ) && $_POST['action'] == 'woocommerce_add_order_note' && isset( $_POST['note_type'] ) && $_POST['note_type'] == 'customer' ) {
			$order_id = filter_input( INPUT_POST, 'post_id', FILTER_SANITIZE_NUMBER_INT );
		} elseif ( isset( $_GET['action'] ) && isset( $_GET['order_id'] ) && ( $_GET['action'] == 'woocommerce_mark_order_complete' || $_GET['action'] == 'woocommerce_mark_order_status' ) ) {
			$order_id = filter_input( INPUT_GET, 'order_id', FILTER_SANITIZE_NUMBER_INT );
		} elseif ( isset( $_GET['action'] ) && $_GET['action'] == 'mark_completed' && $this->order_id ) {
			$order_id = $this->order_id;
		} elseif ( isset( $_POST['action'] ) && $_POST['action'] == 'woocommerce_refund_line_items' ) {
			$order_id = filter_input( INPUT_POST, 'order_id', FILTER_SANITIZE_NUMBER_INT );
		} elseif ( empty( $_POST ) && isset( $_GET['page'] ) && $_GET['page'] == 'wc-settings' && isset( $_GET['tab'] ) && $_GET['tab'] == 'email' && substr( $name, 0, 12 ) == '[woocommerce' ) {
			$email_string = explode( ']', str_replace( '[', '', $name ) );
			$email_option = get_option( $email_string[0], true );
			$context      = 'admin_texts_' . $email_string[0];

			$current_language = $this->wcmlStrings->get_string_language( $email_option[ $email_string[1] ], $context, $name );
		} elseif ( $this->order_id ) {
			$order_id = $this->order_id;
		}

		$order_id = apply_filters( 'wcml_send_email_order_id', $order_id );

		if ( $order_id ) {
			$order_language = get_post_meta( $order_id, 'wpml_language', true );
			if ( $order_language ) {
				$current_language = $order_language;
			} else {
				$current_language = $this->sitepress->get_current_language();
			}
		}

		return apply_filters( 'wcml_email_language', $current_language, $order_id );
	}

	// set correct locale code for emails
	function set_locale_for_emails( $locale, $domain ) {

		if ( $domain == 'woocommerce' && $this->locale ) {
			$locale = $this->locale;
		}

		return $locale;
	}

	function translate_woocommerce_countries( $countries ) {

		if ( isset( $_POST['wc_order_action'] ) && $_POST['wc_order_action'] !== 'send_email_new_order' && isset( $_POST['post_ID'] ) ) {
			$current_language = $this->sitepress->get_current_language();
			$this->refresh_email_lang( $_POST['post_ID'] );
			$countries = include( WC()->plugin_path() . '/i18n/countries.php' );
			$this->change_email_language( $current_language );
		}

		return $countries;
	}


	function send_queued_transactional_email( $allow, $filter, $args ) {
		$this->order_id = $args[0];

		return $allow;
	}

	/**
	 * @param string $emailClass
	 * @param bool   $ignoreClassExists
	 *
	 * @return WC_Email|null
	 */
	private function getEmailObject( $emailClass, $ignoreClassExists = false ) {
		if (
			( $ignoreClassExists || class_exists( $emailClass ) )
			&& isset( $this->wcEmails->emails[ $emailClass ] )
		) {
			return $this->wcEmails->emails[ $emailClass ];
		}

		return null;
	}

	/**
	 * @param string      $domain
	 * @param string      $namePrefix
	 * @param int|false   $orderId
	 * @param string|null $languageCode
	 *
	 * @return Closure
	 */
	private function getTranslatorFor( $domain, $namePrefix, $orderId = false, $languageCode = null ) {
		return function( $field ) use ( $domain, $namePrefix, $orderId, $languageCode ) {
			return $this->wcml_get_translated_email_string( $domain, $namePrefix . $field, $orderId, $languageCode );
		};
	}

	/**
	 * @param WC_Product $product
	 */
	public function low_stock_admin_notification( $product ) {
		$this->admin_notification( $product, 'woocommerce_low_stock_notification', 'low_stock' );
	}

	/**
	 * @param WC_Product $product
	 */
	public function no_stock_admin_notification( $product ) {
		$this->admin_notification( $product, 'woocommerce_no_stock_notification', 'no_stock' );
	}

	/**
	 * @param WC_Product $product
	 * @param string $action
	 * @param string $method
	 */
	private function admin_notification( $product, $action, $method ) {

		$is_action_removed = remove_action( $action, [ $this->wcEmails, $method ] );

		if ( $is_action_removed ) {
			$admin_language               = $this->get_admin_language_by_email( get_option( 'woocommerce_stock_email_recipient' ) );
			$product_id_in_admin_language = wpml_object_id_filter(
				$product->get_id(),
				'product',
				true,
				$admin_language
			);

			$this->sitepress->switch_lang( $admin_language );
			$this->wcEmails->$method( wc_get_product( $product_id_in_admin_language ) );
			$this->sitepress->switch_lang();
		}
	}
}