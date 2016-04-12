<button id="custom_block_link_<?php echo $lang ?>" class="button-secondary js-table-toggle prod_images_link<?php if($is_duplicate_product): ?> js-dup-disabled<?php endif;?>" data-text-opened="<?php _e('Collapse', 'woocommerce-multilingual'); ?>" data-text-closed="<?php _e('Expand', 'woocommerce-multilingual'); ?>"<?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?>>
    <span><?php _e('Expand', 'woocommerce-multilingual'); ?></span>
    <i class="icon-caret-down"></i>
</button>

<table id="<?php echo $product_content.'_'.$lang ?>" class="widefat custom_fields_block js-table">
    <tbody>
        <?php echo apply_filters('wcml_custom_box_html','',$template_data,$lang); ?>
    </tbody>
</table>