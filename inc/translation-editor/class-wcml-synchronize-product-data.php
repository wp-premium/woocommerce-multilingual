<?php

class WCML_Synchronize_Product_Data{

    private $woocommerce_wpml;
    /**
     * @var SitePress
     */
    private $sitepress;
    private $wpdb;

    public function __construct( &$woocommerce_wpml, &$sitepress, &$wpdb ) {
        $this->woocommerce_wpml = $woocommerce_wpml;
        $this->sitepress = $sitepress;
        $this->wpdb = $wpdb;

        if( is_admin() ){
            // filters to sync variable products
            add_action( 'save_post', array( $this, 'sync_post_action' ), 110, 2 ); // After WPML

            add_action( 'icl_pro_translation_completed', array( $this, 'icl_pro_translation_completed' ) );

            add_filter( 'icl_make_duplicate', array( $this, 'icl_make_duplicate'), 110, 4 );
            add_action( 'woocommerce_duplicate_product', array( $this, 'woocommerce_duplicate_product' ), 10, 2 );

            //quick & bulk edit
            add_action( 'woocommerce_product_quick_edit_save', array( $this, 'woocommerce_product_quick_edit_save' ) );
            add_action( 'woocommerce_product_bulk_edit_save', array( $this, 'woocommerce_product_quick_edit_save' ) );

            add_action( 'init', array( $this, 'init' ) );
        }

        add_action( 'woocommerce_reduce_order_stock', array( $this, 'sync_product_stocks_reduce' ) );
        add_action( 'woocommerce_restore_order_stock', array( $this, 'sync_product_stocks_restore' ) );
        add_action( 'woocommerce_product_set_stock_status', array($this, 'sync_stock_status_for_translations' ), 10, 2);
        add_action( 'woocommerce_variation_set_stock_status', array($this, 'sync_stock_status_for_translations' ), 10, 2);

        add_filter( 'future_product', array( $this, 'set_schedule_for_translations'), 10, 2 );
    }

    public function init(){
        $this->check_ajax_actions();
    }

    /**
     * This function takes care of synchronizing original product
     */
    public function sync_post_action( $post_id, $post ){
        global $pagenow, $wp;

        $original_language  = $this->woocommerce_wpml->products->get_original_product_language( $post_id );
        $current_language   = $this->sitepress->get_current_language();
        $duplicated_post_id = apply_filters( 'translate_object_id', $post_id, 'product', false, $original_language );

        $wpml_media_options = maybe_unserialize( get_option( '_wpml_media' ) );

        if( $wpml_media_options[ 'new_content_settings' ][ 'duplicate_media' ] ){
            //sync product gallery
            $this->woocommerce_wpml->media->sync_product_gallery( $duplicated_post_id );
        }
        // check its a product
        $post_type = get_post_type( $post_id );
        //set trid for variations
        if ( $post_type == 'product_variation' ) {
            $var_lang = $this->sitepress->get_language_for_element( wp_get_post_parent_id( $post_id ), 'post_product' );
            if( $this->woocommerce_wpml->products->is_original_product( wp_get_post_parent_id( $post_id ) ) ){
                $this->sitepress->set_element_language_details( $post_id, 'post_product_variation', false, $var_lang );
            }
        }

        if ( $post_type != 'product' ) {
            return;
        }

        // exceptions
        $ajax_call = ( !empty( $_POST[ 'icl_ajx_action' ] ) && $_POST[ 'icl_ajx_action' ] == 'make_duplicates' );
        $api_call  = !empty( $wp->query_vars['wc-api-version'] );
        if ( ( empty( $duplicated_post_id ) || isset( $_POST[ 'autosave' ] ) ) ||
            ( $pagenow != 'post.php' && $pagenow != 'post-new.php' && $pagenow != 'admin.php' && !$ajax_call && !$api_call ) ||
            ( isset( $_GET[ 'action' ] ) && $_GET[ 'action' ] == 'trash' )
        ) {
            return;
        }
        // If we reach this point, we go ahead with sync.
        // Remove filter to avoid double sync
        remove_action( 'save_post', array( $this, 'sync_post_action' ), 110, 2 );
        do_action( 'wcml_before_sync_product', $duplicated_post_id, $post_id );


        //trnsl_interface option
        if ( !$this->woocommerce_wpml->settings['trnsl_interface'] && $original_language != $current_language ) {
            if( !isset( $_POST[ 'wp-preview' ] ) || empty( $_POST[ 'wp-preview' ] ) ){
                //make sure we sync post in current language
                $post_id = apply_filters( 'translate_object_id', $post_id, 'product', false, $current_language );
                do_action( 'wcml_before_sync_product_data', $duplicated_post_id, $post_id, $current_language );
                $this->sync_date_and_parent( $duplicated_post_id, $post_id, $current_language );
                $this->sync_product_data( $duplicated_post_id, $post_id, $current_language );
            }
            return;
        }

        // get language code
        $language_details = $this->sitepress->get_element_language_details( $post_id, 'post_product' );
        if ( $pagenow == 'admin.php' && empty( $language_details ) ) {
            //translation editor support: sidestep icl_translations_cache
            $language_details = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT element_id, trid, language_code, source_language_code FROM {$this->wpdb->prefix}icl_translations
                                        WHERE element_id = %d AND element_type = 'post_product'",
                    $post_id )
            );
        }
        if ( empty( $language_details ) ) {
            return;
        }

        //save files option
        $this->woocommerce_wpml->downloadable->save_files_option( $duplicated_post_id );
        // pick posts to sync
        $traslated_products = array();
        $translations = $this->sitepress->get_element_translations( $language_details->trid, 'post_product', false, true );

        foreach ( $translations as $translation ) {
            if ( $translation->original ) {
                $original_product_id = $translation->element_id;
            } else {
                $traslated_products[ $translation->element_id ] = $translation;
            }
        }

        foreach( $traslated_products as $translated_product_id => $translation ) {
            $lang = $translation->language_code;
            do_action( 'wcml_before_sync_product_data', $original_product_id, $translated_product_id, $lang );
            // Filter upsell products, crosell products and default attributes for translations
            $this->duplicate_product_post_meta( $original_product_id, $translated_product_id );
            if( $wpml_media_options[ 'new_content_settings' ][ 'duplicate_featured' ] ){
                //sync feature image
                $this->woocommerce_wpml->media->sync_thumbnail_id( $original_product_id, $translated_product_id, $lang );
            }
            $this->sync_date_and_parent( $original_product_id, $translated_product_id, $lang );
            $this->sync_product_taxonomies( $original_product_id, $translated_product_id, $lang );
            $this->woocommerce_wpml->attributes->sync_default_product_attr( $original_product_id, $translated_product_id, $lang );
            $this->woocommerce_wpml->attributes->sync_product_attr( $original_product_id, $translated_product_id );
            $this->woocommerce_wpml->products->update_order_for_product_translations( $original_product_id );
            // synchronize post variations
            $this->woocommerce_wpml->sync_variations_data->sync_product_variations( $original_product_id, $translated_product_id, $lang );
            $this->sync_linked_products( $original_product_id, $translated_product_id, $lang );

            // Clear any unwanted data
            wc_delete_product_transients( $translated_product_id );
        }
        if( $this->woocommerce_wpml->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT ) {
            //save custom prices
            $this->woocommerce_wpml->multi_currency->custom_prices->save_custom_prices( $duplicated_post_id );
            $this->woocommerce_wpml->multi_currency->custom_prices->sync_product_variations_custom_prices($original_product_id);
        }
    }

    public function sync_product_data( $original_product_id, $tr_product_id, $lang ){

        $this->duplicate_product_post_meta( $original_product_id, $tr_product_id );

        $this->woocommerce_wpml->attributes->sync_product_attr( $original_product_id, $tr_product_id );

        $this->woocommerce_wpml->attributes->sync_default_product_attr( $original_product_id, $tr_product_id, $lang );

        $wpml_media_options = maybe_unserialize( get_option( '_wpml_media' ) );
        //sync media
        if( $wpml_media_options[ 'new_content_settings' ][ 'duplicate_featured' ] ){
            //sync feature image
            $this->woocommerce_wpml->media->sync_thumbnail_id( $original_product_id, $tr_product_id, $lang );
        }

        if( $wpml_media_options[ 'new_content_settings' ][ 'duplicate_media' ]){
            //sync product gallery
            $this->woocommerce_wpml->media->sync_product_gallery( $original_product_id );
        }

        //sync taxonomies
        $this->sync_product_taxonomies( $original_product_id, $tr_product_id, $lang );

        //duplicate variations

        $this->woocommerce_wpml->sync_variations_data->sync_product_variations( $original_product_id, $tr_product_id, $lang );

        $this->sync_linked_products( $original_product_id, $tr_product_id, $lang );

        // Clear any unwanted data
        wc_delete_product_transients( $tr_product_id );
    }

    public function sync_product_taxonomies( $original_product_id, $tr_product_id, $lang ){
        remove_filter( 'get_term', array( $this->sitepress,'get_term_adjust_id' ) ); // AVOID filtering to current language
        $taxonomies = get_object_taxonomies( 'product' );
        foreach( $taxonomies as $taxonomy ) {
            $terms = get_the_terms( $original_product_id, $taxonomy );
            $terms_array = array();
            $terms_tax_id_array = array();
            if ( $terms ) {
                foreach ( $terms as $term ) {
                    if( $term->taxonomy == "product_type" ){
                        $terms_array[] = $term->name;
                        continue;
                    }
                    $tr_id = apply_filters( 'translate_object_id', $term->term_id, $taxonomy, false, $lang );
                    if( !is_null( $tr_id ) ){
                        // not using get_term - unfiltered get_term
                        $translated_term = $this->wpdb->get_row( $this->wpdb->prepare( "
                            SELECT * FROM {$this->wpdb->terms} t JOIN {$this->wpdb->term_taxonomy} x ON x.term_id = t.term_id WHERE t.term_id = %d AND x.taxonomy = %s", $tr_id, $taxonomy ) );
                        $terms_array[] = $translated_term->term_id;
                        $terms_tax_id_array[] = $translated_term->term_taxonomy_id;
                    }
                }
                if( $taxonomy != 'product_type' && !is_taxonomy_hierarchical( $taxonomy ) ){
                    $terms_array = array_unique( array_map( 'intval', $terms_array ) );
                }
                $this->sitepress->switch_lang( $lang );
                wp_set_post_terms( $tr_product_id, $terms_array, $taxonomy );
                $this->sitepress->switch_lang();

                wp_update_term_count( $terms_tax_id_array, $taxonomy );
            }
        }
    }



    public function sync_linked_products( $product_id, $translated_product_id, $lang ){
        //sync up-sells
        $original_up_sells = maybe_unserialize( get_post_meta( $product_id, '_upsell_ids', true ) );
        $trnsl_up_sells = array();
        if( $original_up_sells ){
            foreach( $original_up_sells as $original_up_sell_product ) {
                $trnsl_up_sells[] = apply_filters( 'translate_object_id', $original_up_sell_product, get_post_type( $original_up_sell_product ), false, $lang );
            }
        }
        update_post_meta( $translated_product_id, '_upsell_ids', $trnsl_up_sells );
        //sync cross-sells
        $original_cross_sells = maybe_unserialize( get_post_meta( $product_id, '_crosssell_ids', true ) );
        $trnsl_cross_sells = array();
        if( $original_cross_sells )
            foreach( $original_cross_sells as $original_cross_sell_product ){
                $trnsl_cross_sells[] = apply_filters( 'translate_object_id', $original_cross_sell_product, get_post_type( $original_cross_sell_product ), false, $lang );
            }
        update_post_meta( $translated_product_id, '_crosssell_ids', $trnsl_cross_sells );
        // refresh parent-children transients (e.g. this child goes to private or draft)
        $translated_product_parent_id = wp_get_post_parent_id( $translated_product_id );
        if ( $translated_product_parent_id ) {
            delete_transient( 'wc_product_children_' . $translated_product_parent_id );
            delete_transient( '_transient_wc_product_children_ids_' . $translated_product_parent_id );
        }
    }



    public function sync_product_stocks_reduce( $order ){
        return $this->sync_product_stocks( $order, 'reduce' );
    }

    public function sync_product_stocks_restore( $order ){
        return $this->sync_product_stocks( $order, 'restore' );
    }

    /**
     * @param $order WC_Order
     * @param $action
     */
    public function sync_product_stocks( $order, $action ){
        $order_id = method_exists( 'WC_Order', 'get_id' ) ? $order->get_id() : $order->id;

        foreach( $order->get_items() as $item ) {

            if( $item instanceof WC_Order_Item_Product ){
                $variation_id = $item->get_variation_id();
                $product_id = $item->get_product_id();
                $qty = $item->get_quantity();
            }else{
                $variation_id = isset( $item[ 'variation_id' ] ) ? $item[ 'variation_id' ] : 0;
                $product_id = $item[ 'product_id' ];
                $qty = $item[ 'qty' ];
            }

            if( $variation_id > 0 ){
                $trid = $this->sitepress->get_element_trid( $variation_id, 'post_product_variation' );
                $translations = $this->sitepress->get_element_translations( $trid, 'post_product_variation' );
                $ld = $this->sitepress->get_element_language_details( $variation_id, 'post_product_variation' );
            } else {
                $trid = $this->sitepress->get_element_trid( $product_id, 'post_product' );
                $translations = $this->sitepress->get_element_translations( $trid, 'post_product' );
                $ld = $this->sitepress->get_element_language_details( $product_id, 'post_product' );
            }

            // Process for non-current languages
            foreach( $translations as $translation ){
                if ( $ld->language_code != $translation->language_code ) {
                    //check if product exist
                    if( get_post_type( $translation->element_id ) == 'product_variation' && !get_post( wp_get_post_parent_id( $translation->element_id ) ) ){
                        continue;
                    }
                    $_product = wc_get_product( $translation->element_id );

                    if( $_product && $_product->exists() && $_product->managing_stock() ) {
                        $total_sales    = get_post_meta($_product->id, 'total_sales', true);

                        if( $action == 'reduce'){
                            $stock  = $_product->reduce_stock( $qty );
                            $total_sales   += $qty;
                        }else{
                            $stock  = $_product->increase_stock( $qty );
                            $total_sales   -= $qty;
                        }
                        update_post_meta( $translation->element_id, 'total_sales', $total_sales );
                    }
                }
            }
        }
    }

    public function sync_stock_status_for_translations( $id, $status ){

        $type = get_post_type( $id );
        $trid = $this->sitepress->get_element_trid( $id, 'post_'.$type );
        $translations = $this->sitepress->get_element_translations( $trid, 'post_'.$type, true);

        foreach ( $translations as $translation ) {
            if ( !$translation->original ) {
                update_post_meta( $translation->element_id, '_stock_status', $status );
            }
        }
    }



    //sync product parent & post_status
    public function sync_date_and_parent( $duplicated_post_id, $post_id, $lang ){
        $tr_parent_id = apply_filters( 'translate_object_id', wp_get_post_parent_id( $duplicated_post_id ), 'product', false, $lang );
        $orig_product = get_post( $duplicated_post_id );
        $args = array();
        $args[ 'post_parent' ] = is_null( $tr_parent_id )? 0 : $tr_parent_id;
        //sync product date

        if( !empty( $this->woocommerce_wpml->settings[ 'products_sync_date' ] ) ){
            $args[ 'post_date' ] = $orig_product->post_date;
        }
        $this->wpdb->update(
            $this->wpdb->posts,
            $args,
            array( 'id' => $post_id )
        );
    }

    public function set_schedule_for_translations( $deprecated, $post ){

        if( $this->woocommerce_wpml->products->is_original_product( $post->ID ) ) {
            $trid = $this->sitepress->get_element_trid( $post->ID, 'post_product');
            $translations = $this->sitepress->get_element_translations( $trid, 'post_product', true);
            foreach( $translations as $translation ) {
                if( !$translation->original ){
                    wp_clear_scheduled_hook( 'publish_future_post', array( $translation->element_id ) );
                    wp_schedule_single_event( strtotime( get_gmt_from_date( $post->post_date) . ' GMT' ), 'publish_future_post', array( $translation->element_id ) );
                }
            }
        }
    }



    public function icl_pro_translation_completed( $tr_product_id ){
        $trid = $this->sitepress->get_element_trid( $tr_product_id, 'post_product' );
        $translations = $this->sitepress->get_element_translations( $trid, 'post_product' );

        foreach( $translations as $translation ){
            if( $translation->original ){
                $original_product_id = $translation->element_id;
            }
        }

        if( !isset( $original_product_id ) ){
            return;
        }

        $lang = $this->sitepress->get_language_for_element( $tr_product_id, 'post_product' );
        $this->sync_product_data( $original_product_id, $tr_product_id, $lang );
    }

    public function icl_make_duplicate( $master_post_id, $lang, $postarr, $id ){
        if( get_post_type( $master_post_id ) == 'product' ){

            $original_language  = $this->woocommerce_wpml->products->get_original_product_language( $master_post_id );
            $master_post_id = apply_filters( 'translate_object_id', $master_post_id, 'product', false, $original_language );

            $this->sync_product_data( $master_post_id, $id, $lang );
            // recount terms only first time
            if( !get_post_meta( $id, '_wcml_terms_recount' ) ){
                $product_cats = wp_get_post_terms( $id, 'product_cat' );

                if( !empty( $product_cats ) ) {
                    foreach ($product_cats as $product_cat) {
                        $cats_to_recount[ $product_cat->term_id ] = $product_cat->parent;
                    }
                    _wc_term_recount( $cats_to_recount, get_taxonomy( 'product_cat' ), true, false );
                    add_post_meta( $id, '_wcml_terms_recount', 'yes' );
                }
            }
        }
    }

    public function woocommerce_product_quick_edit_save( $product ){
        $is_original = $this->woocommerce_wpml->products->is_original_product( $product->id );
        $trid = $this->sitepress->get_element_trid( $product->id, 'post_product' );

        if( $trid ){
            $translations = $this->sitepress->get_element_translations( $trid, 'post_product' );
            if( $translations ){
                foreach( $translations as $translation ){
                    if( $is_original ){
                        if( !$translation->original ){
                            $this->sync_product_data( $product->id, $translation->element_id, $translation->language_code );
                            $this->sync_date_and_parent( $product->id, $translation->element_id, $translation->language_code );
                        }
                    }elseif( $translation->original ){
                        $this->sync_product_data( $translation->element_id, $product->id, $this->sitepress->get_language_for_element( $product->id, 'post_product' ) );
                        $this->sync_date_and_parent( $translation->element_id, $product->id, $this->sitepress->get_language_for_element( $product->id, 'post_product' ) );
                    }
                }
            }
        }
    }



    //duplicate product post meta
    public function duplicate_product_post_meta( $original_product_id, $trnsl_product_id, $data = false ){
        global $iclTranslationManagement;
        $settings = $iclTranslationManagement->settings[ 'custom_fields_translation' ];
        $all_meta = get_post_custom( $original_product_id );
        $post_fields = null;

        unset( $all_meta[ '_thumbnail_id' ] );

        foreach ( $all_meta as $key => $meta ) {
            if ( !isset( $settings[ $key ] ) || $settings[ $key ] == WPML_IGNORE_CUSTOM_FIELD ) {
                continue;
            }
            foreach ( $meta as $meta_value ) {
                if( $key == '_downloadable_files' ){
                    $this->woocommerce_wpml->downloadable->sync_files_to_translations( $original_product_id, $trnsl_product_id, $data );
                }elseif ( $data ) {
                    if ( isset( $settings[ $key ] ) && $settings[ $key ] == WPML_TRANSLATE_CUSTOM_FIELD ) {

                        $post_fields = $this->sync_custom_field_value( $key, $data, $trnsl_product_id, $post_fields );
                    }
                }
            }
        }

        do_action( 'wcml_after_duplicate_product_post_meta', $original_product_id, $trnsl_product_id, $data );
    }

    public function sync_custom_field_value( $custom_field, $translation_data, $trnsl_product_id, $post_fields,  $original_product_id = false, $is_variation = false ){

        if( is_null( $post_fields ) ){
            $post_fields = array();
            if( isset( $_POST['data'] ) ){
                $post_args = wp_parse_args( $_POST['data'] );
                $post_fields = $post_args[ 'fields' ];
            }
        }


        $custom_filed_key = $is_variation && $original_product_id ? $custom_field.$original_product_id : $custom_field;

        if( isset( $translation_data[ md5( $custom_filed_key ) ] ) ){
            $meta_value = $translation_data[ md5( $custom_filed_key ) ];
            $meta_value = apply_filters( 'wcml_meta_value_before_add', $meta_value, $custom_filed_key );
            update_post_meta( $trnsl_product_id, $custom_field, $meta_value );
            unset( $post_fields[ $custom_filed_key ] );
        }else{
            foreach( $post_fields as $post_field_key => $post_field ){
                $meta_value = $translation_data[ md5( $post_field_key ) ];
                $field_key = explode( ':', $post_field_key );
                if( $field_key[0] == $custom_filed_key ){
                    if( substr( $field_key[1], 0, 3 ) == 'new' ){
                        add_post_meta( $trnsl_product_id, $custom_field, $meta_value );
                    }else{
                        update_meta( $field_key[1], $custom_field, $meta_value );
                    }
                    unset( $post_fields[ $post_field_key ] );
                }
            }
        }

        return $post_fields;
    }

    public function woocommerce_duplicate_product( $new_id, $post ){
        $duplicated_products = array();

        //duplicate original first
        $trid = $this->sitepress->get_element_trid( $post->ID, 'post_' . $post->post_type );
        $orig_id = $this->sitepress->get_original_element_id_by_trid( $trid );
        $orig_lang = $this->woocommerce_wpml->products->get_original_product_language( $post->ID );

        $wc_admin = new WC_Admin_Duplicate_Product();

        if( $orig_id == $post->ID ){
            $this->sitepress->set_element_language_details( $new_id, 'post_' . $post->post_type, false, $orig_lang );
            $new_trid = $this->sitepress->get_element_trid( $new_id, 'post_' . $post->post_type );
            $new_orig_id = $new_id;
        }else{
            $post_to_duplicate = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT * FROM {$this->wpdb->posts} WHERE ID=%d", $orig_id ) );
            if ( ! empty( $post_to_duplicate ) ) {
                $new_orig_id = $wc_admin->duplicate_product( $post_to_duplicate );
                do_action( 'wcml_after_duplicate_product' , $new_id, $post_to_duplicate );
                $this->sitepress->set_element_language_details( $new_orig_id, 'post_' . $post->post_type, false, $orig_lang );
                $new_trid = $this->sitepress->get_element_trid( $new_orig_id, 'post_' . $post->post_type );
                if( get_post_meta( $orig_id, '_icl_lang_duplicate_of' ) ){
                    update_post_meta( $new_id, '_icl_lang_duplicate_of', $new_orig_id );
                }
                $this->sitepress->set_element_language_details( $new_id, 'post_' . $post->post_type, $new_trid, $this->sitepress->get_current_language() );
            }
        }

        // Set language info for variations
        if ( $children_products = get_children( 'post_parent=' . $new_orig_id . '&post_type=product_variation' ) ) {
            foreach ( $children_products as $child ) {
                $this->sitepress->set_element_language_details( $child->ID, 'post_product_variation', false, $orig_lang );
            }
        }

        $translations = $this->sitepress->get_element_translations( $trid, 'post_' . $post->post_type );
        $duplicated_products[ 'translations' ] = array();
        if( $translations ){
            foreach( $translations as $translation ){
                if( !$translation->original && $translation->element_id != $post->ID ){
                    $post_to_duplicate = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT * FROM {$this->wpdb->posts} WHERE ID=%d", $translation->element_id ) );

                    if( ! empty( $post_to_duplicate ) ) {
                        $new_id = $wc_admin->duplicate_product( $post_to_duplicate );
                        $new_id_obj = get_post( $new_id );
                        $new_slug = wp_unique_post_slug( sanitize_title( $new_id_obj->post_title ), $new_id, $post_to_duplicate->post_status, $post_to_duplicate->post_type, $new_id_obj->post_parent );

                        $this->wpdb->update(
                            $this->wpdb->posts,
                            array(
                                'post_name'     => $new_slug,
                                'post_status'   => 'draft'
                            ),
                            array( 'ID' => $new_id )
                        );

                        do_action( 'wcml_after_duplicate_product' , $new_id, $post_to_duplicate );
                        $this->sitepress->set_element_language_details( $new_id, 'post_' . $post->post_type, $new_trid, $translation->language_code );
                        if( get_post_meta( $translation->element_id, '_icl_lang_duplicate_of' ) ){
                            update_post_meta( $new_id, '_icl_lang_duplicate_of', $new_orig_id );
                        }
                        $duplicated_products[ 'translations' ][] = $new_id;
                    }
                }
            }
        }

        $duplicated_products[ 'original' ] = $new_orig_id;

        return $duplicated_products;
    }

    public function check_ajax_actions(){
        if( isset( $_POST[ 'icl_ajx_action' ] ) && $_POST[ 'icl_ajx_action' ] == 'connect_translations' ){
            $this->icl_connect_translations_action();
        }
    }

    public function icl_connect_translations_action(){
        $new_trid = $_POST['new_trid'];
        $post_type = $_POST['post_type'];
        $post_id = $_POST['post_id'];
        $set_as_source = $_POST['set_as_source'];
        if( $post_type == 'product' ){

            $translations = $this->sitepress->get_element_translations( $new_trid, 'post_' . $post_type );
            if( $translations ) {
                foreach ( $translations as $translation ) {
                    //current as original need sync translation
                    if ( $translation->original ) {
                        if( $set_as_source ) {
                            $orig_id = $post_id;
                            $trnsl_id = $translation->element_id;
                            $lang = $translation->language_code;
                        }else{
                            $orig_id = $translation->element_id;
                            $trnsl_id = $post_id;
                            $lang = $this->sitepress->get_current_language();
                        }
                        $this->sync_product_data( $orig_id, $trnsl_id, $lang );
                        $this->sync_date_and_parent( $orig_id, $trnsl_id, $lang );
                        $this->sitepress->copy_custom_fields( $orig_id, $trnsl_id );
                    }else {
                        if( $set_as_source ) {
                            $this->sync_product_data( $post_id, $translation->element_id, $translation->language_code );
                            $this->sync_date_and_parent( $post_id, $translation->element_id, $translation->language_code );
                            $this->sitepress->copy_custom_fields( $post_id, $translation->element_id );
                        }
                    }
                }
            }
        }
    }

}