<?php

class WCML_WC_Subscriptions {

	private $new_subscription = false;

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var wpdb */
	private $wpdb;

	public function __construct( woocommerce_wpml $woocommerce_wpml, wpdb $wpdb ) {
		$this->woocommerce_wpml = $woocommerce_wpml;
		$this->wpdb             = $wpdb;
	}

	public function add_hooks() {

		add_action( 'init', [ $this, 'init' ], 9 );
		add_filter( 'wcml_variation_term_taxonomy_ids', [ $this, 'wcml_variation_term_taxonomy_ids' ] );
		add_filter( 'woocommerce_subscription_lengths', [ $this, 'woocommerce_subscription_lengths' ], 10, 2 );

		add_filter( 'wcml_register_endpoints_query_vars', [ $this, 'register_endpoint' ], 10, 3 );
		add_filter( 'wcml_endpoint_permalink_filter', [ $this, 'endpoint_permalink_filter' ], 10, 2 );

		// custom prices
		add_filter( 'wcml_custom_prices_fields', [ $this, 'set_prices_fields' ], 10, 2 );
		add_filter( 'wcml_custom_prices_strings', [ $this, 'set_labels_for_prices_fields' ], 10, 2 );
		add_filter( 'wcml_custom_prices_fields_labels', [ $this, 'set_labels_for_prices_fields' ], 10, 2 );
		add_filter( 'wcml_update_custom_prices_values', [ $this, 'update_custom_prices_values' ], 10, 3 );
		add_action( 'wcml_after_custom_prices_block', [ $this, 'new_subscription_prices_block' ] );

		add_action( 'woocommerce_subscriptions_product_options_pricing', [ $this, 'show_pointer_info' ] );
		add_action( 'woocommerce_variable_subscription_pricing', [ $this, 'show_pointer_info' ] );

		add_filter(
			'woocommerce_subscriptions_product_price',
			[
				$this,
				'woocommerce_subscription_price_from',
			],
			10,
			2
		);

		add_filter( 'wcml_xliff_allowed_variations_types', [ $this, 'set_allowed_variations_types_in_xliff' ] );

		// Add language links to email settings
		add_filter( 'wcml_emails_options_to_translate', [ $this, 'translate_email_options' ] );
		add_filter( 'wcml_emails_section_name_prefix', [ $this, 'email_option_section_prefix' ], 10, 2 );

	}

	public function init() {
		if ( ! is_admin() ) {
			add_filter(
				'woocommerce_subscriptions_product_sign_up_fee',
				[
					$this,
					'subscriptions_product_sign_up_fee_filter',
				],
				10,
				2
			);

			add_action( 'woocommerce_before_calculate_totals', [ $this, 'maybe_backup_recurring_carts' ], 1 );
			add_action( 'woocommerce_after_calculate_totals', [ $this, 'maybe_restore_recurring_carts' ], 200 );

			$this->maybe_force_client_currency_for_subscription();

			add_filter( 'wcs_get_subscription', [ $this, 'filter_subscription_items' ] );
		}

		// Translate emails
		add_filter( 'woocommerce_generated_manual_renewal_order_renewal_notification', [ $this, 'translate_renewal_notification' ], 9 );
		add_filter( 'woocommerce_order_status_failed_renewal_notification', [ $this, 'translate_renewal_notification' ], 9 );
	}


	/**
	 * Filter Subscription Sign-up fee cost
	 *
	 * @param string     $subscription_sign_up_fee
	 * @param WC_Product $product
	 * @return string
	 */
	public function subscriptions_product_sign_up_fee_filter( $subscription_sign_up_fee, $product ) {

		if ( $product && wcml_is_multi_currency_on() ) {
			$currency = $this->woocommerce_wpml->multi_currency->get_client_currency();

			if ( $currency !== wcml_get_woocommerce_currency_option() ) {

				$product_id = $product->get_id();
				if( $product instanceof WC_Product_Variable_Subscription ){
					$product_id = $product->get_meta( '_min_price_variation_id', true );
				}

				$original_product_id = $this->woocommerce_wpml->products->get_original_product_id( $product_id );

				if ( get_post_meta( $original_product_id, '_wcml_custom_prices_status', true ) ) {
					$subscription_sign_up_fee = get_post_meta( $original_product_id, '_subscription_sign_up_fee_' . $currency, true );
				} else {
					$subscription_sign_up_fee = apply_filters( 'wcml_raw_price_amount', $subscription_sign_up_fee );
				}
			}
		}

		return $subscription_sign_up_fee;
	}

	public function wcml_variation_term_taxonomy_ids( $get_variation_term_taxonomy_ids ) {

		$get_variation_term_taxonomy_id = $this->wpdb->get_var( "SELECT tt.term_taxonomy_id FROM {$this->wpdb->terms} AS t LEFT JOIN {$this->wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id WHERE t.slug = 'variable-subscription'" );

		if ( ! empty( $get_variation_term_taxonomy_id ) ) {
			$get_variation_term_taxonomy_ids[] = $get_variation_term_taxonomy_id;
		}

		return $get_variation_term_taxonomy_ids;
	}

	public function woocommerce_subscription_lengths( $subscription_ranges, $subscription_period ) {

		if ( is_array( $subscription_ranges ) ) {
			foreach ( $subscription_ranges as $period => $ranges ) {
				if ( is_array( $ranges ) ) {
					foreach ( $ranges as $range ) {
						if ( $range == '9 months' ) {
							$breakpoint = true;
						}
						$new_subscription_ranges[ $period ][] = apply_filters( 'wpml_translate_single_string', $range, 'wc_subscription_ranges', $range );
					}
				}
			}
		}

		return isset( $new_subscription_ranges ) ? $new_subscription_ranges : $subscription_ranges;
	}

	public function set_prices_fields( $fields, $product_id ) {
		if ( $this->is_subscriptions_product( $product_id ) || $this->new_subscription ) {
			$fields[] = '_subscription_sign_up_fee';
		}

		return $fields;

	}

	public function set_labels_for_prices_fields( $labels, $product_id ) {

		if ( $this->is_subscriptions_product( $product_id ) || $this->new_subscription ) {
			$labels['_regular_price']            = __( 'Subscription Price', 'woocommerce-multilingual' );
			$labels['_subscription_sign_up_fee'] = __( 'Sign-up Fee', 'woocommerce-multilingual' );
		}

		return $labels;

	}

	public function update_custom_prices_values( $prices, $code, $variation_id = false ) {

		if ( isset( $_POST['_custom_subscription_sign_up_fee'][ $code ] ) ) {
			$prices['_subscription_sign_up_fee'] = wc_format_decimal( $_POST['_custom_subscription_sign_up_fee'][ $code ] );
		}

		if ( $variation_id && isset( $_POST['_custom_variation_subscription_sign_up_fee'][ $code ][ $variation_id ] ) ) {
			$prices['_subscription_sign_up_fee'] = wc_format_decimal( $_POST['_custom_variation_subscription_sign_up_fee'][ $code ][ $variation_id ] );
		}

		return $prices;

	}

	public function is_subscriptions_product( $product_id ) {

		$get_variation_term_taxonomy_ids = $this->wpdb->get_col( "SELECT tt.term_taxonomy_id FROM {$this->wpdb->terms} AS t LEFT JOIN {$this->wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id WHERE t.slug IN ( 'subscription', 'variable-subscription' ) AND tt.taxonomy = 'product_type'" );

		if ( get_post_type( $product_id ) == 'product_variation' ) {
			$product_id = wp_get_post_parent_id( $product_id );
		}

		$is_subscriptions_product = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT count(object_id) FROM {$this->wpdb->term_relationships}
				WHERE object_id = %d AND term_taxonomy_id IN (" . wpml_prepare_in( $get_variation_term_taxonomy_ids, '%d' ) . ')',
				$product_id
			)
		);
		return $is_subscriptions_product;
	}

	public function new_subscription_prices_block( $product_id ) {

		if ( $product_id == 'new' ) {
			$this->new_subscription = true;
			echo '<div class="wcml_prices_if_subscription" style="display: none">';
			$custom_prices_ui = new WCML_Custom_Prices_UI( $this->woocommerce_wpml, 'new' );
			$custom_prices_ui->show();
			echo '</div>';
			?>
			<script>
				jQuery(document).ready(function($) {
					jQuery('.wcml_prices_if_subscription .wcml_custom_prices_input').attr('name', '_wcml_custom_prices[new_subscription]').attr( 'id', '_wcml_custom_prices[new_subscription]');
					jQuery('.wcml_prices_if_subscription .wcml_custom_prices_options_block>label').attr('for', '_wcml_custom_prices[new_subscription]');
					jQuery('.wcml_prices_if_subscription .wcml_schedule_input').each( function(){
						jQuery(this).attr('name', jQuery(this).attr('name')+'_subscription');
					});

					jQuery('.options_group>.wcml_custom_prices_block .wcml_custom_prices_input:first-child').click();
					jQuery('.options_group>.wcml_custom_prices_block .wcml_schedule_options .wcml_schedule_input:first-child').click();

					jQuery(document).on('change', 'select#product-type', function () {
						if (jQuery(this).val() == 'subscription') {
							jQuery('.wcml_prices_if_subscription').show();
							jQuery('.options_group>.wcml_custom_prices_block').hide();
						} else if (jQuery(this).val() != 'variable-subscription') {
							jQuery('.wcml_prices_if_subscription').hide();
							jQuery('.options_group>.wcml_custom_prices_block').show();
						}
					});

					jQuery(document).on('click', '#publish', function () {
						if ( jQuery('.wcml_prices_if_subscription').is( ':visible' ) ) {
							jQuery('.options_group>.wcml_custom_prices_block').remove();
							jQuery('.wcml_prices_if_subscription .wcml_custom_prices_input').attr('name', '_wcml_custom_prices[new]');
							jQuery('.wcml_prices_if_subscription .wcml_schedule_input').each( function(){
								jQuery(this).attr('name', jQuery(this).attr('name').replace('_subscription','') );
							});
						}else{
							jQuery('.wcml_prices_if_subscription').remove();
						}
					});
				});
			</script>
			<?php
		}
	}

	public function register_endpoint( $query_vars, $wc_vars, $obj ) {

		$query_vars['view-subscription'] = $obj->get_endpoint_translation( 'view-subscription', isset( $wc_vars['view-subscription'] ) ? $wc_vars['view-subscription'] : 'view-subscription' );
		$query_vars['subscriptions']     = $obj->get_endpoint_translation( 'subscriptions', isset( $wc_vars['subscriptions'] ) ? $wc_vars['subscriptions'] : 'subscriptions' );
		return $query_vars;
	}

	public function endpoint_permalink_filter( $endpoint, $key ) {

		if ( $key == 'view-subscription' ) {
			return 'view-subscription';
		}

		return $endpoint;
	}

	public function show_pointer_info() {

		$pointer_ui = new WCML_Pointer_UI(
			sprintf( __( 'You can translate strings related to subscription products on the %1$sWPML String Translation page%2$s. Use the search on the top of that page to find the strings.', 'woocommerce-multilingual' ), '<a href="' . admin_url( 'admin.php?page=' . WPML_ST_FOLDER . '/menu/string-translation.php&context=woocommerce_subscriptions' ) . '">', '</a>' ),
			'https://wpml.org/documentation/woocommerce-extensions-compatibility/translating-woocommerce-subscriptions-woocommerce-multilingual/',
			'general_product_data .subscription_pricing',
			'prepend'
		);

		$pointer_ui->show();
	}

	/**
	 * @param WC_Cart $cart
	 */
	public function maybe_backup_recurring_carts( $cart ) {
		if ( ! empty( $cart->recurring_carts ) ) {
			$this->recurring_carts = $cart->recurring_carts;
		}
	}

	/**
	 * @param WC_Cart $cart
	 */
	public function maybe_restore_recurring_carts( $cart ) {
		if ( ! empty( $this->recurring_carts ) ) {
			$cart->recurring_carts = $this->recurring_carts;
			$this->recurring_carts = null;
		}
	}

	/**
	 * @param string $price
	 * @param WC_Product|WC_Product_Subscription_Variation $product
	 *
	 * @return string
	 */
	public function woocommerce_subscription_price_from( $price, $product ) {

		if ( $product && $product instanceof WC_Product_Subscription_Variation ) {

			$custom_prices_on = get_post_meta( $product->get_id(), '_wcml_custom_prices_status', true );
			if ( $custom_prices_on ) {
				$client_currency = $this->woocommerce_wpml->multi_currency->get_client_currency();

				$price = get_post_meta( $product->get_id(), '_price_' . $client_currency, true );
			} else {
				$price = apply_filters( 'wcml_raw_price_amount', $price );
			}
		}

		return $price;
	}

	/**
	 * Force client currency for resubscribe subscription
	 */
	public function maybe_force_client_currency_for_subscription() {

		if ( wcml_is_multi_currency_on() ) {

			$subscription_id = false;
			$getData         = wpml_collect( $_GET );

			if ( $getData->has( 'resubscribe' ) ) {
				$subscription_id = (int) $getData->get( 'resubscribe' );
			} elseif ( $getData->has( 'subscription_renewal_early' ) ) {
				$subscription_id = (int) $getData->get( 'subscription_renewal_early' );
			} elseif ( is_cart() || is_checkout() ) {
				$resubscribe_cart_item = wcs_cart_contains_resubscribe();
				if ( $resubscribe_cart_item ) {
					$subscription_id = $resubscribe_cart_item['subscription_resubscribe']['subscription_id'];
				} else {
					$early_renewal_cart_item = wcs_cart_contains_early_renewal();
					if ( $early_renewal_cart_item ) {
						$subscription_id = $early_renewal_cart_item['subscription_renewal']['subscription_renewal_early'];
					}
				}
			}

			if ( $subscription_id ) {
				$subscription_currency = get_post_meta( $subscription_id, '_order_currency', true );
				if ( $subscription_currency && $this->woocommerce_wpml->multi_currency->get_client_currency() !== $subscription_currency ) {
					$this->woocommerce_wpml->multi_currency->set_client_currency( $subscription_currency );
				}
			}
		}
	}

	/**
	 * @param array $allowed_types
	 *
	 * @return array
	 */
	public function set_allowed_variations_types_in_xliff( $allowed_types ) {

		$allowed_types[] = 'variable-subscription';
		$allowed_types[] = 'subscription_variation';

		return $allowed_types;
	}

	/**
	 * Translate strings of renewal notifications
	 *
	 * @param integer $order_id Order ID
	 */
	public function translate_renewal_notification( $order_id ) {

	    if ( isset( WC()->mailer()->emails['WCS_Email_Customer_Renewal_Invoice'] ) ) {
		$this->woocommerce_wpml->emails->refresh_email_lang( $order_id );

		$WCS_Email_Customer_Renewal_Invoice = WC()->mailer()->emails['WCS_Email_Customer_Renewal_Invoice'];
		$WCS_Email_Customer_Renewal_Invoice->heading = __( $WCS_Email_Customer_Renewal_Invoice->heading, 'woocommerce-subscriptions' );
		$WCS_Email_Customer_Renewal_Invoice->subject = __( $WCS_Email_Customer_Renewal_Invoice->subject, 'woocommerce-subscriptions' );

			add_filter( 'woocommerce_email_get_option', [ $this, 'translate_heading_subject' ], 10, 4 );
		}
	}

	/**
	 * Translate custom heading and subject for renewal notification
	 *
	 * @param string                             $return_value original string
	 * @param WCS_Email_Customer_Renewal_Invoice $obj Object of email class
	 * @param string                             $value Original value from setting
	 * @param string                             $key Name of the key
	 * @return string Translated value or original value incase of not translated
	 */
	public function translate_heading_subject( $return_value, $obj, $value, $key ) {

		if ( $obj instanceof WCS_Email_Customer_Renewal_Invoice ) {
			if ( $key == 'subject' || $key == 'heading' ) {
				$translated_admin_string = $this->woocommerce_wpml->emails->wcml_get_translated_email_string( 'admin_texts_woocommerce_customer_renewal_invoice_settings', '[woocommerce_customer_renewal_invoice_settings]' . $key );
				return empty( $translated_admin_string ) ? $return_value : $translated_admin_string;
			}
		}

		return $return_value;
	}

	/**
	 * Add customer renewal invoice option to translate
	 *
	 * @param array $emails_options list of option to translate
	 * @return array $emails_options
	 */
	public function translate_email_options( $emails_options ) {

		if ( is_array( $emails_options ) ) {
			$emails_options[] = 'woocommerce_customer_renewal_invoice_settings';
		}

		return $emails_options;
	}

	/**
	 * Change section name prefix to add language links
	 *
	 * @param string $section_prefix section prefix
	 * @param string $emails_option current option name
	 * @return string $section_prefix
	 */
	public function email_option_section_prefix( $section_prefix, $emails_option ) {

		if ( $emails_option === 'woocommerce_customer_renewal_invoice_settings' ) {
			return 'wcs_email_';
		}

		return $section_prefix;
	}

	/**
	 * @param mixed $subscription
	 *
	 * @return mixed
	 */
	public function filter_subscription_items( $subscription ) {

		if ( $subscription instanceof WC_Subscription ) {
			$this->woocommerce_wpml->orders->adjust_order_item_in_language( $subscription->get_items() );
		}

		return $subscription;
	}
}
