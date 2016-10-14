<?php

class WCML_Upgrade{
    
    private $versions = array(

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
    );
    
    function __construct(){

        add_action('init', array($this, 'run'));
        add_action('init', array($this, 'setup_upgrade_notices'));
        add_action('admin_notices',  array($this, 'show_upgrade_notices'));
        
        add_action('wp_ajax_wcml_hide_notice', array($this, 'hide_upgrade_notice'));

    }   
    
    function setup_upgrade_notices(){
        
        $wcml_settings = get_option('_wcml_settings');
        $version_in_db = get_option('_wcml_version');
        
        if(!empty($version_in_db) && version_compare($version_in_db, '2.9.9.1', '<')){
            $n = 'varimages';
            $wcml_settings['notifications'][$n] = 
                array(
                    'show' => 1, 
                    'text' => __('Looks like you are upgrading from a previous version of WooCommerce Multilingual. Would you like to automatically create translated variations and images?', 'wcml').
                            '<br /><strong>' .
                            ' <a href="' .  admin_url('admin.php?page=wpml-wcml&tab=troubleshooting') . '">' . __('Yes, go to the troubleshooting page', 'wcml') . '</a> |' .
                            ' <a href="#" onclick="jQuery.ajax({type:\'POST\',url: ajaxurl,data:\'action=wcml_hide_notice&notice='.$n.'\',success:function(){jQuery(\'#' . $n . '\').fadeOut()}});return false;">'  . __('No - dismiss', 'wcml') . '</a>' . 
                            '</strong>'
                );
            update_option('_wcml_settings', $wcml_settings);
        }
        
    }
    
    function show_upgrade_notices(){
        $wcml_settings = get_option('_wcml_settings');
        if(!empty($wcml_settings['notifications'])){ 
            foreach($wcml_settings['notifications'] as $k => $notification){
                
                // exceptions
                if(isset($_GET['tab']) && $_GET['tab'] == 'troubleshooting' && $k == 'varimages') continue;
                
                if($notification['show']){
                    ?>
                    <div id="<?php echo $k ?>" class="updated">
                        <p><?php echo $notification['text']  ?></p>
                    </div>
                    <?php    
                }
            }
        }
    }
    
    function hide_upgrade_notice($k){
        
        if(empty($k)){
            $k = $_POST['notice'];
        }
        
        $wcml_settings = get_option('_wcml_settings');
        if(isset($wcml_settings['notifications'][$k])){
            $wcml_settings['notifications'][$k]['show'] = 0;
            update_option('_wcml_settings', $wcml_settings);
        }
    }
    
    function run(){
        
        $version_in_db = get_option('_wcml_version');
        
        // exception - starting in 2.3.2
        if(empty($version_in_db) && get_option('icl_is_wcml_installed')){
            $version_in_db = '2.3.2';
        }
        
        $migration_ran = false;
        
        if($version_in_db && version_compare($version_in_db, WCML_VERSION, '<')){
                        
            foreach($this->versions as $version){
                
                if(version_compare($version, WCML_VERSION, '<=') && version_compare($version, $version_in_db, '>')){

                    $upgrade_method = 'upgrade_' . str_replace('.', '_', $version);
                    
                    if(method_exists($this, $upgrade_method)){
                        $this->$upgrade_method();
                        $migration_ran = true;
                    }
                    
                }
                
            }
            
        }
        
        if($migration_ran || empty($version_in_db)){
            update_option('_wcml_version', WCML_VERSION);            
        }
    }
    
    function upgrade_2_9_9_1(){
        global $wpdb;
        
        //migrate exists currencies
        $currencies = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "icl_currencies ORDER BY `id` DESC");
        foreach($currencies as $currency){
            if(isset($currency->language_code)){
            $wpdb->insert($wpdb->prefix .'icl_languages_currencies', array(
                    'language_code' => $currency->language_code,
                    'currency_id' => $currency->id
                )
            );
        }
        }

        $cols = $wpdb->get_col("SHOW COLUMNS FROM {$wpdb->prefix}icl_currencies");        
        if(in_array('language_code', $cols)){
            $wpdb->query("ALTER TABLE {$wpdb->prefix}icl_currencies DROP COLUMN language_code");
        }
        
        // migrate settings
        $new_settings = array(
            'is_term_order_synced'       => get_option('icl_is_wcml_term_order_synched'),
            'file_path_sync'             => get_option('wcml_file_path_sync'),
            'is_installed'               => get_option('icl_is_wpcml_installed'),
            'dismiss_doc_main'           => get_option('wpml_dismiss_doc_main'),
            'enable_multi_currency'      => get_option('icl_enable_multi_currency'),
            'currency_converting_option' => get_option('currency_converting_option')
        );
        
        if(!get_option('_wcml_settings')){
            add_option('_wcml_settings', $new_settings, false, true);
        }
        
        delete_option('icl_is_wcml_term_order_synced');
        delete_option('wcml_file_path_sync');
        delete_option('icl_is_wpcml_installed');
        delete_option('wpml_dismiss_doc_main');
        delete_option('icl_enable_multi_currency');
        delete_option('currency_converting_option');
        
        
    }
    
    function upgrade_3_1(){
        global $wpdb,$sitepress;
        $wcml_settings = get_option('_wcml_settings');
        
        if(isset($wcml_settings['enable_multi_currency']) && $wcml_settings['enable_multi_currency'] == 'yes'){
            $wcml_settings['enable_multi_currency'] = WCML_MULTI_CURRENCIES_INDEPENDENT;
        }else{
            $wcml_settings['enable_multi_currency'] = WCML_MULTI_CURRENCIES_DISABLED;
        }
        
        $wcml_settings['products_sync_date'] = 1;
        
        
        update_option('_wcml_settings', $wcml_settings);
        
        // multi-currency migration
        if($wcml_settings['enable_multi_currency'] == 'yes' && $wcml_settings['currency_converting_option'] == 2){
            
            // get currencies exchange rates
            $results = $wpdb->get_results("SELECT code, value FROM {$wpdb->prefix}icl_currencies");
            foreach($results as $row){
                $exchange_rates[$row->code] = $row->value;    
            }
            
            // get languages currencies map
            $results = $wpdb->get_results("SELECT l.language_code, c.code FROM {$wpdb->prefix}icl_languages_currencies l JOIN {$wpdb->prefix}icl_currencies c ON l.currency_id = c.id");
            foreach($results as $row){
                $language_currencies[$row->language_code] = $row->code;    
            }
            
            
            $results = $wpdb->get_results($wpdb->prepare("
                SELECT p.ID, t.trid, t.element_type 
                FROM {$wpdb->posts} p JOIN {$wpdb->prefix}icl_translations t ON t.element_id = p.ID AND t.element_type IN ('post_product', 'post_product_variation')
                WHERE 
                    p.post_type in ('product', 'product_variation') AND t.language_code = %s
                    
            ", $sitepress->get_default_language()));
            
            // set custom conversion rates
            foreach($results as $row){
                $translations = $sitepress->get_element_translations($row->trid, $row->element_type);
                $meta = get_post_meta($row->ID);
                $original_prices['_price']    = !empty($meta['_price']) ? $meta['_price'][0] : 0;
                $original_prices['_regular_price'] = !empty($meta['_regular_price']) ? $meta['_regular_price'][0] : 0;
                $original_prices['_sale_price']    = !empty($meta['_sale_price']) ? $meta['_sale_price'][0] : 0;
                
                
                $ccr = array();
                
                foreach($translations as $translation){
                    if($translation->element_id != $row->ID){
                        
                        $meta = get_post_meta($translation->element_id);
                        $translated_prices['_price'] = $meta['_price'][0];
                        $translated_prices['_regular_price'] = $meta['_regular_price'][0];
                        $translated_prices['_sale_price']    = $meta['_sale_price'][0];

                        if(!empty($translated_prices['_price']) && !empty($original_prices['_price']) && $translated_prices['_price'] != $original_prices['_price']){
                            
                            $ccr['_price'][$language_currencies[$translation->language_code]] = $translated_prices['_price'] / $original_prices['_price'];
                            
                        }                
                        if(!empty($translated_prices['_regular_price']) && !empty($original_prices['_regular_price']) && $translated_prices['_regular_price'] != $original_prices['_regular_price']){
                            
                            $ccr['_regular_price'][$language_currencies[$translation->language_code]] = $translated_prices['_regular_price'] / $original_prices['_regular_price'];
                            
                        }                
                        if(!empty($translated_prices['_sale_price']) && !empty($original_prices['_sale_price']) && $translated_prices['_sale_price'] != $original_prices['_sale_price']){
                            
                            $ccr['_sale_price'][$language_currencies[$translation->language_code]] = $translated_prices['_sale_price'] / $original_prices['_sale_price'] ;
                            
                        }                
                        
                        
                    }
                }
                
                if($ccr){
                    update_post_meta($row->ID, '_custom_conversion_rate', $ccr);    
                }
                
                
            }
            
            
        }        
        
    }
    
    function upgrade_3_2(){
        
        woocommerce_wpml::set_up_capabilities();
        
        //delete not existing currencies in WC
        global $wpdb;
        $currencies = $wpdb->get_results("SELECT id,code FROM " . $wpdb->prefix . "icl_currencies ORDER BY `id` DESC");
        $wc_currencies = get_woocommerce_currencies();
        foreach ($currencies as $currency){
            if(!array_key_exists($currency->code,$wc_currencies)){
                $wpdb->delete( $wpdb->prefix . 'icl_currencies', array( 'ID' => $currency->id ) );
            }
        }
        
    }
    
    function upgrade_3_3(){
        global $wpdb, $woocommerce_wpml;
        
        woocommerce_wpml::set_up_capabilities();

        $currencies = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "icl_currencies ORDER BY `id` ASC", OBJECT);
        if($currencies)
        foreach($this->currencies as $currency){
            
            $woocommerce_wpml->settings['currency_options'][$currency->code]['rate']      = $currency->value;
            $woocommerce_wpml->settings['currency_options'][$currency->code]['updated']   = $currency->changed;
            $woocommerce_wpml->settings['currency_options'][$currency->code]['position']  = 'left';
            $woocommerce_wpml->settings['currency_options'][$currency->code]['languages'] = $woocommerce_wpml->settings['currencies_languages'];
            unset($woocommerce_wpml->settings['currencies_languages']);
            
            $woocommerce_wpml->update_settings();
            
        }
        
        $wpdb->query("DROP TABLE `{$wpdb->prefix}icl_currencies`");
        
    }

    function upgrade_3_5()
    {
        global $wpdb;
        $wcml_settings = get_option('_wcml_settings');

        $wcml_settings['products_sync_order'] = 1;

        update_option('_wcml_settings', $wcml_settings);
    }

    function upgrade_3_5_4()
    {
        flush_rewrite_rules( );
    }

    function upgrade_3_6()
    {
        $wcml_settings = get_option('_wcml_settings');

        $wcml_settings['display_custom_prices'] = 0;
        $wcml_settings['currency_switcher_product_visibility'] = 1;

        update_option('_wcml_settings', $wcml_settings);
    }

    function upgrade_3_7(){
        global $woocommerce_wpml,$wpdb;

        $woocommerce_permalinks = maybe_unserialize( get_option('woocommerce_permalinks') );

        if( is_array( $woocommerce_permalinks ) ) {
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
                            array(
                                'context' => 'WordPress',
                                'name' => sprintf( 'URL %s tax slug', $taxonomy )
                            ),
                            array(
                                'context' => sprintf( 'URL %s slugs - %s', $taxonomy, $base ),
                                'name' => sprintf( 'Url %s slug: %s', $taxonomy, $base )
                            )
                        );

                    }
                }

            }
        }

        $endpoint_keys = array( 'order-pay', 'order-received', 'view-order', 'edit-account', 'edit-address', 'lost-password', 'customer-logout', 'add-payment-method' );

        foreach( $endpoint_keys as $endpoint_key ){

            $wpdb->query(
                $wpdb->prepare( "UPDATE {$wpdb->prefix}icl_strings
                                  SET context = 'WooCommerce Endpoints', name = %s
                                  WHERE context = 'WordPress' AND name = %s",
                    $endpoint_key, 'Endpoint slug: '. $endpoint_key )
            );

            // update domain_name_context_md5 value
            $string_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}icl_strings WHERE context = 'WooCommerce Endpoints' AND name = %s", $endpoint_key ) );

            if( $string_id ){
                $wpdb->query(
                    $wpdb->prepare( "UPDATE {$wpdb->prefix}icl_strings
                              SET domain_name_context_md5 = %s
                              WHERE id = %d",
                        md5( $endpoint_key,'WooCommerce Endpoints' ), $string_id )
                );
            }

        }

        if( !isset($woocommerce_wpml->terms) ){
            global $sitepress;
            $woocommerce_wpml->terms = new WCML_Terms( $woocommerce_wpml, $sitepress, $wpdb );
        }
        $woocommerce_wpml->terms->check_if_sync_terms_needed();

        $wcml_settings = get_option('_wcml_settings');

        $wcml_settings['sync_taxonomies_checked'] = 1;

        update_option('_wcml_settings', $wcml_settings);


        //update custom fields for bookings
        $bookable_resources = $wpdb->get_results( "SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE element_type = 'post_bookable_resource' AND source_language_code IS NOT NULL");

        foreach( $bookable_resources AS $bookable_resource ){
            update_post_meta( $bookable_resource->element_id, 'wcml_is_translated', true );
        }

        $bookable_persons = $wpdb->get_results( "SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE element_type = 'post_bookable_person' AND source_language_code IS NOT NULL");

        foreach( $bookable_persons AS $bookable_person ){
            update_post_meta( $bookable_person->element_id, 'wcml_is_translated', true );
        }

    }

    function upgrade_3_7_3()
    {
        global $sitepress;

        $active_languages = $sitepress->get_active_languages();
        $current_language = $sitepress->get_current_language();

        foreach( $active_languages as $lang ){

            $sitepress->switch_lang( $lang['code'] );

            $product_cats = get_terms( 'product_cat', array( 'hide_empty' => false, 'fields' => 'id=>parent' ) );

            _wc_term_recount( $product_cats, get_taxonomy( 'product_cat' ), true, false );

            $product_tags = get_terms( 'product_tag', array( 'hide_empty' => false, 'fields' => 'id=>parent' ) );

            _wc_term_recount( $product_tags, get_taxonomy( 'product_tag' ), true, false );

        }

        $sitepress->switch_lang( $current_language );

    }

    function upgrade_3_7_11(){

        $wcml_settings = get_option('_wcml_settings');
        $wcml_settings['dismiss_doc_main'] = 1;
        update_option('_wcml_settings', $wcml_settings);
 	}

    function upgrade_3_8(){

        $wcml_settings = get_option('_wcml_settings');
        $wcml_settings['set_up_wizard_run'] = 1;

        if( isset($wcml_settings[ 'attributes_settings' ]) ) {
            $attributes_settings = $wcml_settings['attributes_settings'];
            foreach ( $attributes_settings as $name => $value ) {
                if ( substr( $name, 0, 3 ) != 'pa_' ) {
                    unset( $wcml_settings['attributes_settings'] [$name] );
                    $wcml_settings['attributes_settings'] ['pa_' . $name] = $value;
                }
            }
        }

        update_option('_wcml_settings', $wcml_settings);

    }

    function upgrade_3_9(){
        global $wpdb;

        $meta_keys_to_fix = array(
            '_price',
            '_regular_price',
            '_sale_price',
            '_sku'
        );

        $sql = "
            UPDATE {$wpdb->postmeta} 
            SET meta_value = '' 
            WHERE meta_key IN('" . join("','", $meta_keys_to_fix) . "') 
                AND meta_value IS NULL";

        $wpdb->query( $sql );

    }

    function upgrade_3_9_1(){
        global $wpdb, $sitepress;

        $results = $wpdb->get_results("
                        SELECT p.ID, t.trid, t.element_type
                        FROM {$wpdb->posts} p
                        JOIN {$wpdb->prefix}icl_translations t ON t.element_id = p.ID AND t.element_type IN ('post_product', 'post_product_variation')
                        WHERE p.post_type in ('product', 'product_variation') AND t.source_language_code IS NULL
                    ");

        foreach( $results as $product ){

            if( get_post_meta( $product->ID, '_manage_stock', true ) === 'yes' ){

                $translations = $sitepress->get_element_translations( $product->trid, $product->element_type );

                $min_stock = false;

                //collect min stock
                foreach( $translations as $translation ){
                    $stock = get_post_meta( $translation->element_id, '_stock', true );
                    if( !$min_stock || $stock < $min_stock ){
                        $min_stock = $stock;
                    }
                }

                //update stock value
                foreach( $translations as $translation ){
                    update_post_meta( $translation->element_id, '_stock', $min_stock );
                }
            }
        }
    }


}