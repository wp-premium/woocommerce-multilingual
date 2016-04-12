<div class="icl_tt_main_bottom" style="display: none;">
    <br/>
    <?php if ( isset($attribute_taxonomies_arr) && in_array($taxonomy, $attribute_taxonomies_arr) ): ?>

        <form id="wcml_tt_sync_variations" method="post">
            <input type="hidden" name="action" value="wcml_sync_product_variations" />
            <input type="hidden" name="taxonomy" value="<?php echo $taxonomy ?>" />
            <input type="hidden" name="wcml_nonce" value="<?php echo wp_create_nonce('wcml_sync_product_variations') ?>" />
            <input type="hidden" name="last_post_id" value="" />
            <input type="hidden" name="languages_processed" value="0" />

            <p>
                <input class="button-secondary" type="submit" value="<?php esc_attr_e("Synchronize attributes and update product variations", 'woocommerce-multilingual') ?>" />
                <img src="<?php echo ICL_PLUGIN_URL . '/res/img/ajax-loader.gif' ?>" alt="loading" height="16" width="16" class="wpml_tt_spinner" />
            </p>
            <span class="errors icl_error_text"></span>
            <div class="wcml_tt_sycn_preview"></div>
        </form>


        <p><?php _e('This will automatically generate variations for translated products corresponding to recently translated attributes.', 'woocommerce-multilingual'); ?></p>
        <?php if(!empty($wcml_settings['variations_needed'][$taxonomy])): ?>
            <p><?php printf(__('Currently, there are %s variations that need to be created.', 'woocommerce-multilingual'), '<strong>' . $wcml_settings['variations_needed'][$taxonomy] . '</strong>') ?></p>
        <?php endif; ?>

    <?php else: ?>

        <form id="wcml_tt_sync_assignment">
            <input type="hidden" name="taxonomy" value="<?php echo $taxonomy ?>"/>
            <?php wp_nonce_field('wcml_sync_taxonomies_in_content_preview', 'wcml_sync_taxonomies_in_content_preview_nonce'); ?>
            <p>
                <input class="button-secondary" type="submit" value="<?php printf( __( "Synchronize %s assignment in content", 'woocommerce-multilingual' ), $taxonomy_obj->labels->name ) ?>"/>
                <img src="<?php echo ICL_PLUGIN_URL . '/res/img/ajax-loader.gif' ?>" alt="loading" height="16" width="16" class="wpml_tt_spinner"/>
            </p>
            <span class="errors icl_error_text"></span>
        </form>
        <div id="wcml_tt_sync_preview"></div>


        <p><?php printf( __( 'This action lets you automatically apply the %s taxonomy to your content in different  languages. It will scan the original content and apply the same taxonomy to translated content.', 'woocommerce-multilingual' ), '<i>' . $taxonomy_obj->labels->singular_name . '</i>' ); ?></p>


    <?php endif; ?>

</div>