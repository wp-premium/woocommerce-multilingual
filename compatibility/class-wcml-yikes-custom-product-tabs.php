<?php

/**
 * Class WCML_YIKES_Custom_Product_Tabs
 */
class WCML_YIKES_Custom_Product_Tabs {

	const CUSTOM_TABS_FIELD = 'yikes_woo_products_tabs';

	/**
	 * @var WPML_Element_Translation_Package
	 */
	private $tp;

	/**
	 * @var SitePress
	 */
	private $sitepress;

	/**
	 * @var woocommerce_wpml
	 */
	private $woocommerce_wpml;

	/**
	 * WCML_Tab_Manager constructor.
	 *
	 * @param woocommerce_wpml $woocommerce_wpml
	 * @param SitePress $sitepress
	 * @param WPML_Element_Translation_Package $tp
	 */
	function __construct( woocommerce_wpml $woocommerce_wpml, SitePress $sitepress, WPML_Element_Translation_Package $tp ) {
		$this->sitepress        = $sitepress;
		$this->woocommerce_wpml = $woocommerce_wpml;
		$this->tp               = $tp;
	}

	public function add_hooks() {

		if ( is_admin() ) {
			add_action( 'wcml_gui_additional_box_html', array( $this, 'custom_box_html' ), 10, 3 );
			add_filter( 'wcml_gui_additional_box_data', array( $this, 'custom_box_html_data' ), 10, 4 );
			add_action( 'wcml_update_extra_fields', array( $this, 'sync_tabs' ), 10, 4 );
			add_filter( 'wpml_duplicate_custom_fields_exceptions', array( $this, 'custom_fields_exceptions' ) );
			add_filter( 'wcml_do_not_display_custom_fields_for_product', array( $this, 'custom_fields_exceptions' ) );

			add_filter( 'wpml_tm_translation_job_data', array( $this, 'append_custom_tabs_to_translation_package' ), 10, 2 );
			add_action( 'wpml_translation_job_saved',   array( $this, 'save_custom_tabs_translation' ), 10, 3 );

			add_action( 'woocommerce_product_data_panels', array( $this, 'show_pointer_info' ) );
			add_action( 'init', array( $this, 'maybe_remove_admin_language_switcher' ) );
		}
	}

	/**
	 * @param object $obj
	 * @param int $product_id
	 * @param array $data
	 *
	 */
	function custom_box_html( $obj, $product_id, $data ) {

		$orig_prod_tabs = $this->get_product_tabs( $product_id );

		if ( $orig_prod_tabs ) {
			$tabs_section = new WPML_Editor_UI_Field_Section( __( 'Custom Tabs', 'woocommerce-multilingual' ) );

			$keys     = array_keys( $orig_prod_tabs );
			$last_key = end( $keys );
			$divider  = true;

			foreach ( $orig_prod_tabs as $key => $prod_tab ) {
				if ( $key === $last_key ) {
					$divider = false;
				}
				$group     = new WPML_Editor_UI_Field_Group( '', $divider );
				$tab_field = new WPML_Editor_UI_Single_Line_Field( 'tab_' . $key . '_title', __( 'Tab Title', 'woocommerce-multilingual' ), $data, false );
				$group->add_field( $tab_field );
				$tab_field = new WCML_Editor_UI_WYSIWYG_Field( 'tab_' . $key . '_content', null, $data, false );
				$group->add_field( $tab_field );
				$tabs_section->add_field( $group );
			}

			$obj->add_field( $tabs_section );
		}
	}


	/**
	 * @param array $data
	 * @param int $product_id
	 * @param object $translation
	 * @param string $lang
	 *
	 * @return array
	 */
	function custom_box_html_data( $data, $product_id, $translation, $lang ) {

		$orig_prod_tabs = $this->get_product_tabs( $product_id );
		if ( $orig_prod_tabs ) {
			foreach ( $orig_prod_tabs as $key => $prod_tab ) {
				if ( isset( $prod_tab['title'] ) ) {
					$data[ 'tab_' . $key . '_title' ] = array( 'original' => $prod_tab['title'] );
				}
				if ( isset( $prod_tab['content'] ) ) {
					$data[ 'tab_' . $key . '_content' ] = array( 'original' => $prod_tab['content'] );
				}
			}

			if ( $translation ) {
				$tr_prod_tabs = $this->get_product_tabs( $translation->ID );
				if ( $tr_prod_tabs ) {
					foreach ( $tr_prod_tabs as $key => $prod_tab ) {
						if ( isset( $prod_tab['title'] ) ) {
							$data[ 'tab_' . $key . '_title' ]['translation'] = $prod_tab['title'];
						}
						if ( isset( $prod_tab['content'] ) ) {
							$data[ 'tab_' . $key . '_content' ]['translation'] = $prod_tab['content'];
						}
					}
				}
			}
		}

		return $data;
	}

	/**
	 * @param int $original_product_id
	 * @param int $trnsl_product_id
	 * @param array $data
	 * @param string $lang
	 *
	 */
	function sync_tabs( $original_product_id, $trnsl_product_id, $data, $lang ) {

		$orig_prod_tabs = $this->get_product_tabs( $original_product_id );

		if ( ( isset( $_POST['icl_ajx_action'] ) && ( 'make_duplicates' === $_POST['icl_ajx_action'] ) ) || ( get_post_meta( $trnsl_product_id, '_icl_lang_duplicate_of', true ) ) ) {
			update_post_meta( $trnsl_product_id, self::CUSTOM_TABS_FIELD, $orig_prod_tabs );
		} elseif ( $orig_prod_tabs ) {
			$trnsl_product_tabs = array();
			foreach ( $orig_prod_tabs as $key => $orig_prod_tab ) {
				$title_key                             = md5( 'tab_' . $key . '_title' );
				$content_key                           = md5( 'tab_' . $key . '_content' );
				$trnsl_product_tabs[ $key ]['id']      = $orig_prod_tab['id'];
				$trnsl_product_tabs[ $key ]['title']   = isset( $data[ $title_key ] ) ? sanitize_text_field( $data[ $title_key ] ) : '';
				$trnsl_product_tabs[ $key ]['content'] = isset( $data[ $content_key ] ) ? wp_kses_post( $data[ $content_key ] ) : '';
			}
			update_post_meta( $trnsl_product_id, self::CUSTOM_TABS_FIELD, $trnsl_product_tabs );
		}
	}

	/**
	 * @param int $product_id
	 *
	 * @return array
	 */
	private function get_product_tabs( $product_id ) {
		return (array) get_post_meta( $product_id, self::CUSTOM_TABS_FIELD, true );
	}


	/**
	 * @param array $fields
	 *
	 * @return array
	 */
	public function custom_fields_exceptions( $fields ) {
		$fields[] = self::CUSTOM_TABS_FIELD;
		return $fields;
	}

	/**
	 * @param array $package
	 * @param object $post
	 *
	 * @return array
	 */
	public function append_custom_tabs_to_translation_package( $package, $post ) {

		if ( 'product' === $post->post_type ) {

			$orig_prod_tabs = $this->get_product_tabs( $post->ID );
			if ( $orig_prod_tabs ) {
				foreach ( $orig_prod_tabs as $key => $prod_tab ) {
					if ( isset( $prod_tab['title'] ) ) {
						$package['contents'][ self::CUSTOM_TABS_FIELD . ':product_tab:' . $key . ':title' ] = array(
							'translate' => 1,
							'data'      => $this->tp->encode_field_data( $prod_tab['title'], 'base64' ),
							'format'    => 'base64',
						);
					}

					if ( isset( $prod_tab['content'] ) ) {
						$package['contents'][ self::CUSTOM_TABS_FIELD . ':product_tab:' . $key . ':content' ] = array(
							'translate' => 1,
							'data'      => $this->tp->encode_field_data( $prod_tab['content'], 'base64' ),
							'format'    => 'base64',
						);
					}
				}
			}
		}

		return $package;
	}

	/**
	 * @param int $post_id
	 * @param array $data
	 * @param object $job
	 */
	public function save_custom_tabs_translation( $post_id, $data, $job ) {

		$original_product_tabs = $this->get_product_tabs( $job->original_doc_id );

		if ( $original_product_tabs ) {

			$translated_product_tabs = $this->get_product_tabs( $post_id );

			if( !$translated_product_tabs ){
				$translated_product_tabs = $original_product_tabs;
			}

			foreach ( $data as $value ) {

				if ( preg_match( '/'.self::CUSTOM_TABS_FIELD.':product_tab:([0-9]+):(.+)/', $value['field_type'], $matches ) ) {

					$tab_key = $matches[1];
					$field   = $matches[2];

					$translated_product_tabs[ $tab_key ][ $field ] = 'title' === $field ? sanitize_text_field( $value['data'] ) : wp_kses_post( $value['data'] );
				}
			}

			update_post_meta( $post_id, self::CUSTOM_TABS_FIELD, $translated_product_tabs );
		}
	}

	public function show_pointer_info() {

		$a    = __( 'You can translate your custom product tabs on the %s', 'woocommerce-multilingual' );
		$b    = __( 'WooCommerce product translation page', 'woocommerce-multilingual' );
		$link = '<a href="' . admin_url( 'admin.php?page=wpml-wcml' ) . '">' . $b . '</a>';

		$pointer_ui = new WCML_Pointer_UI(
			sprintf( $a, $link ),
			'https://wpml.org/documentation/woocommerce-extensions-compatibility/',
			'yikes_woocommerce_custom_product_tabs',
			'prepend'
		);

		$pointer_ui->show();
	}

	public function maybe_remove_admin_language_switcher( ) {

		if ( isset( $_GET['page'] ) && 'yikes-woo-settings' === $_GET['page'] ) {
			remove_action( 'wp_before_admin_bar_render', array( $this->sitepress, 'admin_language_switcher' ) );
		}

	}

}
