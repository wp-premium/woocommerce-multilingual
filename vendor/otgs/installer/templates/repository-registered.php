<?php

namespace OTGS\Installer\Templates\Repository;

class Registered {

	public static function render( $model ) {
		$expires = call_user_func( $model->whenExpires );
		$message = $expires ?
			sprintf( __( '%s is registered on this site. You will receive automatic updates until %s', 'installer' ), $model->productName, date_i18n( 'F j, Y', strtotime( $expires ) ) ) :
			sprintf( __( '%s is registered on this site. Your Lifetime account gives you updates for life.', 'installer' ), $model->productName );

		?>
		<div class="otgs-installer-registered wp-clearfix">
			<div class="notice inline otgs-installer-notice otgs-installer-notice-registered otgs-installer-notice-<?php echo $model->repoId; ?>">
				<div class="otgs-installer-notice-content">
					<?php echo esc_html( $message ) ?>
					<?php \OTGS\Installer\Templates\Repository\RegisteredButtons::render( $model ); ?>
				</div>
			</div>
		</div>
		<?php

	}
}
