<?php

class WCML_TP_Support{

    public $tp;

    function __construct(){

        $this->tp = new WPML_Element_Translation_Package;

        add_filter( 'wpml_tm_translation_job_data', array( $this, 'append_custom_attributes_to_translation_package' ), 10, 2 );
        add_action( 'wpml_translation_job_saved', array( $this, 'save_custom_attribute_translations' ), 10, 2 );

        add_filter( 'wpml_tm_translation_job_data', array( $this, 'append_variation_descriptions_translation_package' ), 10, 2 );
        add_action( 'wpml_pro_translation_completed', array( $this, 'save_variation_descriptions_translations' ), 20, 3 ); //after WCML_Products
    }


    function append_custom_attributes_to_translation_package( $package, $post ){

        $product = wc_get_product( $post->ID );

        //WC_Product::get_type() available from WooCommerce 2.4.0
        $product_type = method_exists($product, 'get_type') ? $product->get_type() : $product->product_type;

        if( !empty($product) && $product_type == 'variable' ){

            $attributes = $product->get_attributes();

            foreach( $attributes as $attribute_key => $attribute ){
                if( !$attribute['is_taxonomy'] ){

                    $package['contents']['wc_attribute_name:' . $attribute_key] = array(
                        'translate' => 1,
                        'data'      => $this->tp->encode_field_data( $attribute['name'], 'base64' ),
                        'format'    => 'base64'
                    );

                    $values = explode( '|', $attribute['value'] );
                    $values = array_map('trim', $values);

                    foreach( $values as $value_key => $value ){

                        $package['contents']['wc_attribute_value:' . $value_key . ':' . $attribute_key] = array(
                            'translate' => 1,
                            'data'      => $this->tp->encode_field_data( $value, 'base64' ),
                            'format'    => 'base64'
                        );

                    }

                }
            }

        }

        return $package;
    }

    function save_custom_attribute_translations($post_id, $data){
        global $woocommerce_wpml;

        $translated_attributes = array();

        foreach( $data as $data_key => $value){

            if( $value['finished'] && isset( $value['field_type'] ) && strpos( $value['field_type'], 'wc_attribute_' ) === 0 ){

                if( strpos( $value['field_type'], 'wc_attribute_name:' ) === 0 ){

                    $exp = explode( ':', $value['field_type'], 2 );
                    $attribute_key = $exp[1];

                    $translated_attributes[$attribute_key]['name'] = $value['data'];

                } else if( strpos( $value['field_type'], 'wc_attribute_value:' ) === 0 ){

                    $exp = explode( ':', $value['field_type'], 3 );
                    $value_key = $exp[1];
                    $attribute_key = $exp[2];

                    $translated_attributes[$attribute_key]['values'][$value_key] = $value['data'];

                }

            }

        }

        if( $translated_attributes ) {

            $product_attributes = get_post_meta( $post_id, '_product_attributes', true );

            $original_post_language = $woocommerce_wpml->products->get_original_product_language( $post_id );
            $original_post_id = apply_filters( 'translate_object_id', $post_id, 'product', false, $original_post_language );

            $original_attributes = get_post_meta( $original_post_id, '_product_attributes', true );

            foreach ( $translated_attributes as $attribute_key => $attribute ) {

                $product_attributes[$attribute_key] = array(
                    'name' => $attribute['name'],
                    'value' => join( ' | ', $attribute['values'] ),
                    'is_taxonomy' => 0,
                    'is_visible' => $original_attributes[$attribute_key]['is_visible'],
                    'position' => $original_attributes[$attribute_key]['position']
                );


            }

            update_post_meta( $post_id, '_product_attributes', $product_attributes );

        }

    }

    function append_variation_descriptions_translation_package($package, $post){

        $product = wc_get_product( $post->ID );

        //WC_Product::get_type() available from WooCommerce 2.4.0
        $product_type = method_exists($product, 'get_type') ? $product->get_type() : $product->product_type;

        if( !empty($product) && $product_type == 'variable' ) {

            $variations = $product->get_available_variations();

            foreach( $variations as $variation ){

                if( !empty($variation['variation_description']) ){

                    $package['contents']['wc_variation_description:' . $variation['variation_id']] = array(
                        'translate' => 1,
                        'data'      => $this->tp->encode_field_data( $variation['variation_description'], 'base64' ),
                        'format'    => 'base64'
                    );

                }

            }


        }

        return $package;

    }

    function save_variation_descriptions_translations( $post_id, $data, $job ){

        $language = $job->language_code;

        foreach( $data as $data_key => $value){

            if( $value['finished'] && isset( $value['field_type'] ) && strpos( $value['field_type'], 'wc_variation_description:' ) === 0 ){

                $variation_id = substr( $value['field_type'], strpos($value['field_type'], ':') + 1 );

                if( is_post_type_translated( 'product_variation' ) ){

                    $translated_variation_id = apply_filters( 'translate_object_id', $variation_id, 'product_variation', false, $language );

                }else{
                    global $sitepress;
                    $trid = $sitepress->get_element_trid($variation_id, 'post_product_variation');
                    $translations = $sitepress->get_element_translations($trid, 'post_product_variation', true, true, true);

                    $translated_variation_id = isset( $translations[$language] ) ? $translations[$language]->element_id : false;

                }

                if($translated_variation_id){
                    update_post_meta($translated_variation_id, '_variation_description', $value['data'] );
                }


            }

        }

    }
}