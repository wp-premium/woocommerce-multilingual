<?php

namespace OTGS\Installer;

class Collection {
	/**
	 * @var array
	 */
	private $array;

	private function __construct( array $array ) {
		$this->array = $array;
	}

	/**
	 * @param array $array
	 *
	 * @return Collection
	 */
	public static function of( array $array ) {
		return new static( $array );
	}

	/**
	 * @param callable $fn
	 *
	 * @return Collection
	 */
	public function filter( callable $fn ) {
		return self::of( array_filter( $this->array, $fn ) );
	}

	/**
	 * @param callable $fn
	 *
	 * @return Collection
	 */
	public function map( callable $fn ) {
		$keys = array_keys( $this->array );

		$items = array_map( $fn, $this->array, $keys );

		return self::of( array_combine( $keys, $items ) );
	}

	/**
	 * Converts array from key => vales to an array of pairs [ key, value ]
	 * @return Collection
	 */
	public function entities() {
		$toPairs = function ( $value, $key ) { return [ $key, $value ]; };

		return $this->map( $toPairs );
	}

	/**
	 * @param string $column
	 *
	 * @return Collection
	 */
	public function pluck( $column ) {
		return self::of( array_column( $this->array, $column ) );
	}

	/**
	 * @param callable $fn
	 * @param mixed    $initial
	 *
	 * @return mixed
	 */
	public function reduce( callable $fn, $initial = 0 ) {
		return array_reduce( $this->array, $fn, $initial );
	}

	/**
	 * @return Collection
	 */
	public function values() {
		return self::of( array_values( $this->array ) );
	}

	/**
	 * @param Collection $other
	 *
	 * @return Collection
	 */
	public function mergeRecursive( array $other ) {
		return self::of( array_merge_recursive( $this->array, $other ) );
	}

	/**
	 * @param string $key
	 *
	 * @return mixed|Collection|NullCollection|
	 */
	public function get( $key = null ) {
		if ( null !== $key ) {
			$data = array_key_exists( $key, $this->array ) ? $this->array[ $key ] : null;
			if ( is_array( $data ) ) {
				return self::of( $data );
			} elseif ( is_null( $data ) ) {
				return new NullCollection();
			}

			return $data;
		}

		return $this->array;
	}

	public function getOrNull( $key = null ) {
		return $this->get( $key );
	}

	public function contains( $value ) {
		return in_array( $value, $this->array, true );
	}

	public function head() {
		if ( count( $this->array ) ) {
			$temp   = array_values( $this->array );
			$result = $temp[0];
			if ( is_array( $result ) ) {
				return self::of( $result );
			}

			return $result;
		}

		return new NullCollection();
	}
}

class NullCollection {

	public function map( callable $fn ) { return $this; }

	public function filter( callable $fn ) { return $this; }

	public function head() { return $this; }

	public function pluck() { return $this; }

	public function get() { return $this; }

	public function getOrNull() { return null; }

}
