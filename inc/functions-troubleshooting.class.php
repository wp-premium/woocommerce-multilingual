<?php

class WCML_Troubleshooting{

    function __construct(){

        add_action('init', array($this, 'init'));

    }

    function init(){
        add_action('wp_ajax_trbl_sync_products', array($this,'trbl_sync_products'));
        add_action('wp_ajax_trbl_sync_variations', array($this,'trbl_sync_variations'));
        add_action('wp_ajax_trbl_gallery_images', array($this,'trbl_gallery_images'));
        add_action('wp_ajax_trbl_sync_categories', array($this,'trbl_sync_categories'));
        add_action('wp_ajax_trbl_duplicate_terms', array($this,'trbl_duplicate_terms'));

    }

    function wcml_count_products_with_variations(){
       return count(get_option('wcml_variable_products_to_sync'));
    }

    function wcml_count_products(){
        global $woocommerce_wpml;
        return $woocommerce_wpml->products->get_products_count( false );
    }

    function wcml_sync_variations_update_option(){
        global $wpdb;
        $get_variation_term_taxonomy_ids = $wpdb->get_var("SELECT tt.term_taxonomy_id FROM $wpdb->terms AS t LEFT JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE t.name = 'variable'");
        $get_variation_term_taxonomy_ids = apply_filters('wcml_variation_term_taxonomy_ids',(array)$get_variation_term_taxonomy_ids);

        $get_variables_products = $wpdb->get_results($wpdb->prepare("SELECT tr.element_id as id,tr.language_code as lang FROM {$wpdb->prefix}icl_translations AS tr LEFT JOIN $wpdb->term_relationships as t ON tr.element_id = t.object_id LEFT JOIN $wpdb->posts AS p ON tr.element_id = p.ID
                                WHERE p.post_status IN ('publish','future','draft','pending','private') AND tr.source_language_code is NULL AND tr.element_type = 'post_product' AND t.term_taxonomy_id IN (%s) ORDER BY tr.element_id",join(',',$get_variation_term_taxonomy_ids)),ARRAY_A);

        update_option('wcml_variable_products_to_sync',$get_variables_products);
    }

    function wcml_count_products_for_gallery_sync(){
        global $wpdb;
        $all_products = $wpdb->get_results("SELECT p.ID FROM $wpdb->posts AS p LEFT JOIN {$wpdb->prefix}icl_translations AS tr ON tr.element_id = p.ID WHERE p.post_status IN ('publish','future','draft','pending','private') AND p.post_type =  'product' AND tr.source_language_code is NULL");
        foreach($all_products as $key=>$product){
            if(get_post_meta($product->ID,'gallery_sync',true)){
                unset($all_products[$key]);
            }
        }
        return count($all_products);
    }

    function wcml_count_product_categories(){
        global $wpdb;
        $get_product_categories = $wpdb->get_results("SELECT t.term_taxonomy_id FROM $wpdb->term_taxonomy AS t LEFT JOIN {$wpdb->prefix}icl_translations AS tr ON tr.element_id = t.term_taxonomy_id WHERE t.taxonomy = 'product_cat' AND tr.element_type = 'tax_product_cat' AND tr.source_language_code is NULL");
        foreach($get_product_categories as $key=>$get_product_category){
            if(get_option('wcml_sync_category_'.$get_product_category->term_taxonomy_id)){
                unset($get_product_categories[$key]);
            }
        }
        return count($get_product_categories);
    }


    function trbl_sync_products(){
        $nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if( !$nonce || !wp_verify_nonce($nonce, 'trbl_sync_products')){
            die('Invalid nonce');
        }

        global $woocommerce_wpml, $sitepress, $wpdb;

        $all_active_lang = $sitepress->get_active_languages();
        $products_to_sync = get_option( 'wcml_products_to_synchronize', true );

        if( empty( $products_to_sync ) ){

            $products_to_sync = $wpdb->get_results("
                                      SELECT tr.element_id as id, tr.language_code as lang
                                      FROM {$wpdb->prefix}icl_translations AS tr
                                      LEFT JOIN $wpdb->posts AS p
                                      ON tr.element_id = p.ID
                                      WHERE p.post_status IN ('publish','future','draft','pending','private')
                                      AND tr.source_language_code is NULL
                                      AND tr.element_type = 'post_product'
                                      ORDER BY tr.element_id", ARRAY_A
                                );
        }

        $unset_keys = array();
        $products_for_one_ajax = array_slice( $products_to_sync, 0, 3, true );

        $current_language = $sitepress->get_current_language();

        foreach ( $products_for_one_ajax as $key => $product ){

            if( !$woocommerce_wpml->products->is_variable_product( $product[ 'id' ] ) ){

                $sitepress->switch_lang( $sitepress->get_language_for_element( $product[ 'id' ], 'post_product' ) );

                $woocommerce_wpml->products->sync_post_action( $product[ 'id' ], get_post( $product[ 'id' ] ) );

            }

            if( !in_array( $key, $unset_keys ) ){
                $unset_keys[] = $key;
            }
        }

        $sitepress->switch_lang( $current_language );

        foreach( $unset_keys as $unset_key ){
            unset( $products_to_sync[ $unset_key ] );
        }

        update_option('wcml_products_to_synchronize', $products_to_sync );

        echo 1;

        die();
    }

    function trbl_sync_variations(){

        $nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if( !$nonce || !wp_verify_nonce($nonce, 'trbl_sync_variations')){
            die('Invalid nonce');
        }

        $sync_data = filter_input( INPUT_POST, 'sync_data', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $sync_new = filter_input( INPUT_POST, 'sync_new', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $sync_icl = filter_input( INPUT_POST, 'sync_icl', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

        global $woocommerce_wpml,$wpdb,$sitepress;

        $get_variables_products = get_option('wcml_variable_products_to_sync');
        $all_active_lang = $sitepress->get_active_languages();
        $unset_keys = array();
        $products_for_one_ajax = array_slice($get_variables_products,0,3,true);


        foreach ($products_for_one_ajax as $key => $product){
            foreach($all_active_lang as $language){
                if($language['code'] != $product['lang']){
                    $tr_product_id = apply_filters( 'translate_object_id',$product['id'],'product',false,$language['code']);

                    if( !is_null( $tr_product_id ) ){

                        if ( $sync_data || $sync_new  ){
                            $woocommerce_wpml->products->sync_product_variations( $product['id'], $tr_product_id, $language['code'], false, true, $sync_new, $sync_data );
                        }

                        if( $sync_icl ){
                            $this->sync_variations_language_info( $product['id'], $tr_product_id );
                        }

                    }

                    if(!in_array($key,$unset_keys)){
                        $unset_keys[] = $key;
                    }
                }
            }
        }


        foreach($unset_keys as $unset_key){
            unset($get_variables_products[$unset_key]);
        }

        update_option('wcml_variable_products_to_sync',$get_variables_products);

        $wcml_settings = get_option('_wcml_settings');
        if(isset($wcml_settings['notifications']) && isset($wcml_settings['notifications']['varimages'])){
            $wcml_settings['notifications']['varimages']['show'] = 0;
            update_option('_wcml_settings', $wcml_settings);
        }

        echo 1;

        die();
    }

    function sync_variations_language_info( $product_id, $tr_product_id ){

        global $wpdb, $sitepress, $woocommerce_wpml;

        $is_variable_product = $woocommerce_wpml->products->is_variable_product( $product_id );

        if( $is_variable_product ) {
            $translated_variations = $wpdb->get_results(
                                            $wpdb->prepare("
                                              SELECT * FROM $wpdb->posts
                                              WHERE post_status IN ( 'publish', 'future', 'draft', 'pending', 'private' )
                                              AND post_type = 'product_variation'
                                              AND post_parent = %d
                                              ORDER BY ID", $tr_product_id
                                            )
                                        );

            foreach ( $translated_variations as $translated_variation ) {
                //delete broken variations
                if ( !get_post_meta( $translated_variation->ID, '_stock', true ) ) {
                    wp_delete_post( $translated_variation->ID );
                    continue;
                }

                //check relationships
                $orig_variation_id = get_post_meta( $translated_variation->ID, '_wcml_duplicate_of_variation', true );

                $tr_info_for_original_variation = $wpdb->get_row(
                                                    $wpdb->prepare( "
                                                      SELECT trid, translation_id FROM {$wpdb->prefix}icl_translations
                                                      WHERE element_id = %d
                                                      AND element_type='post_product_variation'", $orig_variation_id
                                                    )
                                                );

                $original_language_details = $sitepress->get_element_language_details( $product_id, 'post_product' );

                $language_details_current = $sitepress->get_element_language_details( $tr_product_id, 'post_product' );

                $tr_info_for_current_variation = $wpdb->get_row(
                                                    $wpdb->prepare("
                                                      SELECT trid, translation_id FROM {$wpdb->prefix}icl_translations
                                                      WHERE element_id = %d
                                                      AND element_type='post_product_variation'",
                                                    $translated_variation->ID )
                                                );

                //delete wrong element_type for exists variations
                if ( !$tr_info_for_current_variation ) {
                    $tr_info_for_current_variation = $wpdb->get_row(
                                                        $wpdb->prepare("
                                                          SELECT trid, translation_id
                                                          FROM {$wpdb->prefix}icl_translations
                                                          WHERE element_id = %d
                                                          AND element_type='post_product'",
                                                        $translated_variation->ID )
                                                    );

                    if ( $tr_info_for_current_variation ) {
                        $wpdb->update(
                            $wpdb->prefix . 'icl_translations',
                            array( 'element_type' => 'post_product_variation' ),
                            array( 'translation_id' => $tr_info_for_current_variation->translation_id )
                        );
                    }
                }

                $check_duplicated_post_type = $wpdb->get_row(
                                                $wpdb->prepare("
                                                  SELECT trid, translation_id
                                                  FROM {$wpdb->prefix}icl_translations
                                                  WHERE element_id = %d
                                                  AND element_type='post_product'",
                                                $translated_variation->ID )
                                            );

                if ( $check_duplicated_post_type ) {
                    $wpdb->delete(
                        $wpdb->prefix . 'icl_translations',
                        array( 'translation_id' => $check_duplicated_post_type->translation_id )
                    );
                }


                //set language info for variation if not exists
                if ( !$tr_info_for_original_variation ) {

                    $tr_info_for_original_variation = $wpdb->get_row(
                                                        $wpdb->prepare("
                                                          SELECT trid, translation_id
                                                          FROM {$wpdb->prefix}icl_translations
                                                          WHERE element_id = %d
                                                          AND element_type='post_product'",
                                                        $orig_variation_id )
                                                    );
                    if ( $tr_info_for_original_variation ) {
                        $wpdb->update(
                            $wpdb->prefix . 'icl_translations',
                            array( 'element_type' => 'post_product_variation' ),
                            array( 'translation_id' => $tr_info_for_original_variation->translation_id )
                        );
                    } else {
                        $sitepress->set_element_language_details( $orig_variation_id, 'post_product_variation', $tr_info_for_current_variation->trid, $original_language_details->language_code );
                        $tr_info_for_original_variation = $wpdb->get_row(
                                                            $wpdb->prepare("
                                                              SELECT trid, translation_id
                                                              FROM {$wpdb->prefix}icl_translations
                                                              WHERE element_id = %d
                                                              AND element_type='post_product_variation'",
                                                            $orig_variation_id )
                                                        );
                    }

                    $wpdb->update(
                        $wpdb->prefix . 'icl_translations',
                        array( 'source_language_code' => $original_language_details->language_code ),
                        array( 'translation_id' => $tr_info_for_current_variation->translation_id )
                    );
                }

                if ( $tr_info_for_original_variation->trid != $tr_info_for_current_variation->trid ) {

                    $wpdb->update(
                        $wpdb->prefix . 'icl_translations',
                        array(
                            'trid' => $tr_info_for_original_variation->trid,
                            'language_code' => $language_details_current->language_code,
                            'source_language_code' => $original_language_details->language_code
                        ),
                        array( 'translation_id' => $tr_info_for_current_variation->translation_id )
                    );
                }

                //fix language code for original variation
                $language_details_original_variation = $sitepress->get_element_language_details( $orig_variation_id, 'post_product_variation' );
                if ( $original_language_details->language_code != $language_details_original_variation ) {

                    $wpdb->update(
                        $wpdb->prefix . 'icl_translations',
                        array( 'language_code' => $original_language_details->language_code ),
                        array( 'translation_id' => $tr_info_for_original_variation->translation_id )
                    );
                }
            }
        }
    }

    function trbl_gallery_images(){
        $nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if(!$nonce || !wp_verify_nonce($nonce, 'trbl_gallery_images')){
            die('Invalid nonce');
        }

        $page = isset($_POST['page'])?$_POST['page']:0;

        global $woocommerce_wpml,$wpdb;

        $all_products = $wpdb->get_results($wpdb->prepare("SELECT p.* FROM $wpdb->posts AS p LEFT JOIN {$wpdb->prefix}icl_translations AS tr ON tr.element_id = p.ID WHERE p.post_status IN ('publish','future','draft','pending','private') AND p.post_type =  'product' AND tr.source_language_code is NULL ORDER BY p.ID LIMIT %d,5",$page*5));

        foreach($all_products as $product){
            if(!get_post_meta($product->ID,'gallery_sync',true)){
                $woocommerce_wpml->products->sync_product_gallery($product->ID);
                add_post_meta($product->ID,'gallery_sync',true);
            }
        }

        echo 1;

        die();

    }

    function trbl_sync_categories(){
        $nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if(!$nonce || !wp_verify_nonce($nonce, 'trbl_sync_categories')){
            die('Invalid nonce');
        }

        $page = isset($_POST['page'])?$_POST['page']:0;

        global $wpdb,$sitepress;

        $all_categories = $wpdb->get_results($wpdb->prepare("SELECT t.term_taxonomy_id,t.term_id,tr.language_code FROM $wpdb->term_taxonomy AS t LEFT JOIN {$wpdb->prefix}icl_translations AS tr ON tr.element_id = t.term_taxonomy_id WHERE t.taxonomy = 'product_cat' AND tr.element_type = 'tax_product_cat' AND tr.source_language_code is NULL ORDER BY t.term_taxonomy_id LIMIT %d,5",$page*5));

        foreach($all_categories as $category){
            if(!get_option('wcml_sync_category_'.$category->term_taxonomy_id)){
                add_option('wcml_sync_category_'.$category->term_taxonomy_id,true);
                $trid = $sitepress->get_element_trid($category->term_taxonomy_id,'tax_product_cat');
                $translations = $sitepress->get_element_translations($trid,'tax_product_cat');
                $type = get_woocommerce_term_meta( $category->term_id, 'display_type',true);
                $thumbnail_id = get_woocommerce_term_meta( $category->term_id, 'thumbnail_id',true);
                foreach($translations as $translation){
                    if($translation->language_code != $category->language_code ){
                        update_woocommerce_term_meta( $translation->term_id, 'display_type', $type );
                        update_woocommerce_term_meta( $translation->term_id, 'thumbnail_id', apply_filters( 'translate_object_id',$thumbnail_id,'attachment',true,$translation->language_code) );
                    }
                }
            }

        }

        echo 1;

        die();

    }


    function trbl_duplicate_terms(){
        $nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if(!$nonce || !wp_verify_nonce($nonce, 'trbl_duplicate_terms')){
            die('Invalid nonce');
        }
        global $sitepress;

        $attr = isset($_POST['attr'])?$_POST['attr']:false;

        $terms = get_terms($attr,'hide_empty=0');
        $i = 0;
        $languages = $sitepress->get_active_languages();
        foreach($terms as $term){
            foreach($languages as $language){
                $tr_id = apply_filters( 'translate_object_id',$term->term_id, $attr, false, $language['code']);

                if(is_null($tr_id)){
                    $term_args = array();
                    // hierarchy - parents
                    if ( is_taxonomy_hierarchical( $attr ) ) {
                        // fix hierarchy
                        if ( $term->parent ) {
                            $original_parent_translated = apply_filters( 'translate_object_id', $term->parent, $attr, false, $language['code'] );
                            if ( $original_parent_translated ) {
                                $term_args[ 'parent' ] = $original_parent_translated;
                            }
                        }
                    }

                    if( version_compare( ICL_SITEPRESS_VERSION, '3.1.8.2', '<=' ) ){
                        $term_name = $term->name.' @'.$language['code'];
                    }else{
                        $term_name = $term->name;
                        $slug = $term->name.'-'.$language['code'];
                        $slug = WPML_Terms_Translations::term_unique_slug( $slug, $attr, $language['code'] );
                        $term_args[ 'slug' ] = $slug;
                    }

                    $new_term = wp_insert_term( $term_name , $attr, $term_args );
                    if ( $new_term && !is_wp_error( $new_term ) ) {
                        $tt_id = $sitepress->get_element_trid( $term->term_taxonomy_id, 'tax_' . $attr );
                        $sitepress->set_element_language_details( $new_term[ 'term_taxonomy_id' ], 'tax_' . $attr, $tt_id, $language['code'] );
                    }
                }
            }

        }

        echo 1;

        die();
    }

}