<?php

class WCML_ATE_Activate_Synchronization implements IWPML_Action, IWPML_Backend_Action_Loader {
	public function add_hooks() {
		if ( isset( $_GET['page'] ) && 'wpml-wcml' === $_GET['page'] ) {
			add_filter( 'wpml_tm_load_ate_jobs_synchronization', '__return_true' );
		}
	}

	/**
	 * @return WCML_ATE_Activate_Synchronization
	 */
	public function create() {
		return $this;
	}
}
