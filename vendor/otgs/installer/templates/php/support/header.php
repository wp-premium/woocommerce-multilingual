<tr>
    <th>
        <span><?php echo  wp_kses_post( $model->strings->log->request_url ); ?></span>
    </th>
    <th>
        <span><?php echo  wp_kses_post( $model->strings->log->request_arguments ); ?></span>
    </th>
    <th>
        <span><?php echo  wp_kses_post( $model->strings->log->response ); ?></span>
    </th>
    <th>
        <span><?php echo  wp_kses_post( $model->strings->log->component ); ?></span>
    </th>
    <th>
        <span><?php echo  wp_kses_post( $model->strings->log->time ); ?></span>
    </th>
</tr>