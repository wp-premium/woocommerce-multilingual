<?php

class WCML_Menus_Wrap extends WPML_Templates_Factory {

    private $woocommerce_wpml;

    function __construct( &$woocommerce_wpml ){
        parent::__construct();

        $this->woocommerce_wpml = &$woocommerce_wpml;
    }

    public function get_model(){

        $current_tab = $this->get_current_tab();
        $product_attributes = $this->woocommerce_wpml->attributes->get_translatable_attributes();

        $taxonomy = isset( $_GET['taxonomy'] ) ? $_GET['taxonomy'] : false;
        if( $product_attributes ){
            if( !empty($taxonomy) ){
                foreach( $product_attributes as $attribute ){
                    if( $attribute->attribute_name == $taxonomy ){
                        $selected_attribute = $attribute;
                        break;
                    }
                }
            }
            if( empty( $selected_attribute ) ){
                $selected_attribute = current( $product_attributes );
            }
        }


        $model = array(

            'strings' => array(
                'title'             => __('WooCommerce Multilingual', 'woocommerce-multilingual'),
                'untranslated_terms'=> __('You have untranslated terms!', 'woocommerce-multilingual')
            ),
            'menu' => array(
                'products' => array(
                    'title'     => __('Products', 'woocommerce-multilingual'),
                    'url'       => admin_url('admin.php?page=wpml-wcml'),
                    'active'    => $current_tab == 'products' ? 'nav-tab-active' : ''
                ),
                'taxonomies' => array(
                    'product_cat' => array(
                        'name'      => __('Categories', 'woocommerce-multilingual'),
                        'title'     => !$this->woocommerce_wpml->terms->is_fully_translated( 'product_cat' ) ? __('You have untranslated terms!', 'woocommerce-multilingual') : '',
                        'active'    => $current_tab == 'product_cat' ? 'nav-tab-active':'',
                        'url'       => admin_url('admin.php?page=wpml-wcml&tab=product_cat'),
                        'translated'=> $this->woocommerce_wpml->terms->is_fully_translated( 'product_cat' )
                    ),
                    'product_tag' => array(
                        'name'      => __('Tags', 'woocommerce-multilingual'),
                        'title'     => !$this->woocommerce_wpml->terms->is_fully_translated( 'product_tag' ) ? __('You have untranslated terms!', 'woocommerce-multilingual') : '',
                        'active'    => $current_tab == 'product_tag' ? 'nav-tab-active':'',
                        'url'       => admin_url('admin.php?page=wpml-wcml&tab=product_tag'),
                        'translated'=> $this->woocommerce_wpml->terms->is_fully_translated( 'product_tag' )
                    )
                ),
                'attributes' => array(
                    'name'      => __('Attributes', 'woocommerce-multilingual'),
                    'active'    => $current_tab == 'product-attributes' ? 'nav-tab-active':'',
                    'url'       => admin_url('admin.php?page=wpml-wcml&tab=product-attributes'),
                    'translated'=> !$product_attributes || ( isset( $selected_attribute ) && $this->woocommerce_wpml->terms->is_fully_translated( 'pa_' . $selected_attribute->attribute_name) )
                ),
                'shipping_classes' => array(
                    'name'      => __('Shipping Classes', 'woocommerce-multilingual'),
                    'title'     => !$this->woocommerce_wpml->terms->is_fully_translated( 'product_shipping_class' ) ? __('You have untranslated terms!', 'woocommerce-multilingual') : '',
                    'active'    => $current_tab == 'product_shipping_class' ? 'nav-tab-active':'',
                    'url'       => admin_url('admin.php?page=wpml-wcml&tab=product_shipping_class'),
                    'translated'=> $this->woocommerce_wpml->terms->is_fully_translated( 'product_shipping_class' )
                ),
                'settings' => array(
                    'name'      => __( 'Settings', 'woocommerce-multilingual' ),
                    'active'    => $current_tab == 'settings' ? 'nav-tab-active':'',
                    'url'       => admin_url( 'admin.php?page=wpml-wcml&tab=settings' )
                ),
                'multi_currency' => array(
                    'name'      => __( 'Multi-currency', 'woocommerce-multilingual' ),
                    'active'    => $current_tab == 'multi-currency' ? 'nav-tab-active':'',
                    'url'       => admin_url( 'admin.php?page=wpml-wcml&tab=multi-currency' )
                ),
                'slugs' => array(
                    'name'      => __( 'Store URLs', 'woocommerce-multilingual' ),
                    'active'    => $current_tab == 'slugs' ? 'nav-tab-active':'',
                    'url'       => admin_url( 'admin.php?page=wpml-wcml&tab=slugs' )
                ),
                'status' => array(
                    'name'      => __( 'Status', 'woocommerce-multilingual' ),
                    'active'    => $current_tab == 'status' ? 'nav-tab-active':'',
                    'url'       => admin_url( 'admin.php?page=wpml-wcml&tab=status' )
                ),
                'troubleshooting' => array(
                    'name'      => __( 'Troubleshooting', 'woocommerce-multilingual' ),
                    'active'    => $current_tab == 'troubleshooting' ? 'nav-tab-active':'',
                    'url'       => admin_url( 'admin.php?page=wpml-wcml&tab=troubleshooting' )
                )
            ),
            'can_manage_options' => current_user_can('wpml_manage_woocommerce_multilingual'),
            'can_operate_options' => current_user_can('wpml_operate_woocommerce_multilingual'),
            'rate' => array(
                'on'        => !isset( $this->woocommerce_wpml->settings['rate-block'] ),
                'message'   => sprintf(__('Thank you for using %sWooCommerce Multilingual%s! You can express your love and
                                    support by %s rating our plugin and saying that %sit works%s for you.', 'woocommerce_wpml'),
                    '<strong>',
                    '</strong>',
                    '<a href="https://wordpress.org/support/view/plugin-reviews/woocommerce-multilingual?filter=5#postform" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a>',
                    '<a href="https://wordpress.org/plugins/woocommerce-multilingual/?compatibility[version]='.$this->woocommerce_wpml->get_supported_wp_version().'&compatibility[topic_version]='.WCML_VERSION.'&compatibility[compatible]=1#compatibility" target="_blank">',
                    '</a>'

                ),
                'hide_text' => __('Hide','woocommerce-multilingual'),
                'nonce'     =>  wp_nonce_field('wcml_settings', 'wcml_settings_nonce', true, false)
            ),
            'content' => $this->get_current_menu_content( $current_tab )
        );

        return $model;

    }

    protected function init_template_base_dir() {
        $this->template_paths = array(
            WCML_PLUGIN_PATH . '/templates/',
        );
    }

    public function get_template() {
        return 'menus-wrap.twig';
    }

    protected function get_current_tab(){

        if(isset($_GET['tab'])){
            $current_tab = $_GET['tab'];
            if( !current_user_can('wpml_manage_woocommerce_multilingual') && !current_user_can('wpml_operate_woocommerce_multilingual') ){
                $current_tab = 'products';
            }
        }else{
            $current_tab = 'products';
        }

        return $current_tab;

    }

    protected function get_current_menu_content( $current_tab ){
        global $sitepress, $sitepress_settings;

        $woocommerce_wpml = $this->woocommerce_wpml;

        $content = '';

        switch ( $current_tab ) {

            case 'products':
                $wcml_products_ui = new WCML_Products_UI( $woocommerce_wpml, $sitepress );
                $content = $wcml_products_ui->get_view();

                break;

            case 'multi-currency':
                if( current_user_can('wpml_manage_woocommerce_multilingual') ){
                    $wcml_mc_ui = new WCML_Multi_Currency_UI( $woocommerce_wpml, $sitepress );
                    $content = $wcml_mc_ui->get_view();
                }

                break;

            case 'product-attributes':

                if( current_user_can('wpml_operate_woocommerce_multilingual') ) {
                    $attribute_translation_ui = new WCML_Attribute_Translation_UI( $woocommerce_wpml );
                    $content = $attribute_translation_ui->get_view();
                }
                break;

            case 'slugs':
                if( current_user_can('wpml_operate_woocommerce_multilingual') ) {
                    $wcml_store_urls = new WCML_Store_URLs_UI( $woocommerce_wpml, $sitepress );
                    $content = $wcml_store_urls->get_view();
                }
                break;

            case 'status':
                if( current_user_can('wpml_manage_woocommerce_multilingual') ) {
                    $wcml_status = new WCML_Status_UI($woocommerce_wpml, $sitepress, $sitepress_settings);
                    $content = $wcml_status->get_view();
                }
                break;

            case 'troubleshooting':
                if( current_user_can('wpml_manage_woocommerce_multilingual') ) {
                    $wcml_troubleshooting = new WCML_Troubleshooting_UI($woocommerce_wpml);
                    $content = $wcml_troubleshooting->get_view();
                }
                break;

            case 'settings':
                if( current_user_can('wpml_manage_woocommerce_multilingual') ) {
                    $wcml_settings_ui = new WCML_Settings_UI( $woocommerce_wpml, $sitepress );
                    $content = $wcml_settings_ui->get_view();
                }
                break;

            default:
                if( current_user_can('wpml_operate_woocommerce_multilingual') && in_array( $current_tab, array( 'product_cat', 'product_tag', 'product_shipping_class' ) ) ){
                    $WPML_Translate_Taxonomy = new WPML_Taxonomy_Translation($current_tab, array( 'taxonomy_selector'=> false, 'status'=> WPML_TT_TAXONOMIES_ALL ) );
                    ob_start();
					?>
					<div class="wpml-loading-taxonomy"><span class="spinner is-active"></span><?php echo __( 'Loading ...', 'woocommerce-multilingual' ) ?></div>
					<div class="wpml_taxonomy_loaded" style="display:none">
						<?php
						$WPML_Translate_Taxonomy->render();
					?>
					</div>
					<?php
					
					$content = ob_get_contents();
					ob_end_clean();

                }

        }

        return $content;

    }


}