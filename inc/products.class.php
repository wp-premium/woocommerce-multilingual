<?php

class WCML_Products{

    private $not_display_fields_for_variables_product = array( '_regular_price', '_sale_price', '_price', '_min_variation_price', '_max_variation_price', '_min_variation_regular_price', '_max_variation_regular_price', '_min_variation_sale_price', '_max_variation_sale_price' );
    private $tinymce_plugins_for_rtl = '';
    private $yoast_seo_fields = array( '_yoast_wpseo_focuskw', '_yoast_wpseo_title', '_yoast_wpseo_metadesc' );

    public $tp_support;

    function __construct(){

        add_action( 'init', array( $this, 'init' ) );
        add_action( 'init', array( $this, 'wc_cart_widget_actions' ) );

        //add action for coupons data from WC_Coupon construct
        add_action( 'woocommerce_coupon_loaded', array( $this, 'wcml_coupon_loaded' ) );

        add_action( 'init', array( $this, 'set_prices_config' ), 9999 ); // After TM

    }

    function init(){

        if(is_admin()){
            add_action( 'wp_ajax_wcml_update_product', array( $this, 'update_product_actions' ) );
            add_action( 'wp_ajax_wcml_product_data', array( $this, 'product_data_html' ) );

            add_filter( 'wpml_post_edit_page_link_to_translation', array( $this,'_filter_link_to_translation' ) );
            add_action( 'admin_init', array( $this, 'restrict_admin_with_redirect' ) );

            add_action( 'woocommerce_attribute_added', array( $this, 'make_new_attribute_translatable' ), 10, 2 );

            // filters to sync variable products
            add_action( 'save_post', array( $this, 'sync_post_action' ), 110, 2 ); // After WPML

            add_filter( 'future_product', array( $this, 'set_schedule_for_translations'), 10, 2 );
            //when save new attachment duplicate product gallery
            add_action( 'wpml_media_create_duplicate_attachment', array( $this, 'sync_product_gallery_duplicate_attachment' ), 11, 2 );
            add_action( 'woocommerce_ajax_save_product_variations', array( $this, 'sync_product_variations_action' ), 11 );
            add_action( 'wp_ajax_woocommerce_remove_variations', array( $this, 'remove_translations_for_variations' ), 9 );

            add_filter( 'icl_make_duplicate', array( $this, 'icl_make_duplicate'), 11, 4 );

            //remove media sync on product page
            add_action( 'admin_head', array( $this, 'remove_language_options' ), 11 );

            add_action( 'admin_print_scripts', array( $this, 'preselect_product_type_in_admin_screen' ), 11 );

            add_filter( 'woocommerce_json_search_found_products', array( $this, 'woocommerce_json_search_found_products' ) );
            add_filter( 'tiny_mce_before_init', array( $this, '_mce_set_plugins' ), 9 );

            add_action( 'admin_head', array( $this, 'hide_multilingual_content_setup_box' ) );

            add_action( 'woocommerce_duplicate_product', array( $this, 'woocommerce_duplicate_product' ), 10, 2 );

            add_filter( 'post_row_actions', array( $this, 'filter_product_actions' ), 10, 2 );

            add_filter ( 'locale',array( $this, 'update_product_action_locale_check' ) );

            add_filter( 'wpml_translation_job_post_meta_value_translated', array($this, 'filter_product_attributes_for_translation'), 10, 2 );

            add_action( 'wp_ajax_woocommerce_feature_product' , array( $this, 'sync_feature_product_meta' ), 9 );

            if ( defined( 'ICL_SITEPRESS_VERSION' ) && version_compare( ICL_SITEPRESS_VERSION, '3.2', '>=' ) ) {
                $this->tp_support = new WCML_TP_Support();
            }

        }else{
            add_filter('woocommerce_json_search_found_products', array($this, 'filter_found_products_by_language'));
            add_filter( 'loop_shop_post_in', array( $this, 'filter_products_with_custom_prices' ), 100 );
            add_filter( 'woocommerce_related_products_args', array( $this, 'filter_related_products_args' ) );
        }

        add_action( 'woocommerce_email', array( $this, 'woocommerce_email_refresh_text_domain' ) );
        add_action( 'wp_ajax_woocommerce_update_shipping_method', array( $this, 'wcml_refresh_text_domain' ), 9 );
        add_action( 'wp_ajax_nopriv_woocommerce_update_shipping_method', array( $this, 'wcml_refresh_text_domain' ), 9 );
        add_filter( 'wpml_link_to_translation', array( $this, '_filter_link_to_translation' ), 100, 3 );

        add_filter( 'woocommerce_upsell_crosssell_search_products', array( $this, 'filter_woocommerce_upsell_crosssell_posts_by_language' ) );

        add_filter( 'icl_post_alternative_languages', array( $this, 'hide_post_translation_links' ) );

        add_action( 'woocommerce_reduce_order_stock', array( $this, 'sync_product_stocks_reduce' ) );
        add_action( 'woocommerce_restore_order_stock', array( $this, 'sync_product_stocks_restore' ) );

        add_filter( 'wcml_custom_box_html', array( $this, 'downloadable_files_box' ), 10, 3 );

        //add translation manager filters
        add_filter( 'wpml_tm_save_post_trid_value', array( $this, 'wpml_tm_save_post_trid_value' ), 10, 2 );
        add_filter( 'wpml_tm_save_post_lang_value', array( $this, 'wpml_tm_save_post_lang_value' ), 10, 2 );

        add_action( 'icl_pro_translation_completed', array( $this, 'icl_pro_translation_completed' ) );

        //add sitepress filters
        add_filter( 'wpml_save_post_trid_value', array( $this, 'wpml_save_post_trid_value' ), 10, 3 );
        add_filter( 'wpml_save_post_lang', array( $this, 'wpml_save_post_lang_value' ), 10 );

        //quick & bulk edit
        add_action( 'woocommerce_product_quick_edit_save', array( $this, 'woocommerce_product_quick_edit_save' ) );
        add_action( 'woocommerce_product_bulk_edit_save', array( $this, 'woocommerce_product_quick_edit_save' ) );

        //save taxonomy in WPML interface
        add_action( 'wp_ajax_wpml_tt_save_term_translation', array( $this, 'update_taxonomy_in_variations' ), 7 );

        add_action( 'wp_ajax_woocommerce_remove_variation', array( $this, 'remove_variation_ajax' ), 9 );

        // cart functions
        add_action( 'woocommerce_get_cart_item_from_session', array( $this, 'translate_cart_contents' ), 10, 3 );
        add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'translate_cart_subtotal' ) );
        add_action( 'woocommerce_before_calculate_totals', array( $this, 'woocommerce_calculate_totals' ) );

        //refresh cart total on submit checkout action
        add_action( 'woocommerce_before_checkout_process', array( $this, 'wcml_refresh_cart_total' ) );

        if(defined('WPSEO_VERSION') && defined('WPSEO_PATH') && isset($_GET['page']) && $_GET['page'] == 'wpml-wcml' && isset($_GET['tab']) && $_GET['tab'] == 'products'){
            if(version_compare(WPSEO_VERSION, '3', '<' )) {
                require_once WPSEO_PATH . 'admin/class-metabox.php';
            } elseif( file_exists( WPSEO_PATH . 'admin/metabox/class-metabox.php' ) ) {
                require_once WPSEO_PATH . 'admin/metabox/class-metabox.php';
            }
        }

        // Override cached widget id
        add_filter( 'woocommerce_cached_widget_id', array( $this, 'override_cached_widget_id' ) );

        //update menu_order fro translations after ordering original products
        add_action( 'woocommerce_after_product_ordering', array( $this, 'update_all_products_translations_ordering' ) );

        //filter to copy excerpt value
        add_filter( 'wpml_copy_from_original_custom_fields', array( $this, 'filter_excerpt_field_content_copy' ) );

        add_filter( 'icl_wpml_config_array', array( $this, 'set_taxonomies_config' ) );

        add_filter( 'manage_product_posts_columns', array( $this, 'add_languages_column' ), 100 );
        add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'lock_variable_fields' ), 10, 3 );

        add_action( 'woocommerce_product_set_stock_status', array( $this, 'sync_stock_status_for_translations'), 10, 2 );
        add_action( 'woocommerce_variation_set_stock_status', array( $this, 'sync_stock_status_for_translations'), 10, 2 );

    }

    function hide_multilingual_content_setup_box(){
        remove_meta_box('icl_div_config', convert_to_screen('shop_order'), 'normal');
        remove_meta_box('icl_div_config', convert_to_screen('shop_coupon'), 'normal');
    }

    function wc_cart_widget_actions(){
        add_action('wp_ajax_woocommerce_get_refreshed_fragments',array($this,'wcml_refresh_fragments'),0);
        add_action('wp_ajax_woocommerce_add_to_cart',array($this,'wcml_refresh_fragments'),0);
        add_action('wp_ajax_nopriv_woocommerce_get_refreshed_fragments',array($this,'wcml_refresh_fragments'),0);
        add_action('wp_ajax_nopriv_woocommerce_add_to_cart',array($this,'wcml_refresh_fragments'),0);
    }

    function get_product($product_id) {
        if ( version_compare( WOOCOMMERCE_VERSION, "2.0.0" ) >= 0 ) {
            // WC 2.0
            return get_product( $product_id);
        } else {
            return new WC_Product($product_id);
        }
    }

    /*
     * get list of products
     * $page - number of page;
     * $limit - limit product on one page;
     * if($page = 0 && $limit=0) return all products;
     * return array;
    */
    function get_product_list( $page = 1, $limit = 20, $slang ){
        global $wpdb,$sitepress;

        $sql = "SELECT p.ID,p.post_parent FROM $wpdb->posts AS p
                  LEFT JOIN {$wpdb->prefix}icl_translations AS icl ON icl.element_id = p.id
                WHERE p.post_type = 'product' AND p.post_status IN ('publish','future','draft','pending','private')
                AND icl.element_type= 'post_product' AND icl.source_language_code IS NULL";

        if($slang){
            $sql .= " AND icl.language_code = %s ";
            $sql = $wpdb->prepare( $sql, $slang );
        }

        $sql .= ' ORDER BY p.id DESC';

        $products = $wpdb->get_results( $sql );

        return $this->display_hierarchical( $products, $page, $limit);
    }

    function display_hierarchical($products, $pagenum, $per_page){
        global $wpdb;

        if (!$products ){
            return false;
        }

        $output_products = array();
        $top_level_products = array();
        $children_products = array();

        foreach ( $products as $product ) {
            // catch and repair bad products
            if ( $product->post_parent == $product->ID ) {
                $product->post_parent = 0;
                $wpdb->update( $wpdb->posts, array( 'post_parent' => 0 ), array( 'ID' => $product->ID ) );
            }

            if ( 0 == $product->post_parent ){
                $top_level_products[] = $product->ID;
            }else{
                $children_products[$product->post_parent][] = $product->ID;
            }
        }

        $count = 0;
        $start = ( $pagenum - 1 ) * $per_page;
        $end = $start + $per_page;

        foreach ( $top_level_products as $product_id ) {
            if ( $count >= $end )
                break;

            if ( $count >= $start ) {
                $output_products[] = get_post($product_id);

                if ( isset( $children_products[$product_id] ) ){
                    foreach($children_products[$product_id] as $children){
                        $output_products[] = get_post($children);
                        $count++;
                    }
                    unset($children_products[$product_id]);
                }
            }else{
                if ( isset( $children_products[$product_id] ) ){
                    $count += count( $children_products[ $product_id ] );
                }
            }

            $count++;
        }

        return $output_products;
    }

    function preselect_product_type_in_admin_screen(){
        global $pagenow, $sitepress;
        if('post-new.php' == $pagenow){
            if(isset($_GET['post_type']) && $_GET['post_type'] == 'product' && isset($_GET['trid'])){
                $translations = $sitepress->get_element_translations($_GET['trid'], 'post_product_type');
                foreach($translations as $translation){
                    if($translation->original) {
                        $source_lang = $translation->language_code;
                        break;
                    }
                }
                $terms = get_the_terms($translations[$source_lang]->element_id, 'product_type');
                echo '<script type="text/javascript">';
                echo PHP_EOL . '// <![CDATA[' . PHP_EOL;
                echo 'addLoadEvent(function(){'. PHP_EOL;
                echo "jQuery('#product-type option').removeAttr('selected');" . PHP_EOL;
                echo "jQuery('#product-type option[value=\"" . $terms[0]->slug . "\"]').attr('selected', 'selected');" . PHP_EOL;
                echo '});'. PHP_EOL;
                echo PHP_EOL . '// ]]>' . PHP_EOL;
                echo '</script>';
            }
        }
    }

    /*
     * get pages count
     * $limit - limit product on one page;
     */
    function get_product_last_page($count,$limit){
        $last = ceil((int)$count/(int)$limit);
        return (int)$last;
    }

    /*
     * get products count
     */
    function get_products_count( $slang ){
        global $sitepress,$wpdb;

        $sql = "SELECT count(p.id) FROM $wpdb->posts AS p
                LEFT JOIN {$wpdb->prefix}icl_translations AS icl ON icl.element_id = p.id
                WHERE p.post_type = 'product' AND p.post_status IN ('publish','future','draft','pending','private')
                    AND icl.element_type= 'post_product' AND icl.source_language_code IS NULL";

        if( $slang ){
            $sql .= " AND icl.language_code = %s ";
            $count = $wpdb->get_var( $wpdb->prepare( $sql, $slang ) );
        }else{
            $count = $wpdb->get_var( $sql );
        }

        return (int)$count;
    }

    function create_product_translation_package($product_id,$trid,$language,$status){
        global $sitepress,$wpdb,$iclTranslationManagement;
        //create translation package
        $translation_id = $wpdb->get_var($wpdb->prepare("
                                SELECT translation_id FROM {$wpdb->prefix}icl_translations WHERE trid=%d AND language_code='%s'
                            ", $trid, $language));

        $md5 = $iclTranslationManagement->post_md5(get_post($product_id));
        $translation_package = $iclTranslationManagement->create_translation_package($product_id);

        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;

        list($rid, $update) = $iclTranslationManagement->update_translation_status(array(
            'translation_id'        => $translation_id,
            'status'                => $status,
            'translator_id'         => $user_id,
            'needs_update'          => 0,
            'md5'                   => $md5,
            'translation_service'   => 'local',
            'translation_package'   => serialize($translation_package)
        ));

        if(!$update){
            $job_id = $iclTranslationManagement->add_translation_job($rid, $user_id , $translation_package);
        }

    }
    /*
     * get products from search
     * $title - product name
     * $category - product category
     */
    function get_products_from_filter( $title, $category, $translation_status, $product_status, $slang, $page, $limit ){
        global $wpdb, $sitepress;

        $current_language = $slang;
        $prepare_arg = array();
        $prepare_arg[] = '%'.$title.'%';
        if( $slang ) {
            $prepare_arg[] = $current_language;
        }

        $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM $wpdb->posts AS p";

        if($category){
            $sql .= " LEFT JOIN $wpdb->term_relationships AS tx ON tx.object_id = p.id";
        }

        $sql .= " LEFT JOIN {$wpdb->prefix}icl_translations AS t ON t.element_id = p.id";

        if(in_array($translation_status,array('not','need_update','in_progress','complete'))){
            foreach($sitepress->get_active_languages() as $lang){

                if( $lang['code'] == $slang ) continue;

                $tbl_alias_suffix = str_replace('-','_',$lang['code']);
                $sql .= " LEFT JOIN {$wpdb->prefix}icl_translations iclt_{$tbl_alias_suffix}
                        ON iclt_{$tbl_alias_suffix}.trid=t.trid ";

                if( $slang ){
                    $sql .= " AND iclt_{$tbl_alias_suffix}.language_code='{$lang['code']}'\n";
                }else{
                    $sql .= " AND iclt_{$tbl_alias_suffix}.source_language_code IS NULL ";
                }

                $sql   .= " LEFT JOIN {$wpdb->prefix}icl_translation_status iclts_{$tbl_alias_suffix}
                                ON iclts_{$tbl_alias_suffix}.translation_id=iclt_{$tbl_alias_suffix}.translation_id\n";
            }
        }

        $sql .= " WHERE p.post_title LIKE '%s' AND p.post_type = 'product' AND t.element_type = 'post_product' AND t.source_language_code IS NULL";

        if( $slang ){
            $sql .= " AND t.language_code = %s";
        }

        if($product_status != 'all'){
            $sql .= " AND p.post_status = %s ";
            $prepare_arg[] = $product_status;
        }else{
            $sql .= " AND p.post_status NOT IN ('trash','auto-draft','inherit') ";
        }

        if($category){
            $sql .= " AND tx.term_taxonomy_id = %d ";
            $prepare_arg[] = $category;
        }

        if(in_array($translation_status,array('not','need_update','in_progress','complete'))){
            switch($translation_status){
                case 'not':
                    $sql .= " AND (";
                    $wheres = array();
                    foreach($sitepress->get_active_languages() as $lang){
                        if($lang['code'] == $slang) continue;
                        $tbl_alias_suffix = str_replace('-','_',$lang['code']);
                        $wheres[] = "iclts_{$tbl_alias_suffix}.status IS NULL OR iclts_{$tbl_alias_suffix}.status = ".ICL_TM_WAITING_FOR_TRANSLATOR." OR iclts_{$tbl_alias_suffix}.needs_update = 1\n";
                    }
                    $sql .= join(' OR ', $wheres) . ")";
                    break;
                case 'need_update':
                    $sql .= " AND (";
                    $wheres = array();
                    foreach($sitepress->get_active_languages() as $lang){
                        if($lang['code'] == $slang) continue;
                        $tbl_alias_suffix = str_replace('-','_',$lang['code']);
                        $wheres[] = "iclts_{$tbl_alias_suffix}.needs_update = 1\n";
                    }
                    $sql .= join(' OR ', $wheres) . ")";
                    break;
                case 'in_progress':
                    $sql .= " AND (";
                    $wheres = array();
                    foreach($sitepress->get_active_languages() as $lang){
                        if($lang['code'] == $slang) continue;
                        $tbl_alias_suffix = str_replace('-','_',$lang['code']);
                        $wheres[] = "iclts_{$tbl_alias_suffix}.status = ".ICL_TM_IN_PROGRESS."\n";
                    }
                    $sql .= join(' OR ', $wheres)  . ")";
                    break;
                case 'complete':
                    foreach($sitepress->get_active_languages() as $lang){
                        if($lang['code'] == $slang) continue;
                        $tbl_alias_suffix = str_replace('-','_',$lang['code']);
                        $sql .= " AND (iclts_{$tbl_alias_suffix}.status = ".ICL_TM_COMPLETE." OR iclts_{$tbl_alias_suffix}.status = ".ICL_TM_DUPLICATE.") AND iclts_{$tbl_alias_suffix}.needs_update = 0\n";
                    }
                    break;
            }
        }

        $sql .= " ORDER BY p.id DESC LIMIT ".($page-1)*$limit.",".$limit;

        $data = array();

        $data['products'] = $wpdb->get_results($wpdb->prepare($sql,$prepare_arg));
        $data['count'] = $wpdb->get_var("SELECT FOUND_ROWS()");

        return $data;
    }

    //update product "AJAX"
    function update_product_actions() {
        $nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if(!$nonce || !wp_verify_nonce($nonce, 'update_product_actions')){
            echo json_encode(array('error' => __('Invalid nonce', 'woocommerce-multilingual')));
            die();
        }

        global $woocommerce_wpml,$sitepress, $wpdb,$sitepress_settings,$iclTranslationManagement;

        //get post values
        $data = array();
        $records = $_POST['records'];
        parse_str($records, $data);

        $original_product_id = filter_input( INPUT_POST, 'product_id', FILTER_SANITIZE_NUMBER_INT );
        $job_id = filter_input( INPUT_POST, 'job_id', FILTER_SANITIZE_NUMBER_INT );
        $language = filter_input( INPUT_POST, 'language', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $orig_product = get_post($original_product_id);

        if (!$data['title_' . $language]) {
            echo json_encode(array('error' => __('Title missing', 'woocommerce-multilingual')));
            die();
        }

        $languages = $sitepress->get_active_languages();

        $product_trid = $sitepress->get_element_trid($original_product_id, 'post_' . $orig_product->post_type);
        $tr_product_id = apply_filters( 'translate_object_id',$original_product_id, 'product', false, $language);

        if( get_magic_quotes_gpc() ){
            foreach( $data as $key => $data_item ){
                if( !is_array( $data_item ) ){
                    $data[ $key ] = stripslashes( $data_item );
                }
            }
        }

        if (is_null($tr_product_id)) {

            //insert new post
            $args = array();
            $args['post_title'] = $data['title_' . $language];
            $args['post_type'] = $orig_product->post_type;
            $args['post_content'] = $data['content_' . $language];
            $args['post_excerpt'] = $data['excerpt_' . $language];
            $args['post_status'] = $orig_product->post_status;
            $args['menu_order'] = $orig_product->menu_order;
            $args['ping_status'] = $orig_product->ping_status;
            $args['comment_status'] = $orig_product->comment_status;
            $product_parent = apply_filters( 'translate_object_id',$orig_product->post_parent, 'product', false, $language);
            $args['post_parent'] = is_null($product_parent) ? 0 : $product_parent;

            //TODO: remove after change required WPML version > 3.3
            $_POST['to_lang'] = $language;
            // for WPML > 3.3
            $_POST['icl_post_language'] = $language;

            if($woocommerce_wpml->settings['products_sync_date']){
                $args['post_date'] = $orig_product->post_date;
            }

            $tr_product_id = wp_insert_post($args);

            $translation_id = $wpdb->get_var( $wpdb->prepare("SELECT translation_id
                                                                  FROM {$wpdb->prefix}icl_translations
                                                                  WHERE element_type=%s AND trid=%d AND language_code=%s AND element_id IS NULL " ,
                "post_product", $product_trid, $language ) );

            if ( $translation_id ) {

                $wpdb->query(
                    $wpdb->prepare(
                        "DELETE FROM {$wpdb->prefix}icl_translations WHERE element_id=%d AND trid=%d",
                        $tr_product_id, $product_trid
                    )
                );

                $wpdb->update($wpdb->prefix . 'icl_translations', array('element_id' => $tr_product_id), array('translation_id' => $translation_id));

                $iclTranslationManagement->update_translation_status(
                    array(
                        'status' => ICL_TM_COMPLETE,
                        'needs_update' => 0,
                        'translation_id' => $translation_id,
                        'translator_id' => get_current_user_id()
                    ));

            }else{

                $sitepress->set_element_language_details($tr_product_id, 'post_' . $orig_product->post_type, $product_trid, $language);

            }

            $this->duplicate_product_post_meta($original_product_id, $tr_product_id, $data, true);


        }else{

            //update post
            $args = array();
            $args['ID'] = $tr_product_id;
            $args['post_title'] = $data['title_' . $language];
            $args['post_content'] = $data['content_' . $language];
            $args['post_excerpt'] = $data['excerpt_' . $language];
            $args['post_status'] = $orig_product->post_status;
            $args['ping_status'] = $orig_product->ping_status;
            $args['comment_status'] = $orig_product->comment_status;
            $product_parent = apply_filters( 'translate_object_id',$orig_product->post_parent, 'product', false, $language);
            $args['post_parent'] = is_null($product_parent) ? 0 : $product_parent;
            $_POST['to_lang'] = $language;

            $sitepress->switch_lang( $language );
            wp_update_post($args);
            $sitepress->switch_lang();

            $post_name = $wpdb->get_var( $wpdb->prepare( "SELECT post_name FROM {$wpdb->posts} WHERE ID=%d", $tr_product_id ));
            if(isset( $data['post_name_' . $language]) && $post_name != $data['post_name_' . $language]){
                // update post_name
                // need set POST variable ( WPML used them when filtered this function)

                $new_post_name = sanitize_title( $data['post_name_' . $language] ? $data['post_name_' . $language] :  $data['title_' . $language] );
                $_POST[ 'new_title' ] = $data['title_' . $language];
                $_POST[ 'new_slug' ] = $new_post_name;
                $new_slug = wp_unique_post_slug( $new_post_name, $tr_product_id, $orig_product->post_status, $orig_product->post_type, $args['post_parent']);
                $wpdb->update( $wpdb->posts, array( 'post_name' => $new_slug ), array( 'ID' => $tr_product_id ) );
            }

            $sitepress->set_element_language_details($tr_product_id, 'post_' . $orig_product->post_type, $product_trid, $language);
            $this->duplicate_product_post_meta($original_product_id, $tr_product_id, $data);

        }

        //sync taxonomies
        $this->sync_product_taxonomies($original_product_id, $tr_product_id, $language);

        do_action( 'wcml_update_extra_fields', $tr_product_id, $data, $language );

        do_action( 'wcml_before_sync_product_data', $original_product_id, $tr_product_id, $language );

        $this->sync_product_attr( $original_product_id, $tr_product_id, $language, $data );

        $this->sync_default_product_attr($original_product_id, $tr_product_id, $language);

        $wpml_media_options = maybe_unserialize(get_option('_wpml_media'));
        //sync media
        if($wpml_media_options['new_content_settings']['duplicate_featured']){
            //sync feature image
            $this->sync_thumbnail_id($original_product_id, $tr_product_id, $language);
        }

        if($wpml_media_options['new_content_settings']['duplicate_media']){
            //sync product gallery
            $this->sync_product_gallery($original_product_id);
        }

        // synchronize post variations
        $this->sync_product_variations($original_product_id, $tr_product_id, $language, $data);

        $this->sync_linked_products($original_product_id, $tr_product_id, $language);

        //save images texts
        if(isset($data['images'])){
            foreach($data['images'] as $key=>$image){
                //update image texts
                $wpdb->update(
                    $wpdb->posts,
                    array(
                        'post_title' => $image['title'],
                        'post_content' => $image['description'],
                        'post_excerpt' => $image['caption']
                    ),
                    array( 'id' => $key )
                );
            }
        }


        $translations = $sitepress->get_element_translations( $product_trid, 'post_product', false, false, true );
        if(ob_get_length()){
            ob_clean();
        }
        ob_start();
        $return = array();

        $this->get_translation_statuses($translations,$languages,isset($_POST['slang']) && $_POST['slang'] != 'all'?$_POST['slang']:false, $product_trid, $job_id ? $language : false );
        $return['status'] =  ob_get_clean();


        ob_start();
        $this->product_images_box($tr_product_id,$language);
        $return['images'][$language] =  ob_get_clean();


        $is_variable_product = $this->is_variable_product($original_product_id);

        if($is_variable_product && !$woocommerce_wpml->settings['file_path_sync'] && $woocommerce_wpml->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT){
            ob_start();
            $this->product_variations_box($tr_product_id,$language);
            $return['variations'][$language] =  ob_get_clean();
        }

        // no longer a duplicate
        if(!empty($data['end_duplication'][$original_product_id][$language])){
            delete_post_meta($tr_product_id, '_icl_lang_duplicate_of', $original_product_id);

        }

        $old_slug = $wpdb->get_var( $wpdb->prepare( "SELECT post_name FROM {$wpdb->posts} WHERE ID=%d", $tr_product_id ));
        $return['slug'] = isset( $new_slug )? $new_slug : $old_slug;

        echo json_encode($return);
        die();
    }

    function sync_product_attr( $original_product_id, $tr_product_id, $language = false, $data = false ){

        //get "_product_attributes" from original product
        $orig_product_attrs = $this->get_product_atributes( $original_product_id );
        $trnsl_product_attrs = $this->get_product_atributes( $tr_product_id );

        $trnsl_labels = get_post_meta( $tr_product_id, 'attr_label_translations', true );

        foreach ( $orig_product_attrs as $key => $orig_product_attr ) {
            $sanitized_key = sanitize_title( $orig_product_attr['name'] );

            if( $sanitized_key != $key ) {
                $orig_product_attrs_buff = $orig_product_attrs[ $key ];
                unset( $orig_product_attrs[ $key ] );
                $orig_product_attrs[$sanitized_key] = $orig_product_attrs_buff;
                $key_to_save = $sanitized_key;
            }else{
                $key_to_save = $key;
            }

            if ( $data ){
                if ( isset( $data[ $key . '_' . $language ] ) && !empty( $data[ $key . '_' . $language ] ) && !is_array( $data[ $key . '_' . $language ] ) ) {
                    //get translation values from $data
                    $trnsl_labels[ $language ][ $key_to_save ] = stripslashes( $data[ $key . '_name_' . $language ] );
                    $orig_product_attrs[ $key_to_save ][ 'value' ] = $data[ $key . '_' . $language ];
                } else {
                    $orig_product_attrs[ $key_to_save ][ 'value' ] = '';
                }
            }elseif( !$orig_product_attr[ 'is_taxonomy' ] ){

                if( isset( $trnsl_product_attrs[ $key ] ) ){
                    $orig_product_attrs[ $key_to_save ][ 'value' ] = $trnsl_product_attrs[ $key ][ 'value' ];
                }else{
                    unset ( $orig_product_attrs[ $key_to_save ] );
                }
            }

        }

        update_post_meta( $tr_product_id, 'attr_label_translations', $trnsl_labels );

        //update "_product_attributes"
        update_post_meta( $tr_product_id, '_product_attributes', $orig_product_attrs );

    }

    function sync_product_data( $original_product_id, $tr_product_id, $lang ){

        $this->duplicate_product_post_meta( $original_product_id, $tr_product_id );

        $this->sync_product_attr( $original_product_id, $tr_product_id );

        $this->sync_default_product_attr( $original_product_id, $tr_product_id, $lang );

        $wpml_media_options = maybe_unserialize( get_option( '_wpml_media' ) );
        //sync media
        if( $wpml_media_options[ 'new_content_settings' ][ 'duplicate_featured' ] ){
            //sync feature image
            $this->sync_thumbnail_id( $original_product_id, $tr_product_id, $lang );
        }

        if( $wpml_media_options[ 'new_content_settings' ][ 'duplicate_media' ]){
            //sync product gallery
            $this->sync_product_gallery( $original_product_id );
        }

        //sync taxonomies
        $this->sync_product_taxonomies( $original_product_id, $tr_product_id, $lang );

        //duplicate variations
        $this->sync_product_variations( $original_product_id, $tr_product_id, $lang );

        $this->sync_linked_products( $original_product_id, $tr_product_id, $lang );
    }


    function is_variable_product($product_id){
        global $wpdb;
        $get_variation_term_taxonomy_ids = $wpdb->get_col("SELECT tt.term_taxonomy_id FROM $wpdb->terms AS t LEFT JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE t.name = 'variable' AND tt.taxonomy = 'product_type'");
        $get_variation_term_taxonomy_ids = apply_filters('wcml_variation_term_taxonomy_ids',(array)$get_variation_term_taxonomy_ids);

        $is_variable_product = $wpdb->get_var($wpdb->prepare("SELECT count(object_id) FROM $wpdb->term_relationships WHERE object_id = %d AND term_taxonomy_id IN (".join(',',$get_variation_term_taxonomy_ids).")",$product_id));
        return $is_variable_product;
    }

    function is_grouped_product($product_id){
        global $wpdb;
        $get_variation_term_taxonomy_id = $wpdb->get_var("SELECT tt.term_taxonomy_id FROM $wpdb->terms AS t LEFT JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE t.name = 'grouped'");
        $is_grouped_product = $wpdb->get_var($wpdb->prepare("SELECT count(object_id) FROM $wpdb->term_relationships WHERE object_id = %d AND term_taxonomy_id = %d ",$product_id,$get_variation_term_taxonomy_id));

        return $is_grouped_product;
    }


    function sync_product_taxonomies($original_product_id,$tr_product_id,$lang){
        global $sitepress,$wpdb;
        remove_filter('get_term', array($sitepress,'get_term_adjust_id')); // AVOID filtering to current language

        $taxonomies = get_object_taxonomies('product');
        foreach ($taxonomies as $taxonomy) {
            $terms = get_the_terms($original_product_id, $taxonomy);
            $terms_array = array();
            if ($terms) {

                foreach ($terms as $term) {

                    if($term->taxonomy == "product_type"){
                        $terms_array[] = $term->name;
                        continue;
                    }

                    $tr_id = apply_filters( 'translate_object_id',$term->term_id, $taxonomy, false, $lang);
                    if(!is_null($tr_id)){
                        // not using get_term - unfiltered get_term
                        $translated_term = $wpdb->get_row($wpdb->prepare("
                            SELECT * FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} x ON x.term_id = t.term_id WHERE t.term_id = %d AND x.taxonomy = %s", $tr_id, $taxonomy));

                        $terms_array[] = $translated_term->term_id;
                    }
                }

                if( $taxonomy != 'product_type' && !is_taxonomy_hierarchical($taxonomy)){
                    $terms_array = array_unique( array_map( 'intval', $terms_array ) );
                }

                $sitepress->switch_lang( $lang );
                wp_set_post_terms($tr_product_id, $terms_array, $taxonomy);
                $sitepress->switch_lang();

            }
        }
    }

    function get_product_atributes($product_id){
        $attributes = get_post_meta($product_id,'_product_attributes',true);
        if(!is_array($attributes)){
            $attributes = array();
        }
        return $attributes;
    }

    //duplicate product post meta
    function duplicate_product_post_meta($original_product_id, $trnsl_product_id, $data = false , $add = false ){
        global $sitepress, $woocommerce_wpml;
        $settings = $sitepress->get_settings();
        $lang = $sitepress->get_language_for_element($trnsl_product_id,'post_product');

        $all_meta = get_post_custom($original_product_id);

        unset($all_meta['_thumbnail_id']);

        foreach(wp_get_post_terms($original_product_id, 'product_type', array("fields" => "names")) as $type){
            $product_type = $type;
        }

        foreach ($all_meta as $key => $meta) {
            if( !isset($settings['translation-management']['custom_fields_translation'][$key]) || $settings['translation-management']['custom_fields_translation'][$key] == 0 ){
                continue;
            }
            foreach ($meta as $meta_value) {
                $meta_value = maybe_unserialize($meta_value);
                if($data){
                    if(isset($data[$key.'_'.$lang]) && isset($settings['translation-management']['custom_fields_translation'][$key]) && $settings['translation-management']['custom_fields_translation'][$key] == 2){
                        if($key == '_file_paths'){
                            if( !$woocommerce_wpml->settings['file_path_sync'] ){
                                $file_paths = explode("\n",$data[$key.'_'.$lang]);
                                $file_paths_array = array();
                                foreach($file_paths as $file_path){
                                    $file_paths_array[md5($file_path)] = $file_path;
                                }
                                $meta_value = $file_paths_array;
                            }

                        }elseif($key == '_downloadable_files'){
                            if( !$woocommerce_wpml->settings['file_path_sync'] ) {
                                $file_paths_array = array();
                                    foreach ($data[$key . '_' . $lang] as $file_path) {
                                        $key_file = md5($file_path['file'] . $file_path['name']);
                                    $file_paths_array[$key_file]['name'] = $file_path['name'];
                                    $file_paths_array[$key_file]['file'] = $file_path['file'];
                                }
                                $meta_value = $file_paths_array;
                            }
                        }else{
                            $meta_value = $data[$key.'_'.$lang];
                        }
                    }

                    if(isset($data['regular_price_'.$lang]) && isset($data['sale_price_'.$lang]) && $product_type == 'variable'){
                        switch($key){
                            case '_min_variation_sale_price':
                                $meta_value = count(array_filter($data['sale_price_'.$lang]))?min(array_filter($data['sale_price_'.$lang])):'';
                                break;
                            case '_max_variation_sale_price':
                                $meta_value = count(array_filter($data['sale_price_'.$lang]))?max(array_filter($data['sale_price_'.$lang])):'';
                                break;
                            case '_min_variation_regular_price':
                                $meta_value = count(array_filter($data['regular_price_'.$lang]))?min(array_filter($data['regular_price_'.$lang])):'';
                                break;
                            case '_max_variation_regular_price':
                                $meta_value = count(array_filter($data['regular_price_'.$lang]))?max(array_filter($data['regular_price_'.$lang])):'';
                                break;
                            case '_min_variation_price':
                                if(count(array_filter($data['sale_price_'.$lang])) && min(array_filter($data['sale_price_'.$lang]))<min(array_filter($data['regular_price_'.$lang]))){
                                    $meta_value = min(array_filter($data['sale_price_'.$lang]));
                                }elseif(count(array_filter($data['regular_price_'.$lang]))){
                                    $meta_value = min(array_filter($data['regular_price_'.$lang]));
                                }else{
                                    $meta_value = '';
                                }
                                break;
                            case '_max_variation_price':
                                if(count(array_filter($data['sale_price_'.$lang])) && max(array_filter($data['sale_price_'.$lang]))>max(array_filter($data['regular_price_'.$lang]))){
                                    $meta_value = max(array_filter($data['sale_price_'.$lang]));
                                }elseif(count(array_filter($data['regular_price_'.$lang]))){
                                    $meta_value = max(array_filter($data['regular_price_'.$lang]));
                                }else{
                                    $meta_value = '';
                                }
                                break;
                            case '_price':
                                if(count(array_filter($data['sale_price_'.$lang])) && min(array_filter($data['sale_price_'.$lang]))<min(array_filter($data['regular_price_'.$lang]))){
                                    $meta_value = min(array_filter($data['sale_price_'.$lang]));
                                }elseif(count(array_filter($data['regular_price_'.$lang]))){
                                    $meta_value = min(array_filter($data['regular_price_'.$lang]));
                                }else{
                                    $meta_value = '';
                                }
                                break;
                        }

                    }else{
                        if($key == '_price' && isset($data['sale_price_'.$lang]) && isset($data['regular_price_'.$lang])){
                            if($data['sale_price_'.$lang]){
                                $meta_value = $data['sale_price_'.$lang];
                            }else{
                                $meta_value = $data['regular_price_'.$lang];
                            }
                        }
                    }

                    $meta_value = apply_filters('wcml_meta_value_before_add',$meta_value,$key);
                    if($add){
                        add_post_meta($trnsl_product_id, $key, $meta_value, true);
                    }else{
                        update_post_meta($trnsl_product_id,$key,$meta_value);
                    }
                }else{
                    if( ( $woocommerce_wpml->settings['file_path_sync']  && in_array( $key ,array( '_file_paths', '_downloadable_files' ) ) ) || ( isset($settings['translation-management']['custom_fields_translation'][$key]) && $settings['translation-management']['custom_fields_translation'][$key] == 1 ) ){
                        $meta_value = apply_filters('wcml_meta_value_before_add',$meta_value,$key);
                        update_post_meta($trnsl_product_id, $key, $meta_value);
                    }
                }
            }
        }

        do_action('wcml_after_duplicate_product_post_meta',$original_product_id, $trnsl_product_id, $data);
    }

    function sync_product_gallery( $product_id ){
        if( !defined( 'WPML_MEDIA_VERSION' ) ){
            return;
        }
        global $sitepress;

        $product_gallery = get_post_meta( $product_id, '_product_image_gallery', true );
        $gallery_ids = explode( ',', $product_gallery );

        $trid = $sitepress->get_element_trid( $product_id, 'post_product' );
        $translations = $sitepress->get_element_translations( $trid, 'post_product', true );
        foreach( $translations as $translation ){
            $duplicated_ids = '';
            if ( !$translation->original ) {
                foreach( $gallery_ids as $image_id ){
                    if( get_post( $image_id ) ) {
                        $duplicated_id = apply_filters( 'translate_object_id', $image_id, 'attachment', false, $translation->language_code );
                        if ( is_null( $duplicated_id ) && $image_id ) {

                            $duplicated_id = WPML_Media::create_duplicate_attachment( $image_id, wp_get_post_parent_id( $image_id ), $translation->language_code );
                        }
                        $duplicated_ids .= $duplicated_id . ',';
                    }
                }
                $duplicated_ids = substr( $duplicated_ids, 0, strlen( $duplicated_ids ) - 1 );
                update_post_meta( $translation->element_id, '_product_image_gallery', $duplicated_ids );
            }
        }
    }

    function get_translation_flags($active_languages,$slang = false,$job_language =false){
        global $sitepress;

        foreach($active_languages as $language){
            if( $job_language && $language['code'] != $job_language ) {
                continue;
            }elseif (!$slang || ( ($slang != $language['code']) && (current_user_can('wpml_operate_woocommerce_multilingual') || wpml_check_user_is_translator($slang,$language['code'])) && (!isset($_POST['translation_status_lang']) || (isset($_POST['translation_status_lang']) && ($_POST['translation_status_lang'] == $language['code']) || $_POST['translation_status_lang']=='')))){

                echo '<img src="'. $sitepress->get_flag_url($language['code']).'" width="18" height="12" class="flag_img" />';

            }
        }
    }


    function get_translation_statuses($product_translations,$active_languages,$slang = false, $trid = false, $job_language = false ){
        global $wpdb, $sitepress, $wpml_post_translations;

        foreach ($active_languages as $language) {
            if( $job_language && $language['code'] != $job_language ) {
                continue;
            }elseif(isset($product_translations[$language['code']]) && $product_translations[$language['code']]->original){
                $alt = __('Original language', 'woocommerce-multilingual');
                echo '<i title="'. $alt .'" class="stat_img icon-minus"></i>';
            }elseif ($slang != $language['code']  && (!isset($_POST['translation_status_lang']) || (isset($_POST['translation_status_lang']) && ($_POST['translation_status_lang'] == $language['code']) || $_POST['translation_status_lang']==''))) {

                $status_helper = wpml_get_post_status_helper ();
                $status = $status_helper->get_status( false, $trid, $language['code'] );

                if(!$status){
                    $alt = __('Not translated', 'woocommerce-multilingual');
                    echo '<i title="'. $alt .'" class="stat_img icon-warning-sign"></i>';
                }elseif($status == ICL_TM_NEEDS_UPDATE){
                    $alt = __('Not translated - needs update', 'woocommerce-multilingual');
                    echo '<i title="'. $alt .'" class="stat_img icon-repeat"></i>';
                }elseif($status != ICL_TM_COMPLETE && $status != ICL_TM_DUPLICATE) {
                    $alt = __('In progress', 'woocommerce-multilingual');
                    echo '<i title="'. $alt .'" class="stat_img icon-spinner"></i>';
                }elseif($status == ICL_TM_COMPLETE || $status == ICL_TM_DUPLICATE){
                    $alt = __('Complete', 'woocommerce-multilingual');
                    echo '<i title="'. $alt .'" class="stat_img icon-ok"></i>';
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

    //sync product variations
    function sync_product_variations( $product_id, $tr_product_id, $lang, $data = false, $trbl = false, $add_new = true, $sync_existing = true ){
        global $wpdb,$sitepress,$sitepress_settings, $woocommerce_wpml,$woocommerce, $wpml_post_translations;
        remove_action ( 'save_post', array( $wpml_post_translations, 'save_post_actions' ), 100, 2 );

        $is_variable_product = $this->is_variable_product($product_id);
        if($is_variable_product){
            $get_all_post_variations = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->posts
                            WHERE post_status IN ('publish','private') AND post_type = 'product_variation' AND post_parent = %d ORDER BY ID",$product_id));

            $duplicated_post_variation_ids = array();
            $min_max_prices = array();
            foreach($get_all_post_variations as $k => $post_data){
                $duplicated_post_variation_ids[] = $post_data->ID;
            }

            foreach($min_max_prices as $price_key=>$min_max_price){
                update_post_meta($product_id,$price_key,$min_max_price);
            }

            $all_taxs          = get_object_taxonomies( 'product_variation' );

            foreach ($get_all_post_variations as $k => $post_data) {

                // Find if this has already been duplicated
                $variation_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta AS pm
                                    JOIN {$wpdb->prefix}icl_translations AS tr ON tr.element_id = pm.post_id
                                    WHERE tr.element_type = 'post_product_variation' AND tr.language_code = %s AND pm.meta_key = '_wcml_duplicate_of_variation' AND pm.meta_value = %d",$lang,$post_data->ID));
                $trid = $sitepress->get_element_trid($post_data->ID, 'post_product_variation');
                if (!empty($variation_id) && !is_null($variation_id)) {
                    if( !$sync_existing ){
                        return;
                    }
                    // Update variation
                    wp_update_post(array(
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
                    if( !$add_new ){
                        return;
                    }
                    // Add new variation
                    $guid = $post_data->guid;
                    $replaced_guid = str_replace($product_id, $tr_product_id, $guid);
                    $slug = $post_data->post_name;
                    $replaced_slug = str_replace($product_id, $tr_product_id, $slug);
                    $variation_id = wp_insert_post(array(
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
                    add_post_meta($variation_id, '_wcml_duplicate_of_variation', $post_data->ID);

                    $sitepress->set_element_language_details($variation_id, 'post_product_variation', $trid, $lang);
                }

                //sync media
                $this->sync_thumbnail_id($post_data->ID,$variation_id,$lang);

                //sync file_paths
                if(!$woocommerce_wpml->settings['file_path_sync']  && isset($data['variations_file_paths'][$variation_id])){
                    $file_paths_array = array();
                    foreach($data['variations_file_paths'][$variation_id] as $file_path){
                        $key = md5($file_path['file'].$file_path['name']);
                        $file_paths_array[$key]['name'] = $file_path['name'];
                        $file_paths_array[$key]['file'] = $file_path['file'];
                    }
                    update_post_meta($variation_id,'_downloadable_files',$file_paths_array);

                }elseif($woocommerce_wpml->settings['file_path_sync']){
                    $orig_file_path = maybe_unserialize(get_post_meta($post_data->ID,'_downloadable_files',true));
                    update_post_meta($variation_id,'_downloadable_files',$orig_file_path);
                }

                // sync taxonomies
                if ( !empty( $all_taxs ) ) {
                    foreach ( $all_taxs as $tt ) {
                        $terms = get_the_terms( $post_data->ID, $tt );
                        if ( !empty( $terms ) ) {
                            $tax_sync = array();
                            foreach ( $terms as $term ) {
                                if ( $sitepress->is_translated_taxonomy($tt) ) {
                                    $term_id = apply_filters( 'translate_object_id', $term->term_id, $tt, false, $lang );
                                } else {
                                    $term_id = $term->term_id;
                                }
                                if ( $term_id ) {
                                    $tax_sync[ ] = intval( $term_id );
                                }
                            }
                            //set the fourth parameter in 'true' because we need to add new terms, instead of replacing all
                            wp_set_object_terms( $variation_id, $tax_sync, $tt, true );
                        }
                    }
                }

            }

            $get_current_post_variations = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->posts
                            WHERE post_status IN ('publish','private') AND post_type = 'product_variation' AND post_parent = %d ORDER BY ID",$tr_product_id));

            // Delete variations that no longer exist
            foreach ($get_current_post_variations as $key=>$post_data) {
                $variation_id = get_post_meta($post_data->ID, '_wcml_duplicate_of_variation', true);
                if (!in_array($variation_id, $duplicated_post_variation_ids)) {
                    wp_delete_post($post_data->ID, true);
                    unset($get_current_post_variations[$key]);
                }
            }

            // custom fields to copy
            $cf = (array)$sitepress_settings['translation-management']['custom_fields_translation'];

            // synchronize post variations post meta
            $current_post_variation_ids = array();
            foreach($get_current_post_variations as $k => $post_data){
                $current_post_variation_ids[] = $post_data->ID;
            }

            // refresh parent-children transients
            delete_transient( 'wc_product_children_' . $tr_product_id );
            delete_transient( '_transient_wc_product_children_ids_' . $tr_product_id );


            $original_product_attr = get_post_meta($product_id,'_product_attributes',true);
            $tr_product_attr = get_post_meta($tr_product_id,'_product_attributes',true);


            foreach($duplicated_post_variation_ids as $dp_key => $duplicated_post_variation_id){
                $get_all_post_meta = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->postmeta WHERE post_id = %d",$duplicated_post_variation_id));

                //delete non exists attributes
                $get_all_variation_attributes = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->postmeta WHERE post_id = %d AND meta_key LIKE 'attribute_%%' ",$current_post_variation_ids[$dp_key]));
                foreach($get_all_variation_attributes as $variation_attribute){
                    $attribute_name = substr($variation_attribute->meta_key, 10);
                    if(!isset($original_product_attr[$attribute_name])){
                        delete_post_meta($current_post_variation_ids[$dp_key],$variation_attribute->meta_key);
                    }
                }

                foreach($get_all_post_meta as $k => $post_meta){

                    $meta_key = $post_meta->meta_key;
                    $meta_value = maybe_unserialize($post_meta->meta_value);

                    // update current post variations meta
                    if ((substr($meta_key, 0, 10) == 'attribute_' || isset($cf[$meta_key]) && $cf[$meta_key] == 1)) {

                        // adjust the global attribute slug in the custom field
                        $attid = null;

                        if (substr($meta_key, 0, 10) == 'attribute_') {
                            $tax = substr($meta_key, 10);

                            if (taxonomy_exists($tax)) {

                                $attid = $this->wcml_get_term_id_by_slug( $tax, $meta_value );

                                if($attid){

                                    $term_obj = $this->wcml_get_term_by_id( $attid, $tax );
                                    $trid = $sitepress->get_element_trid( $term_obj->term_taxonomy_id, 'tax_' . $tax );
                                    if ($trid) {
                                        $translations = $sitepress->get_element_translations($trid,'tax_' . $tax);
                                        if (isset($translations[$lang])) {
                                            $meta_value = $wpdb->get_var($wpdb->prepare("SELECT slug FROM $wpdb->terms WHERE term_id = %s", $translations[$lang]->term_id));
                                        }else{
                                            $meta_value = $meta_value.'_'.$lang;
                                        }
                                    }
                                }
                            }else{
                                if( isset( $original_product_attr[$tax] ) ){
                                    $tax = sanitize_title( $original_product_attr[$tax]['name'] );
                                }

                                if(isset($original_product_attr[$tax])){
                                    if(isset($tr_product_attr[$tax])){
                                        $values_arrs = array_map('trim', explode('|',$original_product_attr[$tax]['value']));
                                        $values_arrs_tr = array_map('trim',explode('|',$tr_product_attr[$tax]['value']));
                                        foreach($values_arrs as $key=>$value){
                                            $value_sanitized = sanitize_title($value);

                                            if( ( $value_sanitized == strtolower(urldecode($meta_value)) || strtolower($value_sanitized) == $meta_value || $value == $meta_value ) && isset($values_arrs_tr[$key])){
                                                $meta_value = $values_arrs_tr[$key];
                                            }
                                        }
                                    }else{
                                        $meta_value = $meta_value.'_'.$lang;
                                    }

                                }

                                $meta_key = 'attribute_'.$tax;

                            }
                        }

                        update_post_meta($current_post_variation_ids[$dp_key], $meta_key, $meta_value);
                    }

                    //sync variation prices
                    if(($woocommerce_wpml->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT || $trbl) && in_array($meta_key,array('_sale_price','_regular_price','_price'))){
                        $meta_value = get_post_meta($duplicated_post_variation_ids[$dp_key],$meta_key,true);
                        update_post_meta($current_post_variation_ids[$dp_key], $meta_key, $meta_value);
                    }
                }
            }
        }
        add_action ( 'save_post', array( $wpml_post_translations, 'save_post_actions' ), 100, 2 );
    }

    /* Change locale to saving language - needs for sanitize_title exception wcml-390 */
    function update_product_action_locale_check( $locale ){

        if( isset($_POST['action']) && $_POST['action'] == 'wcml_update_product' ){
            global $sitepress;

            return $sitepress->get_locale( $_POST['language'] );
        }

        return $locale;

    }

    function sync_linked_products( $product_id, $translated_product_id, $lang ){
        global $wpdb;

        //sync up-sells
        $original_up_sells = maybe_unserialize( get_post_meta( $product_id, '_upsell_ids', true ) );
        $trnsl_up_sells = array();
        if($original_up_sells) {
            foreach ($original_up_sells as $original_up_sell_product) {
                $trnsl_up_sells[] = apply_filters( 'translate_object_id',$original_up_sell_product, get_post_type($original_up_sell_product), false, $lang);
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
        if( $translated_product_parent_id ){
            delete_transient('wc_product_children_' . $translated_product_parent_id);
            delete_transient('_transient_wc_product_children_ids_' . $translated_product_parent_id);
        }

    }

    function sync_thumbnail_id($orig_post_id,$trnsl_post_id,$lang){
        if( defined( 'WPML_MEDIA_VERSION' ) ){
            $thumbnail_id = get_post_meta( $orig_post_id, '_thumbnail_id', true );
            $trnsl_thumbnail = apply_filters( 'translate_object_id', $thumbnail_id, 'attachment', false, $lang );
            if( is_null( $trnsl_thumbnail ) && $thumbnail_id ){
                $trnsl_thumbnail = WPML_Media::create_duplicate_attachment( $thumbnail_id, wp_get_post_parent_id( $thumbnail_id ), $lang );
            }

            update_post_meta( $trnsl_post_id, '_thumbnail_id', $trnsl_thumbnail );
            update_post_meta( $orig_post_id, '_wpml_media_duplicate', 1 );
            update_post_meta( $orig_post_id, '_wpml_media_featured', 1 );
        }
    }


    function _filter_link_to_translation( $link, $post_id, $lang ){
        global $woocommerce_wpml;

        if( $woocommerce_wpml->settings[ 'trnsl_interface' ] &&
            (
                ( isset( $_GET[ 'post_type' ] ) && $_GET[ 'post_type' ] == 'product' ) ||
                ( isset( $_GET[ 'post' ] ) && get_post_type( $_GET[ 'post' ] ) == 'product' )
            )
        ){

            if( empty( $post_id ) && isset( $_GET['post'] ) ){
                $post_id = $_GET['post'];
            }

            $is_original_post = $this->is_original_product( $post_id );

            if( isset( $_GET[ 'post' ] ) || !$is_original_post ){
                $link = admin_url( 'admin.php?page=wpml-wcml&tab=products&prid='.$post_id );
            }

        }

        return $link;
    }

    function restrict_admin_with_redirect() {
        global $sitepress,$pagenow,$woocommerce_wpml;

        $default_lang = $sitepress->get_default_language();
        $current_lang = $sitepress->get_current_language();

        if(($pagenow == 'post.php' && isset($_GET['post'])) || ($pagenow == 'admin.php' && isset($_GET['action']) && $_GET['action'] == 'duplicate_product' && isset($_GET['post']))){
            $prod_lang = $sitepress->get_language_for_element($_GET['post'],'post_product');
        }

        if(!$woocommerce_wpml->settings['trnsl_interface'] && $pagenow == 'post.php' && isset($_GET['post'])&& get_post_type($_GET['post'])=='product' && !$this->is_original_product($_GET['post'])){
            add_action('admin_notices', array($this, 'inf_editing_product_in_non_default_lang'));
        }

        if($woocommerce_wpml->settings['trnsl_interface'] && $pagenow == 'post.php' && !is_ajax() && isset($_GET['post']) && !$this->is_original_product($_GET['post']) && get_post_type($_GET['post'])=='product'){
            if((!isset($_GET['action'])) || (isset($_GET['action']) && !in_array($_GET['action'],array('trash','delete')))){
                $orig_language = $this->get_original_product_language($_GET['post']);
                $prid = apply_filters( 'translate_object_id',$_GET['post'],'product',true,$orig_language);
                wp_redirect(admin_url('admin.php?page=wpml-wcml&tab=products&prid='.$prid)); exit;
            }
        }

        if ($woocommerce_wpml->settings['trnsl_interface'] && $pagenow == 'admin.php' && isset($_GET['action']) && $_GET['action'] == 'duplicate_product' && $default_lang!=$prod_lang) {
            wp_redirect(admin_url('admin.php?page=wpml-wcml&tab=products')); exit;
        }
    }

    function inf_editing_product_in_non_default_lang(){
        $message = '<div class="message error"><p>';
        $message .= sprintf(__('The recommended way to translate WooCommerce products is using the <b><a href="%s">WooCommerce Multilingual products translation</a></b> page. Please use this page only for translating elements that are not available in the WooCommerce Multilingual products translation table.', 'woocommerce-multilingual'), admin_url('admin.php?page=wpml-wcml&tab=products'));
        $message .= '</p></div>';

        echo $message;
    }

    //product quickedit
    function filter_product_actions( $actions, $post ){
        if( $post->post_type == 'product' && !$this->is_original_product($post->ID) && isset($actions['inline hide-if-no-js']) ){
            $new_actions = array();
            foreach($actions as $key => $action){
                if($key == 'inline hide-if-no-js'){
                    $new_actions['quick_hide'] = '<a href="#TB_inline?width=200&height=150&inlineId=quick_edit_notice" class="thickbox" title="'.__('Edit this item inline', 'woocommerce-multilingual').'">'.__('Quick Edit', 'woocommerce-multilingual').'</a>';
                }else{
                    $new_actions[$key] = $action;
                }
            }
            return $new_actions;
        }
        return $actions;
    }


    /**
     * Makes new attribute translatable.
     */
    function make_new_attribute_translatable( $id, $attribute ){
        global $sitepress;
        $wpml_settings = $sitepress->get_settings();

        $wpml_settings['taxonomies_sync_option'][wc_attribute_taxonomy_name($attribute['attribute_name'])] = 1;

        if( isset($wpml_settings['translation-management'])){
            $wpml_settings['translation-management']['taxonomies_readonly_config'][wc_attribute_taxonomy_name( $attribute['attribute_name'] )] = 1;
        }

        $sitepress->save_settings($wpml_settings);

    }

    /**
     * Filters upsell/crosell products in the correct language.
     */
    function filter_found_products_by_language($found_products){
        global $wpdb, $sitepress;

        $current_page_language = $sitepress->get_current_language();

        foreach($found_products as $product_id => $output_v){
            $post_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ". $wpdb->prefix ."icl_translations WHERE element_id = %d AND element_type LIKE 'post_%'", $product_id ) );
            $product_language = $post_data->language_code;

            if($product_language !== $current_page_language){
                unset($found_products[$product_id]);
            }
        }

        return $found_products;
    }

    /**
     * Takes off translated products from the Up-sells/Cross-sells tab.
     *
     * @global type $sitepress
     * @global type $wpdb
     * @return type
     */
    function filter_woocommerce_upsell_crosssell_posts_by_language($posts){
        global $sitepress, $wpdb;

        foreach($posts as $key => $post){
            $post_id = $posts[$key]->ID;
            $post_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ". $wpdb->prefix ."icl_translations WHERE element_id = %d ", $post_id), ARRAY_A );

            if($post_data['language_code'] !== $sitepress->get_current_language()){
                unset($posts[$key]);
            }
        }

        return $posts;
    }

    /**
     * Avoids the post translation links on the product post type.
     *
     * @global type $post
     * @return type
     */
    function hide_post_translation_links($output){
        global $post;

        if(is_null($post)){
            return $output;
        }

        $post_type = get_post_type($post->ID);
        $checkout_page_id = get_option('woocommerce_checkout_page_id');

        if($post_type == 'product' || is_page($checkout_page_id)){
            $output = '';
        }

        return $output;
    }

    function sync_product_stocks_reduce( $order ){
        return $this->sync_product_stocks( $order, 'reduce' );
    }

    function sync_product_stocks_restore( $order ){
        return $this->sync_product_stocks( $order, 'restore' );
    }

    function sync_product_stocks( $order, $action ){
        global $sitepress;
        $order_id = $order->id;

        foreach ( $order->get_items() as $item ) {
            if (isset($item['variation_id']) && $item['variation_id']>0){
                $trid = $sitepress->get_element_trid($item['variation_id'], 'post_product_variation');
                $translations = $sitepress->get_element_translations($trid,'post_product_variation');
                $ld = $sitepress->get_element_language_details($item['variation_id'], 'post_product_variation');
            }else{
                $trid = $sitepress->get_element_trid($item['product_id'], 'post_product');
                $translations = $sitepress->get_element_translations($trid,'post_product');
                $ld = $sitepress->get_element_language_details($item['product_id'], 'post_product');
            }

            // Process for non-current languages
            foreach($translations as $translation){
                if ($ld->language_code != $translation->language_code) {

                    //check if product exist
                    if(get_post_type($translation->element_id) == 'product_variation' && !get_post(wp_get_post_parent_id($translation->element_id))){
                        continue;
                    }

                    $_product = wc_get_product($translation->element_id);

                    if ( $_product && $_product->exists() && $_product->managing_stock() ) {

                        $total_sales    = get_post_meta($_product->id, 'total_sales', true);

                        if( $action == 'reduce'){
                            $stock  = $_product->reduce_stock($item['qty']);
                            $total_sales   += $item['qty'];
                        }else{
                            $stock  = $_product->increase_stock( $item['qty'] );
                            $total_sales   -= $item['qty'];
                        }
                        update_post_meta($translation->element_id, 'total_sales', $total_sales);
                    }
                }

            }
        }

    }

    function sanitize_cpa_values($values) {
        // Text based, separate by pipe
        $values = explode('|', esc_html(stripslashes($values)));
        $values = array_map('trim', $values);
        $values = implode('|', $values);
        return $values;
    }

    /**
     * This function takes care of synchronizing original product
     */
    function sync_post_action( $post_id, $post ){
        global $pagenow, $sitepress, $sitepress_settings,$woocommerce_wpml;
        $original_language = $this->get_original_product_language( $post_id );
        $current_language = $sitepress->get_current_language();
        $duplicated_post_id = apply_filters( 'translate_object_id', $post_id, 'product', false, $original_language );
        $wpml_media_options = maybe_unserialize( get_option( '_wpml_media' ) );

        if( $wpml_media_options[ 'new_content_settings' ][ 'duplicate_media' ] ){
            //sync product gallery
            $this->sync_product_gallery( $duplicated_post_id );
        }

        // check its a product
        $post_type = get_post_type( $post_id );

        //set trid for variations
        if ( $post_type == 'product_variation' ) {
            $var_lang = $sitepress->get_language_for_element( wp_get_post_parent_id( $post_id ), 'post_product' );
            if( $this->is_original_product( wp_get_post_parent_id( $post_id ) ) ){
                $sitepress->set_element_language_details( $post_id, 'post_product_variation', false, $var_lang );
            }
        }

        if ( $post_type != 'product' ) {
            return;
        }

        // exceptions
        $ajax_call = ( !empty( $_POST[ 'icl_ajx_action' ] ) && $_POST[ 'icl_ajx_action' ] == 'make_duplicates' );
        if ( empty( $duplicated_post_id ) || isset( $_POST[ 'autosave' ] ) ) {
            return;
        }

        if( $pagenow != 'post.php' && $pagenow != 'post-new.php' && $pagenow != 'admin.php' && !$ajax_call ){
            return;
        }
        if ( isset( $_GET[ 'action' ] ) && $_GET[ 'action' ] == 'trash') {
            return;
        }

        // If we reach this point, we go ahead with sync.
        // Remove filter to avoid double sync
        remove_action( 'save_post', array( $this, 'sync_post_action' ), 110, 2 );

        do_action( 'wcml_before_sync_product', $duplicated_post_id, $post_id );

        //trnsl_interface option
        if (!$woocommerce_wpml->settings['trnsl_interface'] && $original_language != $current_language ) {

            if( !isset( $_POST[ 'wp-preview' ] ) || empty( $_POST[ 'wp-preview' ] ) ){
                $this->sync_date_and_parent( $duplicated_post_id, $post_id, $current_language );
                $this->sync_product_data( $duplicated_post_id, $post_id, $current_language );

                do_action( 'wcml_before_sync_product_data', $duplicated_post_id, $post_id, $current_language );
            }

            return;
        }

        // get language code
        $language_details = $sitepress->get_element_language_details( $post_id, 'post_product' );
        if ( $pagenow == 'admin.php' && empty( $language_details ) ) {
            //translation editor support: sidestep icl_translations_cache
            global $wpdb;
            $language_details = $wpdb->get_row( $wpdb->prepare( "SELECT element_id, trid, language_code, source_language_code FROM {$wpdb->prefix}icl_translations WHERE element_id = %d AND element_type = 'post_product'", $post_id ) );
        }

        if ( empty( $language_details ) ) {
            return;
        }

        //save custom prices
        $this->save_custom_prices( $duplicated_post_id );

        // pick posts to sync
        $traslated_products = array();
        $translations = $sitepress->get_element_translations( $language_details->trid, 'post_product', false, true );
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
                $this->sync_thumbnail_id( $original_product_id, $translated_product_id, $lang );
            }

            $this->sync_date_and_parent( $original_product_id, $translated_product_id, $lang );

            $this->sync_product_taxonomies( $original_product_id, $translated_product_id, $lang );

            $this->sync_default_product_attr( $original_product_id, $translated_product_id, $lang );

            $this->sync_product_attr( $original_product_id, $translated_product_id );

            $this->update_order_for_product_translations( $original_product_id );

            // synchronize post variations
            $this->sync_product_variations( $original_product_id, $translated_product_id, $lang );
            $this->sync_linked_products( $original_product_id, $translated_product_id, $lang );

        }

        $this->sync_product_variations_custom_prices( $original_product_id );
    }


    function sync_product_variations_action( $product_id ){
        global $sitepress, $wpdb;

        if( $this->is_original_product( $product_id ) ){

            $this->sync_product_variations_custom_prices( $product_id );

            $trid = $sitepress->get_element_trid( $product_id, 'post_product' );
            if ( empty( $trid ) ) {
                $trid = $wpdb->get_var( $wpdb->prepare( "SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_id = %d AND element_type = 'post_product'", $product_id ) );
            }

            $translations = $sitepress->get_element_translations( $trid, 'post_product' );
            foreach ( $translations as $translation ) {
                if ( !$translation->original ) {
                    $this->sync_product_variations($product_id, $translation->element_id, $translation->language_code);

                    $this->sync_default_product_attr($product_id, $translation->element_id, $translation->language_code);
                }
            }

        }
    }

    function remove_translations_for_variations(){
        check_ajax_referer( 'delete-variations', 'security' );

        if ( ! current_user_can( 'edit_products' ) ) {
            die(-1);
        }

        global $sitepress;

        $variation_ids = (array) $_POST['variation_ids'];

        foreach ( $variation_ids as $variation_id ) {

            $trid = $sitepress->get_element_trid( $variation_id, 'post_product_variation' );
            $translations = $sitepress->get_element_translations( $trid, 'post_product_variation' );

            foreach ( $translations as $translation ) {
                if ( !$translation->original ) {
                    wp_delete_post( $translation->element_id );
                }
            }

        }

    }

    function save_custom_prices($post_id){
        global $woocommerce_wpml;

        $nonce = filter_input( INPUT_POST, '_wcml_custom_prices_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

        if( isset( $_POST[ '_wcml_custom_prices' ] ) && isset( $nonce ) && wp_verify_nonce( $nonce, 'wcml_save_custom_prices' ) && !$this->is_variable_product( $post_id ) ){

            if( isset( $_POST[ '_wcml_custom_prices' ][ $post_id ] ) || isset( $_POST[ '_wcml_custom_prices' ][ 'new' ] ) ) {
                $wcml_custom_prices_option = isset( $_POST[ '_wcml_custom_prices' ][ $post_id ] ) ? $_POST[ '_wcml_custom_prices' ][ $post_id ] : isset( $_POST[ '_wcml_custom_prices' ][ 'new' ] );
            }else{
                $current_option = get_post_meta( $post_id, '_wcml_custom_prices_status', true );
                $wcml_custom_prices_option = $current_option ? $current_option : 0;
            }

            update_post_meta( $post_id, '_wcml_custom_prices_status', $wcml_custom_prices_option );

            if( $wcml_custom_prices_option == 1){

                $currencies = $woocommerce_wpml->multi_currency_support->get_currencies();

                foreach( $currencies as $code => $currency ){
                    $sale_price = wc_format_decimal( $_POST[ '_custom_sale_price' ][ $code ] );
                    $regular_price = wc_format_decimal( $_POST[ '_custom_regular_price' ][ $code ] );

                    $date_from = strtotime( $_POST[ '_custom_sale_price_dates_from' ][ $code ] );
                    $date_to = strtotime( $_POST[ '_custom_sale_price_dates_to' ][ $code ] );
                    $schedule = $_POST[ '_wcml_schedule' ][ $code ];

                    $this->update_custom_prices( $post_id, $regular_price, $sale_price, $schedule, $date_from, $date_to, $code );
                }
            }
        }
    }

    //sync product parent & post_status
    function sync_date_and_parent( $duplicated_post_id, $post_id, $lang ){
        global $wpdb,$woocommerce_wpml;

        $tr_parent_id = apply_filters( 'translate_object_id', wp_get_post_parent_id( $duplicated_post_id ), 'product', false, $lang );

        $orig_product = get_post( $duplicated_post_id );

        $args = array();
        $args[ 'post_parent' ] = is_null( $tr_parent_id )? 0 : $tr_parent_id;

        //sync product date
        if( !empty($woocommerce_wpml->settings[ 'products_sync_date' ]) ){
            $args[ 'post_date' ] = $orig_product->post_date;
        }

        $wpdb->update(
            $wpdb->posts,
            $args,
            array( 'id' => $post_id )
        );


    }

    function update_custom_prices($post_id,$regular_price,$sale_price,$schedule,$date_from,$date_to,$code){
        $price = '';
        update_post_meta($post_id,'_regular_price_'.$code,$regular_price);
        update_post_meta($post_id,'_sale_price_'.$code,$sale_price);

        // Dates
        update_post_meta($post_id,'_wcml_schedule_'.$code,$schedule);
        if ( $date_from )
            update_post_meta( $post_id, '_sale_price_dates_from_'.$code,  $date_from  );
        else
            update_post_meta( $post_id, '_sale_price_dates_from_'.$code, '' );

        if ( $date_to )
            update_post_meta( $post_id, '_sale_price_dates_to_'.$code,  $date_to );
        else
            update_post_meta( $post_id, '_sale_price_dates_to_'.$code, '' );

        if ( $date_to && ! $date_from )
            update_post_meta( $post_id, '_sale_price_dates_from_'.$code, strtotime( 'NOW', current_time( 'timestamp' ) ) );

        // Update price if on sale
        if ( $sale_price != '' && $date_to == '' && $date_from == '' ){
            $price = stripslashes( $sale_price );
            update_post_meta( $post_id, '_price_'.$code, stripslashes( $sale_price ) );
        }else{
            $price = stripslashes( $regular_price );
            update_post_meta( $post_id, '_price_'.$code, stripslashes( $regular_price ) );
        }

        if ( $sale_price != '' && $date_from < strtotime( 'NOW', current_time( 'timestamp' ) ) ){
            update_post_meta( $post_id, '_price_'.$code, stripslashes($sale_price) );
            $price = stripslashes( $sale_price );
        }

        if ( $date_to && $date_to < strtotime( 'NOW', current_time( 'timestamp' ) ) ) {
            update_post_meta( $post_id, '_price_'.$code, stripslashes($regular_price) );
            $price = stripslashes( $regular_price );
            update_post_meta( $post_id, '_sale_price_dates_from_'.$code, '');
            update_post_meta( $post_id, '_sale_price_dates_to_'.$code, '');
        }

        return $price;
    }

    function sync_product_variations_custom_prices( $product_id ){
        global $wpdb,$woocommerce_wpml;
        $is_variable_product = $this->is_variable_product($product_id);

        if($is_variable_product){
            $get_all_post_variations = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->posts
                            WHERE post_status IN ('publish','private') AND post_type = 'product_variation' AND post_parent = %d ORDER BY ID",$product_id));

            $duplicated_post_variation_ids = array();
            $min_max_prices = array();
            foreach($get_all_post_variations as $k => $post_data){
                $duplicated_post_variation_ids[] = $post_data->ID;


                if( !isset( $_POST['_wcml_custom_prices'][$post_data->ID] ) ){
                    continue; // save changes for individual variation
                }

                //save custom prices for variation
                $nonce = filter_input( INPUT_POST, '_wcml_custom_prices_variation_' . $post_data->ID . '_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
                if(isset( $_POST['_wcml_custom_prices'][$post_data->ID]) && isset( $nonce ) && wp_verify_nonce( $nonce, 'wcml_save_custom_prices_variation_' . $post_data->ID )){

                    update_post_meta($post_data->ID,'_wcml_custom_prices_status',$_POST['_wcml_custom_prices'][$post_data->ID]);

                    $currencies = $woocommerce_wpml->multi_currency_support->get_currencies();

                    if($_POST['_wcml_custom_prices'][$post_data->ID] == 1){
                        foreach($currencies as $code => $currency){
                            $sale_price = $_POST['_custom_variation_sale_price'][$code][$post_data->ID];
                            $regular_price = $_POST['_custom_variation_regular_price'][$code][$post_data->ID];

                            $date_from = strtotime($_POST['_custom_sale_price_dates_from'][$code][$post_data->ID]);
                            $date_to = strtotime($_POST['_custom_sale_price_dates_to'][$code][$post_data->ID]);
                            $schedule = $_POST['_wcml_schedule'][$code][$post_data->ID];

                            $price = $this->update_custom_prices($post_data->ID,$regular_price,$sale_price,$schedule,$date_from,$date_to,$code);

                            if(!isset($min_max_prices['_min_variation_price_'.$code]) || ($price && $price < $min_max_prices['_min_variation_price_'.$code])){
                                $min_max_prices['_min_variation_price_'.$code] = $price;
                                $min_max_prices['_min_price_variation_id_'.$code] = $post_data->ID;
                            }

                            if(!isset($min_max_prices['_max_variation_price_'.$code]) || ($price && $price > $min_max_prices['_max_variation_price_'.$code])){
                                $min_max_prices['_max_variation_price_'.$code] = $price;
                                $min_max_prices['_max_price_variation_id_'.$code] = $post_data->ID;
                            }

                            if(!isset($min_max_prices['_min_variation_regular_price_'.$code]) || ($regular_price && $regular_price < $min_max_prices['_min_variation_regular_price_'.$code])){
                                $min_max_prices['_min_variation_regular_price_'.$code] = $regular_price;
                            }

                            if(!isset($min_max_prices['_max_variation_regular_price_'.$code]) || ($regular_price && $regular_price > $min_max_prices['_max_variation_regular_price_'.$code])){
                                $min_max_prices['_max_variation_regular_price_'.$code] = $regular_price;
                            }

                            if(!isset($min_max_prices['_min_variation_sale_price_'.$code]) || ($sale_price && $sale_price < $min_max_prices['_min_variation_sale_price_'.$code])){
                                $min_max_prices['_min_variation_sale_price_'.$code] = $sale_price;
                            }

                            if(!isset($min_max_prices['_max_variation_sale_price_'.$code]) || ($sale_price && $sale_price > $min_max_prices['_max_variation_sale_price_'.$code])){
                                $min_max_prices['_max_variation_sale_price_'.$code] = $sale_price;
                            }
                        }
                    }
                }
            }
        }
    }


    function sync_default_product_attr( $orig_post_id, $transl_post_id, $lang ){
        global $wpdb;
        $original_default_attributes = get_post_meta( $orig_post_id, '_default_attributes', true );
        if( !empty( $original_default_attributes ) ){
            $unserialized_default_attributes = array();
            foreach(maybe_unserialize( $original_default_attributes ) as $attribute => $default_term_slug ){
                // get the correct language
                if ( substr( $attribute, 0, 3 ) == 'pa_' ) {
                    //attr is taxonomy
                    $default_term_id = $this->wcml_get_term_id_by_slug( $attribute, $default_term_slug );
                    $tr_id = apply_filters( 'translate_object_id', $default_term_id, $attribute, false, $lang );

                    if( $tr_id ){
                        $translated_term = $this->wcml_get_term_by_id( $tr_id, $attribute );
                        $unserialized_default_attributes[$attribute] = $translated_term->slug;
                    }
                }else{
                    //custom attr
                    $orig_product_attributes = get_post_meta( $orig_post_id, '_product_attributes', true );
                    $unserialized_orig_product_attributes = maybe_unserialize( $orig_product_attributes );
                    if( isset( $unserialized_orig_product_attributes[$attribute] ) ){
                        $orig_attr_values = explode( '|', $unserialized_orig_product_attributes[$attribute]['value'] );
                        foreach( $orig_attr_values as $key=>$orig_attr_value ){
                            $orig_attr_value_sanitized = strtolower( sanitize_title ( $orig_attr_value ) );
                            if( $orig_attr_value_sanitized == $default_term_slug || trim( $orig_attr_value ) == trim( $default_term_slug ) ){
                                $tnsl_product_attributes = get_post_meta( $transl_post_id, '_product_attributes', true );
                                $unserialized_tnsl_product_attributes = maybe_unserialize( $tnsl_product_attributes );
                                if( isset( $unserialized_tnsl_product_attributes[$attribute] ) ){
                                    $trnsl_attr_values = explode( '|', $unserialized_tnsl_product_attributes[$attribute]['value'] );

                                    if( $orig_attr_value_sanitized == $default_term_slug ){
                                        $trnsl_attr_value = strtolower( sanitize_title( trim( $trnsl_attr_values[$key] ) ) );
                                    }else{
                                        $trnsl_attr_value = trim( $trnsl_attr_values[$key] );
                                    }

                                    $unserialized_default_attributes[$attribute] = $trnsl_attr_value;
                                }
                            }
                        }
                    }
                }
            }

            $data = array( 'meta_value' => maybe_serialize( $unserialized_default_attributes ) );
        }else{
            $data = array( 'meta_value' => maybe_serialize( array() ) );
        }

        $where = array( 'post_id' => $transl_post_id, 'meta_key' => '_default_attributes' );
        $wpdb->update( $wpdb->postmeta, $data, $where );
    }

    /*
     * get attribute translation
     */
    function get_custom_attribute_translation($product_id, $attribute_key, $attribute, $lang_code) {
        $tr_post_id = apply_filters( 'translate_object_id',$product_id, 'product', false, $lang_code);
        $transl = array();
        if ($tr_post_id) {
            if (!$attribute['is_taxonomy']) {
                $tr_attrs = get_post_meta($tr_post_id, '_product_attributes', true);

                if ($tr_attrs) {
                    foreach ($tr_attrs as $key=>$tr_attr) {
                        if ($attribute_key == $key) {
                            $transl['value'] = $tr_attr['value'];
                            $trnsl_labels = maybe_unserialize( get_post_meta( $tr_post_id, 'attr_label_translations', true ) );
                            if(isset($trnsl_labels[$lang_code][$attribute_key])){
                                $transl['name'] = $trnsl_labels[$lang_code][$attribute_key];
                            }else{
                                $transl['name'] = $tr_attr['name'];
                            }
                            return $transl;
                        }
                    }
                }
                return false;
            }
        }
        return false;
    }

    //get product content
    function get_product_contents($product_id){
        global $woocommerce_wpml;
        $contents = array();
        $contents[] = 'title';
        $contents[] = 'content';
        $contents[] = 'excerpt';
        $contents[] = 'images';

        if(!isset($product_type)){
            foreach(wp_get_post_terms($product_id, 'product_type', array("fields" => "names")) as $type){
                $product_type = $type;
            }
        }

        if(!$woocommerce_wpml->settings['file_path_sync'] && isset($product_type) && $product_type == 'variable'){
            $contents[] = 'variations';
        }

        global $sitepress;
        $settings = $sitepress->get_settings();

        $post_custom_keys = $this->wcml_get_translatable_product_custom_fields( $product_id );

        foreach($post_custom_keys as $meta_key){

            if($this->check_custom_field_is_single_value($product_id,$meta_key)){
                if(in_array($meta_key,$this->not_display_fields_for_variables_product)){
                    continue;
                }
            }else{
                $exception = apply_filters('wcml_product_content_exception',true,$product_id,$meta_key);
                if($exception){
                    continue;
                }
            }
            $contents[] = $meta_key;

        }

        return apply_filters('wcml_product_content_fields', $contents, $product_id );
    }

    //get product content labels
    function get_product_contents_labels($product_id){
        global $woocommerce_wpml;

        $contents = array();
        $contents[] = __('Title', 'woocommerce-multilingual');
        $contents[] = __('Content / Description', 'woocommerce-multilingual');
        $contents[] = __('Excerpt', 'woocommerce-multilingual');
        $contents[] = __('Images', 'woocommerce-multilingual');

        foreach(wp_get_post_terms($product_id, 'product_type', array("fields" => "names")) as $type){
            $product_type = $type;
        }

        if(!$woocommerce_wpml->settings['file_path_sync'] && isset($product_type) && $product_type == 'variable'){
            $contents[] = __('Variations', 'woocommerce-multilingual');
        }

        global $sitepress,$wpseo_metabox;
        $settings = $sitepress->get_settings();

        $post_custom_keys = $this->wcml_get_translatable_product_custom_fields( $product_id );

        foreach( $post_custom_keys as $meta_key ){

            if(in_array($meta_key,$this->not_display_fields_for_variables_product)){
                continue;
            }

            if($this->check_custom_field_is_single_value($product_id,$meta_key)){
                if(defined('WPSEO_VERSION')){
                    if(!is_null($wpseo_metabox) && in_array($meta_key,$this->yoast_seo_fields)){
                        $wpseo_metabox_values = $wpseo_metabox->get_meta_boxes('product');
                        $contents[] = $wpseo_metabox_values[str_replace('_yoast_wpseo_','',$meta_key)]['title'];
                        continue;
                    }
                }
            }else{
                $exception = apply_filters('wcml_product_content_exception',true,$product_id,$meta_key);
                if($exception){
                    continue;
                }

            }

            $custom_key_label = apply_filters( 'wcml_product_content_label', $meta_key, $product_id );
            if( $custom_key_label != $meta_key ){
                $contents[] = $custom_key_label;
                continue;
            }

            $custom_key_label = str_replace('_',' ',$meta_key);
            $contents[] = trim($custom_key_label[0]) ? ucfirst($custom_key_label) : ucfirst(substr($custom_key_label,1));


        }

        return apply_filters('wcml_product_content_fields_label', $contents, $product_id);
    }


    function wcml_get_translatable_product_custom_fields( $product_id ){
        $all_post_custom_keys = get_post_custom_keys($product_id) ;

        // filter out not translatable custom fields
        $post_custom_keys = array();
        foreach( $all_post_custom_keys as $meta_key ){
            if(isset($settings['translation-management']['custom_fields_translation'][$meta_key]) && $settings['translation-management']['custom_fields_translation'][$meta_key] == 2){
                $post_custom_keys[] = $meta_key;
            }
        }

        return apply_filters ( 'wcml_translatable_custom_fields',  $post_custom_keys );
    }

    function check_custom_field_is_single_value($product_id,$meta_key){

        $meta_value = maybe_unserialize( get_post_meta( $product_id, $meta_key, true ) );
        if(is_array($meta_value)){
            return false;
        }else{
            return apply_filters( 'wcml_check_is_single', true, $product_id, $meta_key );
        }

    }

    //get product content translation
    function get_product_content_translation($product_id,$content,$lang_code){
        global $woocommerce_wpml;

        $tr_post_id = apply_filters( 'translate_object_id',$product_id, 'product', false, $lang_code);

        if (is_null($tr_post_id) && (in_array($content, array('title','content','excerpt','variations','images'))))
            return false;

        switch ($content) {
            case 'title':
                $tr_post = get_post($tr_post_id);
                $tr_post_content['title'] = $tr_post->post_title;
                $tr_post_content['name'] = $tr_post->post_name;
                return $tr_post_content;
                break;
            case 'content':
                $tr_post = get_post($tr_post_id);
                return $tr_post->post_content;
                break;
            case 'excerpt':
                $tr_post = get_post($tr_post_id);
                return $tr_post->post_excerpt;
                break;
            default:
                global $wpdb,$sitepress;

                foreach(wp_get_post_terms($product_id, 'product_type', array("fields" => "names")) as $type){
                    $product_type = $type;
                }

                if($content == 'regular_price'){
                    $var_key = '_regular_price';
                }elseif($content == 'sale_price'){
                    $var_key = '_sale_price';
                }elseif($content == 'variations_file_paths' && $product_type == 'variable' && !$woocommerce_wpml->settings['file_path_sync']){
                    $var_key =  '_file_paths';
                }else{
                    return get_post_meta($tr_post_id,$content,true);
                }

                if($product_type == 'simple'  || $product_type == 'external'){
                    return get_post_meta($tr_post_id, $var_key, true);
                }

                if($product_type == 'variable'){
                    $variables = array();
                    $variables_all = array();
                    $variations = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_parent = %d AND post_type = 'product_variation'",$tr_post_id));

                    foreach($variations as $variation){

                        $variation_values = $wpdb->get_results($wpdb->prepare("SELECT meta_key,meta_value FROM $wpdb->postmeta WHERE (meta_key = %s OR meta_key LIKE 'attribute_%%') AND post_id = %d",$var_key,$variation->ID));
                        $variables = array();
                        $variables['value'] = '';
                        $variables['label'] = '';
                        $variables['variation'] = $variation->ID;
                        foreach($variation_values as $variation_value){
                            if($variation_value->meta_key == $var_key){
                                $variables['value'] = $variation_value->meta_value;
                            }else{
                                //get attribute name
                                $attribute = str_replace('attribute_','',$variation_value->meta_key);
                                $tr_product_attr  = maybe_unserialize(get_post_meta($tr_post_id,'_product_attributes',true));
                                $term_id = $this->wcml_get_term_id_by_slug( $tr_product_attr[$attribute]['name'], $variation_value->meta_value );
                                $term_name = $this->wcml_get_term_by_id( $term_id, $tr_product_attr[$attribute]['name'] );
                                if($term_name){
                                    //if attribute is taxonomy
                                    $term_name = $term_name->name;
                                }else{
                                    //if attribute isn't taxonomy
                                    if(isset($tr_product_attr[$attribute]) && !$tr_product_attr[$attribute]['is_taxonomy']){
                                        $term_name = $variation_value->meta_value;
                                    }

                                    if(!$term_name){
                                        $original_language = $sitepress->get_language_for_element($variation->ID,'post_product_variation');
                                        $orig_variation_id = apply_filters( 'translate_object_id',$variation->ID,'product_variation',true,$original_language);

                                        if(get_post_meta($orig_variation_id,$var_key,true) == ''){
                                            if(substr($tr_product_attr[$attribute]['name'], 0, 3) == 'pa_'){
                                                $attr_name =  str_replace('pa_','',$tr_product_attr[$attribute]['name']);
                                                $attr_name =  str_replace('_','',$attr_name);
                                            }else{
                                                $attr_name =  str_replace('_','',$tr_product_attr[$attribute]['name']);
                                            }

                                            $label = sprintf(__('Any %s', 'woocommerce-multilingual'),$attr_name);
                                            $variables['label'] .= $label.' & ';
                                            continue;
                                        }else{
                                            $label = __('Please translate all attributes', 'woocommerce-multilingual');
                                            $variables['label'] .= $label.' & ';
                                            $variables['not_translated'] = true;
                                            continue;
                                        }
                                    }
                                }
                                $variables['label'] .= urldecode($term_name).' & ';
                            }
                        }

                        $variables['label'] = substr($variables['label'],0,strlen($variables['label'])-3);
                        $variables_all[$variation->ID] = $variables;

                    }
                    return $variables_all;
                }
                break;
        }
    }

    function product_variations_box($product_id, $lang, $is_duplicate_product = false)
    {
        global $sitepress, $woocommerce_wpml,$wpdb,$woocommerce;

        $original_language = $sitepress->get_language_for_element($product_id,'post_product');
        $template_data = array();
        $template_data['all_variations_ids'] = array();

        $trn_product_id = null;
        if ($original_language != $lang) {
            $trn_product_id = apply_filters( 'translate_object_id',$product_id, 'product', false, $lang);
        }

        if ($original_language == $lang) {
            $template_data['original'] = true;
        } else {
            $template_data['original'] = false;
        }

        //file path
        if (!$woocommerce_wpml->settings['file_path_sync']) {
            global $wpdb;
            $is_downloable = $wpdb->get_results($wpdb->prepare("SELECT pm.meta_value FROM $wpdb->posts AS p LEFT JOIN $wpdb->postmeta AS pm ON p.id=pm.post_id WHERE p.post_parent = %d AND p.post_type = 'product_variation' AND pm.meta_key='_downloadable' AND pm.meta_value = 'yes'", $product_id));
            $template_data['all_file_paths'] = $this->get_product_content_translation($product_id, 'variations_file_paths', $lang);

            $variations = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_parent = %d AND post_type = 'product_variation'",is_null($trn_product_id)?$product_id:$trn_product_id));

            foreach($variations as $variation){
                $files = maybe_unserialize(get_post_meta($variation->ID,'_downloadable_files',true));

                if($files){
                    if(is_null($trn_product_id)){
                        $template_data['all_file_paths']['count'] = count($files);
                    }else{
                        $template_data['all_file_paths']['count'] = count(maybe_unserialize(get_post_meta(apply_filters( 'translate_object_id',$variation->ID, 'product_variation', false, $original_language),'_downloadable_files',true)));
                    }
                    foreach($files as $file){
                        $variables = array();
                        $variables['value'] = $file['file'];
                        $variables['label'] = $file['name'];
                        $template_data['all_file_paths'][$variation->ID][] = $variables;
                    }
                }
            }
        }

        $is_product_has_variations = $wpdb->get_var($wpdb->prepare("SELECT count(id) FROM $wpdb->posts WHERE post_parent = %d AND post_type = 'product_variation'",$product_id));

        foreach ($template_data['all_file_paths'] as $key => $price) {
            $template_data['all_variations_ids'][] = $key;
        }

        if (!$is_product_has_variations){
            $template_data['empty_variations'] = true;
        } elseif($original_language != $lang && is_null($trn_product_id)){
            $template_data['empty_translation'] = true;
        }elseif (!$is_downloable){
            $template_data['not_downloaded'] = true;
        }

        include WCML_PLUGIN_PATH . '/menu/sub/variations_box.php';
    }

    function product_images_box($product_id,$lang, $is_duplicate_product = false ) {
        global $sitepress,$wpdb;
        $original_language = $this->get_original_product_language($product_id);
        if(!$this->is_original_product($product_id)){
            $orig_product_id = apply_filters( 'translate_object_id',$product_id, 'product', false, $original_language);
        }else{
            $orig_product_id = $product_id;
        }

        $product_id = apply_filters( 'translate_object_id',$product_id, 'product', false, $lang);
        $template_data = array();

        if($original_language == $lang){
            $template_data['original'] = true;
        }else{
            $template_data['original'] = false;
        }
        if (!is_null($product_id)) {
            $product_images = $this->product_images_ids($orig_product_id);
            if (empty($product_images)) {
                $template_data['empty_images'] = true;
            } else {
                if ($original_language == $lang) {
                    $template_data['images_thumbnails'] = $product_images;
                }
                foreach ($product_images as $prod_image) {
                    $prod_image = apply_filters( 'translate_object_id',$prod_image, 'attachment', false, $lang);
                    $images_texs = array();
                    if(!is_null($prod_image)){
                        $attachment_data = $wpdb->get_row($wpdb->prepare("SELECT post_title,post_excerpt,post_content FROM $wpdb->posts WHERE ID = %d", $prod_image));
                        $images_texs['title'] = $attachment_data->post_title;
                        $images_texs['caption'] = $attachment_data->post_excerpt;
                        $images_texs['description'] = $attachment_data->post_content;
                    }
                    $template_data['images_data'][$prod_image] = $images_texs;
                }
            }
        } else {
            $template_data['empty_translation'] = true;
        }

        include WCML_PLUGIN_PATH . '/menu/sub/images-box.php';
    }

    function custom_box( $product_id, $product_content, $trn_contents, $lang, $lang_name, $is_duplicate_product ){

        global $sitepress;
        $original_language = $sitepress->get_language_for_element($product_id,'post_product');
        $tr_product_id = apply_filters( 'translate_object_id',$product_id, 'product', false, $lang);
        $template_data = array();

        $template_data['product_id'] = $product_id;
        $template_data['tr_product_id'] = $tr_product_id;
        $template_data['product_content'] = $product_content;
        $template_data['trn_contents'] = $trn_contents;
        $template_data['lang'] = $lang;
        $template_data['lang_name'] = $lang_name;
        $template_data['is_duplicate_product'] = $is_duplicate_product;

        if($original_language == $lang){
            $template_data['original'] = true;
        }else{
            $template_data['original'] = false;
        }

        if($tr_product_id){
            $template_data['translation_exist'] = true;
        }else{
            $template_data['translation_exist'] = false;
        }

        include WCML_PLUGIN_PATH . '/menu/sub/custom-box.php';

    }

    function downloadable_files_box($html,$data,$lang){
        if($data['product_content'] == '_downloadable_files'){
            $files = maybe_unserialize(get_post_meta($data['tr_product_id'],'_downloadable_files',true));
            $data['count'] = count(maybe_unserialize(get_post_meta($data['product_id'],'_downloadable_files',true)));
            if($files){
                foreach($files as $file){
                    $variables = array();
                    $variables['value'] = $file['file'];
                    $variables['label'] = $file['name'];
                    $data['files_data'][] = $variables;
                }
            }else{
                $data['files_data'] = array();
            }

            ob_start();
            include WCML_PLUGIN_PATH . '/menu/sub/downloadable-files-box.php';

            return ob_get_clean();
        }
    }


    function product_images_ids($product_id){
        global $wpdb;
        $product_images_ids = array();

        //thumbnail image
        $tmb = get_post_meta($product_id,'_thumbnail_id',true);
        if($tmb){
            $product_images_ids[] = $tmb;
        }

        //product gallery
        $product_gallery = get_post_meta($product_id,'_product_image_gallery',true);
        if($product_gallery){
            $product_gallery = explode(',',$product_gallery);
            foreach($product_gallery as $img){
                if(!in_array($img,$product_images_ids)){
                    $product_images_ids[] = $img;
                }
            }
        }

        foreach(wp_get_post_terms($product_id, 'product_type', array("fields" => "names")) as $type){
            $product_type = $type;
        }

        if(isset($product_type) && $product_type == 'variable'){
            $get_post_variations_image = $wpdb->get_col($wpdb->prepare("SELECT pm.meta_value FROM $wpdb->posts AS p
                            LEFT JOIN $wpdb->postmeta AS pm ON p.ID = pm.post_id
                            WHERE pm.meta_key='_thumbnail_id' AND p.post_status IN ('publish','private') AND p.post_type = 'product_variation' AND p.post_parent = %d ORDER BY ID",$product_id));
            foreach($get_post_variations_image as $variation_image){
                if($variation_image && !in_array($variation_image,$product_images_ids)){
                    $product_images_ids[] = $variation_image;
                }
            }
        }

        return $product_images_ids;
    }

    // translation-management $trid filter
    function wpml_tm_save_post_trid_value($trid,$post_id){
        if(isset($_POST['action']) && $_POST['action'] == 'wcml_update_product'){
            global $sitepress;
            $trid = $sitepress->get_element_trid($post_id, 'post_product');
        }
        return $trid;
    }

    // translation-management $lang filter
    function wpml_tm_save_post_lang_value($lang,$post_id){
        if(isset($_POST['action']) &&  $_POST['action'] == 'wcml_update_product'){
            global $sitepress;
            $lang = $sitepress->get_language_for_element($post_id,'post_product');
        }
        return $lang;
    }

    // sitepress $trid filter
    function wpml_save_post_trid_value($trid,$post_status){
        if(isset($_POST['action']) && $_POST['action'] == 'wcml_update_product' && $post_status != 'auto-draft'){
            global $sitepress;
            $trid = $sitepress->get_element_trid($_POST['product_id'], 'post_product');
        }
        return $trid;
    }

    // sitepress $lang filter
    function wpml_save_post_lang_value($lang){
        if(isset($_POST['action']) &&  $_POST['action'] == 'wcml_update_product' && isset($_POST['to_lang'])){
            $lang = filter_input( INPUT_POST, 'to_lang', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        }
        return $lang;
    }

    //update taxonomy in variations
    function update_taxonomy_in_variations(){
        global $wpdb;
        $original_element   = filter_input( INPUT_POST, 'translation_of', FILTER_SANITIZE_NUMBER_INT );
        $taxonomy           = filter_input( INPUT_POST, 'taxonomy', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $language           = filter_input( INPUT_POST, 'language', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $slug               = filter_input( INPUT_POST, 'slug', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $name               = filter_input( INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $term_id = $wpdb->get_var($wpdb->prepare("SELECT term_id FROM $wpdb->term_taxonomy WHERE term_taxonomy_id = %d",$original_element));
        $original_term = $this->wcml_get_term_by_id( $term_id, $taxonomy );
        $original_slug = $original_term->slug;
        //get variations with original slug

        $variations = $wpdb->get_results($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key=%s AND meta_value = %s",'attribute_'.$taxonomy,$original_slug));

        foreach($variations as $variation){
            //update taxonomy in translation of variation
            $trnsl_variation_id = apply_filters( 'translate_object_id',$variation->post_id,'product_variation',false,$language);
            if(!is_null($trnsl_variation_id)){
                if(!$slug){
                    $slug = sanitize_title($name);
                }
                update_post_meta($trnsl_variation_id,'attribute_'.$taxonomy,$slug);
            }
        }
    }

    function sync_product_gallery_duplicate_attachment($att_id, $dup_att_id){

        $product_id = wp_get_post_parent_id($att_id);
        $post_type = get_post_type($product_id);
        if ($post_type != 'product') {
            return;
        }
        $this->sync_product_gallery($product_id);
    }

    function remove_language_options(){
        global $WPML_media,$typenow;
        if( defined('WPML_MEDIA_VERSION') && $typenow == 'product'){
            remove_action('icl_post_languages_options_after',array( $WPML_media,'language_options'));
            add_action( 'icl_post_languages_options_after', array( $this, 'media_inputs' ) );
        }
    }

    function media_inputs(){
        $wpml_media_options = maybe_unserialize(get_option('_wpml_media'));

        echo '<input name="icl_duplicate_attachments" type="hidden" value="'.$wpml_media_options['new_content_settings']['duplicate_media'].'" />';
        echo '<input name="icl_duplicate_featured_image" type="hidden" value="'.$wpml_media_options['new_content_settings']['duplicate_featured'].'" />';
    }

    function icl_pro_translation_completed($tr_product_id) {
        global $sitepress;

        $trid = $sitepress->get_element_trid($tr_product_id,'post_product');
        $translations = $sitepress->get_element_translations($trid,'post_product');

        foreach($translations as $translation){
            if($translation->original){
                $original_product_id = $translation->element_id;
            }
        }

        if(!isset($original_product_id)){
            return;
        }

        $lang = $sitepress->get_language_for_element($tr_product_id,'post_product');

        $this->sync_product_data($original_product_id, $tr_product_id, $lang);
    }

    function translate_cart_contents($item, $values, $key) {
        if ( version_compare( WOOCOMMERCE_VERSION, "2.0.0" ) < 0 ) {
            // clearing subtotal triggers calculate_totals (WC 1.x)
            // for WC 2.x its done with the function below
            $_SESSION['subtotal'] = 0;
        }

        // translate the product id and product data
        $item['product_id'] = apply_filters( 'translate_object_id',$item['product_id'], 'product', true);
        if ($item['variation_id']) {
            $item['variation_id'] = apply_filters( 'translate_object_id',$item['variation_id'], 'product_variation', true);
        }
        $product_id = $item['variation_id'] ? $item['variation_id'] : $item['product_id'];
        $item['data']->post->post_title = get_the_title($item['product_id']);
        return $item;
    }

    function translate_cart_subtotal($cart) {

        if ( apply_filters( 'translate_cart_subtotal_exception', false, $cart ) ){
            return;
        }

        $cart->calculate_totals();
    }

    // refresh cart total to return correct price from WC object
    function wcml_refresh_cart_total() {
        WC()->cart->calculate_totals();
    }

    function remove_variation_ajax(){
        global $sitepress;
        if(isset($_POST['variation_id'])){
            $trid = $sitepress->get_element_trid( filter_input( INPUT_POST, 'variation_id', FILTER_SANITIZE_NUMBER_INT ), 'post_product_variation');
            if ($trid) {
                $translations = $sitepress->get_element_translations($trid, 'post_product_variation');
                if($translations){
                    foreach($translations as $translation){
                        if(!$translation->original){
                            wp_delete_post($translation->element_id,true);
                        }
                    }
                }
            }
        }
    }

    function icl_make_duplicate($master_post_id, $lang, $postarr, $id){
        if( get_post_type( $master_post_id ) == 'product' ){
            $this->sync_product_data($master_post_id, $id, $lang);

            // recount terms only first time
            if( !get_post_meta( $id, '_wcml_terms_recount' ) ){
                $product_cats = wp_get_post_terms( $id, 'product_cat' );

                if(!empty($product_cats)) {

                    foreach ($product_cats as $product_cat) {
                        $cats_to_recount[$product_cat->term_id] = $product_cat->parent;
                    }
                    _wc_term_recount($cats_to_recount, get_taxonomy('product_cat'), true, false);
                    add_post_meta($id, '_wcml_terms_recount', 'yes');

                }
            }

        }
    }

    function woocommerce_json_search_found_products($found_products) {
        global $sitepress;

        $new_found_products = array();
        foreach($found_products as $post => $formatted_product_name) {
            $parent = wp_get_post_parent_id($post);

            if( ( isset( $_COOKIE [ '_wcml_dashboard_order_language' ] )
                    && ( ( !$parent && $sitepress->get_language_for_element( $post, 'post_product') == $_COOKIE [ '_wcml_dashboard_order_language' ] )
                        || ( $parent && $sitepress->get_language_for_element( $parent, 'post_product') == $_COOKIE [ '_wcml_dashboard_order_language' ] ) )
                )
                ||
                ( ! isset( $_COOKIE [ '_wcml_dashboard_order_language' ] )
                    && ( ( !$parent && $this->is_original_product($post) )
                        || ( $parent && $this->is_original_product($parent) ) )
                )
            ) {

                $new_found_products[$post] = $formatted_product_name;

            }
        }

        return $new_found_products;
    }

    function wcml_refresh_fragments(){
        WC()->cart->calculate_totals();
        $this->wcml_refresh_text_domain();
    }

    function woocommerce_email_refresh_text_domain(){
        if( !isset($_GET['page']) || ( isset($_GET['page']) && $_GET['page'] != 'wc-settings' ) ){
            $this->wcml_refresh_text_domain();
        }
    }

    function wcml_refresh_text_domain(){
        global $woocommerce;
        $domain = 'woocommerce';
        unload_textdomain($domain);
        $woocommerce->load_plugin_textdomain();
    }


    function _mce_set_plugins($settings) {
        if (is_rtl()) {
            if(!empty($settings['plugins'])){
                $this->tinymce_plugins_for_rtl = $settings['plugins'];
            }else{
                $settings['plugins'] = $this->tinymce_plugins_for_rtl;
            }
        }
        return $settings;
    }

    /*
     *  Update cart and cart session when switch language
     */
    function woocommerce_calculate_totals( $cart ){
        global $sitepress, $woocommerce, $woocommerce_wpml;
        $current_language = $sitepress->get_current_language();
        $new_cart_data = array();

        foreach( $cart->cart_contents as $key => $cart_item ){
            $tr_product_id = apply_filters( 'translate_object_id',$cart_item[ 'product_id' ], 'product', false, $current_language );

            //translate custom attr labels in cart object
            if( isset( $cart_item[ 'data' ]->product_attributes ) ){
                foreach( $cart_item[ 'data' ]->product_attributes as $attr_key => $product_attribute ){
                    if( !$product_attribute[ 'is_taxonomy' ] ){
                        $cart->cart_contents[ $key ][ 'data' ]->product_attributes[ $attr_key ][ 'name' ] = $woocommerce_wpml->strings->translated_attribute_label( $product_attribute[ 'name' ], $product_attribute[ 'name' ], $tr_product_id );

                    }
                }
            }

            //translate custom attr value in cart object
            if( isset( $cart_item[ 'variation' ] ) && is_array( $cart_item[ 'variation' ] ) ){
                foreach( $cart_item[ 'variation' ] as $attr_key => $attribute ){
                    $cart->cart_contents[ $key ][ 'variation' ][ $attr_key ] = $this->get_cart_attribute_translation( $attr_key, $attribute, $cart_item['variation_id'], $current_language, $cart_item[ 'data' ]->parent->id, $tr_product_id );
                }
            }

            if( $cart_item[ 'product_id' ] == $tr_product_id ){
                $new_cart_data[ $key ] = apply_filters( 'wcml_cart_contents_not_changed', $cart->cart_contents[$key], $key, $current_language );
                continue;
            }


            if( isset( $cart->cart_contents[ $key ][ 'variation_id' ] ) && $cart->cart_contents[ $key ][ 'variation_id' ] ){
                $tr_variation_id = apply_filters( 'translate_object_id', $cart_item[ 'variation_id' ], 'product_variation', false, $current_language );
                if( !is_null( $tr_variation_id ) ){
                    $cart->cart_contents[ $key ][ 'product_id' ] = intval( $tr_product_id );
                    $cart->cart_contents[ $key ][ 'variation_id' ] = intval( $tr_variation_id );
                    $cart->cart_contents[ $key ][ 'data' ]->id = intval( $tr_product_id );
                    $cart->cart_contents[ $key ][ 'data' ]->post = get_post( $tr_product_id );
                }
            }else{
                if( !is_null( $tr_product_id ) ){
                    $cart->cart_contents[ $key ][ 'product_id' ] = intval( $tr_product_id );
                    $cart->cart_contents[ $key ][ 'data' ]->id = intval( $tr_product_id );
                    $cart->cart_contents[ $key ][ 'data' ]->post = get_post( $tr_product_id );
                }
            }

            if( !is_null( $tr_product_id ) ){
                $cart_item_data = $this->get_cart_item_data_from_cart( $cart->cart_contents[ $key ] );
                $new_key = $woocommerce->cart->generate_cart_id( $cart->cart_contents[ $key ][ 'product_id' ], $cart->cart_contents[ $key ][ 'variation_id' ], $cart->cart_contents[ $key ][ 'variation' ], $cart_item_data );
                $cart->cart_contents = apply_filters( 'wcml_update_cart_contents_lang_switch', $cart->cart_contents, $key, $new_key, $current_language );
                $new_cart_data[ $new_key ] = $cart->cart_contents[ $key ];
                $new_cart_data = apply_filters( 'wcml_cart_contents', $new_cart_data, $cart->cart_contents, $key, $new_key );
            }

        }

        $cart->cart_contents = $this->wcml_check_on_duplicate_products_in_cart( $new_cart_data );

        $woocommerce->session->cart = $cart;

        return $cart;
    }

    function wcml_check_on_duplicate_products_in_cart( $cart_contents ){
        global $woocommerce;

        $exists_products = array();
        remove_action( 'woocommerce_before_calculate_totals', array( $this, 'woocommerce_calculate_totals' ) );

        foreach( $cart_contents as $key => $cart_content ){

            $cart_contents = apply_filters( 'wcml_check_on_duplicated_products_in_cart', $cart_contents, $key, $cart_content );

            if( apply_filters( 'wcml_exception_duplicate_products_in_cart', false, $cart_content ) ){
                continue;
            }

            $search_key = md5(serialize($cart_content));

            if( array_key_exists( $search_key, $exists_products ) ){
                unset( $cart_contents[ $key ] );
                $cart_contents[$exists_products[$search_key]]['quantity'] = $cart_contents[$exists_products[$search_key]]['quantity'] + $cart_content['quantity'];
                $woocommerce->cart->calculate_totals();
            }else{
                $exists_products[ $search_key ] = $key;
            }
        }

        add_action( 'woocommerce_before_calculate_totals', array( $this, 'woocommerce_calculate_totals' ) );

        return $cart_contents;

    }

    function get_cart_attribute_translation( $attr_key, $attribute, $variation_id, $current_language, $product_id, $tr_product_id ){

        $attr_translation = $attribute;

        if( !empty( $attribute ) ){
            //delete 'attribute_' at the beginning
            $taxonomy = substr( $attr_key, 10, strlen( $attr_key ) - 1 );

            if( taxonomy_exists( $taxonomy ) ){

                $term_id = $this->wcml_get_term_id_by_slug( $taxonomy, $attribute );
                $trnsl_term_id = apply_filters( 'translate_object_id',$term_id,$taxonomy,true,$current_language);
                $term = $this->wcml_get_term_by_id( $trnsl_term_id, $taxonomy );
                $attr_translation = $term->slug;
            }else{

                $trnsl_attr = get_post_meta( $variation_id, $attr_key, true );

                if( $trnsl_attr ){
                    $attr_translation = $trnsl_attr;
                }else{
                    $attr_translation = $this->get_custom_attr_translation( $product_id, $tr_product_id, $taxonomy, $attribute );
                }
            }
        }

        return $attr_translation;
    }

    //get cart_item_data from existing cart array ( from session )
    function get_cart_item_data_from_cart($cart_contents){
        unset($cart_contents['product_id']);
        unset($cart_contents['variation_id']);
        unset($cart_contents['variation']);
        unset($cart_contents['quantity']);
        unset($cart_contents['data']);

        return apply_filters( 'wcml_filter_cart_item_data', $cart_contents );
    }

    /*
    * Get custom attribute translation
    * Returned translated attribute or original if missed
    */
    function get_custom_attr_translation( $product_id, $tr_product_id, $taxonomy, $attribute ){

        $orig_product_attributes = get_post_meta($product_id, '_product_attributes', true);
        $unserialized_orig_product_attributes = maybe_unserialize($orig_product_attributes);
        foreach($unserialized_orig_product_attributes as $orig_attr_key => $orig_product_attribute){
            $orig_attr_key = urldecode($orig_attr_key);
            if( strtolower($taxonomy) == $orig_attr_key){
                $values = explode('|',$orig_product_attribute['value']);
                foreach($values as $key_id => $value){
                    if(trim($value," ") == $attribute){
                        $attr_key_id = $key_id;
                    }
                }
            }
        }

        $trnsl_product_attributes = get_post_meta($tr_product_id, '_product_attributes', true);
        $unserialized_trnsl_product_attributes = maybe_unserialize($trnsl_product_attributes);
        $taxonomy = sanitize_title($taxonomy);
        $trnsl_attr_values = explode('|',$unserialized_trnsl_product_attributes[$taxonomy]['value']);

        if(isset($attr_key_id) && isset($trnsl_attr_values[$attr_key_id])){
            return trim($trnsl_attr_values[$attr_key_id]);
        }

        return $attribute;
    }

    function wcml_coupon_loaded($coupons_data){
        global $sitepress;

        $product_ids  = array();
        $exclude_product_ids  = array();
        $product_categories_ids  = array();
        $exclude_product_categories_ids  = array();

        foreach($coupons_data->product_ids as $prod_id){
            $post_type = get_post_field('post_type', $prod_id);
            $trid = $sitepress->get_element_trid($prod_id,'post_' . $post_type);
            $translations = $sitepress->get_element_translations($trid,'post_' . $post_type);
            foreach($translations as $translation){
                $product_ids[] = $translation->element_id;
            }
        }
        foreach($coupons_data->exclude_product_ids as $prod_id){
            $post_type = get_post_field('post_type', $prod_id);
            $trid = $sitepress->get_element_trid($prod_id,'post_' . $post_type);
            $translations = $sitepress->get_element_translations($trid,'post_' . $post_type);
            foreach($translations as $translation){
                $exclude_product_ids[] = $translation->element_id;
            }
        }

        foreach($coupons_data->product_categories as $cat_id){
            $term = $this->wcml_get_term_by_id( $cat_id,'product_cat' );
            $trid = $sitepress->get_element_trid($term->term_taxonomy_id,'tax_product_cat');
            $translations = $sitepress->get_element_translations($trid,'tax_product_cat');

            foreach($translations as $translation){
                $product_categories_ids[] = $translation->term_id;
            }
        }

        foreach($coupons_data->exclude_product_categories as $cat_id){
            $term = $this->wcml_get_term_by_id( $cat_id,'product_cat' );
            $trid = $sitepress->get_element_trid($term->term_taxonomy_id,'tax_product_cat');
            $translations = $sitepress->get_element_translations($trid,'tax_product_cat');
            foreach($translations as $translation){
                $exclude_product_categories_ids[] = $translation->term_id;
            }
        }

        $coupons_data->product_ids = $product_ids;
        $coupons_data->exclude_product_ids = $exclude_product_ids;
        $coupons_data->product_categories = $product_categories_ids;
        $coupons_data->exclude_product_categories = $exclude_product_categories_ids;

        return $coupons_data;
    }


    function set_taxonomies_config( $config_all ) {
        global $woocommerce_wpml;

        $all_products_taxonomies = get_taxonomies( array( 'object_type' => array( 'product' ) ), 'objects' );

        foreach($all_products_taxonomies as $tax_key => $tax) {
            if( substr( $tax_key, 0 , 3 ) != 'pa_' || !in_array( $tax_key, array( 'product_cat', 'product_tag', 'product_shipping_class' ) ) ) continue;

            $found = false;

            foreach( $config_all['wpml-config']['taxonomies']['taxonomy'] as $key => $taxonomy ){

                if( $tax_key == $taxonomy['value'] ){
                    $config_all['wpml-config']['taxonomies']['taxonomy'][$key]['attr']['translate'] = 1;
                    $found = true;
                }

            }

            if( !$found ){
                $config_all['wpml-config']['taxonomies']['taxonomy'][] = array( 'value' => $tax_key, 'attr' => array( 'translate' => 1 ) );
            }

        }

        return $config_all;
    }


    function set_prices_config(){
        global $sitepress, $iclTranslationManagement, $sitepress_settings, $woocommerce_wpml;

        $wpml_settings = $sitepress->get_settings();

        if (!isset($wpml_settings['translation-management']) || !isset($iclTranslationManagement) || !( $iclTranslationManagement instanceof TranslationManagement) ) {
            return;
        }

        $keys = array(
            '_regular_price',
            '_sale_price',
            '_price',
            '_min_variation_regular_price',
            '_min_variation_sale_price',
            '_min_variation_price',
            '_max_variation_regular_price',
            '_max_variation_sale_price',
            '_max_variation_price',
            '_sale_price_dates_from',
            '_sale_price_dates_to',
            '_wcml_schedule'
        );

        $save = false;

        foreach ($keys as $key) {
            $iclTranslationManagement->settings['custom_fields_readonly_config'][] = $key;
            if (!isset($sitepress_settings['translation-management']['custom_fields_translation'][$key]) ||
                $wpml_settings['translation-management']['custom_fields_translation'][$key] != 1) {
                $wpml_settings['translation-management']['custom_fields_translation'][$key] = 1;
                $save = true;
            }

            if(!empty($woocommerce_wpml->multi_currency_support)){
                foreach($woocommerce_wpml->multi_currency_support->get_currency_codes() as $code){
                    $new_key = $key.'_'.$code;
                    $iclTranslationManagement->settings['custom_fields_readonly_config'][] = $new_key;
                    if (!isset($sitepress_settings['translation-management']['custom_fields_translation'][$new_key]) ||
                        $wpml_settings['translation-management']['custom_fields_translation'][$new_key] != 0) {
                        $wpml_settings['translation-management']['custom_fields_translation'][$new_key] = 0;
                        $save = true;
                    }
                }
            }

        }

        if ($save) {
            $sitepress->save_settings($wpml_settings);
        }
    }

    function woocommerce_duplicate_product($new_id, $post){
        global $sitepress,$wpdb;

        //duplicate original first
        $trid = $sitepress->get_element_trid( $post->ID, 'post_' . $post->post_type );
        $orig_id = $sitepress->get_original_element_id_by_trid( $trid );
        $orig_lang = $this->get_original_product_language( $post->ID );

        $wc_admin = new WC_Admin_Duplicate_Product();

        if( $orig_id == $post->ID ){
            $sitepress->set_element_language_details($new_id, 'post_' . $post->post_type, false, $orig_lang);
            $new_trid = $sitepress->get_element_trid( $new_id, 'post_' . $post->post_type );

            $new_orig_id = $new_id;
        }else{
            $post_to_duplicate = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE ID=%d", $orig_id ));

            if ( ! empty( $post_to_duplicate ) ) {
                $new_orig_id = $wc_admin->duplicate_product( $post_to_duplicate );

                do_action( 'wcml_after_duplicate_product' , $new_id, $post_to_duplicate );

                $sitepress->set_element_language_details( $new_orig_id, 'post_' . $post->post_type, false, $orig_lang );
                $new_trid = $sitepress->get_element_trid( $new_orig_id, 'post_' . $post->post_type );
                if( get_post_meta( $orig_id, '_icl_lang_duplicate_of' ) ){
                    update_post_meta( $new_id, '_icl_lang_duplicate_of', $new_orig_id );
                }


                $sitepress->set_element_language_details( $new_id, 'post_' . $post->post_type, $new_trid, $sitepress->get_current_language() );
            }
        }

        $translations = $sitepress->get_element_translations( $trid, 'post_' . $post->post_type );


        if($translations){

            foreach($translations as $translation){
                if( !$translation->original && $translation->element_id != $post->ID ){
                    $post_to_duplicate = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE ID=%d", $translation->element_id ) );

                    if ( ! empty( $post_to_duplicate ) ) {
                        $new_id = $wc_admin->duplicate_product( $post_to_duplicate );

                        $new_id_obj = get_post( $new_id );
                        $new_slug = wp_unique_post_slug( sanitize_title( $new_id_obj->post_title ), $new_id, $post_to_duplicate->post_status, $post_to_duplicate->post_type, $new_id_obj->post_parent );
                        $wpdb->update(
                            $wpdb->posts,
                            array(
                                'post_name' => $new_slug,
                                'post_status' => 'draft'
                            ),
                            array( 'ID' => $new_id )
                        );

                        do_action( 'wcml_after_duplicate_product' , $new_id, $post_to_duplicate );

                        $sitepress->set_element_language_details( $new_id, 'post_' . $post->post_type, $new_trid, $translation->language_code );

                        if( get_post_meta( $translation->element_id, '_icl_lang_duplicate_of' ) ){
                            update_post_meta( $new_id, '_icl_lang_duplicate_of', $new_orig_id );
                        }

                    }
                }
            }

        }

    }

    function woocommerce_product_quick_edit_save($product){
        global $sitepress;
        $is_original = $this->is_original_product($product->id);

        $trid = $sitepress->get_element_trid($product->id, 'post_product');
        if ($trid) {
            $translations = $sitepress->get_element_translations($trid, 'post_product');
            if($translations){
                foreach($translations as $translation){
                    if($is_original){
                        if(!$translation->original){
                            $this->sync_product_data($product->id,$translation->element_id,$translation->language_code);
                        }
                    }elseif($translation->original){
                        $this->sync_product_data($translation->element_id,$product->id,$sitepress->get_language_for_element($product->id,'post_product'));
                    }
                }
            }
        }

    }

    function override_cached_widget_id($widget_id){

        if (defined('ICL_LANGUAGE_CODE')){
            $widget_id .= ':' . ICL_LANGUAGE_CODE;
        }

        return $widget_id;
    }


    //update menu_order fro translations after ordering original products
    function update_all_products_translations_ordering(){
        global $wpdb, $sitepress, $woocommerce_wpml;

        if( $woocommerce_wpml->settings['products_sync_order'] ) {

            $current_language = $sitepress->get_current_language();

            if ($current_language == $sitepress->get_default_language()) {
                $products = $wpdb->get_results($wpdb->prepare("SELECT p.ID FROM $wpdb->posts AS p LEFT JOIN {$wpdb->prefix}icl_translations AS icl ON icl.element_id = p.id WHERE p.post_type = 'product' AND p.post_status IN ( 'publish', 'future', 'draft', 'pending', 'private' ) AND icl.element_type= 'post_product' AND icl.language_code = %s", $current_language));

                foreach ($products as $product) {
                    $this->update_order_for_product_translations( $product->ID );
                }
            }
        }
    }

    //update menu_order fro translations after ordering original products
    function update_order_for_product_translations( $product_id ){
        global $wpdb, $sitepress, $woocommerce_wpml;

        if( isset($woocommerce_wpml->settings['products_sync_order']) && $woocommerce_wpml->settings['products_sync_order'] ) {

            $current_language = $sitepress->get_current_language();

            if ( $current_language == $sitepress->get_default_language() ) {
                $menu_order = $wpdb->get_var($wpdb->prepare("SELECT menu_order FROM $wpdb->posts WHERE ID = %d", $product_id ) );

                $trid = $sitepress->get_element_trid($product_id, 'post_product');
                $translations = $sitepress->get_element_translations($trid, 'post_product');

                foreach ($translations as $translation) {

                    if ($translation->element_id != $product_id) {
                        $wpdb->update( $wpdb->posts, array('menu_order' => $menu_order), array('ID' => $translation->element_id));
                    }
                }
            }

        }
    }


    function filter_excerpt_field_content_copy( $elements ) {

        if ( $elements[ 'post_type' ] == 'product' ) {
            $elements[ 'excerpt' ] ['editor_type'] = 'editor';
        }

        if ( function_exists( 'format_for_editor' ) ) {
            // WordPress 4.3 uses format_for_editor
            $elements[ 'excerpt' ] ['value'] = htmlspecialchars_decode(format_for_editor($elements[ 'excerpt' ] ['value'], $_POST[ 'excerpt_type']));
        } else {
            // Backwards compatible for WordPress < 4.3
            if($_POST[ 'excerpt_type'] == 'rich'){
                $elements[ 'excerpt' ] ['value'] = htmlspecialchars_decode(wp_richedit_pre($elements[ 'excerpt' ] ['value']));
            }else{
                $elements[ 'excerpt' ] ['value'] = htmlspecialchars_decode(wp_htmledit_pre($elements[ 'excerpt' ] ['value']));
            }
        }

        return $elements;
    }


    function product_data_html(){
        $nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if(!$nonce || !wp_verify_nonce($nonce, 'wcml_product_data')){
            echo json_encode(array('error' => __('Invalid nonce', 'woocommerce-multilingual')));
            die();
        }
        global $woocommerce_wpml,$sitepress,$wpdb,$iclTranslationManagement;

        $product_id = filter_input( INPUT_POST, 'product_id', FILTER_SANITIZE_NUMBER_INT );
        $job_id = filter_input( INPUT_POST, 'job_id', FILTER_SANITIZE_NUMBER_INT );
        $job = $iclTranslationManagement->get_translation_job ( $job_id );

        $product = get_post( $product_id );
        $default_language = $sitepress->get_language_for_element($product_id,'post_product');
        $active_languages = $sitepress->get_active_languages();

        if(ob_get_length()){
            ob_clean();
        }
        ob_start();
        $return = array();

        include WCML_PLUGIN_PATH . '/menu/sub/product-data.php';

        $return['html'] =  ob_get_clean();

        echo json_encode($return);

        die();
    }

    // Check if user can translate product
    function user_can_translate_product( $trid, $language_code ){
        global $wpdb, $iclTranslationManagement,$sitepress;

        $current_translator = $iclTranslationManagement->get_current_translator();
        $job_id = $wpdb->get_var($wpdb->prepare("
			SELECT tj.job_id FROM {$wpdb->prefix}icl_translate_job tj
				JOIN {$wpdb->prefix}icl_translation_status ts ON tj.rid = ts.rid
				JOIN {$wpdb->prefix}icl_translations t ON ts.translation_id = t.translation_id
				WHERE t.trid = %d AND t.language_code='%s'
				ORDER BY tj.job_id DESC LIMIT 1
		", $trid, $language_code));

        if( $job_id && wpml_check_user_is_translator( $sitepress->get_source_language_by_trid($trid), $language_code)){
            return true;
        }

        return false;
    }


    // Check if original product
    function is_original_product( $product_id ){

        $cache_key =  $product_id;
        $cache_group = 'is_original_product';

        $temp_is_original = wp_cache_get($cache_key, $cache_group);
        if($temp_is_original) return $temp_is_original;


        global $wpdb;

        $is_original = $wpdb->get_var( $wpdb->prepare( "SELECT source_language_code IS NULL FROM {$wpdb->prefix}icl_translations WHERE element_id=%d AND element_type='post_product'", $product_id ) );

        wp_cache_set( $cache_key, $is_original, $cache_group );

        return $is_original;
    }

    // Get original product language
    function get_original_product_language( $product_id ){

        $cache_key = $product_id;
        $cache_group = 'original_product_language';

        $temp_language = wp_cache_get( $cache_key, $cache_group );
        if($temp_language) return $temp_language;

        global $wpdb;

        $language = $wpdb->get_var( $wpdb->prepare( "
                            SELECT t2.language_code FROM {$wpdb->prefix}icl_translations as t1
                            LEFT JOIN {$wpdb->prefix}icl_translations as t2 ON t1.trid = t2.trid
                            WHERE t1.element_id=%d AND t1.element_type=%s AND t2.source_language_code IS NULL", $product_id, 'post_'.get_post_type($product_id) ) );

        wp_cache_set( $cache_key, $language, $cache_group );

        return $language;
    }

    function add_languages_column( $columns ){

        if ( ( version_compare(ICL_SITEPRESS_VERSION, '3.2', '<') && version_compare( WOOCOMMERCE_VERSION, '2.3', '<' ) ) || array_key_exists( 'icl_translations',$columns ) ){
            return $columns;
        }

        global $sitepress,$wpdb;

        $active_languages = $sitepress->get_active_languages();
        if ( count( $active_languages ) <= 1 || get_query_var( 'post_status' ) == 'trash') {
            return $columns;
        }

        $languages = array();
        foreach ( $active_languages as $v ) {
            if ( $v[ 'code' ] == $sitepress->get_current_language() )
                continue;
            $languages[ ] = $v[ 'code' ];
        }

        $res = $wpdb->get_results( $wpdb->prepare ( "
			SELECT f.lang_code, f.flag, f.from_template, l.name
			FROM {$wpdb->prefix}icl_flags f
				JOIN {$wpdb->prefix}icl_languages_translations l ON f.lang_code = l.language_code
			WHERE l.display_language_code = %s AND f.lang_code IN (%s)
		", $sitepress->get_admin_language(), join( "','", $languages ) ) );

        foreach ( $res as $r ) {
            if ( $r->from_template ) {
                $wp_upload_dir = wp_upload_dir();
                $flag_path         = $wp_upload_dir[ 'baseurl' ] . '/flags/';
            } else {
                $flag_path = ICL_PLUGIN_URL . '/res/flags/';
            }
            $flags[ $r->lang_code ] = '<img src="' . $flag_path . $r->flag . '" width="18" height="12" alt="' . $r->name . '" title="' . $r->name . '" />';
        }

        $flags_column = '';
        foreach ( $active_languages as $v ) {
            if ( isset( $flags[ $v[ 'code' ] ] ) )
                $flags_column .= $flags[ $v[ 'code' ] ];
        }

        $new_columns = array();
        $added = false;
        foreach ( $columns as $k => $v ) {
            $new_columns[ $k ] = $v;
            if ( $k == 'name' ) {
                $new_columns[ 'icl_translations' ] = $flags_column;
                $added = true;
            }
        }

        if(!$added){
            $new_columns[ 'icl_translations' ] = $flags_column;
        }

        return $new_columns;

    }

    // display products with custom prices only if enabled "Show only products with custom prices in secondary currencies" option on settings page
    function filter_products_with_custom_prices( $filtered_posts ) {
        global $wpdb,$woocommerce_wpml;

        if( $woocommerce_wpml->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT &&
            isset($woocommerce_wpml->settings['display_custom_prices']) &&
            $woocommerce_wpml->settings['display_custom_prices'] ){

            $client_currency = $woocommerce_wpml->multi_currency_support->get_client_currency();
            $woocommerce_currency = get_option('woocommerce_currency');

            if( $client_currency == $woocommerce_currency ){
                return $filtered_posts;
            }

            $matched_products = array();

            $matched_products_query = $wpdb->get_results( "
	        	SELECT DISTINCT ID, post_parent, post_type FROM $wpdb->posts
				INNER JOIN $wpdb->postmeta ON ID = post_id
				WHERE post_type IN ( 'product', 'product_variation' ) AND post_status = 'publish' AND meta_key = '_wcml_custom_prices_status' AND meta_value = 1
			", OBJECT_K );

            if ( $matched_products_query ) {

                remove_filter('get_post_metadata', array($woocommerce_wpml->multi_currency_support, 'product_price_filter'), 10, 4);

                foreach ( $matched_products_query as $product ) {
                    if( !get_post_meta( $product->ID,'_price_'.$client_currency, true ) ) continue;
                    if ( $product->post_type == 'product' )
                        $matched_products[] = apply_filters( 'translate_object_id', $product->ID, 'product', true );
                    if ( $product->post_parent > 0 && ! in_array( $product->post_parent, $matched_products ) )
                        $matched_products[] = apply_filters( 'translate_object_id', $product->post_parent, get_post_type($product->post_parent), true );
                }

                add_filter('get_post_metadata', array($woocommerce_wpml->multi_currency_support, 'product_price_filter'), 10, 4);
            }

            // Filter the id's
            if ( sizeof( $filtered_posts ) == 0) {
                $filtered_posts = $matched_products;
                $filtered_posts[] = 0;
            } else {
                $filtered_posts = array_intersect( $filtered_posts, $matched_products );
                $filtered_posts[] = 0;
            }
        }

        return $filtered_posts;
    }

    function filter_related_products_args( $args ){
        global $woocommerce_wpml;

        if( $woocommerce_wpml->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT &&
            isset($woocommerce_wpml->settings['display_custom_prices']) &&
            $woocommerce_wpml->settings['display_custom_prices'] ){

            $client_currency = $woocommerce_wpml->multi_currency_support->get_client_currency();
            $woocommerce_currency = get_option('woocommerce_currency');

            if( $client_currency != $woocommerce_currency ){
                $args['meta_query'][] =  array(
                    'key'     => '_wcml_custom_prices_status',
                    'value'   => 1,
                    'compare' => '=',
                );
            }

        }

        return $args;
    }

    function filter_product_attributes_for_translation( $translated, $key ){

        $translated = $translated
            ? preg_match('#^(?!field-_product_attributes-(.+)-(.+)-(?!value|name))#', $key) : 0;

        return $translated;

    }

    /*
     * Use custom query, because get_term_by function return false for terms with "0" slug      *
     */
    function wcml_get_term_id_by_slug( $taxonomy, $slug ){
        global $wpdb;
        return $wpdb->get_var( $wpdb->prepare( "SELECT tt.term_id FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy = %s AND t.slug = %s LIMIT 1", $taxonomy, $slug ) );
    }

    function wcml_get_term_by_id( $term_id, $taxonomy ){
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("
                            SELECT * FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} x ON x.term_id = t.term_id WHERE t.term_id = %d AND x.taxonomy = %s", $term_id, $taxonomy ) );
    }

    function lock_variable_fields( $loop, $variation_data, $variation ){

        $product_id = false;
        if( ( isset( $_GET['post'] ) && get_post_type( $_GET['post'] ) == 'product' ) ){
            $product_id = $_GET['post'];
        }elseif( isset( $_POST['action'] ) && $_POST['action'] == 'woocommerce_load_variations' && isset( $_POST['product_id'] ) ){
            $product_id = $_POST['product_id'];
        }

        if( !$product_id ){
            return;
        }elseif( !$this->is_original_product( $product_id ) ){ ?>
            <script type="text/javascript">
                jQuery(document).ready(function() {
                    wcml_lock_variation_fields();
                });
            </script>
            <?php
        }

    }

    function sync_feature_product_meta(){

        if ( current_user_can( 'edit_products' ) && check_admin_referer( 'woocommerce-feature-product' ) ) {
            $product_id = absint( $_GET['product_id'] );

            if ( 'product' === get_post_type( $product_id ) && $this->is_original_product( $product_id ) ) {
                global $sitepress;

                $value = get_post_meta( $product_id, '_featured', true ) === 'yes' ? 'no' : 'yes';

                $trid = $sitepress->get_element_trid( $product_id, 'post_product' );
                $translations = $sitepress->get_element_translations( $trid, 'post_product', true );
                foreach( $translations as $translation ){

                    if ( !$translation->original ) {

                        update_post_meta( $translation->element_id, '_featured', $value );
                    }
                }

            }
        }

    }

    function set_schedule_for_translations( $deprecated, $post ){
        global $sitepress;

        if( $this->is_original_product( $post->ID ) ) {

            $trid = $sitepress->get_element_trid( $post->ID, 'post_product');
            $translations = $sitepress->get_element_translations( $trid, 'post_product', true);
            foreach ($translations as $translation) {

                if (!$translation->original) {
                    wp_clear_scheduled_hook('publish_future_post', array($translation->element_id));
                    wp_schedule_single_event(strtotime(get_gmt_from_date($post->post_date) . ' GMT'), 'publish_future_post', array($translation->element_id));
                }
            }
        }
    }

    function sync_stock_status_for_translations( $id, $status ){
        global $sitepress;

        $type = get_post_type( $id );
        $trid = $sitepress->get_element_trid( $id, 'post_'.$type );
        $translations = $sitepress->get_element_translations( $trid, 'post_'.$type, true);
        foreach ($translations as $translation) {
            if ( $translation->element_id != $id ) {
                update_post_meta( $translation->element_id, '_stock_status', $status );
            }
        }

    }

}