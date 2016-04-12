<?php
global $woocommerce_wpml;

$woocommerce_wpml->troubleshooting->wcml_sync_variations_update_option();
$prod_with_variations = $woocommerce_wpml->troubleshooting->wcml_count_products_with_variations();
$prod_count = $woocommerce_wpml->troubleshooting->wcml_count_products_for_gallery_sync();
$prod_categories_count = $woocommerce_wpml->troubleshooting->wcml_count_product_categories();
$products = $woocommerce_wpml->troubleshooting->wcml_count_products();

$all_products_taxonomies = get_taxonomies(array('object_type'=>array('product')),'objects');
unset( $all_products_taxonomies['product_type'], $all_products_taxonomies['product_cat'], $all_products_taxonomies['product_tag'] );
?>
<div class="wrap wcml_trblsh">
    <div id="icon-wpml" class="icon32"><br /></div>
    <h2><?php _e('Troubleshooting', 'woocommerce-multilingual') ?></h2>
    <div class="wcml_trbl_warning">
        <h3><?php _e('Please make a backup of your database before you start the synchronization', 'woocommerce-multilingual') ?></h3>
    </div>
    <div class="trbl_variables_products">
        <ul>
            <li>
                <label>
                    <input type="checkbox" id="wcml_sync_products_data" />
                    <?php _e('Synchronize products translations data ( copy meta information from the original product ):', 'woocommerce-multilingual') ?>
                    <span class="prod_status"><?php echo $products; ?></span>&nbsp;<span><?php _e('left', 'woocommerce-multilingual') ?></span>
                </label>
            </li>
            <li>
                <label>
                    <input type="checkbox" id="wcml_sync_product_variations_data" checked="checked" />
                    <?php _e('Synchronize variation translations data ( copy meta information from the original variation ):', 'woocommerce-multilingual') ?>
                    <span class="var_status"><?php echo $prod_with_variations; ?></span>&nbsp;<span><?php _e('left', 'woocommerce-multilingual') ?></span>
                </label>
            </li>
            <li>
                <label>
                    <input type="checkbox" id="wcml_sync_product_variations_new" checked="checked" />
                    <?php _e('Create missing variation translations:', 'woocommerce-multilingual') ?>
                    <span class="var_status"><?php echo $prod_with_variations; ?></span>&nbsp;<span><?php _e('left', 'woocommerce-multilingual') ?></span>
                </label>
            </li>
            <li>
                <label>
                    <input type="checkbox" id="wcml_sync_product_variations_icl" checked="checked" />
                    <?php _e('Update variations translation relationships ( mark variations in the translated products as translations of corresponding variations in the original products ):', 'woocommerce-multilingual') ?>
                    <span class="var_status"><?php echo $prod_with_variations; ?></span>&nbsp;<span><?php _e('left', 'woocommerce-multilingual') ?></span>
                </label>
            </li>
            <?php if(defined('WPML_MEDIA_VERSION')): ?>
            <li>
                <label>
                    <input type="checkbox" id="wcml_sync_gallery_images" />
                    <?php _e('Synchronize products "gallery images":', 'woocommerce-multilingual') ?>
                    <span class="gallery_status"><?php echo $prod_count; ?></span>&nbsp;<span><?php _e('left', 'woocommerce-multilingual') ?></span>
                </label>
            </li>
            <?php endif; ?>
            <li>
                <label>
                    <input type="checkbox" id="wcml_sync_categories" />
                    <?php _e('Synchronize product categories fields: display type, thumbnail:', 'woocommerce-multilingual') ?>
                    <span class="cat_status"><?php echo $prod_categories_count; ?></span>&nbsp;<span><?php _e('left', 'woocommerce-multilingual') ?></span>
                </label>

            </li>

            <li>
                <label>
                    <input type="checkbox" id="wcml_duplicate_terms" <?php echo !count($all_products_taxonomies)?'disabled="disabled"':''; ?> />
                    <?php _e('Duplicate terms in secondary language ( please select attribute ):', 'woocommerce-multilingual') ?>
                    <select id="attr_to_duplicate" <?php echo !count($all_products_taxonomies)?'disabled="disabled"':''; ?>>
                        <?php
                        $terms_count = false;
                        if(!count($all_products_taxonomies)){ ?>
                            <option value="0" ><?php _e('none', 'woocommerce-multilingual'); ?></option>
                        <?php }
                        foreach($all_products_taxonomies as $tax_key => $tax):
                            if(!$terms_count) $terms_count = wp_count_terms($tax_key); ?>
                            <option value="<?php echo $tax_key; ?>" rel="<?php echo wp_count_terms($tax_key); ?>"><?php echo ucfirst($tax->labels->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="attr_status"><?php echo $terms_count; ?></span>&nbsp;<span><?php _e('left', 'woocommerce-multilingual') ?></span>
                </label>

            </li>
            <li>
                <button type="button" class="button-secondary" id="wcml_trbl"><?php _e('Start', 'woocommerce-multilingual') ?></button>
                <input id="count_prod_variat" type="hidden" value="<?php echo $prod_with_variations; ?>"/>
                <input id="count_products" type="hidden" value="<?php echo $products; ?>"/>
                <input id="count_prod" type="hidden" value="<?php echo $prod_count; ?>"/>
                <input id="count_categories" type="hidden" value="<?php echo $prod_categories_count; ?>"/>
                <input id="count_terms" type="hidden" value="<?php echo $terms_count; ?>"/>
                <input id="sync_galerry_page" type="hidden" value="0"/>
                <input id="sync_category_page" type="hidden" value="0"/>
                <span class="spinner"></span>
            </li>
        </ul>
    </div>
</div>

<script type="text/javascript">

    jQuery(document).ready(function(){
        //troubleshooting page
        jQuery('#wcml_trbl').on('click',function(){
            var field = jQuery(this);
            field.attr('disabled', 'disabled');
            jQuery('.spinner').css('display','inline-block').css('visibility','visible');

            if( jQuery('#wcml_sync_products_data').is(':checked') ){
                sync_products();
            }else if( jQuery('#wcml_sync_product_variations_data').is(':checked') || jQuery('#wcml_sync_product_variations_new').is(':checked') || jQuery('#wcml_sync_product_variations_icl').is(':checked') ){
                sync_variations();
            }else if(jQuery('#wcml_sync_gallery_images').is(':checked')){
                sync_product_gallery();
            }else if(jQuery('#wcml_sync_categories').is(':checked')){
                sync_product_categories();
            }else if(jQuery('#wcml_duplicate_terms').is(':checked')){
                duplicate_terms();
            }else{
                jQuery('#wcml_trbl').removeAttr('disabled');
                jQuery('.spinner').hide();
            }
        });

        jQuery('#attr_to_duplicate').on('change',function(){
           jQuery('.attr_status').html(jQuery(this).find('option:selected').attr('rel'))
           jQuery('#count_terms').val(jQuery(this).find('option:selected').attr('rel'))
        });

    });

    function sync_products(){
        jQuery.ajax({
            type : "post",
            url : ajaxurl,
            data : {
                action: "trbl_sync_products",
                wcml_nonce: "<?php echo wp_create_nonce('trbl_sync_products'); ?>"
            },
            success: function(response) {
                if(jQuery('#count_products').val() == 0){
                    jQuery('.prod_status').html(0);
                    if( jQuery('#wcml_sync_product_variations_data').is(':checked') || jQuery('#wcml_sync_product_variations_new').is(':checked') || jQuery('#wcml_sync_product_variations_icl').is(':checked') ){
                        sync_variations();
                    }else if(jQuery('#wcml_sync_gallery_images').is(':checked')){
                        sync_product_gallery();
                    }else if(jQuery('#wcml_sync_categories').is(':checked')){
                        sync_product_categories();
                    }else if(jQuery('#wcml_duplicate_terms').is(':checked')){
                        duplicate_terms();
                    }else{
                        jQuery('#wcml_trbl').removeAttr('disabled');
                        jQuery('.spinner').hide();
                        jQuery('#wcml_trbl').next().fadeOut();
                    }

                }else{
                    var left = jQuery('#count_products').val() - 3;
                    if( left < 0 ){
                        left = 0;
                    }
                    jQuery('.prod_status').html(left);

                    jQuery('#count_products').val(left);
                    sync_products();
                }
            }
        });
    }


    function sync_variations(){
        jQuery.ajax({
            type : "post",
            url : ajaxurl,
            data : {
                action: "trbl_sync_variations",
                sync_data: jQuery('#wcml_sync_product_variations_data').is(':checked'),
                sync_new: jQuery('#wcml_sync_product_variations_new').is(':checked'),
                sync_icl: jQuery('#wcml_sync_product_variations_icl').is(':checked'),
                wcml_nonce: "<?php echo wp_create_nonce('trbl_sync_variations'); ?>"
            },
            success: function(response) {
                if(jQuery('#count_prod_variat').val() == 0){
                    jQuery('.var_status').each(function(){
                        if( jQuery(this).parent().find('input').is(':checked') ){
                            jQuery(this).html(0);
                        }
                    });
                    if(jQuery('#wcml_sync_gallery_images').is(':checked')){
                        sync_product_gallery();
                    }else if(jQuery('#wcml_sync_categories').is(':checked')){
                        sync_product_categories();
                    }else if(jQuery('#wcml_duplicate_terms').is(':checked')){
                        duplicate_terms();
                    }else{
                        jQuery('#wcml_trbl').removeAttr('disabled');
                        jQuery('.spinner').hide();
                        jQuery('#wcml_trbl').next().fadeOut();
                    }

                }else{
                    var left = jQuery('#count_prod_variat').val()-3;
                    if(left < 0 ){
                        left = 0;
                    }
                    jQuery('.var_status').each(function(){
                        if( jQuery(this).parent().find('input').is(':checked') ){
                            jQuery(this).html(left);
                        }
                    });
                    jQuery('#count_prod_variat').val(left);
                    sync_variations();
                }
            }
        });
    }

    function sync_product_gallery(){
        jQuery.ajax({
            type : "post",
            url : ajaxurl,
            data : {
                action: "trbl_gallery_images",
                wcml_nonce: "<?php echo wp_create_nonce('trbl_gallery_images'); ?>",
                page: jQuery('#sync_galerry_page').val()
            },
            success: function(response) {
                if(jQuery('#count_prod').val() == 0){
                    if(jQuery('#wcml_sync_categories').is(':checked')){
                        sync_product_categories();
                    }else if(jQuery('#wcml_duplicate_terms').is(':checked')){
                        duplicate_terms();
                    }else{
                    jQuery('#wcml_trbl').removeAttr('disabled');
                    jQuery('.spinner').hide();
                    jQuery('#wcml_trbl').next().fadeOut();
                    }
                    jQuery('.gallery_status').html(0);
                }else{
                    var left = jQuery('#count_prod').val()-5;
                    if(left < 0 ){
                        left = 0;
                    }else{
                        jQuery('#sync_galerry_page').val(parseInt(jQuery('#sync_galerry_page').val())+1)
                    }
                    jQuery('.gallery_status').html(left);
                    jQuery('#count_prod').val(left);
                    sync_product_gallery();
                }
            }
        });
    }

    function sync_product_categories(){
        jQuery.ajax({
            type : "post",
            url : ajaxurl,
            data : {
                action: "trbl_sync_categories",
                wcml_nonce: "<?php echo wp_create_nonce('trbl_sync_categories'); ?>",
                page: jQuery('#sync_category_page').val()
            },
            success: function(response) {
                if(jQuery('#count_categories').val() == 0){
                    if(jQuery('#wcml_duplicate_terms').is(':checked')){
                        duplicate_terms();
                    }else{
                    jQuery('#wcml_trbl').removeAttr('disabled');
                    jQuery('.spinner').hide();
                    jQuery('#wcml_trbl').next().fadeOut();
                    }
                    jQuery('.cat_status').html(0);
                }else{
                    var left = jQuery('#count_categories').val()-5;
                    if(left < 0 ){
                        left = 0;
                    }else{
                        jQuery('#sync_category_page').val(parseInt(jQuery('#sync_category_page').val())+1)
                    }
                    jQuery('.cat_status').html(left);
                    jQuery('#count_categories').val(left);
                    sync_product_categories();
                }
            }
        });
    }

    function duplicate_terms(){
        jQuery.ajax({
            type : "post",
            url : ajaxurl,
            data : {
                action: "trbl_duplicate_terms",
                wcml_nonce: "<?php echo wp_create_nonce('trbl_duplicate_terms'); ?>",
                attr: jQuery('#attr_to_duplicate option:selected').val()
            },
            success: function(response) {
                if(jQuery('#count_terms').val() == 0){
                    jQuery('#wcml_trbl').removeAttr('disabled');
                    jQuery('.spinner').hide();
                    jQuery('#wcml_trbl').next().fadeOut();
                    jQuery('.attr_status').html(0);
                }else{
                    var left = jQuery('#count_terms').val()-5;
                    if(left < 0 ){
                        left = 0;
                    }
                    jQuery('.attr_status').html(left);
                    jQuery('#count_terms').val(left);

                    duplicate_terms();
                }
            }
        });
    }

</script>