<?php

class OTGS_Installer_Instance {

	/**
	 * @var string
	 */
	public $bootfile;

	/**
	 * @var string
	 */
	public $version;

	/**
	 * @var string
	 */
	public $high_priority;

	/**
	 * @var bool
	 */
	public $delegated;

	/**
	 * @param string $bootfile
	 *
	 * @return $this
	 */
	public function set_bootfile( $bootfile ) {
		$this->bootfile = $bootfile;
		return $this;
	}

	/**
	 * @param string $high_priority
	 *
	 * @return $this
	 */
	public function set_high_priority( $high_priority ) {
		$this->high_priority = $high_priority;
		return $this;
	}

	/**
	 * @param string $version
	 *
	 * @return $this
	 */
	public function set_version( $version ) {
		$this->version = $version;
		return $this;
	}

	/**
	 * @param bool $delegated
	 *
	 * @return $this
	 */
	public function set_delegated( $delegated ) {
		$this->delegated = (bool) $delegated;
		return $this;
	}
}