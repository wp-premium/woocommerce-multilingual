<?php
/**
 * @author  OnTheGo Systems
 * @package WPML\Templates
 */

namespace WPML\Templates\PHP;

class Model {
	/**
	 * @var \WPML\Templates\PHP\Model[]|mixed[]
	 */
	private $attributes = [];

	/**
	 * Model constructor.
	 *
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
	 * @see \WPML\Templates\PHP\Model::__toString
	 */
	public function __get( $name ) {
		if ( ! array_key_exists( $name, $this->attributes ) ) {
			$this->attributes[ $name ] = new Model();
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
			$is_assoc = is_array( $value ) && count( array_filter( array_keys( $value ), 'is_string' ) ) > 0;
			if($is_assoc) {
				$value = new Model( $value );
			}
		}
		$this->attributes[ $name ] = $value;
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
		return $this->__get( $name ) === '' || ( ( $this->__get( $name ) instanceof Model ) && ! $this->__get( $name )->getAttributes() );
	}

	/**
	 * @return mixed[]|\WPML\Templates\PHP\Model[]
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
