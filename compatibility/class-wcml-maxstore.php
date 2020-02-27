<?php

class WCML_MaxStore {

	public function add_hooks() {

		add_filter( 'wcml_force_reset_cart_fragments', [ $this, 'wcml_force_reset_cart_fragments' ] );

	}

	public function wcml_force_reset_cart_fragments() {

		return 1;

	}

}
