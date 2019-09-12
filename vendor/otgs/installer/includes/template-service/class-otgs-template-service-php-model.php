<?php

class OTGS_Template_Service_Php_Model {
	/**
	 * @var OTGS_Template_Service_Php_Model[]|mixed[]
	 */
	private $attributes = [];

	/**
	 * @param array $data
	 */
	public function __construct( $data = [] ) {
		foreach ( $data as $key => $value ) {
			$this->__set( $key, $value );
		}
	}

	/**
	 * If a property does not exist, the method will create it as an "empty" instance of `Model`
	 * so that children properties can be called without throwing errors.
	 *
	 * @param string $name
	 *
	 * @return mixed|null
	 * @see OTGS_Template_Service_Php_Model::__toString
	 */
	public function __get( $name ) {
		if ( ! array_key_exists( $name, $this->attributes ) ) {
			$this->attributes[ $name ] = new OTGS_Template_Service_Php_Model();
		}

		return $this->attributes[ $name ];
	}

	/**
	 * It ensures that $value is always either an array or a primitive type.
	 *
	 * @param string $name
	 * @param mixed  $value
	 */
	public function __set( $name, $value ) {
		if ( is_object( $value ) ) {
			$value = get_object_vars( $value );
		}
		if ( is_array( $value ) ) {
			if( $this->isAssoc( $value ) ) {
				$value = new OTGS_Template_Service_Php_Model( $value );
			} else {
				foreach ($value as $id => $element) {
					$value[$id] = $this->isAssoc( $element ) ? new OTGS_Template_Service_Php_Model( $element ) : $element;
				}
			}
		}
		$this->attributes[ $name ] = $value;
	}

	/**
	 * @param mixed  $value
	 *
	 * @return bool
	 */
	private function isAssoc( $value ) {
		return is_array( $value ) && count( array_filter( array_keys( $value ), 'is_string' ) ) > 0;
	}

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	public function hasValue( $name ) {
		return ! $this->isNull( $name ) && ! $this->isEmpty( $name );
	}

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	public function isNull( $name ) {
		return $this->__get( $name ) === null;
	}

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	public function isEmpty( $name ) {
		return $this->__get( $name ) === ''
		       || ( ( $this->__get( $name ) instanceof OTGS_Template_Service_Php_Model )
		            && ! $this->__get( $name )->getAttributes()
		       );
	}

	/**
	 * @return mixed[]|OTGS_Template_Service_Php_Model[]
	 */
	public function getAttributes() {
		return $this->attributes;
	}

	/**
	 * This logic allows using the model in a template even when referring to properties which do no exist.
	 *
	 * Example:
	 * `<h1><?php echo esc_html( $model->non_existing_property->title ); ?></h1>` Will output an empty string instead of throwing an error
	 *
	 * @return string
	 */
	public function __toString() {
		if ( count( $this->attributes ) === 0 ) {
			return '';
		}
		if ( count( $this->attributes ) === 1 ) {
			return array_values( $this->attributes )[0];
		}

		return wp_json_encode( $this->attributes );
	}

}