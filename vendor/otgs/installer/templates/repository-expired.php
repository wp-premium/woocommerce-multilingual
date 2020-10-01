<?php

namespace OTGS\Installer\Templates\Repository;

class Expired {

	public static function render( $model ) {
		$withProduct      = function ( $str ) use ( $model ) { return sprintf( $str, $model->productName ); };
		$withSiteName = function ( $str ) use ( $model ) {
			$siteUrl = $model->productName === 'WPML' ? 'WPML.org' : 'Toolset.com';

			return sprintf( $str, $siteUrl );
		};
		$withProductTwice = function ( $str ) use ( $model ) { return sprintf( $str, $model->productName, $model->productName ); };

		$title                  = $withProduct( __( 'You are using an expired account of %s.', 'installer' ) );
		$into                   = __( 'It means you will not receive updates. This can lead to stability and even security issues.', 'installer' );
		$accountQuestion        = $withSiteName( __( 'Do you have an account on %s?' ) );
		$extendInfo             = __( 'Great! You just need to extend your subscription.', 'installer' );
		$extendAlternative      = $withProduct( __( 'You have a %s account. You just need to extend your subscription.', 'installer' ) );
		$extendButton           = __( 'Extend Subscription', 'installer' );
		$discountQuestion       = $withProduct( __( 'OK. You need to set up renewal for %s.', 'installer' ) );
		$discountAlternative    = $withProductTwice( __( 'You do not have a %s account yet. You need to set up a renewal for %s.', 'installer' ) );
		$discountButton         = $withProduct( __( 'Set Up Renewal For %s', 'installer' ) );
		$findAccountInfo        = __( 'No worries. We can check that for you.', 'installer' );
		$findAccountButton      = __( 'Check', 'installer' );
		$findAccountPlaceholder = __( 'Your Email Address', 'installer' );

		?>
        <div class="otgs-installer-registered otgs-installer-expired clearfix">
            <div class="notice inline otgs-installer-notice otgs-installer-notice-expired">
                <div class="otgs-installer-notice-content">
                    <h2><?php echo esc_html( $title ); ?>
                        <a class="update_site_key_js"
                           href="#"
                           data-repository="<?php echo $model->repoId ?>"
                           data-nonce="<?php echo $model->updateSiteKeyNonce ?>"
                        >Refresh</a>
                    </h2>
                    <p><?php echo esc_html( $into ); ?></p>
                    <div class="otgs-installer-notice-status">

						<?php if ( $model->endUserRenewalUrl ): ?>

                            <div class="js-question">
                                <p class="otgs-installer-notice-status-item"><?php echo esc_html( $accountQuestion ); ?></p>
                                <a class="js-yes-button otgs-installer-notice-status-item otgs-installer-notice-status-item-btn"
                                   href="">
									<?php esc_html_e( 'Yes', 'installer' ); ?>
                                </a>
                                <a class="js-no-button otgs-installer-notice-status-item otgs-installer-notice-status-item-btn"
                                   href="">
									<?php esc_html_e( 'No', 'installer' ); ?>
                                </a>
                                <a class="js-dont-know otgs-installer-notice-status-item otgs-installer-notice-status-item-link" href="">
									<?php esc_html_e( 'I do not remember', 'installer' ); ?>
                                </a>
                            </div>

                            <div class="js-yes-section" style="display: none;">
                                <p class="otgs-installer-notice-status-item"
                                   data-alternative="<?php echo esc_html( $extendAlternative ); ?>"
                                >
									<?php echo esc_html( $extendInfo ); ?>
                                </p>
                                <a class="otgs-installer-notice-status-item otgs-installer-notice-status-item-btn"
                                   href="<?php echo esc_url( $model->productUrl . '/account' ); ?>"
                                >
									<?php echo esc_html( $extendButton ); ?>
                                </a>
                            </div>

                            <div class="js-no-section" style="display: none;">
                                <p class="otgs-installer-notice-status-item"
                                   data-alternative="<?php echo esc_html( $discountAlternative ); ?>"
                                >
									<?php echo esc_html( $discountQuestion ); ?>
                                </p>
                                <a class="otgs-installer-notice-status-item otgs-installer-notice-status-item-btn"
                                   href="<?php echo esc_url( $model->endUserRenewalUrl . '&token=' . $model->siteKey ); ?>">
									<?php echo esc_html( $discountButton ); ?>
                                </a>
                            </div>

                            <div class="js-find-account-section" style="display: none;">
                                <p class="otgs-installer-notice-status-item"><?php echo esc_html( $findAccountInfo ); ?></p>
                                <div class="otgs-installer-notice-status-item-wrapper">
                                    <input type="text" placeholder="<?php echo esc_attr( $findAccountPlaceholder ); ?>"/>
                                    <a class="js-find-account otgs-installer-notice-status-item otgs-installer-notice-status-item-btn btn-disabled"
                                       href=""
                                       data-repository="<?php echo $model->repoId ?>"
                                       data-nonce="<?php echo $model->findAccountNonce ?>"
                                    >
										<?php echo esc_html( $findAccountButton ); ?>
                                    </a>
                                </div>
                            </div>

						<?php else: ?>

                            <div class="js-yes-section" >
                                <a class="otgs-installer-notice-status-item otgs-installer-notice-status-item-btn"
                                   href="<?php echo esc_url( $model->productUrl . '/account' ); ?>"
                                >
									<?php echo esc_html( $extendButton ); ?>
                                </a>
                            </div>

						<?php endif; ?>

						<?php
						\OTGS\Installer\Templates\Repository\RegisteredButtons::render( $model );
						?>
                    </div>
                </div>
            </div>
        </div>
		<?php
	}
}
