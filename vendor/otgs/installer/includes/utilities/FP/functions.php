<?php

namespace OTGS\Installer\FP;

/**
 * Returns new function which will behave like $function with
 * predefined left arguments passed to partial
 *
 * @param callable $function
 * @param mixed ...
 *
 * @return callable
 */
function partial( callable $function, $dummy ) {
	$args = array_slice( func_get_args(), 1 );

	return function () use ( $function, $args ) {
		return call_user_func_array( $function, array_merge( $args, func_get_args() ) );
	};
}
