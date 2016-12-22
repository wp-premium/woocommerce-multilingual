<?php

class WCML_Links {

    public static function generate_tracking_link( $link, $term = false, $content = false, $id = false ) {

        $params = '?utm_source=wcml-admin&utm_medium=plugin&utm_term=';
        $params .= $term ? $term : 'WPML';
        $params .= '&utm_content=';
        $params .= $content ? $content : 'required-plugins';
        $params .= '&utm_campaign=WCML';

        if ( $id ) {
            $params .= $id;
        }
        return $link . $params;

    }

    public static function filter_woocommerce_redirect_location( $link ) {
        global $sitepress;
        return html_entity_decode( $sitepress->convert_url( $link ) );
    }

}