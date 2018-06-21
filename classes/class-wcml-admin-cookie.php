<?php

class WCML_Admin_Cookie{

	/** @var string */
	private $name;

	/**
	 * WCML_Admin_Cookie constructor.
	 *
	 * @param $name
	 */
	public function __construct( $name ) {
		$this->name = $name;
	}

	/**
	 * @param mixed $value
	 * @param int $expiration
	 */
	public function set_value( $value, $expiration = null ){
		if( null === $expiration ){
			$expiration = time() + DAY_IN_SECONDS;
		}
		wc_setcookie( $this->name, $value, $expiration );
	}

	/**
	 * @return mixed
	 */
	public function get_value() {
		$value = null;
		if ( isset( $_COOKIE [ $this->name ] ) ){
			$value = $_COOKIE[ $this->name ];
		}
		return $value;
	}

}