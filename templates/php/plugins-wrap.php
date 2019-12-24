<?php
/**
 * @var $model \WPML\Templates\PHP\Model
 *
 * phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped
 */
?>
<div class="wrap">
	<h1><?php echo wp_kses_post( $model->strings->title ); ?></h1>

	<nav class="wcml-tabs wpml-tabs" style="display:table;margin-top:30px;">
		<a class="nav-tab nav-tab-active wcml-required-plugins-tab" href="<?php echo esc_attr( $model->link_url ); ?>"><?php echo wp_kses_post( $model->strings->required ); ?></a>
	</nav>

	<div class="wcml-wrap wcml-required-plugins-wrap">
		<div class="wcml-section">
			<div class="wcml-section-header">
				<h3>
					<?php echo wp_kses_post( $model->strings->plugins ); ?>
					<i class="otgs-ico-help wcml-tip" data-tip="<?php echo esc_attr( $model->strings->depends ); ?>"> </i>
				</h3>
			</div>
			<div class="wcml-section-content wcml-section-content-wide">
				<ul>
					<?php
					if ( true === $model->old_wpml ) {
						?>
						<li>
							<i class="otgs-ico-warning wpml-multilingual-cms otgs-old"></i>
							<?php echo wp_kses_post( $model->strings->old_wpml_link ); ?>
							<a href="<?php echo esc_attr( $model->tracking_link ); ?>"
							   target="_blank"><?php echo wp_kses_post( $model->strings->update_wpml ); ?></a>
						</li>
						<?php
					} elseif ( true === $model->icl_version ) {
						?>
						<li>
							<i class="otgs-ico-ok wpml-multilingual-cms"></i>
							<?php echo sprintf( $model->strings->inst_active, $model->strings->wpml ); ?>
						</li>
						<?php
						if ( true === $model->icl_setup ) {
							?>
							<li>
								<i class="otgs-ico-ok wpml-multilingual-cms wpml-setup"></i>
								<?php echo sprintf( $model->strings->is_setup, $model->strings->wpml ); ?>
							</li>
							<?php
						} else {
							?>
							<li>
								<i class="otgs-ico-warning wpml-multilingual-cms otgs-no-setup"></i>
								<?php echo sprintf( $model->strings->not_setup, $model->strings->wpml ); ?>
							</li>
							<?php
						}
					} else {
						?>
						<li>
							<i class="otgs-ico-warning wpml-multilingual-cms otgs-missing"></i>
							<?php echo wp_kses_post( $model->strings->wpml_not_inst ); ?>
							<a href="<?php echo esc_attr( $model->install_wpml_link ); ?>" target="_blank"><?php echo wp_kses_post( $model->strings->get_wpml ); ?></a>
						</li>
						<?php
					}
					if ( true === $model->tm_version ) {
						?>
						<li>
							<i class="otgs-ico-ok wpml-translation-management"></i>
							<?php echo sprintf( $model->strings->inst_active, $model->strings->tm ); ?>
						</li>
						<?php
					} else {
						?>
						<li>
							<i class="otgs-ico-warning wpml-translation-management otgs-missing"></i>
							<?php echo sprintf( $model->strings->not_inst, $model->strings->tm ); ?>
							<a href="<?php echo esc_attr( $model->install_wpml_link ); ?>" target="_blank"><?php echo wp_kses_post( $model->strings->get_wpml_tm ); ?></a>
						</li>
						<?php
					}
					if ( true === $model->st_version ) {
						?>
						<li>
							<i class="otgs-ico-ok wpml-string-translation"></i>
							<?php echo sprintf( $model->strings->inst_active, $model->strings->st ); ?>
						</li>
						<?php
					} else {
						?>
						<li>
							<i class="otgs-ico-warning wpml-string-translation otgs-missing"></i>
							<?php echo sprintf( $model->strings->not_inst, $model->strings->st ); ?>
							<a href="<?php echo esc_attr( $model->install_wpml_link ); ?>" target="_blank"><?php echo wp_kses_post( $model->strings->get_wpml_st ); ?></a>
						</li>
						<?php
					}
					if ( true === $model->old_wc ) {
						?>
						<li>
							<i class="otgs-ico-warning woocommerce otgs-old"></i>
							<?php echo wp_kses_post( $model->strings->old_wc ); ?>
							<a href="<?php echo esc_attr( $model->wc_link ); ?>" target="_blank"><?php echo wp_kses_post( $model->strings->download_wc ); ?></a>
						</li>
						<?php
					} elseif ( true === $model->wc ) {
						?>
						<li>
							<i class="otgs-ico-ok woocommerce"></i>
							<?php echo sprintf( $model->strings->inst_active, $model->strings->wc ); ?>
						</li>
						<?php
					} else {
						?>
						<li>
							<i class="otgs-ico-warning woocommerce otgs-missing"></i>
							<?php echo sprintf( $model->strings->not_inst, $model->strings->wc ); ?>
							<a href="<?php echo esc_attr( $model->wc_link ); ?>" target="_blank"><?php echo wp_kses_post( $model->strings->download_wc ); ?></a>
						</li>
						<?php
					}
					?>
				</ul>
			</div>
		</div>
	</div>
</div>
