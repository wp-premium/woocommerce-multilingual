<td>
    <button id="wc_composite_link_<?php echo $lang ?>" class="button-secondary js-table-toggle wc_composite_link<?php if($is_duplicate_product): ?> js-dup-disabled<?php endif;?>" data-text-opened="<?php _e('Collapse', 'woocommerce-multilingual'); ?>" data-text-closed="<?php _e('Expand', 'woocommerce-multilingual'); ?>"<?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?>>
        <span><?php _e('Expand', 'woocommerce-multilingual'); ?></span>
        <i class="icon-caret-down"></i>
    </button>

    <table id="prod_wc_composite_<?php echo $lang ?>" class="widefat prod_variations js-table">

        <tbody>

            <?php $disabled = $template_data['wc_composite_components']['_is_original'] ? ' disabled="disabled"' : ''; ?>
            <?php foreach($template_data['wc_composite_components']['components'] as $key => $component): ?>
            <tr>
                <td>
                    <label><?php _e('Title', 'woocommerce-multilingual'); ?>&nbsp;
                        <textarea rows="1" name="wc_composite_component[<?php echo $key ?>][title]"<?php echo $disabled ?>><?php echo esc_attr( $component['title'] ) ?></textarea>
                    </label>
                </td>
                <td>
                    <label><?php _e('Description', 'woocommerce-multilingual'); ?>&nbsp;
                        <textarea rows="1" name="wc_composite_component[<?php echo $key ?>][description]"<?php echo $disabled ?>><?php echo esc_attr( $component['description'] ) ?></textarea>
                    </label>
                </td>
            </tr>
            <?php endforeach ?>

        </tbody>

    </table>

</td>




