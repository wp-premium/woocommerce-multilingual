<?php

class WooCommerce_Functions_Wrapper{

    public static function is_deprecated(){

        if( version_compare( WC_VERSION , '3.0.0', '<' ) ){
            return true;
        }else{
            return false;
        }

    }

    public static function get_product_id( $product ){
        if( self::is_deprecated() ){
            return $product->id;
        }else{
            return $product->get_id();
        }
    }

    public static function get_product_type( $product_id ){
        if( self::is_deprecated() ){
            $product = wc_get_product( $product_id );
            return $product->product_type;
        }else{
            return WC_Product_Factory::get_product_type( $product_id );
        }
    }

    public static function reduce_stock( $product_id, $qty ){
        if( self::is_deprecated() ){
            $product = wc_get_product( $product_id );
            return $product->reduce_stock( $qty );
        }else{
            return wc_update_product_stock( $product_id, $qty, 'decrease' );
        }
    }

    public static function increase_stock( $product_id, $qty ){
        if( self::is_deprecated() ){
            $product = wc_get_product( $product_id );
            return $product->increase_stock( $qty );
        }else{
            return wc_update_product_stock( $product_id, $qty, 'increase' );
        }
    }

    public static function set_stock( $product_id, $qty ){
        if( self::is_deprecated() ){
            $product = wc_get_product( $product_id );
            return $product->set_stock( $qty );
        }else{
            return wc_update_product_stock( $product_id, $qty, 'set' );
        }
    }

    public static function get_order_currency( $order ){
        if( self::is_deprecated() ){
            return $order->get_order_currency();
        }else{
            return $order->get_currency();
        }
    }
    
    public static function get_item_downloads( $object, $item ){
        if( self::is_deprecated() ){
            return $object->get_item_downloads( $item );
        }else{
            return $item->get_item_downloads( );
        }
    }

    public static function get_order_id( $order ){
        if( self::is_deprecated() ){
            return $order->id;
        }else{
            return $order->get_id();
        }
    }

}

?>
