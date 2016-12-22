<?php

class WCML_Multi_Currency_Reports{

    private $woocommerce_wpml;

    private $reports_currency;

    public function __construct(){

        if( is_admin() ){
            add_filter( 'init', array( $this, 'reports_init' ) );
            add_action( 'wp_ajax_wcml_reports_set_currency', array($this, 'set_reports_currency') );

            add_action( 'wc_reports_tabs', array($this, 'reports_currency_selector') );

            if ( current_user_can( 'view_woocommerce_reports' ) ||
                 current_user_can( 'manage_woocommerce' ) ||
                 current_user_can( 'publish_shop_orders' ) ) {

                add_filter( 'woocommerce_dashboard_status_widget_sales_query', array($this, 'filter_dashboard_status_widget_sales_query') );
                add_filter( 'woocommerce_dashboard_status_widget_top_seller_query', array($this, 'filter_dashboard_status_widget_sales_query') );
            }

            add_action( 'current_screen', array($this, 'admin_screen_loaded'), 10, 1 );
        }

    }

    public function admin_screen_loaded(  $screen ){

        if( $screen->id === 'dashboard'){
            add_filter( 'woocommerce_reports_get_order_report_query', array($this, 'filter_dashboard_status_widget_sales_query') ); // woocommerce 2.6
        }

    }

    public function reports_init(){
        global $woocommerce_wpml;

        $this->woocommerce_wpml =& $woocommerce_wpml;

        if(isset($_GET['page']) && $_GET['page'] == 'wc-reports'){ //wc-reports - 2.1.x, woocommerce_reports 2.0.x

            add_filter('woocommerce_reports_get_order_report_query', array($this, 'admin_reports_query_filter'));

            $wcml_reports_set_currency_nonce = wp_create_nonce( 'reports_set_currency' );

            wc_enqueue_js( "
                jQuery('#dropdown_shop_report_currency').on('change', function(){
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
            $orders_currencies = $this->woocommerce_wpml->multi_currency->orders->get_orders_currencies();
            if(!isset($orders_currencies[$this->reports_currency])){
                $this->reports_currency = !empty($orders_currencies) ? key($orders_currencies) : false;
            }

            add_filter('woocommerce_currency_symbol', array($this, '_set_reports_currency_symbol'));
            add_filter('woocommerce_report_sales_by_category_get_products_in_category', array($this, '_use_categories_in_all_languages'), 10, 2);

        }
    }

    public function admin_reports_query_filter( $query ){
        global $wpdb;

        $query['join']  .= " LEFT JOIN {$wpdb->postmeta} AS meta_order_currency ON meta_order_currency.post_id = posts.ID ";
        $query['where'] .= sprintf(" AND meta_order_currency.meta_key='_order_currency' AND meta_order_currency.meta_value = '%s' ",
                                $this->reports_currency);

        return $query;
    }

    public function _set_reports_currency_symbol( $currency ){
        static $no_recur = false;
        if(!empty($this->reports_currency) && empty($no_recur)){
            $no_recur= true;
            $currency = get_woocommerce_currency_symbol($this->reports_currency);
            $no_recur= false;
        }
        return $currency;
    }

    public function _use_categories_in_all_languages( $product_ids, $category_id ){
        global $sitepress;

        $category_term = $this->woocommerce_wpml->terms->wcml_get_term_by_id( $category_id, 'product_cat' );

        if( !is_wp_error($category_term) ){
            $trid = $sitepress->get_element_trid( $category_term->term_taxonomy_id, 'tax_product_cat' );
            $translations = $sitepress->get_element_translations( $trid, 'tax_product_cat', true );

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

    public function set_reports_currency(){

        $nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if(!$nonce || !wp_verify_nonce($nonce, 'reports_set_currency')){
            echo json_encode( array('error' => __('Invalid nonce', 'woocommerce-multilingual') ) );
            die();
        }

        setcookie('_wcml_reports_currency', filter_input( INPUT_POST, 'currency', FILTER_SANITIZE_FULL_SPECIAL_CHARS ),
                    time() + 86400, COOKIEPATH, COOKIE_DOMAIN);

        exit;

    }

    public function reports_currency_selector(){

        $orders_currencies = $this->woocommerce_wpml->multi_currency->orders->get_orders_currencies();
        $currencies = get_woocommerce_currencies();

        // remove temporary
        remove_filter('woocommerce_currency_symbol', array($this, '_set_reports_currency_symbol'));

        ?>

        <select id="dropdown_shop_report_currency" style="margin-left:5px;">
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
        // add back
        add_filter('woocommerce_currency_symbol', array($this, '_set_reports_currency_symbol'));

    }

    /*
    * Filter WC dashboard status query
    *
    * @param string $query Query to filter
    *
    * @return string
    */
    public function filter_dashboard_status_widget_sales_query( $query ){
        global $wpdb;
        $currency = $this->woocommerce_wpml->multi_currency->admin_currency_selector->get_cookie_dashboard_currency();
        $query['where'] .= " AND posts.ID IN  ( SELECT order_currency.post_id FROM {$wpdb->postmeta} AS order_currency
                            WHERE order_currency.meta_key = '_order_currency' AND order_currency.meta_value = '{$currency}' ) ";

        return $query;
    }






}