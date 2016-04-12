<?php
class woocommerce_wpml {

    var $settings;

    var $currencies;
    var $products;
    var $store;
    var $emails;
    var $terms;
    var $orders;
    var $missing;

    function __construct(){

        add_action('init', array($this, 'init'),2);

        add_action('widgets_init', array($this, 'register_widget'));

    }

    function init(){
        new WCML_Upgrade;

        $this->settings = $this->get_settings();

        $this->dependencies = new WCML_Dependencies;
        add_action('admin_menu', array($this, 'menu'));

        if(!$this->dependencies->check()){
            return false;
        }

        global $sitepress,$pagenow;

        $this->load_css_and_js();

        if($this->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT
            || ( isset($_GET['page']) && $_GET['page'] == 'wpml-wcml' && !isset($_GET['tab']) )
            || ( isset( $_POST[ 'action' ] ) && in_array( $_POST[ 'action' ], array( 'wcml_new_currency', 'wcml_save_currency', 'wcml_delete_currency', 'wcml_currencies_list', 'wcml_update_currency_lang', 'wcml_update_default_currency') ) )
        ){
            require_once WCML_PLUGIN_PATH . '/inc/multi-currency-support.class.php';
            $this->multi_currency_support = new WCML_Multi_Currency_Support;
            require_once WCML_PLUGIN_PATH . '/inc/multi-currency.class.php';
            $this->multi_currency = new WCML_WC_MultiCurrency;
        }else{
            add_shortcode('currency_switcher', '__return_empty_string');
        }

        $this->endpoints         = new WCML_Endpoints;
        $this->products          = new WCML_Products;
        $this->store             = new WCML_Store_Pages;
        $this->emails            = new WCML_Emails;
        $this->terms             = new WCML_Terms;
        $this->orders            = new WCML_Orders;
        $this->troubleshooting   = new WCML_Troubleshooting();
        $this->compatibility     = new WCML_Compatibility();
        $this->strings           = new WCML_WC_Strings;
        $this->currency_switcher = new WCML_CurrencySwitcher;
        $this->xdomain_data      = new xDomain_Data;
        $this->languages_upgrader = new WCML_Languages_Upgrader;

        $this->url_translation   = new WCML_Url_Translation;



        if(isset($_GET['page']) && $_GET['page'] == 'wc-reports'){
            require_once WCML_PLUGIN_PATH . '/inc/reports.class.php';
            $this->reports          = new WCML_Reports;
        }

        include WCML_PLUGIN_PATH . '/inc/wc-rest-api-support.php';

        new WCML_Ajax_Setup;

        new WCML_Requests;

        new WCML_WooCommerce_Rest_API_Support;

        $this->install();

        add_action('init', array($this,'load_locale'));

        if(is_admin()){
            add_action('admin_footer', array($this, 'documentation_links'));
            add_action('admin_notices', array($this, 'admin_notice_after_install'));
        }

        add_filter('woocommerce_get_checkout_payment_url', array($this, 'filter_woocommerce_redirect_location'));
        add_filter('woocommerce_get_cancel_order_url', array($this, 'filter_woocommerce_redirect_location'));
        add_filter('woocommerce_get_return_url', array($this, 'filter_woocommerce_redirect_location'));
        //add_filter('woocommerce_redirect', array($this, 'filter_woocommerce_redirect_location'));

        add_filter('woocommerce_paypal_args', array($this, 'filter_paypal_args'));


        if(is_admin() &&
            (
                (isset($_GET['page']) && $_GET['page'] == 'wpml-wcml') ||
                (($pagenow == 'edit.php' || $pagenow == 'post-new.php') && isset($_GET['post_type']) && ($_GET['post_type'] == 'shop_coupon' || $_GET['post_type'] == 'shop_order')) ||
                ($pagenow == 'post.php' && isset($_GET['post']) && (get_post_type($_GET['post']) == 'shop_coupon' || get_post_type($_GET['post']) == 'shop_order')) ||
                (isset($_GET['page']) && $_GET['page'] == 'shipping_zones') || ( isset($_GET['page']) && $_GET['page'] == 'product_attributes')
            )
        ){
            remove_action( 'wp_before_admin_bar_render', array($sitepress, 'admin_language_switcher') );
        }

        if( ($pagenow == 'post.php' && isset($_GET['post']) && get_post_type($_GET['post']) == 'product' && !$this->products->is_original_product($_GET['post'])) ||
            ($pagenow == 'post-new.php' && isset($_GET['source_lang']) && isset($_GET['post_type']) && $_GET['post_type'] == 'product')
            && !$this->settings['trnsl_interface']){
            add_action('init', array($this, 'load_lock_fields_js'));
            add_action( 'admin_footer', array($this,'hidden_label'));
        }

        add_action('wp_ajax_wcml_update_setting_ajx', array($this, 'update_setting_ajx'));
        add_action( 'woocommerce_settings_save_general', array( $this, 'currency_options_update_default_currency'));
        add_filter( 'wpml_tm_dashboard_translatable_types', array( $this, 'hide_variation_type_on_tm_dashboard') );
    }

    function register_widget(){

        $settings = $this->get_settings();
        if($settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT){
            require_once WCML_PLUGIN_PATH . '/inc/currency-switcher-widget.class.php';
            register_widget('WC_Currency_Switcher_Widget');
        }

    }

    function get_settings(){

        $defaults = array(
            'file_path_sync'               => 1,
            'is_term_order_synced'         => 0,
            'enable_multi_currency'        => WCML_MULTI_CURRENCIES_DISABLED,
            'dismiss_doc_main'             => 0,
            'trnsl_interface'              => 1,
            'currency_options'             => array(),
            'currency_switcher_product_visibility'             => 1
        );

        if(empty($this->settings)){
            $this->settings = get_option('_wcml_settings');
        }

        foreach($defaults as $key => $value){
            if(!isset($this->settings[$key])){
                $this->settings[$key] = $value;
            }
        }

        return $this->settings;
    }

    function update_settings($settings = null){
        if(!is_null($settings)){
            $this->settings = $settings;
        }
        update_option('_wcml_settings', $this->settings);
    }

    function update_setting_ajx(){
        $nonce = filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if(!$nonce || !wp_verify_nonce($nonce, 'wcml_settings')){
            die('Invalid nonce');
        }

        $data = $_POST;
        $error = '';
        $html = '';

        $this->settings[$data['setting']] = $data['value'];
        $this->update_settings();

        echo json_encode(array('html' => $html, 'error'=> $error));
        exit;
    }

    function load_locale(){
        load_plugin_textdomain('woocommerce-multilingual', false, WCML_LOCALE_PATH);
    }

    function install(){
        global $wpdb;

        if(empty($this->settings['set_up'])){ // from 3.2     

            if ($this->settings['is_term_order_synced'] !== 'yes') {
                //global term ordering resync when moving to >= 3.3.x
                add_action('init', array($this->terms, 'sync_term_order_globally'), 20);
            }

            if(!isset($this->settings['wc_admin_options_saved'])){
                $this->handle_admin_texts();
                $this->settings['wc_admin_options_saved'] = 1;
            }

            if(!isset($this->settings['trnsl_interface'])){
                $this->settings['trnsl_interface'] = 1;
            }

            if(!isset($this->settings['products_sync_date'])){
                $this->settings['products_sync_date'] = 1;
            }

            if(!isset($this->settings['products_sync_order'])){
                $this->settings['products_sync_order'] = 1;
            }

            if(!isset($this->settings['display_custom_prices'])){
                $this->settings['display_custom_prices'] = 0;
            }

            self::set_up_capabilities();

            $this->set_language_information();

            $this->settings['set_up'] = 1;
            $this->update_settings();


        }

        if(empty($this->settings['downloaded_translations_for_wc'])){ //from 3.3.3
            $this->languages_upgrader->download_woocommerce_translations_for_active_languages();
            $this->settings['downloaded_translations_for_wc'] = 1;
            $this->update_settings();
        }
    }

    public static function set_up_capabilities(){

        $role = get_role( 'administrator' );
        if($role){
            $role->add_cap( 'wpml_manage_woocommerce_multilingual' );
            $role->add_cap( 'wpml_operate_woocommerce_multilingual' );
        }

        $role = get_role( 'super_admin' );
        if($role){
            $role->add_cap( 'wpml_manage_woocommerce_multilingual' );
            $role->add_cap( 'wpml_operate_woocommerce_multilingual' );
        }

        $super_admins = get_super_admins();
        foreach ($super_admins as $admin) {
            $user = new WP_User( $admin );
            $user->add_cap( 'wpml_manage_woocommerce_multilingual' );
            $user->add_cap( 'wpml_operate_woocommerce_multilingual' );
        }

        $role = get_role( 'shop_manager' );
        if($role){
            $role->add_cap( 'wpml_operate_woocommerce_multilingual' );
        }

    }

    function set_language_information(){
        global $sitepress,$wpdb;

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

    function menu(){
        if($this->dependencies->check()){
            $top_page = apply_filters('icl_menu_main_page', basename(ICL_PLUGIN_PATH) .'/menu/languages.php');

            if(current_user_can('wpml_manage_woocommerce_multilingual')){
                add_submenu_page($top_page, __('WooCommerce Multilingual', 'woocommerce-multilingual'),
                __('WooCommerce Multilingual', 'woocommerce-multilingual'), 'wpml_manage_woocommerce_multilingual', 'wpml-wcml', array($this, 'menu_content'));

                if(isset($_GET['page']) && $_GET['page'] == basename(WCML_PLUGIN_PATH).'/menu/sub/troubleshooting.php'){
                    add_submenu_page($top_page,
                        __('Troubleshooting', 'woocommerce-multilingual'), __('Troubleshooting', 'woocommerce-multilingual'),
                        'wpml_manage_troubleshooting', basename(WCML_PLUGIN_PATH).'/menu/sub/troubleshooting.php');
                }

            }else{
                global $wpdb,$sitepress_settings,$sitepress;
                $user_lang_pairs = get_user_meta(get_current_user_id(), $wpdb->prefix.'language_pairs', true);
                if( !empty( $user_lang_pairs[$sitepress->get_default_language()] ) ){
                    add_menu_page(__('WooCommerce Multilingual', 'woocommerce-multilingual'),
                        __('WooCommerce Multilingual', 'woocommerce-multilingual'), 'translate',
                        'wpml-wcml', array($this, 'menu_content'), ICL_PLUGIN_URL . '/res/img/icon16.png');
                }
            }

        }elseif(current_user_can('wpml_manage_woocommerce_multilingual')){
            if(!defined('ICL_SITEPRESS_VERSION')){
                add_menu_page( __( 'WooCommerce Multilingual', 'woocommerce-multilingual' ), __( 'WooCommerce Multilingual', 'woocommerce-multilingual' ),
                    'wpml_manage_woocommerce_multilingual', WCML_PLUGIN_PATH . '/menu/plugins.php', null, WCML_PLUGIN_URL . '/assets/images/icon16.png' );
            }else{
                $top_page = apply_filters('icl_menu_main_page', basename(ICL_PLUGIN_PATH) .'/menu/languages.php');
                add_submenu_page($top_page, __('WooCommerce Multilingual', 'woocommerce-multilingual'),
                    __('WooCommerce Multilingual', 'woocommerce-multilingual'), 'wpml_manage_woocommerce_multilingual', 'wpml-wcml', array($this, 'menu_content'));
            }

        }
    }

    function menu_content(){
        if($this->dependencies->check()){
            include WCML_PLUGIN_PATH . '/menu/management.php';
        }else{
            include WCML_PLUGIN_PATH . '/menu/plugins.php';
        }

    }

    function load_css_and_js() {
        global $pagenow;

        if(isset($_GET['page'])){

            if( in_array($_GET['page'], array('wpml-wcml',basename(WCML_PLUGIN_PATH).'/menu/sub/troubleshooting.php',basename(WCML_PLUGIN_PATH).'/menu/plugins.php'))) {


                if ( !wp_style_is( 'toolset-font-awesome', 'registered' ) ) { // check if style are already registered
                    wp_register_style('toolset-font-awesome', WCML_PLUGIN_URL . '/assets/css/font-awesome.min.css', null, WCML_VERSION); // register if not
                }

                wp_register_style('wpml-wcml', WCML_PLUGIN_URL . '/assets/css/management.css', array('toolset-font-awesome'), WCML_VERSION);
                wp_register_style('cleditor', WCML_PLUGIN_URL . '/assets/css/jquery.cleditor.css', null, WCML_VERSION);
                wp_register_script('wcml-tm-scripts', WCML_PLUGIN_URL . '/assets/js/scripts.js', array('jquery', 'jquery-ui-core', 'jquery-ui-resizable'), WCML_VERSION);
                wp_register_script('jquery-cookie', WCML_PLUGIN_URL . '/assets/js/jquery.cookie.js', array('jquery'), WCML_VERSION);
                wp_register_script('cleditor', WCML_PLUGIN_URL . '/assets/js/jquery.cleditor.min.js', array('jquery'), WCML_VERSION);

                wp_enqueue_style('toolset-font-awesome'); // enqueue styles
                wp_enqueue_style('wpml-wcml');
                wp_enqueue_style('cleditor');
                wp_enqueue_style('wp-pointer');

                wp_enqueue_media();
                wp_enqueue_script('wcml-tm-scripts');
                wp_enqueue_script('jquery-cookie');
                wp_enqueue_script('cleditor');
                wp_enqueue_script('suggest');
                wp_enqueue_script('wp-pointer');


                wp_localize_script('wcml-tm-scripts', 'wcml_settings',
                    array(
                        'nonce'             => wp_create_nonce( 'woocommerce_multilingual' )
                    )
                );

                if( $_GET['page'] == 'wpml-wcml' ){
                    //load wp-editor scripts
                    wp_enqueue_script('word-count');
                    wp_enqueue_script('editor');
                    wp_enqueue_script( 'quicktags' );
                    wp_enqueue_script( 'wplink' );
                    wp_enqueue_style( 'buttons' );
                }

                $this->load_tooltip_resources();

            }elseif( $_GET['page'] == WPML_TM_FOLDER.'/menu/main.php' ){
                wp_register_script('wpml_tm', WCML_PLUGIN_URL . '/assets/js/wpml_tm.js', array('jquery'), WCML_VERSION);
                wp_enqueue_script('wpml_tm');
            }
        }

        if( $pagenow == 'options-permalink.php' ){
            wp_register_style('wcml_op', WCML_PLUGIN_URL . '/assets/css/options-permalink.css', null, WCML_VERSION);
            wp_enqueue_style('wcml_op');
        }

        if( !is_admin() ){
            wp_register_script('cart-widget', WCML_PLUGIN_URL . '/assets/js/cart_widget.js', array('jquery'), WCML_VERSION);
            wp_enqueue_script('cart-widget');
        }
    }

    //load Tooltip js and styles from WC
    function load_tooltip_resources(){
        if( class_exists('woocommerce') ){
            wp_register_script( 'jquery-tiptip', WC()->plugin_url() . '/assets/js/jquery-tiptip/jquery.tipTip.min.js', array( 'jquery' ), WC_VERSION, true );
            wp_register_script( 'wcml-tooltip-init', WCML_PLUGIN_URL . '/assets/js/tooltip_init.js', array('jquery'), WCML_VERSION);
            wp_enqueue_script( 'jquery-tiptip' );
            wp_enqueue_script( 'wcml-tooltip-init' );
            wp_enqueue_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WC_VERSION );
            wp_enqueue_style( 'wcml_tooltip_styles', WCML_PLUGIN_URL . '/assets/css/tooltip.css', null, WCML_VERSION);
        }
    }

    function load_lock_fields_js(){
        wp_register_script('wcml-lock-script', WCML_PLUGIN_URL . '/assets/js/lock_fields.js', array('jquery'), WCML_VERSION);
        wp_enqueue_script('wcml-lock-script');

        wp_localize_script( 'wcml-lock-script', 'unlock_fields', array( 'menu_order' => $this->settings['products_sync_order'], 'file_paths' => $this->settings['file_path_sync'] ) );
    }

    function hidden_label(){
        echo '<img src="'.WCML_PLUGIN_URL.'/assets/images/locked.png" class="wcml_lock_img" alt="'.__('This field is locked for editing because WPML will copy its value from the original language.', 'woocommerce-multilingual').'" title="'.__('This field is locked for editing because WPML will copy its value from the original language.', 'woocommerce-multilingual').'" style="display: none;position:relative;left:2px;top:2px;">';

        if( isset($_GET['post']) ){
            $original_language = $this->products->get_original_product_language($_GET['post']);
            $original_id = apply_filters( 'translate_object_id',$_GET['post'],'product',true,$original_language);
        }elseif( isset($_GET['trid']) ){
            global $sitepress;
            $original_id = $sitepress->get_original_element_id_by_trid( $_GET['trid'] );
        }

        echo '<h3 class="wcml_prod_hidden_notice">'.sprintf(__("This is a translation of %s. Some of the fields are not editable. It's recommended to use the %s for translating products.", 'woocommerce-multilingual'),'<a href="'.get_edit_post_link($original_id).'" >'.get_the_title($original_id).'</a>','<a href="'.admin_url('admin.php?page=wpml-wcml&tab=products&prid='.$original_id).'" >'.__('WooCommerce Multilingual products translator', 'woocommerce-multilingual').'</a>').'</h3>';
    }

    function generate_tracking_link($link,$term=false,$content = false, $id = false){
        $params = '?utm_source=wcml-admin&utm_medium=plugin&utm_term=';
        $params .= $term?$term:'WPML';
        $params .= '&utm_content=';
        $params .= $content?$content:'required-plugins';
        $params .= '&utm_campaign=WCML';

        if($id){
            $params .= $id;
        }
        return $link.$params;
    }

    function documentation_links(){
        global $post, $pagenow;

        if( is_null( $post ) )
            return;

        $get_post_type = get_post_type($post->ID);

        if($get_post_type == 'product' && $pagenow == 'edit.php'){
            $prot_link = '<span class="button"><img align="baseline" src="' . ICL_PLUGIN_URL .'/res/img/icon.png" width="16" height="16" style="margin-bottom:-4px" /> <a href="'. $this->generate_tracking_link('http://wpml.org/documentation/related-projects/woocommerce-multilingual/','woocommerce-multilingual','documentation','#4') .'" target="_blank">' .
                    __('How to translate products', 'sitepress') . '<\/a>' . '<\/span>';
            $quick_edit_notice = '<div id="quick_edit_notice" style="display:none;"><p>'. sprintf(__("Quick edit is disabled for product translations. It\'s recommended to use the %s for editing products translations. %s", 'woocommerce-multilingual'), '<a href="'.admin_url('admin.php?page=wpml-wcml&tab=products').'" >'.__('WooCommerce Multilingual products editor', 'woocommerce-multilingual').'</a>','<a href="" class="quick_product_trnsl_link" >'.__('Edit this product translation', 'woocommerce-multilingual').'</a>').'</p></div>';
            $quick_edit_notice_prod_link = '<input type="hidden" id="wcml_product_trnsl_link" value="'.admin_url('admin.php?page=wpml-wcml&tab=products&prid=').'">';
        ?>
                <script type="text/javascript">
                    jQuery(".subsubsub").append('<?php echo $prot_link ?>');
                    jQuery(".subsubsub").append('<?php echo $quick_edit_notice ?>');
                    jQuery(".subsubsub").append('<?php echo $quick_edit_notice_prod_link ?>');
                    jQuery(".quick_hide a").on('click',function(){
                        jQuery(".quick_product_trnsl_link").attr('href',jQuery("#wcml_product_trnsl_link").val()+jQuery(this).closest('tr').attr('id').replace(/post-/,''));
                    });

                    //lock feautured for translations
                    jQuery(document).on('click', '.featured a', function(){

                        if( jQuery(this).closest('tr').find('.quick_hide').size() > 0 ){

                            return false;

                        }

                    });

                </script>
        <?php
        }

        if(isset($_GET['taxonomy'])){
            $pos = strpos($_GET['taxonomy'], 'pa_');

            if($pos !== false && $pagenow == 'edit-tags.php'){
                $prot_link = '<span class="button" style="padding:4px;margin-top:0px; float: left;"><img align="baseline" src="' . ICL_PLUGIN_URL .'/res/img/icon16.png" width="16" height="16" style="margin-bottom:-4px" /> <a href="'. $this->generate_tracking_link('http://wpml.org/documentation/related-projects/woocommerce-multilingual/','woocommerce-multilingual','documentation','#3') .'" target="_blank" style="text-decoration: none;">' .
                            __('How to translate attributes', 'sitepress') . '<\/a>' . '<\/span><br \/><br \/>';
                ?>
                        <script type="text/javascript">
                            jQuery("table.widefat").before('<?php echo $prot_link ?>');
                        </script>
                <?php
            }
        }

        if(isset($_GET['taxonomy']) && $_GET['taxonomy'] == 'product_cat'){

                $prot_link = '<span class="button" style="padding:4px;margin-top:0px; float: left;"><img align="baseline" src="' . ICL_PLUGIN_URL .'/res/img/icon16.png" width="16" height="16" style="margin-bottom:-4px" /> <a href="'. $this->generate_tracking_link('http://wpml.org/documentation/related-projects/woocommerce-multilingual/','woocommerce-multilingual','documentation','#3') .'" target="_blank" style="text-decoration: none;">' .
                            __('How to translate product categories', 'sitepress') . '<\/a>' . '<\/span><br \/><br \/>';
                ?>
                        <script type="text/javascript">
                            jQuery("table.widefat").before('<?php echo $prot_link ?>');
                        </script>
                <?php
        }
    }

    function admin_notice_after_install(){
        if( !$this->settings['dismiss_doc_main'] ){

            $url = $_SERVER['REQUEST_URI'];
            $pos = strpos($url, '?');

            if($pos !== false){
                $url .= '&wcml_action=dismiss';
            } else {
                $url .= '?wcml_action=dismiss';
            }
    ?>
            <div id="message" class="updated message fade" style="clear:both;margin-top:5px;"><p>
                <?php _e('Would you like to see a quick overview?', 'woocommerce-multilingual'); ?>
                </p>
                <p>
                <a class="button-primary" href="<?php echo $this->generate_tracking_link('http://wpml.org/documentation/related-projects/woocommerce-multilingual/','woocommerce-multilingual','documentation'); ?>" target="_blank"><?php _e('Learn how to turn your e-commerce site multilingual', 'woocommerce-multilingual') ?></a>
                <a class="button-secondary" href="<?php echo $url; ?>"><?php _e('Dismiss', 'woocommerce-multilingual') ?></a>
                </p>
            </div>
    <?php
        }
    }

    function filter_woocommerce_redirect_location($link){
        global $sitepress;
        return html_entity_decode($sitepress->convert_url($link));
    }

    function filter_paypal_args($args) {
        global $sitepress;
        $args['lc'] = $sitepress->get_current_language();

        //filter URL when default permalinks uses
        $wpml_settings = $sitepress->get_settings();
        if( $wpml_settings[ 'language_negotiation_type' ] == 3 ){
            $args[ 'notify_url' ] = str_replace( '%2F&', '&', $args[ 'notify_url' ] );
        }

        return $args;
    }


    function handle_admin_texts(){
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

    function currency_options_update_default_currency(){
        $current_currency = get_option('woocommerce_currency');
        $new_currency = $_POST['woocommerce_currency'];

        if( isset( $this->settings['currency_options'][ $current_currency ] )){
            $currency_settings =  $this->settings['currency_options'][ $current_currency ];
            unset( $this->settings['currency_options'][ $current_currency ] );
            $this->settings['currency_options'][$new_currency] = $currency_settings;
            $this->update_settings();
        }
    }

    function hide_variation_type_on_tm_dashboard( $types ){
        unset( $types['product_variation'] );

        return $types;
    }

    //get latest stable version from WC readme.txt
    function get_stable_wc_version(){
        global $woocommerce;

        $file = $woocommerce->plugin_path(). '/readme.txt';
        $values = file($file);
        $wc_info = explode( ':', $values[5] );
        if( $wc_info[0] == 'Stable tag' ){
            $version =  trim( $wc_info[1] );
        }else{
            foreach( $values as $value ){
                $wc_info = explode( ':', $value );

                if( $wc_info[0] == 'Stable tag' ){
                    $version = trim( $wc_info[1] );
                }
            }
        }

        return $version;
    }

}
