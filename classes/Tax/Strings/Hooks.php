<?php

namespace WCML\Tax\Strings;

use IWPML_Backend_Action;
use IWPML_Frontend_Action;

class Hooks implements IWPML_Backend_Action, IWPML_Frontend_Action {

	const STRINGS_CONTEXT = 'admin_texts_woocommerce_tax';

	public function add_hooks() {
		add_action( 'woocommerce_tax_rate_added', [ $this, 'registerLabelString' ], 10, 2 );
		add_action( 'woocommerce_tax_rate_updated', [ $this, 'registerLabelString' ], 10, 2 );
		add_filter( 'woocommerce_rate_label', [ $this, 'translateLabelString' ], 10, 2 );
	}

	/**
	 * @param string $label
	 * @param int $taxId
	 *
	 * @return string
	 */
	public function translateLabelString( $label, $taxId ) {

		$stringId = icl_get_string_id( $label, self::STRINGS_CONTEXT, $this->getStringName( $taxId ) );

		if ( ! $stringId ) {
			$this->migrateStringToTaxIdName( $taxId, $label );
		}

		return icl_translate( self::STRINGS_CONTEXT, $this->getStringName( $taxId ), $label );
	}

	/**
	 * @param int $taxId
	 * @param array $taxRate
	 */
	public function registerLabelString( $taxId, $taxRate ) {
		if ( ! empty( $taxRate['tax_rate_name'] ) ) {
			$this->registerString( $taxId, $taxRate['tax_rate_name'] );
		}
	}

	/**
	 * @param int $taxId
	 * @param string $label
	 *
	 * @return int
	 */
	private function registerString( $taxId, $label ) {
		return icl_register_string( self::STRINGS_CONTEXT, $this->getStringName( $taxId ), $label );
	}

	/**
	 * migration from WCML < 4.9.0
	 *
	 * @param int $taxId
	 * @param string $label
	 */
	private function migrateStringToTaxIdName( $taxId, $label ) {
		$newStringId = $this->registerString( $taxId, $label );

		$oldStringId = icl_get_string_id( $label, 'woocommerce taxes', $label );

		$oldStringTranslations = icl_get_string_translations_by_id( $oldStringId );

		foreach ( $oldStringTranslations as $languageCode => $translation ) {
			icl_add_string_translation(
				$newStringId,
				$languageCode,
				$translation['value'],
				ICL_STRING_TRANSLATION_COMPLETE
			);
		}
	}

	/**
	 * @param int $taxId
	 *
	 * @return string
	 */
	private function getStringName( $taxId ) {
		return 'tax_label_' . $taxId;
	}

}
