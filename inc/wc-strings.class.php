<?php

class WCML_WC_Strings{

    private $translations_from_mo_file = array();
    private $mo_files = array();
    private $current_language;

    function __construct(){

        add_action( 'init', array( $this, 'init' ) );
        add_action( 'init', array( $this, 'pre_init' ) );
        add_filter( 'query_vars', array( $this, 'translate_query_var_for_product' ) );
        add_filter( 'wp_redirect', array( $this, 'encode_shop_slug' ), 10, 2 );
        add_action( 'registered_taxonomy', array ( $this, 'translate_attributes_label_in_wp_taxonomies' ), 100, 3 );
        $this->payment_gateways_filters();
        $this->shipping_methods_filters();

        add_action('wp_ajax_woocommerce_shipping_zone_methods_save_settings', array( $this, 'save_shipping_zone_method_from_ajax'), 9);
    }

    function payment_gateways_filters( ){

        $payment_gateways = WC()->payment_gateways()->payment_gateways;

        foreach ( $payment_gateways as $gateway ) {
            if( isset( $gateway->id ) ){
                $gateway_id = $gateway->id;
            }else{
                continue;
            }
            add_filter( 'woocommerce_settings_api_sanitized_fields_'.$gateway_id, array( $this, 'register_gateway_strings' ) );
            add_filter( 'option_woocommerce_'.$gateway_id.'_settings', array( $this, 'translate_gateway_strings' ), 9, 2 );
        }
    }

    function shipping_methods_filters( ){

        $shipping_methods = WC()->shipping->get_shipping_methods();

        foreach ( $shipping_methods as $shipping_method ) {
            if( isset( $shipping_method->id ) ){
                $shipping_method_id = $shipping_method->id;
            }else{
                continue;
            }

            if( ( defined('WC_VERSION') && version_compare( WC_VERSION , '2.6', '<' ) ) ){
                add_filter( 'woocommerce_settings_api_sanitized_fields_'.$shipping_method_id, array( $this, 'register_shipping_strings' ) );
            }else{
                add_filter( 'woocommerce_shipping_' . $shipping_method_id . '_instance_settings_values', array( $this, 'register_zone_shipping_strings' ),9,2 );
            }

            add_filter( 'option_woocommerce_'.$shipping_method_id.'_settings', array( $this, 'translate_shipping_strings' ), 9, 2 );
        }
    }

    function save_shipping_zone_method_from_ajax(){
        foreach( $_POST['data'] as $key => $value ){
            if( strstr( $key, '_title' ) ){
                $shipping_id = str_replace( 'woocommerce_', '', $key );
                $shipping_id = str_replace( '_title', '', $shipping_id );
                $this->register_shipping_title( $shipping_id.$_POST['instance_id'], $value );
                break;
            }
        }
    }

    function register_zone_shipping_strings( $instance_settings, $object ){
        if( !empty( $instance_settings['title'] ) ){
            $this->register_shipping_title( $object->id.$object->instance_id, $instance_settings['title'] );
        }
        return $instance_settings;
    }

    function pre_init(){
        // Slug translation
        if( !WPML_SUPPORT_STRINGS_IN_DIFF_LANG ){
            add_filter('gettext_with_context', array($this, 'translate_default_slug'), 2, 4);
        }

    }
    
    function init(){
        global $pagenow, $sitepress;

        $this->current_language = $sitepress->get_current_language();
        if( $this->current_language == 'all' ){
            $this->current_language = $sitepress->get_default_language();
        }

        add_filter('woocommerce_package_rates', array($this, 'translate_shipping_methods_in_package'));
        add_action('woocommerce_tax_rate_added', array($this, 'register_tax_rate_label_string'), 10, 2 );
        add_filter('woocommerce_rate_label',array($this,'translate_woocommerce_rate_label'));

        add_filter('woocommerce_gateway_title', array($this, 'translate_gateway_title'), 10, 2);
        add_filter('woocommerce_gateway_description', array($this, 'translate_gateway_description'), 10, 2);

        //translate attribute label
        add_filter('woocommerce_attribute_label',array($this,'translated_attribute_label'),10,3);
        add_filter('woocommerce_cart_item_name',array($this,'translated_cart_item_name'),10,3);
        add_filter('woocommerce_checkout_product_title',array($this,'translated_checkout_product_title'),10,2);

        if(is_admin() && $pagenow == 'options-permalink.php'){
            add_filter( 'gettext_with_context', array( $this, 'category_base_in_strings_language' ), 99, 3 );

            if( WPML_SUPPORT_STRINGS_IN_DIFF_LANG ) {
                add_action( 'admin_footer', array( $this, 'show_custom_url_base_translation_links' ) );
            }

            add_action('admin_footer', array($this, 'show_custom_url_base_language_requirement'));
        }

        if(is_admin() && $pagenow == 'admin.php' && isset($_GET['page']) && $_GET['page'] == 'wc-settings'){
            add_action('admin_footer', array($this, 'show_language_notice_for_wc_settings'));
        }
        
        if(is_admin() && $pagenow == 'edit.php' && isset($_GET['page']) && $_GET['page'] == 'woocommerce_attributes'){
            add_action('admin_footer', array($this, 'show_attribute_label_language_warning'));    
        }

        add_action( 'woocommerce_product_options_attributes', array ( $this, 'notice_after_woocommerce_product_options_attributes' ) );

        add_filter( 'woocommerce_attribute_taxonomies', array( $this, 'translate_attribute_taxonomies_labels') );

        add_filter('woocommerce_get_breadcrumb', array($this, 'filter_woocommerce_breadcrumbs' ), 10, 2 );
    }

    function translated_attribute_label($label, $name, $product_obj = false){
        global $sitepress,$product;

        $product_id = false;
        $lang = $sitepress->get_current_language();
        $name = sanitize_title($name);

        if( isset($product->id) ){
            $product_id = $product->id;
        }elseif( is_numeric( $product_obj ) ){
            $product_id = $product_obj;
        }elseif( isset($product_obj->id) ){
            $product_id = $product_obj->id;
        }

        if( $product_id ){

            $custom_attr_translation =  get_post_meta( $product_id, 'attr_label_translations', true ) ;

            if( $custom_attr_translation ){
                if( isset( $custom_attr_translation[$lang][$name] ) ){
                    return  $custom_attr_translation[$lang][$name];
                }
            }

        }

        if(is_admin() && !wpml_is_ajax()){
            global $sitepress_settings;

            $string_language = $this->get_string_language( 'taxonomy singular name: '.$label, 'WordPress' );

            if($sitepress_settings['admin_default_language'] != $string_language){
                $string_id = icl_get_string_id( 'taxonomy singular name: '.$label, 'WordPress' );
                $strings = icl_get_string_translations_by_id($string_id);
                if($strings){
                    return $strings[$sitepress_settings['admin_default_language']]['value'];
                }
            }else{
                return $label;
            }

        }

        $trnsl_label = apply_filters( 'wpml_translate_single_string', $label, 'WordPress', 'taxonomy singular name: '.$label, $lang );

        if( $label != $trnsl_label ){
            return $trnsl_label;
        }

        // backward compatibility for WCML < 3.6.1
        $trnsl_labels = get_option('wcml_custom_attr_translations');

        if( isset( $trnsl_labels[$lang][$name] ) && !empty( $trnsl_labels[$lang][$name] ) ){
            return $trnsl_labels[$lang][$name];
        }

        return $label;
    }

    function translated_cart_item_name($title, $values, $cart_item_key){

        if($values){

            $parent = $values['data']->post->post_parent;
            $tr_product_id = apply_filters( 'translate_object_id', $values['product_id'], 'product', true );
            $trnsl_title = get_the_title($tr_product_id);
            
            if($parent){
                $tr_parent = apply_filters( 'translate_object_id', $parent, 'product', true );
                $trnsl_title = get_the_title( $tr_parent ) . ' &rarr; ' . $trnsl_title;
            }

            if( strstr( $title,'</a>' ) ){
                $trnsl_title = sprintf( '<a href="%s">%s</a>', $values['data']->get_permalink(), $trnsl_title );
            }else{
                $trnsl_title = $trnsl_title. '&nbsp;';
            }

              $title = $trnsl_title;
        }

        return $title;
    }

    function translated_checkout_product_title($title,$product){

        if(isset($product->id)){
            $tr_product_id = apply_filters( 'translate_object_id', $product->id, 'product', true, $this->current_language );
            $title = get_the_title($tr_product_id);
        }

        return $title;
    }
    
    
    function translate_query_var_for_product($public_query_vars){
        global $woocommerce_wpml, $sitepress;

        $product_permalink  = $this->product_permalink_slug();
        $string_language = $this->get_string_language( $product_permalink, $woocommerce_wpml->url_translation->url_strings_context(), $woocommerce_wpml->url_translation->url_string_name('product') );

        if($sitepress->get_current_language() != $string_language){
            $translated_slug = $this->get_translated_product_base_by_lang( false,$product_permalink );
            
            if(isset($_GET[$translated_slug])){
                $buff = $_GET[$translated_slug];
                unset($_GET[$translated_slug]);
                $_GET[$product_permalink] = $buff;
            }
            
        }
        
        return $public_query_vars;
    }
    
    function get_translated_product_base_by_lang($language = false, $product_slug = false){
        global $woocommerce_wpml;

        if(!$language){
            global $sitepress;
            $language = $sitepress->get_current_language();

        }

        if(!$product_slug){
            $product_slug  = $this->product_permalink_slug();
        }

        if ( version_compare( WPML_ST_VERSION, '2.3', '>=' ) ) {
            $translated_slug = apply_filters( 'wpml_get_translated_slug', $product_slug, 'product' , $language );
        }elseif ( apply_filters( 'wpml_slug_translation_available', false) ) {
            $translated_slug = apply_filters( 'wpml_get_translated_slug', 'product' , $language );
        } else {
            $translated_slug = apply_filters( 'wpml_translate_single_string', $product_slug, $woocommerce_wpml->url_translation->url_strings_context(), $woocommerce_wpml->url_translation->url_string_name( 'product' ) );
        }

        return $translated_slug;
    }

    // Catch the default slugs for translation
    function translate_default_slug($translation, $text, $context, $domain) {
        global $sitepress, $woocommerce_wpml;

        if ($context == 'slug' || $context == 'default-slug') {
            $wc_slug = $woocommerce_wpml->url_translation->get_woocommerce_product_base();
            if(is_admin()){
                $admin_language = $sitepress->get_admin_language();
            }
            $current_language = $sitepress->get_current_language();

            $strings_language = $this->get_domain_language('woocommerce');

            if ($text == $wc_slug && $domain == 'woocommerce' && $strings_language) {
                $sitepress->switch_lang($strings_language);
                $translation = _x($text, 'URL slug', $domain);
                $sitepress->switch_lang($current_language);
                if(is_admin()){
                    $sitepress->set_admin_language($admin_language);
                }
            }else{
               $translation = $text;
            }

            if(!is_admin()){
                $sitepress->switch_lang($current_language);
            }
        }

        return $translation;

    }

    function register_shipping_strings( $fields ){
        $shipping = WC_Shipping::instance();

        foreach( $shipping->get_shipping_methods() as $shipping_method ){
            if( isset( $_POST['woocommerce_'.$shipping_method->id.'_enabled'] ) ){
                $shipping_method_id = $shipping_method->id;
                break;
            }
        }

        if( isset( $shipping_method_id ) ){
            $this->register_shipping_title( $shipping_method_id, $fields['title'] );
        }

        return $fields;
    }

    function register_shipping_title( $shipping_method_id, $title ){
        do_action( 'wpml_register_single_string', 'woocommerce', $shipping_method_id .'_shipping_method_title', $title );
    }

    function translate_shipping_strings( $value, $option = false ){

        if( $option && isset( $value['enabled']) && $value['enabled'] == 'no' ){
            return $value;
        }

        $shipping_id = str_replace( 'woocommerce_', '', $option );
        $shipping_id = str_replace( '_settings', '', $shipping_id );

        if( isset( $value['title'] ) ){
            $value['title'] = $this->translate_shipping_method_title( $value['title'], $shipping_id );
        }

        return $value;
    }

    function translate_shipping_methods_in_package( $available_methods ){

        foreach($available_methods as $key => $method){
            $method->label =  $this->translate_shipping_method_title( $method->label, $key );
        }

        return $available_methods;
    }

    function translate_shipping_method_title( $title, $shipping_id ) {

        $title = apply_filters( 'wpml_translate_single_string', $title, 'woocommerce', $shipping_id .'_shipping_method_title', $this->current_language );

        return $title;
    }

    function translate_woocommerce_rate_label( $label ){

        $label = apply_filters( 'wpml_translate_single_string', $label, 'woocommerce taxes', $label );

        return $label;
    }

    function register_tax_rate_label_string( $id, $tax_rate ){

        if( !empty( $tax_rate['tax_rate_name'] ) ){
            do_action('wpml_register_single_string', 'woocommerce taxes', $tax_rate['tax_rate_name'] , $tax_rate['tax_rate_name'] );
        }

    }

    function register_gateway_strings( $fields ){

        $wc_payment_gateways = WC_Payment_Gateways::instance();

        foreach( $wc_payment_gateways->payment_gateways() as $gateway ){
            if( isset( $_POST['woocommerce_'.$gateway->id.'_enabled'] ) ){
                $gateway_id = $gateway->id;
                break;
            }
        }

        if( isset( $gateway_id ) ){
            do_action('wpml_register_single_string', 'woocommerce', $gateway_id .'_gateway_title', $fields['title'] );

            if( isset( $fields['description'] ) ) {
                do_action('wpml_register_single_string', 'woocommerce', $gateway_id . '_gateway_description', $fields['description']);
            }

            if( isset( $fields['instructions'] ) ){
                do_action('wpml_register_single_string', 'woocommerce', $gateway_id .'_gateway_instructions', $fields['instructions']  );
            }
        }

        return $fields;
    }


    function translate_gateway_strings( $value, $option = false ){

        if( $option && isset( $value['enabled']) && $value['enabled'] == 'no' ){
            return $value;
        }

        $gateway_id = str_replace( 'woocommerce_', '', $option );
        $gateway_id = str_replace( '_settings', '', $gateway_id );

        if( isset( $value['instructions'] ) ){
            $value['instructions'] = $this->translate_gateway_instructions( $gateway_id, $value['instructions'] );
        }

        if( isset( $value['description'] ) ){
            $value['description'] = $this->translate_gateway_description( $value['description'], $gateway_id );
        }

        if( isset( $value['title'] ) ){
            $value['title'] = $this->translate_gateway_title( $value['title'], $gateway_id );
        }

        return $value;

    }

    function translate_gateway_title($title, $gateway_title) {
        $title = apply_filters( 'wpml_translate_single_string', $title, 'woocommerce', $gateway_title .'_gateway_title', $this->current_language );
        return $title;
    }

    function translate_gateway_description( $description, $gateway_title) {
        $description = apply_filters( 'wpml_translate_single_string', $description, 'woocommerce', $gateway_title . '_gateway_description', $this->current_language );
        return $description;
    }

    function translate_gateway_instructions( $id, $instructions){
        $instructions = apply_filters( 'wpml_translate_single_string', $instructions, 'woocommerce', $id . '_gateway_instructions', $this->current_language );
        return $instructions;
    }

    function show_custom_url_base_language_requirement(){
        $this->string_language_notice();
        $category_base = ($c = get_option('category_base') ) ? $c : 'category';
        ?>
        <script>
            if(jQuery('#woocommerce_permalink_structure').length){
                jQuery('#woocommerce_permalink_structure').parent().append(jQuery('#wpml_wcml_custom_base_req').html());
            }
            if(jQuery('input[name="woocommerce_product_category_slug"]').length && jQuery('input[name="woocommerce_product_category_slug"]').val() == '<?php echo $category_base ?>'){
                jQuery('input[name="woocommerce_product_category_slug"]').parent().append('<br><i class="icon-warning-sign"><?php
                    _e('You are using the same value as for the regular category base. This is known to create conflicts resulting in urls not working properly.', 'woocommerce-multilingual') ?></i>');
            }
        </script>
        <?php

    }

    function show_custom_url_base_translation_links(){
        global $woocommerce_wpml,$sitepress;
        $permalink_options = get_option( 'woocommerce_permalinks' );

        $lang_selector = new WPML_Simple_Language_Selector( $sitepress );

        $bases = array( 'tag_base' => 'product_tag', 'category_base' => 'product_cat', 'attribute_base' => 'attribute', 'product_base' => 'product' );

        foreach( $bases as $key => $base ){

            switch($base){
                case 'product_tag':
                    $input_name = 'woocommerce_product_tag_slug';
                    $value = !empty( $permalink_options['tag_base'] ) ? $permalink_options['tag_base'] : $woocommerce_wpml->url_translation->default_product_tag_base;
                    break;
                case 'product_cat':
                    $input_name = 'woocommerce_product_category_slug';
                    $value = !empty( $permalink_options['category_base'] ) ? $permalink_options['category_base'] : $woocommerce_wpml->url_translation->default_product_category_base;
                    break;
                case 'attribute':
                    $input_name = 'woocommerce_product_attribute_slug';
                    $value = !empty( $permalink_options['attribute_base'] ) ? $permalink_options['attribute_base'] : '';
                    break;
                case 'product':
                    $input_name = 'product_permalink_structure';
                    if( empty( $permalink_options['product_base'] ) ){
                        $value = _x( 'product', 'default-slug', 'woocommerce' );
	  	            }else{
                        $value = trim( $permalink_options['product_base'], '/' );
                    }
                    break;
            }

            $language = $this->get_string_language( trim( $value, '/' ), $woocommerce_wpml->url_translation->url_strings_context(), $woocommerce_wpml->url_translation->url_string_name( $base ) );

            if( is_null($language) ){
                $language = $sitepress->get_default_language();
            }

            echo $lang_selector->render( array( 'id' => $key.'_language_selector', 'name' => $key.'_language', 'selected' => $language, 'show_please_select' => false  ) ); ?>

            <script>
                var input = jQuery('input[name="<?php echo $input_name ?>"]');

                if(input.length){

                    if( '<?php echo $input_name ?>' == 'product_permalink_structure' && jQuery('input[name="product_permalink"]:checked').val() == '' ){
                        input = jQuery('input[name="product_permalink"]:checked').closest('.form-table').find('code').eq(0);
                    }

                    input.parent().append('<div class="translation_controls"></div>');

                    if( '<?php echo $input_name ?>' == 'woocommerce_product_attribute_slug' && input.val() == '' ){

                        input.parent().find('.translation_controls').append('&nbsp;');

                    }else{
                        input.parent().find('.translation_controls').append('<a href="<?php
                            echo admin_url( 'admin.php?page=' . WPML_ST_FOLDER . '/menu/string-translation.php&context='. urlencode($woocommerce_wpml->url_translation->url_strings_context() ).'&search='.$value.'&em=1' )
                             ?>"><?php _e('translations', 'woocommerce-multilingual') ?></a>');
                    }

                }

                if( '<?php echo $input_name ?>' == 'product_permalink_structure' && jQuery('input[name="product_permalink"]:checked').val() == '' ){

                    input = jQuery('input[name="product_permalink"]:checked').closest('.form-table').find('code').eq(0);
                }

                jQuery('#<?php echo $key ?>_language_selector').appendTo( input.parent().find('.translation_controls') );
            </script>
        <?php }

    }

    function show_language_notice_for_wc_settings(){
        $this->string_language_notice();
        ?>
        <script>
            var notice_ids = ['woocommerce_new_order_subject','woocommerce_new_order_heading',
                            'woocommerce_cancelled_order_subject','woocommerce_cancelled_order_heading',
                            'woocommerce_customer_processing_order_subject','woocommerce_customer_processing_order_heading',
                            'woocommerce_customer_completed_order_subject','woocommerce_customer_completed_order_heading',
                            'woocommerce_customer_invoice_subject','woocommerce_customer_invoice_heading',
                            'woocommerce_customer_note_subject','woocommerce_customer_note_heading',
                            'woocommerce_customer_reset_password_subject','woocommerce_customer_reset_password_heading',
                            'woocommerce_customer_new_account_subject','woocommerce_customer_new_account_heading',
                            'woocommerce_bacs_title','woocommerce_bacs_description','woocommerce_bacs_instructions',
                            'woocommerce_cheque_title','woocommerce_cheque_description','woocommerce_cheque_instructions',
                            'woocommerce_cod_title','woocommerce_cod_description','woocommerce_cod_instructions',
                            'woocommerce_paypal_title','woocommerce_paypal_description',
                            'woocommerce_checkout_pay_endpoint',
                            'woocommerce_checkout_order_received_endpoint',
                            'woocommerce_myaccount_add_payment_method_endpoint',
                            'woocommerce_myaccount_view_order_endpoint',
                            'woocommerce_myaccount_edit_account_endpoint',
                            'woocommerce_myaccount_edit_address_endpoint',
                            'woocommerce_myaccount_lost_password_endpoint',
                            'woocommerce_logout_endpoint'
            ];

            for (i = 0; i < notice_ids.length; i++) {

                if( jQuery('#'+notice_ids[i]).length ){
                    jQuery('#'+notice_ids[i]).after(jQuery('#wpml_wcml_custom_base_req').html());
                }

            }

        </script>
    <?php
    }

    function string_language_notice(){
        global $sitepress_settings, $sitepress,$woocommerce_wpml;

        echo '<div id="wpml_wcml_custom_base_req" style="display:none"><div><i>';
        if(  !WPML_SUPPORT_STRINGS_IN_DIFF_LANG && $sitepress_settings['st']['strings_language'] != $sitepress->get_default_language() ){
            $strings_language = $sitepress->get_language_details($sitepress_settings['st']['strings_language']);
            echo sprintf(__('Please enter this text in %s', 'woocommerce-multilingual'), '<strong>' . $strings_language['display_name'] . '</strong>');
            echo '</i>&nbsp;<i class="icon-question-sign wcml_tootlip_icon" data-tip="';
            echo sprintf(__('You have to enter this text in the strings language ( %s ) so you can translate it using the WPML String Translation.', 'woocommerce-multilingual'), $strings_language['display_name'] ).'">';
        }
        echo '</i></div></div>';

        $woocommerce_wpml->load_tooltip_resources();
    }

    function show_attribute_label_language_warning(){
        global $sitepress_settings, $sitepress;

        if(!WPML_SUPPORT_STRINGS_IN_DIFF_LANG && $sitepress_settings['st']['strings_language'] != $sitepress->get_default_language()){
            $default_language = $sitepress->get_language_details($sitepress->get_default_language());
            $strings_language = $sitepress->get_language_details($sitepress_settings['st']['strings_language']);
            echo '<div id="wpml_wcml_attr_language" style="display:none"><div class="icl_cyan_box"><i>';
            echo sprintf(__("You need to enter attribute names in %s (even though your site's default language is %s). Then, translate it to %s and the rest of the site's languages using in the %sWooCommerce Multlingual admin%s.", 'woocommerce-multilingual'),
                 $strings_language['display_name'],
                 $default_language['display_name'],  $default_language['display_name'],
                '<strong><a href="' . admin_url('admin.php?page=wpml-wcml') . '">', '</a></strong>');
            echo '</i></div><br /></div>';
            ?>
            <script>
                if(jQuery('#attribute_label').length){
                    jQuery('#attribute_label').parent().prepend(jQuery('#wpml_wcml_attr_language').html());
                }
            </script>
            <?php

        }

    }

    function category_base_in_strings_language($text, $original_value, $context){
        if($context == 'slug' && ($original_value == 'product-category' || $original_value == 'product-tag')){
            $text = $original_value;
        }
        return $text;
    }

    function encode_shop_slug($location, $status){
        if(get_post_type(get_query_var('p')) == 'product'){
            global $sitepress;
            $language = $sitepress->get_language_for_element(get_query_var('p'), 'post_product');
            $base_slug = $this->get_translated_product_base_by_lang($language);

            $location = str_replace($base_slug , urlencode($base_slug),$location);
        }

        return $location;
    }


    function get_missed_product_slug_translations_languages(){
        global $sitepress, $woocommerce_wpml;

        $miss_slug_lang = array();

        $iclsettings = $sitepress->get_settings();

        if( $iclsettings['posts_slug_translation']['on'] ){
            $slug = $this->product_permalink_slug();

            if ( has_filter( 'wpml_slug_translation_available') ) {

                if( version_compare( WPML_ST_VERSION, '2.2.6', '>' ) ){
                    $slug_translation_languages = apply_filters( 'wpml_get_slug_translation_languages', array(), 'product' );
                } else {
                    $slug_translation_languages = apply_filters( 'wpml_get_slug_translation_languages', array(), $slug );
                }

            } else {
                $string_id = icl_get_string_id( $slug, $woocommerce_wpml->url_translation->url_strings_context(), $woocommerce_wpml->url_translation->url_string_name('product') );
                $slug_translations = icl_get_string_translations_by_id( $string_id );
            }



            $string_language = $this->get_string_language( $slug, $woocommerce_wpml->url_translation->url_strings_context(), $woocommerce_wpml->url_translation->url_string_name('product') );

            foreach( $sitepress->get_active_languages() as $lang_info ){
                if(
                    (
                        ( isset( $slug_translations ) && !array_key_exists( $lang_info['code'], $slug_translations ) ) ||
                        ( isset( $slug_translation_languages ) && !in_array( $lang_info['code'], $slug_translation_languages ) )
                    ) && $lang_info['code'] != $string_language
                ){
                    $miss_slug_lang[] = ucfirst($lang_info['display_name']);
                }
            }
        }

        return $miss_slug_lang;
    }


    function product_permalink_slug(){
        $permalinks         = get_option( 'woocommerce_permalinks' );
        $slug = empty( $permalinks['product_base'] ) ? 'product' : trim($permalinks['product_base'],'/');

        return $slug;
    }

    function get_domain_language( $domain ){

        if ( WPML_SUPPORT_STRINGS_IN_DIFF_LANG ) {
            global $sitepress;

            $lang_of_domain = new WPML_Language_Of_Domain( $sitepress );
            $domain_lang = $lang_of_domain->get_language( $domain );
            if ( $domain_lang ) {
                $source_lang = $domain_lang;
            }else{
                $source_lang = 'en';
            }

            return $source_lang;
        }else{
            global $sitepress_settings;

            if ( isset($sitepress_settings['st']['strings_language']) ){
                return $sitepress_settings['st']['strings_language'];
            }

            return 'en';
        }

    }

    // TODO will use a filter in the future wpmlst-529
    function get_string_language( $value, $context, $name = false ){

        if ( WPML_SUPPORT_STRINGS_IN_DIFF_LANG ) {
            global $wpdb;

            if( $name !== false ){

                $string_language = apply_filters( 'wpml_get_string_language', null, $context, $name );

            }else{

                $string_id = icl_get_string_id( $value, $context, $name );

                $string_object                  = new WPML_ST_String($string_id, $wpdb);
                $string_language                = $string_object->get_language();

            }


            return $string_language;
        }else{
            global $sitepress_settings;

            if ( isset($sitepress_settings['st']['strings_language']) ){
                return $sitepress_settings['st']['strings_language'];
            }

            return 'en';
        }

    }

    function set_string_language( $value, $context, $name , $language ){

        if ( WPML_SUPPORT_STRINGS_IN_DIFF_LANG ) {
            global $wpdb;

            $string_id = icl_get_string_id( $value, $context, $name );

            $string_object                  = new WPML_ST_String( $string_id, $wpdb );
            $string_language                = $string_object->set_language( $language );

            return $string_language;
        }else{

            return false;
        }

    }


    /*
     * Filter breadcrumbs
     *
     */
    function filter_woocommerce_breadcrumbs( $breadcrumbs, $object ){
        global $sitepress;

        if( $sitepress->get_current_language() != $sitepress->get_default_language() ){

            $permalinks   = get_option( 'woocommerce_permalinks' );

            $shop_page_id = wc_get_page_id( 'shop' );
            $orig_shop_page = get_post( apply_filters( 'translate_object_id', $shop_page_id, 'page', true, $sitepress->get_default_language() ) );

            // If permalinks contain the shop page in the URI prepend the breadcrumb with shop
            // Similar to WC_Breadcrumb::prepend_shop_page
            if ( $shop_page_id && $orig_shop_page && strstr( $permalinks['product_base'], '/' . $orig_shop_page->post_name ) && get_option( 'page_on_front' ) != $shop_page_id ) {
                $breadcrumbs_buff = array();
                $i = 0;
                foreach( $breadcrumbs as $key => $breadcrumb ){

                    if( !in_array( $breadcrumb, $breadcrumbs_buff ) ){
                        $breadcrumbs_buff[ $i ] = $breadcrumb;
                    }

                    if( $key === 0 ){
                        $i++;
                        $breadcrumbs_buff[ $i ] = array( get_the_title( $shop_page_id ), get_permalink( $shop_page_id ) );
                    }
                    $i++;
                }
                $breadcrumbs = $breadcrumbs_buff;

                $breadcrumbs = array_values($breadcrumbs);
            }

        }

        return $breadcrumbs;
    }

    /*
     * Add notice message to users
     */
    function notice_after_woocommerce_product_options_attributes(){
        global $sitepress;

        if( isset( $_GET['post'] ) && $sitepress->get_default_language() != $sitepress->get_current_language() ){
            $original_product_id = apply_filters( 'translate_object_id', $_GET['post'], 'product', true, $sitepress->get_default_language() );

            printf( '<p>'.__('In order to edit custom attributes you need to use the <a href="%s">custom product translation editor</a>', 'woocommerce-multilingual').'</p>', admin_url('admin.php?page=wpml-wcml&tab=products&prid='.$original_product_id ) );
        }
    }

    function translate_attribute_taxonomies_labels( $attribute_taxonomies ){
        global $sitepress;

        if( is_admin() && !wpml_is_ajax() ){

            foreach( $attribute_taxonomies as $key => $attribute_taxonomy ){
                $string_language = $this->get_string_language( $attribute_taxonomy->attribute_name, 'WordPress', 'taxonomy singular name: '.$attribute_taxonomy->attribute_name );

                if( $sitepress->get_current_language() == $string_language ) continue;

                $string_id = icl_get_string_id( $attribute_taxonomy->attribute_name, 'WordPress', 'taxonomy singular name: '.$attribute_taxonomy->attribute_name );
                $strings = icl_get_string_translations_by_id( $string_id );

                if($strings && isset($strings[$sitepress->get_current_language()])) {
                    $attribute_taxonomies[$key]->attribute_label = $strings[$sitepress->get_current_language()]['value'];
                }
            }

        }


        return $attribute_taxonomies;
    }

    function get_translation_from_woocommerce_mo_file( $string, $language, $return_original = true ){
        global $sitepress;

        $original_string = $string;

        if ( ! isset( $this->translations_from_mo_file[ $original_string ][ $language ] ) ) {

            if ( ! isset( $this->translations_from_mo_file[ $original_string ] ) ) {
                $this->translations_from_mo_file[ $original_string ] = array();
            }

            if ( ! isset( $this->mo_files[ $language ] ) ) {
                $mo = new MO();
                $mo_file =  WP_LANG_DIR . '/plugins/woocommerce-'  . $sitepress->get_locale( $language ) . '.mo';
                if( !file_exists( $mo_file ) ){
                    return $return_original ? $string : null;
                }

                $mo->import_from_file( $mo_file  );
                $this->mo_files[ $language ] = &$mo->entries;
            }

            if( in_array( $string, array( 'product','product-category','product-tag' ) ) ){
                $string = 'slug'. chr(4) .$string;
            }

            if( isset( $this->mo_files[ $language ][$string] ) ){
                $this->translations_from_mo_file[ $original_string ][ $language ] = $this->mo_files[ $language ][$string]->translations[0];
            } else {
                $this->translations_from_mo_file[ $original_string ][ $language ] = $return_original ? $original_string : null;
            }
        }

        return $this->translations_from_mo_file[ $original_string ][ $language ];

    }

    function translate_attributes_label_in_wp_taxonomies( $taxonomy, $obj_type, $args ){
        global $wp_taxonomies, $sitepress;
        $obj_type = array_unique( (array) $obj_type );

        $current_language = $sitepress->get_current_language();

        if( $current_language != 'all' && in_array( 'product', $obj_type ) && substr( $taxonomy, 0, 3) == 'pa_' && isset( $wp_taxonomies[ $taxonomy ] )){
            $wp_taxonomies[$taxonomy]->labels->name = apply_filters( 'wpml_translate_single_string', $args['labels']->name, 'WordPress', 'taxonomy singular name: '.$args['labels']->name, $current_language );
        }

    }

}