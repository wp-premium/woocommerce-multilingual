<?php
  
// Our case:
// Muli-currency can be enabled by an option in wp_options - wcml_multi_currency_enabled
// User currency will be set in the woocommerce session as 'client_currency'
//     
  
class WCML_WC_MultiCurrency{
        
    private $client_currency;
    
    private $exchange_rates = array();
    
    private $currencies_without_cents = array('JPY', 'TWD', 'KRW', 'BIF', 'BYR', 'CLP', 'GNF', 'ISK', 'KMF', 'PYG', 'RWF', 'VUV', 'XAF', 'XOF', 'XPF');
    
    function __construct(){
        
        add_filter('init', array($this, 'init'), 5);
        add_filter('woocommerce_adjust_price', array($this, 'raw_price_filter'), 10 );
        
    }
    
    function init(){
        
        add_filter('wcml_price_currency', array($this, 'price_currency_filter'));            
        
        add_filter('wcml_raw_price_amount', array($this, 'raw_price_filter'), 10, 2);

        add_filter('wcml_formatted_price', array($this, 'formatted_price'), 10, 2);

        add_filter( 'woocommerce_get_variation_prices_hash', array( $this, 'add_currency_to_variation_prices_hash' ) );
        
        add_filter('wcml_shipping_price_amount', array($this, 'shipping_price_filter'));
        add_filter('wcml_shipping_free_min_amount', array($this, 'shipping_free_min_amount'));
        add_action('woocommerce_product_meta_start', array($this, 'currency_switcher'));
        
        add_filter('wcml_get_client_currency', array($this, 'get_client_currency'));
        add_filter('woocommerce_paypal_args', array($this, 'filter_price_woocommerce_paypal_args'));

        add_action('woocommerce_email_before_order_table', array($this, 'fix_currency_before_order_email'));
        add_action('woocommerce_email_after_order_table', array($this, 'fix_currency_after_order_email'));
        
        // orders
        if(is_admin()){
            global $wp, $pagenow;
            add_action( 'restrict_manage_posts', array($this, 'filter_orders_by_currency_dropdown'));
            $wp->add_query_var('_order_currency');
            
            add_filter('posts_join', array($this, 'filter_orders_by_currency_join'));
            add_filter('posts_where', array($this, 'filter_orders_by_currency_where'));
            
            // use correct order currency on order detail page
            add_filter('woocommerce_currency_symbol', array($this, '_use_order_currency_symbol'));
            

            //new order currency/language switchers
            add_action( 'woocommerce_process_shop_order_meta', array( $this, 'process_shop_order_meta'), 10, 2 );
            add_action( 'woocommerce_order_actions_start', array( $this, 'order_currency_dropdown' ) );

            add_filter( 'woocommerce_ajax_order_item', array( $this, 'filter_ajax_order_item' ), 10, 2 );

            add_action( 'wp_ajax_wcml_order_set_currency', array( $this, 'set_order_currency' ) );
        
            // reports
            add_action('woocommerce_reports_tabs', array($this, 'reports_currency_dropdown')); // WC 2.0.x
            add_action('wc_reports_tabs', array($this, 'reports_currency_dropdown')); // WC 2.1.x
            
            add_action('init', array($this, 'reports_init'));
            
            add_action('wp_ajax_wcml_reports_set_currency', array($this,'set_reports_currency'));

            //dashboard status screen
            if( current_user_can( 'view_woocommerce_reports' ) || current_user_can( 'manage_woocommerce' ) || current_user_can( 'publish_shop_orders' ) ){
                add_action( 'init', array( $this, 'set_dashboard_currency') );

                if( version_compare( WOOCOMMERCE_VERSION, '2.4', '<' ) && $pagenow == 'index.php' ){
                    add_action( 'admin_footer', array( $this, 'dashboard_currency_dropdown' ) );
                }else{
                    add_action( 'woocommerce_after_dashboard_status_widget', array( $this, 'dashboard_currency_dropdown' ) );
                }

                add_filter( 'woocommerce_dashboard_status_widget_sales_query', array( $this, 'filter_dashboard_status_widget_sales_query' ) );
                add_filter( 'woocommerce_dashboard_status_widget_top_seller_query', array( $this, 'filter_dashboard_status_widget_sales_query' ) );
                add_action( 'wp_ajax_wcml_dashboard_set_currency', array( $this, 'set_dashboard_currency_ajax' ) );

                add_filter('woocommerce_currency_symbol', array($this, 'filter_dashboard_currency_symbol'));
                //filter query to get order by status
                add_filter( 'query', array( $this, 'filter_order_status_query' ) );
            }

            add_action( 'woocommerce_variation_options', array( $this, 'add_individual_variation_nonce' ), 10, 3 );
        }
        

        //custom prices for different currencies for products/variations [BACKEND]
        add_action( 'woocommerce_product_options_pricing', array( $this, 'woocommerce_product_options_custom_pricing' ) );
        add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'woocommerce_product_after_variable_attributes_custom_pricing'), 10, 3 );

        add_filter('woocommerce_price_filter_widget_max_amount', array($this, 'raw_price_filter'), 99);
        add_filter('woocommerce_price_filter_widget_min_amount', array($this, 'raw_price_filter'), 99);

    }

    function raw_price_filter($price, $currency = false) {

        if( $currency === false ){
            $currency = $this->get_client_currency();
        }
        $price = $this->convert_price_amount($price, $currency);

        $price = $this->apply_rounding_rules($price, $currency);

        return $price;
        
    }

    /*
     * Converts the price from the default currency to the given currency and applies the format
     */
    function formatted_price($amount, $currency = false){
        global $woocommerce_wpml;

        if( $currency === false ){
            $currency = $this->get_client_currency();
        }

        $amount = $this->raw_price_filter($amount, $currency);

        $currency_details = $woocommerce_wpml->multi_currency_support->get_currency_details_by_code( $currency );

        switch ( $currency_details[ 'position' ] ) {
            case 'left' :
                $format = '%1$s%2$s';
                break;
            case 'right' :
                $format = '%2$s%1$s';
                break;
            case 'left_space' :
                $format = '%1$s&nbsp;%2$s';
                break;
            case 'right_space' :
                $format = '%2$s&nbsp;%1$s';
                break;
        }

        $wc_price_args = array(

            'currency'              => $currency,
            'decimal_separator'     => $currency_details['decimal_sep'],
            'thousand_separator'    => $currency_details['thousand_sep'],
            'decimals'              => $currency_details['num_decimals'],
            'price_format'          => $format,


        );

        $price = wc_price($amount, $wc_price_args);

        return $price;
    }

    function apply_rounding_rules($price, $currency = false ){
        global $woocommerce_wpml;

        if( !$currency )
        $currency = $this->get_client_currency();
        $currency_options = $woocommerce_wpml->settings['currency_options'][$currency];
        
        if($currency_options['rounding'] != 'disabled'){       
            
            if($currency_options['rounding_increment'] > 1){
                $price  = $price / $currency_options['rounding_increment'];    
            }   
             
            switch($currency_options['rounding']){
                case 'up':   
                    $rounded_price = ceil($price);
                    break;
                case 'down':
                    $rounded_price = floor($price);
                    break;
                case 'nearest':
                    if(version_compare(PHP_VERSION, '5.3.0') >= 0){
                        $rounded_price = round($price, 0, PHP_ROUND_HALF_UP);    
                    }else{
                        if($price - floor($price) < 0.5){
                            $rounded_price = floor($price);        
                        }else{
                            $rounded_price = ceil($price);                                
                        }
                    }
                    break;
            }
            
            if($rounded_price > 0){
                $price = $rounded_price;
            }
            
            if($currency_options['rounding_increment'] > 1){
                $price  = $price * $currency_options['rounding_increment'];    
            }   
        }
        
        
        if($currency_options['auto_subtract'] && $currency_options['auto_subtract'] < $price){
            $price = $price - $currency_options['auto_subtract'];
        }

        return $price;
        
    }
    
    function apply_currency_position( $price, $currency_code ){
        global $woocommerce_wpml;
        $currencies = $woocommerce_wpml->multi_currency_support->get_currencies();

        if( isset( $currencies[$currency_code]['position'] ) ){
            $position = $currencies[$currency_code]['position'];
        }else{
            remove_filter( 'option_woocommerce_currency_pos', array( $woocommerce_wpml->multi_currency_support, 'filter_currency_position_option' ) );
            $position = get_option('woocommerce_currency_pos');
            add_filter( 'option_woocommerce_currency_pos', array( $woocommerce_wpml->multi_currency_support, 'filter_currency_position_option' ) );
        }

        switch( $position ){
            case 'left': $price = sprintf( '%s%s', get_woocommerce_currency_symbol( $currency_code ), $price ); break;
            case 'right': $price = sprintf( '%s%s', $price, get_woocommerce_currency_symbol( $currency_code ) ); break;
            case 'left_space': $price = sprintf( '%s %s', get_woocommerce_currency_symbol( $currency_code ), $price ); break;
            case 'right_space': $price = sprintf( '%s %s', $price, get_woocommerce_currency_symbol( $currency_code ) ); break;
        }

        return $price;
    }
    
    function shipping_price_filter($price) {
        
        $price = $this->raw_price_filter($price, $this->get_client_currency());

        return $price;
        
    }    
    
    function shipping_free_min_amount($price) {
        
        $price = $this->raw_price_filter($price, $this->get_client_currency());
        
        return $price;
        
    }        
    
    function convert_price_amount($amount, $currency = false){

        if(empty($currency)){
            $currency = $this->get_client_currency();
        }
        
        $exchange_rates = $this->get_exchange_rates();

        if(isset($exchange_rates[$currency]) && is_numeric($amount)){
            $amount = $amount * $exchange_rates[$currency];
            
            // exception - currencies_without_cents
            if(in_array($currency, $this->currencies_without_cents)){
                
                if(version_compare(PHP_VERSION, '5.3.0') >= 0){
                    $amount = round($amount, 0, PHP_ROUND_HALF_UP);
                }else{
                    if($amount - floor($amount) < 0.5){
                        $amount = floor($amount);        
                    }else{
                        $amount = ceil($amount);                                
                    }
                }
                
            }
            
        }else{
            $amount = 0;
        }

        return $amount;        
        
    }   
    
    // convert back to default currency
    function unconvert_price_amount($amount, $currency = false){

        if(empty($currency)){
            $currency = $this->get_client_currency();
        }
        
        if($currency != get_option('woocommerce_currency')){
        
            $exchange_rates = $this->get_exchange_rates();
            
            if(isset($exchange_rates[$currency]) && is_numeric($amount)){
                $amount = $amount / $exchange_rates[$currency];
                
                // exception - currencies_without_cents
                if(in_array($currency, $this->currencies_without_cents)){
                    
                    if(version_compare(PHP_VERSION, '5.3.0') >= 0){
                        $amount = round($amount, 0, PHP_ROUND_HALF_UP);
                    }else{
                        if($amount - floor($amount) < 0.5){
                            $amount = floor($amount);        
                        }else{
                            $amount = ceil($amount);                                
                        }
                    }
                    
                }
                
            }else{
                $amount = 0;
            }
            
        }

        return $amount;        
        
    }
        
    function price_currency_filter($currency){
        
        if(isset($this->order_currency)){
            $currency = $this->order_currency;
        }else{
            $currency = $this->get_client_currency();    
        }
        
        return $currency;
    }

    function add_currency_to_variation_prices_hash($data){

        $data['currency'] = $this->get_client_currency();
        $data['exchange_rates_hash'] = md5( json_encode( $this->exchange_rates ) );

        return $data;

    }


    function get_exchange_rates(){
        global $woocommerce_wpml;
        if(empty($this->exchange_rates)){

            $this->exchange_rates = array(get_option('woocommerce_currency') => 1);
            $woo_currencies = get_woocommerce_currencies(); 
            
            $currencies = $woocommerce_wpml->multi_currency_support->get_currencies();
            foreach($currencies as $code => $currency){
                if(!empty($woo_currencies[$code])){
                    $this->exchange_rates[$code] = $currency['rate'];
                }
            }
        }

        return apply_filters('wcml_exchange_rates', $this->exchange_rates);
    }

    function currency_switcher(){
        global $woocommerce_wpml;

        $settings = $woocommerce_wpml->get_settings();

        if( is_product() && isset($settings['currency_switcher_product_visibility']) && $settings['currency_switcher_product_visibility'] === 1 ){
            echo(do_shortcode('[currency_switcher]'));
        }

    }
    
    function get_client_currency(){
        global $woocommerce, $woocommerce_wpml;
        
        $currency = $woocommerce_wpml->multi_currency_support->get_client_currency();
        
        return $currency;
        
    }
    
    function woocommerce_currency_hijack($currency){
        if(isset($this->order_currency)){
            $currency = $this->order_currency;                
        }
        return $currency;
    }
    
    // handle currency in order emails before handled in woocommerce
    function fix_currency_before_order_email($order){
        
        // backwards comp
        if(!method_exists($order, 'get_order_currency')) return;
        
        $this->order_currency = $order->get_order_currency();
        add_filter('woocommerce_currency', array($this, 'woocommerce_currency_hijack'));
    }
    
    function fix_currency_after_order_email($order){
        unset($this->order_currency);
        remove_filter('woocommerce_currency', array($this, 'woocommerce_currency_hijack'));
    }
    
    function filter_orders_by_currency_join($join){
        global $wp_query, $typenow, $wpdb;
        
        if($typenow == 'shop_order' &&!empty($wp_query->query['_order_currency'])){
            $join .= " JOIN {$wpdb->postmeta} wcml_pm ON {$wpdb->posts}.ID = wcml_pm.post_id AND wcml_pm.meta_key='_order_currency'";
        }
        
        return $join;
    }
    
    function filter_orders_by_currency_where($where){
        global $wp_query, $typenow;
        
        if($typenow == 'shop_order' &&!empty($wp_query->query['_order_currency'])){
            $where .= " AND wcml_pm.meta_value = '" . esc_sql($wp_query->query['_order_currency']) .  "'";
        }
        
        return $where;
    }
    
    function filter_orders_by_currency_dropdown(){
        global $wp_query, $typenow;
        
        if($typenow != 'shop_order') return false;
        
        $order_currencies = $this->get_orders_currencies();
        $currencies = get_woocommerce_currencies(); 
        ?>        
        <select id="dropdown_shop_order_currency" name="_order_currency">
            <option value=""><?php _e( 'Show all currencies', 'woocommerce-multilingual' ) ?></option>
            <?php foreach($order_currencies as $currency => $count): ?>            
            <option value="<?php echo $currency ?>" <?php 
                if ( isset( $wp_query->query['_order_currency'] ) ) selected( $currency, $wp_query->query['_order_currency'] ); 
                ?> ><?php printf("%s (%s) (%d)", $currencies[$currency], get_woocommerce_currency_symbol($currency), $count) ?></option>
            <?php endforeach; ?>
        </select>
        <?php
        
    }
    
    function get_orders_currencies(){
        global $wpdb;
        
        $currencies = array();
        
        $results = $wpdb->get_results("
            SELECT m.meta_value AS currency, COUNT(m.post_id) AS c
            FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON p.ID = m.post_id
            WHERE meta_key='_order_currency' AND p.post_type='shop_order'
            GROUP BY meta_value           
        ");
        
        foreach($results as $row){
            $currencies[$row->currency] = $row->c;
        }

        return $currencies;
        
        
    }
    
    function _use_order_currency_symbol($currency){
        
        if(!function_exists('get_current_screen')){
            return $currency;
        }

        $current_screen = get_current_screen();

        remove_filter('woocommerce_currency_symbol', array($this, '_use_order_currency_symbol'));
        if(!empty($current_screen) && $current_screen->id == 'shop_order'){

            $the_order = new WC_Order( get_the_ID() );
            if($the_order && method_exists($the_order, 'get_order_currency')){
                if( !$the_order->get_order_currency() && isset( $_COOKIE[ '_wcml_order_currency' ] ) ){
                    $currency =  get_woocommerce_currency_symbol($_COOKIE[ '_wcml_order_currency' ]);
                }else{
                    $currency = get_woocommerce_currency_symbol($the_order->get_order_currency());
                }
            }
            
        }elseif( isset( $_POST['action'] ) &&  in_array( $_POST['action'], array( 'woocommerce_add_order_item', 'woocommerce_calc_line_taxes', 'woocommerce_save_order_items' ) ) ){

            if( isset( $_COOKIE[ '_wcml_order_currency' ] ) ){
                $currency =  get_woocommerce_currency_symbol($_COOKIE[ '_wcml_order_currency' ]);
            }elseif( get_post_meta( $_POST['order_id'], '_order_currency' ) ){
                $currency = get_woocommerce_currency_symbol( get_post_meta( $_POST['order_id'], '_order_currency', true ) );
            }
        }
        
        add_filter('woocommerce_currency_symbol', array($this, '_use_order_currency_symbol'));

        return $currency;
    }
    
    function reports_init(){

        if(isset($_GET['page']) && ($_GET['page'] == 'woocommerce_reports' || $_GET['page'] == 'wc-reports')){ //wc-reports - 2.1.x, woocommerce_reports 2.0.x
            
            add_filter('woocommerce_reports_get_order_report_query', array($this, 'admin_reports_query_filter'));

            $wcml_reports_set_currency_nonce = wp_create_nonce( 'reports_set_currency' );

            wc_enqueue_js( "
                jQuery('#dropdown_shop_report_currency').on('change', function(){ 
                    jQuery('#dropdown_shop_report_currency_chzn').after('&nbsp;' + icl_ajxloaderimg); // WC 2.0
                    jQuery('#dropdown_shop_report_currency_chzn a.chzn-single').css('color', '#aaa'); // WC 2.0
                    jQuery('#dropdown_shop_report_currency_chosen').after('&nbsp;' + icl_ajxloaderimg);
                    jQuery('#dropdown_shop_report_currency_chosen a.chosen-single').css('color', '#aaa');
                    jQuery.ajax({
                        url: ajaxurl,
                        type: 'post',
                        data: {
                            action: 'wcml_reports_set_currency',
                            currency: jQuery('#dropdown_shop_report_currency').val(),
                            wcml_nonce: '".$wcml_reports_set_currency_nonce."'
                            },
                        success: function( response ){
                            if(typeof response.error !== 'undefined'){
                                alert(response.error);
                            }else{
                               window.location = window.location.href;
                            }
                        }
                    })
                });
            ");
            
            $this->reports_currency = isset($_COOKIE['_wcml_reports_currency']) ? $_COOKIE['_wcml_reports_currency'] : get_option('woocommerce_currency');
            //validation
            $orders_currencies = $this->get_orders_currencies();
            if(!isset($orders_currencies[$this->reports_currency])){
                $this->reports_currency = !empty($orders_currencies) ? key($orders_currencies) : false;    
            }
            
            add_filter('woocommerce_currency_symbol', array($this, '_set_reports_currency_symbol'));
            
            add_filter('woocommerce_report_sales_by_category_get_products_in_category', array($this, '_use_categories_in_all_languages'), 10, 2);

            
            /* for WC 2.0.x - start */
            add_filter('woocommerce_reports_sales_overview_order_totals_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_sales_overview_order_totals_where', array($this, 'reports_filter_by_currency_where'));

            add_filter('woocommerce_reports_sales_overview_discount_total_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_sales_overview_discount_total_where', array($this, 'reports_filter_by_currency_where'));

            add_filter('woocommerce_reports_sales_overview_shipping_total_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_sales_overview_shipping_total_where', array($this, 'reports_filter_by_currency_where'));

            add_filter('woocommerce_reports_sales_overview_order_items_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_sales_overview_order_items_where', array($this, 'reports_filter_by_currency_where'));

            add_filter('woocommerce_reports_sales_overview_orders_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_sales_overview_orders_where', array($this, 'reports_filter_by_currency_where'));
            
            add_filter('woocommerce_reports_daily_sales_orders_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_daily_sales_orders_where', array($this, 'reports_filter_by_currency_where'));

            add_filter('woocommerce_reports_monthly_sales_orders_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_monthly_sales_orders_where', array($this, 'reports_filter_by_currency_where'));

            add_filter('woocommerce_reports_monthly_sales_order_items_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_monthly_sales_order_items_where', array($this, 'reports_filter_by_currency_where'));

            add_filter('woocommerce_reports_top_sellers_order_items_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_top_sellers_order_items_where', array($this, 'reports_filter_by_currency_where'));

            add_filter('woocommerce_reports_top_earners_order_items_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_top_earners_order_items_where', array($this, 'reports_filter_by_currency_where'));
            
            add_filter('woocommerce_reports_product_sales_order_items_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_product_sales_order_items_where', array($this, 'reports_filter_by_currency_where'));

            add_filter('woocommerce_reports_coupons_overview_total_order_count_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_coupons_overview_total_order_count_where', array($this, 'reports_filter_by_currency_where'));

            add_filter('woocommerce_reports_coupons_overview_totals_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_coupons_overview_totals_where', array($this, 'reports_filter_by_currency_where'));

            add_filter('woocommerce_reports_coupons_overview_coupons_by_count_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_coupons_overview_coupons_by_count_where', array($this, 'reports_filter_by_currency_where'));
            
            add_filter('woocommerce_reports_coupons_sales_used_coupons_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_coupons_sales_used_coupons_where', array($this, 'reports_filter_by_currency_where'));

            add_filter('woocommerce_reports_coupon_sales_order_totals_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_coupon_sales_order_totals_where', array($this, 'reports_filter_by_currency_where'));

            add_filter('woocommerce_reports_customer_overview_customer_orders_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_customer_overview_customer_orders_where', array($this, 'reports_filter_by_currency_where'));

            add_filter('woocommerce_reports_customer_overview_guest_orders_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_customer_overview_guest_orders_where', array($this, 'reports_filter_by_currency_where'));


            add_filter('woocommerce_reports_monthly_taxes_gross_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_monthly_taxes_gross_where', array($this, 'reports_filter_by_currency_where'));

            add_filter('woocommerce_reports_monthly_taxes_shipping_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_monthly_taxes_shipping_where', array($this, 'reports_filter_by_currency_where'));

            add_filter('woocommerce_reports_monthly_taxes_order_tax_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_monthly_taxes_order_tax_where', array($this, 'reports_filter_by_currency_where'));

            add_filter('woocommerce_reports_monthly_taxes_shipping_tax_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_monthly_taxes_shipping_tax_where', array($this, 'reports_filter_by_currency_where'));

            add_filter('woocommerce_reports_monthly_taxes_tax_rows_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_monthly_taxes_tax_rows_where', array($this, 'reports_filter_by_currency_where'));

            add_filter('woocommerce_reports_category_sales_order_items_join', array($this, 'reports_filter_by_currency_join'));
            add_filter('woocommerce_reports_category_sales_order_items_where', array($this, 'reports_filter_by_currency_where'));
            
            /* for WC 2.0.x - end */
            
        }
    }
    
    function admin_reports_query_filter($query){
        global $wpdb;
        
        $query['join'] .= " LEFT JOIN {$wpdb->postmeta} AS meta_order_currency ON meta_order_currency.post_id = posts.ID ";
        
        $query['where'] .= sprintf(" AND meta_order_currency.meta_key='_order_currency' AND meta_order_currency.meta_value = '%s' ", $this->reports_currency);

        return $query;
    }
    
    function set_reports_currency(){

        $nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if(!$nonce || !wp_verify_nonce($nonce, 'reports_set_currency')){
            echo json_encode( array('error' => __('Invalid nonce', 'woocommerce-multilingual') ) );
            die();
        }
        
        setcookie('_wcml_reports_currency', filter_input( INPUT_POST, 'currency', FILTER_SANITIZE_FULL_SPECIAL_CHARS ), time() + 86400, COOKIEPATH, COOKIE_DOMAIN);
        
        exit;
        
    }
     
    function reports_currency_dropdown(){
        
        $orders_currencies = $this->get_orders_currencies();
        $currencies = get_woocommerce_currencies(); 
        
        // remove temporary
        remove_filter('woocommerce_currency_symbol', array($this, '_set_reports_currency_symbol'));
        
        ?>
        
        <select id="dropdown_shop_report_currency">
            <?php if(empty($orders_currencies)): ?>
                <option value=""><?php _e('Currency - no orders found', 'woocommerce-multilingual') ?></option>
            <?php else: ?>                
                <?php foreach($orders_currencies as $currency => $count): ?>
                    <option value="<?php echo $currency ?>" <?php selected( $currency, $this->reports_currency ); ?>>
                        <?php printf("%s (%s)", $currencies[$currency], get_woocommerce_currency_symbol($currency)) ?>
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        
        <?php
        wc_enqueue_js( "jQuery('select#dropdown_shop_report_currency, select[name=m]').css('width', '180px').chosen();");
        
        // add back
        add_filter('woocommerce_currency_symbol', array($this, '_set_reports_currency_symbol'));
        
    }
    
    function order_currency_dropdown($order_id){
        if( !get_post_meta( $order_id, '_order_currency') ){
            global $woocommerce_wpml, $sitepress;

            $current_order_currency = $this->get_cookie_order_currency();

            $wc_currencies = get_woocommerce_currencies();
            $currencies = $woocommerce_wpml->multi_currency_support->get_currency_codes();

            ?>
            <li class="wide">
                <label><?php _e('Order currency:'); ?></label>
                <select id="dropdown_shop_order_currency" name="wcml_shop_order_currency">

                    <?php foreach($currencies as $currency): ?>

                        <option value="<?php echo $currency ?>" <?php echo $current_order_currency == $currency ? 'selected="selected"':''; ?>><?php echo $wc_currencies[$currency]; ?></option>

                    <?php endforeach; ?>

                </select>
            </li>
        <?php
            $wcml_order_set_currency_nonce = wp_create_nonce( 'set_order_currency' );

            wc_enqueue_js( "
                var order_currency_current_value = jQuery('#dropdown_shop_order_currency option:selected').val();

                jQuery('#dropdown_shop_order_currency').on('change', function(){

                    if(confirm('" . esc_js(__("All the products will be removed from the current order in order to change the currency", 'woocommerce-multilingual')). "')){
                        jQuery.ajax({
                            url: ajaxurl,
                            type: 'post',
                            dataType: 'json',
                            data: {
                                action: 'wcml_order_set_currency',
                                currency: jQuery('#dropdown_shop_order_currency option:selected').val(),
                                wcml_nonce: '".$wcml_order_set_currency_nonce."'
                                },
                            success: function( response ){
                                if(typeof response.error !== 'undefined'){
                                    alert(response.error);
                                }else{
                                   window.location = window.location.href;
                                }
                            }
                        });
                    }else{
                        jQuery(this).val( order_currency_current_value );
                        return false;
                    }

                });

            ");

        }

    }

    function set_order_currency(){
        $nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if(!$nonce || !wp_verify_nonce($nonce, 'set_order_currency')){
            echo json_encode(array('error' => __('Invalid nonce', 'woocommerce-multilingual')));
            die();
        }
        $currency = filter_input( INPUT_POST, 'currency', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

        setcookie('_wcml_order_currency', $currency, time() + 86400, COOKIEPATH, COOKIE_DOMAIN);

        $return['currency'] = $currency;

        echo json_encode($return);

        die();
    }

    function get_cookie_order_currency(){

        if( isset( $_COOKIE[ '_wcml_order_currency' ] ) ){
            return $_COOKIE['_wcml_order_currency'] ;
        }else{
            return get_option('woocommerce_currency');
        }

    }

    function process_shop_order_meta( $post_id, $post ){

        if( isset( $_POST['wcml_shop_order_currency'] ) ){
            update_post_meta( $post_id, '_order_currency', filter_input( INPUT_POST, 'wcml_shop_order_currency', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) );
        }

    }

    function filter_ajax_order_item( $item, $item_id ){
        if( !get_post_meta( $_POST['order_id'], '_order_currency') ){
            $order_currency = $this->get_cookie_order_currency();
        }else{
            $order_currency = get_post_meta( $_POST['order_id'], '_order_currency', true);
        }

        $custom_price = get_post_meta( $_POST['item_to_add'], '_price_'.$order_currency, true );

        if( $custom_price ){
            $item['line_subtotal'] = $custom_price;
            $item['line_total'] = $custom_price;
        }else{
            $item['line_subtotal'] = $this->raw_price_filter( $item['line_subtotal'], $order_currency );
            $item['line_total'] = $this->raw_price_filter( $item['line_total'], $order_currency );
        }

        wc_update_order_item_meta( $item_id, '_line_subtotal', $item['line_subtotal'] );
        $item['line_subtotal_tax'] = $this->convert_price_amount( $item['line_subtotal_tax'], $order_currency );
        wc_update_order_item_meta( $item_id, '_line_subtotal_tax', $item['line_subtotal_tax'] );
        wc_update_order_item_meta( $item_id, '_line_total', $item['line_total'] );
        $item['line_tax'] = $this->convert_price_amount( $item['line_tax'], $order_currency );
        wc_update_order_item_meta( $item_id, '_line_tax', $item['line_tax'] );

        return $item;
    }
    
    function _set_reports_currency_symbol($currency){
        static $no_recur = false;        
        if(!empty($this->reports_currency) && empty($no_recur)){
            $no_recur= true;
            $currency = get_woocommerce_currency_symbol($this->reports_currency);
            $no_recur= false;
        }
        return $currency;
    }
    
    function _use_categories_in_all_languages($product_ids, $category_id){
        global $sitepress, $woocommerce_wpml;
        
        $category_term = $woocommerce_wpml->products->wcml_get_term_by_id( $category_id, 'product_cat' );
        
        if(!is_wp_error($category_term)){
            $trid = $sitepress->get_element_trid($category_term->term_taxonomy_id, 'tax_product_cat');
            $translations = $sitepress->get_element_translations($trid, 'tax_product_cat', true);
            
            foreach($translations as $translation){
                if($translation->term_id != $category_id){
                    $term_ids    = get_term_children( $translation->term_id, 'product_cat' );
                    $term_ids[]  = $translation->term_id;
                    $product_ids = array_merge(array_unique($product_ids), get_objects_in_term( $term_ids, 'product_cat' ));
                }
            }
        }
        
        return $product_ids;
    }
     
    function woocommerce_product_options_custom_pricing(){
        global $pagenow,$sitepress,$woocommerce_wpml;

        $this->load_custom_prices_js_css();

        if( ( isset($_GET['post'] ) && ( get_post_type($_GET['post']) != 'product' || !$woocommerce_wpml->products->is_original_product( $_GET['post'] ) ) ) ||
            ( isset($_GET['post_type'] ) && $_GET['post_type'] == 'product' && isset( $_GET['source_lang'] ) ) ){
            return;
        }

        $product_id = 'new';

        if($pagenow == 'post.php' && isset($_GET['post']) && get_post_type($_GET['post']) == 'product'){
            $product_id = $_GET['post'];
        }

        $this->custom_pricing_output($product_id);

        wp_nonce_field('wcml_save_custom_prices','_wcml_custom_prices_nonce');

    }

    function custom_pricing_output($post_id = false){
        global $woocommerce,$woocommerce_wpml;

        $custom_prices = array();
        $is_variation = false;

        if($post_id){
            $custom_prices = get_post_custom($post_id);
            if(get_post_type($post_id) == 'product_variation'){
                $is_variation = true;
                }
            }

        include WCML_PLUGIN_PATH . '/menu/sub/custom-prices.php';
    }

    function add_individual_variation_nonce($loop, $variation_data, $variation){

        wp_nonce_field('wcml_save_custom_prices_variation_' . $variation->ID, '_wcml_custom_prices_variation_' . $variation->ID . '_nonce');

    }

    function load_custom_prices_js_css(){
        wp_register_style('wpml-wcml-prices', WCML_PLUGIN_URL . '/assets/css/wcml-prices.css', null, WCML_VERSION);
        wp_register_script('wcml-tm-scripts-prices', WCML_PLUGIN_URL . '/assets/js/prices.js', array('jquery'), WCML_VERSION);

        wp_enqueue_style('wpml-wcml-prices');
        wp_enqueue_script('wcml-tm-scripts-prices');
    }


    function woocommerce_product_after_variable_attributes_custom_pricing($loop, $variation_data, $variation){
        global $woocommerce_wpml;

        if( $woocommerce_wpml->products->is_original_product( $variation->post_parent ) ) {

            echo '<tr><td>';
            $this->custom_pricing_output( $variation->ID );
            echo '</td></tr>';

        }

    }


    /*
     * Filter WC dashboard status query
     *
     * @param string $query Query to filter
     *
     * @return string
     */
    function filter_dashboard_status_widget_sales_query( $query ){
        global $wpdb;
        $currency = $this->get_cookie_dashboard_currency();
        $query['where'] .= " AND posts.ID IN  ( SELECT order_currency.post_id FROM {$wpdb->postmeta} AS order_currency WHERE order_currency.meta_key = '_order_currency' AND order_currency.meta_value = '{$currency}' ) ";

        return $query;
    }

    /*
     * Add currency drop-down on dashboard page ( WooCommerce status block )
     */
    function dashboard_currency_dropdown(){
        global $woocommerce_wpml, $sitepress;

        $current_dashboard_currency = $this->get_cookie_dashboard_currency();

        $wc_currencies = get_woocommerce_currencies();
        $order_currencies = $this->get_orders_currencies();
        ?>
            <select id="dropdown_dashboard_currency" style="display: none; margin : 10px; ">

                <?php foreach($order_currencies as $currency => $count ): ?>

                    <option value="<?php echo $currency ?>" <?php echo $current_dashboard_currency == $currency ? 'selected="selected"':''; ?>><?php echo $wc_currencies[$currency]; ?></option>

                <?php endforeach; ?>

            </select>
        <?php

        $wcml_dashboard_set_currency_nonce = wp_create_nonce( 'dashboard_set_currency' );

        wc_enqueue_js( "

            jQuery(document).ready(function(){

                var dashboard_dropdown = jQuery('#dropdown_dashboard_currency').clone();
                jQuery('#dropdown_dashboard_currency').remove();
                dashboard_dropdown.insertBefore('.sales-this-month a').show();
                jQuery('#woocommerce_dashboard_status .wc_status_list li').css('display','table');

            });

            jQuery(document).on('change', '#dropdown_dashboard_currency', function(){
               jQuery.ajax({
                    url: ajaxurl,
                    type: 'post',
                    data: {
                        action: 'wcml_dashboard_set_currency',
                        currency: jQuery('#dropdown_dashboard_currency').val(),
                        wcml_nonce: '".$wcml_dashboard_set_currency_nonce."'
                    },
                    success: function( response ){
                        if(typeof response.error !== 'undefined'){
                            alert(response.error);
                        }else{
                           window.location = window.location.href;
                        }
                    }
                })
            });
        ");
    }

    function set_dashboard_currency_ajax(){

        $nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if(!$nonce || !wp_verify_nonce($nonce, 'dashboard_set_currency')){
            echo json_encode(array('error' => __('Invalid nonce', 'woocommerce-multilingual')));
            die();
        }

        $this->set_dashboard_currency(filter_input( INPUT_POST, 'currency', FILTER_SANITIZE_FULL_SPECIAL_CHARS ));

        die();
    }


    /*
     * Set dashboard currency cookie
     */
    function set_dashboard_currency( $currency_code = false ){

        if( !$currency_code && !headers_sent()){
            $order_currencies = $this->get_orders_currencies();
            $currency_code = get_woocommerce_currency();
            if(!isset($order_currencies[$currency_code])){
                foreach( $order_currencies as $currency_code => $count ){
                    $currency_code = $currency_code;
                    break;
                }
            }
        }

        setcookie('_wcml_dashboard_currency', $currency_code , time() + 86400, COOKIEPATH, COOKIE_DOMAIN);

    }

    /*
     * Get dashboard currency cookie
     *
     * @return string
     *
     */
    function get_cookie_dashboard_currency(){

        if( isset( $_COOKIE [ '_wcml_dashboard_currency' ] ) ){
            $currency = $_COOKIE[ '_wcml_dashboard_currency' ];
        }else{
            $currency = get_woocommerce_currency();
        }

        return $currency;
    }

    /*
     * Filter currency symbol on dashboard page
     *
     * @param string $currency Currency code
     *
     * @return string
     *
     */
    function filter_dashboard_currency_symbol( $currency ){
        global $pagenow;

        remove_filter( 'woocommerce_currency_symbol', array( $this, 'filter_dashboard_currency_symbol' ) );
        if( isset( $_COOKIE [ '_wcml_dashboard_currency' ] ) && $pagenow == 'index.php' ){
            $currency = get_woocommerce_currency_symbol( $_COOKIE [ '_wcml_dashboard_currency' ] );
        }
        add_filter( 'woocommerce_currency_symbol', array( $this, 'filter_dashboard_currency_symbol' ) );

        return $currency;
    }


    /*
    * Filter status query
    *
    * @param string $query
    *
    * @return string
    *
    */
    function filter_order_status_query( $query ){
        global $pagenow,$wpdb;

        if( $pagenow == 'index.php' ){
            $sql = "SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts} WHERE post_type = 'shop_order' GROUP BY post_status";

            if( $query == $sql){

                $currency = $this->get_cookie_dashboard_currency();
                $query = "SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts} WHERE post_type = 'shop_order' AND ID IN  ( SELECT order_currency.post_id FROM {$wpdb->postmeta} AS order_currency WHERE order_currency.meta_key = '_order_currency' AND order_currency.meta_value = '{$currency}' ) GROUP BY post_status";

            }
        }

        return $query;
    }

    /* for WC 2.0.x - start */    
    function reports_filter_by_currency_join($join){
        global $wpdb;
        
        $join .= " LEFT JOIN {$wpdb->postmeta} wcml_rpm ON wcml_rpm.post_id = posts.ID ";

        return $join;
    }
    
    function reports_filter_by_currency_where($where){
        
        $where .= " AND wcml_rpm.meta_key = '_order_currency' AND wcml_rpm.meta_value = '" . esc_sql($this->reports_currency) . "'";

        return $where;
    }
    /* for WC 2.0.x - end */


    function filter_price_woocommerce_paypal_args( $args ){
        global $woocommerce_wpml;

        foreach( $args as $key => $value ){
            if( substr( $key, 0, 7 ) == 'amount_' ){

                $currency_details = $woocommerce_wpml->multi_currency_support->get_currency_details_by_code( $args['currency_code'] );

                $args[ $key ] =  number_format( $value, $currency_details['num_decimals'], '.', '' );
            }
        }

        return $args;
    }

}

