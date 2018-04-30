<?php

/**
 * Class WCML_Dynamic_Pricing
 */
class WCML_Dynamic_Pricing {

	public function add_hooks() {

		if ( ! is_admin() ) {
			add_filter( 'wc_dynamic_pricing_load_modules', array( $this, 'filter_price' ) );
			add_filter( 'woocommerce_dynamic_pricing_is_applied_to', array(
				$this,
				'woocommerce_dynamic_pricing_is_applied_to'
			), 10, 5 );
			add_filter( 'woocommerce_dynamic_pricing_get_rule_amount', array(
				$this,
				'woocommerce_dynamic_pricing_get_rule_amount'
			), 10, 2 );
			add_filter( 'dynamic_pricing_product_rules', array( $this, 'dynamic_pricing_product_rules' ) );
			add_filter( 'wcml_calculate_totals_exception', array( $this, 'calculate_totals_exception' ) );
		}
		add_filter( 'woocommerce_product_get__pricing_rules', array( $this, 'translate_variations_in_rules' ) );

	}

	/**
	 * @param $modules
	 *
	 * @return mixed
	 */
	function filter_price( $modules ) {

		foreach ( $modules as $mod_key => $module ) {
			if ( isset( $module->available_rulesets ) ) {
				$available_rulesets = $module->available_rulesets;
				foreach ( $available_rulesets as $rule_key => $available_ruleset ) {
					$rules = $available_ruleset['rules'];
					if ( $rules ) {
						foreach ( $rules as $r_key => $rule ) {
							if ( 'fixed_product' === $rule['type'] ) {
								$rules[ $r_key ]['amount'] = apply_filters( 'wcml_raw_price_amount', $rule['amount'] );
							}
						}
						$modules[ $mod_key ]->available_rulesets[ $rule_key ]['rules'] = $rules;
					}
				}
			}
		}

		return $modules;
	}


	/**
	 * @param bool                           $process_discounts
	 * @param WC_Product                     $_product
	 * @param int                            $module_id
	 * @param WC_Dynamic_Pricing_Simple_Base $obj
	 * @param array|int                      $cat_ids
	 *
	 * @return bool|WP_Error
	 */
	function woocommerce_dynamic_pricing_is_applied_to( $process_discounts, $_product, $module_id, $obj, $cat_ids ) {
		if ( $cat_ids && ( ! empty( $obj->available_rulesets ) || ! empty( $obj->adjustment_sets ) ) ) {

			if ( ! is_array( $cat_ids ) ) {
				$cat_ids = array( $cat_ids );
			}

			foreach ( $cat_ids as $i => $cat_id ) {
				$cat_ids[$i] = apply_filters( 'translate_object_id', $cat_id, 'product_cat', true );
			}

			$process_discounts = is_object_in_term( WooCommerce_Functions_Wrapper::get_product_id( $_product ), 'product_cat', $cat_ids );
		}

		return $process_discounts;
	}


	/**
	 * @param $amount
	 * @param $rule
	 *
	 * @return mixed|void
	 */
	function woocommerce_dynamic_pricing_get_rule_amount( $amount, $rule ) {

		if ( 'price_discount' === $rule['type'] || 'fixed_price' === $rule['type'] ) {
			$amount = apply_filters( 'wcml_raw_price_amount', $amount );
		}

		return $amount;
	}


	/**
	 * @param $rules
	 *
	 * @return array
	 */
	function dynamic_pricing_product_rules( $rules ) {
		if ( is_array( $rules ) ) {
			foreach ( $rules as $r_key => $rule ) {
				foreach ( $rule['rules'] as $key => $product_rule ) {
					if ( 'price_discount' === $product_rule['type'] || 'fixed_price' === $product_rule['type'] ) {
						$rules[ $r_key ]['rules'][ $key ]['amount'] = apply_filters( 'wcml_raw_price_amount', $product_rule['amount'] );
					}
				}
			}
		}
		return $rules;
	}

	/**
	 * @return bool
	 */
	function calculate_totals_exception() {
		return false;
	}

	/**
	 * @param $rules
	 *
	 * @return array
	 */
	function translate_variations_in_rules( $rules ) {
		if ( is_array( $rules ) ) {
			foreach ( $rules as $r_key => $rule ) {
				if ( isset( $rule['variation_rules']['args']['variations'] ) ) {
					foreach ( $rule['variation_rules']['args']['variations'] as $i => $variation_id ) {
						$rules[ $r_key ]['variation_rules']['args']['variations'][ $i ] = apply_filters( 'translate_object_id', $variation_id, 'product_variation', true );
					}
				}
			}
		}

		return $rules;
	}

}
