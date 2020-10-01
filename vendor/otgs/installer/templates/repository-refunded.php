<?php

namespace OTGS\Installer\Templates\Repository;

class Refunded {

	public static function render( $model ) {
		$title            = sprintf( __( 'Remember to remove %s from this website.', 'installer' ), $model->productName );
		$into             = sprintf( __( 'This site is using %s plugin, which is not paid for. After receiving a refund, you should remove this plugin from your sites. Using unregistered plugins means that you are not receiving stability and security updates and will ultimately lead to problems running the site.', 'installer' ), $model->productName );
		$buyQuestion      = __( 'Bought again?', 'installer' );
		$buyButton        = __( 'Check my order status', 'installer' );
		?>
		<div class="otgs-installer-registered clearfix">
			<div class="notice inline otgs-installer-notice otgs-installer-notice-refund">
				<div class="otgs-installer-notice-content">
					<h2><?php echo esc_html( $title ); ?></h2>
					<p><?php echo esc_html( $into ); ?></p>
					<div class="otgs-installer-notice-status">
						<p class="otgs-installer-notice-status-item"><?php echo esc_html( $buyQuestion ); ?></p>
						<a class="update_site_key_js otgs-installer-notice-status-item otgs-installer-notice-status-item-btn"
						   href="#"
						   data-repository="<?php echo $model->repoId ?>"
						   data-nonce="<?php echo $model->updateSiteKeyNonce ?>"
						>
							<?php echo esc_html( $buyButton ); ?>
						</a>

						<?php
                        if( $model->shouldDisplayUnregisterLink ) {
	                        \OTGS\Installer\Templates\Repository\RegisteredButtons::render( $model );
                        }
						?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}
