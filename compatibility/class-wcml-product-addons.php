<?php

class WCML_Product_Addons{

    function __construct(){

        add_filter('get_product_addons_product_terms',array($this,'addons_product_terms'));
        add_filter('get_product_addons_fields',array($this,'product_addons_filter'),10,2);

        add_action('updated_post_meta',array($this,'register_addons_strings'),10,4);

        global $pagenow;
        if($pagenow == 'edit.php' && isset($_GET['post_type']) && $_GET['post_type']=='product' && isset($_GET['page']) && $_GET['page']=='global_addons' && !isset($_GET['edit'])){
            add_action('admin_notices', array($this, 'inf_translate_strings'));
        }

        add_action( 'addons_panel_start', array( $this, 'inf_translate_strings' ) );
    }

    function register_addons_strings( $meta_id, $id, $meta_key, $addons){
        if( $meta_key != '_product_addons' )
            return false;

        foreach($addons as $addon){
            //register name
            do_action('wpml_register_single_string', 'wc_product_addons_strings', $id.'_addon_'.$addon['type'].'_'.$addon['position'].'_name', $addon['name']);
            //register description
            do_action('wpml_register_single_string', 'wc_product_addons_strings', $id.'_addon_'.$addon['type'].'_'.$addon['position'].'_description', $addon['description']);
            //register options labels
            foreach($addon['options'] as $key=>$option){
                do_action('wpml_register_single_string', 'wc_product_addons_strings', $id.'_addon_'.$addon['type'].'_'.$addon['position'].'_option_label_'.$key, $option['label']);
            }
        }
    }

    function product_addons_filter($addons, $object_id){
        global $sitepress;

        $addon_type = get_post_type($object_id);
        if( $addon_type != 'global_product_addon' )
            $object_id = $sitepress->get_original_element_id( $object_id , 'post_'.$addon_type );

        foreach($addons as $add_id => $addon){
            $addons[$add_id]['name'] = apply_filters( 'wpml_translate_single_string', $addon['name'], 'wc_product_addons_strings', $object_id.'_addon_'.$addon['type'].'_'.$addon['position'].'_name' );
            $addons[$add_id]['description'] = apply_filters( 'wpml_translate_single_string', $addon['description'], 'wc_product_addons_strings', $object_id.'_addon_'.$addon['type'].'_'.$addon['position'].'_description');
            foreach($addon['options'] as $key=>$option){
                $addons[$add_id]['options'][$key]['label'] = apply_filters( 'wpml_translate_single_string', $option['label'], 'wc_product_addons_strings', $object_id.'_addon_'.$addon['type'].'_'.$addon['position'].'_option_label_'.$key);

                //price filter
                $addons[$add_id]['options'][$key]['price']  = apply_filters('wcml_raw_price_amount', $option['price']);
            }
        }

        return $addons;
    }


    function addons_product_terms($product_terms){
        global $sitepress;

        foreach($product_terms as $key => $product_term){
            $product_terms[$key] = apply_filters( 'translate_object_id',$product_term,'product_cat',true,$sitepress->get_default_language());
        }

        return $product_terms;
    }

    function inf_translate_strings(){
        $message = '<div><p class="icl_cyan_box">';
        $message .= sprintf(__('To translate Add-ons strings please save Add-ons and go to the <b><a href="%s">String Translation interface</a></b>', 'woocommerce-multilingual'), admin_url('admin.php?page='.WPML_ST_FOLDER.'/menu/string-translation.php&context=wc_product_addons_strings'));
        $message .= '</p></div>';

        echo $message;
    }

}
