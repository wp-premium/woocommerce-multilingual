<?php

class WCML_Klarna_Gateway{

    public function add_hooks(){

        add_filter( 'wcml_multi_currency_ajax_actions', array( $this, 'ajax_action_needs_multi_currency' ) );

    }

    function ajax_action_needs_multi_currency( $actions ){

        $actions[] = 'klarna_checkout_cart_callback_update';

        return $actions;
    }

}
