<?php

class WCML_Currency_Switcher {

    private $woocommerce_wpml;

    public function __construct() {
        global $woocommerce_wpml;

        $this->woocommerce_wpml =& $woocommerce_wpml;

        add_action( 'init', array($this, 'init'), 5 );

    }

    public function init() {

        add_action( 'wp_ajax_wcml_currencies_order', array($this, 'wcml_currencies_order') );
        add_action( 'wp_ajax_wcml_currencies_switcher_preview', array($this, 'wcml_currencies_switcher_preview') );

        add_action( 'currency_switcher', array($this, 'currency_switcher') );
        add_shortcode( 'currency_switcher', array($this, 'currency_switcher_shortcode') );

        // Built in currency switcher
        add_action( 'woocommerce_product_meta_start', array($this, 'show_currency_switcher') );

    }

    public function wcml_currencies_order() {
        $nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if ( !$nonce || !wp_verify_nonce( $nonce, 'set_currencies_order_nonce' ) ) {
            die('Invalid nonce');
        }
        global $woocommerce_wpml;

        $woocommerce_wpml->settings['currencies_order'] = explode( ';', $_POST['order'] );
        $woocommerce_wpml->update_settings();
        echo json_encode( array('message' => __( 'Currencies order updated', 'woocommerce-multilingual' )) );
        die;
    }

    public function wcml_currencies_switcher_preview() {
        $nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if ( !$nonce || !wp_verify_nonce( $nonce, 'wcml_currencies_switcher_preview' ) ) {
            die('Invalid nonce');
        }

        echo $this->currency_switcher(
            array(
                'format'         => $_POST['template'] ? $_POST['template'] : '%name% (%symbol%) - %code%',
                'switcher_style' => $_POST['switcher_type'],
                'orientation'    => $_POST['orientation']
            )
        );

        die();
    }

    public function currency_switcher_shortcode( $atts ) {
        extract( shortcode_atts( array(), $atts ) );

        ob_start();
        $this->currency_switcher( $atts );
        $html = ob_get_contents();
        ob_end_clean();

        return $html;
    }

    public function currency_switcher( $args = array() ) {
        global $sitepress;

        if ( is_page( wc_get_page_id( 'myaccount' ) ) ) {
            return '';
        }

        $wcml_settings = $this->woocommerce_wpml->get_settings();

        $preview = '';

        if ( isset($wcml_settings['display_custom_prices']) && $wcml_settings['display_custom_prices'] ) {

            if ( is_page( wc_get_page_id( 'cart' ) ) ||
                is_page( wc_get_page_id( 'checkout' ) )
            ) {
                return '';
            } elseif ( is_product() ) {
                $current_product_id = wc_get_product()->id;
                $original_product_language = $this->woocommerce_wpml->products->get_original_product_language( $current_product_id );

                if ( !get_post_meta( apply_filters( 'translate_object_id', $current_product_id, get_post_type( $current_product_id ), true, $original_product_language ), '_wcml_custom_prices_status', true ) ) {
                    return '';
                }
            }

        }

        if ( !isset($args['switcher_style']) ) {
            $args['switcher_style'] = isset($wcml_settings['currency_switcher_style']) ? $wcml_settings['currency_switcher_style'] : 'dropdown';
        }

        if ( !isset($args['orientation']) ) {
            $args['orientation'] = isset($wcml_settings['wcml_curr_sel_orientation']) ? $wcml_settings['wcml_curr_sel_orientation'] : 'vertical';
        }

        if ( !isset($args['format']) ) {
            $args['format'] = isset($wcml_settings['wcml_curr_template']) && $wcml_settings['wcml_curr_template'] != '' ?
                $wcml_settings['wcml_curr_template'] : '%name% (%symbol%) - %code%';
        }

        $wc_currencies = get_woocommerce_currencies();

        if ( !isset($wcml_settings['currencies_order']) ) {
            $currencies = $this->woocommerce_wpml->multi_currency->get_currency_codes();
        } else {
            $currencies = $wcml_settings['currencies_order'];
        }

        if( !is_admin() ){
            foreach ( $currencies as $k => $currency ) {
                if ( $wcml_settings['currency_options'][$currency]['languages'][$sitepress->get_current_language()] != 1 ) {
                    unset( $currencies[$k] );
                }
            }
        }

        if( count( $currencies ) > 1 ) {

            if ( $args['switcher_style'] == 'dropdown' ) {
                $preview .= '<select class="wcml_currency_switcher">';
            } else {
                $args['orientation'] = $args['orientation'] == 'horizontal' ? 'curr_list_horizontal' : 'curr_list_vertical';
                $preview .= '<ul class="wcml_currency_switcher ' . $args['orientation'] . '">';
            }

            foreach ( $currencies as $currency ) {
                $currency_format = preg_replace( array('#%name%#', '#%symbol%#', '#%code%#'),
                    array($wc_currencies[$currency], get_woocommerce_currency_symbol( $currency ), $currency), $args['format'] );

                if ( $args['switcher_style'] == 'dropdown' ) {
                    $selected = $currency == $this->woocommerce_wpml->multi_currency->get_client_currency() ? ' selected="selected"' : '';
                    $preview .= '<option value="' . $currency . '"' . $selected . '>' . $currency_format . '</option>';
                } else {
                    $selected = $currency == $this->woocommerce_wpml->multi_currency->get_client_currency() ? ' class="wcml-active-currency"' : '';
                    $preview .= '<li rel="' . $currency . '" ' . $selected . ' >' . $currency_format . '</li>';
                }
            }

            if ( $args['switcher_style'] == 'dropdown' ) {
                $preview .= '</select>';
            } else {
                $preview .= '</ul>';
            }

        } else{

            if( is_admin() ){

                $preview .= '<i>' . __("You haven't added any secondary currencies.", 'woocommerce-multilingual') . '</i>';

            }

        }

        if ( !isset($args['echo']) || $args['echo'] ) {
            echo $preview;
        } else {
            return $preview;
        }

    }

    public function show_currency_switcher() {
        $settings = $this->woocommerce_wpml->get_settings();

        if ( is_product() && isset($settings['currency_switcher_product_visibility']) && $settings['currency_switcher_product_visibility'] === 1 ) {
            echo(do_shortcode( '[currency_switcher]' ));
            echo '<br />';
        }

    }


}