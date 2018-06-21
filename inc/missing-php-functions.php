<?php

/* PHP 5.3 - start */

if ( false === function_exists( 'lcfirst' ) ) {
	/**
	 * Make a string's first character lowercase
	 *
	 * @param string $str
	 *
	 * @return string the resulting string.
	 */
	function lcfirst( $str ) {
		$str[0] = strtolower( $str[0] );

		return (string) $str;
	}
}

if ( get_magic_quotes_gpc() ) {
	if ( ! function_exists( 'stripslashes_deep' ) ) {
		function stripslashes_deep( $value ) {
			$value = is_array( $value ) ?
				array_map( 'stripslashes_deep', $value ) :
				stripslashes( $value );

			return $value;
		}
	}

	$_POST   = array_map( 'stripslashes_deep', $_POST );
	$_GET    = array_map( 'stripslashes_deep', $_GET );
	$_COOKIE = array_map( 'stripslashes_deep', $_COOKIE );
}
/* PHP 5.3 - end */

add_action( 'plugins_loaded', 'wcml_check_wpml_functions' );

function wcml_check_wpml_functions() {

	if ( ! has_filter( 'translate_object_id' ) ) {
		add_filter( 'translate_object_id', 'icl_object_id', 10, 4 );
	}

	if ( ! has_action( 'wpml_register_single_string' ) ) {
		if ( function_exists( 'wpml_register_single_string_action' ) ) {
			add_action( 'wpml_register_single_string', 'wpml_register_single_string_action', 10, 4 );
		} elseif ( function_exists( 'icl_register_string' ) ) {
			add_action( 'wpml_register_single_string', 'icl_register_string', 10, 4 );
		}
	}

}

// two WordPress functions that were added in 4.4.0
if ( version_compare( $GLOBALS['wp_version'], '4.4.0', '<' ) ) {

	if ( ! function_exists( 'get_the_post_thumbnail_url' ) ) {
		function get_the_post_thumbnail_url( $post = null, $size = 'post-thumbnail' ) {
			$post_thumbnail_id = get_post_thumbnail_id( $post );
			if ( ! $post_thumbnail_id ) {
				return false;
			}

			return wp_get_attachment_image_url( $post_thumbnail_id, $size );
		}
	}

	if ( ! function_exists( 'wp_get_attachment_image_url' ) ) {
		function wp_get_attachment_image_url( $attachment_id, $size = 'thumbnail', $icon = false ) {
			$image = wp_get_attachment_image_src( $attachment_id, $size, $icon );

			return isset( $image['0'] ) ? $image['0'] : false;
		}
	}

	if ( ! function_exists( 'rest_get_url_prefix' ) ) {
		function rest_get_url_prefix() {
			/**
			 * Filters the REST URL prefix.
			 *
			 * @param string $prefix URL prefix. Default 'wp-json'.
			 */
			return apply_filters( 'rest_url_prefix', 'wp-json' );
		}
	}

}

// WC function which needs for front ( combination of Dokan + Bookings ) but defined only for admin
if ( !is_admin() && ! function_exists( 'woocommerce_wp_text_input' ) ) {
	/**
	 * Output a text input box.
	 *
	 * @param array $field
	 */
	function woocommerce_wp_text_input( $field ) {
		global $thepostid, $post;

		$thepostid              = empty( $thepostid ) ? $post->ID : $thepostid;
		$field['placeholder']   = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
		$field['class']         = isset( $field['class'] ) ? $field['class'] : 'short';
		$field['style']         = isset( $field['style'] ) ? $field['style'] : '';
		$field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
		$field['value']         = isset( $field['value'] ) ? $field['value'] : get_post_meta( $thepostid, $field['id'], true );
		$field['name']          = isset( $field['name'] ) ? $field['name'] : $field['id'];
		$field['type']          = isset( $field['type'] ) ? $field['type'] : 'text';
		$field['desc_tip']      = isset( $field['desc_tip'] ) ? $field['desc_tip'] : false;
		$data_type              = empty( $field['data_type'] ) ? '' : $field['data_type'];

		switch ( $data_type ) {
			case 'price':
				$field['class'] .= ' wc_input_price';
				$field['value']  = wc_format_localized_price( $field['value'] );
				break;
			case 'decimal':
				$field['class'] .= ' wc_input_decimal';
				$field['value']  = wc_format_localized_decimal( $field['value'] );
				break;
			case 'stock':
				$field['class'] .= ' wc_input_stock';
				$field['value']  = wc_stock_amount( $field['value'] );
				break;
			case 'url':
				$field['class'] .= ' wc_input_url';
				$field['value']  = esc_url( $field['value'] );
				break;

			default:
				break;
		}

		// Custom attribute handling
		$custom_attributes = array();

		if ( ! empty( $field['custom_attributes'] ) && is_array( $field['custom_attributes'] ) ) {

			foreach ( $field['custom_attributes'] as $attribute => $value ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $value ) . '"';
			}
		}

		echo '<p class="form-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '">
	<label for="' . esc_attr( $field['id'] ) . '">' . wp_kses_post( $field['label'] ) . '</label>';

		if ( ! empty( $field['description'] ) && false !== $field['desc_tip'] ) {
			echo wc_help_tip( $field['description'] );
		}

		echo '<input type="' . esc_attr( $field['type'] ) . '" class="' . esc_attr( $field['class'] ) . '" style="' . esc_attr( $field['style'] ) . '" name="' . esc_attr( $field['name'] ) . '" id="' . esc_attr( $field['id'] ) . '" value="' . esc_attr( $field['value'] ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" ' . implode( ' ', $custom_attributes ) . ' /> ';

		if ( ! empty( $field['description'] ) && false === $field['desc_tip'] ) {
			echo '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>';
		}

		echo '</p>';
	}
}
