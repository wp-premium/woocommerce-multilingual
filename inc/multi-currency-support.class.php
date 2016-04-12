<?php
  
class WCML_Multi_Currency_Support{
    
    private $currencies = array();
    private $currency_codes = array();
    
    private $client_currency;
    private $exchange_rates = array();
    
    function __construct(){

        add_action('init', array($this, 'init'), 5);
        $this->install();

        $this->init_currencies();

        if(is_ajax()){
            add_action('wp_ajax_nopriv_wcml_switch_currency', array($this, 'switch_currency'));
            add_action('wp_ajax_wcml_switch_currency', array($this, 'switch_currency'));
            
            add_action('wp_ajax_legacy_update_custom_rates', array($this, 'legacy_update_custom_rates'));
            add_action('wp_ajax_legacy_remove_custom_rates', array($this, 'legacy_remove_custom_rates'));

            add_action('wp_ajax_wcml_new_currency', array($this,'add_currency')); 
            add_action('wp_ajax_wcml_save_currency', array($this,'save_currency'));
            add_action('wp_ajax_wcml_delete_currency', array($this,'delete_currency'));
            add_action('wp_ajax_wcml_currencies_list', array($this,'currencies_list'));
            
            add_action('wp_ajax_wcml_update_currency_lang', array($this,'update_currency_lang'));
            add_action('wp_ajax_wcml_update_default_currency', array($this,'update_default_currency'));
            
        }
        
        if(is_admin()){
            add_action('admin_footer', array($this, 'currency_options_wc_integration'));            
            add_action('woocommerce_settings_save_general', array($this, 'currency_options_wc_integration_save_hook'));
        }
        
        add_action( 'wp_enqueue_scripts', array( $this, 'register_styles' ) );
        add_action( 'init', array( $this, 'register_styles' ) );

        add_filter( 'woocommerce_cart_contents_total', array( $this, 'filter_woocommerce_cart_contents_total'), 100 );
        add_filter( 'woocommerce_cart_subtotal', array( $this, 'filter_woocommerce_cart_subtotal'), 100, 3 );

        add_action( 'update_option_woocommerce_currency', array( $this, 'set_default_currencies_languages' ), 10, 2 );

    }

    function _load_filters(){
        $load = false;
        
        if(!is_admin() && $this->get_client_currency() != get_option('woocommerce_currency')){
            $load = true;
        }else{
            if(is_ajax() && $this->get_client_currency() != get_option('woocommerce_currency')){

                $ajax_actions = apply_filters('wcml_multi_currency_is_ajax', array('woocommerce_get_refreshed_fragments', 'woocommerce_update_order_review', 'woocommerce-checkout', 'woocommerce_checkout', 'woocommerce_add_to_cart', 'woocommerce_update_shipping_method'));

                if( ( isset( $_POST['action'] ) && in_array( $_POST['action'], $ajax_actions ) ) || (  isset( $_GET['action'] ) && in_array( $_GET['action'], $ajax_actions ) ) ){
                    $load = true;
                }

            }
        }
        
        return apply_filters('wcml_load_multi_currency', $load);
    }
    
    function init(){

        if($this->_load_filters()){    
            
            add_filter('woocommerce_currency', array($this, 'currency_filter'));
            //add_filter('option_woocommerce_currency', array($this, 'currency_filter'));
            
            add_filter('get_post_metadata', array($this, 'product_price_filter'), 10, 4);
            add_filter('get_post_metadata', array($this, 'variation_prices_filter'), 12, 4); // second

            add_filter('woocommerce_package_rates', array($this, 'shipping_taxes_filter'));
            
            add_action('woocommerce_coupon_loaded', array($this, 'filter_coupon_data'));
            
            add_filter('option_woocommerce_free_shipping_settings', array($this, 'adjust_min_amount_required'));
            
            // table rate shipping support
            if(defined('TABLE_RATE_SHIPPING_VERSION')){
                add_filter('woocommerce_table_rate_query_rates', array($this, 'table_rate_shipping_rates'));
                add_filter('woocommerce_table_rate_instance_settings', array($this, 'table_rate_instance_settings'));
            }
            
            //filters for wc-widget-price-filter
            add_filter( 'woocommerce_price_filter_results', array( $this, 'filter_price_filter_results' ), 10, 3 );
            add_filter( 'woocommerce_price_filter_widget_amount', array( $this, 'filter_price_filter_widget_amount' ) );

            add_filter('option_woocommerce_price_thousand_sep', array($this, 'filter_currency_thousand_sep_option'));
            add_filter('option_woocommerce_price_decimal_sep', array($this, 'filter_currency_decimal_sep_option'));
            add_filter('option_woocommerce_price_num_decimals', array($this, 'filter_currency_num_decimals_option'));

            add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'filter_currency_num_decimals_in_cart' ) );
            
        }


        add_filter('option_woocommerce_currency_pos', array($this, 'filter_currency_position_option'));
        add_action( 'woocommerce_view_order', array( $this, 'filter_view_order' ), 9 );

        add_action('currency_switcher', array($this, 'currency_switcher'));        
        add_shortcode('currency_switcher', array($this, 'currency_switcher_shortcode'));

        if( version_compare( WOOCOMMERCE_VERSION, '2.3', '<' ) ){
            add_filter( 'wc_price', array( $this, 'price_in_specific_currency' ), 10, 3 );
        }

        add_filter( 'wc_price_args', array( $this, 'filter_wc_price_args') );

        if(defined('W3TC')){
            require WCML_PLUGIN_PATH . '/inc/w3tc-compatibility.class.php';
            $this->WCML_WC_MultiCurrency_W3TC = new WCML_WC_MultiCurrency_W3TC;
        }

        if(!is_admin()) $this->load_inline_js();

        add_action( 'woocommerce_get_children', array( $this, 'filter_product_variations_with_custom_prices' ), 10 );

        
    }

    function install(){
        global $woocommerce_wpml;

        if(empty($woocommerce_wpml->settings['multi_currency']['set_up'])){
            $woocommerce_wpml->settings['multi_currency']['set_up'] = 1;
            $woocommerce_wpml->update_settings();

            $this->set_default_currencies_languages();
        }

        return;

    }

    function init_currencies(){
        global $woocommerce_wpml, $sitepress;
        $this->currencies =& $woocommerce_wpml->settings['currency_options'];  // ref
        
        $save_to_db = false;
        
        $active_languages = $sitepress->get_active_languages();
        
        $currency_defaults = array(
                                'rate'                  => 0,
                                'position'              => 'left',
                                'thousand_sep'          => ',',
                                'decimal_sep'           => '.',
                                'num_decimals'          => 2,
                                'rounding'              => 'disabled',
                                'rounding_increment'    => 1,
                                'auto_subtract'         => 0
        );
        
        foreach($this->currencies as $code => $currency){
            foreach($currency_defaults as $key => $val){
                if(!isset($currency[$key])){
                    $this->currencies[$code][$key] = $val;
                    $save_to_db = true;
                }
            }
            
            foreach($active_languages as $language){
                if(!isset($currency['languages'][$language['code']])){
                    $this->currencies[$code]['languages'][$language['code']] = 1;
                    $save_to_db = true;
                }
            }
        }
        
        $this->currency_codes = array_keys($this->currencies);

        // default language currencies
        foreach($active_languages as $language){
            if(!isset($woocommerce_wpml->settings['default_currencies'][$language['code']])){
                $woocommerce_wpml->settings['default_currencies'][$language['code']] = 0;
                $save_to_db = true;
            }
        }
        
        // sanity check
        if(isset($woocommerce_wpml->settings['default_currencies'])){
            foreach($woocommerce_wpml->settings['default_currencies'] as $language => $value){
                if(!isset($active_languages[$language])){
                    unset($woocommerce_wpml->settings['default_currencies'][$language]);
                    $save_to_db = true;
                }
                if(!empty($value) && !in_array($value, $this->currency_codes)){
                    $woocommerce_wpml->settings['default_currencies'][$language] = 0;
                    $save_to_db = true;
                }
            }
        }

        // add missing currencies to currencies_order
        if(isset($woocommerce_wpml->settings['currencies_order'])){
            foreach ($this->currency_codes as $currency) {
                if (!in_array($currency, $woocommerce_wpml->settings['currencies_order'])) {
                    $woocommerce_wpml->settings['currencies_order'][] = $currency;
                    $save_to_db = true;
                }
            }
        }

        if($save_to_db){
            $woocommerce_wpml->update_settings();                
        }

        // force disable multi-currency when the default currency is empty
        $wc_currency    = get_option('woocommerce_currency');
        if(empty($wc_currency)){
            $woocommerce_wpml->settings['enable_multi_currency'] = WCML_MULTI_CURRENCIES_DISABLED;
        }
        
    }
    
    function get_currencies(){
        
        // by default, exclude default currency
        $currencies = array();
        foreach($this->currencies as $key => $value){
            if(get_option('woocommerce_currency') != $key){
                $currencies[$key] = $value;
            }
        }
         
        return $currencies;
    }
    
    function get_currency_codes(){
        return $this->currency_codes;
    }

    function set_default_currencies_languages( $old_value = false, $new_value = false ){
        global $woocommerce_wpml,$sitepress;

        $settings = $woocommerce_wpml->get_settings();

        $wc_currency = $new_value ? $new_value : get_option('woocommerce_currency');

        $active_languages = $sitepress->get_active_languages();
        foreach ($this->get_currency_codes() as $code) {
            foreach($active_languages as $language){
                if(!isset($settings['currency_options'][$code]['languages'][$language['code']])){
                    $settings['currency_options'][$code]['languages'][$language['code']] = 1;
                }
            }
        }

        foreach($active_languages as $language){
            if(!isset($settings['default_currencies'][$language['code']])){
                $settings['default_currencies'][$language['code']] = false;
            }

            if(!isset($settings['currency_options'][$wc_currency]['languages'][$language['code']])){
                $settings['currency_options'][$wc_currency]['languages'][$language['code']] = 1;
            }
        }

        $woocommerce_wpml->update_settings($settings);

    }
    
    function add_currency(){
        $nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if(!$nonce || !wp_verify_nonce($nonce, 'wcml_new_currency')){
            die('Invalid nonce');
        }

        global $sitepress, $woocommerce_wpml;;
        $settings = $woocommerce_wpml->get_settings();
        
        $return = array();
        
        if(!empty($_POST['currency_code'])){
            
            $currency_code = filter_input( INPUT_POST, 'currency_code', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            
            $active_languages = $sitepress->get_active_languages();
            $return['languages'] ='';
            foreach($active_languages as $language){
                if(!isset($settings['currency_options'][$currency_code]['languages'][$language['code']])){
                    $settings['currency_options'][$currency_code]['languages'][$language['code']] = 1;
                }
            }
            $settings['currency_options'][$currency_code]['rate'] = (double) filter_input( INPUT_POST, 'currency_value', FILTER_VALIDATE_FLOAT , FILTER_FLAG_ALLOW_FRACTION);
            $settings['currency_options'][$currency_code]['updated'] = date('Y-m-d H:i:s');        

            $wc_currency = get_option('woocommerce_currency'); 
            if(!isset($settings['currencies_order']))
                $settings['currencies_order'][] = $wc_currency;

            $settings['currencies_order'][] = $currency_code;

            $woocommerce_wpml->update_settings($settings);

            $wc_currencies = get_woocommerce_currencies();
            $return['currency_name_formatted'] = sprintf('%s (%s)', $wc_currencies[$currency_code], sprintf('%s 99.99', get_woocommerce_currency_symbol($currency_code)));
            $return['currency_name_formatted_without_rate'] = sprintf('%s (%s)', $wc_currencies[$currency_code], get_woocommerce_currency_symbol($currency_code));
            $return['currency_meta_info'] = sprintf('1 %s = %s %s', $wc_currency, $settings['currency_options'][$currency_code]['rate'], $currency_code);
            

            $code = $currency_code;
            $this->init_currencies();
            $currency = $this->currencies[$currency_code];
            ob_start();
            include WCML_PLUGIN_PATH . '/menu/sub/custom-currency-options.php'; 
            $return['currency_options'] = ob_get_contents();
            ob_end_clean();

        }

        echo json_encode($return);
        die();
    }    
    
    function save_currency(){
        $nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if(!$nonce || !wp_verify_nonce($nonce, 'save_currency')){
            die('Invalid nonce');
        }

        global $woocommerce_wpml;
        
        $currency_code = filter_input( INPUT_POST, 'currency', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $options = $_POST['currency_options'][$currency_code];
        
        $changed = false;
        $rate_changed = false;
        foreach($this->currencies[$currency_code] as $key => $value){
            
            if(isset($options[$key]) && $options[$key] != $value){
                $this->currencies[$currency_code][$key] = $options[$key];
                $changed = true;
                if($key == 'rate'){
                    $rate_changed = true;
                }
            }
            
        }

        if($changed){
            if($rate_changed){
                $this->currencies[$currency_code]['updated'] = date('Y-m-d H:i:s');
            }
            $woocommerce_wpml->settings['currency_options'] = $this->currencies;
            $woocommerce_wpml->update_settings();
        }
        
        
        $wc_currency = get_option('woocommerce_currency'); 
        $wc_currencies = get_woocommerce_currencies();
        
        switch($this->currencies[$currency_code]['position']){
            case 'left': $price = sprintf('%s99.99', get_woocommerce_currency_symbol($currency_code)); break;
            case 'right': $price = sprintf('99.99%s', get_woocommerce_currency_symbol($currency_code)); break;
            case 'left_space': $price = sprintf('%s 99.99', get_woocommerce_currency_symbol($currency_code)); break;
            case 'right_space': $price = sprintf('99.99 %s', get_woocommerce_currency_symbol($currency_code)); break;
        }
        $return['currency_name_formatted'] = sprintf('%s (%s)', $wc_currencies[$currency_code], $price);
        
        $return['currency_meta_info'] = sprintf('1 %s = %s %s', $wc_currency, $this->currencies[$currency_code]['rate'], $currency_code);
        
        echo json_encode($return);
        exit;
    }
    
    function delete_currency(){
        $nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if(!$nonce || !wp_verify_nonce($nonce, 'wcml_delete_currency')){
            die('Invalid nonce');
        }
        global $woocommerce_wpml;

        $settings = $woocommerce_wpml->get_settings();
        unset($settings['currency_options'][$_POST['code']]);
        
        if(isset($settings['currencies_order'])){
            foreach($settings['currencies_order'] as $key=>$cur_code){
                if($cur_code == $_POST['code']) unset($settings['currencies_order'][$key]);
            }
        }

        $woocommerce_wpml->update_settings($settings);
        
        exit;
    }

    function currencies_list(){
        $nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if(!$nonce || !wp_verify_nonce($nonce, 'wcml_currencies_list')){
            die('Invalid nonce');
        }

        global $woocommerce_wpml;
        $wc_currencies = get_woocommerce_currencies();
        $wc_currency = get_option('woocommerce_currency');
        unset($wc_currencies[$wc_currency]);
        $currencies = $woocommerce_wpml->multi_currency_support->get_currencies();
        $html = '<select name="code">';
        foreach($wc_currencies as $wc_code=>$currency_name){
            if(empty($currencies[$wc_code])){
                $html .= '<option value="'.$wc_code.'">'.$currency_name.'</option>';
            }
        }
        $html .= '</select>';
        ob_clean();
        echo $html;

        die();
    }
    
    function update_currency_lang(){
        $nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if(!$nonce || !wp_verify_nonce($nonce, 'wcml_update_currency_lang')){
            die('Invalid nonce');
        }
        global $woocommerce_wpml;

        $settings = $woocommerce_wpml->get_settings();
        $settings['currency_options'][$_POST['code']]['languages'][$_POST['lang']] = $_POST['value'];

        $woocommerce_wpml->update_settings($settings);
        exit;
    }

    function update_default_currency(){
        $nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if(!$nonce || !wp_verify_nonce($nonce, 'wcml_update_default_currency')){
            die('Invalid nonce');
        }
        global $woocommerce_wpml;
        $woocommerce_wpml->settings['default_currencies'][$_POST['lang']] = $_POST['code'];
        $woocommerce_wpml->update_settings();
        
        exit;
    }
    
    function currency_options_wc_integration(){
        global $woocommerce_wpml;
        
        if($woocommerce_wpml->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT && count($this->currencies) > 1 && isset($_GET['page']) && $_GET['page'] == 'wc-settings' && (!isset($_GET['tab']) || (isset($_GET['tab']) && $_GET['tab'] == 'general'))){
            
            wp_enqueue_style('wcml_wc', WCML_PLUGIN_URL . '/assets/css/wcml-wc-integration.css', array(), WCML_VERSION);
            
            $wc_currencies = get_woocommerce_currencies();
            $wc_currency = get_option('woocommerce_currency');
                                     
            foreach($this->currencies as $code => $currency){
                $selected = $code == $wc_currency ? ' selected' : '';
                $menu[] = '<a class="wcml_currency_options_menu_item' . $selected . '" href="#" data-currency="' . $code . '">' . 
                    sprintf('%s (%s)', $wc_currencies[$code], get_woocommerce_currency_symbol($code)) . '</a>';
                
                if($code != $wc_currency){
                    $symbols[] = get_woocommerce_currency_symbol($code);
                    
                    $options_currency_pos[] = $currency['position'];
                    $options_thousand_sep[] = $currency['thousand_sep'];
                    $options_decimal_sep[] = $currency['decimal_sep'];
                    $options_num_decimals[] = $currency['num_decimals'];
                }
                
            }
            
            $menu = '<p>' . esc_js(__('Select the currency you want to set the options for:', 'woocommerce-multilingual')) . '</p><br />' . join (' | ', $menu);
            
            $codes = "['" . join("', '", array_keys($this->get_currencies())) . "']";            
            $symbols = "['" . join("', '", $symbols) . "']";            
            $symbol_default =  get_woocommerce_currency_symbol($wc_currency);
            $symbol_default = html_entity_decode($symbol_default);
            
            $options_currency_pos = "['" . join("', '", $options_currency_pos) . "']";            
            $options_thousand_sep = "['" . join("', '", $options_thousand_sep) . "']";            
            $options_decimal_sep = "['" . join("', '", $options_decimal_sep) . "']";            
            $options_num_decimals = "['" . join("', '", $options_num_decimals) . "']";            
            
            wc_enqueue_js( "
                var wcml_wc_currency_options_integration = {
                    
                    init: function(){  
                        
                        var table = jQuery('.form-table').eq(1);                         
                        var currencies = {$codes};
                        var symbols = {$symbols};
                        var symbol_default = '{$symbol_default}';
                        
                        var options_currency_pos = {$options_currency_pos};
                        var options_thousand_sep = {$options_thousand_sep};
                        var options_decimal_sep = {$options_decimal_sep};
                        var options_num_decimals = {$options_num_decimals};
                        
                        table.find('tr').each(function( index ){
                            if(index > 0){
                                jQuery(this).addClass('wcml_co_row');
                                jQuery(this).addClass('wcml_co_row_{$wc_currency}');
                            }
                        });
                                                
                        table.find('tr').each(function( index ){
                            if(index > 0){
                                for(var i in currencies){
                                    var currency_option_row = jQuery(this).clone();    
                                    currency_option_row.removeClass('wcml_co_row_{$wc_currency}');
                                    currency_option_row.addClass('wcml_co_row_' + currencies[i]);
                                    currency_option_row.addClass('hidden');
                                    
                                    var html = currency_option_row.html();
                                    
                                    html = html.replace(/woocommerce_currency_pos/g, 'woocommerce_currency_pos_' + currencies[i]);
                                    html = html.replace(/woocommerce_price_thousand_sep/g, 'woocommerce_price_thousand_sep_' + currencies[i]);
                                    html = html.replace(/woocommerce_price_decimal_sep/g, 'woocommerce_price_decimal_sep_' + currencies[i]);
                                    html = html.replace(/woocommerce_price_num_decimals/g, 'woocommerce_price_num_decimals_' + currencies[i]);
                                    
                                    html = html.replace(new RegExp(symbol_default, 'g'), symbols[i]);
                                    
                                    currency_option_row.html(html);
                                    
                                    currency_option_row.find('select[name=woocommerce_currency_pos_' + currencies[i] + ']').val(options_currency_pos[i]);
                                    currency_option_row.find('input[name=woocommerce_price_thousand_sep_' + currencies[i] + ']').val(options_thousand_sep[i]);
                                    currency_option_row.find('input[name=woocommerce_price_decimal_sep_' + currencies[i] + ']').val(options_decimal_sep[i]);
                                    currency_option_row.find('input[name=woocommerce_price_num_decimals_' + currencies[i] + ']').val(options_num_decimals[i]);
                                    
                                    jQuery(this).after(currency_option_row);
                                }
                            }
                        });

                        table.find('tr').eq(0).after('<tr valign=\"top\"><td>&nbsp;</td><td>{$menu}</td></tr>');                
                        jQuery(document).on('click', '.wcml_currency_options_menu_item', function(){
                            jQuery('.wcml_currency_options_menu_item').removeClass('selected');
                            jQuery(this).addClass('selected');
                            
                            jQuery('.wcml_co_row').hide();
                            jQuery('.wcml_co_row_' + jQuery(this).data('currency')).show();
                            
                            return false;
                        });
                        
                        
                    }                        
                    
                }
                
                wcml_wc_currency_options_integration.init();
                
                
            " );                     

        }
    }
    
    function currency_options_wc_integration_save_hook(){
        global $woocommerce_wpml;
        
        if( $woocommerce_wpml->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT ){
            
            $save = false;
            
            $options = array(
                'woocommerce_currency_pos' => 'position',
                'woocommerce_price_thousand_sep' => 'thousand_sep',
                'woocommerce_price_decimal_sep' => 'decimal_sep',
                'woocommerce_price_num_decimals' => 'num_decimals'
            );

            $woocommerce_currency = get_option('woocommerce_currency', true); 

            foreach($options as $wc_key => $key){
                foreach($this->get_currencies() as $code => $currency){
                    if(isset($_POST[$wc_key.'_'. $code]) && $_POST[$wc_key.'_'. $code] != $this->currencies[$code][$key]){
                        $save = true;
                        $this->currencies[$code][$key] = $_POST[$wc_key.'_'. $code];
                    }
                }

                //update default currency
                if(isset($_POST[$wc_key]) && $_POST[$wc_key] != $this->currencies[$woocommerce_currency][$key]){
                    $save = true;
                    $this->currencies[$woocommerce_currency][$key] = $_POST[$wc_key];
                }

            }

            if($save){
                $woocommerce_wpml->settings['currency_options'] = $this->currencies;
                $woocommerce_wpml->update_settings();
                
                $this->init_currencies();
            }
            
        }
        
    }
    
    function filter_currency_position_option($value){

        $currency_code = $this->check_admin_order_currency_code();

        if(isset($this->currencies[$currency_code]['position']) && get_option('woocommerce_currency') != $currency_code &&
            in_array($this->currencies[$currency_code]['position'], array('left', 'right', 'left_space', 'right_space'))){
            $value = $this->currencies[$currency_code]['position'];
        }
        return $value;
    }
    
    function filter_view_order( $order_id ){
        $currency_code = get_post_meta( $order_id, '_order_currency', true );

        $this->client_currency = $currency_code;
    }
    
    function filter_currency_thousand_sep_option($value){

        $currency_code = $this->check_admin_order_currency_code();

        if(isset($this->currencies[$currency_code]['thousand_sep']) ){
            $value = $this->currencies[$currency_code]['thousand_sep'];
        }
        return $value;
    }
    
    function filter_currency_decimal_sep_option($value){

        $currency_code = $this->check_admin_order_currency_code();

        if(isset($this->currencies[$currency_code]['decimal_sep']) ){
            $value = $this->currencies[$currency_code]['decimal_sep'];

        }

        return $value;
    }

    function filter_currency_num_decimals_option($value){

        $currency_code = $this->check_admin_order_currency_code();

        if(isset($this->currencies[$currency_code]['num_decimals']) ){
            $value = $this->currencies[$currency_code]['num_decimals'];
        }
        return $value;
    }

    function filter_currency_num_decimals_in_cart( $cart ){
        $cart->dp = wc_get_price_decimals();
    }


    function check_admin_order_currency_code(){
        global $pagenow;

        if( ( ( is_ajax() && isset($_POST['action']) && in_array($_POST['action'],array('woocommerce_add_order_item','woocommerce_save_order_items','woocommerce_calc_line_taxes'))) || ( $pagenow == 'post-new.php' && isset( $_GET['post_type'] ) && $_GET['post_type'] == 'shop_order' ) ) && isset( $_COOKIE[ '_wcml_order_currency' ] ) ){
            $currency_code = $_COOKIE[ '_wcml_order_currency' ];
        }elseif( isset($_GET['post']) && get_post_type($_GET['post']) == 'shop_order'){
            $currency_code = get_post_meta( $_GET['post'], '_order_currency', true );
        }elseif( isset( $_COOKIE[ '_wcml_dashboard_currency' ] ) && is_admin() && !defined( 'DOING_AJAX' ) && $pagenow == 'index.php' ){
            $currency_code = $_COOKIE[ '_wcml_dashboard_currency' ];
        }else{
            $currency_code = $this->client_currency;
        }

        return apply_filters( 'wcml_filter_currency_position', $currency_code );

    }
    
    function load_inline_js(){

        wp_register_script('wcml-mc-scripts', WCML_PLUGIN_URL . '/assets/js/wcml-multi-currency.js', array('jquery'), WCML_VERSION, true);

        wp_enqueue_script('wcml-mc-scripts');

        $script_vars['wcml_mc_nonce'] = wp_create_nonce( 'switch_currency' );
        $script_vars['wcml_spinner'] = WCML_PLUGIN_URL . '/assets/images/ajax-loader.gif';

        if(isset($this->WCML_WC_MultiCurrency_W3TC)){
            $script_vars['w3tc'] = 1;
        }

        wp_localize_script('wcml-mc-scripts', 'wcml_mc_settings', $script_vars );

    }
    
    function product_price_filter($null, $object_id, $meta_key, $single){
        global $sitepress;

        static $no_filter = false;
                
        if(empty($no_filter) && in_array(get_post_type($object_id), array('product', 'product_variation'))){
            
            $price_keys = array(
                '_price', '_regular_price', '_sale_price', 
                '_min_variation_price', '_max_variation_price',                
                '_min_variation_regular_price', '_max_variation_regular_price',
                '_min_variation_sale_price', '_max_variation_sale_price');
            
            if(in_array($meta_key, $price_keys)){
                $no_filter = true;
                
                // exception for products migrated from before WCML 3.1 with independent prices
                // legacy prior 3.1
                $original_object_id = apply_filters( 'translate_object_id',$object_id, get_post_type($object_id), false, $sitepress->get_default_language());
                $ccr = get_post_meta($original_object_id, '_custom_conversion_rate', true);
                if(in_array($meta_key, array('_price', '_regular_price', '_sale_price')) && !empty($ccr) && isset($ccr[$meta_key][$this->get_client_currency()])){                    
                    $price_original = get_post_meta($original_object_id, $meta_key, $single);
                    $price = $price_original * $ccr[$meta_key][$this->get_client_currency()];
                    
                }else{
                        
                    // normal filtering                    
                    // 1. manual prices
                    $manual_prices = $this->get_product_custom_prices($object_id, $this->get_client_currency());
                    
                    if($manual_prices && !empty($manual_prices[$meta_key])){
                        
                        $price = $manual_prices[$meta_key];
                        
                    }else{
                    // 2. automatic conversion
                        $price = get_post_meta($object_id, $meta_key, $single);
                        $price = apply_filters('wcml_raw_price_amount', $price );
                        
                    }
                    
                }
                
                
                $no_filter = false;
            }
            
        }


        return !empty($price) ? $price : $null;
    }
    
    function variation_prices_filter($null, $object_id, $meta_key, $single){        
        
        if(empty($meta_key) && get_post_type($object_id) == 'product_variation'){
            static $no_filter = false;
            
            if(empty($no_filter)){
                $no_filter = true;
                
                $variation_fields = get_post_meta($object_id);
                
                $manual_prices = $this->get_product_custom_prices($object_id, $this->get_client_currency());
                
                foreach($variation_fields as $k => $v){
                    
                    if(in_array($k, array('_price', '_regular_price', '_sale_price'))){
                        
                        foreach($v as $j => $amount){
                            
                            if(isset($manual_prices[$k])){
                                $variation_fields[$k][$j] = $manual_prices[$k];     // manual price
                                
                            }else{
                                $variation_fields[$k][$j] = apply_filters('wcml_raw_price_amount', $amount );   // automatic conversion
                            }
                            
                        }
                        
                    }
                    
                }
                
                $no_filter = false;
            }
            
        }
        
        return !empty($variation_fields) ? $variation_fields : $null;
        
    }

    function get_product_custom_prices($product_id, $currency = false){
        global $wpdb, $sitepress, $woocommerce_wpml;
        
        $distinct_prices = false;
        
        if(empty($currency)){
            $currency = $this->get_client_currency();
        }
        
        $original_product_id = $product_id;
        $post_type = get_post_type($product_id);
        $product_translations = $sitepress->get_element_translations($sitepress->get_element_trid($product_id, 'post_'.$post_type), 'post_'.$post_type);
        foreach($product_translations as $translation){
            if( $translation->original ){
                $original_product_id = $translation->element_id;
                break;
            }
        }
        
        $product_meta = get_post_custom($original_product_id);
        
        $custom_prices = false;
        
        if(!empty($product_meta['_wcml_custom_prices_status'][0])){
        
            $prices_keys = array(
                '_price', '_regular_price', '_sale_price', 
                '_min_variation_price', '_max_variation_price',                
                '_min_variation_regular_price', '_max_variation_regular_price',
                '_min_variation_sale_price', '_max_variation_sale_price');
            
            foreach($prices_keys as $key){
                
                if(!empty($product_meta[$key . '_' . $currency][0])){
                    $custom_prices[$key] = $product_meta[$key . '_' . $currency][0];
                }
                
            }
        
        }
        
        if(!isset($custom_prices['_price'])) return false;
        
        $current__price_value = $custom_prices['_price'];
        
        // update sale price
        if(!empty($custom_prices['_sale_price'])){
            
            if(!empty($product_meta['_wcml_schedule_' . $currency][0])){
                // custom dates
                if(!empty($product_meta['_sale_price_dates_from_' . $currency][0]) && !empty($product_meta['_sale_price_dates_to_' . $currency][0])){
                    if(current_time('timestamp') > $product_meta['_sale_price_dates_from_' . $currency][0] && current_time('timestamp') < $product_meta['_sale_price_dates_to_' . $currency][0]){
                        $custom_prices['_price'] = $custom_prices['_sale_price'];
                    }else{
                        $custom_prices['_price'] = $custom_prices['_regular_price'];
                    }
                }else{
                    $custom_prices['_price'] = $custom_prices['_sale_price'];
                }
                
            }else{
                // inherit
                if(!empty($product_meta['_sale_price_dates_from'][0]) && !empty($product_meta['_sale_price_dates_to'][0])){
                    if(current_time('timestamp') > $product_meta['_sale_price_dates_from'][0] && current_time('timestamp') < $product_meta['_sale_price_dates_to'][0]){
                        $custom_prices['_price'] = $custom_prices['_sale_price'];
                    }else{
                        $custom_prices['_price'] = $custom_prices['_regular_price'];
                    }
                }else{
                    $custom_prices['_price'] = $custom_prices['_sale_price'];
                }
            }
            
        }
        
        if($custom_prices['_price'] != $current__price_value){
            update_post_meta($product_id, '_price_' . $currency, $custom_prices['_price']);
        }
        
        // detemine min/max variation prices        
        if(!empty($product_meta['_min_variation_price'])){
            
            static $product_min_max_prices = array();
            
            if(empty($product_min_max_prices[$product_id])){
                
                // get variation ids
                $variation_ids = $wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_parent = %d", $product_id));
                
                // variations with custom prices
                $res = $wpdb->get_results($wpdb->prepare("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE post_id IN(%s) AND meta_key='_wcml_custom_prices_status'",join(',', $variation_ids)));
                foreach($res as $row){
                    $custom_prices_enabled[$row->post_id] = $row->meta_value;
                }
                
                // REGULAR PRICES
                // get custom prices
                $res = $wpdb->get_results($wpdb->prepare("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE post_id IN(%s) AND meta_key='_regular_price_" . $currency . "'",join(',', $variation_ids)));
                foreach($res as $row){
                    $regular_prices[$row->post_id] = $row->meta_value;
                }
                
                // get default prices (default currency)
                $res = $wpdb->get_results($wpdb->prepare("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE post_id IN(%s) AND meta_key='_regular_price'",join(',', $variation_ids)));
                foreach($res as $row){
                    $default_regular_prices[$row->post_id] = $row->meta_value;
                }
                
                // include the dynamic prices
                foreach($variation_ids as $vid){
                    if(empty($regular_prices[$vid]) && isset($default_regular_prices[$vid])){
                        $regular_prices[$vid] = apply_filters('wcml_raw_price_amount', $default_regular_prices[$vid] );
                    }
                }
                
                // SALE PRICES
                // get custom prices
                $res = $wpdb->get_results($wpdb->prepare("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE post_id IN(%s) AND meta_key=%s",join(',', $variation_ids),'_sale_price_'.$currency));
                foreach($res as $row){
                    $custom_sale_prices[$row->post_id] = $row->meta_value;
                }
                
                // get default prices (default currency)
                $res = $wpdb->get_results($wpdb->prepare("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE post_id IN(%s) AND meta_key='_sale_price' AND meta_value <> ''",join(',', $variation_ids)));
                foreach($res as $row){
                    $default_sale_prices[$row->post_id] = $row->meta_value;
                }
                
                // include the dynamic prices
                foreach($variation_ids as $vid){
                    if(empty($sale_prices[$vid]) && isset($default_sale_prices[$vid])){
                        $sale_prices[$vid] = apply_filters('wcml_raw_price_amount', $default_sale_prices[$vid]);
                    }
                }
                
                
                // PRICES
                // get custom prices
                $res = $wpdb->get_results($wpdb->prepare("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE post_id IN(%s) AND meta_key=%s",join(',', $variation_ids),'_price_'.$currency));
                foreach($res as $row){
                    $custom_prices_prices[$row->post_id] = $row->meta_value;
                }
                
                // get default prices (default currency)
                $res = $wpdb->get_results($wpdb->prepare("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE post_id IN(%s) AND meta_key='_price'",join(',', $variation_ids)));
                foreach($res as $row){
                    $default_prices[$row->post_id] = $row->meta_value;
                }
                
                // include the dynamic prices
                foreach($variation_ids as $vid){
                    if(empty($custom_prices_prices[$vid]) && isset($default_prices[$vid])){
                        $prices[$vid] = apply_filters('wcml_raw_price_amount', $default_prices[$vid]);
                    }
                }
                
                if(!empty($regular_prices)){
                    $product_min_max_prices[$product_id]['_min_variation_regular_price'] = min($regular_prices);
                    $product_min_max_prices[$product_id]['_max_variation_regular_price'] = max($regular_prices);
                }
                
                if(!empty($sale_prices)){
                    $product_min_max_prices[$product_id]['_min_variation_sale_price'] = min($sale_prices);
                    $product_min_max_prices[$product_id]['_max_variation_sale_price'] = max($sale_prices);
                }
                
                if(!empty($prices)){
                    $product_min_max_prices[$product_id]['_min_variation_price'] = min($prices);
                    $product_min_max_prices[$product_id]['_max_variation_price'] = max($prices);
                }
                
                
            }
            
            if(isset($product_min_max_prices[$product_id]['_min_variation_regular_price'])){
                $custom_prices['_min_variation_regular_price'] = $product_min_max_prices[$product_id]['_min_variation_regular_price'];                    
            }
            if(isset($product_min_max_prices[$product_id]['_max_variation_regular_price'])){
                $custom_prices['_max_variation_regular_price'] = $product_min_max_prices[$product_id]['_max_variation_regular_price'];                    
            }

            if(isset($product_min_max_prices[$product_id]['_min_variation_sale_price'])){
                $custom_prices['_min_variation_sale_price'] = $product_min_max_prices[$product_id]['_min_variation_sale_price'];                    
            }
            if(isset($product_min_max_prices[$product_id]['_max_variation_sale_price'])){
                $custom_prices['_max_variation_sale_price'] = $product_min_max_prices[$product_id]['_max_variation_sale_price'];                    
            }

            if(isset($product_min_max_prices[$product_id]['_min_variation_price'])){
                $custom_prices['_min_variation_price'] = $product_min_max_prices[$product_id]['_min_variation_price'];                    
            }
            if(isset($product_min_max_prices[$product_id]['_max_variation_price'])){
                $custom_prices['_max_variation_price'] = $product_min_max_prices[$product_id]['_max_variation_price'];                    
            }
            
            
            
            
            
        }
        
        return $custom_prices; 
        
    }

    function currency_filter($currency){
        
        $currency = apply_filters('wcml_price_currency', $currency);
        
        return $currency;
    }
    
    function shipping_taxes_filter($methods){
            
        global $woocommerce;
        $woocommerce->shipping->load_shipping_methods();
        $shipping_methods = $woocommerce->shipping->get_shipping_methods();

        foreach($methods as $k => $method){

            // exceptions
            if(
                isset($shipping_methods[$method->id]) && isset($shipping_methods[$method->id]->settings['type']) && $shipping_methods[$method->id]->settings['type'] == 'percent'
                 || preg_match('/^table_rate-[0-9]+ : [0-9]+$/', $k)
            ){
                continue;
            }


            foreach($method->taxes as $j => $tax){

                $methods[$k]->taxes[$j] = apply_filters('wcml_shipping_price_amount', $methods[$k]->taxes[$j]);

            }

            if($methods[$k]->cost){

                if( isset($shipping_methods[$method->id]) && preg_match('/percent/', $shipping_methods[$method->id]->settings['cost']) ){
                    continue;
                }

                $methods[$k]->cost = apply_filters('wcml_shipping_price_amount', $methods[$k]->cost);
            }

        }

        return $methods;
    }
    
    function table_rate_shipping_rates($rates){
        
        foreach($rates as $k => $rate){
            
            $rates[$k]->rate_cost                   = apply_filters('wcml_shipping_price_amount', $rates[$k]->rate_cost);
            $rates[$k]->rate_cost_per_item          = apply_filters('wcml_shipping_price_amount', $rates[$k]->rate_cost_per_item);
            $rates[$k]->rate_cost_per_weight_unit   = apply_filters('wcml_shipping_price_amount', $rates[$k]->rate_cost_per_weight_unit);
            
        }
        
        return $rates;
    }
    
    function table_rate_instance_settings($settings){
        
        if(is_numeric($settings['handling_fee'])){
            $settings['handling_fee'] = apply_filters('wcml_shipping_price_amount', $settings['handling_fee']);            
        }
        $settings['min_cost'] = apply_filters('wcml_shipping_price_amount', $settings['min_cost']);
        
        return $settings;
    }

    function adjust_min_amount_required($options){
        
        if(!empty($options['min_amount'])){
            
            $options['min_amount'] = apply_filters('wcml_shipping_free_min_amount', $options['min_amount']);
            
        }
        
        return $options;
    }    
        
    function filter_coupon_data($coupon){

        // Alias compatibility
        if( isset( $coupon->amount ) && !isset( $coupon->coupon_amount ) ){
            $coupon->coupon_amount = $coupon->amount;
        }
        if( isset( $coupon->type ) && !isset( $coupon->discount_type ) ){
            $coupon->discount_type = $coupon->type;
        }
        //

        if($coupon->discount_type == 'fixed_cart' || $coupon->discount_type == 'fixed_product'){
            $coupon->coupon_amount = apply_filters('wcml_raw_price_amount', $coupon->coupon_amount);
        }

        $coupon->minimum_amount = apply_filters('wcml_raw_price_amount',  $coupon->minimum_amount);
        $coupon->maximum_amount = apply_filters('wcml_raw_price_amount',  $coupon->maximum_amount);

    }
    
    function get_client_currency(){
        global $woocommerce, $woocommerce_wpml, $sitepress, $wp_query, $wpdb;
        
        $default_currencies   = $woocommerce_wpml->settings['default_currencies'];
        $current_language     = $sitepress->get_current_language();
        $current_language     = ( $current_language != 'all' && !is_null( $current_language ) ) ? $current_language : $sitepress->get_default_language();
        $active_languages     = $sitepress->get_active_languages();

        if( is_product() &&
            isset($woocommerce_wpml->settings['display_custom_prices']) &&
            $woocommerce_wpml->settings['display_custom_prices'] ){

            $product_obj = wc_get_product();
            $current_product_id = $product_obj->id;
            $original_product_language = $woocommerce_wpml->products->get_original_product_language( $current_product_id );
            $default = false;

            if( $product_obj->is_type( 'variable' ) ){
                foreach( $product_obj->get_children() as $child ){
                    if( !get_post_meta( apply_filters( 'translate_object_id', $child , get_post_type( $child ), true, $original_product_language ), '_wcml_custom_prices_status', true ) ){
                        $default = true;
                        break;
                    }
                }
            }elseif( !get_post_meta( apply_filters( 'translate_object_id', $current_product_id , get_post_type( $current_product_id ), true, $original_product_language ), '_wcml_custom_prices_status', true ) ){
                $default = true;
            }

            if( $default ){
                $this->client_currency = get_option('woocommerce_currency');
            }

        }

        if( isset($_GET['pay_for_order']) && $_GET['pay_for_order'] == true && isset($_GET['key']) ){
            $order_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_order_key' AND meta_value = %s", $_GET['key']));
            if( $order_id ){
                $this->client_currency = get_post_meta( $order_id, '_order_currency', true );
            }
        }


        if(isset($_POST['action']) && $_POST['action'] == 'wcml_switch_currency' && !empty($_POST['currency'])){
           $this->client_currency = filter_input( INPUT_POST, 'currency', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        }

        if( is_null($this->client_currency) && isset($default_currencies[$current_language]) && $default_currencies[$current_language] && !empty($woocommerce->session) && $current_language != $woocommerce->session->get('client_currency_language') ){
            $this->client_currency = $default_currencies[$current_language];
        }

            // client currency in general / if enabled for this language

        if(is_null($this->client_currency) && !empty($woocommerce->session) ){
                $session_currency = $woocommerce->session->get('client_currency');
                if($session_currency && !empty($this->currencies[$session_currency]['languages'][$current_language])){
                $this->client_currency = $woocommerce->session->get('client_currency');
                }

            }

            if(is_null($this->client_currency)){
                $woocommerce_currency = get_option('woocommerce_currency');

                // fall on WC currency if enabled for this language
                if(!empty($this->currencies[$woocommerce_currency]['languages'][$current_language])){
                    $this->client_currency = $woocommerce_currency;
                }else{
                    // first currency enabled for this language
                    foreach($this->currencies as $code => $data){
                        if(!empty($data['languages'][$current_language])){
                            $this->client_currency = $code;
                        break;
                    }
                }
                }
            }

        if(!empty($woocommerce->session) && $this->client_currency){
            $woocommerce->session->set('client_currency', $this->client_currency);
            $woocommerce->session->set('client_currency_language',$current_language);
        }


        return apply_filters('wcml_client_currency', $this->client_currency);
    }

    function get_currency_details_by_code( $code ){

        if( isset( $this->currencies[ $code ] ) ){
            return $this->currencies[ $code ] ;
        }

        return false;
    }
    
    function set_client_currency($currency){
        
        global $woocommerce,$sitepress;
        $this->client_currency = $currency;

        $woocommerce->session->set('client_currency', $currency);
        $woocommerce->session->set('client_currency_language', $sitepress->get_current_language());


        do_action('wcml_set_client_currency', $currency);

    }
    
    function switch_currency(){
        $nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if(!$nonce || !wp_verify_nonce($nonce, 'switch_currency')){
            echo json_encode(array('error' => __('Invalid nonce', 'woocommerce-multilingual')));
            die();
        }

        $currency = filter_input( INPUT_POST, 'currency', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

        $this->set_client_currency($currency);
        
        // force set user cookie when user is not logged in        
        global $woocommerce, $current_user;
        if(empty($woocommerce->session->data) && empty($current_user->ID)){
            $woocommerce->session->set_customer_session_cookie(true);    
        }

        do_action('wcml_switch_currency', $currency );
        
        exit;
        
    }

    /*
     * Limitation: If the default currency is configured to display more decimals than the other currencies,
     * the prices in the secondary currencies would be approximated to the number of decimals that they have more.
    */
    function price_in_specific_currency( $return, $price, $args ){

        if(isset($args['currency']) && $this->client_currency != $args['currency']){
            remove_filter( 'wc_price', array( $this, 'price_in_specific_currency' ), 10, 3 );
            $this->client_currency = $args['currency'];
            $return = wc_price($price, $args);
            add_filter( 'wc_price', array( $this, 'price_in_specific_currency' ), 10, 3 );
        }

        return $return;

    }

    function filter_wc_price_args( $args ){

        if( isset($args['currency']) ){

            if(isset($this->currencies[$args['currency']]['decimal_sep']) ){
                $args['decimal_separator'] = $this->currencies[$args['currency']]['decimal_sep'];
            }

            if(isset($this->currencies[$args['currency']]['thousand_sep']) ){
                $args['thousand_separator'] = $this->currencies[$args['currency']]['thousand_sep'];
            }

            if(isset($this->currencies[$args['currency']]['num_decimals']) ){
                $args['decimals'] = $this->currencies[$args['currency']]['num_decimals'];
            }

            if( isset($this->currencies[$args['currency']]['position']) ){
                $current_currency = $this->client_currency;
                $this->client_currency = $args['currency'];
                $args['price_format'] = get_woocommerce_price_format();
                $this->client_currency = $current_currency;
            }

        }

        return $args;
    }

    function legacy_update_custom_rates(){

        $nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if(!$nonce || !wp_verify_nonce($nonce, 'legacy_update_custom_rates')){
            die('Invalid nonce');
        }

        foreach($_POST['posts'] as $post_id => $rates){
            
            update_post_meta($post_id, '_custom_conversion_rate', $rates);
            
        }
        
        echo json_encode(array());
        
        exit;
    }
    
    function legacy_remove_custom_rates(){

        $nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if(!$nonce || !wp_verify_nonce($nonce, 'legacy_remove_custom_rates')){
            echo json_encode(array('error' => __('Invalid nonce', 'woocommerce-multilingual')));
            die();
        }

        delete_post_meta($_POST['post_id'], '_custom_conversion_rate');
        echo json_encode(array());
        
        exit;
    }
    
    function currency_switcher_shortcode($atts){
        extract( shortcode_atts( array(), $atts ) );
    
        ob_start();
        $this->currency_switcher($atts);
        $html = ob_get_contents();
        ob_end_clean();

        return $html;
    }
    
    function currency_switcher($args = array()){
        global $sitepress, $woocommerce_wpml;

        if ( is_page( wc_get_page_id('myaccount') ) ) {
           return '';
        }

        $settings = $woocommerce_wpml->get_settings();

        if( isset($settings['display_custom_prices']) && $settings['display_custom_prices'] ){

            if( is_page( wc_get_page_id('cart') ) ||
                is_page( wc_get_page_id('checkout') ) ){
                    return '';
            }elseif ( is_product() ){
                $current_product_id = wc_get_product()->id;
                $original_product_language = $woocommerce_wpml->products->get_original_product_language( $current_product_id );

                if( !get_post_meta( apply_filters( 'translate_object_id', $current_product_id , get_post_type( $current_product_id ), true, $original_product_language ), '_wcml_custom_prices_status', true ) ){
                    return '';
                }
            }

        }

        if(!isset($args['switcher_style'])){
            $args['switcher_style'] = isset($settings['currency_switcher_style'])?$settings['currency_switcher_style']:'dropdown';
        }

        if(!isset($args['orientation'])){
            $args['orientation'] = isset($settings['wcml_curr_sel_orientation'])?$settings['wcml_curr_sel_orientation']:'vertical';
        }

        if(!isset($args['format'])){
            $args['format'] = isset($settings['wcml_curr_template']) && $settings['wcml_curr_template'] != '' ? $settings['wcml_curr_template']:'%name% (%symbol%) - %code%';
        }

        
        $wc_currencies = get_woocommerce_currencies();
                
        if(!isset($settings['currencies_order'])){
            $currencies = $this->get_currency_codes();
        }else{
            $currencies = $settings['currencies_order'];
        }
        
        if($args['switcher_style'] == 'dropdown'){
            echo '<select class="wcml_currency_switcher">';
        }else{
            $args['orientation'] = $args['orientation'] == 'horizontal'?'curr_list_horizontal':'curr_list_vertical';
            echo '<ul class="wcml_currency_switcher '.$args['orientation'].'">';
        }

        foreach($currencies as $currency){
            if($woocommerce_wpml->settings['currency_options'][$currency]['languages'][$sitepress->get_current_language()] == 1 ){

                
                $currency_format = preg_replace(array('#%name%#', '#%symbol%#', '#%code%#'),
                    array($wc_currencies[$currency], get_woocommerce_currency_symbol($currency), $currency), $args['format']);

                if($args['switcher_style'] == 'dropdown'){
                    $selected = $currency == $this->get_client_currency() ? ' selected="selected"' : '';
                    echo '<option value="' . $currency . '"' . $selected . '>' . $currency_format . '</option>';
                }else{
                    $selected = $currency == $this->get_client_currency() ? ' class="wcml-active-currency"' : '';
                    echo '<li rel="' . $currency . '" '.$selected.' >' . $currency_format . '</li>';
                }
            }
        }

        if($args['switcher_style'] == 'dropdown'){
            echo '</select>';
        }else{
            echo '</ul>';
        }

    }        
    
    function register_styles(){
        wp_register_style('currency-switcher', WCML_PLUGIN_URL . '/assets/css/currency-switcher.css', null, WCML_VERSION);
        wp_enqueue_style('currency-switcher');
    }    
    

    function filter_price_filter_results( $matched_products, $min, $max ){
        global $woocommerce_wpml,$wpdb;

        $current_currency = $this->get_client_currency();
        if( $current_currency != get_option('woocommerce_currency') ){
            $filtered_min = $woocommerce_wpml->multi_currency->unconvert_price_amount( $min, $current_currency );
            $filtered_max = $woocommerce_wpml->multi_currency->unconvert_price_amount( $max, $current_currency );

            $matched_products = $wpdb->get_results( $wpdb->prepare("
	        	SELECT DISTINCT ID, post_parent, post_type FROM $wpdb->posts
				INNER JOIN $wpdb->postmeta ON ID = post_id
				WHERE post_type IN ( 'product', 'product_variation' ) AND post_status = 'publish' AND meta_key = %s AND meta_value BETWEEN %d AND %d
			", '_price', $filtered_min, $filtered_max ), OBJECT_K );

            foreach( $matched_products as $key => $matched_product ){
                $custom_price = get_post_meta( $matched_product->ID, '_price_'.$current_currency, true );
                if( $custom_price && ( $custom_price < $min || $custom_price > $max ) ){
                    unset( $matched_products[$key] );
                }
            }
        }

        return $matched_products;
    }

    function filter_price_filter_widget_amount( $amount ){

        $current_currency = $this->get_client_currency();
        if( $current_currency != get_option('woocommerce_currency') ){
            $amount = apply_filters('wcml_raw_price_amount', $amount );
        }

        return $amount;

    }

    function filter_woocommerce_cart_contents_total( $cart_contents_total ){
        global $woocommerce;
        remove_filter( 'woocommerce_cart_contents_total', array( $this, 'filter_woocommerce_cart_contents_total'), 100 );
        $woocommerce->cart->calculate_totals();
        $cart_contents_total = $woocommerce->cart->get_cart_total();
        add_filter( 'woocommerce_cart_contents_total', array( $this, 'filter_woocommerce_cart_contents_total'), 100 );

        return $cart_contents_total;
    }

    function filter_woocommerce_cart_subtotal( $cart_subtotal, $compound, $obj ){
        global $woocommerce;
        remove_filter( 'woocommerce_cart_subtotal', array( $this, 'filter_woocommerce_cart_subtotal'), 100, 3 );
        $woocommerce->cart->calculate_totals();
        $cart_subtotal = $woocommerce->cart->get_cart_subtotal( $compound );
        add_filter( 'woocommerce_cart_subtotal', array( $this, 'filter_woocommerce_cart_subtotal'), 100, 3 );

        return $cart_subtotal;
    }

    //display variations with custom prices when "Show only products with custom prices in secondary currencies" enabled
    function filter_product_variations_with_custom_prices( $children ){
        global $woocommerce_wpml;

        if( is_product() && $woocommerce_wpml->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT &&
            isset($woocommerce_wpml->settings['display_custom_prices']) &&
            $woocommerce_wpml->settings['display_custom_prices'] ){

            foreach( $children as $key => $child ){

                $orig_lang = $woocommerce_wpml->products->get_original_product_language( $child );
                $orig_child_id = apply_filters( 'translate_object_id', $child, get_post_type( $child ), true, $orig_lang );

                if( !get_post_meta( $orig_child_id, '_wcml_custom_prices_status', true ) ){
                    unset( $children[ $key ] );
                }
            }

        }

        return $children;

    }
    
}

