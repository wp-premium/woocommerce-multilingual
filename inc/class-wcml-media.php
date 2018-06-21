<?php

class WCML_Media{

    /** @var woocommerce_wpml */
    private $woocommerce_wpml;
    /** @var  SitePress */
    private $sitepress;
    /** @var  wpdb */
    private $wpdb;

    public $settings = array();

    public function __construct( $woocommerce_wpml, $sitepress, $wpdb ){
        $this->woocommerce_wpml = $woocommerce_wpml;
        $this->sitepress        = $sitepress;
        $this->wpdb             = $wpdb;
    }

    public function product_images_ids( $product_id ){
        $product_images_ids = array();

        //thumbnail image
        $tmb = get_post_meta( $product_id, '_thumbnail_id', true );
        if( $tmb ) {
            $product_images_ids[] = $tmb;
        }

        //product gallery
        $product_gallery = get_post_meta( $product_id, '_product_image_gallery', true );
        if( $product_gallery ) {
            $product_gallery = explode( ',', $product_gallery );
            foreach( $product_gallery as $img ){
                if( !in_array( $img, $product_images_ids ) ){
                    $product_images_ids[] = $img;
                }
            }
        }

        foreach( wp_get_post_terms( $product_id, 'product_type', array( "fields" => "names" ) ) as $type ){
            $product_type = $type;
        }

        if( isset( $product_type ) && $product_type == 'variable' ){
            $get_post_variations_image = $this->wpdb->get_col(
                $this->wpdb->prepare(
                    "SELECT pm.meta_value FROM {$this->wpdb->posts} AS p
                                                LEFT JOIN {$this->wpdb->postmeta} AS pm ON p.ID = pm.post_id
                                                WHERE pm.meta_key='_thumbnail_id'
                                                  AND p.post_status IN ('publish','private')
                                                  AND p.post_type = 'product_variation'
                                                  AND p.post_parent = %d
                                                ORDER BY ID", $product_id )
            );
            foreach( $get_post_variations_image as $variation_image ){
                if( $variation_image && !in_array( $variation_image, $product_images_ids ) ){
                    $product_images_ids[] = $variation_image;
                }
            }
        }

        foreach( $product_images_ids as $key => $image ){
            if( ! get_post_status ( $image ) ){
                unset( $product_images_ids[ $key ] );
            }
        }

        return $product_images_ids;
    }

}