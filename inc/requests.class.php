<?php
class WCML_Requests{
    
    function __construct(){
        
        add_action('init', array($this, 'run'));

        
    }
    
    function run(){
        global $woocommerce_wpml;

        $nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

        if(isset($_POST['wcml_mc_options']) && check_admin_referer('wcml_mc_options', 'wcml_mc_options_nonce') && wp_verify_nonce($nonce, 'wcml_mc_options')){
            
            $woocommerce_wpml->settings['enable_multi_currency'] = $_POST['multi_currency'];  
            $woocommerce_wpml->settings['display_custom_prices'] =  empty($_POST['display_custom_prices']) ? 0 : 1;
            
            //update default currency settings
            if( $_POST['multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT ){
                $options = array(
                    'woocommerce_currency_pos' => 'position',
                    'woocommerce_price_thousand_sep' => 'thousand_sep',
                    'woocommerce_price_decimal_sep' => 'decimal_sep',
                    'woocommerce_price_num_decimals' => 'num_decimals'
                );

                $woocommerce_currency = get_option('woocommerce_currency', true);

                foreach($options as $wc_key => $key){
                    $woocommerce_wpml->settings['currency_options'][$woocommerce_currency][$key] = get_option( $wc_key,true );
                }
            }

            $woocommerce_wpml->update_settings();
            
        }

        if(isset($_POST['currency_switcher_options']) && check_admin_referer('currency_switcher_options', 'currency_switcher_options_nonce') && wp_verify_nonce($nonce, 'wcml_mc_options')){

            if(isset($_POST['currency_switcher_style'])) $woocommerce_wpml->settings['currency_switcher_style'] = $_POST['currency_switcher_style'];  
            if(isset($_POST['wcml_curr_sel_orientation'])) $woocommerce_wpml->settings['wcml_curr_sel_orientation'] = $_POST['wcml_curr_sel_orientation'];
            if(isset($_POST['wcml_curr_template'])) $woocommerce_wpml->settings['wcml_curr_template'] = $_POST['wcml_curr_template'];
            $woocommerce_wpml->settings['currency_switcher_product_visibility'] = empty($_POST['currency_switcher_product_visibility']) ? 0 : 1;

            $woocommerce_wpml->update_settings();
            
        }

        if(isset($_POST['wcml_update_languages_currencies']) && isset($_POST['currency_for']) && wp_verify_nonce($nonce, 'wcml_update_languages_currencies')){
            global $wpdb;
            $currencies = $_POST['currency_for'];
            foreach($currencies as $key=>$language_currency){
                $exist_currency = $wpdb->get_var($wpdb->prepare("SELECT currency_id FROM " . $wpdb->prefix . "icl_languages_currencies WHERE language_code = %s",$key));
                if($language_currency != get_woocommerce_currency()){
                    if(!$exist_currency){
                        $wpdb->insert($wpdb->prefix .'icl_languages_currencies', array(
                                'currency_id' => $language_currency,
                                'language_code' => $key
                            )
                        );
                    } else {
                        $wpdb->update(
                            $wpdb->prefix .'icl_languages_currencies',
                            array(
                                'currency_id' => $language_currency
                            ),
                            array( 'language_code' => $key )
                        );

                        wp_safe_redirect(admin_url('admin.php?page=wpml-wcml'));
                    }
                }elseif($exist_currency){
                    $wpdb->delete($wpdb->prefix .'icl_languages_currencies', array('language_code' => $key) );
                }
            }
        }


        if(isset($_POST['wcml_file_path_options_table']) && wp_verify_nonce($nonce, 'wcml_file_path_options_table')){
            global $sitepress,$sitepress_settings;

            $wcml_file_path_sync = filter_input( INPUT_POST, 'wcml_file_path_sync', FILTER_SANITIZE_NUMBER_INT );

            $woocommerce_wpml->settings['file_path_sync'] = $wcml_file_path_sync;
            $woocommerce_wpml->update_settings();
            
            $new_value =$wcml_file_path_sync == 0?2:$wcml_file_path_sync;
            $sitepress_settings['translation-management']['custom_fields_translation']['_downloadable_files'] = $new_value;
            $sitepress_settings['translation-management']['custom_fields_translation']['_file_paths'] = $new_value;
            $sitepress->save_settings($sitepress_settings);
            }
      
        if(isset($_POST['wcml_trsl_interface_table']) && wp_verify_nonce($nonce, 'wcml_trsl_interface_table')){
            $woocommerce_wpml->settings['trnsl_interface'] = filter_input( INPUT_POST, 'trnsl_interface', FILTER_SANITIZE_NUMBER_INT );
            $woocommerce_wpml->update_settings();
        }
        
        if(isset($_POST['wcml_products_sync_prop']) && wp_verify_nonce($nonce, 'wcml_products_sync_prop')){
            $woocommerce_wpml->settings['products_sync_date'] = empty($_POST['products_sync_date']) ? 0 : 1;
            $woocommerce_wpml->settings['products_sync_order'] = empty($_POST['products_sync_order']) ? 0 : 1;
            $woocommerce_wpml->update_settings();
        }

        if(isset($_GET['wcml_action']) && $_GET['wcml_action'] = 'dismiss'){
            $woocommerce_wpml->settings['dismiss_doc_main'] = 1;
            $woocommerce_wpml->update_settings();
        }
    }

}