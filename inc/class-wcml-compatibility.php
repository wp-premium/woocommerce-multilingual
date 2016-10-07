<?php

class WCML_Compatibility {

    /**
     * @var SitePress
     */
    public $sitepress;

    /**
     * @var woocommerce_wpml
     */
    public $woocommerce_wpml;

    /**
     * @var wpdb
     */
    public $wpdb;

    function __construct( &$sitepress, &$woocommerce_wpml, &$wpdb ) {
        $this->sitepress = $sitepress;
        $this->woocommerce_wpml = $woocommerce_wpml;
        $this->wpdb = $wpdb;
        $this->init();

    }

    function init(){
        //hardcoded list of extensions and check which ones the user has and then include the corresponding file from the ‘compatibility’ folder

        //WooCommerce Tab Manager plugin
        if(class_exists('WC_Tab_Manager')){
            global $woocommerce;
            $this->tab_manager = new WCML_Tab_Manager( $this->sitepress, $woocommerce, $this->woocommerce_wpml, $this->wpdb );
        }

        //WooCommerce Table Rate Shipping plugin
        if(defined('TABLE_RATE_SHIPPING_VERSION')){
            $this->table_rate_shipping = new WCML_Table_Rate_Shipping( $this->sitepress, $this->woocommerce_wpml );
        }
        
        //WooCommerce Subscriptions
        if(class_exists('WC_Subscriptions')){
            $this->wp_subscriptions = new WCML_WC_Subscriptions();
        }

        //WooCommerce Name Your Price
        if(class_exists('WC_Name_Your_Price')){
            $this->name_your_price = new WCML_WC_Name_Your_Price();
        }

        //Product Bundle
        if(class_exists('WC_Product_Bundle')){
            $this->product_bundles = new WCML_Product_Bundles( $this->sitepress, $this->woocommerce_wpml );
        }
        
         // WooCommerce Variation Swatches and Photos
        if(class_exists('WC_SwatchesPlugin')){	
            $this->variation_sp = new WCML_Variation_Swatches_and_Photos();
        }
     
        // Product Add-ons
        if(class_exists( 'Product_Addon_Display' )){
            $this->product_addons = new WCML_Product_Addons( $this->sitepress );
        }

        // Product Per Product Shipping
        if(defined( 'PER_PRODUCT_SHIPPING_VERSION' )){
            new WCML_Per_Product_Shipping();
        }
        //Store Exporter plugin
        if(defined('WOO_CE_PATH')){
            $this->wc_exporter = new WCML_wcExporter();
        }
        
        //Gravity Forms
        if(class_exists('GFForms')){
            $this->gravityforms = new WCML_gravityforms();
        }

        //Sensei WooThemes
        if(class_exists('WooThemes_Sensei')){
            $this->sensei = new WCML_sensei();
        }

        //Extra Product Options
        if(class_exists('TM_Extra_Product_Options')){
            $this->extra_product_options = new WCML_Extra_Product_Options();
        }

        // Dynamic Pricing
        if ( class_exists('WC_Dynamic_Pricing') ) {
            $this->dynamic_pricing = new WCML_Dynamic_Pricing( $this->sitepress );
        }

        // WooCommerce Bookings
        if(defined( 'WC_BOOKINGS_VERSION' ) && version_compare(WC_BOOKINGS_VERSION, '1.7.8', '>=') ){
            $this->bookings = new WCML_Bookings( $this->sitepress, $this->woocommerce_wpml, $this->wpdb );

            // WooCommerce Accommodation Bookings
            if( defined( 'WC_ACCOMMODATION_BOOKINGS_VERSION' ) ){
                $this->accomodation_bookings = new WCML_Accommodation_Bookings(  $this->woocommerce_wpml );
            }
        }

        // WooCommerce Checkout Field Editor
        if ( function_exists( 'woocommerce_init_checkout_field_editor' ) ) {
            $this->checkout_field_editor = new WCML_Checkout_Field_Editor();
        }
				
        if (class_exists('WC_Bulk_Stock_Management')) {
            $this->wc_bulk_stock_management = new WCML_Bulk_Stock_Management();
        }

        // WooCommerce Advanced Ajax Layered Navigation
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        if ( is_plugin_active( 'woocommerce-ajax-layered-nav/ajax_layered_nav-widget.php' ) ) {
            $this->wc_ajax_layered_nav_widget = new WCML_Ajax_Layered_Nav_Widget();
        }

        if ( is_plugin_active( 'woocommerce-ajax-cart/wooajaxcart.php' ) ) {
            $this->wc_ajax_cart = new WCML_WC_Ajax_Cart();
        }

        // woocommerce composite products
        if ( isset( $GLOBALS[ 'woocommerce_composite_products' ] ) ) {
            $this->wc_composite_products = new WCML_Composite_Products(  $this->sitepress, $this->woocommerce_wpml );
        }
				
        // woocommerce checkout addons
        if (function_exists('init_woocommerce_checkout_add_ons')) {
            $this->wc_checkout_addons = new WCML_Checkout_Addons();
        }

        // woocommerce checkout addons
        if ( wp_get_theme() == 'Flatsome' ) {
            $this->flatsome = new WCML_Flatsome();
        }

        if (class_exists('WC_Mix_and_Match')) {
            $this->mix_and_match_products = new WCML_Mix_and_Match_Products();
        }

        if ( defined( 'WPSEO_VERSION') ) {
            $this->wpseo = new WCML_WPSEO();
        }

        //Adventure Tours theme
        if( function_exists( 'adventure_tours_check' ) ){
            $this->adventure_tours = new WCML_Adventure_tours();
        }

        //Aurum Theme
        if( wp_get_theme() == 'Aurum' ){
            new WCML_Aurum();
        }

        // Visual Products Configurator
        if( class_exists( 'Vpc' ) ){
            $this->vpc = new WCML_Vpc();
        }

        // WooCommerce Show Single Variations
        if( defined( 'JCK_WSSV_PATH' ) ){
            new WCML_JCK_WSSV();
        }


    }

}