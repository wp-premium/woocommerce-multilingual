<?php

class WCML_Upgrade {

	/** @var array */
	private $versions = [
		'2.9.9.1',
		'3.1',
		'3.2',
		'3.3',
		'3.5',
		'3.5.4',
		'3.6',
		'3.7',
		'3.7.3',
		'3.7.11',
		'3.8',
		'3.9',
		'3.9.1',
		'4.0',
		'4.1.0',
		'4.2.0',
		'4.2.2',
		'4.2.7',
		'4.2.10',
		'4.2.11',
		'4.3.0',
		'4.3.4',
		'4.3.5',
		'4.4.1',
		'4.4.3',
		'4.6.8',
		'4.7.3',
		'4.7.6',
		'4.7.8',
	];

	public function __construct() {
		add_action( 'init', [ $this, 'run' ] );
		add_action( 'init', [ $this, 'setup_upgrade_notices' ] );
		add_action( 'admin_notices', [ $this, 'show_upgrade_notices' ] );

		add_action( 'wp_ajax_wcml_hide_notice', [ $this, 'hide_upgrade_notice' ] );
	}

	public function setup_upgrade_notices() {
		$wcml_settings = get_option( '_wcml_settings' );
		$version_in_db = get_option( '_wcml_version' );

		if ( ! empty( $version_in_db ) && version_compare( $version_in_db, '2.9.9.1', '<' ) ) {
			$n                                    = 'varimages';
			$wcml_settings['notifications'][ $n ] =
				[
					'show' => 1,
					'text' => __( 'Looks like you are upgrading from a previous version of WooCommerce Multilingual. Would you like to automatically create translated variations and images?', 'woocommerce-multilingual' ) .
								'<br /><strong>' .
								' <a href="' . admin_url( 'admin.php?page=wpml-wcml&tab=troubleshooting' ) . '">' . __( 'Yes, go to the troubleshooting page', 'woocommerce-multilingual' ) . '</a> |' .
								' <a href="#" onclick="jQuery.ajax({type:\'POST\',url: ajaxurl,data:\'action=wcml_hide_notice&notice=' . $n . '\',success:function(){jQuery(\'#' . $n . '\').fadeOut()}});return false;">' . __( 'No - dismiss', 'woocommerce-multilingual' ) . '</a>' .
								'</strong>',
				];
			update_option( '_wcml_settings', $wcml_settings );
		}
	}

	public function show_upgrade_notices() {
		$wcml_settings = get_option( '_wcml_settings' );
		if ( ! empty( $wcml_settings['notifications'] ) ) {
			foreach ( $wcml_settings['notifications'] as $k => $notification ) {

				// Exceptions.
				if ( isset( $_GET['tab'] ) && 'troubleshooting' === $_GET['tab'] && 'varimages' === $k ) {
					continue;
				}

				if ( $notification['show'] ) {
					?>
					<div id="<?php echo $k; ?>" class="updated">
						<p><?php echo $notification['text']; ?></p>
					</div>
					<?php
				}
			}
		}
	}

	public function hide_upgrade_notice( $k ) {
		if ( empty( $k ) ) {
			$k = $_POST['notice'];
		}

		$wcml_settings = get_option( '_wcml_settings' );
		if ( isset( $wcml_settings['notifications'][ $k ] ) ) {
			$wcml_settings['notifications'][ $k ]['show'] = 0;
			update_option( '_wcml_settings', $wcml_settings );
		}
	}

	public function run() {
		$version_in_db = get_option( '_wcml_version' );

		// Exception - starting in 2.3.2.
		if ( empty( $version_in_db ) && get_option( 'icl_is_wcml_installed' ) ) {
			$version_in_db = '2.3.2';
		}

		$migration_ran = false;

		if ( $version_in_db && version_compare( $version_in_db, WCML_VERSION, '<' ) ) {
			foreach ( $this->versions as $version ) {
				if ( version_compare( $version, $version_in_db, '>' ) ) {
					$upgrade_method = 'upgrade_' . str_replace( '.', '_', $version );

					if ( method_exists( $this, $upgrade_method ) ) {
						$this->$upgrade_method();
						$migration_ran = true;
					}
				}
			}
		}

		if ( $migration_ran || empty( $version_in_db ) ) {
			update_option( '_wcml_version', WCML_VERSION );
		}

		if ( get_option( '_wcml_4_1_0_migration_required' ) && class_exists( 'woocommerce' ) ) {
			$this->upgrade_4_1_0();
			delete_option( '_wcml_4_1_0_migration_required' );
		}
	}

	public function upgrade_2_9_9_1() {
		global $wpdb;

		// Migrate existing currencies.
		$currencies = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . 'icl_currencies ORDER BY `id` DESC' );
		foreach ( $currencies as $currency ) {
			if ( isset( $currency->language_code ) ) {
				$wpdb->insert(
					$wpdb->prefix . 'icl_languages_currencies',
					[
						'language_code' => $currency->language_code,
						'currency_id'   => $currency->id,
					]
				);
			}
		}

		$cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}icl_currencies" );
		if ( in_array( 'language_code', $cols ) ) {
			$wpdb->query( "ALTER TABLE {$wpdb->prefix}icl_currencies DROP COLUMN language_code" );
		}

		// Migrate settings.
		$new_settings = [
			'is_term_order_synced'       => get_option( 'icl_is_wcml_term_order_synched' ),
			'file_path_sync'             => get_option( 'wcml_file_path_sync' ),
			'is_installed'               => get_option( 'icl_is_wpcml_installed' ),
			'dismiss_doc_main'           => get_option( 'wpml_dismiss_doc_main' ),
			'enable_multi_currency'      => get_option( 'icl_enable_multi_currency' ),
			'currency_converting_option' => get_option( 'currency_converting_option' ),
		];

		if ( ! get_option( '_wcml_settings' ) ) {
			add_option( '_wcml_settings', $new_settings, '', true );
		}

		delete_option( 'icl_is_wcml_term_order_synced' );
		delete_option( 'wcml_file_path_sync' );
		delete_option( 'icl_is_wpcml_installed' );
		delete_option( 'wpml_dismiss_doc_main' );
		delete_option( 'icl_enable_multi_currency' );
		delete_option( 'currency_converting_option' );

	}

	public function upgrade_3_1() {
		global $wpdb, $sitepress;
		$wcml_settings = get_option( '_wcml_settings' );

		if ( isset( $wcml_settings['enable_multi_currency'] ) && 'yes' === $wcml_settings['enable_multi_currency'] ) {
			$wcml_settings['enable_multi_currency'] = WCML_MULTI_CURRENCIES_INDEPENDENT;
		} else {
			$wcml_settings['enable_multi_currency'] = WCML_MULTI_CURRENCIES_DISABLED;
		}

		$wcml_settings['products_sync_date'] = 1;

		update_option( '_wcml_settings', $wcml_settings );

		// Multi-currency migration.
		if ( 'yes' === $wcml_settings['enable_multi_currency'] && 2 === (int) $wcml_settings['currency_converting_option'] ) {

			// Get currencies exchange rates.
			$results = $wpdb->get_results( "SELECT code, value FROM {$wpdb->prefix}icl_currencies" );
			foreach ( $results as $row ) {
				$exchange_rates[ $row->code ] = $row->value;
			}

			// Get languages currencies map.
			$results = $wpdb->get_results( "SELECT l.language_code, c.code FROM {$wpdb->prefix}icl_languages_currencies l JOIN {$wpdb->prefix}icl_currencies c ON l.currency_id = c.id" );
			foreach ( $results as $row ) {
				$language_currencies[ $row->language_code ] = $row->code;
			}

			$results = $wpdb->get_results(
				$wpdb->prepare(
					"
                SELECT p.ID, t.trid, t.element_type 
                FROM {$wpdb->posts} p JOIN {$wpdb->prefix}icl_translations t ON t.element_id = p.ID AND t.element_type IN ('post_product', 'post_product_variation')
                WHERE 
                    p.post_type in ('product', 'product_variation') AND t.language_code = %s
                    
            ",
					$sitepress->get_default_language()
				)
			);

			// Set custom conversion rates.
			foreach ( $results as $row ) {
				$translations                      = $sitepress->get_element_translations( $row->trid, $row->element_type );
				$meta                              = get_post_meta( $row->ID );
				$original_prices['_price']         = ! empty( $meta['_price'] ) ? $meta['_price'][0] : 0;
				$original_prices['_regular_price'] = ! empty( $meta['_regular_price'] ) ? $meta['_regular_price'][0] : 0;
				$original_prices['_sale_price']    = ! empty( $meta['_sale_price'] ) ? $meta['_sale_price'][0] : 0;

				$ccr = [];

				foreach ( $translations as $translation ) {
					if ( $translation->element_id != $row->ID ) {
						$meta                                = get_post_meta( $translation->element_id );
						$translated_prices['_price']         = $meta['_price'][0];
						$translated_prices['_regular_price'] = $meta['_regular_price'][0];
						$translated_prices['_sale_price']    = $meta['_sale_price'][0];

						if ( ! empty( $translated_prices['_price'] ) && ! empty( $original_prices['_price'] ) && $translated_prices['_price'] != $original_prices['_price'] ) {
							$ccr['_price'][ $language_currencies[ $translation->language_code ] ] = $translated_prices['_price'] / $original_prices['_price'];
						}

						if ( ! empty( $translated_prices['_regular_price'] ) && ! empty( $original_prices['_regular_price'] ) && $translated_prices['_regular_price'] != $original_prices['_regular_price'] ) {
							$ccr['_regular_price'][ $language_currencies[ $translation->language_code ] ] = $translated_prices['_regular_price'] / $original_prices['_regular_price'];
						}

						if ( ! empty( $translated_prices['_sale_price'] ) && ! empty( $original_prices['_sale_price'] ) && $translated_prices['_sale_price'] != $original_prices['_sale_price'] ) {
							$ccr['_sale_price'][ $language_currencies[ $translation->language_code ] ] = $translated_prices['_sale_price'] / $original_prices['_sale_price'];
						}
					}
				}

				if ( $ccr ) {
					update_post_meta( $row->ID, '_custom_conversion_rate', $ccr );
				}
			}
		}

	}

	public function upgrade_3_2() {
		WCML_Capabilities::set_up_capabilities();

		// Delete not existing currencies in WC.
		global $wpdb;
		$currencies    = $wpdb->get_results( 'SELECT id,code FROM ' . $wpdb->prefix . 'icl_currencies ORDER BY `id` DESC' );
		$wc_currencies = get_woocommerce_currencies();
		foreach ( $currencies as $currency ) {
			if ( ! array_key_exists( $currency->code, $wc_currencies ) ) {
				$wpdb->delete( $wpdb->prefix . 'icl_currencies', [ 'ID' => $currency->id ] );
			}
		}
	}

	public function upgrade_3_3() {
		global $wpdb, $woocommerce_wpml;

		WCML_Capabilities::set_up_capabilities();

		$currencies = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . 'icl_currencies ORDER BY `id` ASC', OBJECT );
		if ( $currencies ) {
			foreach ( $this->currencies as $currency ) {
				$woocommerce_wpml->settings['currency_options'][ $currency->code ]['rate']      = $currency->value;
				$woocommerce_wpml->settings['currency_options'][ $currency->code ]['updated']   = $currency->changed;
				$woocommerce_wpml->settings['currency_options'][ $currency->code ]['position']  = 'left';
				$woocommerce_wpml->settings['currency_options'][ $currency->code ]['languages'] = $woocommerce_wpml->settings['currencies_languages'];
				unset( $woocommerce_wpml->settings['currencies_languages'] );

				$woocommerce_wpml->update_settings();
			}
		}

		$wpdb->query( "DROP TABLE `{$wpdb->prefix}icl_currencies`" );
	}

	public function upgrade_3_5() {
		global $wpdb;
		$wcml_settings = get_option( '_wcml_settings' );

		$wcml_settings['products_sync_order'] = 1;

		update_option( '_wcml_settings', $wcml_settings );
	}

	public function upgrade_3_5_4() {
		flush_rewrite_rules();
	}

	public function upgrade_3_6() {
		$wcml_settings = get_option( '_wcml_settings' );

		$wcml_settings['display_custom_prices']                = 0;
		$wcml_settings['currency_switcher_product_visibility'] = 1;

		update_option( '_wcml_settings', $wcml_settings );
	}

	public function upgrade_3_7() {
		global $woocommerce_wpml, $wpdb;

		$woocommerce_permalinks = maybe_unserialize( get_option( 'woocommerce_permalinks' ) );

		if ( is_array( $woocommerce_permalinks ) ) {
			foreach ( $woocommerce_permalinks as $base_key => $base ) {

				$base_key = trim( $base_key, '/' );

				if ( $base ) {
					$taxonomy = false;

					switch ( $base_key ) {
						case 'category_base':
							$taxonomy = 'product_cat';
							break;
						case 'tag_base':
							$taxonomy = 'product_tag';
							break;
						case 'attribute_base':
							$taxonomy = 'attribute';
							break;
					}

					if ( $taxonomy ) {
						$wpdb->update(
							$wpdb->prefix . 'icl_strings',
							[
								'context' => 'WordPress',
								'name'    => sprintf( 'URL %s tax slug', $taxonomy ),
							],
							[
								'context' => sprintf( 'URL %s slugs - %s', $taxonomy, $base ),
								'name'    => sprintf( 'Url %s slug: %s', $taxonomy, $base ),
							]
						);

					}
				}
			}
		}

		$endpoint_keys = [
			'order-pay',
			'order-received',
			'view-order',
			'edit-account',
			'edit-address',
			'lost-password',
			'customer-logout',
			'add-payment-method',
		];

		foreach ( $endpoint_keys as $endpoint_key ) {
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->prefix}icl_strings
                                  SET context = 'WooCommerce Endpoints', name = %s
                                  WHERE context = 'WordPress' AND name = %s",
					$endpoint_key,
					'Endpoint slug: ' . $endpoint_key
				)
			);

			// Update domain_name_context_md5 value.
			$string_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}icl_strings WHERE context = 'WooCommerce Endpoints' AND name = %s", $endpoint_key ) );

			if ( $string_id ) {
				$wpdb->query(
					$wpdb->prepare(
						"UPDATE {$wpdb->prefix}icl_strings
                              SET domain_name_context_md5 = %s
                              WHERE id = %d",
						md5( $endpoint_key, 'WooCommerce Endpoints' ),
						$string_id
					)
				);
			}
		}

		if ( ! isset( $woocommerce_wpml->terms ) ) {
			global $sitepress;
			$woocommerce_wpml->terms = new WCML_Terms( $woocommerce_wpml, $sitepress, $wpdb );
		}
		$woocommerce_wpml->terms->check_if_sync_terms_needed();

		$wcml_settings = get_option( '_wcml_settings' );

		$wcml_settings['sync_taxonomies_checked'] = 1;

		update_option( '_wcml_settings', $wcml_settings );

		// Update custom fields for bookings.
		$bookable_resources = $wpdb->get_results( "SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE element_type = 'post_bookable_resource' AND source_language_code IS NOT NULL" );

		foreach ( $bookable_resources as $bookable_resource ) {
			update_post_meta( $bookable_resource->element_id, 'wcml_is_translated', true );
		}

		$bookable_persons = $wpdb->get_results( "SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE element_type = 'post_bookable_person' AND source_language_code IS NOT NULL" );

		foreach ( $bookable_persons as $bookable_person ) {
			update_post_meta( $bookable_person->element_id, 'wcml_is_translated', true );
		}
	}

	public function upgrade_3_7_3() {
		global $sitepress;

		$active_languages = $sitepress->get_active_languages();
		$current_language = $sitepress->get_current_language();

		foreach ( $active_languages as $lang ) {
			$sitepress->switch_lang( $lang['code'] );

			$product_cats = get_terms(
				'product_cat',
				[
					'hide_empty' => false,
					'fields'     => 'id=>parent',
				]
			);

			_wc_term_recount( $product_cats, get_taxonomy( 'product_cat' ), true, false );

			$product_tags = get_terms(
				'product_tag',
				[
					'hide_empty' => false,
					'fields'     => 'id=>parent',
				]
			);

			_wc_term_recount( $product_tags, get_taxonomy( 'product_tag' ), true, false );
		}

		$sitepress->switch_lang( $current_language );
	}

	public function upgrade_3_7_11() {
		$wcml_settings                     = get_option( '_wcml_settings' );
		$wcml_settings['dismiss_doc_main'] = 1;
		update_option( '_wcml_settings', $wcml_settings );
	}

	public function upgrade_3_8() {
		$wcml_settings                      = get_option( '_wcml_settings' );
		$wcml_settings['set_up_wizard_run'] = 1;

		if ( isset( $wcml_settings['attributes_settings'] ) ) {
			$attributes_settings = $wcml_settings['attributes_settings'];
			foreach ( $attributes_settings as $name => $value ) {
				if ( substr( $name, 0, 3 ) != 'pa_' ) {
					unset( $wcml_settings['attributes_settings'] [ $name ] );
					$wcml_settings['attributes_settings'] [ 'pa_' . $name ] = $value;
				}
			}
		}

		update_option( '_wcml_settings', $wcml_settings );
	}

	public function upgrade_3_9() {
		global $wpdb;

		$meta_keys_to_fix = [
			'_price',
			'_regular_price',
			'_sale_price',
			'_sku',
		];

		$sql = "
            UPDATE {$wpdb->postmeta} 
            SET meta_value = '' 
            WHERE meta_key IN('" . join( "','", $meta_keys_to_fix ) . "') 
                AND meta_value IS NULL";

		$wpdb->query( $sql );
	}

	public function upgrade_3_9_1() {
		global $wpdb, $sitepress;

		$results = $wpdb->get_results(
			"
                        SELECT p.ID, t.trid, t.element_type
                        FROM {$wpdb->posts} p
                        JOIN {$wpdb->prefix}icl_translations t ON t.element_id = p.ID AND t.element_type IN ('post_product', 'post_product_variation')
                        WHERE p.post_type in ('product', 'product_variation') AND t.source_language_code IS NULL
                    "
		);

		foreach ( $results as $product ) {
			if ( get_post_meta( $product->ID, '_manage_stock', true ) === 'yes' ) {
				$translations = $sitepress->get_element_translations( $product->trid, $product->element_type );

				$min_stock = false;

				// Collect min stock.
				foreach ( $translations as $translation ) {
					$stock = get_post_meta( $translation->element_id, '_stock', true );
					if ( ! $min_stock || $stock < $min_stock ) {
						$min_stock = $stock;
					}
				}

				// Update stock value.
				foreach ( $translations as $translation ) {
					update_post_meta( $translation->element_id, '_stock', $min_stock );
				}
			}
		}
	}

	public function upgrade_4_0() {
		$wcml_settings                                 = get_option( '_wcml_settings' );
		$wcml_settings['dismiss_tm_warning']           = 0;
		$wcml_settings['cart_sync']['lang_switch']     = WCML_CART_SYNC;
		$wcml_settings['cart_sync']['currency_switch'] = WCML_CART_SYNC;

		update_option( '_wcml_settings', $wcml_settings );

	}

	public function upgrade_4_1_0() {
		global $wpdb;

		if ( ! class_exists( 'WooCommerce' ) ) {
			update_option( '_wcml_4_1_0_migration_required', true );
		} else {
			$results = $wpdb->get_results(
				"
                        SELECT *
                        FROM {$wpdb->postmeta}
                        WHERE meta_key LIKE '\\_price\\_%' OR meta_key LIKE '\\_regular_price\\_%' OR ( meta_key LIKE '\\_sale_price\\_%' AND meta_key NOT LIKE '\\_sale\\_price\\_dates%' )
                    "
			);

			foreach ( $results as $price ) {
				$formatted_price = wc_format_decimal( $price->meta_value );
				update_post_meta( $price->post_id, $price->meta_key, $formatted_price );

				if ( 'product_variation' === get_post_type( $price->post_id ) ) {
					delete_transient( 'wc_var_prices_' . wp_get_post_parent_id( $price->post_id ) );
				}
			}

			$wcml_settings = get_option( '_wcml_settings' );

			if (
				isset( $wcml_settings['currency_switcher_style'] ) &&
				'list' === $wcml_settings['currency_switcher_style']
			) {
				if ( 'horizontal' === $wcml_settings['wcml_curr_sel_orientation'] ) {
					$switcher_style = 'wcml-horizontal-list';
				} else {
					$switcher_style = 'wcml-vertical-list';
				}
			} else {
				$switcher_style = 'wcml-dropdown';
			}

			$wcml_settings['currency_switchers']['product'] = [
				'switcher_style' => $switcher_style,
				'template'       => isset( $wcml_settings['wcml_curr_template'] ) ? $wcml_settings['wcml_curr_template'] : '',
				'widget_title'   => '',
				'color_scheme'   => [
					'font_current_normal'       => '',
					'font_current_hover'        => '',
					'background_current_normal' => '',
					'background_current_hover'  => '',
					'font_other_normal'         => '',
					'font_other_hover'          => '',
					'background_other_normal'   => '',
					'background_other_hover'    => '',
					'border_normal'             => '',
				],
			];

			$wcml_settings['currency_switcher_additional_css'] = '';
			update_option( '_wcml_settings', $wcml_settings );
		}
	}

	public function upgrade_4_2_0() {
		$wcml_settings                         = get_option( '_wcml_settings' );
		$wcml_settings['dismiss_cart_warning'] = 0;

		update_option( '_wcml_settings', $wcml_settings );
	}

	private function upgrade_4_2_2() {
		// #wcml-2128
		$user = new WP_User( 'admin' );
		if ( $user->exists() && ! is_super_admin( $user->ID ) ) {
			$user->remove_cap( 'wpml_manage_woocommerce_multilingual' );
			if ( ! in_array( 'shop_manager', $user->roles, true ) ) {
				$user->remove_cap( 'wpml_operate_woocommerce_multilingual' );
			}
		}
	}

	private function upgrade_4_2_7() {
		// #wcml-2242
		$wcml_settings = get_option( '_wcml_settings' );
		if ( 'yahoo' === $wcml_settings['multi_currency']['exchange_rates']['service'] ) {
			$wcml_settings['multi_currency']['exchange_rates']['service'] = 'fixerio';
			update_option( '_wcml_settings', $wcml_settings );
		}
	}

	private function upgrade_4_2_10() {
		// #wcml-2307
		global $wpdb;

		if ( defined( 'WC_BOOKINGS_VERSION' ) && version_compare( WC_BOOKINGS_VERSION, '1.10.9', '>=' ) ) {
			$results = $wpdb->get_results(
				"
                        SELECT *
                        FROM {$wpdb->postmeta}
                        WHERE meta_key LIKE '\\_wc_booking_base_cost\\_%' )
                    "
			);

			foreach ( $results as $price ) {
				$base_cost_price      = $price->meta_value;
				$block_cost_field_key = str_replace( 'base', 'block', $price->meta_key );
				update_post_meta( $price->post_id, $block_cost_field_key, $base_cost_price );
			}
		}
	}

	private function upgrade_4_2_11() {
		global $wpdb;

		$wpdb->query(
			"UPDATE {$wpdb->prefix}woocommerce_order_itemmeta
                                  SET meta_key = '_wcml_converted_subtotal'
                                  WHERE meta_key = 'wcml_converted_subtotal'"
		);

		$wpdb->query(
			"UPDATE {$wpdb->prefix}woocommerce_order_itemmeta
                                  SET meta_key = '_wcml_converted_total'
                                  WHERE meta_key = 'wcml_converted_total'"
		);

		WCML_Install::insert_default_categories();
	}

	private function upgrade_4_3_0() {
		$wcml_settings = get_option( '_wcml_settings' );
		if (
			WCML_MULTI_CURRENCIES_INDEPENDENT === $wcml_settings['enable_multi_currency'] &&
			isset( $wcml_settings['multi_currency']['exchange_rates']['service'] ) &&
			'fixierio' === $wcml_settings['multi_currency']['exchange_rates']['service']
		) {
			$wcml_settings['multi_currency']['exchange_rates']['service'] = 'fixerio';
			update_option( '_wcml_settings', $wcml_settings );

			$announcement_url   = 'https://github.com/fixerAPI/fixer#readme';
			$api_key_url        = 'https://fixer.io/dashboard';
			$announcement_link  = '<a href="' . $announcement_url . '" target="_blank">' . __( 'important change about this service', 'woocommerce-multilingual' ) . '</a>';
			$fixer_api_key_link = '<a href="' . $api_key_url . '" target="_blank">' . __( 'Fixer.io API key', 'woocommerce-multilingual' ) . '</a>';
			$fixerio_name       = '<strong>Fixer.io</strong>';
			$mc_settings_link   = '<a href="' . admin_url( 'admin.php?page=wpml-wcml&tab=multi-currency' ) . '">' . __( 'multi-currency settings page', 'woocommerce-multilingual' ) . '</a>';

			// translators: 1: Fixer.io, 2: Announcement link.
			$message = sprintf( __( 'Your site uses %1$s to automatically calculate prices in the secondary currency. There is an %2$s effective June 1st, 2018.', 'woocommerce-multilingual' ), $fixerio_name, $announcement_link );

			$message .= '<br />';
			// translators: 1: Mulicurrency tab link, 2: Fixer.io API key.
			$message .= sprintf( __( 'Please go to the %1$s and fill in your %2$s.', 'woocommerce-multilingual' ), $mc_settings_link, $fixer_api_key_link );

			$notice = new WPML_Notice( 'wcml-fixerio-api-key-required', $message, 'wcml-save-multi-currency-options' );
			$notice->set_css_class_types( 'warning' );
			$notice->set_dismissible( true );
			$wpml_admin_notices = wpml_get_admin_notices();
			$wpml_admin_notices->add_notice( $notice );
		}
	}

	private function upgrade_4_3_4() {
		global $wpdb;

		// Delete wrongly duplicated attachments.
		$wpdb->query( "DELETE FROM {$wpdb->prefix}icl_translations WHERE `element_id` IN ( SELECT ID FROM {$wpdb->prefix}posts WHERE `guid` LIKE '%attachment_id%' ) " );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}postmeta WHERE `post_id` IN ( SELECT ID FROM {$wpdb->prefix}posts WHERE `guid` LIKE '%attachment_id%' ) " );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}posts WHERE `guid` LIKE '%attachment_id%'" );
	}

	private function upgrade_4_3_5() {
		if ( class_exists( 'WC_Product_Bundle' ) && function_exists( 'WC_PB' ) ) {
			global $wpdb;
			// Delete wrongly bundled items.
			$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_bundled_itemmeta WHERE `meta_key` LIKE 'translation_item_id_of_%' AND `meta_value` IN ( SELECT bundled_item_id FROM {$wpdb->prefix}woocommerce_bundled_items WHERE `product_id` = 0 AND `bundle_id` = 0 ) " );
			$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_bundled_items WHERE `product_id` = 0 AND `bundle_id` = 0 " );
			$not_existing_items = $wpdb->get_col( "SELECT m.`meta_id` FROM {$wpdb->prefix}woocommerce_bundled_itemmeta AS m LEFT JOIN {$wpdb->prefix}woocommerce_bundled_items as i ON m.meta_value = i.bundled_item_id WHERE m.`meta_key` LIKE 'translation_item_id_of_%' AND i.`bundled_item_id` IS NULL" );
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_bundled_itemmeta WHERE `meta_id` IN ( %s )", join( ',', $not_existing_items ) ) );
		}
	}

	private function upgrade_4_4_1() {
		/** @var woocommerce_wpml $woocommerce_wpml */
		global $sitepress, $woocommerce_wpml;

		if ( ! $woocommerce_wpml->is_wpml_prior_4_2() ) {
			$wcml_settings = get_option( '_wcml_settings' );
			$tm_settings   = $sitepress->get_setting( 'translation-management', [] );

			if ( $wcml_settings['trnsl_interface'] ) {
				$tm_settings[ WPML_TM_Post_Edit_TM_Editor_Mode::TM_KEY_FOR_POST_TYPE_USE_NATIVE ]['product'] = false;
			} else {
				$tm_settings[ WPML_TM_Post_Edit_TM_Editor_Mode::TM_KEY_FOR_POST_TYPE_USE_NATIVE ]['product'] = true;
			}

			$sitepress->set_setting( 'translation-management', $tm_settings, true );
		}
	}


	private function upgrade_4_4_3() {
		if ( class_exists( 'WC_SwatchesPlugin' ) ) {
			global $wpdb, $sitepress, $wpml_post_translations, $woocommerce_wpml;

			$variation_sp      = new WCML_Variation_Swatches_And_Photos( $woocommerce_wpml );
			$original_products = $wpdb->get_results(
				"
                        SELECT element_id
                        FROM {$wpdb->prefix}icl_translations
                        WHERE element_type = 'post_product' AND source_language_code IS NULL"
			);

			$current_language = $sitepress->get_current_language();

			foreach ( $original_products as $product ) {
				$sitepress->switch_lang( $wpml_post_translations->get_element_lang_code( $product->element_id ) );

				$translations = $wpml_post_translations->get_element_translations( $product->element_id, false, true );
				foreach ( $translations as $translation ) {
					$variation_sp->sync_variation_swatches_and_photos( $product->element_id, $translation, false );
				}
			}

			$sitepress->switch_lang( $current_language );
		}
	}

	private function upgrade_4_7_0() {
		global $wpdb;

		$wpdb->query(
			"UPDATE {$wpdb->prefix}icl_strings
                      SET `context` = 'admin_texts_woocommerce_gateways',
                          `domain_name_context_md5` = MD5( CONCAT( 'admin_texts_woocommerce_gateways', `name`, `gettext_context` ) )
                      WHERE `context` = 'woocommerce' AND `name` LIKE '%_gateway_title'"
		);
		$wpdb->query(
			"UPDATE {$wpdb->prefix}icl_strings
                      SET `context` = 'admin_texts_woocommerce_gateways',
                          `domain_name_context_md5` = MD5( CONCAT( 'admin_texts_woocommerce_gateways', `name`, `gettext_context` ) )
                      WHERE `context` = 'woocommerce' AND `name` LIKE '%_gateway_description'"
		);
		$wpdb->query(
			"UPDATE {$wpdb->prefix}icl_strings
                      SET `context` = 'admin_texts_woocommerce_gateways',
                          `domain_name_context_md5` = MD5( CONCAT( 'admin_texts_woocommerce_gateways', `name`, `gettext_context` ) )
                      WHERE `context` = 'woocommerce' AND `name` LIKE '%_gateway_instructions'"
		);
	}

	private function upgrade_4_7_3() {
		delete_option( 'wcml_currency_switcher_template_objects' );
	}

	private function upgrade_4_7_6() {
		global $wpdb;

		$wpdb->query(
			"UPDATE {$wpdb->prefix}icl_strings
                      SET `context` = 'admin_texts_woocommerce_shipping',
                          `domain_name_context_md5` = MD5( CONCAT( 'admin_texts_woocommerce_shipping', `name`, `gettext_context` ) )
                      WHERE `context` = 'woocommerce' AND `name` LIKE '%_shipping_method_title'"
		);
	}

	private function upgrade_4_7_8() {
		$emailOptions = [
			'woocommerce_new_order_settings',
			'woocommerce_cancelled_order_settings',
			'woocommerce_failed_order_settings',
			'woocommerce_customer_on_hold_order_settings',
			'woocommerce_customer_processing_order_settings',
			'woocommerce_customer_completed_order_settings',
			'woocommerce_customer_refunded_order_settings',
			'woocommerce_customer_invoice_settings',
			'woocommerce_customer_note_settings',
			'woocommerce_customer_reset_password_settings',
			'woocommerce_customer_new_account_settings'
		];


		foreach ( $emailOptions as $emailOption ) {

			$emailSettings = get_option( $emailOption );

			if ( isset( $emailSettings['additional_content'] ) && $emailSettings['additional_content'] ) {
				$domain = 'admin_texts_' . $emailOption;
				$name   = '[' . $emailOption . ']additional_content';

				icl_register_string( $domain, $name, $emailSettings['additional_content'], false, '' );
			}
		}
	}
}
