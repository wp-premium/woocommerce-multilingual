<?php

class WCML_Flatsome {

	public function __construct() {
		add_filter( 'wcml_multi_currency_ajax_actions', [ $this, 'add_action_to_multi_currency_ajax' ] );
	}

	public function add_action_to_multi_currency_ajax( $actions ) {

		$actions[] = 'ux_quickview';
		$actions[] = 'flatsome_ajax_search_products';

		return $actions;
	}

}
