<div class="wrap otgs-installer-support-link">
	<?php
        if ( !$model->hide_title ) {
    ?>
    <h2><?php echo  wp_kses_post( $model->title ); ?></h2>
    <?php
		}
	?>

    <p><a href="<?php echo esc_url ($model->link->url ); ?>"><?php echo  wp_kses_post( $model->link->text ); ?></a></p>
</div>