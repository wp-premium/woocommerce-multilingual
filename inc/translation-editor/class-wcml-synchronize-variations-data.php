<?php

class WCML_Synchronize_Variations_Data{

    private $woocommerce_wpml;
    private $sitepress;
    private $wpdb;

    public function __construct( &$woocommerce_wpml, &$sitepress, &$wpdb ) {
        $this->woocommerce_wpml = $woocommerce_wpml;
        $this->sitepress = $sitepress;
        $this->wpdb = $wpdb;

        add_action( 'woocommerce_ajax_save_product_variations', array( $this, 'sync_product_variations_action' ), 11 );
        add_action( 'wp_ajax_woocommerce_remove_variations', array( $this, 'remove_translations_for_variations' ), 9 );

        //save taxonomy in WPML interface
        add_action( 'wp_ajax_wpml_tt_save_term_translation', array( $this, 'update_taxonomy_in_variations' ), 7 );

        add_action( 'wp_ajax_woocommerce_remove_variation', array( $this, 'remove_variation_ajax' ), 9 );
    }

    public function sync_product_variations_action( $product_id ){

        if( $this->woocommerce_wpml->products->is_original_product( $product_id ) ){

            if( $this->woocommerce_wpml->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT ) {
                $this->woocommerce_wpml->multi_currency->custom_prices->sync_product_variations_custom_prices($product_id);
            }
            $trid = $this->sitepress->get_element_trid( $product_id, 'post_product' );

            if ( empty( $trid ) ) {
                $trid = $this->wpdb->get_var(
                    $this->wpdb->prepare(
                        "SELECT trid FROM {$this->wpdb->prefix}icl_translations
                                    WHERE element_id = %d AND element_type = 'post_product'",
                        $product_id )
                );
            }
            $translations = $this->sitepress->get_element_translations( $trid, 'post_product' );
            foreach ( $translations as $translation ) {
                if ( !$translation->original ) {
                    $this->sync_product_variations($product_id, $translation->element_id, $translation->language_code);
                    $this->woocommerce_wpml->attributes->sync_default_product_attr($product_id, $translation->element_id, $translation->language_code);
                }
            }
        }
    }

    /*
    * sync product variations
    * $product_id - original product id
    * $tr_product_id - translated product id
    * $lang - trnsl language
    * $data - array of values (when we save original product this array is empty, but when we update translation in this array we have price values and etc.)     *
    * */
    public function sync_product_variations( $product_id, $tr_product_id, $lang, $data = false, $trbl = false ){
        global $sitepress_settings, $wpml_post_translations;

        remove_action ( 'save_post', array( $wpml_post_translations, 'save_post_actions' ), 100, 2 );
        $is_variable_product = $this->woocommerce_wpml->products->is_variable_product( $product_id );

        if( $is_variable_product ){
            $get_all_post_variations = $this->wpdb->get_results(
                $this->wpdb->prepare(
                    "SELECT * FROM {$this->wpdb->posts}
                                                WHERE post_status IN ('publish','private')
                                                  AND post_type = 'product_variation'
                                                  AND post_parent = %d ORDER BY ID",
                    $product_id )
            );
            $duplicated_post_variation_ids = array();
            $min_max_prices = array();

            foreach( $get_all_post_variations as $k => $post_data ){
                $duplicated_post_variation_ids[] = $post_data->ID;
            }

            foreach( $min_max_prices as $price_key => $min_max_price ){
                update_post_meta( $product_id, $price_key, $min_max_price );
            }
            $all_taxs = get_object_taxonomies( 'product_variation' );

            foreach( $get_all_post_variations as $k => $post_data ) {
                $original_variation_id = $post_data->ID;

                // Find if this has already been duplicated
                $variation_id = $this->wpdb->get_var(
                    $this->wpdb->prepare(
                        "SELECT post_id FROM {$this->wpdb->postmeta} AS pm
                                        JOIN {$this->wpdb->prefix}icl_translations AS tr ON tr.element_id = pm.post_id
                                        WHERE tr.element_type = 'post_product_variation'
                                          AND tr.language_code = %s
                                          AND pm.meta_key = '_wcml_duplicate_of_variation'
                                          AND pm.meta_value = %d",
                        $lang, $original_variation_id )
                );
                $trid = $this->sitepress->get_element_trid( $original_variation_id, 'post_product_variation' );
                if( !empty( $variation_id ) && !is_null( $variation_id ) ) {
                    // Update variation
                    wp_update_post( array(
                        'ID' => $variation_id,
                        'post_author' => $post_data->post_author,
                        'post_date_gmt' => $post_data->post_date_gmt,
                        'post_content' => $post_data->post_content,
                        'post_title' => $post_data->post_title,
                        'post_excerpt' => $post_data->post_excerpt,
                        'post_status' => $post_data->post_status,
                        'comment_status' => $post_data->comment_status,
                        'ping_status' => $post_data->ping_status,
                        'post_password' => $post_data->post_password,
                        'post_name' => $post_data->post_name,
                        'to_ping' => $post_data->to_ping,
                        'pinged' => $post_data->pinged,
                        'post_modified' => $post_data->post_modified,
                        'post_modified_gmt' => $post_data->post_modified_gmt,
                        'post_content_filtered' => $post_data->post_content_filtered,
                        'post_parent' => $tr_product_id, // current post ID
                        'menu_order' => $post_data->menu_order,
                        'post_type' => $post_data->post_type,
                        'post_mime_type' => $post_data->post_mime_type,
                        'comment_count' => $post_data->comment_count
                    ));
                } else {
                    // Add new variation
                    $guid = $post_data->guid;
                    $replaced_guid = str_replace( $product_id, $tr_product_id, $guid );
                    $slug = $post_data->post_name;
                    $replaced_slug = str_replace( $product_id, $tr_product_id, $slug );
                    $variation_id = wp_insert_post( array(
                        'post_author' => $post_data->post_author,
                        'post_date_gmt' => $post_data->post_date_gmt,
                        'post_content' => $post_data->post_content,
                        'post_title' => $post_data->post_title,
                        'post_excerpt' => $post_data->post_excerpt,
                        'post_status' => $post_data->post_status,
                        'comment_status' => $post_data->comment_status,
                        'ping_status' => $post_data->ping_status,
                        'post_password' => $post_data->post_password,
                        'post_name' => $replaced_slug,
                        'to_ping' => $post_data->to_ping,
                        'pinged' => $post_data->pinged,
                        'post_modified' => $post_data->post_modified,
                        'post_modified_gmt' => $post_data->post_modified_gmt,
                        'post_content_filtered' => $post_data->post_content_filtered,
                        'post_parent' => $tr_product_id, // current post ID
                        'guid' => $replaced_guid,
                        'menu_order' => $post_data->menu_order,
                        'post_type' => $post_data->post_type,
                        'post_mime_type' => $post_data->post_mime_type,
                        'comment_count' => $post_data->comment_count
                    ));
                    add_post_meta( $variation_id, '_wcml_duplicate_of_variation', $original_variation_id );
                    $this->sitepress->set_element_language_details( $variation_id, 'post_product_variation', $trid, $lang );
                }
                //sync media
                $this->woocommerce_wpml->media->sync_thumbnail_id( $original_variation_id, $variation_id, $lang );
                //sync file_paths
                $this->woocommerce_wpml->downloadable->sync_files_to_translations( $original_variation_id, $variation_id, $data );
                //sync description
                if( isset( $data[ md5( 'variation_desc'.$original_variation_id ) ] ) ){
                    update_post_meta( $variation_id, '_variation_description', $data[ md5( 'variation_desc'.$original_variation_id ) ] );
                }
                // sync taxonomies
                if ( !empty( $all_taxs ) ) {
                    foreach ( $all_taxs as $tt ) {
                        $terms = get_the_terms( $original_variation_id, $tt );
                        if ( !empty( $terms ) ) {
                            $tax_sync = array();
                            foreach ( $terms as $term ) {
                                if ( $this->sitepress->is_translated_taxonomy( $tt ) ) {
                                    $term_id = apply_filters( 'translate_object_id', $term->term_id, $tt, false, $lang );
                                } else {
                                    $term_id = $term->term_id;
                                }
                                if ( $term_id ) {
                                    $tax_sync[] = intval( $term_id );
                                }
                            }
                            //set the fourth parameter in 'true' because we need to add new terms, instead of replacing all
                            wp_set_object_terms( $variation_id, $tax_sync, $tt, true );
                        }
                    }
                }
            }
            $get_current_post_variations = $this->wpdb->get_results(
                $this->wpdb->prepare(
                    "SELECT * FROM {$this->wpdb->posts}
                                                    WHERE post_status IN ('publish','private')
                                                    AND post_type = 'product_variation'
                                                    AND post_parent = %d ORDER BY ID",
                    $tr_product_id )
            );
            // Delete variations that no longer exist
            foreach( $get_current_post_variations as $key => $current_post_variation ){
                $orig_variation_id = get_post_meta( $current_post_variation->ID, '_wcml_duplicate_of_variation', true );
                if( !in_array( $orig_variation_id, $duplicated_post_variation_ids ) ){
                    wp_delete_post( $current_post_variation->ID, true );
                    unset( $get_current_post_variations[ $key ] );
                }
            }
            // custom fields to copy
            $cf = (array)$sitepress_settings[ 'translation-management' ][ 'custom_fields_translation' ];
            // synchronize post variations post meta
            $current_post_variation_ids = array();
            foreach( $get_current_post_variations as $k => $current_post_variation ){
                $current_post_variation_ids[] = $current_post_variation->ID;
            }
            // refresh parent-children transients
            delete_transient( 'wc_product_children_' . $tr_product_id );
            delete_transient( '_transient_wc_product_children_ids_' . $tr_product_id );
            $original_product_attr = get_post_meta( $product_id, '_product_attributes', true );
            $tr_product_attr = get_post_meta( $tr_product_id, '_product_attributes', true );

            foreach( $duplicated_post_variation_ids as $dp_key => $duplicated_post_variation_id ){
                $get_all_post_meta = $this->wpdb->get_results(
                    $this->wpdb->prepare(
                        "SELECT * FROM {$this->wpdb->postmeta} WHERE post_id = %d",
                        $duplicated_post_variation_id )
                );
                //delete non exists attributes
                $get_all_variation_attributes = $this->wpdb->get_results(
                    $this->wpdb->prepare(
                        "SELECT * FROM {$this->wpdb->postmeta}
                                                        WHERE post_id = %d
                                                          AND meta_key LIKE 'attribute_%%' ",
                        $current_post_variation_ids[ $dp_key ] )
                );
                foreach( $get_all_variation_attributes as $variation_attribute ){
                    $attribute_name = substr( $variation_attribute->meta_key, 10 );
                    if( !isset( $original_product_attr[ $attribute_name ] ) ){
                        delete_post_meta( $current_post_variation_ids[ $dp_key ], $variation_attribute->meta_key );
                    }
                }

                $post_fields = null;
                foreach( $get_all_post_meta as $k => $post_meta ){

                    $meta_key = $post_meta->meta_key;
                    $meta_value = maybe_unserialize( $post_meta->meta_value );
                    // update current post variations meta
                    if( ( substr( $meta_key, 0, 10 ) == 'attribute_' || isset( $cf[ $meta_key ] ) && $cf[ $meta_key ] == 1 ) ) {
                        // adjust the global attribute slug in the custom field
                        $attid = null;
                        if( substr( $meta_key, 0, 10 ) == 'attribute_' ) {
                            $tax = wc_sanitize_taxonomy_name ( substr( $meta_key, 10 ) );
                            if( taxonomy_exists( $tax ) ){
                                $attid = $this->woocommerce_wpml->terms->wcml_get_term_id_by_slug( $tax, $meta_value );
                                if( $this->woocommerce_wpml->attributes->is_translatable_attribute( $tax ) && $attid ){
                                    $term_obj = $this->woocommerce_wpml->terms->wcml_get_term_by_id( $attid, $tax );
                                    $trid = $this->sitepress->get_element_trid( $term_obj->term_taxonomy_id, 'tax_' . $tax );
                                    if( $trid ) {
                                        $translations = $this->sitepress->get_element_translations( $trid, 'tax_' . $tax );
                                        if( isset( $translations[ $lang ] ) ){
                                            $meta_value = $this->wpdb->get_var(
                                                $this->wpdb->prepare(
                                                    "SELECT slug FROM {$this->wpdb->terms} WHERE term_id = %s",
                                                    $translations[ $lang ]->term_id )
                                            );
                                        }else{
                                            $meta_value = $meta_value.'_'.$lang;
                                        }
                                    }
                                }
                            }else{
                                if( !isset( $original_product_attr[ $tax ] ) ){
                                    $tax = sanitize_title( $tax );
                                }

                                if( isset( $original_product_attr[ $tax ] ) ){
                                    if( isset( $tr_product_attr[ $tax ] ) ){
                                        $values_arrs = array_map( 'trim', explode( '|', $original_product_attr[ $tax ][ 'value' ] ) );
                                        $values_arrs_tr = array_map( 'trim', explode( '|', $tr_product_attr[ $tax ][ 'value' ] ) );

                                        foreach( $values_arrs as $key => $value ){
                                            $value_sanitized = sanitize_title( $value );
                                            if(
                                                ( $value_sanitized == strtolower( urldecode( $meta_value ) ) ||
                                                    strtolower( $value_sanitized ) == $meta_value ||
                                                    $value == $meta_value )
                                                && isset( $values_arrs_tr[ $key ] ) )
                                            {
                                                $meta_value = $values_arrs_tr[ $key ];
                                            }
                                        }
                                    }else{
                                        $meta_value = $meta_value.'_'.$lang;
                                    }
                                }
                                $meta_key = 'attribute_'.$tax;
                            }

                        }
                        update_post_meta( $current_post_variation_ids[ $dp_key ], $meta_key, $meta_value );
                    }
                    //sync variation prices
                    if(
                        ( $this->woocommerce_wpml->settings[ 'enable_multi_currency' ] == WCML_MULTI_CURRENCIES_INDEPENDENT || $trbl ) &&
                        in_array( $meta_key, array( '_sale_price', '_regular_price', '_price' ) )
                    ){
                        $meta_value = get_post_meta( $duplicated_post_variation_ids[ $dp_key ], $meta_key, true );
                        update_post_meta( $current_post_variation_ids[ $dp_key ], $meta_key, $meta_value );
                    }

                    if( isset( $cf[ $meta_key ] ) && $cf[ $meta_key ] == WPML_TRANSLATE_CUSTOM_FIELD ){
                        //sync custom fields
                        $post_fields = $this->woocommerce_wpml->sync_product_data->sync_custom_field_value( $meta_key, $data, $variation_id, $post_fields, $original_variation_id, true );
                    }
                }
            }
        }
        add_action ( 'save_post', array( $wpml_post_translations, 'save_post_actions' ), 100, 2 );
    }

    public function remove_translations_for_variations(){
        check_ajax_referer( 'delete-variations', 'security' );

        if ( ! current_user_can( 'edit_products' ) ) {
            die(-1);
        }
        $variation_ids = (array) $_POST['variation_ids'];

        foreach ( $variation_ids as $variation_id ) {
            $trid = $this->sitepress->get_element_trid( $variation_id, 'post_product_variation' );
            $translations = $this->sitepress->get_element_translations( $trid, 'post_product_variation' );

            foreach ( $translations as $translation ) {
                if ( !$translation->original ) {
                    wp_delete_post( $translation->element_id );
                }
            }
        }
    }

    //update taxonomy in variations
    public function update_taxonomy_in_variations(){
        $original_element   = filter_input( INPUT_POST, 'translation_of', FILTER_SANITIZE_NUMBER_INT );
        $taxonomy           = filter_input( INPUT_POST, 'taxonomy', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $language           = filter_input( INPUT_POST, 'language', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $slug               = filter_input( INPUT_POST, 'slug', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $name               = filter_input( INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $term_id = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT term_id FROM {$this->wpdb->term_taxonomy} WHERE term_taxonomy_id = %d"
                ,$original_element )
        );
        $original_term = $this->woocommerce_wpml->terms->wcml_get_term_by_id( $term_id, $taxonomy );
        $original_slug = $original_term->slug;
        //get variations with original slug
        $variations = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT post_id FROM {$this->wpdb->postmeta} WHERE meta_key=%s AND meta_value = %s",
                'attribute_'.$taxonomy, $original_slug
            )
        );

        foreach( $variations as $variation ){
            //update taxonomy in translation of variation
            $trnsl_variation_id = apply_filters( 'translate_object_id', $variation->post_id, 'product_variation', false, $language );
            if( !is_null( $trnsl_variation_id ) ){
                if( !$slug ){
                    $slug = sanitize_title( $name );
                }
                update_post_meta( $trnsl_variation_id, 'attribute_'.$taxonomy, $slug );
            }
        }
    }

    public function remove_variation_ajax(){
        if( isset( $_POST[ 'variation_id' ] ) ){
            $trid = $this->sitepress->get_element_trid( filter_input( INPUT_POST, 'variation_id', FILTER_SANITIZE_NUMBER_INT ), 'post_product_variation' );
            if( $trid ){
                $translations = $this->sitepress->get_element_translations( $trid, 'post_product_variation' );
                if( $translations ){
                    foreach( $translations as $translation ){
                        if( !$translation->original ){
                            wp_delete_post( $translation->element_id, true );
                        }
                    }
                }
            }
        }
    }

}