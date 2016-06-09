<?php
class WCML_Requests{
    
    function __construct(){
        
        add_action('init', array($this, 'run'));

        
    }
    
    function run(){
        global $woocommerce_wpml;

        $nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

        if(isset($_POST['wcml_save_settings']) && wp_verify_nonce($nonce, 'wcml_save_settings_nonce')){
            global $sitepress,$sitepress_settings;

            $woocommerce_wpml->settings['trnsl_interface'] = filter_input( INPUT_POST, 'trnsl_interface', FILTER_SANITIZE_NUMBER_INT );

            $woocommerce_wpml->settings['products_sync_date'] = empty($_POST['products_sync_date']) ? 0 : 1;
            $woocommerce_wpml->settings['products_sync_order'] = empty($_POST['products_sync_order']) ? 0 : 1;

            $wcml_file_path_sync = filter_input( INPUT_POST, 'wcml_file_path_sync', FILTER_SANITIZE_NUMBER_INT );

            $woocommerce_wpml->settings['file_path_sync'] = $wcml_file_path_sync;
            $woocommerce_wpml->update_settings();

            $new_value =$wcml_file_path_sync == 0?2:$wcml_file_path_sync;
            $sitepress_settings['translation-management']['custom_fields_translation']['_downloadable_files'] = $new_value;
            $sitepress_settings['translation-management']['custom_fields_translation']['_file_paths'] = $new_value;
            $sitepress->save_settings($sitepress_settings);
        }

        if(isset($_GET['wcml_action']) && $_GET['wcml_action'] = 'dismiss'){
            $woocommerce_wpml->settings['dismiss_doc_main'] = 1;
            $woocommerce_wpml->update_settings();
        }


        add_action('wp_ajax_wcml_ignore_warning', array( $this, 'update_settings_from_warning') );

        // Override cached widget id
        add_filter( 'woocommerce_cached_widget_id', array( $this, 'override_cached_widget_id' ) );
    }

    function update_settings_from_warning(){
        $nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if(!$nonce || !wp_verify_nonce($nonce, 'wcml_ignore_warning')){
            die('Invalid nonce');
        }
        global $woocommerce_wpml;

        $woocommerce_wpml->settings[$_POST['setting']] = 1;
        $woocommerce_wpml->update_settings();

    }

    public function override_cached_widget_id( $widget_id ){

        if( defined( 'ICL_LANGUAGE_CODE' ) ){
            $widget_id .= ':' . ICL_LANGUAGE_CODE;
        }

        return $widget_id;
    }

}