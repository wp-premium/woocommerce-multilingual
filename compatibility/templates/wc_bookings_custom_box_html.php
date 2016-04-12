<?php if( isset( $template_data[ 'resources' ] ) ): ?>
    <?php foreach( $template_data[ 'resources' ] as $original_resource_id => $trnsl_resource_id ): ?>
        <tr>
            <td>
                <?php if( !$template_data[ 'original' ] ): ?>
                    <input type="hidden" name="<?php echo $template_data[ 'product_content' ].'_'.$template_data['lang'].'[id][]'; ?>" value="<?php echo $trnsl_resource_id; ?>" />
                    <?php if( empty( $trnsl_resource_id ) ): ?>
                        <input type="hidden" name="<?php echo $template_data[ 'product_content' ].'_'.$template_data['lang'].'[orig_id][]'; ?>" value="<?php echo $original_resource_id; ?>" />
                    <?php endif;?>
                <?php endif;?>
                <textarea rows="1" <?php if( !$template_data['original'] ): ?>name="<?php echo $template_data['product_content'].'_'.$template_data['lang'].'[title][]'; ?>"<?php endif;?> <?php if( $template_data['original'] ): ?> disabled="disabled"<?php endif;?>><?php echo $template_data[ 'original' ]? get_the_title( $original_resource_id ): get_the_title( $trnsl_resource_id ); ?></textarea>
            </td>
        </tr>
    <?php endforeach; ?>
<?php elseif( isset( $template_data[ 'persons' ] ) ): ?>
    <?php foreach( $template_data[ 'persons' ] as $original_person_id => $trnsl_person_id ): ?>
        <tr>
            <td>
                <?php if( !$template_data[ 'original' ] ): ?>
                    <input type="hidden" name="<?php echo $template_data[ 'product_content' ].'_'.$template_data['lang'].'[id][]'; ?>" value="<?php echo $trnsl_person_id; ?>" />
                    <?php if( empty( $trnsl_person_id ) ): ?>
                        <input type="hidden" name="<?php echo $template_data[ 'product_content' ].'_'.$template_data['lang'].'[orig_id][]'; ?>" value="<?php echo $original_person_id; ?>" />
                    <?php endif;?>
                <?php endif;?>
                <textarea rows="1" <?php if( !$template_data['original'] ): ?>name="<?php echo $template_data['product_content'].'_'.$template_data['lang'].'[title][]'; ?>"<?php endif;?> <?php if( $template_data['original'] ): ?> disabled="disabled"<?php endif;?>><?php echo $template_data[ 'original' ]? get_the_title( $original_person_id ): get_the_title( $trnsl_person_id ); ?></textarea>
                <textarea rows="1" <?php if( !$template_data['original'] ): ?>name="<?php echo $template_data['product_content'].'_'.$template_data['lang'].'[description][]'; ?>"<?php endif;?> <?php if( $template_data['original'] ): ?> disabled="disabled"<?php endif;?>><?php echo $template_data[ 'original' ]? get_post( $original_person_id )->post_excerpt: $template_data[ 'translation_exist' ] ? get_post( $trnsl_person_id )->post_excerpt : ''; ?></textarea>
            </td>
        </tr>
    <?php endforeach; ?>
<?php endif; ?>
