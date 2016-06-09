<?php

class WCML_Setup_Translation_Interface_UI extends WPML_Templates_Factory {

    private $woocommerce_wpml;

    function __construct( &$woocommerce_wpml, $next_step_url){
        parent::__construct();

        $this->woocommerce_wpml = &$woocommerce_wpml;
        $this->next_step_url = $next_step_url;

    }

    public function get_model(){

        $model = array(
            'strings' => array(
                'heading'       => __('Select the translation interface', 'woocommerce-multilingual'),
                'description'   => __('The recommended way is using the WPML Translation Editor. It is streamlined for making the translation process much easier while also providing a much better integration with various WooCommerce extensions.', 'woocommerce-multilingual'),
                'continue'      => __('Continue', 'woocommerce-multilingual'),
            ),

            'translation_interface'         => $this->woocommerce_wpml->settings['trnsl_interface'],
            'translation_interface_native'  => WCML_TRANSLATION_METHOD_MANUAL,
            'translation_interface_wpml'    => WCML_TRANSLATION_METHOD_EDITOR,
            'label_wpml_editor'           => __('WPML Translation Editor', 'woocommerce-multilingual'),
            'label_native_editor'         => __('Native WooCommerce product editing screen' , 'woocommerce-multilingual'),


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
        return '/setup/translation-interface.twig';
    }


}