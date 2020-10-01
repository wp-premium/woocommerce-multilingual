<?php

namespace OTGS\Installer\Templates\Repository;

class Register {

	public static function render( $model ) {
		?>
		<div class="otgs-installer-registered clearfix">
			<div class="enter_site_key_wrap_js notice inline otgs-installer-notice otgs-installer-notice-<?php echo $model->repoId; ?>"
				 xmlns="http://www.w3.org/1999/html">
				<div class="otgs-installer-notice-content">
					<h2>
						<?php echo esc_html( sprintf( __( 'Already purchased %s?', 'installer' ), $model->productName ) ); ?>
						<a class="enter_site_key_js otgs-installer-notice-link-register"
						   href="#"
							<?php
							if ( \WP_Installer::get_repository_hardcoded_site_key( $model->repoId ) ): ?>
								disabled
								title="<?php printf( esc_attr__( "Site-key was set by %s, most likely in wp-config.php. Please remove the constant before attempting to register.", 'installer' ), 'OTGS_INSTALLER_SITE_KEY_' . strtoupper( $model->repoId ) ) ?>"
							<?php endif; ?>
						>
							<?php printf( __( 'Register %s', 'installer' ), $model->productName ); ?>
						</a>
					</h2>
				</div>
			</div>
		</div>

		<form class="otgsi_site_key_form" method="post">
			<input type="hidden" name="action" value="save_site_key"/>
			<input type="hidden" name="nonce" value="<?php echo $model->saveSiteKeyNonce ?>"/>
			<input type="hidden" name="repository_id" value="<?php echo $model->repoId ?>">

			<?php
			$steps = [
				1 => sprintf(
					__( 'Get your site-key for %1$s. If you already have a key, get it from %2$s. Otherwise, %3$s', 'installer' ),
					self::removeScheme( $model->siteUrl ),
					self::getAccountLink( $model ),
					self::getRegisterLink( $model ) ),
				2 => __( 'Insert your key and activate automatic updates:', 'installer' )
				     . '<span class="otgs-installer-register-inputs">'
				     . '<input type="text" size="20" name="site_key_'
				     . $model->repoId
				     . '" placeholder="'
				     . esc_attr( 'site key' )
				     . '" />'
				     . '<input class="button-primary" type="submit" value="'
				     . esc_attr__( 'OK', 'installer' )
				     . '" />'
				     . '<input class="button-secondary cancel_site_key_js" type="button" value="'
				     . esc_attr__( 'Cancel registration', 'installer' )
				     . '" />'
				     . '</span>'

			];

			$required_items_count = count( $steps );

			$filtered_items = apply_filters( 'otgs_installer_repository_registration_steps', $steps, $model->repoId );
			if ( ! $filtered_items || ! is_array( $filtered_items ) || $required_items_count < 2 ) {
				$filtered_items = $steps;
			}

			$steps = $filtered_items;
			ksort( $steps );
			?>
			<ol>
				<?php
				foreach ( $steps as $item ) {
					?>
					<li>
						<?php echo $item; ?>
					</li>
					<?php
				}
				?>
			</ol>
		</form>
		<?php
	}

	private static function removeScheme( $str ) {
		return str_replace( [ 'https://', 'http://' ], '', $str );
	}

	/**
	 * @param $model
	 *
	 * @return string
	 */
	private static function getAccountLink( $model ) {
		$url = $model->siteKeysManagementUrl . '?add=' . urlencode( $model->siteUrl );
		ob_start();
		?>
		<a target="_blank" rel="nofollow"
		   href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'your account', 'installer' ); ?></a>
		<?php
		return trim( ob_get_clean() );
	}

	/**
	 * @param $model
	 *
	 * @return string
	 */
	private static function getRegisterLink( $model ) {
		$buttonText = sprintf( esc_attr( 'register on %s.', 'installer' ), self::removeScheme( $model->productUrl ) );
		ob_start();
		?>
		<a target="_blank" rel="nofollow"
		   href="<?php echo esc_url( $model->productUrl ); ?>"><?php echo $buttonText ?></a>
		<?php
		return trim( ob_get_clean() );
	}

}
