<?php

class WCML_WPSEO{

    function __construct(){

        add_filter( 'wcml_product_content_label', array( $this, 'wpseo_custom_field_label' ), 10, 2 );

        if( defined( 'WPSEO_VERSION') && defined( 'WPSEO_PATH' ) && isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] == 'wpml-wcml' && isset( $_GET[ 'tab' ] ) && $_GET[ 'tab' ] == 'products' ){
            if( version_compare( WPSEO_VERSION, '3', '<' ) ) {
                require_once WPSEO_PATH . 'admin/class-metabox.php';
            } elseif( file_exists( WPSEO_PATH . 'admin/metabox/class-metabox.php' ) ) {
                require_once WPSEO_PATH . 'admin/metabox/class-metabox.php';
            }
        }

    }

    function wpseo_custom_field_label( $field, $product_id ){
        global $woocommerce_wpml, $wpseo_metabox;

        $yoast_seo_fields = array( '_yoast_wpseo_focuskw', '_yoast_wpseo_title', '_yoast_wpseo_metadesc' );

        if ( !is_array(  maybe_unserialize( get_post_meta( $product_id, $field, true ) ) ) ) {

            if ( !is_null( $wpseo_metabox ) && in_array( $field, $yoast_seo_fields ) ) {

                $wpseo_metabox_values = $wpseo_metabox->get_meta_boxes( 'product' );

                $label = $wpseo_metabox_values[ str_replace( '_yoast_wpseo_', '', $field ) ][ 'title' ];

                return $label;
            }
        }

        return $field;
    }


}

