<?php

class WCML_Settings_UI extends WPML_Templates_Factory {

    private $woocommerce_wpml;
    private $sitepress;

    function __construct( &$woocommerce_wpml, &$sitepress ){
        parent::__construct();

        $this->woocommerce_wpml = &$woocommerce_wpml;
        $this->sitepress = &$sitepress;

    }

    public function get_model(){

        $model = array(
            'form' => array(
                'action' => $_SERVER['REQUEST_URI'],

                'translation_interface' => array(
                    'heading'   => __('Product Translation Interface','woocommerce-multilingual'),
                    'tip'       => __( 'The recommended way is using the WPML Translation Editor. It is streamlined for making the translation process much easier while also providing a much better integration with various WooCommerce extensions.',
                                    'woocommerce-multilingual' ),
                    'wcml'      => array(
                        'label' => __('WPML Translation Editor', 'woocommerce-multilingual'),

                    ),
                    'native'      => array(
                        'label' => __('Native WooCommerce product editing screen' , 'woocommerce-multilingual'),

                    ),
                    'controls_value' => $this->woocommerce_wpml->settings['trnsl_interface'],

                ),

                'synchronization' => array(
                    'heading'   => __('Products synchronization', 'woocommerce-multilingual'),
                    'tip'       => __( 'Configure specific product properties that should be synced to translations.', 'woocommerce-multilingual' ),
                    'sync_date' => array(
                        'value' => $this->woocommerce_wpml->settings['products_sync_date'],
                        'label' => __('Sync publishing date for translated products.', 'woocommerce-multilingual')
                    ),
                    'sync_order'=> array(
                        'value' => $this->woocommerce_wpml->settings['products_sync_order'],
                        'label' => __('Sync products and product taxonomies order.', 'woocommerce-multilingual')
                    ),
                ),

                'file_sync' => array(
                    'heading'   => __('Products Download Files', 'woocommerce-multilingual'),
                    'tip'       => __( 'If you are using downloadable products, you can choose to have their paths
                                            synchronized, or seperate for each language.', 'woocommerce-multilingual' ),
                    'value'         => $this->woocommerce_wpml->settings['file_path_sync'],
                    'label_same'    => __('Use the same files for translations', 'woocommerce-multilingual'),
                    'label_diff'    => __('Add separate download files for translations', 'woocommerce-multilingual'),
                ),


                'nonce'             => wp_nonce_field('wcml_save_settings_nonce', 'wcml_nonce', true, false),
                'save_label'        => __( 'Save changes', 'woocommerce-multilingual' ),

            ),

            'native_translation'  => WCML_TRANSLATION_METHOD_MANUAL,
            'wpml_translation'    => WCML_TRANSLATION_METHOD_EDITOR,

            'troubleshooting' => array(
                'url'   => admin_url( 'admin.php?page=wpml-wcml&tab=troubleshooting' ),
                'label' => __( 'Troubleshooting page', 'woocommerce-multilingual' )
            )
        );

        return $model;

    }

    protected function init_template_base_dir() {
        $this->template_paths = array(
            WCML_PLUGIN_PATH . '/templates/',
        );
    }

    public function get_template() {
        return 'settings-ui.twig';
    }


}