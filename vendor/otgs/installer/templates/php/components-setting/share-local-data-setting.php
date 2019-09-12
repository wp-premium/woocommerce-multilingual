<div class="otgs-installer-component-setting" data-has-setting="<?php echo esc_attr( $model->has_setting ); ?>">
	<?php
	if ( isset( $model->custom_raw_heading ) && $model->custom_raw_heading !== null ) {
		echo  wp_kses_post( $model->custom_raw_heading );
	} else {
		?>
		<h4 class="heading"><?php echo  wp_kses_post( $model->strings->heading ); ?>
			<a
				href="<?php echo  esc_url( $model->company_url ); ?>"
				target="_blank"
				rel="noopener"
				class="otgs-external-link"
			><?php echo  wp_kses_post( $model->company_site ); ?></a>
		</h4>
	<?php } ?>
	<input
					type="checkbox"
					<?php if ( $model->is_repo_allowed ) { ?>
						checked="checked"
					<?php } ?>
					id="<?php echo esc_attr( $model->nonce->action . $model->nonce->value ); ?>"
					class="js-otgs-components-report-user-choice otgs-switcher-input"
					value="1"
					data-nonce-action="<?php echo esc_attr( $model->nonce->action ); ?>"
					data-nonce-value="<?php echo esc_attr( $model->nonce->value ); ?>"
					data-repo="<?php echo esc_attr( $model->repo ); ?>"
	/>
	<label for="<?php echo esc_attr( $model->nonce->action . $model->nonce->value ); ?>"
		   class="otgs-switcher wpml-theme"
		   data-on="ON"
		   data-off="OFF"
	>
		<?php
		if ( isset( $model->custom_raw_label ) && $model->custom_raw_label !== null ) {
			echo  wp_kses_post( $model->custom_raw_label );
		} else {
			echo  wp_kses_post( $model->strings->report_to . ' ' . $model->company_site . ' ' . $model->strings->which_theme_and_plugins );
		}
		?>
	</label>
	<div class="spinner otgs-components-report-setting-spinner"></div>

	<p>
		<a
				href="<?php echo esc_url( $model->privacy_policy_url ); ?>"
				target="_blank"
				rel="noopener"
				class="otgs-external-link"
		>
			<?php
			if ( isset( $model->custom_privacy_policy_text ) && $model->custom_privacy_policy_text !== null ) {
				echo  wp_kses_post( $model->custom_privacy_policy_text );
			} else {
				echo  wp_kses_post( $model->privacy_policy_text );
			}
			?>
		</a>
	</p>
</div>
