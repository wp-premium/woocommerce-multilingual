<?php

class WCML_Install{

    public static function initialize( &$woocommerce_wpml, &$sitepress ) {

        // Install routine
        if ( empty($woocommerce_wpml->settings['set_up']) ) { // from 3.2

            if ( $woocommerce_wpml->settings['is_term_order_synced'] !== 'yes' ) {
                //global term ordering resync when moving to >= 3.3.x
                add_action( 'init', array($woocommerce_wpml->terms, 'sync_term_order_globally'), 20 );
            }

            if ( !isset($woocommerce_wpml->settings['wc_admin_options_saved']) ) {
                self::handle_admin_texts();
                $woocommerce_wpml->settings['wc_admin_options_saved'] = 1;
            }

            if ( !isset( $woocommerce_wpml->settings['trnsl_interface'] ) ) {
                $woocommerce_wpml->settings['trnsl_interface'] = 1;
            }

            if ( !isset($woocommerce_wpml->settings['products_sync_date']) ) {
                $woocommerce_wpml->settings['products_sync_date'] = 1;
            }

            if ( !isset($woocommerce_wpml->settings['products_sync_order']) ) {
                $woocommerce_wpml->settings['products_sync_order'] = 1;
            }

            if ( !isset($woocommerce_wpml->settings['display_custom_prices']) ) {
                $woocommerce_wpml->settings['display_custom_prices'] = 0;
            }

            if ( !isset($woocommerce_wpml->settings['sync_taxonomies_checked']) ) {
                $woocommerce_wpml->terms->check_if_sync_terms_needed();
                $woocommerce_wpml->settings['sync_taxonomies_checked'] = 1;
            }

            WCML_Capabilities::set_up_capabilities();

            self:: set_language_information( $sitepress );
            self:: check_product_type_terms( );

            $woocommerce_wpml->settings['set_up'] = 1;
            $woocommerce_wpml->update_settings();

        }

        if(empty($woocommerce_wpml->settings['downloaded_translations_for_wc'])){ //from 3.3.3
            $woocommerce_wpml->languages_upgrader->download_woocommerce_translations_for_active_languages();
            $woocommerce_wpml->settings['downloaded_translations_for_wc'] = 1;
            $woocommerce_wpml->update_settings();
        }

        add_filter( 'wpml_tm_dashboard_translatable_types', array( __CLASS__, 'hide_variation_type_on_tm_dashboard') );

        new WCML_Setup( $woocommerce_wpml, $sitepress );
        if ( !empty($woocommerce_wpml->settings['set_up_wizard_run']) ) {
            add_action( 'admin_notices', array(__CLASS__, 'admin_notice_after_install') );
        }

    }

    private static function set_language_information( &$sitepress ){
        global $wpdb;

        $def_lang = $sitepress->get_default_language();
        //set language info for products
        $products = $wpdb->get_results("SELECT ID FROM $wpdb->posts WHERE post_type = 'product' AND post_status <> 'auto-draft'");
        foreach($products as $product){
            $exist = $sitepress->get_language_for_element($product->ID,'post_product');
            if(!$exist){
                $sitepress->set_element_language_details($product->ID, 'post_product',false,$def_lang);
            }
        }

        //set language info for taxonomies
        $terms = $wpdb->get_results("SELECT term_taxonomy_id FROM $wpdb->term_taxonomy WHERE taxonomy = 'product_cat'");
        foreach($terms as $term){
            $exist = $sitepress->get_language_for_element($term->term_taxonomy_id, 'tax_product_cat');
            if(!$exist){
                $sitepress->set_element_language_details($term->term_taxonomy_id, 'tax_product_cat',false,$def_lang);
            }
        }
        $terms = $wpdb->get_results("SELECT term_taxonomy_id FROM $wpdb->term_taxonomy WHERE taxonomy = 'product_tag'");
        foreach($terms as $term){
            $exist = $sitepress->get_language_for_element($term->term_taxonomy_id, 'tax_product_tag');
            if(!$exist){
                $sitepress->set_element_language_details($term->term_taxonomy_id, 'tax_product_tag',false,$def_lang);
            }
        }

        $terms = $wpdb->get_results("SELECT term_taxonomy_id FROM $wpdb->term_taxonomy WHERE taxonomy = 'product_shipping_class'");
        foreach($terms as $term){
            $exist = $sitepress->get_language_for_element($term->term_taxonomy_id, 'tax_product_shipping_class');
            if(!$exist){
                $sitepress->set_element_language_details($term->term_taxonomy_id, 'tax_product_shipping_class',false,$def_lang);
            }
        }
    }

    //handle situation when product_type terms translated before activating WCML
    private static function check_product_type_terms(){
        global $wpdb;
        //check if terms were translated
        $translations = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}icl_translations WHERE element_type = 'tax_product_type'" );

        if( $translations ){
            foreach( $translations as $translation ){
                if( !is_null( $translation->source_language_code ) ){
                    //check relationships
                    $term_relationships = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->term_relationships} WHERE term_taxonomy_id = %d", $translation->element_id  ) );
                    if( $term_relationships ){
                        $orig_term = $wpdb->get_var( $wpdb->prepare( "SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE element_type = 'tax_product_type' AND trid = %d AND source_language_code IS NULL", $translation->trid ) );
                        if( $orig_term ){
                            foreach( $term_relationships as $term_relationship ){
                                $wpdb->update(
                                    $wpdb->term_relationships,
                                    array(
                                        'term_taxonomy_id' => $orig_term
                                    ),
                                    array(
                                        'object_id' => $term_relationship->object_id,
                                        'term_taxonomy_id' => $translation->element_id
                                    )
                                );
                            }
                        }
                    }
                    $term_id = $wpdb->get_var( $wpdb->prepare( "SELECT term_id FROM {$wpdb->term_taxonomy} WHERE term_taxonomy_id = %d", $translation->element_id  ) );

                    if( $term_id ){
                        $wpdb->delete(
                            $wpdb->terms,
                            array(
                                'term_id' => $term_id
                            )
                        );

                        $wpdb->delete(
                            $wpdb->term_taxonomy,
                            array(
                                'term_taxonomy_id' => $translation->element_id
                            )
                        );
                    }
                }
            }

            foreach( $translations as $translation ){
                $wpdb->delete(
                    $wpdb->prefix . 'icl_translations',
                    array(
                        'translation_id' => $translation->translation_id
                    )
                );
            }
        }
    }

    private static function handle_admin_texts(){
        if(class_exists('woocommerce')){
            //emails texts
            $emails = new WC_Emails();
            foreach($emails->emails as $email){
                $option_name  = $email->plugin_id.$email->id.'_settings';
                if(!get_option($option_name)){
                    add_option($option_name,$email->settings);
                }
            }
        }
    }

    public static function admin_notice_after_install(){
        global $woocommerce_wpml;

        if( !$woocommerce_wpml->settings['dismiss_doc_main'] ){

            $url = $_SERVER['REQUEST_URI'];
            $pos = strpos($url, '?');

            if($pos !== false){
                $url .= '&wcml_action=dismiss';
            } else {
                $url .= '?wcml_action=dismiss';
            }
            ?>
            <div id="message" class="updated message fade otgs-is-dismissible">
                <p>
                    <?php printf(__("You've successfully installed %sWooCommerce Multilingual%s. Would you like to see a quick overview?", 'woocommerce-multilingual'), '<strong>', '</strong>'); ?>
                </p>
                <p>
                    <a class="button-primary align-right" href="<?php echo WCML_Links::generate_tracking_link('https://wpml.org/documentation/related-projects/woocommerce-multilingual/','woocommerce-multilingual','documentation'); ?>" target="_blank">
                        <?php _e('Learn how to turn your e-commerce site multilingual', 'woocommerce-multilingual') ?>
                    </a>
                </p>
                <a class="notice-dismiss" href="<?php echo $url; ?>"><span class="screen-reader-text"><?php _e('Dismiss', 'woocommerce-multilingual') ?></span></a>
            </div>
            <?php
        }
    }

    public static function hide_variation_type_on_tm_dashboard( $types ){
        unset( $types['product_variation'] );
        return $types;
    }

}