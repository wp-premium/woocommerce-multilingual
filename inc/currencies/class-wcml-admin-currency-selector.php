<?php

class WCML_Admin_Currency_Selector{
    private $woocommerce_wpml;

    public function __construct(){

        if( is_admin() ){
            add_filter('init', array($this, 'admin_currency_selector_init') );
        }


    }

    public function admin_currency_selector_init(){
        global $pagenow, $woocommerce_wpml;

        $this->woocommerce_wpml =& $woocommerce_wpml;

        //dashboard status screen
        if ( current_user_can( 'view_woocommerce_reports' ) || current_user_can( 'manage_woocommerce' ) || current_user_can( 'publish_shop_orders' ) ) {
            $this->set_dashboard_currency();

            if ( version_compare( WOOCOMMERCE_VERSION, '2.4', '<' ) && $pagenow == 'index.php' ) {
                add_action( 'admin_footer', array($this, 'show_dashboard_currency_selector') );
            } else {
                add_action( 'woocommerce_after_dashboard_status_widget', array($this, 'show_dashboard_currency_selector') );
            }

            add_action( 'wp_ajax_wcml_dashboard_set_currency', array($this, 'set_dashboard_currency_ajax') );

            add_filter( 'woocommerce_currency_symbol', array($this, 'filter_dashboard_currency_symbol') );
        }

    }

    /*
     * Add currency drop-down on dashboard page ( WooCommerce status block )
     */
    public function show_dashboard_currency_selector(){

        $current_dashboard_currency = $this->get_cookie_dashboard_currency();

        $wc_currencies = get_woocommerce_currencies();
        $order_currencies = $this->woocommerce_wpml->multi_currency->orders->get_orders_currencies();
        ?>
        <select id="dropdown_dashboard_currency" style="display: none; margin : 10px; ">

            <?php foreach($order_currencies as $currency => $count ): ?>

                <option value="<?php echo $currency ?>" <?php echo $current_dashboard_currency == $currency ? 'selected="selected"':''; ?>>
                    <?php echo $wc_currencies[$currency]; ?>
                </option>

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

    public function set_dashboard_currency_ajax(){

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
    public function set_dashboard_currency( $currency_code = false ){

        if( !$currency_code && !headers_sent()){
            $order_currencies = $this->woocommerce_wpml->multi_currency->orders->get_orders_currencies();
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
    public function get_cookie_dashboard_currency(){

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
    public function filter_dashboard_currency_symbol( $currency ){
        global $pagenow;

        remove_filter( 'woocommerce_currency_symbol', array( $this, 'filter_dashboard_currency_symbol' ) );
        if( isset( $_COOKIE [ '_wcml_dashboard_currency' ] ) && $pagenow == 'index.php' ){
            $currency = get_woocommerce_currency_symbol( $_COOKIE [ '_wcml_dashboard_currency' ] );
        }
        add_filter( 'woocommerce_currency_symbol', array( $this, 'filter_dashboard_currency_symbol' ) );

        return $currency;
    }


}