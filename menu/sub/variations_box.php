<button id="prod_variations_link_<?php echo $product_id ?>_<?php echo $lang ?>" class="button-secondary js-table-toggle<?php if($is_duplicate_product): ?> js-dup-disabled<?php endif;?>" data-text-opened="<?php esc_attr_e('Collapse', 'woocommerce-multilingual'); ?>" data-text-closed="<?php esc_attr_e('Expand', 'woocommerce-multilingual'); ?>"<?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?>>
    <span><?php _e('Expand', 'woocommerce-multilingual'); ?></span>
    <i class="icon-caret-down"></i>
</button>

<table id="prod_variations_<?php echo $product_id ?>_<?php echo $lang ?>" class="widefat prod_variations js-table">
<?php if(isset($template_data['empty_variations'])): ?>
    <tr>
        <td><?php _e('Please add variations to product', 'woocommerce-multilingual'); ?></td>
    <tr>
<?php else: ?>
    <tbody>
        <tr>
            <?php if($is_downloable): ?>
            <th></th>
            <?php endif; ?>
            <?php foreach($template_data['all_variations_ids'] as $variation_id): ?>
                <th>
                    <?php echo $template_data['all_file_paths'][$variation_id]['label']; ?>
                </th>
            <?php endforeach; ?>
        </tr>

        <?php if(isset($template_data['empty_translation'])): ?>
            <tr>
                <td><?php _e('Please save translation before translate variations file paths', 'woocommerce-multilingual'); ?></td>
            </tr>
        <?php elseif(isset($template_data['not_downloaded'])): ?>
                <tr>
                <td><?php _e('Variations are not downloadable', 'woocommerce-multilingual'); ?></td>
                </tr>
        <?php else: ?>
            <?php if($is_downloable): ?>
                <tr>
                    <td><?php _e('Download URL', 'woocommerce-multilingual'); ?></td>
                    <?php foreach($template_data['all_variations_ids'] as $variation_id): $file_paths = ''; ?>
                        <?php if(isset($template_data['all_file_paths'][$variation_id]['not_translated'])){
                            echo '<td></td>';
                            continue;
                        }
                        if(get_post_meta($variation_id,'_downloadable',true) == 'yes'): ?>
                            <td>
                                <?php for($i=0;$i<$template_data['all_file_paths']['count'];$i++): ?>
                                    <?php if($template_data['original']): ?>
                                        <input type="text" value="<?php echo $template_data['all_file_paths'][$variation_id][$i]['label']; ?>" class="" disabled="disabled">
                                        <input type="text" value="<?php echo $template_data['all_file_paths'][$variation_id][$i]['value']; ?>" class="" disabled="disabled">
                                    <?php else: ?>
                                        <div>
                                            <input type="text" value="<?php echo isset($template_data['all_file_paths'][$variation_id][$i])?$template_data['all_file_paths'][$variation_id][$i]['label']:''; ?>" name='<?php echo 'variations_file_paths['.$variation_id.']['.$i.'][name]'; ?>' class="wcml_file_paths_name" placeholder="<?php esc_attr_e('Enter translation for name', 'woocommerce-multilingual') ?>">
                                            <input type="text" value="<?php echo isset($template_data['all_file_paths'][$variation_id][$i])?$template_data['all_file_paths'][$variation_id][$i]['value']:''; ?>" name='<?php echo 'variations_file_paths['.$variation_id.']['.$i.'][file]'; ?>' class="wcml_file_paths_file" placeholder="<?php esc_attr_e('Enter translation', 'woocommerce-multilingual') ?>"/>
                                            <button type="button" class="button-secondary wcml_file_paths_button"><?php _e('Choose a file', 'woocommerce-multilingual') ?></button>
                                        </div>
                                <?php endif; ?>
                                <?php endfor; ?>
                            </td>
                        <?php else: ?>
                            <td><?php _e('Variation is not downloadable', 'woocommerce-multilingual'); ?></td>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tr>
            <?php endif; ?>
        <?php endif; ?>
    </tbody>
<?php endif; ?>
</table>