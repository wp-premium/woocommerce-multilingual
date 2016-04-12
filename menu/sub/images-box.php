<button id="prod_images_link_<?php echo $lang ?>" class="button-secondary js-table-toggle prod_images_link<?php if($is_duplicate_product): ?> js-dup-disabled<?php endif;?>" data-text-opened="<?php _e('Collapse', 'woocommerce-multilingual'); ?>" data-text-closed="<?php _e('Expand', 'woocommerce-multilingual'); ?>"<?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?>>
    <span><?php _e('Expand', 'woocommerce-multilingual'); ?></span>
    <i class="icon-caret-down"></i>
</button>

<table id="prod_images_<?php echo $lang ?>" class="widefat prod_images js-table">
    <tbody>
        <tr>
            <?php if($template_data['original']): ?>
                <td></td>
                <?php if(!isset($template_data['empty_images'])): ?>
                    <?php foreach($template_data['images_thumbnails'] as $prod_image): ?>
                        <td>
                            <?php echo wp_get_attachment_image( $prod_image , 'thumbnail'); ?>
                        </td>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>
        </tr>
        <?php if(isset($template_data['empty_images'])): ?>
            <tr>
                <td><?php _e('Please set images for product', 'woocommerce-multilingual'); ?></td>
            </tr>
        <?php elseif(isset($template_data['empty_translation'])): ?>
            <tr>
                <td><?php _e('Please save translation before translate images texts', 'woocommerce-multilingual'); ?></td>
            </tr>
        <?php else: ?>
            <?php $texts = array('title','caption','description'); ?>
            <?php foreach($texts as $text): ?>
                <tr>
                    <td>
                        <?php if($text == 'title'): ?>
                            <?php _e('Title', 'woocommerce-multilingual');  ?>
                        <?php elseif($text == 'caption'): ?>
                            <?php _e('Caption', 'woocommerce-multilingual'); ?>
                        <?php else: ?>
                            <?php _e('Description', 'woocommerce-multilingual'); ?>
                        <?php endif; ?>
                    </td>
                    <?php foreach($template_data['images_data'] as $key=>$image_data): ?>
                        <?php if(!empty($image_data)): ?>
                        <td>
                            <?php if($template_data['original']): ?>
                                <input type="text" value="<?php echo $image_data[$text]?>" readonly="readonly"/>
                            <?php else: ?>
                                <input type="text" name="images[<?php echo $key ?>][<?php echo $text; ?>]" value="<?php echo $image_data[$text]?>" placeholder="<?php esc_attr_e('Enter translation', 'woocommerce-multilingual') ?>"/>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>