<?php if(isset($template_data['orig_tabs'])): ?>
    <?php foreach($template_data['orig_tabs'] as $key=>$values):
        $trnsl_tab_id = isset($template_data['tr_tabs'][$key]['id'])?$template_data['tr_tabs'][$key]['id']:'';
        $orig_tab_id = $template_data['orig_tabs'][$key]['id'];
        ?>
        <tr>
            <td>
                <?php if($values['type'] == 'product'): ?>
                    <?php if(!$template_data['original']): ?>
                        <input type="hidden" name="<?php echo $template_data['product_content'].'_'.$template_data['lang'].'[id][]'; ?>" value="<?php echo $trnsl_tab_id; ?>" />
                    <?php endif;?>
                    <textarea rows="1" <?php if(!$template_data['original']): ?>name="<?php echo $template_data['product_content'].'_'.$template_data['lang'].'[title][]'; ?>"<?php endif;?> <?php if($template_data['original']): ?> disabled="disabled"<?php endif;?>><?php echo $template_data['original']?get_the_title($orig_tab_id):get_the_title($trnsl_tab_id); ?></textarea>
                <?php else: ?>
                    <textarea rows="1" <?php if(!$template_data['original']): ?>name="<?php echo $template_data['product_content'].'_'.$template_data['lang'].'[core_title]['.$key.']'; ?>"<?php endif;?> <?php if($template_data['original']): ?> disabled="disabled"<?php endif;?>><?php echo isset($template_data['tr_tabs'][$key]['title']) ? $template_data['tr_tabs'][$key]['title'] : ''; ?></textarea>
                <?php endif; ?>
            </td>
            <td>
                <?php if($values['type'] == 'core'): ?>
                    <textarea rows="1" <?php if(!$template_data['original']): ?>name="<?php echo $template_data['product_content'].'_'.$template_data['lang'].'[core_heading]['.$key.']'; ?>"<?php endif;?> <?php if($template_data['original']): ?> disabled="disabled"<?php endif;?>><?php echo isset($template_data['tr_tabs'][$key]['heading']) ? $template_data['tr_tabs'][$key]['heading'] : ''; ?></textarea>
                <?php else: ?>
                    <?php if($template_data['original']): ?>
                        <button type="button" class="button-secondary wcml_edit_content origin_content"><?php _e('Show content', 'woocommerce-multilingual') ?></button>
                    <?php else: ?>
                        <button type="button" class="button-secondary wcml_edit_content<?php if($template_data['is_duplicate_product']): ?> js-dup-disabled<?php endif;?>"<?php if($template_data['is_duplicate_product']): ?> disabled="disabled"<?php endif;?>><?php _e('Edit translation', 'woocommerce-multilingual') ?></button>
                    <?php endif;?>
                    <div class="wcml_editor">
                        <a class="media-modal-close wcml_close_cross" href="javascript:void(0);" title="<?php esc_attr_e('Close', 'woocommerce-multilingual') ?>"><span class="media-modal-icon"></span></a>
                        <div class="wcml_editor_original">
                            <h3><?php _e('Original content:', 'woocommerce-multilingual') ?></h3>
                            <textarea class="wcml_original_content"><?php echo get_post($orig_tab_id)->post_content; ?></textarea>
                        </div>
                        <div class="wcml_line"></div>
                        <div class="wcml_editor_translation">
                            <?php if(!$template_data['original']): ?>
                                <?php
                                if($trnsl_tab_id){
                                    $content = get_post($trnsl_tab_id)->post_content;
                                }else{
                                    $content = '';
                                }

                                wp_editor($content, 'wcmleditor'.$template_data['product_content'].$key.$template_data['lang'], array('textarea_name'=>$template_data['product_content'] .
                                '_'.$template_data['lang'].'[content][]','textarea_rows'=>20,'editor_class'=>'wcml_content_tr')); ?>
                            <?php endif; ?>
                        </div>
                        <div class="wcml_editor_buttons">
                            <?php if($template_data['original']): ?>
                                <button type="button" class="button-secondary wcml_popup_close"><?php _e('Close', 'woocommerce-multilingual') ?></button>
                            <?php else: ?>
                                <h3><?php printf(__('%s translation', 'woocommerce-multilingual'),$template_data['lang_name']); ?></h3>
                                <button type="button" class="button-secondary wcml_popup_cancel"><?php _e('Cancel', 'woocommerce-multilingual') ?></button>
                                <button type="button" class="button-secondary wcml_popup_ok"><?php _e('Ok', 'woocommerce-multilingual') ?></button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
<?php endif; ?>
