<?php
  
class WCML_Currency_Switcher_Widget extends WP_Widget {

    function __construct() {

        parent::__construct( 'currency_sel_widget', __('Currency switcher', 'woocommerce-multilingual'), __('Currency switcher', 'woocommerce-multilingual'));
    }

    function widget($args, $instance) {

        echo $args['before_widget'];

        do_action('currency_switcher');

        echo $args['after_widget'];
    }

    function form( $instance ) {

        printf('<p><a href="%s">%s</a></p>',admin_url('admin.php?page=wpml-wcml&tab=multi-currency#currency-switcher'),__('Configure options', 'woocommerce-multilingual'));
        return;

    }
}