<?php

namespace OTGS\Installer\Templates\Repository;

class RegisteredButtons {

	public static function render( $model ) {
		$disabled           = '';
		$title              = '';
		if ( \WP_Installer::get_repository_hardcoded_site_key( $model->repoId ) ) {
			$disabled = 'disabled';
			$title    = 'title="' . sprintf( esc_attr__( "Site-key was set by %s, most likely in wp-config.php. Please remove the constant before attempting to unregister.", 'installer' ), 'OTGS_INSTALLER_SITE_KEY_' . strtoupper( $model->repoId ) ) . '"';
		}
		?>
			<a class="remove_site_key_js otgs-installer-notice-status-item otgs-installer-notice-status-item-link-unregister"
			   data-repository="<?php echo $model->repoId ?>"
			   data-confirmation="<?php esc_attr_e( 'Are you sure you want to unregister?', 'installer' ) ?>"
			   data-nonce="<?php echo $model->removeSiteKeyNonce ?>"
				<?php echo $disabled; ?>
				<?php echo $title; ?>
			>
				<?php printf( __( "Unregister %s from this site", 'installer' ), $model->productName ) ?>
			</a>&nbsp;

			<?php if ( ! $model->expired ): ?>
				<a class="update_site_key_js otgs-installer-notice-status-item-btn"
				   data-repository="<?php echo $model->repoId ?>"
				   data-nonce="<?php echo $model->updateSiteKeyNonce; ?>"
				>
					<?php _e( 'Check for updates', 'installer' ); ?>
				</a>
			<?php endif; ?>

		<?php
	}
}
