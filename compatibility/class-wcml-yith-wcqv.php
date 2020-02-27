<?php

class WCML_YITH_WCQV {

	public function add_hooks() {

		add_filter( 'wcml_multi_currency_ajax_actions', [ $this, 'ajax_action_needs_multi_currency' ] );

	}

	public function ajax_action_needs_multi_currency( $actions ) {

		$actions[] = 'yith_load_product_quick_view';

		return $actions;
	}

}
