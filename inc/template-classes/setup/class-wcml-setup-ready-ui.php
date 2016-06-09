<?php

class WCML_Setup_Ready_UI extends WPML_Templates_Factory {

    private $woocommerce_wpml;

    function __construct( &$woocommerce_wpml ){
        parent::__construct();

        $this->woocommerce_wpml = &$woocommerce_wpml;

    }

    public function get_model(){

        $translated_attributes = $this->woocommerce_wpml->attributes->get_translatable_attributes();
        $untranslated_attribute_terms = array();
        foreach( $translated_attributes as $attribute ){
            if( !$this->woocommerce_wpml->terms->is_fully_translated( 'pa_' . $attribute->attribute_name ) ){
                $untranslated_attribute_terms[] = '<strong>' . $attribute->attribute_label . '</strong>';
            }
        }

        $untranslated_categories = !$this->woocommerce_wpml->terms->is_fully_translated( 'product_cat' );
        $untranslated_tags = !$this->woocommerce_wpml->terms->is_fully_translated( 'product_tag' );
        $untranslated_shipping_classes = !$this->woocommerce_wpml->terms->is_fully_translated( 'product_shipping_class' );

        $model = array(
            'strings' => array(
                'heading'       => __('Ready!', 'woocommerce-multilingual'),
                'description'   => __("Further actions that are necessary for making your existing content multilingual, are listed below.", 'woocommerce-multilingual'),
                'continue'      => __('Start translating products', 'woocommerce-multilingual'),
            ),

            'multi_currency_on'     => $this->woocommerce_wpml->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT,
            'multi_currency_link'   => sprintf( __('Add secondary currencies on the %smulti-currency configuration%s page', 'woocommerce-multilingual'),
                '<a href="' . admin_url('admin.php?page=wpml-wcml&tab=multi-currency&src=setup') . '">', '</a>'),

            'untranslated_attr_terms'=> $untranslated_attribute_terms ?
                sprintf( __('Translate existing terms for these %sproduct attributes%s: %s', 'woocommerce-multilingual'),
                '<a href="' . admin_url('admin.php?page=wpml-wcml&tab=product-attributes&src=setup') . '">', '</a>', join(', ', $untranslated_attribute_terms )) : false,

            'untranslated_categories'=> $untranslated_categories ?
                sprintf( __('Translate existing %sproduct categories%s', 'woocommerce-multilingual'),
                    '<a href="' . admin_url('admin.php?page=wpml-wcml&tab=product_cat&src=setup') . '">', '</a>') : false,

            'untranslated_tags'=> $untranslated_tags ?
                sprintf( __('Translate existing %sproduct tags%s', 'woocommerce-multilingual'),
                    '<a href="' . admin_url('admin.php?page=wpml-wcml&tab=product_tag&src=setup') . '">', '</a>') : false,

            'untranslated_shipping_classes'=> $untranslated_shipping_classes ?
                sprintf( __('%sAdd missing translations%s for shipping classes', 'woocommerce-multilingual'),
                    '<a href="' . admin_url('admin.php?page=wpml-wcml&tab=product_shipping_class&src=setup') . '">', '</a>') : false,

            'url_translation' => sprintf( __('Translate %sURL slugs%s to create multilingual store and product urls', 'woocommerce-multilingual'),
                '<a href="' . admin_url('admin.php?page=wpml-wcml&tab=slugs&src=setup') . '">', '</a>' ),

            'continue_url'  => admin_url('admin.php?page=wpml-wcml&tab=products&src=setup')
        );

        return $model;

    }

    protected function init_template_base_dir() {
        $this->template_paths = array(
            WCML_PLUGIN_PATH . '/templates/',
        );
    }

    public function get_template() {
        return '/setup/ready.twig';
    }


}