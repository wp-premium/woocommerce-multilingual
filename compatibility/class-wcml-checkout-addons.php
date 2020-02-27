<?php
/**
 * Compatibility class for  wc_checkout_addons plugin.
 *
 * @author konrad
 */
class WCML_Checkout_Addons {

	public function add_hooks() {
		add_filter( 'option_wc_checkout_add_ons', [ $this, 'option_wc_checkout_add_ons' ], 10, 2 );
	}

	public function option_wc_checkout_add_ons( $option_value, $option_name = null ) {
		if ( is_array( $option_value ) ) {
			foreach ( $option_value as $addon_id => $addon_conf ) {
				$addon_conf = $this->handle_option_part( $addon_id, $addon_conf );
				if ( isset( $addon_conf['options'] ) ) {
					foreach ( $addon_conf['options'] as $index => $fields ) {
						$addon_conf['options'][ $index ] = $this->handle_option_part( $index, $fields );
					}
				}
				$option_value[ $addon_id ] = $addon_conf;
			}
		}

		return $option_value;
	}

	private function handle_option_part( $index, $conf ) {
		$conf = $this->register_or_translate( 'label', $conf, $index );
		$conf = $this->register_or_translate( 'description', $conf, $index );
		$conf = $this->adjust_price( $conf );
		return $conf;
	}

	private function register_or_translate( $element, $conf, $index ) {
		if ( isset( $conf[ $element ] ) ) {
			$string = $conf[ $element ];
			$key    = $index . '_' . $element . '_' . md5( $string );
			if ( $this->is_default_language() ) {
				do_action( 'wpml_register_single_string', 'wc_checkout_addons', $key, $string );
			} else {
				$conf[ $element ] = apply_filters( 'wpml_translate_single_string', $string, 'wc_checkout_addons', $key );
			}
		}
		return $conf;
	}

	private function adjust_price( $conf ) {
		if ( isset( $conf['adjustment'], $conf['adjustment_type'] )
			 && $conf['adjustment_type'] === 'fixed'
			 && ! $this->is_default_language() ) {
			$conf['adjustment'] = apply_filters( 'wcml_raw_price_amount', $conf['adjustment'] );
		}
		return $conf;
	}

	private function is_default_language() {
		return apply_filters( 'wpml_current_language', null ) === apply_filters( 'wpml_default_language', null );
	}
}
