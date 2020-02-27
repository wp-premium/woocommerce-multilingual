<?php

class WCML_Aurum {

	public function __construct() {

		add_filter( 'wcml_multi_currency_ajax_actions', [ $this, 'add_ajax_action' ] );
	}

	public function add_ajax_action( $actions ) {

		$actions[] = 'lab_wc_add_to_cart';

		return $actions;
	}

}
