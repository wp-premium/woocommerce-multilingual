<div class="wrap">
    <h1><?php echo wp_kses_post( $model->strings->page_title ); ?></h1>

    <div class="otgs-installer-support-test-connection">
        <h2><?php echo wp_kses_post( $model->strings->tester->title ); ?> <a class="button check-again"><?php echo wp_kses_post( $model->strings->tester->button_label ); ?></a></h2>

        <ul><?php foreach ( $model->tester->endpoints as $endpoint ) { ?>
                <li class="endpoint" data-repository="<?php echo esc_attr( $endpoint->repository ); ?>" data-type="<?php echo esc_attr( $endpoint->type ); ?>"><span class="dashicons dashicons-yes status"></span> <?php echo esc_attr( $endpoint->description ); ?></li>

	        <?php } ?>
        </ul>

	    <?php echo wp_kses_post( $model->tester->nonce ); ?>

        <span class="wp-clearfix"></span>
    </div>

    <hr>

    <div class="otgs-installer-support-test-connection">
        <h2><?php echo wp_kses_post( $model->strings->requirements->title ); ?></h1></h2>

        <ul>
	        <?php foreach ( $model->requirements as $requirement ) {
	            $icon_class = $requirement->active ? 'dashicons-yes' : 'dashicons-no-alt';
	            ?>
                <li><span class="dashicons  <?php echo esc_attr( $icon_class ); ?> status"></span> <?php echo wp_kses_post( $requirement->name ) ?></li>
	        <?php } ?>
        </ul>

        <span class="wp-clearfix"></span>
    </div>

    <hr>

    <h2><?php echo wp_kses_post( $model->strings->instances->title ); ?></h2>

    <table class="wp-list-table widefat striped installer-instances">
        <thead>
        <?php include 'header-instance.php'; ?>
        </thead>

        <tbody>
        <?php foreach ( $model->instances as $instance ) { ?>
            <tr <?php if ( $instance->delegated ) { ?> class="active" <?php } ?>>
                <td>
                    <?php echo wp_kses_post( $instance->bootfile ); ?>
                </td>
                <td>
                    <?php echo wp_kses_post( $instance->version ); ?>
                </td>
                <td>
                    <?php echo wp_kses_post( $instance->high_priority ); ?>
                </td>
                <td>
	                <?php if ( $instance->delegated ) { ?> <span class="dashicons dashicons-yes"></span> <?php } ?>
                </td>
            </tr>
        <?php } ?>
        </tbody>
        <tfoot>
        <?php include 'header-instance.php'; ?>
        </tfoot>

    </table>

    <br>

    <hr>

    <h2><?php echo wp_kses_post( $model->strings->log->title ); ?></h2>

    <table class="wp-list-table widefat fixed striped posts">
        <thead>
        <?php include 'header.php'; ?>

        </thead>

        <tbody id="the-list">
        <?php foreach ( $model->log_entries as $log_entry ) { ?>
            <tr>
                <td class="title column-title has-row-actions column-primary">
	                <?php echo wp_kses_post( $log_entry->request_url ); ?>
                </td>
                <td class="title column-title has-row-actions column-primary">
	                <?php echo wp_kses_post( print_r( $log_entry->request_arguments, true ) ); ?>
                </td>
                <td class="title column-title has-row-actions column-primary">
	                <?php echo wp_kses_post( $log_entry->response ); ?>
                </td>
                <td class="title column-title has-row-actions column-primary">
	                <?php echo wp_kses_post( $log_entry->component ); ?>
                </td>
                <td class="title column-title has-row-actions column-primary">
	                <?php echo wp_kses_post( $log_entry->time ); ?>
                </td>
            </tr>
        <?php } ?>
        <?php if ( !$model->log_entries ) { ?>
            <tr>
                <td colspan="5" class="title column-title has-row-actions column-primary">
	                <?php echo wp_kses_post( $model->strings->log->empty_log ); ?>
                </td>
            </tr>
        <?php } ?>

        </tbody>
        <tfoot>

        <?php include 'header.php'; ?>

        </tfoot>

    </table>
</div>

