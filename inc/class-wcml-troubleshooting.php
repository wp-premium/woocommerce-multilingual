<?php

class WCML_Troubleshooting{

    function __construct(){

        add_action('init', array($this, 'init'));

    }

    function init(){
        add_action('wp_ajax_trbl_sync_variations', array($this,'trbl_sync_variations'));
        add_action('wp_ajax_trbl_gallery_images', array($this,'trbl_gallery_images'));
        add_action('wp_ajax_trbl_update_count', array($this,'trbl_update_count'));
        add_action('wp_ajax_trbl_sync_categories', array($this,'trbl_sync_categories'));
        add_action('wp_ajax_trbl_duplicate_terms', array($this,'trbl_duplicate_terms'));

    }

    function wcml_count_products_with_variations(){
       return count(get_option('wcml_products_to_sync'));
    }

    function trbl_update_count(){

        $nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if(!$nonce || !wp_verify_nonce($nonce, 'trbl_update_count')){
            die('Invalid nonce');
        }

        $this->wcml_sync_variations_update_option();
        echo $this->wcml_count_products_with_variations();

        die();
    }

    function wcml_sync_variations_update_option(){
        global $wpdb;
        $get_variation_term_taxonomy_ids = $wpdb->get_var("SELECT tt.term_taxonomy_id FROM $wpdb->terms AS t LEFT JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE t.name = 'variable'");
        $get_variation_term_taxonomy_ids = apply_filters('wcml_variation_term_taxonomy_ids',(array)$get_variation_term_taxonomy_ids);

        $get_variables_products = $wpdb->get_results($wpdb->prepare("SELECT tr.element_id as id,tr.language_code as lang FROM {$wpdb->prefix}icl_translations AS tr LEFT JOIN $wpdb->term_relationships as t ON tr.element_id = t.object_id LEFT JOIN $wpdb->posts AS p ON tr.element_id = p.ID
                                WHERE p.post_status = 'publish' AND tr.source_language_code is NULL AND tr.element_type = 'post_product' AND t.term_taxonomy_id IN (%s) ORDER BY tr.element_id",join(',',$get_variation_term_taxonomy_ids)),ARRAY_A);

        update_option('wcml_products_to_sync',$get_variables_products);
    }

    function wcml_count_products(){
        global $wpdb;
        $get_products_count = $wpdb->get_var("SELECT count(ID) FROM $wpdb->posts AS p LEFT JOIN {$wpdb->prefix}icl_translations AS tr ON tr.element_id = p.ID WHERE p.post_status = 'publish' AND p.post_type =  'product' AND tr.source_language_code is NULL");
        return $get_products_count;
    }

    function wcml_count_products_for_gallery_sync(){
        global $wpdb;
        $all_products = $wpdb->get_results("SELECT p.ID FROM $wpdb->posts AS p LEFT JOIN {$wpdb->prefix}icl_translations AS tr ON tr.element_id = p.ID WHERE p.post_status = 'publish' AND p.post_type =  'product' AND tr.source_language_code is NULL");
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


    function trbl_sync_variations(){

        $nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if(!$nonce || !wp_verify_nonce($nonce, 'trbl_sync_variations')){
            die('Invalid nonce');
        }

        global $woocommerce_wpml,$wpdb,$sitepress;

        $get_variables_products = get_option('wcml_products_to_sync');
        $all_active_lang = $sitepress->get_active_languages();
        $unset_keys = array();
        $products_for_one_ajax = array_slice($get_variables_products,0,3,true);


        foreach ($products_for_one_ajax as $key => $product){
            foreach($all_active_lang as $language){
                if($language['code'] != $product['lang']){
                    $tr_product_id = apply_filters( 'translate_object_id',$product['id'],'product',false,$language['code']);

                    if(!is_null($tr_product_id)){
                        $woocommerce_wpml->sync_variations_data->sync_product_variations($product['id'],$tr_product_id,$language['code'],false,true);
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

        update_option('wcml_products_to_sync',$get_variables_products);

        $wcml_settings = get_option('_wcml_settings');
        if(isset($wcml_settings['notifications']) && isset($wcml_settings['notifications']['varimages'])){
            $wcml_settings['notifications']['varimages']['show'] = 0;
            update_option('_wcml_settings', $wcml_settings);
        }

        echo 1;

        die();
    }

    function trbl_gallery_images(){
        $nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if(!$nonce || !wp_verify_nonce($nonce, 'trbl_gallery_images')){
            die('Invalid nonce');
        }

        $page = isset($_POST['page'])?$_POST['page']:0;

        global $woocommerce_wpml,$wpdb;

        $all_products = $wpdb->get_results($wpdb->prepare("SELECT p.* FROM $wpdb->posts AS p LEFT JOIN {$wpdb->prefix}icl_translations AS tr ON tr.element_id = p.ID WHERE p.post_status = 'publish' AND p.post_type =  'product' AND tr.source_language_code is NULL ORDER BY p.ID LIMIT %d,5",$page*5));

        foreach($all_products as $product){
            if(!get_post_meta($product->ID,'gallery_sync',true)){
                $woocommerce_wpml->media->sync_product_gallery($product->ID);
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

                    $term_name = $term->name;
                    $slug = $term->name.'-'.$language['code'];
                    $slug = WPML_Terms_Translations::term_unique_slug( $slug, $attr, $language['code'] );
                    $term_args[ 'slug' ] = $slug;

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