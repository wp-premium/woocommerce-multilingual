<?php
  
class WCML_Terms{
    
    const ALL_TAXONOMY_TERMS_TRANSLATED = 0;
    const NEW_TAXONOMY_TERMS = 1;
    const NEW_TAXONOMY_IGNORED = 2;
    
    private $_tmp_locale_val = false;

    function __construct(){
        
        add_action('init', array($this, 'init'));
    }
    
    function init(){
        global $sitepress;
        
        add_action('updated_woocommerce_term_meta',array($this,'sync_term_order'), 100,4);

        add_filter('wp_get_object_terms', array($sitepress, 'get_terms_filter'));
        
        add_action('icl_save_term_translation', array($this,'save_wc_term_meta'), 100,4);
        
        add_action('created_term', array($this, 'translated_terms_status_update'), 10,3);
        add_action('edit_term', array($this, 'translated_terms_status_update'), 10,3);
        add_action('wp_ajax_wcml_update_term_translated_warnings', array('WCML_Terms', 'wcml_update_term_translated_warnings'));
        add_action('wp_ajax_wcml_ingore_taxonomy_translation', array('WCML_Terms', 'wcml_ingore_taxonomy_translation'));
        add_action('wp_ajax_wcml_uningore_taxonomy_translation', array('WCML_Terms', 'wcml_uningore_taxonomy_translation'));

        add_action('created_term', array('WCML_Terms', 'set_flag_for_variation_on_attribute_update'), 10, 3);

        if ( defined( 'ICL_SITEPRESS_VERSION' ) && version_compare( ICL_SITEPRESS_VERSION, '3.1.8.2', '<=' ) ) {
            // Backward compatibillity for WPML <= 3.1.8.2
            add_action('wpml_taxonomy_translation_bottom', array('WCML_Terms', 'show_variations_sync_button'), 10, 1);
            add_filter('wpml_taxonomy_show_tax_sync_button', array('WCML_Terms', 'hide_tax_sync_button_for_attributes'));
        }else{
            add_filter('wpml_taxonomy_translation_bottom', array('WCML_Terms', 'sync_taxonomy_translations'), 10, 3 );
        }

        add_action('wp_ajax_wcml_sync_product_variations', array('WCML_Terms', 'wcml_sync_product_variations'));
        add_action('wp_ajax_wcml_tt_sync_taxonomies_in_content', array('WCML_Terms', 'wcml_sync_taxonomies_in_content'));
        add_action('wp_ajax_wcml_tt_sync_taxonomies_in_content_preview', array('WCML_Terms', 'wcml_sync_taxonomies_in_content_preview'));
        
        if(is_admin()){
            add_action('admin_menu', array($this, 'admin_menu_setup'));    
        }
        
        add_action('delete_term',array($this, 'wcml_delete_term'),10,4);
        add_filter('get_the_terms',array($this,'shipping_terms'),10,3);
        //filter coupons terms in admin
        add_filter('get_terms',array($this,'filter_coupons_terms'),10,3);
        add_filter('get_terms',array($this,'filter_shipping_classes_terms'),10,3);


        add_filter( 'woocommerce_get_product_terms', array( $this, 'get_product_terms_filter' ), 10, 4 );

        add_filter( 'pre_update_option_woocommerce_flat_rate_settings', array( $this, 'update_woocommerce_shipping_settings_for_class_costs' ) );
        add_filter( 'pre_update_option_woocommerce_international_delivery_settings', array( $this, 'update_woocommerce_shipping_settings_for_class_costs' ) );

        add_action('wp_ajax_woocommerce_shipping_zone_methods_save_settings', array( $this, 'update_woocommerce_shipping_settings_for_class_costs_from_ajax'), 9);
    }
    
    function admin_menu_setup(){
        global $pagenow;
        if($pagenow == 'edit-tags.php' && isset($_GET['action']) && $_GET['action'] == 'edit'){
            add_action('admin_notices', array($this, 'show_term_translation_screen_notices'));    
        }
        
    }

    function save_wc_term_meta($original_tax, $result){
        global $wpdb;

        // WooCommerce before termmeta table migration
        $wc_before_term_meta = get_option( 'db_version' ) < 34370;

        // backwards compatibility - before the termmeta table was added
        if( $wc_before_term_meta ){

            $term_wc_meta = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->woocommerce_termmeta} WHERE woocommerce_term_id=%s", $original_tax->term_id));
            foreach ( $term_wc_meta as $wc_meta ){
                $wc_original_metakey = $wc_meta->meta_key;
                $wc_original_metavalue = $wc_meta->meta_value;
                update_woocommerce_term_meta($result['term_id'], $wc_original_metakey, $wc_original_metavalue);
            }
            // End of backwards compatibility - before the termmeta table was added
        }else{

            $term_wc_meta = get_term_meta($original_tax->term_id, false, 1);
            foreach ( $term_wc_meta as $key => $values ) {
                update_term_meta( $result['term_id'], $key, array_pop( $values ) );
            }

        }

        //update flat rate options for shipping classes
        if( $original_tax->taxonomy == 'product_shipping_class' ){

            $settings = get_option( 'woocommerce_flat_rate_settings' );
            if( is_array( $settings ) ){
                update_option( 'woocommerce_flat_rate_settings', $this->update_woocommerce_shipping_settings_for_class_costs( $settings ) );
            }

            $settings = get_option( 'woocommerce_international_delivery_settings' );
            if( is_array( $settings ) ){
                update_option( 'woocommerce_international_delivery_settings', $this->update_woocommerce_shipping_settings_for_class_costs( $settings ) );
            }

        }
    }

    function _switch_wc_locale(){
        global $sitepress;
        $locale = !empty($this->_tmp_locale_val) ? $this->_tmp_locale_val : $sitepress->get_locale($sitepress->get_current_language());
        return $locale;
    }

    function show_term_translation_screen_notices(){
        global $sitepress, $wpdb;
        $taxonomies = array_keys(get_taxonomies(array('object_type'=>array('product')),'objects'));
        $taxonomies = $taxonomies + array_keys(get_taxonomies(array('object_type'=>array('product_variations')),'objects'));
        $taxonomies = array_unique($taxonomies);
        $taxonomy = isset($_GET['taxonomy']) ? $_GET['taxonomy'] : false;
        if($taxonomy && in_array($taxonomy, $taxonomies)){
            $taxonomy_obj = get_taxonomy($taxonomy);
            $language = isset($_GET['lang']) ? $_GET['lang'] : false;
            if(empty($language) && isset($_GET['tag_ID'])){
                $tax_id = $wpdb->get_var($wpdb->prepare("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id=%d AND taxonomy=%s", $_GET['tag_ID'], $taxonomy));                
                $language = $sitepress->get_language_for_element($tax_id, 'tax_' . $taxonomy);
            }
            if(empty($language)){
                $language = $sitepress->get_default_language();
            }

            $message = sprintf(__('To translate %s please use the %s translation%s page, inside the %sWooCommerce Multilingual admin%s.', 'woocommerce-multilingual'),
            $taxonomy_obj->labels->name,
            '<strong><a href="' . admin_url('admin.php?page=wpml-wcml&tab=' . $taxonomy ) . '">' . $taxonomy_obj->labels->singular_name,  '</a></strong>',
                '<strong><a href="' . admin_url('admin.php?page=wpml-wcml">'), '</a></strong>');

            echo '<div class="updated"><p>' . $message . '</p></div>';
            
        }
        
    } 
    
    function sync_term_order_globally() {
        //syncs the term order of any taxonomy in $wpdb->prefix.'woocommerce_attribute_taxonomies'
        //use it when term orderings have become unsynched, e.g. before WCML 3.3.
        global $sitepress, $wpdb, $woocommerce_wpml;

        if(!defined('WOOCOMMERCE_VERSION')){
            return;
        }

        $cur_lang = $sitepress->get_current_language();
        $lang = $sitepress->get_default_language();
        $sitepress->switch_lang($lang);

        $taxes = wc_get_attribute_taxonomies ();

        if ($taxes) foreach ($taxes as $woo_tax) {
            $tax = 'pa_'.$woo_tax->attribute_name;
            $meta_key = 'order_'.$tax;
            //if ($tax != 'pa_frame') continue;
            $terms = get_terms($tax);
            if ($terms)foreach ($terms as $term) {
                $term_order = get_woocommerce_term_meta($term->term_id,$meta_key);
                $trid = $sitepress->get_element_trid($term->term_taxonomy_id,'tax_'.$tax);
                $translations = $sitepress->get_element_translations($trid,'tax_' . $tax);
                if ($translations) foreach ($translations as $trans) {
                    if ($trans->language_code != $lang) {
                        update_woocommerce_term_meta( $trans->term_id, $meta_key, $term_order);
                    }
                }
            }
        }
        
        //sync product categories ordering
        $terms = get_terms('product_cat');
        if ($terms) foreach($terms as $term) {
            $term_order = get_woocommerce_term_meta($term->term_id,'order');
            $trid = $sitepress->get_element_trid($term->term_taxonomy_id,'tax_product_cat');
            $translations = $sitepress->get_element_translations($trid,'tax_product_cat');
            if ($translations) foreach ($translations as $trans) {
                if ($trans->language_code != $lang) {
                    update_woocommerce_term_meta( $trans->term_id, 'order', $term_order);
                }
            }
        }

        $sitepress->switch_lang($cur_lang);
        
        $woocommerce_wpml->settings['is_term_order_synced'] = 'yes';
        $woocommerce_wpml->update_settings();
        
    }

    function sync_term_order($meta_id, $object_id, $meta_key, $meta_value) {
        global $wpdb, $sitepress;

        // WooCommerce before termmeta table migration
        $wc_before_term_meta = get_option( 'db_version' ) < 34370;

        if (!isset($_POST['thetaxonomy']) || !taxonomy_exists($_POST['thetaxonomy']) || substr($meta_key,0,5) != 'order')
            return;

        $tax = filter_input( INPUT_POST, 'thetaxonomy', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

        $term_taxonomy_id = $wpdb->get_var($wpdb->prepare("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id=%d AND taxonomy=%s", $object_id, $tax));
        $trid = $sitepress->get_element_trid($term_taxonomy_id, 'tax_' . $tax);
        $translations = $sitepress->get_element_translations($trid,'tax_' . $tax);
        if ($translations) foreach ($translations as $trans) {
            if ($trans->element_id != $term_taxonomy_id) {

                // Backwards compatibility - WooCommerce termmeta table
                if( $wc_before_term_meta ) {
                    $wpdb->update( $this->wpdb->prefix . 'woocommerce_termmeta',
                        array('meta_value' => $meta_value),
                        array('woocommerce_term_id' => $trans->term_id, 'meta_key' => $meta_key) );
                    // END Backwards compatibility - WooCommerce termmeta table
                } else{
                    update_term_meta( $trans->term_id, $meta_key, $meta_value);
                }

            }
        }

    }

    function translated_terms_status_update($term_id, $tt_id, $taxonomy){

        if ( isset( $_POST['product_cat_thumbnail_id'] ) ){
            global $sitepress,$sitepress_settings;

            if($sitepress_settings['sync_taxonomy_parents'] && $this->is_original_category($tt_id,'tax_'.$taxonomy) ){
                $trid = $sitepress->get_element_trid($tt_id,'tax_'.$taxonomy);
                $translations = $sitepress->get_element_translations($trid,'tax_'.$taxonomy);
                
                foreach($translations as $translation){
                    if(!$translation->original){
                        if(isset($_POST['display_type'])){
                        update_woocommerce_term_meta( $translation->term_id, 'display_type', esc_attr( $_POST['display_type'] ) );
                        }
                        update_woocommerce_term_meta( $translation->term_id, 'thumbnail_id', apply_filters( 'translate_object_id',esc_attr( $_POST['product_cat_thumbnail_id'] ),'attachment',true,$translation->language_code));
                    }
                }
            }
        }

        global $wp_taxonomies;
        if(in_array('product', $wp_taxonomies[$taxonomy]->object_type) || in_array('product_variation', $wp_taxonomies[$taxonomy]->object_type)){
            self::update_terms_translated_status($taxonomy);    
        }

    }

    function is_original_category( $tt_id, $taxonomy ){
        global $wpdb;
        $is_original = $wpdb->get_var($wpdb->prepare("SELECT source_language_code IS NULL FROM {$wpdb->prefix}icl_translations WHERE element_id=%d AND element_type=%s", $tt_id, $taxonomy ));
    }
    
    static function wcml_update_term_translated_warnings(){

        $nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if(!$nonce || !wp_verify_nonce($nonce, 'wcml_update_term_translated_warnings_nonce')){
            die('Invalid nonce');
        }

        global $woocommerce_wpml;
        
        $ret = array();

        $taxonomy = filter_input( INPUT_POST, 'taxonomy', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

        $wcml_settings = $woocommerce_wpml->get_settings();

        if($wcml_settings['untranstaled_terms'][$taxonomy]['status'] == self::ALL_TAXONOMY_TERMS_TRANSLATED ||
            $wcml_settings['untranstaled_terms'][$taxonomy]['status'] == self::NEW_TAXONOMY_IGNORED){

            $ret['hide'] = 1;

        }else{
            $ret['hide'] = 0;
        }

        
        echo json_encode($ret);
        exit;
        
    }
    
    static function wcml_ingore_taxonomy_translation(){
        $nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if(!$nonce || !wp_verify_nonce($nonce, 'wcml_ingore_taxonomy_translation_nonce')){
            die('Invalid nonce');
        }

        global $woocommerce_wpml;
        
        $ret = array();
        
        $taxonomy = filter_input( INPUT_POST, 'taxonomy', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

        $wcml_settings = $woocommerce_wpml->get_settings();
        $wcml_settings['untranstaled_terms'][$taxonomy]['status'] = self::NEW_TAXONOMY_IGNORED;

        $woocommerce_wpml->update_settings($wcml_settings);

        $ret['html']  = '<i class="icon-ok"></i> ';
        $ret['html'] .= sprintf(__('%s do not require translation.', 'woocommerce-multilingual'), get_taxonomy($taxonomy)->labels->name);
        $ret['html'] .= '<div class="actions">';
        $ret['html'] .= '<a href="#unignore-' . $taxonomy . '">' . __('Change', 'woocommerce-multilingual') . '</a>';
        $ret['html'] .= '</div>';

        echo json_encode($ret);
        exit;
    }
    
    static function wcml_uningore_taxonomy_translation(){
        $nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if(!$nonce || !wp_verify_nonce($nonce, 'wcml_ingore_taxonomy_translation_nonce')){
            die('Invalid nonce');
        }

        global $woocommerce_wpml;
        
        $ret = array();
        
        $taxonomy = filter_input( INPUT_POST, 'taxonomy', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

        $wcml_settings = $woocommerce_wpml->get_settings();

        if($wcml_settings['untranstaled_terms'][$taxonomy]['count'] > 0){
            $wcml_settings['untranstaled_terms'][$taxonomy]['status'] = self::NEW_TAXONOMY_TERMS;

            $ret['html']  = '<i class="icon-warning-sign"></i> ';
            $ret['html'] .= sprintf(__('Some %s are missing translations (%d translations missing).', 'woocommerce-multilingual'), get_taxonomy($taxonomy)->labels->name, $wcml_settings['untranstaled_terms'][$taxonomy]['count']);
            $ret['html'] .= '<div class="actions">';
            $ret['html'] .= '<a href="' . admin_url('admin.php?page=wpml-wcml&tab=' . $taxonomy) . '">' . __('Translate now', 'woocommerce-multilingual') . '</a> | ';
            $ret['html'] .= '<a href="#ignore-' . $taxonomy . '">' . __('Change', 'woocommerce-multilingual') . '</a>';
            $ret['html'] .= '</div>';

            $ret['warn'] = 1;

        }else{
            $wcml_settings['untranstaled_terms'][$taxonomy]['status'] = self::ALL_TAXONOMY_TERMS_TRANSLATED;

            $ret['html']  = '<i class="icon-ok"></i> ';
            $ret['html'] .= sprintf(__('All %s are translated.', 'woocommerce-multilingual'), get_taxonomy($taxonomy)->labels->name);

            $ret['warn'] = 0;
        }

        $woocommerce_wpml->update_settings($wcml_settings);


        echo json_encode($ret);
        exit;
    }    
    
    static function update_terms_translated_status($taxonomy){
        global $woocommerce_wpml, $sitepress, $wpdb;
        
        $wcml_settings = $woocommerce_wpml->get_settings();

        $active_languages = $sitepress->get_active_languages();

        $not_translated_count = 0;
        foreach($active_languages as $language){

                $terms = $wpdb->get_results($wpdb->prepare("
                        SELECT t1.element_id AS e1, t2.element_id AS e2 FROM {$wpdb->term_taxonomy} x
                        JOIN {$wpdb->prefix}icl_translations t1 ON x.term_taxonomy_id = t1.element_id AND t1.element_type = %s AND t1.source_language_code IS NULL
                        LEFT JOIN {$wpdb->prefix}icl_translations t2 ON t2.trid = t1.trid AND t2.language_code = %s
                    ", 'tax_' . $taxonomy, $language['code']));
                foreach($terms as $term){
                    if( empty( $term->e2 ) ){
                        $not_translated_count ++;
                    }

                }
            }
        
        $status = $not_translated_count ? self::NEW_TAXONOMY_TERMS : self::ALL_TAXONOMY_TERMS_TRANSLATED;    
        
        if(isset($wcml_settings['untranstaled_terms'][$taxonomy]) && $wcml_settings['untranstaled_terms'][$taxonomy] === self::NEW_TAXONOMY_IGNORED){
            $status = self::NEW_TAXONOMY_IGNORED; 
        }
        
        $wcml_settings['untranstaled_terms'][$taxonomy] = array('count' => $not_translated_count , 'status' => $status);
                
        $woocommerce_wpml->update_settings($wcml_settings);               
        
        return $wcml_settings['untranstaled_terms'][$taxonomy];        
        
    }
    
    static function is_fully_translated($taxonomy){
        global $woocommerce_wpml;
        
        $wcml_settings = $woocommerce_wpml->get_settings();
        
        $return = true;
        
        if(!isset($wcml_settings['untranstaled_terms'][$taxonomy])){
            $wcml_settings['untranstaled_terms'][$taxonomy] = self::update_terms_translated_status($taxonomy);
        }

        if($wcml_settings['untranstaled_terms'][$taxonomy]['status'] == self::NEW_TAXONOMY_TERMS){
            $return = false;
        }
        
        
        return $return;
    }
    
    static function get_untranslated_terms_number($taxonomy){
        global $woocommerce_wpml;
        
        $wcml_settings = $woocommerce_wpml->get_settings();
        
        if(!isset($wcml_settings['untranstaled_terms'][$taxonomy])){
            $wcml_settings['untranstaled_terms'][$taxonomy] = self::update_terms_translated_status($taxonomy);
        }
        
        return $wcml_settings['untranstaled_terms'][$taxonomy]['count'];
        
    }
    
    static function set_flag_for_variation_on_attribute_update($term_id, $tt_id, $taxonomy){    
        global $woocommerce_wpml, $sitepress;
        
        $attribute_taxonomies = wc_get_attribute_taxonomies();        
        foreach($attribute_taxonomies as $a){
            $attribute_taxonomies_arr[] = 'pa_' . $a->attribute_name;
        }

        if(isset( $attribute_taxonomies_arr ) && in_array($taxonomy, $attribute_taxonomies_arr)){

				$wcml_settings = $woocommerce_wpml->get_settings();

				// get term language
				$term_language = $sitepress->get_element_language_details($tt_id, 'tax_' . $taxonomy);

				if($term_language->language_code != $sitepress->get_default_language()){
					// get term in the default language
					$term_id = apply_filters( 'translate_object_id',$term_id, $taxonomy, false, $sitepress->get_default_language());

					//does it belong to any posts (variations)
					$objects = get_objects_in_term($term_id, $taxonomy);

					if(!isset($wcml_settings['variations_needed'][$taxonomy])){
						$wcml_settings['variations_needed'][$taxonomy] = 0;
					}
					$wcml_settings['variations_needed'][$taxonomy] += count($objects);

					$woocommerce_wpml->update_settings($wcml_settings);

				}
		}
        
    }

    static function sync_taxonomy_translations( $html, $taxonomy, $taxonomy_obj ){
        global $woocommerce_wpml;
        
        if(is_admin() && isset($_GET['page']) && $_GET['page'] == 'wpml-wcml' && isset($_GET['tab'])){
            $wcml_settings = $woocommerce_wpml->get_settings();
            $attribute_taxonomies = wc_get_attribute_taxonomies();        
            foreach($attribute_taxonomies as $a){
                $attribute_taxonomies_arr[] = 'pa_' . $a->attribute_name;
            }

            ob_start();
            include WCML_PLUGIN_PATH . '/menu/sub/sync-taxonomy-translations.php';
            $html = ob_get_contents();
            ob_end_clean();

        }

        return $html;
    }

    // Backward compatibillity for WPML <= 3.1.8.2
    static function show_variations_sync_button($taxonomy){
        global $woocommerce_wpml;

        if(is_admin() && isset($_GET['page']) && $_GET['page'] == 'wpml-wcml' && isset($_GET['tab'])){
            $wcml_settings = $woocommerce_wpml->get_settings();
            $attribute_taxonomies = wc_get_attribute_taxonomies();
            foreach($attribute_taxonomies as $a){
                $attribute_taxonomies_arr[] = 'pa_' . $a->attribute_name;
            }

            if(isset( $attribute_taxonomies_arr ) && in_array($taxonomy, $attribute_taxonomies_arr)){

                ?>

                <form id="wcml_tt_sync_variations" method="post">
                    <input type="hidden" name="action" value="wcml_sync_product_variations" />
                    <input type="hidden" name="taxonomy" value="<?php echo $taxonomy ?>" />
                    <input type="hidden" name="wcml_nonce" value="<?php echo wp_create_nonce('wcml_sync_product_variations') ?>" />
                    <input type="hidden" name="last_post_id" value="" />
                    <input type="hidden" name="languages_processed" value="0" />

                    <p>
                        <input class="button-secondary" type="submit" value="<?php esc_attr_e("Synchronize attributes and update product variations", 'woocommerce-multilingual') ?>" />
                        <img src="<?php echo ICL_PLUGIN_URL . '/res/img/ajax-loader.gif' ?>" alt="loading" height="16" width="16" class="wpml_tt_spinner" />
                    </p>
                    <span class="errors icl_error_text"></span>
                    <div class="wcml_tt_sycn_preview"></div>
                </form>


                <p><?php _e('This will automatically generate variations for translated products corresponding to recently translated attributes.'); ?></p>
                <?php if(!empty($wcml_settings['variations_needed'][$taxonomy])): ?>
                    <p><?php printf(__('Currently, there are %s variations that need to be created.', 'woocommerce-multilingual'), '<strong>' . $wcml_settings['variations_needed'][$taxonomy] . '</strong>') ?></p>
                <?php endif; ?>


            <?php

            }

        }

    }
    
    // Backward compatibillity for WPML <= 3.1.8.2
    static function hide_tax_sync_button_for_attributes($value){
        global $woocommerce_wpml;

        if(is_admin() && isset($_GET['page']) && $_GET['page'] == 'wpml-wcml' && isset($_GET['tab'])){

            $wcml_settings = $woocommerce_wpml->get_settings();
            $attribute_taxonomies = wc_get_attribute_taxonomies();
            foreach($attribute_taxonomies as $a){
                $attribute_taxonomies_arr[] = 'pa_' . $a->attribute_name;
            }

            $taxonomy = isset($_GET['tab']) ? $_GET['tab'] : false;

            if(isset($attribute_taxonomies_arr) && in_array($taxonomy, $attribute_taxonomies_arr)){
                $value = false;
            }

        }

        return $value;
    }
    
    static function wcml_sync_product_variations($taxonomy){
        $nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if(!$nonce || !wp_verify_nonce($nonce, 'wcml_sync_product_variations')){
            die('Invalid nonce');
        }

        global $woocommerce_wpml, $wpdb, $sitepress;
        
        $VARIATIONS_THRESHOLD = 20;
        
        $wcml_settings = $woocommerce_wpml->get_settings();
        $response = array();
        
        $taxonomy = filter_input( INPUT_POST, 'taxonomy', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        
        $languages_processed = intval( $_POST['languages_processed']);

        $condition = $languages_processed?'>=':'>';

        $where = isset($_POST['last_post_id']) && $_POST['last_post_id'] ? ' ID '.$condition.' ' . intval($_POST['last_post_id']) . ' AND ' : '';
        
        $post_ids = $wpdb->get_col($wpdb->prepare("                
                SELECT DISTINCT tr.object_id 
                FROM {$wpdb->term_relationships} tr
                JOIN {$wpdb->term_taxonomy} tx on tr.term_taxonomy_id = tx.term_taxonomy_id
                JOIN {$wpdb->posts} p ON tr.object_id = p.ID
                JOIN {$wpdb->prefix}icl_translations t ON t.element_id = p.ID 
                WHERE {$where} tx.taxonomy = %s AND p.post_type = 'product' AND t.element_type='post_product' AND t.language_code = %s 
                ORDER BY ID ASC
                
        ", $taxonomy, $sitepress->get_default_language()));
        
        if($post_ids){
            
            $variations_processed = 0;
            $posts_processed = 0;
            foreach($post_ids as $post_id){
                $terms = wp_get_post_terms($post_id, $taxonomy);    
                $terms_count = count($terms) . "\n\n";
                
                $trid = $sitepress->get_element_trid($post_id, 'post_product');
                $translations = $sitepress->get_element_translations($trid, 'post_product');
                
                $i = 1;

                foreach($translations as $translation){

                    if($i > $languages_processed && $translation->element_id != $post_id){
                        $woocommerce_wpml->products->sync_product_taxonomies($post_id, $translation->element_id, $translation->language_code);
                        $woocommerce_wpml->products->sync_product_variations($post_id, $translation->element_id, $translation->language_code, false, true);
                        $woocommerce_wpml->products->create_product_translation_package($post_id,$trid, $translation->language_code,ICL_TM_COMPLETE);
                        $variations_processed += $terms_count*2;
                        $response['languages_processed'] = $i;
                        $i++;
                        //check if sum of 2 iterations doesn't exceed $VARIATIONS_THRESHOLD
                        if($variations_processed >= $VARIATIONS_THRESHOLD){                    
                            break;
                        }
                    }else{
                        $i++;
                    }
                }
                $response['last_post_id'] = $post_id;
                if(--$i == count($translations)){
                    $response['languages_processed'] = 0;
                    $languages_processed = 0;
                }else{
                    break;
                }
                
                $posts_processed ++;
                
            }

            $response['go'] = 1;
            
        }else{
            
            $response['go'] = 0;
            
        }
        
        $response['progress']   = $response['go'] ? sprintf(__('%d products left', 'woocommerce-multilingual'), count($post_ids) - $posts_processed) : __('Synchronization complete!', 'woocommerce-multilingual');
        
        if($response['go'] && isset($wcml_settings['variations_needed'][$taxonomy]) && !empty($variations_processed)){
            $wcml_settings['variations_needed'][$taxonomy] = max($wcml_settings['variations_needed'][$taxonomy] - $variations_processed, 0);            
        }else{
            if($response['go'] == 0){
                $wcml_settings['variations_needed'][$taxonomy] = 0;    
            }            
        }
        $woocommerce_wpml->update_settings($wcml_settings);                       
        
        echo json_encode($response);
        exit;
    }

    static function wcml_sync_taxonomies_in_content_preview(){
        $nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if(!$nonce || !wp_verify_nonce($nonce, 'wcml_sync_taxonomies_in_content_preview')){
            die('Invalid nonce');
        }

        global $wp_taxonomies;

        $html = $message = $errors = '';


        if(isset($wp_taxonomies[$_POST['taxonomy']])){
            $object_types = $wp_taxonomies[$_POST['taxonomy']]->object_type;

            foreach($object_types as $object_type){

                $html .= self::render_assignment_status($object_type, $_POST['taxonomy'], $preview = true);

            }

        }else{
            $errors = sprintf(__('Invalid taxonomy %s', 'woocommerce-multilingual'), $_POST['taxonomy']);
        }


        echo json_encode(array('html' => $html, 'message'=> $message, 'errors' => $errors));
        exit;
    }

    public static function wcml_sync_taxonomies_in_content(){
        $nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if(!$nonce || !wp_verify_nonce($nonce, 'wcml_sync_taxonomies_in_content')){
            die('Invalid nonce');
        }

        global $wp_taxonomies;

        $html = $message = $errors = '';

        if(isset($wp_taxonomies[$_POST['taxonomy']])){
            $html .= self::render_assignment_status($_POST['post'], $_POST['taxonomy'], $preview = false);

        }else{
            $errors .= sprintf(__('Invalid taxonomy %s', 'woocommerce-multilingual'), $_POST['taxonomy']);
        }


        echo json_encode(array('html' => $html, 'errors' => $errors));
        exit;
    }

    public static function render_assignment_status($object_type, $taxonomy, $preview = true){
        global $sitepress, $wp_post_types, $wp_taxonomies,$wpdb;

        $default_language = $sitepress->get_default_language();

        $posts = $wpdb->get_results($wpdb->prepare( "SELECT * FROM $wpdb->posts AS p LEFT JOIN {$wpdb->prefix}icl_translations AS tr ON tr.element_id = p.ID WHERE p.post_status = 'publish' AND p.post_type = %s AND tr.source_language_code is NULL", $object_type ) );

        foreach($posts as $post){

            $terms = wp_get_post_terms($post->ID, $taxonomy);

            $term_ids = array();
            foreach($terms as $term){
                $term_ids[] = $term->term_id;
            }

            $trid = $sitepress->get_element_trid($post->ID, 'post_' . $post->post_type);
            $translations = $sitepress->get_element_translations($trid, 'post_' . $post->post_type, true, true);

            foreach($translations as $language => $translation){

                if($language != $default_language && $translation->element_id){

                    $terms_of_translation =  wp_get_post_terms($translation->element_id, $taxonomy);

                    $translation_term_ids = array();
                    foreach($terms_of_translation as $term){

                        $term_id_original = apply_filters( 'translate_object_id',$term->term_id, $taxonomy, false, $default_language );
                        if(!$term_id_original || !in_array($term_id_original, $term_ids)){
                            // remove term

                            if($preview){
                                $needs_sync = true;
                                break(3);
                            }

                            $current_terms = wp_get_post_terms($translation->element_id, $taxonomy);
                            $updated_terms = array();
                            foreach($current_terms as $cterm){
                                if($cterm->term_id != $term->term_id){
                                    $updated_terms[] = $taxonomy != 'product_type' ? $term->term_id : $term->name;
                                }
                                if(!$preview){

                                    if( $taxonomy != 'product_type' && !is_taxonomy_hierarchical($taxonomy)){
                                        $updated_terms = array_unique( array_map( 'intval', $updated_terms ) );
                                    }

                                    wp_set_post_terms($translation->element_id, $updated_terms, $taxonomy);
                                }

                            }

                        }else{
                            $translation_term_ids[] = $term_id_original;
                        }

                    }

                    foreach($term_ids as $term_id){

                        if(!in_array($term_id, $translation_term_ids)){
                            // add term

                            if($preview){
                                $needs_sync = true;
                                break(3);
                            }
                            $terms_array = array();
                            $term_id_translated = apply_filters( 'translate_object_id',$term_id, $taxonomy, false, $language);

                            // not using get_term
                            $translated_term = $wpdb->get_row($wpdb->prepare("
                            SELECT * FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} x ON x.term_id = t.term_id WHERE t.term_id = %d AND x.taxonomy = %s", $term_id_translated, $taxonomy));

                            if( $translated_term ){
                                $terms_array[] = $translated_term->term_id;
                            }

                            if(!$preview){

                                if( $taxonomy != 'product_type' && !is_taxonomy_hierarchical($taxonomy)){
                                    $terms_array = array_unique( array_map( 'intval', $terms_array ) );
                                }

                                wp_set_post_terms($translation->element_id, $terms_array, $taxonomy, true);
                            }

                        }

                    }

                }

            }

        }

        $out = '';

        if($preview){

            $out .= '<div class="wcml_tt_sync_row">';
            if(!empty($needs_sync)){
                $out .= '<form class="wcml_tt_do_sync">';
                $out .= '<input type="hidden" name="post" value="' . $object_type . '" />';
                $out .= wp_nonce_field('wcml_sync_taxonomies_in_content', 'wcml_sync_taxonomies_in_content_nonce',true,false);
                $out .= '<input type="hidden" name="taxonomy" value="' . $taxonomy . '" />';
                $out .= sprintf(__('Some translated %s have different %s assignments.', 'woocommerce-multilingual'),
                    '<strong>' . mb_strtolower($wp_post_types[$object_type]->labels->name) . '</strong>',
                    '<strong>' . mb_strtolower($wp_taxonomies[$taxonomy]->labels->name) . '</strong>');
                $out .= '&nbsp;<a class="submit button-secondary" href="#">' . sprintf(__('Update %s for all translated %s', 'woocommerce-multilingual'),
                        '<strong>' . mb_strtolower($wp_taxonomies[$taxonomy]->labels->name) . '</strong>',
                        '<strong>' . mb_strtolower($wp_post_types[$object_type]->labels->name) . '</strong>') . '</a>' .
                    '&nbsp;<img src="'. ICL_PLUGIN_URL . '/res/img/ajax-loader.gif" alt="loading" height="16" width="16" class="wpml_tt_spinner" />';
                $out .= "</form>";
            }else{
                $out .= sprintf(__('All %s have the same %s assignments.', 'woocommerce-multilingual'),
                    '<strong>' . mb_strtolower($wp_taxonomies[$taxonomy]->labels->name) . '</strong>',
                    '<strong>' . mb_strtolower($wp_post_types[$object_type]->labels->name) . '</strong>');
            }
            $out .= "</div>";

        }else{

            $out .= sprintf(__('Successfully updated %s for all translated %s.', 'woocommerce-multilingual'), $wp_taxonomies[$taxonomy]->labels->name, $wp_post_types[$object_type]->labels->name);

        }

        return $out;
    }

    function shipping_terms($terms, $post_id, $taxonomy){
        global $pagenow, $woocommerce_wpml;

        if( isset( $_POST['action'] ) && $_POST['action'] == 'woocommerce_load_variations' ){
            return $terms;
        }

        if( $pagenow != 'post.php' && ( get_post_type($post_id) == 'product' || get_post_type($post_id) == 'product_variation' ) && $taxonomy == 'product_shipping_class'){
            global $sitepress;
            remove_filter('get_the_terms',array($this,'shipping_terms'), 10, 3);
            $terms = get_the_terms( apply_filters( 'translate_object_id', $post_id, get_post_type($post_id), true, $woocommerce_wpml->products->get_original_product_language( $post_id ) ),'product_shipping_class');
            add_filter('get_the_terms',array($this,'shipping_terms'), 10, 3);
            return $terms;
        }

        return $terms;
    }

    function filter_coupons_terms($terms, $taxonomies, $args){
        global $sitepress,$pagenow;

        if(is_admin() && (($pagenow == 'post-new.php' && isset($_GET['post_type']) && $_GET['post_type'] == 'shop_coupon') || ($pagenow == 'post.php' && isset($_GET['post']) && get_post_type($_GET['post']) == 'shop_coupon')) && in_array('product_cat',$taxonomies)){
            remove_filter('get_terms',array($this,'filter_coupons_terms'));
            $current_language = $sitepress->get_current_language();
            $sitepress->switch_lang($sitepress->get_default_language());
            $terms = get_terms( 'product_cat', 'orderby=name&hide_empty=0');
            add_filter('get_terms',array($this,'filter_coupons_terms'),10,3);
            $sitepress->switch_lang($current_language);
        }

        return $terms;
    }

    function filter_shipping_classes_terms( $terms, $taxonomies, $args ){
        global $sitepress;

        if( is_admin() && in_array( 'product_shipping_class', $taxonomies) && isset($_GET['page']) && $_GET['page'] == 'wc-settings' && isset($_GET['tab']) && $_GET['tab'] == 'shipping' ){
            remove_filter('get_terms',array($this,'filter_shipping_classes_terms'));
            $current_language = $sitepress->get_current_language();
            $sitepress->switch_lang($sitepress->get_default_language());
            add_filter( 'get_terms', array( 'WPML_Terms_Translations', 'get_terms_filter' ), 10, 2 );
            $terms = get_terms( $taxonomies, $args );
            remove_filter( 'get_terms', array( 'WPML_Terms_Translations', 'get_terms_filter' ), 10, 2 );
            add_filter( 'get_terms', array( $this, 'filter_shipping_classes_terms' ), 10, 3 );
            $sitepress->switch_lang($current_language);
        }

        return $terms;
    }

    function wcml_delete_term($term, $tt_id, $taxonomy, $deleted_term){
        global $wp_taxonomies;

        foreach($wp_taxonomies as $key=>$taxonomy_obj){
            if((in_array('product',$taxonomy_obj->object_type) || in_array('product_variation',$taxonomy_obj->object_type) ) && $key==$taxonomy){
                self::update_terms_translated_status($taxonomy);
                break;
            }
        }

    }

    function get_product_terms_filter( $terms, $product_id, $taxonomy, $args ){
        global $sitepress;

        $language = $sitepress->get_language_for_element( $product_id, 'post_'.get_post_type( $product_id ) );

        $is_objects_array = is_object( current ( $terms ) );

        $filtered_terms = array();

        foreach( $terms as $term ){

            if( !$is_objects_array ){
                $term_obj = get_term_by( 'name', $term, $taxonomy );
                if( !$term_obj ){
                    $term_obj = get_term_by( 'slug', $term, $taxonomy );
                    $is_slug =  true;
                }
            }

            if( empty($term_obj) ){
                $filtered_terms[] = $term;
                continue;
            }

            $trnsl_term_id = apply_filters( 'translate_object_id', $term_obj->term_id, $taxonomy, true, $language );

            if( $is_objects_array ){
                $filtered_terms[] = get_term( $trnsl_term_id, $taxonomy );
            }else{
                if( isset( $is_slug ) ){
                    $filtered_terms[] = get_term( $trnsl_term_id, $taxonomy )->slug;
                }else{
                    $filtered_terms[] = ( is_ajax() && isset( $_POST['action'] ) && in_array( $_POST['action'], array( 'woocommerce_add_variation', 'woocommerce_link_all_variations') ) ) ? strtolower ( get_term( $trnsl_term_id, $taxonomy )->name ) : get_term( $trnsl_term_id, $taxonomy )->name;
                }
            }
        }

        return $filtered_terms;
    }

    function update_woocommerce_shipping_settings_for_class_costs( $settings ){

        foreach( $settings as $setting_key => $value ){

            if(  substr($setting_key, 0, 11) == 'class_cost_' ){

                global $sitepress;
                remove_filter( 'get_term', array( $sitepress, 'get_term_adjust_id' ), 1 );

                $shipp_class_key = substr($setting_key, 11 );

                if( is_numeric( $shipp_class_key ) ){
                    $shipp_class = get_term( $shipp_class_key, 'product_shipping_class' );
                }else{
                    $shipp_class = get_term_by( 'slug', $shipp_class_key, 'product_shipping_class' );
                }

                $trid = $sitepress->get_element_trid( $shipp_class->term_taxonomy_id, 'tax_product_shipping_class' );

                $translations = $sitepress->get_element_translations( $trid, 'tax_product_shipping_class' );

                foreach( $translations as $translation ){

                    $tr_shipp_class = get_term_by( 'term_taxonomy_id', $translation->element_id, 'product_shipping_class' );

                    if( is_numeric( $shipp_class_key ) ){
                        $settings[ 'class_cost_'.$tr_shipp_class->term_id ] = $value;
                    }else{
                        $settings[ 'class_cost_'.$tr_shipp_class->slug ] = $value;
                    }

                }

                add_filter( 'get_term', array( $sitepress, 'get_term_adjust_id' ), 1 );

            }

        }

        return $settings;
    }


    function update_woocommerce_shipping_settings_for_class_costs_from_ajax(){

        if( isset( $_POST['data']['woocommerce_flat_rate_type'] ) && $_POST['data']['woocommerce_flat_rate_type'] == 'class' ){

            $settings = array();
            foreach( $_POST['data'] as $key => $value ){
                if( substr( $key, 0, 33 ) ==  'woocommerce_flat_rate_class_cost_'){
                    $settings[ substr( $key, 22 ) ] = $value;
                }
            }

            $updated_costs_settings = $this->update_woocommerce_shipping_settings_for_class_costs( $settings );

            $flat_rate_setting_id = 'woocommerce_flat_rate_'.$_POST['data']['instance_id'].'_settings';
            $settings = get_option( $flat_rate_setting_id , true );

            $settings = array_replace( $settings, $updated_costs_settings );

            update_option( $flat_rate_setting_id, $settings );
        }
    }

}
