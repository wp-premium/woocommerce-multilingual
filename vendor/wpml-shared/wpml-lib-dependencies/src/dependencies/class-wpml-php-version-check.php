<?php
/**
 * WPML_PHP_Version_Check class file.
 *
 * @package WPML\LibDependencies
 */

if ( ! class_exists( 'WPML_PHP_Version_Check' ) ) {

	/**
	 * Class WPML_PHP_Version_Check
	 */
	class WPML_PHP_Version_Check {

		/**
		 * Required php version.
		 *
		 * @var string
		 */
		private $required_php_version;

		/**
		 * Plugin name.
		 *
		 * @var string
		 */
		private $plugin_name;

		/**
		 * Plugin file.
		 *
		 * @var string
		 */
		private $plugin_file;

		/**
		 * Text domain.
		 *
		 * @var string
		 */
		private $text_domain;

		/**
		 * WPML_PHP_Version_Check constructor.
		 *
		 * @param string $required_version Required php version.
		 * @param string $plugin_name      Plugin name.
		 * @param string $plugin_file      Plugin file.
		 * @param string $text_domain      Text domain.
		 */
		public function __construct( $required_version, $plugin_name, $plugin_file, $text_domain ) {
			$this->required_php_version = $required_version;
			$this->plugin_name          = $plugin_name;
			$this->plugin_file          = $plugin_file;
			$this->text_domain          = $text_domain;
		}

		/**
		 * Check php version.
		 *
		 * @return bool
		 */
		public function is_ok() {
			if ( version_compare( $this->required_php_version, phpversion(), '>' ) ) {
				add_action( 'admin_notices', array( $this, 'php_requirement_message' ) );

				return false;
			}

			return true;
		}

		/**
		 * Show notice with php requirement.
		 */
		public function php_requirement_message() {
			load_plugin_textdomain( $this->text_domain, false, dirname( plugin_basename( $this->plugin_file ) ) . '/locale' );

			$errata_page_link = 'https://wpml.org/errata/parse-error-syntax-error-unexpected-t_class-and-other-errors-when-using-php-versions-older-than-5-6/';

			// phpcs:disable WordPress.WP.I18n.NonSingularStringLiteralDomain
			/* translators: 1: Current PHP version number, 2: Plugin version, 3: Minimum required PHP version number */
			$message = sprintf( __( 'Your server is running PHP version %1$s but %2$s requires at least %3$s.', $this->text_domain ), phpversion(), $this->plugin_name, $this->required_php_version );

			$message .= '<br>';
			/* translators: Link to errata page */
			$message .= sprintf( __( 'You can find version of the plugin suitable for your environment <a href="%s">here</a>.', $this->text_domain ), $errata_page_link );
			// phpcs:enable WordPress.WP.I18n.NonSingularStringLiteralDomain
			?>
			<div class="message error">
				<p>
					<?php echo wp_kses_post( $message ); ?>
				</p>
			</div>
			<?php
		}
	}
}
