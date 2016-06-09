<?php

class WCML_Setup_Introduction_UI extends WPML_Templates_Factory {

    private $woocommerce_wpml;
    private $next_step_url;

    function __construct( &$woocommerce_wpml, $next_step_url ){
        parent::__construct();

        $this->woocommerce_wpml = &$woocommerce_wpml;
        $this->next_step_url = $next_step_url;

    }

    public function get_model(){

        $model = array(
            'strings' => array(
                'heading'       => __('Welcome to WooCommerce Multilingual!', 'woocommerce-multilingual'),
                'description1'   => __('Configure the multilingual support for your e-commerce site in just a couple of minutes.', 'woocommerce-multilingual'),
                'description2'  => __('By default, the products are translatable just like product categories, product tags and attributes. So you can start translating these right away.', 'woocommerce-multilingual'),
                'description3'  => __('You can configure, however, which attributes you want to translate, install the translated shop pages or enable the multi-currency mode.', 'woocommerce-multilingual'),
                'continue'      => __('Start', 'woocommerce-multilingual'),
                'later'         => __("No, thanks. I'll do it later.", 'woocommerce-multilingual')
            ),
            'later_url'     => admin_url('admin.php?page=wpml-wcml&src=setup_later'),
            'continue_url'  => $this->next_step_url
        );

        return $model;

    }

    protected function init_template_base_dir() {
        $this->template_paths = array(
            WCML_PLUGIN_PATH . '/templates/',
        );
    }

    public function get_template() {
        return '/setup/introduction.twig';
    }


}