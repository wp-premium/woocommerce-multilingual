<?php

/**
 * Class WCML_Not_Supported_Payment_Gateway
 */
class WCML_Not_Supported_Payment_Gateway extends WCML_Payment_Gateway{

	public function get_output_model() {
		return [
			'id'          => $this->get_id(),
			'title'       => $this->get_title(),
			'isSupported' => false,
			'settings'    => [],
			'tooltip'     => '',
			'strings'     => [
				'labelNotYetSupported' => __( 'Not yet supported', 'woocommerce-multilingual' ),
			],
		];
	}
}