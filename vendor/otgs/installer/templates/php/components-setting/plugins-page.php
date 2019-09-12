<tr class="<?php echo esc_attr( $model->css->tr_classes ); ?>">
	<td colspan="<?php echo esc_attr( $model->col_count ); ?>" class="plugin-update colspanchange">
		<div class="<?php echo esc_attr( $model->css->notice_classes ); ?>">
			<p class="installer-q-icon">
				<?php echo wp_kses_post( $model->strings->valid_subscription ); ?>
			</p>
		</div>
	</td>
</tr>
