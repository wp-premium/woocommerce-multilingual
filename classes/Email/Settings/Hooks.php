<?php

namespace WCML\Email\Settings;

use IWPML_Backend_Action;
use IWPML_DIC_Action;
use SitePress;
use WCML_WC_Strings;
use WPML_Simple_Language_Selector;

class Hooks implements IWPML_Backend_Action, IWPML_DIC_Action {

	const KEY_PREFIX = 'wcml_lang';

	/** @var SitePress */
	private $sitepress;

	/** @var WCML_WC_Strings */
	private $wcmlStrings;

	public function __construct( SitePress $sitepress, WCML_WC_Strings $wcmlStrings ) {
		$this->sitepress   = $sitepress;
		$this->wcmlStrings = $wcmlStrings;
	}

	public function add_hooks() {
		if ( $this->isEmailSettingsPage() ) {
			add_action( 'admin_footer', [ $this, 'showLanguageLinksForWcEmails' ] );
			$this->setEmailsStringLanguage();
		}
	}

	/**
	 * @return bool
	 */
	private function isEmailSettingsPage() {
		global $pagenow;

		return is_admin()
			   && $pagenow === 'admin.php'
			   && isset( $_GET['page'], $_GET['tab'] )
			   && $_GET['page'] === 'wc-settings'
			   && $_GET['tab'] === 'email';
	}

	public function showLanguageLinksForWcEmails() {
		$emailOptions = [
			'woocommerce_new_order_settings',
			'woocommerce_cancelled_order_settings',
			'woocommerce_failed_order_settings',
			'woocommerce_customer_on_hold_order_settings',
			'woocommerce_customer_processing_order_settings',
			'woocommerce_customer_completed_order_settings',
			'woocommerce_customer_refunded_order_settings',
			'woocommerce_customer_invoice_settings',
			'woocommerce_customer_note_settings',
			'woocommerce_customer_reset_password_settings',
			'woocommerce_customer_new_account_settings',
		];

		$emailOptions = apply_filters( 'wcml_emails_options_to_translate', $emailOptions );

		$textKeys = [
			'subject',
			'heading',
			'subject_downloadable',
			'heading_downloadable',
			'subject_full',
			'subject_partial',
			'heading_full',
			'heading_partial',
			'subject_paid',
			'heading_paid',
			'additional_content',
		];

		$textKeys = apply_filters( 'wcml_emails_text_keys_to_translate', $textKeys );

		foreach ( $emailOptions as $emailOption ) {
			$sectionPrefix = apply_filters( 'wcml_emails_section_name_prefix', 'wc_email_', $emailOption );
			$sectionName   = str_replace( 'woocommerce_', $sectionPrefix, $emailOption );
			$sectionName   = apply_filters( 'wcml_emails_section_name_to_translate', str_replace( '_settings', '', $sectionName ) );

			if ( isset( $_GET['section'] ) && $_GET['section'] === $sectionName ) {

				$emailSettings = $this->get_email_option( $emailOption );

				if ( $emailSettings ) {

					foreach ( $emailSettings as $settingsKey => $settingsValue ) {

						if ( in_array( $settingsKey, $textKeys ) ) {

							$emailInputKey = self::getEmailInputKey( $emailOption, $settingsKey );
							$langSelector  = new WPML_Simple_Language_Selector( $this->sitepress );
							$language      = $this->wcmlStrings->get_string_language(
								$settingsValue,
								self::getStringDomain( $emailOption ),
								self::getStringName( $emailOption, $settingsKey )
							);

							if ( is_null( $language ) ) {
								$language = $this->sitepress->get_default_language();
							}

							$langSelector->render(
								[
									'id'                 => $emailOption . '_' . $settingsKey . '_language_selector',
									'name'               => self::KEY_PREFIX . '-' . $emailOption . '-' . $settingsKey,
									'selected'           => $language,
									'show_please_select' => false,
									'echo'               => true,
									'style'              => 'width: 18%;float: left',
								]
							);

							$stPage = admin_url( 'admin.php?page=' . WPML_ST_FOLDER . '/menu/string-translation.php&context=admin_texts_' . $emailOption );

							?>
							<script>
								var input = jQuery('#<?php echo $emailInputKey; ?>');
								if (input.length) {
									input.parent().append('<div class="translation_controls"></div>');
									input.parent().find('.translation_controls').append('<a href="<?php echo esc_url( $stPage ); ?>" style="margin-left: 10px"><?php esc_html_e( 'translations', 'woocommerce-multilingual' ); ?></a>');
									jQuery('#<?php echo esc_html( $emailOption . '_' . $settingsKey . '_language_selector' ); ?>').prependTo(input.parent().find('.translation_controls'));
								}
							</script>
							<?php
						}
					}
				}
			}
		}
	}

	public function setEmailsStringLanguage() {
		foreach ( $_POST as $key => $language ) {
			if ( substr( $key, 0, 9 ) === self::KEY_PREFIX ) {

				$keyParts = explode( '-', $key );

				if ( isset( $keyParts[2] ) ) {
					list( , $emailType, $emailElement ) = $keyParts;

					$emailInputKey     = self::getEmailInputKey( $emailType, $emailElement );
					$emailSettings     = $this->get_email_option( $emailType, true );
					$optionStringValue = $emailSettings[ $emailElement ];

					$stringValue = isset( $_POST[ $emailInputKey ] ) ? $_POST[ $emailInputKey ] : $optionStringValue;
					$domain      = self::getStringDomain( $emailType );
					$name        = self::getStringName( $emailType, $emailElement );

					do_action( 'wpml_register_single_string', $domain, $name, $stringValue, false, $this->wcmlStrings->get_string_language( $optionStringValue, $domain ) );

					$this->wcmlStrings->set_string_language( $stringValue, $domain, $name, $language );
				}
			}
		}
	}

	/**
	 * @param string $option
	 * @param bool $default
	 *
	 * @return mixed
	 */
	private function get_email_option( $option, $default = false ) {
		$emailSettings = get_option( $option, $default );
		if ( $emailSettings && is_array( $emailSettings ) && ! isset( $emailSettings['additional_content'] ) ) {
			$emailSettings['additional_content'] = '';
		}

		return $emailSettings;
	}

	/**
	 * @param string $emailType
	 * @param string $emailElement
	 *
	 * @return string
	 */
	private static function getEmailInputKey( $emailType, $emailElement ) {
		return str_replace( '_settings', '', $emailType ) . '_' . $emailElement;
	}

	/**
	 * @param string $emailType
	 *
	 * @return string
	 */
	private static function getStringDomain( $emailType ) {
		return 'admin_texts_' . $emailType;
	}

	/**
	 * @param string $emailType
	 * @param string $emailElement
	 *
	 * @return string
	 */
	private static function getStringName( $emailType, $emailElement ) {
		return '[' . $emailType . ']' . $emailElement;
	}
}
