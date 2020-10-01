<?php

namespace WCML\MO;

class Hooks implements \IWPML_Backend_Action, \IWPML_Frontend_Action {

	public function add_hooks() {
		add_action( 'wpml_language_has_switched', [ $this, 'forceRemoveUnloadedDomain' ], 0 );
	}

	public function forceRemoveUnloadedDomain() {
		if ( isset( $GLOBALS['l10n_unloaded']['woocommerce'] ) ) {
			unset( $GLOBALS['l10n_unloaded']['woocommerce'] );
		}
	}
}
