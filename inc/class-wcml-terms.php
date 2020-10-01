<?php

class WCML_Terms {

	const PRODUCT_SHIPPING_CLASS           = 'product_shipping_class';
	private $ALL_TAXONOMY_TERMS_TRANSLATED = 0;
	private $NEW_TAXONOMY_TERMS            = 1;
	private $NEW_TAXONOMY_IGNORED          = 2;

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var SitePress */
	private $sitepress;
	/** @var wpdb */
	private $wpdb;

	/**
	 * WCML_Terms constructor.
	 *
	 * @param woocommerce_wpml $woocommerce_wpml
	 * @param SitePress        $sitepress
	 * @param wpdb             $wpdb
	 */
	public function __construct( woocommerce_wpml $woocommerce_wpml, SitePress $sitepress, wpdb $wpdb ) {
		$this->woocommerce_wpml = $woocommerce_wpml;
		$this->sitepress        = $sitepress;
		$this->wpdb             = $wpdb;
	}

	public function add_hooks() {

		add_action( 'updated_woocommerce_term_meta', [ $this, 'sync_term_order' ], 100, 4 );

		add_filter( 'wp_get_object_terms', [ $this->sitepress, 'get_terms_filter' ] );
		add_action( 'created_term', [ $this, 'translated_terms_status_update' ], 10, 3 );
		add_action( 'edit_term', [ $this, 'translated_terms_status_update' ], 10, 3 );
		add_action(
			'wp_ajax_wcml_update_term_translated_warnings',
			[
				$this,
				'wcml_update_term_translated_warnings',
			]
		);

		add_action( 'created_term', [ $this, 'set_flag_for_variation_on_attribute_update' ], 10, 3 );

		add_filter( 'wpml_taxonomy_translation_bottom', [ $this, 'sync_taxonomy_translations' ], 10, 3 );

		add_action( 'wp_ajax_wcml_sync_product_variations', [ $this, 'wcml_sync_product_variations' ] );
		add_action( 'wp_ajax_wcml_tt_sync_taxonomies_in_content', [ $this, 'wcml_sync_taxonomies_in_content' ] );
		add_action(
			'wp_ajax_wcml_tt_sync_taxonomies_in_content_preview',
			[
				$this,
				'wcml_sync_taxonomies_in_content_preview',
			]
		);

		if ( is_admin() ) {
			add_action( 'admin_menu', [ $this, 'admin_menu_setup' ] );

			add_filter( 'pre_option_default_product_cat', [ $this, 'pre_option_default_product_cat' ] );
			add_filter( 'update_option_default_product_cat', [ $this, 'update_option_default_product_cat' ], 1, 2 );
		}

		add_action( 'update_term_meta', [ $this, 'update_category_count_meta' ], 10, 4 );
		add_action( 'delete_term', [ $this, 'wcml_delete_term' ], 10, 4 );
		add_filter( 'get_the_terms', [ $this, 'shipping_terms' ], 10, 3 );
		add_filter( 'get_terms', [ $this, 'filter_shipping_classes_terms' ], 10, 3 );

		add_filter( 'woocommerce_get_product_terms', [ $this, 'get_product_terms_filter' ], 10, 4 );
		add_action( 'created_term_translation', [ $this, 'set_flag_to_sync' ], 10, 3 );

		add_filter( 'woocommerce_get_product_subcategories_cache_key', [ $this, 'add_lang_parameter_to_cache_key' ] );
	}

	public function admin_menu_setup() {
		global $pagenow;
		if ( $pagenow == 'edit-tags.php' && isset( $_GET['action'] ) && $_GET['action'] == 'edit' ) {
			add_action( 'admin_notices', [ $this, 'show_term_translation_screen_notices' ] );
		}

		$page = isset( $_GET['page'] ) ? $_GET['page'] : '';
		if ( $page === ICL_PLUGIN_FOLDER . '/menu/taxonomy-translation.php' ) {
			WCML_Resources::load_management_css();
			WCML_Resources::load_taxonomy_translation_scripts();
		}

	}

	public function show_term_translation_screen_notices() {

		$taxonomies = array_keys( get_taxonomies( [ 'object_type' => [ 'product' ] ], 'objects' ) );
		$taxonomies = $taxonomies + array_keys( get_taxonomies( [ 'object_type' => [ 'product_variations' ] ], 'objects' ) );
		$taxonomies = array_unique( $taxonomies );
		$taxonomy   = isset( $_GET['taxonomy'] ) ? $_GET['taxonomy'] : false;
		if ( $taxonomy && in_array( $taxonomy, $taxonomies ) ) {
			$taxonomy_obj = get_taxonomy( $taxonomy );
			$message      = sprintf(
				__( 'To translate %1$s please use the %2$s translation%3$s page, inside the %4$sWooCommerce Multilingual admin%5$s.', 'woocommerce-multilingual' ),
				$taxonomy_obj->labels->name,
				'<strong><a href="' . admin_url( 'admin.php?page=wpml-wcml&tab=' . $taxonomy ) . '">' . $taxonomy_obj->labels->singular_name,
				'</a></strong>',
				'<strong><a href="' . admin_url( 'admin.php?page=wpml-wcml">' ),
				'</a></strong>'
			);

			echo '<div class="updated"><p>' . $message . '</p></div>';
		}

	}

	public function sync_term_order_globally() {
		// syncs the term order of any taxonomy in $this->wpdb->prefix.'woocommerce_attribute_taxonomies'.
		// use it when term orderings have become unsynched, e.g. before WCML 3.3.
		if ( ! defined( 'WOOCOMMERCE_VERSION' ) ) {
			return;
		}

		$cur_lang = $this->sitepress->get_current_language();
		$lang     = $this->sitepress->get_default_language();
		$this->sitepress->switch_lang( $lang );

		$taxes = wc_get_attribute_taxonomies();

		if ( $taxes ) {
			foreach ( $taxes as $woo_tax ) {
				$tax      = 'pa_' . $woo_tax->attribute_name;
				$meta_key = 'order_' . $tax;
				// if ($tax != 'pa_frame') continue;
				$terms = get_terms( $tax );
				if ( $terms ) {
					foreach ( $terms as $term ) {
										$term_order   = get_term_meta( $term->term_id, $meta_key, true );
										$trid         = $this->sitepress->get_element_trid( $term->term_taxonomy_id, 'tax_' . $tax );
										$translations = $this->sitepress->get_element_translations( $trid, 'tax_' . $tax );
						if ( $translations ) {
							foreach ( $translations as $trans ) {
								if ( $trans->language_code != $lang ) {
									update_term_meta( $trans->term_id, $meta_key, $term_order );
								}
							}
						}
					}
				}
			}
		}

		// sync product categories ordering.
		$terms = get_terms( 'product_cat' );
		if ( $terms ) {
			foreach ( $terms as $term ) {
				$term_order   = get_term_meta( $term->term_id, 'order', true );
				$trid         = $this->sitepress->get_element_trid( $term->term_taxonomy_id, 'tax_product_cat' );
				$translations = $this->sitepress->get_element_translations( $trid, 'tax_product_cat' );
				if ( $translations ) {
					foreach ( $translations as $trans ) {
						if ( $trans->language_code != $lang ) {
							update_term_meta( $trans->term_id, 'order', $term_order );
						}
					}
				}
			}
		}

		$this->sitepress->switch_lang( $cur_lang );

		$this->woocommerce_wpml->settings['is_term_order_synced'] = 'yes';
		$this->woocommerce_wpml->update_settings();

	}

	public function sync_term_order( $meta_id, $object_id, $meta_key, $meta_value ) {

		// WooCommerce before termmeta table migration.
		$wc_before_term_meta = get_option( 'db_version' ) < 34370;

		if ( ! isset( $_POST['thetaxonomy'] ) || ! taxonomy_exists( $_POST['thetaxonomy'] ) || substr( $meta_key, 0, 5 ) !== 'order' ) {
			return;
		}

		$tax = filter_input( INPUT_POST, 'thetaxonomy', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		$term_taxonomy_id = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT term_taxonomy_id FROM {$this->wpdb->term_taxonomy} WHERE term_id=%d AND taxonomy=%s", $object_id, $tax ) );
		$trid             = $this->sitepress->get_element_trid( $term_taxonomy_id, 'tax_' . $tax );
		$translations     = $this->sitepress->get_element_translations( $trid, 'tax_' . $tax );
		if ( $translations ) {
			foreach ( $translations as $trans ) {
				if ( $trans->element_id != $term_taxonomy_id ) {

					// Backwards compatibility - WooCommerce termmeta table.
					if ( $wc_before_term_meta ) {
						$this->wpdb->update(
							$this->wpdb->prefix . 'woocommerce_termmeta',
							[ 'meta_value' => $meta_value ],
							[
								'woocommerce_term_id' => $trans->term_id,
								'meta_key'            => $meta_key,
							]
						);
						// END Backwards compatibility - WooCommerce termmeta table.
					} else {
						update_term_meta( $trans->term_id, $meta_key, $meta_value );
					}
				}
			}
		}

	}

	public function translated_terms_status_update( $term_id, $tt_id, $taxonomy ) {

		if ( isset( $_POST['product_cat_thumbnail_id'] ) || isset( $_POST['display_type'] ) ) {
			global $sitepress_settings;

			if ( $this->is_original_category( $tt_id, 'tax_' . $taxonomy ) ) {
				$trid         = $this->sitepress->get_element_trid( $tt_id, 'tax_' . $taxonomy );
				$translations = $this->sitepress->get_element_translations( $trid, 'tax_' . $taxonomy );

				foreach ( $translations as $translation ) {
					if ( ! $translation->original ) {
						if ( isset( $_POST['display_type'] ) ) {
							update_term_meta( $translation->term_id, 'display_type', esc_attr( $_POST['display_type'] ) );
						}
						update_term_meta( $translation->term_id, 'thumbnail_id', apply_filters( 'translate_object_id', esc_attr( $_POST['product_cat_thumbnail_id'] ), 'attachment', true, $translation->language_code ) );
					}
				}
			}
		}

		global $wp_taxonomies;
		if ( in_array( 'product', $wp_taxonomies[ $taxonomy ]->object_type ) || in_array( 'product_variation', $wp_taxonomies[ $taxonomy ]->object_type ) ) {
			$this->update_terms_translated_status( $taxonomy );
		}

	}

	public function is_original_category( $tt_id, $taxonomy ) {
		$is_original = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT source_language_code IS NULL FROM {$this->wpdb->prefix}icl_translations WHERE element_id=%d AND element_type=%s", $tt_id, $taxonomy ) );
		return $is_original ? true : false;
	}

	public function wcml_update_term_translated_warnings() {
		$ret = [];

		$taxonomy = filter_input( INPUT_POST, 'taxonomy', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		$wcml_settings = $this->woocommerce_wpml->get_settings();

		$attribute_taxonomies = $this->woocommerce_wpml->attributes->get_translatable_attributes();

		$attribute_taxonomies_arr = [];
		foreach ( $attribute_taxonomies as $a ) {
			$attribute_taxonomies_arr[] = 'pa_' . $a->attribute_name;
		}

		$ret['is_attribute'] = intval( in_array( $taxonomy, $attribute_taxonomies_arr ) );

		if ( isset( $wcml_settings['untranstaled_terms'][ $taxonomy ] ) &&
			(
				$wcml_settings['untranstaled_terms'][ $taxonomy ]['status'] == $this->ALL_TAXONOMY_TERMS_TRANSLATED ||
				$wcml_settings['untranstaled_terms'][ $taxonomy ]['status'] == $this->NEW_TAXONOMY_IGNORED
			)
		) {

			$ret['hide'] = 1;
		} else {

			$ret['hide'] = 0;

			if ( isset( $wcml_settings[ 'sync_' . $taxonomy ] ) ) {
				$ret['show_button'] = $wcml_settings[ 'sync_' . $taxonomy ];
			} elseif ( in_array( $taxonomy, $attribute_taxonomies_arr ) ) {
				$ret['show_button'] = $wcml_settings['sync_variations'];
			}
		}

		if ( $ret['is_attribute'] ) {
			$ret['hide'] = $this->woocommerce_wpml->attributes->is_attributes_fully_translated();
		}

		echo json_encode( $ret );
		exit;

	}

	public function update_terms_translated_status( $taxonomy ) {

		$wcml_settings        = $this->woocommerce_wpml->get_settings();
		$is_translatable      = 1;
		$not_translated_count = 0;
		$original_terms       = [];

		if ( isset( $wcml_settings['attributes_settings'][ $taxonomy ] ) && ! $wcml_settings['attributes_settings'][ $taxonomy ] ) {
			$is_translatable = 0;
		}

		if ( $is_translatable ) {

			$active_languages = $this->sitepress->get_active_languages();

			foreach ( $active_languages as $language ) {
				$terms = $this->wpdb->get_results(
					$this->wpdb->prepare(
						"
                    SELECT t1.element_id AS e1, t2.element_id AS e2 FROM {$this->wpdb->term_taxonomy} x
                    JOIN {$this->wpdb->prefix}icl_translations t1 ON x.term_taxonomy_id = t1.element_id AND t1.element_type = %s AND t1.source_language_code IS NULL
                    LEFT JOIN {$this->wpdb->prefix}icl_translations t2 ON t2.trid = t1.trid AND t2.language_code = %s
                ",
						'tax_' . $taxonomy,
						$language['code']
					)
				);
				foreach ( $terms as $term ) {
					if ( empty( $term->e2 ) && ! in_array( $term->e1, $original_terms ) ) {
						$original_terms[] = $term->e1;
						$not_translated_count ++;
					}
				}
			}
		}

		$status = $not_translated_count ? $this->NEW_TAXONOMY_TERMS : $this->ALL_TAXONOMY_TERMS_TRANSLATED;

		if ( isset( $wcml_settings['untranstaled_terms'][ $taxonomy ] ) && $wcml_settings['untranstaled_terms'][ $taxonomy ] === $this->NEW_TAXONOMY_IGNORED ) {
			$status = $this->NEW_TAXONOMY_IGNORED;
		}

		$wcml_settings['untranstaled_terms'][ $taxonomy ] = [
			'count'  => $not_translated_count,
			'status' => $status,
		];

		$this->woocommerce_wpml->update_settings( $wcml_settings );

		return $wcml_settings['untranstaled_terms'][ $taxonomy ];

	}

	public function is_fully_translated( $taxonomy ) {

		$wcml_settings = $this->woocommerce_wpml->get_settings();

		$return = true;

		if ( ! isset( $wcml_settings['untranstaled_terms'][ $taxonomy ] ) ) {
			$wcml_settings['untranstaled_terms'][ $taxonomy ] = $this->update_terms_translated_status( $taxonomy );
		}

		if ( $wcml_settings['untranstaled_terms'][ $taxonomy ]['status'] == $this->NEW_TAXONOMY_TERMS ) {
			$return = false;
		}

		return $return;
	}

	public function get_untranslated_terms_number( $taxonomy, $force_update = false ) {

		$wcml_settings = $this->woocommerce_wpml->get_settings();

		if ( $force_update || ! isset( $wcml_settings['untranstaled_terms'][ $taxonomy ] ) ) {
			$wcml_settings['untranstaled_terms'][ $taxonomy ] = $this->update_terms_translated_status( $taxonomy );
		}

		return $wcml_settings['untranstaled_terms'][ $taxonomy ]['count'];

	}

	public function set_flag_for_variation_on_attribute_update( $term_id, $tt_id, $taxonomy ) {

		$attribute_taxonomies = wc_get_attribute_taxonomies();
		foreach ( $attribute_taxonomies as $a ) {
			$attribute_taxonomies_arr[] = 'pa_' . $a->attribute_name;
		}

		if ( isset( $attribute_taxonomies_arr ) && in_array( $taxonomy, $attribute_taxonomies_arr ) ) {

				$wcml_settings = $this->woocommerce_wpml->get_settings();

				// get term language.
				$term_language = $this->sitepress->get_element_language_details( $tt_id, 'tax_' . $taxonomy );

			if ( isset( $term_language->language_code ) && $term_language->language_code != $this->sitepress->get_default_language() ) {
				// get term in the default language.
				$term_id = apply_filters( 'translate_object_id', $term_id, $taxonomy, false, $this->sitepress->get_default_language() );

				// does it belong to any posts (variations).
				$objects = get_objects_in_term( $term_id, $taxonomy );

				if ( ! isset( $wcml_settings['variations_needed'][ $taxonomy ] ) ) {
					$wcml_settings['variations_needed'][ $taxonomy ] = 0;
				}
				$wcml_settings['variations_needed'][ $taxonomy ] += count( $objects );

				$this->woocommerce_wpml->update_settings( $wcml_settings );

			}
		}

	}

	public function sync_taxonomy_translations( $html, $taxonomy, $taxonomy_obj ) {

		$is_wcml = is_admin() && $taxonomy && isset( $_GET['page'] ) && $_GET['page'] == 'wpml-wcml' && isset( $_GET['tab'] );
		$is_ajax = is_ajax() && $taxonomy && isset( $_POST['action'] ) && $_POST['action'] === 'wpml_get_terms_and_labels_for_taxonomy_table';

		if ( $is_wcml || $is_ajax ) {

			$sync_tax = new WCML_Sync_Taxonomy( $this->woocommerce_wpml, $taxonomy, $taxonomy_obj );
			$html     = $sync_tax->get_view();
		}

		return $html;
	}

	public function wcml_sync_product_variations( $taxonomy ) {
		$nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wcml_sync_product_variations' ) ) {
			die( 'Invalid nonce' );
		}

		$VARIATIONS_THRESHOLD = 20;

		$wcml_settings = $this->woocommerce_wpml->get_settings();
		$response      = [];

		$taxonomy = filter_input( INPUT_POST, 'taxonomy', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		$languages_processed = intval( $_POST['languages_processed'] );

		$condition = $languages_processed ? '>=' : '>';

		$where = isset( $_POST['last_post_id'] ) && $_POST['last_post_id'] ? ' ID ' . $condition . ' ' . intval( $_POST['last_post_id'] ) . ' AND ' : '';

		$post_ids = $this->wpdb->get_col(
			$this->wpdb->prepare(
				"                
                SELECT DISTINCT tr.object_id 
                FROM {$this->wpdb->term_relationships} tr
                JOIN {$this->wpdb->term_taxonomy} tx on tr.term_taxonomy_id = tx.term_taxonomy_id
                JOIN {$this->wpdb->posts} p ON tr.object_id = p.ID
                JOIN {$this->wpdb->prefix}icl_translations t ON t.element_id = p.ID 
                WHERE {$where} tx.taxonomy = %s AND p.post_type = 'product' AND t.element_type='post_product' AND t.source_language_code IS NULL  
                ORDER BY ID ASC
                
        ",
				$taxonomy
			)
		);

		if ( $post_ids ) {

			$variations_processed = 0;
			$posts_processed      = 0;
			foreach ( $post_ids as $post_id ) {
				$terms       = wp_get_post_terms( $post_id, $taxonomy );
				$terms_count = count( $terms );

				$trid         = $this->sitepress->get_element_trid( $post_id, 'post_product' );
				$translations = $this->sitepress->get_element_translations( $trid, 'post_product' );

				$i = 1;

				foreach ( $translations as $translation ) {

					if ( $i > $languages_processed && $translation->element_id != $post_id ) {
						$this->woocommerce_wpml->sync_product_data->sync_product_taxonomies( $post_id, $translation->element_id, $translation->language_code );
						$this->woocommerce_wpml->sync_variations_data->sync_product_variations( $post_id, $translation->element_id, $translation->language_code, [ 'is_troubleshooting' => true ] );
						$this->woocommerce_wpml->translation_editor->create_product_translation_package( $post_id, $trid, $translation->language_code, ICL_TM_COMPLETE );
						$variations_processed           += $terms_count * 2;
						$response['languages_processed'] = $i;
						$i++;
						// check if sum of 2 iterations doesn't exceed $VARIATIONS_THRESHOLD.
						if ( $variations_processed >= $VARIATIONS_THRESHOLD ) {
							break;
						}
					} else {
						$i++;
					}
				}
				$response['last_post_id'] = $post_id;
				if ( --$i == count( $translations ) ) {
					$response['languages_processed'] = 0;
					$languages_processed             = 0;
				} else {
					break;
				}

				$posts_processed ++;

			}

			$response['go'] = 1;

		} else {

			$response['go'] = 0;

		}

		$response['progress'] = $response['go'] ? sprintf( __( '%d products left', 'woocommerce-multilingual' ), count( $post_ids ) - $posts_processed ) : __( 'Synchronization complete!', 'woocommerce-multilingual' );

		if ( $response['go'] && isset( $wcml_settings['variations_needed'][ $taxonomy ] ) && ! empty( $variations_processed ) ) {
			$wcml_settings['variations_needed'][ $taxonomy ] = max( $wcml_settings['variations_needed'][ $taxonomy ] - $variations_processed, 0 );
		} else {
			if ( $response['go'] == 0 ) {
				$wcml_settings['variations_needed'][ $taxonomy ] = 0;
			}
		}
		$wcml_settings['sync_variations'] = 0;

		$this->woocommerce_wpml->update_settings( $wcml_settings );

		echo json_encode( $response );
		exit;
	}

	public function wcml_sync_taxonomies_in_content_preview() {
		$nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wcml_sync_taxonomies_in_content_preview' ) ) {
			die( 'Invalid nonce' );
		}

		global $wp_taxonomies;

		$html = $message = $errors = '';

		if ( isset( $wp_taxonomies[ $_POST['taxonomy'] ] ) ) {
			$object_types = $wp_taxonomies[ $_POST['taxonomy'] ]->object_type;

			foreach ( $object_types as $object_type ) {

				$html .= $this->render_assignment_status( $object_type, $_POST['taxonomy'], $preview = true );

			}
		} else {
			$errors = sprintf( __( 'Invalid taxonomy %s', 'woocommerce-multilingual' ), $_POST['taxonomy'] );
		}

		echo json_encode(
			[
				'html'    => $html,
				'message' => $message,
				'errors'  => $errors,
			]
		);
		exit;
	}

	public function wcml_sync_taxonomies_in_content() {
		$nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wcml_sync_taxonomies_in_content' ) ) {
			die( 'Invalid nonce' );
		}

		global $wp_taxonomies;

		$html = $message = $errors = '';

		if ( isset( $wp_taxonomies[ $_POST['taxonomy'] ] ) ) {
			$html .= $this->render_assignment_status( $_POST['post'], $_POST['taxonomy'], $preview = false );

		} else {
			$errors .= sprintf( __( 'Invalid taxonomy %s', 'woocommerce-multilingual' ), $_POST['taxonomy'] );
		}

		echo json_encode(
			[
				'html'   => $html,
				'errors' => $errors,
			]
		);
		exit;
	}

	public function render_assignment_status( $object_type, $taxonomy, $preview = true ) {
		global $wp_post_types, $wp_taxonomies;

		$default_language         = $this->sitepress->get_default_language();
		$is_taxonomy_translatable = $this->is_translatable_wc_taxonomy( $taxonomy );

		$posts = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT * FROM {$this->wpdb->posts} AS p LEFT JOIN {$this->wpdb->prefix}icl_translations AS tr ON tr.element_id = p.ID WHERE p.post_status = 'publish' AND p.post_type = %s AND tr.source_language_code is NULL", $object_type ) );

		foreach ( $posts as $post ) {

			$terms = wp_get_post_terms( $post->ID, $taxonomy );

			$term_ids = [];
			foreach ( $terms as $term ) {
				$term_ids[] = $term->term_id;
			}

			$trid         = $this->sitepress->get_element_trid( $post->ID, 'post_' . $post->post_type );
			$translations = $this->sitepress->get_element_translations( $trid, 'post_' . $post->post_type, true, true );

			foreach ( $translations as $language => $translation ) {

				if ( $language != $default_language && $translation->element_id ) {

					$terms_of_translation = wp_get_post_terms( $translation->element_id, $taxonomy );

					$translation_term_ids = [];
					foreach ( $terms_of_translation as $term ) {

						$term_id_original = apply_filters( 'translate_object_id', $term->term_id, $taxonomy, false, $default_language );
						if ( ! $term_id_original || ! in_array( $term_id_original, $term_ids ) ) {
							// remove term.
							if ( $preview ) {
								$needs_sync = true;
								break( 3 );
							}

							$current_terms = wp_get_post_terms( $translation->element_id, $taxonomy );
							$updated_terms = [];
							foreach ( $current_terms as $cterm ) {
								if ( $cterm->term_id != $term->term_id ) {
									$updated_terms[] = $is_taxonomy_translatable ? $term->term_id : $term->name;
								}
								if ( ! $preview ) {

									if ( $is_taxonomy_translatable && ! is_taxonomy_hierarchical( $taxonomy ) ) {
										$updated_terms = array_unique( array_map( 'intval', $updated_terms ) );
									}

									wp_set_post_terms( $translation->element_id, $updated_terms, $taxonomy );
								}
							}
						} else {
							$translation_term_ids[] = $term_id_original;
						}
					}

					foreach ( $term_ids as $term_id ) {

						if ( ! in_array( $term_id, $translation_term_ids ) ) {
							// add term.
							if ( $preview ) {
								$needs_sync = true;
								break( 3 );
							}
							$terms_array        = [];
							$term_id_translated = apply_filters( 'translate_object_id', $term_id, $taxonomy, false, $language );

							// not using get_term.
							$translated_term = $this->wpdb->get_row(
								$this->wpdb->prepare(
									"
                            SELECT * FROM {$this->wpdb->terms} t JOIN {$this->wpdb->term_taxonomy} x ON x.term_id = t.term_id WHERE t.term_id = %d AND x.taxonomy = %s",
									$term_id_translated,
									$taxonomy
								)
							);

							if ( $translated_term ) {
								$terms_array[] = $translated_term->term_id;
							}

							if ( ! $preview ) {

								if ( $is_taxonomy_translatable && ! is_taxonomy_hierarchical( $taxonomy ) ) {
									$terms_array = array_unique( array_map( 'intval', $terms_array ) );
								}

								wp_set_post_terms( $translation->element_id, $terms_array, $taxonomy, true );
							}
						}
					}
				}
			}
		}

		$wcml_settings                        = $this->woocommerce_wpml->get_settings();
		$wcml_settings[ 'sync_' . $taxonomy ] = 0;
		$this->woocommerce_wpml->update_settings( $wcml_settings );

		$out = '';

		if ( $preview ) {

			$out .= '<div class="wcml_tt_sync_row">';
			if ( ! empty( $needs_sync ) ) {
				$out .= '<form class="wcml_tt_do_sync">';
				$out .= '<input type="hidden" name="post" value="' . $object_type . '" />';
				$out .= wp_nonce_field( 'wcml_sync_taxonomies_in_content', 'wcml_sync_taxonomies_in_content_nonce', true, false );
				$out .= '<input type="hidden" name="taxonomy" value="' . $taxonomy . '" />';
				$out .= sprintf(
					__( 'Some translated %1$s have different %2$s assignments.', 'woocommerce-multilingual' ),
					'<strong>' . mb_strtolower( $wp_post_types[ $object_type ]->labels->name ) . '</strong>',
					'<strong>' . mb_strtolower( $wp_taxonomies[ $taxonomy ]->labels->name ) . '</strong>'
				);
				$out .= '&nbsp;<a class="submit button-secondary" href="#">' . sprintf(
					__( 'Update %1$s for all translated %2$s', 'woocommerce-multilingual' ),
					'<strong>' . mb_strtolower( $wp_taxonomies[ $taxonomy ]->labels->name ) . '</strong>',
					'<strong>' . mb_strtolower( $wp_post_types[ $object_type ]->labels->name ) . '</strong>'
				) . '</a>' .
					'&nbsp;<img src="' . ICL_PLUGIN_URL . '/res/img/ajax-loader.gif" alt="loading" height="16" width="16" class="wcml_tt_spinner" />';
				$out .= '</form>';
			} else {
				$out .= sprintf(
					__( 'All %1$s have the same %2$s assignments.', 'woocommerce-multilingual' ),
					'<strong>' . mb_strtolower( $wp_taxonomies[ $taxonomy ]->labels->name ) . '</strong>',
					'<strong>' . mb_strtolower( $wp_post_types[ $object_type ]->labels->name ) . '</strong>'
				);
			}
			$out .= '</div>';

		} else {

			$out .= sprintf( __( 'Successfully updated %1$s for all translated %2$s.', 'woocommerce-multilingual' ), $wp_taxonomies[ $taxonomy ]->labels->name, $wp_post_types[ $object_type ]->labels->name );

		}

		return $out;
	}

	/**
	 * Filter shipping terms
	 *
	 * @param WP_Term[]|false|WP_Error $terms    Terms to filter.
	 * @param int                      $post_id  Post ID.
	 * @param string                   $taxonomy Taxonomy.
	 *
	 * @return WP_Term[]|false|WP_Error
	 */
	public function shipping_terms( $terms, $post_id, $taxonomy ) {
		global $pagenow;

		if (
			'post.php' === $pagenow ||
			self::PRODUCT_SHIPPING_CLASS !== $taxonomy ||
			( isset( $_POST['action'] ) && 'woocommerce_load_variations' === $_POST['action'] ) ) {
			return $terms;
		}

		$post_type = get_post_type( $post_id );
		if ( ! in_array( $post_type, [ 'product', 'product_variation' ], true ) ) {
			return $terms;
		}

		$current_language = $this->sitepress->get_current_language();
		$key              = md5( wp_json_encode( [ $post_id, $current_language ] ) );
		$found            = false;
		$terms            = WPML_Non_Persistent_Cache::get( $key, __CLASS__, $found );
		if ( ! $found ) {
			remove_filter( 'get_the_terms', [ $this, 'shipping_terms' ], 10 );
			$terms = get_the_terms(
				apply_filters( 'translate_object_id', $post_id, $post_type, true, $current_language ),
				self::PRODUCT_SHIPPING_CLASS
			);
			add_filter( 'get_the_terms', [ $this, 'shipping_terms' ], 10, 3 );
			WPML_Non_Persistent_Cache::set( $key, $terms, __CLASS__ );
		}

		return $terms;
	}

	public function filter_shipping_classes_terms( $terms, $taxonomies, $args ) {

	    if( $taxonomies && is_admin() && in_array( self::PRODUCT_SHIPPING_CLASS, $taxonomies ) ){
		    $on_wc_settings_page = isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] === 'wc-settings';
		    $on_shipping_tab = isset( $_GET[ 'tab' ] ) && $_GET[ 'tab' ] === 'shipping';
		    $on_classes_section = isset( $_GET[ 'section' ] ) && $_GET[ 'section' ] === 'classes';

			if ( $on_wc_settings_page && $on_shipping_tab && ! $on_classes_section ) {
				remove_filter( 'get_terms', [ $this, 'filter_shipping_classes_terms' ] );
				remove_filter( 'get_terms', [ 'WPML_Terms_Translations', 'get_terms_filter' ], 10, 2 );
				$this->sitepress->switch_lang( $this->sitepress->get_default_language() );
				$terms = get_terms( $args );
				add_filter( 'get_terms', [ 'WPML_Terms_Translations', 'get_terms_filter' ], 10, 2 );
				add_filter( 'get_terms', [ $this, 'filter_shipping_classes_terms' ], 10, 3 );
				$this->sitepress->switch_lang();
			}
		}

		return $terms;
	}

	public function wcml_delete_term( $term, $tt_id, $taxonomy, $deleted_term ) {
		global $wp_taxonomies;

		foreach ( $wp_taxonomies as $key => $taxonomy_obj ) {
			if ( ( in_array( 'product', $taxonomy_obj->object_type ) || in_array( 'product_variation', $taxonomy_obj->object_type ) ) && $key == $taxonomy ) {
				$this->update_terms_translated_status( $taxonomy );
				break;
			}
		}

	}

	/**
	 * @param array  $terms
	 * @param int    $product_id
	 * @param string $taxonomy
	 * @param array  $args
	 *
	 * @return array
	 */
	public function get_product_terms_filter( $terms, $product_id, $taxonomy, $args ) {

		$language = $this->sitepress->get_language_for_element( $product_id, 'post_' . get_post_type( $product_id ) );

		$is_objects_array = is_object( current( $terms ) );

		$filtered_terms = [];

		foreach ( $terms as $term ) {

			if ( ! $is_objects_array ) {
				$term_obj = get_term_by( 'name', $term, $taxonomy );

				$is_wc_filtering_by_slug = isset( $args['fields'] ) && in_array( $args['fields'], [ 'id=>slug', 'slugs' ] );
				if ( $is_wc_filtering_by_slug || ! $term_obj ) {
					$term_obj = get_term_by( 'slug', $term, $taxonomy );
					$is_slug  = true;
				}
			}

			if ( empty( $term_obj ) ) {
				$filtered_terms[] = $term;
				continue;
			}

			$trnsl_term_id = apply_filters( 'translate_object_id', $term_obj->term_id, $taxonomy, true, $language );

			if ( $is_objects_array ) {
				$filtered_terms[] = get_term( $trnsl_term_id, $taxonomy );
			} else {
				if ( isset( $is_slug ) ) {
					$filtered_terms[] = get_term( $trnsl_term_id, $taxonomy )->slug;
				} else {
					$filtered_terms[] = ( is_ajax() && isset( $_POST['action'] ) && in_array(
						$_POST['action'],
						[
							'woocommerce_add_variation',
							'woocommerce_link_all_variations',
						]
					) ) ? strtolower( get_term( $trnsl_term_id, $taxonomy )->name ) : get_term( $trnsl_term_id, $taxonomy )->name;
				}
			}
		}

		return $filtered_terms;
	}

	public function set_flag_to_sync( $taxonomy, $el_id, $language_code ) {
		if ( $el_id ) {
			$elem_details = $this->sitepress->get_element_language_details( $el_id, 'tax_' . $taxonomy );
			if ( null !== $elem_details->source_language_code ) {
				$this->check_if_sync_term_translation_needed( $el_id, $taxonomy );
			}
		}
	}

	public function check_if_sync_terms_needed() {

		$wcml_settings                                = $this->woocommerce_wpml->get_settings();
		$wcml_settings['sync_variations']             = 0;
		$wcml_settings['sync_product_cat']            = 0;
		$wcml_settings['sync_product_tag']            = 0;
		$wcml_settings['sync_product_shipping_class'] = 0;
		$this->woocommerce_wpml->update_settings( $wcml_settings );

		$taxonomies_to_check = [ 'product_cat', 'product_tag', self::PRODUCT_SHIPPING_CLASS ];

		foreach ( $taxonomies_to_check as $check_taxonomy ) {
			$terms = get_terms(
				$check_taxonomy,
				[
					'hide_empty' => false,
					'fields'     => 'ids',
				]
			);
			if ( is_array( $terms ) ) {
				foreach ( $terms as $term ) {
					if ( $this->check_if_sync_term_translation_needed( $term['term_taxonomy_id'], $check_taxonomy ) ) {
						break;
					}
				}
			}
		}

		$attribute_taxonomies = wc_get_attribute_taxonomies();
		$flag_set             = false;
		foreach ( $attribute_taxonomies as $a ) {

			$terms = get_terms(
				'pa_' . $a->attribute_name,
				[
					'hide_empty' => false,
					'fields'     => 'ids',
				]
			);
			if ( is_array( $terms ) ) {
				foreach ( $terms as $term ) {
					$flag_set = $this->check_if_sync_term_translation_needed( $term['term_taxonomy_id'], 'pa_' . $a->attribute_name );
					if ( $flag_set ) {
						break;
					}
				}
			}

			if ( $flag_set ) {
				break;
			}
		}

	}

	public function check_if_sync_term_translation_needed( $t_id, $taxonomy ) {

		$wcml_settings = $this->woocommerce_wpml->get_settings();

		$attribute_taxonomies     = wc_get_attribute_taxonomies();
		$attribute_taxonomies_arr = [];
		foreach ( $attribute_taxonomies as $a ) {
			$attribute_taxonomies_arr[] = 'pa_' . $a->attribute_name;
		}

		if ( ( isset( $wcml_settings[ 'sync_' . $taxonomy ] ) && $wcml_settings[ 'sync_' . $taxonomy ] ) || ( in_array( $taxonomy, $attribute_taxonomies_arr ) && isset( $wcml_settings['sync_variations'] ) && $wcml_settings['sync_variations'] ) ) {
			return true;
		}

		$translations = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT t2.element_id, t2.source_language_code FROM {$this->wpdb->prefix}icl_translations AS t1 LEFT JOIN {$this->wpdb->prefix}icl_translations AS t2 ON t1.trid = t2.trid WHERE t1.element_id = %d AND t1.element_type = %s ", $t_id, 'tax_' . $taxonomy ) );

		foreach ( $translations as $key => $translation ) {
			if ( is_null( $translation->source_language_code ) ) {
				$original_count = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT count( object_id ) FROM {$this->wpdb->term_relationships} WHERE term_taxonomy_id = %d ", $translation->element_id ) );
				unset( $translations[ $key ] );
			}
		}

		foreach ( $translations as $translation ) {

			$count = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT count( object_id ) FROM {$this->wpdb->term_relationships} WHERE term_taxonomy_id = %d ", $translation->element_id ) );
			if ( $original_count != $count ) {

				if ( in_array( $taxonomy, [ 'product_cat', 'product_tag', self::PRODUCT_SHIPPING_CLASS ] ) ) {
					$wcml_settings[ 'sync_'.$taxonomy ] = 1;
                    $this->woocommerce_wpml->update_settings($wcml_settings);
                    return true;
                }

				if ( isset( $attribute_taxonomies_arr ) && in_array( $taxonomy, $attribute_taxonomies_arr ) ) {
					$wcml_settings['sync_variations'] = 1;
					$this->woocommerce_wpml->update_settings( $wcml_settings );
					return true;
				}
			}
		}

	}

	public function get_table_taxonomies( $taxonomies ) {

		foreach ( $taxonomies as $key => $taxonomy ) {
			if ( substr( $key, 0, 3 ) !== 'pa_' ) {
				unset( $taxonomies[ $key ] );
			}
		}

		return $taxonomies;
	}

	public function get_wc_taxonomies() {

		global $wp_taxonomies;
		$taxonomies = [];

		// don't use get_taxonomies for product, because when one more post type registered for product taxonomy functions returned taxonomies only for product type.
		foreach ( $wp_taxonomies as $key => $taxonomy ) {

			if (
				( in_array( 'product', $taxonomy->object_type ) || in_array( 'product_variation', $taxonomy->object_type ) ) &&
				! in_array( $key, $taxonomies )
			) {

				if ( substr( $key, 0, 3 ) == 'pa_' && ! $this->woocommerce_wpml->attributes->is_translatable_attribute( $key ) ) {
					continue;
				}

				$taxonomies[] = $key;
			}
		}

		return $taxonomies;

	}

	public function has_wc_taxonomies_to_translate() {

		$taxonomies = $this->get_wc_taxonomies();

		$no_tax_to_trnls = false;
		foreach ( $taxonomies as $taxonomy ) {

			$is_fully_translated = 0 === $this->get_untranslated_terms_number( $taxonomy );
			if (
				! $this->is_translatable_wc_taxonomy( $taxonomy ) ||
				$is_fully_translated
			) {
				continue;
			} else {
				$no_tax_to_trnls = true;
			}
		}

		return $no_tax_to_trnls;

	}

	/*
	* Use custom query, because get_term_by function return false for terms with "0" slug      *
	*/
	public function wcml_get_term_id_by_slug( $taxonomy, $slug ) {

		return $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT tt.term_id FROM {$this->wpdb->terms} AS t
                        INNER JOIN {$this->wpdb->term_taxonomy} AS tt
                        ON t.term_id = tt.term_id
                        WHERE tt.taxonomy = %s AND t.slug = %s LIMIT 1",
				$taxonomy,
				sanitize_title( $slug )
			)
		);
	}

	public function wcml_get_term_by_id( $term_id, $taxonomy ) {

		return $this->wpdb->get_row(
			$this->wpdb->prepare(
				"
                        SELECT * FROM {$this->wpdb->terms} t
                        JOIN {$this->wpdb->term_taxonomy} x
                        ON x.term_id = t.term_id
                        WHERE t.term_id = %d AND x.taxonomy = %s",
				$term_id,
				$taxonomy
			)
		);
	}

	public function wcml_get_translated_term( $term_id, $taxonomy, $language ) {

		$tr_id = apply_filters( 'translate_object_id', $term_id, $taxonomy, false, $language );

		if ( ! is_null( $tr_id ) ) {
			$term_id = $tr_id;
		}

		return $this->wcml_get_term_by_id( $term_id, $taxonomy );
	}

	public function is_translatable_wc_taxonomy( $taxonomy ) {
		if ( in_array( $taxonomy, [ 'product_type', 'product_visibility' ], true ) ) {
			return false;
		}

		return true;
	}

	public function pre_option_default_product_cat() {

		$lang = $this->sitepress->get_current_language();

		$lang          = $lang === 'all' ? $this->sitepress->get_default_language() : $lang;
		$wcml_settings = $this->woocommerce_wpml->get_settings();
		$ttid          = isset( $wcml_settings['default_categories'][ $lang ] ) ? (int) $wcml_settings['default_categories'][ $lang ] : 0;

		return $ttid === 0
			? false : $this->wpdb->get_var(
				$this->wpdb->prepare(
					"SELECT term_id
		                     FROM {$this->wpdb->term_taxonomy}
		                     WHERE term_taxonomy_id= %d
		                     AND taxonomy='product_cat'",
					$ttid
				)
			);
	}

	public function update_option_default_product_cat( $oldvalue, $new_value ) {
		$new_value     = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT term_taxonomy_id FROM {$this->wpdb->term_taxonomy} WHERE taxonomy='product_cat' AND term_id=%d", $new_value ) );
		$translations  = $this->sitepress->get_element_translations( $this->sitepress->get_element_trid( $new_value, 'tax_product_cat' ) );
		$wcml_settings = $this->woocommerce_wpml->get_settings();

		if ( ! empty( $translations ) ) {
			foreach ( $translations as $t ) {
				$wcml_settings['default_categories'][ $t->language_code ] = $t->element_id;
			}
			if ( isset( $wcml_settings ) ) {
				$this->woocommerce_wpml->update_settings( $wcml_settings );
			}
		}
	}

	public function update_category_count_meta( $meta_id, $object_id, $meta_key, $meta_value ) {

		if ( 'product_count_product_cat' === $meta_key ) {
			remove_action( 'update_term_meta', [ $this, 'update_category_count_meta' ], 10, 4 );

			$trid         = $this->sitepress->get_element_trid( $object_id, 'tax_product_cat' );
			$translations = $this->sitepress->get_element_translations( $trid, 'tax_product_cat' );

			foreach ( $translations as $translation ) {
				if ( $translation->element_id !== $object_id ) {
					update_term_meta( $translation->element_id, $meta_key, $meta_value );
				}
			}

			add_action( 'update_term_meta', [ $this, 'update_category_count_meta' ], 10, 4 );
		}
	}

	/**
	 * @param string $key
	 *
	 * @return string
	 */
	public function add_lang_parameter_to_cache_key( $key ) {
		return $key . '-' . $this->sitepress->get_current_language();
	}

}
