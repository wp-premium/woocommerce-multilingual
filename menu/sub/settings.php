<?php global $sitepress_settings;
$default_language = $sitepress->get_default_language();
?>

<div class="wcml-section">
    <div class="wcml-section-header">
        <h3>
            <?php _e('Plugins Status', 'woocommerce-multilingual'); ?>
            <i class="icon-question-sign" data-tip="<?php _e('WooCommerce Multilingual depends on several plugins to work. If any required plugin is missing, you should install and activate it.', 'woocommerce-multilingual') ?>"></i>
        </h3>
    </div>
    <div class="wcml-section-content">
        <ul>
            <?php if (defined('ICL_SITEPRESS_VERSION')) : ?>
                <li><i class="icon-ok"></i> <?php printf(__('%s plugin is installed and active.', 'woocommerce-multilingual'), '<strong>WPML</strong>'); ?></li>
                <?php if($sitepress->setup()): ?>
                    <li><i class="icon-ok"></i> <?php printf(__('%s is set up.', 'woocommerce-multilingual'), '<strong>WPML</strong>'); ?></li>
                <?php else: ?>
                    <li><i class="icon-warning-sign"></i> <?php printf(__('%s is not set up.', 'woocommerce-multilingual'), '<strong>WPML</strong>'); ?></li>
                <?php endif; ?>
            <?php endif; ?>
            <?php if (defined('WPML_MEDIA_VERSION')) : ?>
                <li><i class="icon-ok"></i> <?php printf(__('%s plugin is installed and active.', 'woocommerce-multilingual'), '<strong>WPML Media</strong>'); ?></li>
            <?php endif; ?>
            <?php if (defined('WPML_TM_VERSION')) : ?>
                <li><i class="icon-ok"></i> <?php printf(__('%s plugin is installed and active.', 'woocommerce-multilingual'), '<strong>WPML Translation Management</strong>'); ?></li>
            <?php endif; ?>
            <?php if (defined('WPML_ST_VERSION')) : ?>
                <li><i class="icon-ok"></i> <?php printf(__('%s plugin is installed and active.', 'woocommerce-multilingual'), '<strong>WPML String Translation</strong>'); ?></li>
            <?php endif; ?>
            <?php
            global $woocommerce;
            if (class_exists('Woocommerce')) : ?>
                <li><i class="icon-ok"></i> <?php printf(__('%s plugin is installed and active.', 'woocommerce-multilingual'), '<strong>WooCommerce</strong>'); ?></li>
            <?php endif; ?>
        </ul>
    </div> <!-- .wcml-section-content -->

</div> <!-- .wcml-section -->


<?php
$miss_slug_lang = $woocommerce_wpml->strings->get_missed_product_slug_translations_languages();
$prod_slug = $woocommerce_wpml->strings->product_permalink_slug();

if( ( !WPML_SUPPORT_STRINGS_IN_DIFF_LANG && $default_language != 'en' && empty( $woocommerce_wpml->settings['dismiss_non_default_language_warning'] ) ) || !empty($woocommerce_wpml->dependencies->xml_config_errors) || !empty($miss_slug_lang) ): ?>
    <div class="wcml-section">
        <div class="wcml-section-header">
            <h3>
                <?php _e('Configuration warnings', 'woocommerce-multilingual'); ?>
                <i class="icon-question-sign" data-tip="<?php _e('Reporting miscelaneous configuration issues that can make WooCommerce Multilingual not run normally', 'woocommerce-multilingual') ?>"></i>
            </h3>
        </div>

        <div class="wcml-section-content">

            <?php if( !empty( $miss_slug_lang ) ): ?>

                <?php if ( apply_filters( 'wpml_slug_translation_available', false ) ): ?>
                    <?php // Use new API for WPML >= 3.2.3 ?>
                    <p><i class="icon-warning-sign"></i><?php printf(__("Your product permalink base is not translated in %s. The urls for the translated products will not work. Go to the %sString Translation%s to translate.", 'woocommerce-multilingual'), '<b>'. implode(', ',$miss_slug_lang).'</b>' ,'<a href="' . admin_url('admin.php?page='.WPML_ST_FOLDER.'/menu/string-translation.php&search='.$prod_slug.'&context=WordPress&em=1') . '">', '</a>') ?> </p>
                <?php else:?>
                    <p><i class="icon-warning-sign"></i><?php printf(__("Your product permalink base is not translated in %s. The urls for the translated products will not work. Go to the %sString Translation%s to translate.", 'woocommerce-multilingual'), '<b>'. implode(', ',$miss_slug_lang).'</b>' ,'<a href="'.admin_url('admin.php?page='.WPML_ST_FOLDER.'/menu/string-translation.php&search='.$prod_slug.'&context=WordPress&em=1').'">', '</a>') ?> </p>
                <?php endif;?>

            <?php endif;?>

            <?php if(!WPML_SUPPORT_STRINGS_IN_DIFF_LANG && $default_language != 'en'): ?>

                <?php if($sitepress_settings['st']['strings_language'] != 'en'): ?>
                    <p><i class="icon-warning-sign"></i><strong><?php _e('Attention required: probable problem with URLs in different languages', 'woocommerce-multilingual') ?></strong></p>

                    <p><?php _e("Your site's default language is not English and the strings language is also not English. This may lead to problems with your site's URLs in different languages.", 'woocommerce-multilingual') ?></p>

                    <ul>
                        <li>&raquo;&nbsp;<?php _e('Change the strings language to English', 'woocommerce-multilingual') ?></li>
                        <li>&raquo;&nbsp;<?php _e('Re-scan strings', 'woocommerce-multilingual') ?></li>
                    </ul>

                    <p class="submit">
                        <input type="hidden" id="wcml_fix_strings_language_nonce" value="<?php echo wp_create_nonce('wcml_fix_strings_language') ?>" />
                        <input id="wcml_fix_strings_language" type="button" class="button-primary" value="<?php esc_attr_e('Run fix', 'woocommerce-multilingual') ?>" />
                    </p>

                    <p><?php printf(__("Please review the %sguide for running WooCommerce multilingual with default language other than English%s.", 'woocommerce-multilingual'), '<a href="http://wpml.org/?page_id=355545">', '</a>') ?> </p>

                <?php elseif( empty( $woocommerce_wpml->settings['dismiss_non_default_language_warning'] ) ): ?>

                    <p><?php _e("Your site's default language is not English. There are some settings that require careful attention.", 'woocommerce-multilingual') ?> </p>
                    <p><?php printf(__("Please review the %sguide for running WooCommerce multilingual with default language other than English%s.", 'woocommerce-multilingual'), '<a href="http://wpml.org/?page_id=355545">', '</a>') ?> </p>

                <?php endif; ?>

                <?php if($sitepress_settings['st']['strings_language'] == 'en' && empty( $woocommerce_wpml->settings['dismiss_non_default_language_warning'] )): ?>
                    <p class="submit">
                        <input id="wcml_dimiss_non_default_language_warning" type="button" class="button-primary" value="<?php esc_attr_e('Dismiss', 'woocommerce-multilingual') ?>" />
                        <?php wp_nonce_field('wcml_settings', 'wcml_settings_nonce'); ?>
                    </p>
                <?php endif; ?>

            <?php endif; ?>

            <?php if(!empty($woocommerce_wpml->dependencies->xml_config_errors)): ?>
                <p><i class="icon-warning-sign"></i>
                    <strong><?php _e('Some settings from the WooCommerce Multilingual wpml-config.xml file have been overwritten.', 'woocommerce-multilingual'); ?></strong>
                </p>
                <p><?php printf(__('You should check WPML configuration files added by other plugins or manual settings on the %sMultilingual Content Setup%s section.', 'woocommerce-multilingual'),
                        '<a href="' . admin_url('admin.php?page=' . WPML_TM_FOLDER . '/menu/main.php&sm=mcsetup') . '">' , '</a>')  ?>
                </p>
                <ul>
                    <?php foreach($woocommerce_wpml->dependencies->xml_config_errors as $error): ?>
                        <li><?php echo $error ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

    </div>
<?php endif; ?>


<div class="wcml-section">

    <div class="wcml-section-header">
        <h3>
            <?php _e('WooCommerce Store Pages', 'woocommerce-multilingual'); ?>
            <i class="icon-question-sign" data-tip="<?php _e('To run a multilingual e-commerce site, you need to have the WooCommerce shop pages translated in all the site\'s languages. Once all the pages are installed you can add the translations for them from this menu.', 'woocommerce-multilingual') ?>"></i>
        </h3>
    </div>

    <div class="wcml-section-content">

        <?php $miss_lang = $woocommerce_wpml->store->get_missing_store_pages(); ?>
        <?php if($miss_lang == 'non_exist'): ?>
            <ul>
                <li>
                    <i class="icon-warning-sign"></i><span><?php _e("One or more WooCommerce pages have not been created.", 'woocommerce-multilingual'); ?></span>
                </li>
                <li><a href="<?php echo admin_url('admin.php?page=wc-status&tab=tools'); ?>"><?php _e('Install WooCommerce Pages') ?></a></li>
            </ul>
        <?php elseif($miss_lang): ?>
            <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
                <?php wp_nonce_field('create_pages', 'wcml_nonce'); ?>
                <input type="hidden" name="create_missing_pages" value="1"/>
                <div class="wcml_miss_lang">
                    <p><i class="icon-warning-sign"></i></p>
                    <?php
                    if(isset($miss_lang['codes'])): ?>
                        </p>
                        <?php
                        if(count($miss_lang['codes']) == 1){
                            _e("WooCommerce store pages don't exist for this language:", 'woocommerce-multilingual');
                        }else{
                            _e("WooCommerce store pages don't exist for these languages:", 'woocommerce-multilingual');
                        }
                        ?>
                        </p>
                    <?php endif; ?>

                    <?php
                    if(isset($miss_lang['lang'])): ?>
                        <p>
                            <strong><?php echo $miss_lang['lang'] ?></strong>
                            <input class="button" type="submit" name="create_pages" value="<?php esc_attr(_e('Create missing translations.', 'woocommerce-multilingual')) ?>"  />
                        </p>
                    <?php endif; ?>

                    <?php
                    if(isset($miss_lang['in_progress'])): ?>
                        <p>
                            <?php _e("These pages are currently being translated by translators via WPML: ", 'woocommerce-multilingual'); ?>
                            <strong><?php echo $miss_lang['in_progress'] ?></strong>
                        </p>
                    <?php endif; ?>

                    <a id="wcmp_hide" class="wcmp_lang_hide" href="javascript:void(0);"><?php _e('Hide this message', 'woocommerce-multilingual') ?></a>
                </div>
                <p>
                    <a id="wcmp_show" class="none" href="javascript:void(0);"><?php _e('Show details about missing translations', 'woocommerce-multilingual') ?></a>
                </p>
            </form>
        <?php else: ?>
            <p>
                <i class="icon-ok"></i><span><?php _e("WooCommerce store pages are translated to all the site's languages.", 'woocommerce-multilingual'); ?></span>
            </p>
        <?php endif; ?>

    </div> <!-- .wcml-section-content -->

</div> <!-- .wcml-section -->

<div class="wcml-section">

    <div class="wcml-section-header">
        <h3>
            <?php _e('Taxonomies missing translations', 'woocommerce-multilingual'); ?>
            <i class="icon-question-sign" data-tip="<?php esc_attr_e('To run a fully translated site, you should translate all taxonomy terms. Some store elements, such as variations, depend on taxonomy translation.', 'woocommerce-multilingual') ?>"></i>
        </h3>
    </div>

    <div class="wcml-section-content js-tax-translation">
        <input type="hidden" id="wcml_ingore_taxonomy_translation_nonce" value="<?php echo wp_create_nonce('wcml_ingore_taxonomy_translation_nonce') ?>" />
        <?php
        global $wp_taxonomies;
        $taxonomies = array();

        //don't use get_taxonomies for product, because when one more post type registered for product taxonomy functions returned taxonomies only for product type
        foreach($wp_taxonomies as $key=>$taxonomy){
            if((in_array('product',$taxonomy->object_type) || in_array('product_variation',$taxonomy->object_type) ) && !in_array($key,$taxonomies)){
                $taxonomies[] = $key;
            }
        }

        ?>

        <ul>
            <?php
            $no_tax_to_update = true;
            foreach($taxonomies as $taxonomy): ?>
                <?php if($taxonomy == 'product_type' || WCML_Terms::get_untranslated_terms_number($taxonomy) == 0){
                    continue;
                }else{
                    $no_tax_to_update = false;
                }?>
                <li class="js-tax-translation-<?php echo $taxonomy ?>">
                    <?php if($untranslated = WCML_Terms::get_untranslated_terms_number($taxonomy)): ?>
                        <?php if(WCML_Terms::is_fully_translated($taxonomy)): // covers the 'ignore' case' ?>
                            <i class="icon-ok"></i> <?php printf(__('%s do not require translation.', 'woocommerce-multilingual'), get_taxonomy($taxonomy)->labels->name); ?>
                            <div class="actions">
                                <a href="#unignore-<?php echo $taxonomy ?>" title="<?php esc_attr_e('This taxonomy requires translation.', 'woocommerce-multilingual') ?>"><?php _e('Change', 'woocommerce-multilingual') ?></a>
                            </div>
                        <?php else: ?>
                            <i class="icon-warning-sign"></i> <?php printf(__('Some %s are missing translations (%d translations missing).', 'woocommerce-multilingual'), get_taxonomy($taxonomy)->labels->name, $untranslated); ?>
                            <div class="actions">
                                <a href="<?php echo admin_url('admin.php?page=wpml-wcml&tab=' . $taxonomy) ?>"><?php _e('Translate now', 'woocommerce-multilingual') ?></a> |
                                <a href="#ignore-<?php echo $taxonomy ?>" title="<?php esc_attr_e('This taxonomy does not require translation.', 'woocommerce-multilingual') ?>"><?php _e('Ignore', 'woocommerce-multilingual') ?></a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <i class="icon-ok"></i> <?php printf(__('All %s are translated.', 'woocommerce-multilingual'), get_taxonomy($taxonomy)->labels->name); ?>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
            <?php if($no_tax_to_update): ?>
                <li><i class="icon-ok"></i> <?php _e('Right now, there are no taxonomy terms needing translation.', 'woocommerce-multilingual'); ?></li>
            <?php endif; ?>
        </ul>


    </div>

</div>

<div class="wcml-section">
    <div class="wcml-section-header">
        <h3>
            <?php _e('Product Translation Interface', 'woocommerce-multilingual'); ?>
            <i class="icon-question-sign" data-tip="<?php _e('The recommended way to translate products is using the products translation table in the WooCommerce Multilingual admin. Choose to go to the native WooCommerce interface, if your products include custom sections that require direct access.', 'woocommerce-multilingual') ?>"></i>
        </h3>
    </div>
    <div class="wcml-section-content">
        <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
            <?php wp_nonce_field('wcml_trsl_interface_table', 'wcml_nonce'); ?>
            <ul>
                <li>
                    <p><?php _e('Choose what to do when clicking on the translation controls for products:', 'woocommerce-multilingual'); ?></p>
                </li>
                <li>
                    <input type="radio" name="trnsl_interface" value="1" <?php echo $woocommerce_wpml->settings['trnsl_interface'] == '1'?'checked':''; ?> id="wcml_trsl_interface_wcml" />
                    <label for="wcml_trsl_interface_wcml"><?php _e('Go to the product translation table in WooCommerce Multilingual', 'woocommerce-multilingual'); ?></label>
                </li>
                <li>
                    <input type="radio" name="trnsl_interface" value="0" <?php echo $woocommerce_wpml->settings['trnsl_interface'] == '0'?'checked':''; ?> id="wcml_trsl_interface_native" />
                    <label for="wcml_trsl_interface_native"><?php _e('Go to the native WooCommerce product editing screen', 'woocommerce-multilingual'); ?></label>
                </li>
            </ul>
            <p class="button-wrap">
                <input type='submit' name="wcml_trsl_interface_table" value='<?php esc_attr(_e('Save', 'woocommerce-multilingual')); ?>' class='button-secondary' />
            </p>
        </form>
    </div> <!-- .wcml-section-content -->

</div> <!-- .wcml-section -->

<div class="wcml-section">

    <div class="wcml-section-header">
        <h3>
            <?php _e('Products synchronization', 'woocommerce-multilingual'); ?>
            <i class="icon-question-sign" data-tip="<?php _e('Configure specific product properties that should be synced to translations.', 'woocommerce-multilingual') ?>"></i>
        </h3>
    </div>

    <div class="wcml-section-content">
        <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
            <?php wp_nonce_field('wcml_products_sync_prop', 'wcml_nonce'); ?>
            <ul>
                <li>
                    <input type="checkbox" name="products_sync_date" value="1" <?php echo checked(1, $woocommerce_wpml->settings['products_sync_date']) ?> id="wcml_products_sync_date" />
                    <label for="wcml_products_sync_date"><?php _e('Sync publishing date for translated products.', 'woocommerce-multilingual'); ?></label>
                </li>
                <li>
                    <input type="checkbox" name="products_sync_order" value="1" <?php echo checked(1, $woocommerce_wpml->settings['products_sync_order']) ?> id="wcml_products_sync_order" />
                    <label for="wcml_products_sync_order"><?php _e('Sync products and product taxonomies order.', 'woocommerce-multilingual'); ?></label>
                </li>
            </ul>
            <p class="button-wrap">
                <input type='submit' name="wcml_products_sync_prop" value='<?php esc_attr(_e('Save', 'woocommerce-multilingual')); ?>' class='button-secondary' />
            </p>
        </form>
    </div>

</div>


<div class="wcml-section">

    <div class="wcml-section-header">
        <h3>
            <?php _e('File Paths Synchronization ', 'woocommerce-multilingual'); ?>
            <i class="icon-question-sign" data-tip="<?php _e('If you are using downloadable products, you can choose to have their paths synchronized, or seperate for each language.', 'woocommerce-multilingual') ?>"></i>
        </h3>
    </div>

    <div class="wcml-section-content">
        <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
            <?php wp_nonce_field('wcml_file_path_options_table', 'wcml_nonce'); ?>
            <ul>
                <li>
                    <input type="radio" name="wcml_file_path_sync" value="1" <?php echo $woocommerce_wpml->settings['file_path_sync'] == '1'?'checked':''; ?> id="wcml_file_path_sync_auto" />
                    <label for="wcml_file_path_sync_auto"><?php _e('Use the same file paths in all languages', 'woocommerce-multilingual'); ?></label>
                </li>
                <li>
                    <input type="radio" name="wcml_file_path_sync" value="0" <?php echo $woocommerce_wpml->settings['file_path_sync'] == '0'?'checked':''; ?> id="wcml_file_path_sync_self" />
                    <label for="wcml_file_path_sync_self"><?php _e('Different file paths for each language', 'woocommerce-multilingual'); ?></label>
                </li>
            </ul>
            <p class="button-wrap">
                <input type='submit' name="wcml_file_path_options_table" value='<?php esc_attr(_e('Save', 'woocommerce-multilingual')); ?>' class='button-secondary' />
            </p>
        </form>

    </div> <!-- .wcml-section-content -->

</div> <!-- .wcml-section -->


<div class="wcml-section">

<?php
$wc_currencies  = get_woocommerce_currencies();
$wc_currency    = get_option('woocommerce_currency');

$active_languages = $sitepress->get_active_languages();

switch(get_option('woocommerce_currency_pos')){
    case 'left':        $positioned_price = sprintf('%s99.99', get_woocommerce_currency_symbol($wc_currency)); break;
    case 'right':       $positioned_price = sprintf('99.99%s', get_woocommerce_currency_symbol($wc_currency)); break;
    case 'left_space':  $positioned_price = sprintf('%s 99.99', get_woocommerce_currency_symbol($wc_currency)); break;
    case 'right_space': $positioned_price = sprintf('99.99 %s', get_woocommerce_currency_symbol($wc_currency)); break;
}

?>

<div class="wcml-section-header">
    <h3>
        <?php _e('Manage Currencies', 'woocommerce-multilingual'); ?>
        <i class="icon-question-sign" data-tip="<?php _e('This will let you enable the multi-currency mode where users can see prices according to their currency preference and configured exchange rate.', 'woocommerce-multilingual') ?>"></i>
    </h3>
</div>
<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" id="wcml_mc_options">
<div class="wcml-section-content">
<?php wp_nonce_field('wcml_mc_options', 'wcml_nonce'); ?>

<ul id="wcml_mc_options_block">

    <li>
        <ul id="multi_currency_option_select">
            <li>
                <input type="radio" name="multi_currency" id="multi_currency_disabled" value="<?php echo WCML_MULTI_CURRENCIES_DISABLED ?>" <?php
                echo checked($woocommerce_wpml->settings['enable_multi_currency'], WCML_MULTI_CURRENCIES_DISABLED) ?><?php
                if(empty($wc_currency)) echo ' disabled="disabled"' ?> />
                <label for="multi_currency_disabled"><?php _e("No multi-currency.", 'woocommerce-multilingual'); ?></label>
            </li>
            <li>
                <input type="radio" name="multi_currency" id="multi_currency_independent" value="<?php echo WCML_MULTI_CURRENCIES_INDEPENDENT ?>" <?php
                echo checked($woocommerce_wpml->settings['enable_multi_currency'], WCML_MULTI_CURRENCIES_INDEPENDENT) ?><?php
                if(empty($wc_currency)) echo ' disabled="disabled"' ?> />
                <label for="multi_currency_independent">
                    <?php _e("Multiple currencies, independent of languages.", 'woocommerce-multilingual'); ?>
                    &nbsp;
                    <a href=" <?php echo $woocommerce_wpml->generate_tracking_link('http://wpml.org/documentation/related-projects/woocommerce-multilingual/multi-currency-support-woocommerce/','multi-currency-support-woocommerce','documentation') ?>"><?php _e('Learn more', 'wpl-wcml') ?></a>.
                </label>
            </li>
        </ul>
    </li>
</ul>

<?php if(empty($wc_currency)): ?>
<p>
    <i class="icon-warning-sign"></i>
    <?php printf(__('The multi-currency mode cannot be enabled as a specific currency was not set. Go to the %sWooCommerce settings%s page and select the default currency for your store.',
        'woocommerce-multilingual'), '<a href="' . admin_url('admin.php?page=wc-settings') . '">', '</a>') ?>
</p>
<?php endif; ?>

<?php if(!empty($wc_currency)): ?>
<div id="multi-currency-per-language-details" <?php if ( $woocommerce_wpml->settings['enable_multi_currency'] != WCML_MULTI_CURRENCIES_INDEPENDENT ):?>style="display:none"<?php endif;?>>

    <div class="currencies-table-content">

        <p>
            <?php printf(__("Your store's base currency is %s (%s). To change it, go to the %s page.", 'woocommerce-multilingual'), $wc_currencies[$wc_currency],get_woocommerce_currency_symbol($wc_currency),'<a href="'. admin_url(sprintf('admin.php?page=%s&tab=general', 'wc-settings')) .'">WooCommerce settings</a>'); ?>
        </p>
        <input type="hidden" id="update_currency_lang_nonce" value="<?php echo wp_create_nonce('wcml_update_currency_lang'); ?>"/>
        <table class="widefat currency_table" id="currency-table">
            <thead>
            <tr>
                <th><?php _e('Currency', 'woocommerce-multilingual'); ?></th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td class="currency_code">
                    <span class="code_val"><?php echo $wc_currencies[$wc_currency]; ?><?php printf(__(' (%s)', 'woocommerce-multilingual'), $positioned_price); ?></span>
                    <div class="currency_value"><span><?php _e( 'default', 'woocommerce-multilingual' ); ?></span></div>
                </td>
                <td class="currency-actions"></td>

            </tr>
            <?php
            unset($wc_currencies[$wc_currency]);
            $currencies = $woocommerce_wpml->multi_currency_support->get_currencies();
            foreach ($currencies as $code => $currency) :
                switch($currency['position']){
                    case 'left': $positioned_price = sprintf('%s99.99', get_woocommerce_currency_symbol($code)); break;
                    case 'right': $positioned_price = sprintf('99.99%s', get_woocommerce_currency_symbol($code)); break;
                    case 'left_space': $positioned_price = sprintf('%s 99.99', get_woocommerce_currency_symbol($code)); break;
                    case 'right_space': $positioned_price = sprintf('99.99 %s', get_woocommerce_currency_symbol($code)); break;
                }
                ?>
                <tr id="currency_row_<?php echo $code ?>">
                    <td class="currency_code">
                        <?php include WCML_PLUGIN_PATH . '/menu/sub/custom-currency-options.php'; ?>
                        <span class="code_val"><?php echo $wc_currencies[$code]; ?><?php printf(__(' (%s)', 'woocommerce-multilingual'), $positioned_price); ?></span>
                        <div class="currency_value">
                            <span><?php printf('1 %s = %s %s', $wc_currency, $currency['rate'], $code); ?></span>
                        </div>

                    </td>

                    <td class="currency-actions">
                        <div class="currency_action_update">
                            <a href="javascript:void(0);" title="<?php esc_attr(_e('Edit', 'woocommerce-multilingual')); ?>" class="edit_currency" data-currency="<?php echo $code ?>">
                                <i class="icon-edit" title="<?php esc_attr(_e('Edit', 'woocommerce-multilingual')); ?>"></i>
                            </a>
                            <i class="icon-ok-circle save_currency"></i>
                        </div>
                        <div class="currency_action_delete">
                            <a href="javascript:void(0);" title="<?php esc_attr(_e('Delete', 'woocommerce-multilingual')); ?>" class="delete_currency" data-currency="<?php echo $code ?>" >
                                <i class="icon-trash" title="<?php esc_attr(_e('Delete', 'woocommerce-multilingual')); ?>"></i>
                            </a>
                            <i class="icon-remove-circle cancel_currency"></i>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr class="default_currency">
                <td colspan="2">
                    <span class="cur_label"><?php _e('Default currency', 'woocommerce-multilingual'); ?></span>
                    <span class="inf_message"><?php _e('Switch to this currency when switching language in the front-end', 'woocommerce-multilingual'); ?></span>
                </td>
            </tr>
            </tbody>
        </table>

        <div class="currency_wrap">
            <div class="currency_inner">
                <table class="widefat currency_lang_table" id="currency-lang-table">
                    <thead>
                    <tr>
                        <?php foreach($active_languages as $language): ?>
                            <th>
                                <img src="<?php echo $sitepress->get_flag_url($language['code'] ) ?>" width="18" height="12" />
                            </th>
                        <?php endforeach; ?>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <?php foreach($active_languages as $language): ?>
                            <td class="currency_languages">
                                <div class="wcml_onof_buttons">
                                    <ul>
                                        <li <?php echo $woocommerce_wpml->settings['currency_options'][$wc_currency]['languages'][$language['code']] == 0 ?'class="on"':''; ?> ><a class="off_btn" href="javascript:void(0);" data-language="<?php echo $language['code']; ?>" data-currency="<?php echo $wc_currency; ?>" ><?php _e( 'OFF', 'woocommerce-multilingual' ); ?></a></li>
                                        <li <?php echo $woocommerce_wpml->settings['currency_options'][$wc_currency]['languages'][$language['code']] == 1 ?'class="on"':''; ?> ><a class="on_btn" href="javascript:void(0);" data-language="<?php echo $language['code']; ?>" data-currency="<?php echo $wc_currency ?>"><?php _e( 'ON', 'woocommerce-multilingual' ); ?></a></li>
                                    </ul>
                                </div>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php foreach ($currencies as $code => $currency) : ?>
                        <tr id="currency_row_langs_<?php echo $code ?>">
                            <?php foreach($active_languages as $language): ?>
                                <td class="currency_languages">
                                    <div class="wcml_onof_buttons">
                                        <ul>
                                            <li <?php echo $currency['languages'][$language['code']] == 0 ?'class="on"':''; ?> ><a class="off_btn" href="javascript:void(0);" data-language="<?php echo $language['code']; ?>" data-currency="<?php echo $code; ?>"><?php _e( 'OFF', 'woocommerce-multilingual' ); ?></a></li>
                                            <li <?php echo $currency['languages'][$language['code']] == 1 ?'class="on"':''; ?> ><a class="on_btn" href="javascript:void(0);" data-language="<?php echo $language['code']; ?>" data-currency="<?php echo $code; ?>"><?php _e( 'ON', 'woocommerce-multilingual' ); ?></a></li>
                                        </ul>
                                    </div>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="default_currency">
                        <?php foreach($active_languages as $language): ?>
                            <td class="currency_languages">
                                <select rel="<?php echo $language['code']; ?>">
                                    <option value="0" <?php selected('0', $woocommerce_wpml->settings['default_currencies'][$language['code']]); ?>><?php _e('Keep', 'woocommerce-multilingual'); ?></option>
                                    <?php if($woocommerce_wpml->settings['currency_options'][$wc_currency]['languages'][$language['code']] == 1): ?>
                                        <option value="<?php echo $wc_currency; ?>" <?php selected($wc_currency, $woocommerce_wpml->settings['default_currencies'][$language['code']]); ?>><?php echo $wc_currency; ?></option>
                                    <?php endif; ?>
                                    <?php foreach($currencies as $code2 => $currency2): ?>
                                        <?php if($woocommerce_wpml->settings['currency_options'][$code2]['languages'][$language['code']] == 1): ?>
                                            <option value="<?php echo $code2; ?>" <?php selected($code2, $woocommerce_wpml->settings['default_currencies'][$language['code']]); ?>><?php echo $code2; ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    </tbody>
                </table>
                <input type="hidden" id="wcml_update_default_currency_nonce" value="<?php echo wp_create_nonce('wcml_update_default_currency'); ?>"/>

            </div>
        </div>

        <?php // this is a template for scripts.js : jQuery('.wcml_add_currency button').click(function(); ?>
        <table class="hidden js-table-row-wrapper">
            <tr class="edit-mode js-table-row">
                <td class="currency_code" data-message="<?php _e( 'Please fill field', 'woocommerce-multilingual' ); ?>">
                    <span class="code_val"></span>
                    <select name="code" style="display:block">
                        <?php foreach($wc_currencies as $wc_code=>$currency_name): ?>
                            <?php if(empty($currencies[$wc_code])): ?>
                                <option value="<?php echo $wc_code; ?>"><?php echo $currency_name; ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <div class="currency_value" data-message="<?php _e( 'Only numeric', 'woocommerce-multilingual' ); ?>">
                                <span>
                                    <?php printf('1 %s = ',$wc_currency); ?>
                                    <span class="curr_val"></span>
                                    <input type="text" value="" style="display: inline-block;">
                                    <span class="curr_val_code"></span>
                                </span>
                    </div>
                </td>
                <td class="currency-actions">
                    <div class="currency_action_update">
                        <a href="javascript:void(0);" title="Edit" class="edit_currency" style="display:none">
                            <i class="icon-edit" title="Edit"></i>
                        </a>
                        <i class="icon-ok-circle save_currency" style="display:inline"></i>
                    </div>
                    <div class="currency_action_delete">
                        <a href="javascript:void(0);" title="Delete" class="delete_currency" data-currency="" style="display:none">
                            <i class="icon-trash" alt="Delete"></i>
                        </a>
                        <i class="icon-remove-circle cancel_currency" style="display:inline"></i>
                    </div>
                </td>
            </tr>
        </table>

        <table class="hidden js-currency_lang_table">
            <tr>
                <?php foreach($active_languages as $language): ?>
                    <td class="currency_languages">
                        <div class="wcml_onof_buttons">
                            <ul>
                                <li><a class="off_btn" href="javascript:void(0);" data-language="<?php echo $language['code']; ?>"><?php _e( 'OFF', 'woocommerce-multilingual' ); ?></a></li>
                                <li class="on"><a class="on_btn" href="javascript:void(0);" data-language="<?php echo $language['code']; ?>"><?php _e( 'ON', 'woocommerce-multilingual' ); ?></a></li>
                            </ul>
                        </div>
                    </td>
                <?php endforeach; ?>
            </tr>
        </table>

        <input type="hidden" value="<?php echo WCML_PLUGIN_URL; ?>" class="wcml_plugin_url" />
        <input type="hidden" id="new_currency_nonce" value="<?php echo wp_create_nonce('wcml_new_currency'); ?>" />
        <input type="hidden" id="del_currency_nonce" value="<?php echo wp_create_nonce('wcml_delete_currency'); ?>" />
        <input type="hidden" id="currencies_list_nonce" value="<?php echo wp_create_nonce('wcml_currencies_list'); ?>" />
    </div>

    <p class="wcml_add_currency button-wrap">
        <button type="button" class="button-secondary">
            <i class="icon-plus"></i>
            <?php _e('Add currency', 'woocommerce-multilingual'); ?>
        </button>
    </p>

    <?php // backward compatibility ?>
    <?php
    $posts = $wpdb->get_results($wpdb->prepare("
                        SELECT m.post_id, m.meta_value, p.post_title
                        FROM {$wpdb->postmeta} m
                            JOIN {$wpdb->posts} p ON p.ID = m.post_id
                            JOIN {$wpdb->prefix}icl_translations t ON t.element_id = p.ID AND t.element_type IN ('post_product', 'post_product_variation')
                        WHERE m.meta_key='_custom_conversion_rate' AND t.language_code = %s
                        ORDER BY m.post_id desc
                    ", $default_language));

    if($posts){
        echo "<script>
                        function wcml_remove_custom_rates(post_id, el){
                            jQuery.ajax({
                                type: 'post',
                                dataType: 'json',
                                url: ajaxurl,
                                data: {action: 'legacy_remove_custom_rates', 'post_id': post_id},
                                success: function(){
                                    el.parent().parent().fadeOut(function(){ jQuery(this).remove()});
                                }
                            })
                            return false;
                        }";
        echo '</script>';
        echo '<p>' . __('Products using custom currency rates as they were migrated from the previous versions - option to support different prices per language', 'woocommerce-multilingual') . '</p>';
        echo '<form method="post" id="wcml_custom_exchange_rates">';
        echo '<input type="hidden" name="action" value="legacy_update_custom_rates">';
        echo '<table class="widefat currency_table" >';
        echo '<thead>';
        echo '<tr>';
        echo '<th rowspan="2">' . __('Product', 'woocommerce-multilingual') . '</th>';
        echo '<th colspan="' . count($currencies) . '">_price</th>';
        echo '<th colspan="' . count($currencies) . '">_sale_price</th>';
        echo '<th rowspan="2">&nbsp;</th>';
        echo '</tr>';
        echo '<tr>';
        foreach($currencies as $code => $currency){
            echo '<th>' . $code . '</th>';
        }
        foreach($currencies as $code => $currency){
            echo '<th>' . $code . '</th>';
        }
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        foreach($posts as $post){
            $rates = unserialize($post->meta_value);
            echo '<tr>';
            echo '<td><a href="' . get_edit_post_link($post->post_id) . '">' . apply_filters('the_title', $post->post_title) . '</a></td>';

            foreach($currencies as $code => $currency){
                echo '<td>';
                if(isset($rates['_price'][$code])){
                    echo '<input name="posts[' . $post->post_id . '][_price][' . $code . ']" size="3" value="' . round($rates['_price'][$code],3) . '">';
                }else{
                    _e('n/a', 'woocommerce-multilingual');
                }
                echo '</td>';
            }

            foreach($currencies as $code => $currency){
                echo '<td>';
                if(isset($rates['_sale_price'][$code])){
                    echo '<input name="posts[' . $post->post_id . '][_sale_price][' . $code . ']" size="3" value="' . round($rates['_sale_price'][$code],3) . '">';
                }else{
                    _e('n/a', 'woocommerce-multilingual');
                }
                echo '</td>';
            }

            echo '<td align="right"><a href="#" onclick=" if(confirm(\'' . esc_js(__('Are you sure?', 'woocommerce-multilingual') ) . '\')) wcml_remove_custom_rates(' . $post->post_id . ', jQuery(this));return false;"><i class="icon-trash" title="' . __('Delete', 'woocommerce-multilingual') . '"></i></a></td>';
            echo '<tr>';

        }
        echo '</tbody>';
        echo '</table>';
        echo '<p class="button-wrap"><input class="button-secondary" type="submit" value="' . esc_attr__('Update', 'woocommerce-multilingual') . '" /></p>';
        echo '</form>';


    }
    ?>
    <ul id="display_custom_prices_select">
        <li>
            <input type="checkbox" name="display_custom_prices" id="display_custom_prices" value="1" <?php echo checked( 1, $woocommerce_wpml->settings['display_custom_prices']) ?> >
            <label for="display_custom_prices"><?php _e('Show only products with custom prices in secondary currencies', 'woocommerce-multilingual'); ?></label>
            <i class="icon-question-sign" data-tip="<?php _e('When this option is on, when you switch to a secondary currency on the front end, only the products with custom prices in that currency are being displayed. Products with prices determined based on the exchange rate are hidden.', 'woocommerce-multilingual') ?>"></i>
        </li>
    </ul>
    <p class="button-wrap general_option_btn">
        <input type='submit' name="wcml_mc_options" value='<?php _e('Save', 'woocommerce-multilingual'); ?>' class='button-secondary' />
        <?php wp_nonce_field('wcml_mc_options', 'wcml_mc_options_nonce'); ?>
    </p>

</div>

<?php endif; //if(!empty($wc_currency)) ?>

</div> <!-- .wcml-section-content -->

<?php if(!empty($wc_currency)): ?>
<div class="wcml-section">
    <?php include WCML_PLUGIN_PATH . '/menu/sub/currency-switcher-options.php'; ?>
</div>
<?php endif ?>

</form>
</div> <!-- .wcml-section -->
<input type="hidden" id="wcml_warn_message" value="<?php esc_attr_e('The changes you made will be lost if you navigate away from this page.', 'woocommerce-multilingual');?>"/>
<input type="hidden" id="wcml_warn_disable_language_massage" value="<?php esc_attr_e('At least one currency must be enabled for this language!', 'woocommerce-multilingual');?>"/>
<div class="troubleshoot_link_block">
    <a href="<?php echo admin_url('admin.php?page='.basename(WCML_PLUGIN_PATH) .'/menu/sub/troubleshooting.php'); ?>"><?php  _e('Troubleshooting', 'woocommerce-multilingual'); ?></a>
</div>
<div class="clear"></div>
