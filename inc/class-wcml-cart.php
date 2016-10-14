<?php
class WCML_Cart
{

    private $woocommerce_wpml;
    private $sitepress;

    public function __construct( &$woocommerce_wpml, &$sitepress )
    {
        $this->woocommerce_wpml = $woocommerce_wpml;
        $this->sitepress = $sitepress;

        //cart widget
        add_action( 'wp_ajax_woocommerce_get_refreshed_fragments', array( $this, 'wcml_refresh_fragments' ), 0 );
        add_action( 'wp_ajax_woocommerce_add_to_cart', array( $this, 'wcml_refresh_fragments' ), 0 );
        add_action( 'wp_ajax_nopriv_woocommerce_get_refreshed_fragments', array( $this, 'wcml_refresh_fragments' ), 0 );
        add_action( 'wp_ajax_nopriv_woocommerce_add_to_cart', array( $this, 'wcml_refresh_fragments' ), 0 );

        //cart
        add_action( 'woocommerce_before_calculate_totals', array( $this, 'woocommerce_calculate_totals' ), 100 );
        add_action( 'woocommerce_get_cart_item_from_session', array( $this, 'translate_cart_contents' ), 10, 3 );
        add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'translate_cart_subtotal' ) );
        add_action( 'woocommerce_before_checkout_process', array( $this, 'wcml_refresh_cart_total' ) );

        add_filter('woocommerce_paypal_args', array($this, 'filter_paypal_args'));

        $this->localize_flat_rates_shipping_classes();
    }

    public function wcml_refresh_fragments(){
        WC()->cart->calculate_totals();
        $this->woocommerce_wpml->locale->wcml_refresh_text_domain();
    }

    /*
     *  Update cart and cart session when switch language
     */
    public function woocommerce_calculate_totals( $cart, $currency = false ){
        global $woocommerce;

        $current_language = $this->sitepress->get_current_language();
        $new_cart_data = array();

        foreach( $cart->cart_contents as $key => $cart_item ){
            $tr_product_id = apply_filters( 'translate_object_id', $cart_item[ 'product_id' ], 'product', false, $current_language );
            //translate custom attr labels in cart object
            if( isset( $cart_item[ 'data' ]->product_attributes ) ){
                foreach( $cart_item[ 'data' ]->product_attributes as $attr_key => $product_attribute ){
                    if( !$product_attribute[ 'is_taxonomy' ] ){
                        $cart->cart_contents[ $key ][ 'data' ]->product_attributes[ $attr_key ][ 'name' ] = $this->woocommerce_wpml->strings->translated_attribute_label(
                                                                                                                $product_attribute[ 'name' ],
                                                                                                                $product_attribute[ 'name' ],
                                                                                                                $tr_product_id );
                    }
                }
            }

            //translate custom attr value in cart object
            if( isset( $cart_item[ 'variation' ] ) && is_array( $cart_item[ 'variation' ] ) ){
                foreach( $cart_item[ 'variation' ] as $attr_key => $attribute ){
                    $cart->cart_contents[ $key ][ 'variation' ][ $attr_key ] = $this->get_cart_attribute_translation(
                                                                                    $attr_key,
                                                                                    $attribute,
                                                                                    $cart_item[ 'variation_id' ],
                                                                                    $current_language,
                                                                                    $cart_item[ 'data' ]->parent->id,
                                                                                    $tr_product_id
                                                                                );
                }
            }

            if( $currency !== false ){
                $cart->cart_contents[ $key ][ 'data' ]->price = get_post_meta( $cart_item['product_id'], '_price', 1 );
            }

            if( $cart_item[ 'product_id' ] == $tr_product_id ){
                $new_cart_data[ $key ] = apply_filters( 'wcml_cart_contents_not_changed', $cart->cart_contents[$key], $key, $current_language );
                continue;
            }

            if( isset( $cart->cart_contents[ $key ][ 'variation_id' ] ) && $cart->cart_contents[ $key ][ 'variation_id' ] ){
                $tr_variation_id = apply_filters( 'translate_object_id', $cart_item[ 'variation_id' ], 'product_variation', false, $current_language );
                if( !is_null( $tr_variation_id ) ){
                    $cart->cart_contents[ $key ][ 'product_id' ] = intval( $tr_product_id );
                    $cart->cart_contents[ $key ][ 'variation_id' ] = intval( $tr_variation_id );
                    $cart->cart_contents[ $key ][ 'data' ]->id = intval( $tr_product_id );
                    $cart->cart_contents[ $key ][ 'data' ]->post = get_post( $tr_product_id );
                }
            }else{
                if( !is_null( $tr_product_id ) ){
                    $cart->cart_contents[ $key ][ 'product_id' ] = intval( $tr_product_id );
                    $cart->cart_contents[ $key ][ 'data' ]->id = intval( $tr_product_id );
                    $cart->cart_contents[ $key ][ 'data' ]->post = get_post( $tr_product_id );
                }
            }

            if( !is_null( $tr_product_id ) ){
                $cart_item_data = $this->get_cart_item_data_from_cart( $cart->cart_contents[ $key ] );
                $new_key = $woocommerce->cart->generate_cart_id(
                                $cart->cart_contents[ $key ][ 'product_id' ],
                                $cart->cart_contents[ $key ][ 'variation_id' ],
                                $cart->cart_contents[ $key ][ 'variation' ],
                                $cart_item_data );
                $cart->cart_contents = apply_filters( 'wcml_update_cart_contents_lang_switch', $cart->cart_contents, $key, $new_key, $current_language );
                $new_cart_data[ $new_key ] = $cart->cart_contents[ $key ];
                $new_cart_data = apply_filters( 'wcml_cart_contents', $new_cart_data, $cart->cart_contents, $key, $new_key );
            }
        }

        $cart->cart_contents = $this->wcml_check_on_duplicate_products_in_cart( $new_cart_data );
        $woocommerce->session->cart = $cart;
        return $cart;
    }

    public function wcml_check_on_duplicate_products_in_cart( $cart_contents ){
        global $woocommerce;

        $exists_products = array();
        remove_action( 'woocommerce_before_calculate_totals', array( $this, 'woocommerce_calculate_totals' ), 100 );

        foreach( $cart_contents as $key => $cart_content ){
            $cart_contents = apply_filters( 'wcml_check_on_duplicated_products_in_cart', $cart_contents, $key, $cart_content );
            if( apply_filters( 'wcml_exception_duplicate_products_in_cart', false, $cart_content ) ){
                continue;
            }

            $quantity = $cart_content['quantity'];
            // unset unnecessary data to generate id to check
            unset( $cart_content['quantity'] );
            unset( $cart_content['line_total'] );
            unset( $cart_content['line_subtotal'] );
            unset( $cart_content['line_tax'] );
            unset( $cart_content['line_subtotal_tax'] );
            unset( $cart_content['line_tax_data'] );

            $search_key = md5( serialize( $cart_content ) );
            if( array_key_exists( $search_key, $exists_products ) ){
                unset( $cart_contents[ $key ] );
                $cart_contents[ $exists_products[ $search_key ] ][ 'quantity' ] = $cart_contents[ $exists_products[ $search_key ] ][ 'quantity' ] + $quantity;
                $woocommerce->cart->calculate_totals();
            }else{
                $exists_products[ $search_key ] = $key;
            }
        }

        add_action( 'woocommerce_before_calculate_totals', array( $this, 'woocommerce_calculate_totals' ), 100 );
        return $cart_contents;
    }

    public function get_cart_attribute_translation( $attr_key, $attribute, $variation_id, $current_language, $product_id, $tr_product_id ){

        $attr_translation = $attribute;

        if( !empty( $attribute ) ){
            //delete 'attribute_' at the beginning
            $taxonomy = substr( $attr_key, 10, strlen( $attr_key ) - 1 );

            if( taxonomy_exists( $taxonomy ) ){
                if( $this->woocommerce_wpml->attributes->is_translatable_attribute( $taxonomy ) ) {
                    $term_id = $this->woocommerce_wpml->terms->wcml_get_term_id_by_slug( $taxonomy, $attribute );
                    $trnsl_term_id = apply_filters( 'translate_object_id', $term_id, $taxonomy, true, $current_language );
                    $term = $this->woocommerce_wpml->terms->wcml_get_term_by_id( $trnsl_term_id, $taxonomy );
                    $attr_translation = $term->slug;
                }
            }else{

                $trnsl_attr = get_post_meta( $variation_id, $attr_key, true );

                if( $trnsl_attr ){
                    $attr_translation = $trnsl_attr;
                }else{
                    $attr_translation = $this->woocommerce_wpml->attributes->get_custom_attr_translation( $product_id, $tr_product_id, $taxonomy, $attribute );
                }
            }
        }

        return $attr_translation;
    }

    //get cart_item_data from existing cart array ( from session )
    public function get_cart_item_data_from_cart( $cart_contents ){
        unset( $cart_contents[ 'product_id' ] );
        unset( $cart_contents[ 'variation_id' ] );
        unset( $cart_contents[ 'variation' ] );
        unset( $cart_contents[ 'quantity' ] );
        unset( $cart_contents[ 'data' ] );

        return apply_filters( 'wcml_filter_cart_item_data', $cart_contents );
    }

    public function translate_cart_contents( $item, $values, $key ) {
        // translate the product id and product data
        $item[ 'product_id' ] = apply_filters( 'translate_object_id', $item[ 'product_id' ], 'product', true );
        if ($item[ 'variation_id' ]) {
            $item[ 'variation_id' ] = apply_filters( 'translate_object_id',$item[ 'variation_id' ], 'product_variation', true );
        }
        $product_id = $item[ 'variation_id' ] ? $item[ 'variation_id' ] : $item[ 'product_id' ];
        $item[ 'data' ]->post->post_title = get_the_title( $item[ 'product_id' ] );
        return $item;
    }

    public function translate_cart_subtotal( $cart ) {

        if( isset( $_SERVER['REQUEST_URI'] ) ){
            //special case: check if attachment loading
            $attachments = array( 'png', 'jpg', 'jpeg', 'gif', 'js', 'css' );

            foreach( $attachments as $attachment ){
                $match = preg_match( '/\.'.$attachment.'$/',  $_SERVER['REQUEST_URI'] );
                if( !empty( $match ) ){
                    return false;
                }
            }
        }

        if( apply_filters( 'wcml_calculate_totals_exception', true, $cart ) ){
            $cart->calculate_totals();
        }

    }

    // refresh cart total to return correct price from WC object
    public function wcml_refresh_cart_total() {
        WC()->cart->calculate_totals();
    }


    public function localize_flat_rates_shipping_classes(){
        global $woocommerce;

        if(is_ajax() && isset($_POST['action']) && $_POST['action'] == 'woocommerce_update_order_review'){
            $woocommerce->shipping->load_shipping_methods();
            $shipping_methods = $woocommerce->shipping->get_shipping_methods();
            foreach($shipping_methods as $method){
                if(isset($method->flat_rate_option)){
                    add_filter('option_' . $method->flat_rate_option, array($this, 'translate_shipping_class'));
                }
            }

        }
    }

    public function translate_shipping_class($rates){

        if(is_array($rates)){
            foreach($rates as $shipping_class => $value){
                $term_id = $this->woocommerce_wpml->terms->wcml_get_term_id_by_slug('product_shipping_class', $shipping_class );

                if($term_id && !is_wp_error($term_id)){
                    $translated_term_id = apply_filters( 'translate_object_id', $term_id, 'product_shipping_class', true);
                    if($translated_term_id != $term_id){
                        $term = $this->woocommerce_wpml->terms->wcml_get_term_by_id( $translated_term_id, 'product_shipping_class' );
                        unset($rates[$shipping_class]);
                        $rates[$term->slug] = $value;

                    }
                }
            }
        }
        return $rates;
    }

    public function filter_paypal_args( $args ) {
        $args['lc'] = $this->sitepress->get_current_language();

        //filter URL when default permalinks uses
        $wpml_settings = $this->sitepress->get_settings();
        if( $wpml_settings[ 'language_negotiation_type' ] == 3 ){
            $args[ 'notify_url' ] = str_replace( '%2F&', '&', $args[ 'notify_url' ] );
        }

        return $args;
    }
}