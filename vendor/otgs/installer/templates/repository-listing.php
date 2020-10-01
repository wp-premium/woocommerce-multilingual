<?php if((!$this->repository_has_subscription($repository_id) && $match = $this->get_matching_cp($repository)) && $match['exp']): ?>
<p class="alignright installer_highlight"><strong><?php printf('Price offers available until %s', date_i18n(get_option( 'date_format' ), $match['exp'])) ?></strong></p>
<?php endif; ?>

<h3 id="repository-<?php echo $repository_id ?>"><?php echo $repository['data']['name'] ?></h3>
<?php
$getWhenSubscriptionExpires = function ( $repository ) { return $repository['subscription']['data']->expires; };
$model                      = (object) [
	'repoId'                => $repository_id,
	'productName'           => $repository['data']['product-name'],
	'productUrl'            => $repository['data']['url'],
	'siteUrl'               => $this->get_installer_site_url( $repository_id ),
	'siteKeysManagementUrl' => $repository['data']['site_keys_management_url'],
	'updateSiteKeyNonce'    => wp_create_nonce( 'update_site_key_' . $repository_id ),
	'saveSiteKeyNonce'      => wp_create_nonce( 'save_site_key_' . $repository_id ),
	'removeSiteKeyNonce'    => wp_create_nonce( 'remove_site_key_' . $repository_id ),
	'findAccountNonce'      => wp_create_nonce( 'find_account_' . $repository_id ),
	'whenExpires'           => \OTGS\Installer\FP\partial( $getWhenSubscriptionExpires, $repository ),
	'expired'               => false,
	'siteKey'               => WP_Installer_API::get_site_key( $repository_id ),
	'endUserRenewalUrl'     => $this->get_end_user_renewal_url( $repository_id ),
];
?>
<table class="widefat otgs_wp_installer_table" id="installer_repo_<?php echo $repository_id ?>">

    <tr>
        <td class="otgsi_register_product_wrap" colspan="2">
	        <?php
			if ( ! $this->repository_has_subscription( $repository_id ) ) {
				\OTGS\Installer\Templates\Repository\Register::render( $model );
				$site_key = false;
			} else {
				$site_key          = $repository['subscription']['key'];
				$subscription_type = $this->get_subscription_type_for_repository( $repository_id );
				$upgrade_options   = $this->get_upgrade_options( $repository_id );

				if ( $this->repository_has_expired_subscription( $repository_id ) ) {
					$model->expired = true;
					\OTGS\Installer\Templates\Repository\Expired::render( $model );
				} else if ( $this->repository_has_refunded_subscription( $repository_id ) ) {
					$model->expired = true;
					$model->shouldDisplayUnregisterLink = $this->should_display_unregister_link_on_refund_notice();
					\OTGS\Installer\Templates\Repository\Refunded::render( $model );
				} else {
					$this->show_subscription_renew_warning( $repository_id, $subscription_type );
					\OTGS\Installer\Templates\Repository\Registered::render( $model );
				}

			}
			?>

            <div class="installer-error-box hidden"></div>

        </td>
    </tr>

    <?php

    $subscription_type = isset($subscription_type) ? $subscription_type : null;
    $upgrade_options = isset($upgrade_options) ? $upgrade_options : null;
    $packages = $this->_render_product_packages($repository['data']['packages'], $subscription_type, $model->expired, $upgrade_options, $repository_id);
    if(empty($subscription_type) || $model->expired){
        $subpackages_expandable = true;
    }else{
        $subpackages_expandable = false;
    }

    ?>

    <?php foreach($packages as $package): ?>
    <tr id="repository-<?php echo $repository_id ?>_<?php echo $package['id'] ?>">
        <td class="installer-repository-image"><img width="140" src="<?php echo $package['image_url'] ?>" /></td>
		<?php if ( ! $model->expired ): ?>
			<td>
				<p><strong><?php echo $package['name'] ?></strong></p>
				<p><?php echo $package['description'] ?></p>

				<?php if($package['products']): ?>
					<?php foreach($package['products'] as $product): ?>
					<ul class="installer-products-list" style="display:inline">
						<li>
							<a class="button-secondary" href="<?php echo $product['url'] ?>"><?php echo $product['label'] ?></a>
						</li>
					</ul>
					<?php endforeach; ?>
				<?php endif; ?>

				<?php
				if ( $package['downloads'] ) {
					WP_Installer_Channels()->load_channel_selector( $repository_id, $package['downloads'] );
					include $this->plugin_path() . '/templates/downloads-list.php';
				}
				?>

				<?php if(!empty($package['sub-packages'])): ?>

					<?php $subpackages = $this->_render_product_packages($package['sub-packages'], $subscription_type, $model->expired, $upgrade_options, $repository_id); ?>

					<?php if($subpackages): ?>

					<?php if($subpackages_expandable): ?>
					<h5><a class="installer_expand_button" href="#" title="<?php esc_attr_e('Click to see individual components options.', 'installer') ?>"><?php _e('Individual components', 'installer') ?></a></h5>
					<?php endif; ?>

					<table class="otgs_wp_installer_subtable" style="<?php if($subpackages_expandable) echo 'display:none' ?>">
					<?php foreach($subpackages as $package): ?>
						<tr id="repository-<?php echo $repository_id ?>_<?php echo $package['id'] ?>">
							<td><img width="70" height="70" src="<?php echo $package['image_url'] ?>" /></td>
							<td>
								<p><strong><?php echo $package['name'] ?></strong></p>
								<p><?php echo $package['description'] ?></p>

								<?php if($package['products']): ?>
									<?php foreach($package['products'] as $product): ?>
										<ul class="installer-products-list" style="display:inline">
											<li>
												<a class="button-secondary" href="<?php echo $product['url'] ?>"><?php echo $product['label'] ?></a>
											</li>
										</ul>
									<?php endforeach; ?>
								<?php endif; ?>

								<?php if($package['downloads']): ?>
									<?php include $this->plugin_path() . '/templates/downloads-list.php'; ?>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</table>
					<?php endif; ?>

				<?php endif;  ?>


			</td>
		<?php endif; ?>
    </tr>

    <?php endforeach; ?>

</table>


<p><i><?php printf(__('This page lets you install plugins and update existing plugins. To remove any of these plugins, go to the %splugins%s page and if you have the permission to remove plugins you should be able to do this.', 'installer'), '<a href="' . admin_url('plugins.php') . '">' , '</a>'); ?></i></p>



<br />
