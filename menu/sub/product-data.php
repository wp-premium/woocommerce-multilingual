<?php
$product_id = $product->ID;
$product_images = $woocommerce_wpml->products->product_images_ids($product->ID);
$product_contents = $woocommerce_wpml->products->get_product_contents($product_id);
$trid = $sitepress->get_element_trid($product_id,'post_'.$product->post_type);
$product_translations = $sitepress->get_element_translations($trid,'post_'.$product->post_type,true,true);
$check_on_permissions = false;
if(!current_user_can('wpml_operate_woocommerce_multilingual')){
    $check_on_permissions = true;
}

$lang_codes = array();
foreach ($active_languages as $language) {
    if($default_language == $language['code'] || current_user_can('wpml_manage_woocommerce_multilingual') || (wpml_check_user_is_translator($default_language,$language['code']) && !current_user_can('wpml_manage_woocommerce_multilingual')) ){
        if(!isset($_GET['slang']) || (isset($_GET['slang']) && ($_GET['slang'] == $language['code'] || $default_language == $language['code'])))
            $lang_codes[$language['code']] = $language['display_name'];
    }
}
$default_language_display_name = $lang_codes[$default_language];
unset($lang_codes[$default_language]);

if( isset($job->language_code ) ){
    $lang_codes = array($default_language => $default_language_display_name, $job->language_code => $lang_codes[$job->language_code] );
}else{
    $lang_codes = array($default_language => $default_language_display_name)+$lang_codes;
}


$button_labels = array(
    'save'      => esc_attr__('Save', 'woocommerce-multilingual'),
    'update'    => esc_attr__('Update', 'woocommerce-multilingual'),
);
?>
<tr class="outer" data-prid="<?php echo $product->ID; ?>" <?php echo !isset( $display_inline ) ? 'display="none"' : ''; ?> >
    <td colspan="3">
        <div class="wcml_product_row" id="prid_<?php echo $product->ID; ?>" <?php echo isset($pr_edit) ? 'style="display:block;"':''; ?>>
            <div class="inner">
                <table class="fixed wcml_products_translation">
                    <thead>
                        <tr>
                            <th scope="col"><?php _e('Language', 'woocommerce-multilingual') ?></th>
                            <?php $product_contents_labels = $woocommerce_wpml->products->get_product_contents_labels($product_id);?>
                            <?php foreach ($product_contents_labels as $product_content) : ?>
                                <th scope="col"><?php echo $product_content; ?></th>
                            <?php endforeach; ?>
                            <?php
                            $attributes = $woocommerce_wpml->products->get_product_atributes($product_id);
                            foreach($attributes as $key=>$attribute): ?>
                                <?php if(!$attribute['is_taxonomy']): ?>
                                    <th scope="col"><?php echo  $attribute['name']; ?></th>
                                <?php else: ?>
                                    <?php unset($attributes[$key]); ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <?php
                            do_action('wcml_extra_titles',$product_id);
                            ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lang_codes as $key=>$lang) : if($key != $default_language && $check_on_permissions && ! $woocommerce_wpml->products->user_can_translate_product( $trid, $key )) continue;?>
                            <?php if($key != $default_language && isset($product_translations[$key])
                                && get_post_meta($product_translations[$key]->element_id, '_icl_lang_duplicate_of', true) == $product->ID):
                                $is_duplicate_product = true; ?>
                                <tr class="wcml_duplicate_product_notice">
                                    <td>&nbsp;</td>
                                    <td colspan="<?php echo count($product_contents); ?>">
                                        <span class="js-wcml_duplicate_product_notice_<?php echo $key ?>" >
                                            <?php printf(__('This product is an exact duplicate of the %s product.', 'wcml-wpml'),
                                                $lang_codes[$sitepress->get_default_language()]); ?>&nbsp;
                                            <a href="#edit-<?php echo $product_id ?>_<?php echo $key ?>"><?php _e('Edit independently.', 'woocommerce-multilingual') ?></a>
                                        </span>
                                        <span class="js-wcml_duplicate_product_undo_<?php echo $key ?>" style="display: none;" >
                                            <a href="#undo-<?php echo $product_id ?>_<?php echo $key ?>"><?php _e('Undo (keep this product as a duplicate)', 'woocommerce-multilingual') ?></a>
                                        </span>
                                    </td>
                                </tr>
                            <?php else: $is_duplicate_product = false; ?>
                            <?php endif; ?>
                            <tr rel="<?php echo $key; ?>">
                                <td>
                                    <?php echo $lang; ?>
                                    <?php if($default_language == $key && current_user_can('wpml_operate_woocommerce_multilingual') ): ?>
                                        <a class="edit-translation-link" title="<?php __("edit product", "wpml-wcml") ?>" href="<?php echo get_edit_post_link($product_id); ?>"><i class="icon-edit"></i></a>
                                    <?php else: ?>
                                        <input type="hidden" name="icl_language" value="<?php echo $key ?>" />
                                        <input type="hidden" name="job_id" value="<?php echo $job_id ?>" />
                                        <input type="hidden" name="end_duplication[<?php echo $product_id ?>][<?php echo $key ?>]" value="<?php echo !intval($is_duplicate_product) ?>" />
                                        <?php $button_label = isset($product_translations[$key]) && !is_null($product_translations[$key]->element_id) ? $button_labels['update'] : $button_labels['save'] ;?>
                                        <input type="submit" name="product#<?php echo $product_id ?>#<?php echo $key ?>" disabled value="<?php echo $button_label ?>" class="button-secondary wcml_update">
                                        <span class="wcml_spinner spinner"></span>
                                    <?php endif; ?>
                                </td>
                                <?php
                                if(!current_user_can('wpml_manage_woocommerce_multilingual') && isset($product_translations[$key])){
                                    $tr_status = $wpdb->get_row($wpdb->prepare("SELECT status,translator_id FROM ". $wpdb->prefix ."icl_translation_status WHERE translation_id = %d",$product_translations[$key]->translation_id));

                                    if(!is_null($tr_status) && get_current_user_id() != $tr_status->translator_id ){
                                        if($tr_status->status == ICL_TM_IN_PROGRESS){ ?>
                                            <td><?php _e('Translation in progress', 'woocommerce-multilingual'); ?><br>&nbsp;</td>
                                            <?php continue;
                                        }elseif($tr_status->status == ICL_TM_WAITING_FOR_TRANSLATOR && !$job_id ){
                                            $tr_job_id = $wpdb->get_var($wpdb->prepare("
                                                                    SELECT j.job_id
                                                                        FROM {$wpdb->prefix}icl_translate_job j
                                                                        JOIN {$wpdb->prefix}icl_translation_status s ON j.rid = s.rid
                                                                    WHERE s.translation_id = %d
                                                                ", $product_translations[$key]->translation_id ) );
                                            ?>
                                            <td><?php printf('<a href="%s" class="button-secondary">'.__('Take this and edit', 'woocommerce-multilingual').'</a>', admin_url('admin.php?page=wpml-wcml&tab=products&prid=' . $product->ID.'&job_id='.$tr_job_id)); ?><br>&nbsp;</td>
                                            <?php continue;
                                        }
                                    }
                                }

                                foreach ($product_contents as $product_content) : ?>
                                    <td>
                                        <?php
                                        $trn_contents  = $woocommerce_wpml->products->get_product_content_translation($product_id,$product_content,$key);

                                        $missing_translation = false;
                                        if($default_language == $key){
                                            $product_fields_values[$product_content] = $trn_contents;
                                        }else{
                                            if(isset($product_fields_values[$product_content]) &&
                                                !empty($product_fields_values[$product_content]) &&
                                                empty($trn_contents)
                                            ){
                                                $missing_translation = true;
                                            }
                                        }

                                        if(!$woocommerce_wpml->products->check_custom_field_is_single_value($product_id,$product_content)){
                                            echo $woocommerce_wpml->products->custom_box($product_id,$product_content,$trn_contents,$key,$lang,$is_duplicate_product);
                                        }else if(in_array($product_content, array('_file_paths'))): ?>
                                            <?php
                                            $file_paths = '';
                                            if( is_array($trn_contents) ){
                                                foreach($trn_contents as $trn_content){
                                                    $file_paths = $file_paths ? $file_paths . "\n" .$trn_content : $trn_content;
                                                }
                                            } ?>
                                            <?php if($default_language == $key): ?>
                                                <textarea value="<?php echo $file_paths; ?>" disabled="disabled"><?php echo $file_paths; ?></textarea>
                                            <?php else: ?>
                                                <textarea value="<?php echo $file_paths; ?>" name='<?php echo $product_content.'_'.$key ?>' class="wcml_file_paths_textarea<?php if($is_duplicate_product): ?> js-dup-disabled<?php endif;?>"<?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?>><?php echo $file_paths; ?></textarea>
                                                <button type="button" class="button-secondary wcml_file_paths<?php if($is_duplicate_product): ?> js-dup-disabled<?php endif;?>"<?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?>><?php _e('Choose a file', 'woocommerce-multilingual') ?></button>
                                            <?php endif;?>
                                        <?php elseif($product_content == 'title'): ?>
                                            <?php if($default_language == $key): ?>
                                                <textarea rows="1" disabled="disabled"><?php echo $trn_contents['title']; ?></textarea><br>
                                            <?php else: ?>
                                                <textarea class="<?php if($is_duplicate_product): ?> js-dup-disabled<?php endif;?>" name="<?php echo $product_content.'_'.$key; ?>" rows="2" placeholder="<?php esc_attr_e('Enter translation', 'woocommerce-multilingual') ?>"<?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?> ><?php echo $trn_contents['title']; ?></textarea>
                                            <?php endif;?>
                                            <div class="edit_slug_block">
                                                <?php $hide = !$trn_contents['name'] ? 'hidden' : ''; ?>
                                                    <a href="javascript:void(0)" class="edit_slug_show_link <?php echo $hide; ?>"><?php $default_language == $key ? _e('Show slug', 'woocommerce-multilingual') : _e('Edit slug', 'woocommerce-multilingual') ?></a>
                                                    <a href="javascript:void(0)" class="edit_slug_hide_link  <?php echo $hide; ?>"><?php _e('Hide', 'woocommerce-multilingual') ?></a>
                                                    </br>
                                                    <?php if($default_language == $key): ?>
                                                        <input type="text" value="<?php echo $trn_contents['name']; ?>" class="edit_slug_input" disabled="disabled" />
                                                    <?php else: ?>
                                                        <input type="text" value="<?php echo $trn_contents['name']; ?>" class="edit_slug_input <?php echo $hide; ?>" name="<?php echo 'post_name_'.$key; ?>"  <?php echo $hide?'disabled="disabled"':''; ?> />
                                                    <?php endif;?>
                                                    <?php if(!$trn_contents['name']): ?>
                                                        <span class="edit_slug_warning"><?php _e('Please save translation before edit slug', 'woocommerce-multilingual') ?></span>
                                                    <?php endif;?>
                                            </div>
                                        <?php elseif(is_array($trn_contents)): ?>
                                            <?php foreach ($trn_contents as $tax_key=>$trn_content) : ?>
                                                <?php if($default_language == $key): ?>
                                                    <textarea rows="1" disabled="disabled"><?php echo $trn_content; ?></textarea>
                                                <?php else: ?>
                                                    <input class="<?php if($is_duplicate_product): ?> js-dup-disabled<?php endif;?>" type="text" name="<?php echo $product_content.'_'.$key.'['.$tax_key.']'; ?>" value="<?php echo $trn_content ?>" placeholder="<?php esc_attr_e('Enter translation', 'woocommerce-multilingual') ?>"<?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?> /><br>
                                                <?php endif;?>
                                            <?php endforeach; ?>
                                        <?php elseif(in_array($product_content,array('content','excerpt'))): ?>
                                            <?php if($default_language == $key): ?>
                                                <button type="button" class="button-secondary wcml_edit_content origin_content"><?php _e('Show content', 'woocommerce-multilingual') ?></button>
                                            <?php else: ?>
                                                <button type="button" class="button-secondary wcml_edit_content<?php if($is_duplicate_product): ?> js-dup-disabled<?php endif;?>"<?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?>><?php _e('Edit translation', 'woocommerce-multilingual') ?></button>
                                                <?php if($missing_translation): ?>
                                                    <span class="wcml_field_translation_<?php echo $product_content ?>_<?php echo $key ?>">
                                                        <p class="missing-translation">
                                                            <i class="icon-warning-sign"></i>
                                                            <?php _e('Translation missing', 'woocommerce-multilingual'); ?>
                                                        </p>
                                                    </span>
                                                <?php endif; ?>
                                            <?php endif;?>
                                            <div class="wcml_editor">
                                                <a class="media-modal-close wcml_close_cross" href="javascript:void(0);" title="<?php esc_attr_e('Close', 'woocommerce-multilingual') ?>"><span class="media-modal-icon"></span></a>
                                                <div class="wcml_editor_original">
                                                    <h3><?php _e('Original content:', 'woocommerce-multilingual') ?></h3>
                                                    <?php
                                                    if($product_content == 'content'){
                                                        $original_content = apply_filters('the_editor_content', $product->post_content);
                                                        $original_content = wpautop($original_content);
                                                    }else{
                                                        $original_content = apply_filters('the_editor_content', $product->post_excerpt);
                                                    }
                                                    ?>
                                                    <textarea class="wcml_original_content"><?php echo $original_content; ?></textarea>

                                                </div>
                                                <div class="wcml_line"></div>
                                                <div class="wcml_editor_translation">
                                                    <?php if($default_language != $key): ?>
                                                        <?php
                                                        $tr_id = apply_filters( 'translate_object_id',$product_id, 'product', true, $key);
                                                        if(!$woocommerce_wpml->settings['first_editor_call']):
                                                             wp_editor($trn_contents, 'wcmleditor'.$product_content.$tr_id.$key, array('textarea_name'=>$product_content .'_'.$key,'textarea_rows'=>20,'editor_class'=>'wcml_content_tr')); ?>
                                                        <?php else: ?>
                                                            <div id="wp-wcmleditor<?php echo $product_content.$tr_id.$key ?>-wrap" class="wp-core-ui wp-editor-wrap">
                                                                <div id="wp-wcml<?php echo $product_content.$tr_id.$key ?>-editor-tools" class="wp-editor-tools hide-if-no-js">
                                                                    <div id="wp-wcmleditor<?php echo $product_content.$tr_id.$key ?>-media-buttons" class="wp-media-buttons">
                                                                        <a href="#" id="insert-media-button" class="button insert-media add_media" data-editor="wcmleditor<?php echo $product_content.$tr_id.$key ?>" title="<?php _e('Add Media'); ?>">
                                                                            <span class="wp-media-buttons-icon"></span>
                                                                            <?php _e('Add Media'); ?>
                                                                        </a>
                                                                    </div>
                                                                    <div class="wp-editor-tabs">
                                                                        <a id="wcmleditor<?php echo $product_content.$tr_id.$key ?>-html" class="wp-switch-editor switch-html" ><?php _e('Text'); ?></a>
                                                                        <a id="wcmleditor<?php echo $product_content.$tr_id.$key ?>-tmce" class="wp-switch-editor switch-tmce" ><?php _e('Visual'); ?></a>
                                                                    </div>
                                                                </div>
                                                                <div id="wp-wcmleditor<?php echo $product_content.$tr_id.$key ?>-editor-container" class="wp-editor-container">
                                                                    <textarea class="wcml_content_tr wp-editor-area" rows="20" autocomplete="off" cols="40" name="<?php echo $product_content .'_'.$key; ?>" id="wcmleditor<?php echo $product_content.$tr_id.$key ?>" aria-hidden="true"><?php echo $trn_contents ?></textarea>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>

                                                    <?php endif; ?>
                                                </div>
                                                <div class="wcml_editor_buttons">
                                                    <?php if($default_language == $key): ?>
                                                        <button type="button" class="button-secondary wcml_popup_close"><?php _e('Close', 'woocommerce-multilingual') ?></button>
                                                    <?php else: ?>
                                                        <h3><?php printf(__('%s translation', 'woocommerce-multilingual'),$lang); ?></h3>
                                                        <button type="button" class="button-secondary wcml_popup_cancel"><?php _e('Cancel', 'woocommerce-multilingual') ?></button>
                                                        <button type="button" class="button-secondary wcml_popup_ok"><?php _e('Ok', 'woocommerce-multilingual') ?></button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php elseif(in_array($product_content,array('images'))):
                                            echo $woocommerce_wpml->products->product_images_box($product_id,$key,$is_duplicate_product); ?>
                                        <?php elseif(in_array($product_content,array('variations'))):
                                            echo $woocommerce_wpml->products->product_variations_box($product_id,$key,$is_duplicate_product); ?>
                                        <?php elseif($product_content == '_file_paths'): ?>
                                            <textarea placeholder="<?php esc_attr_e('Upload file', 'woocommerce-multilingual') ?>" value="" name='<?php echo $product_content.'_'.$key ?>' class="wcml_file_paths_textarea<?php if($is_duplicate_product): ?> js-dup-disabled<?php endif;?>"<?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?>></textarea>
                                            <button type="button" class="button-secondary wcml_file_paths<?php if($is_duplicate_product): ?> js-dup-disabled<?php endif;?>"<?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?>><?php _e('Choose a file', 'woocommerce-multilingual') ?></button>
                                        <?php else: ?>
                                            <?php if($default_language == $key): ?>
                                                <textarea rows="1" disabled="disabled"><?php echo $trn_contents; ?></textarea><br>
                                            <?php else: ?>
                                                <textarea class="<?php if($is_duplicate_product): ?> js-dup-disabled<?php endif;?>" name="<?php echo $product_content.'_'.$key; ?>" rows="2" placeholder="<?php esc_attr_e('Enter translation', 'woocommerce-multilingual') ?>"<?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?> ><?php echo $trn_contents; ?></textarea>
                                            <?php endif;?>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                                <?php do_action('wcml_gui_additional_box',$product_id,$key,$is_duplicate_product); ?>
                                <?php
                                foreach ($attributes as $attr_key=>$attribute):  ?>
                                    <td>
                                        <?php $trn_attribute = $woocommerce_wpml->products->get_custom_attribute_translation($product_id, $attr_key, $attribute, $key); ?>
                                        <label class="custom_attr_label"><?php _e('name', 'woocommerce-multilingual'); ?></label>
                                        <br>
                                        <?php if (!$trn_attribute): ?>
                                            <input type="text" name="<?php echo $attr_key . '_name_' . $key ; ?>" value="" placeholder="<?php esc_attr_e('Enter translation', 'woocommerce-multilingual') ?>"<?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?> />
                                        <?php else: ?>
                                            <?php if($default_language == $key): ?>
                                                <textarea rows="1" disabled="disabled"><?php echo $trn_attribute['name']; ?></textarea>
                                            <?php else: ?>
                                                <input class="<?php if($is_duplicate_product): ?> js-dup-disabled<?php endif;?>" type="text" name="<?php echo $attr_key . '_name_' . $key; ?>" value="<?php echo $trn_attribute['name']; ?>" placeholder="<?php esc_attr_e('Enter translation', 'woocommerce-multilingual') ?>" <?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?> />
                                            <?php endif;?>
                                        <?php endif;?>
                                        <br>
                                        <label class="custom_attr_label"><?php _e('values', 'woocommerce-multilingual'); ?></label>
                                        <br>
                                        <?php if (!$trn_attribute): ?>
                                            <input class="<?php if($is_duplicate_product): ?> js-dup-disabled<?php endif;?>" type="text" name="<?php echo $attr_key . '_' . $key ; ?>" value="" placeholder="<?php esc_attr_e('Enter translation', 'woocommerce-multilingual') ?>" <?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?>/>
                                        <?php else: ?>
                                            <?php if($default_language == $key): ?>
                                                <textarea rows="1" disabled="disabled"><?php echo $trn_attribute['value']; ?></textarea>
                                            <?php else: ?>
                                                <input class="<?php if($is_duplicate_product): ?> js-dup-disabled<?php endif;?>" type="text" name="<?php echo $attr_key . '_' . $key; ?>" value="<?php echo $trn_attribute['value']; ?>" placeholder="<?php esc_attr_e('Enter translation', 'woocommerce-multilingual') ?>" <?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?> />
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php
    if(!$woocommerce_wpml->settings['first_editor_call']){
        //load editor js
        if ( class_exists( '_WP_Editors' ) )
        _WP_Editors::editor_js();
        $woocommerce_wpml->settings['first_editor_call'] = true;
        $woocommerce_wpml->update_settings();
    }

    ?>
    </td>
</tr>
