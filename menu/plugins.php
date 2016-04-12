<?php global $woocommerce_wpml, $sitepress; ?>
<div class="wrap">
    <div id="icon-wpml" class="icon32"><br /></div>
    <h2><?php _e('WooCommerce Multilingual', 'woocommerce-multilingual') ?></h2>
    <a class="nav-tab nav-tab-active" href="<?php echo admin_url('admin.php?page=wpml-wcml'); ?>"><?php _e('Required plugins', 'woocommerce-multilingual') ?></a>
    <div class="wcml_wrap">
        <div class="wcml-section">
            <div class="wcml-section-header">
                <h3>
                    <?php _e('Plugins Status', 'woocommerce-multilingual'); ?>
                    <i class="icon-question-signp" data-tip="<?php _e('WooCommerce Multilingual depends on several plugins to work. If any required plugin is missing, you should install and activate it.', 'woocommerce-multilingual') ?>"></i>
                </h3>
            </div>
            <div class="wcml-section-content">
                <ul>
                     <?php if (defined('ICL_SITEPRESS_VERSION') && version_compare(ICL_SITEPRESS_VERSION, '2.0.5', '<')) : ?>
                        <li><i class="icon-warning-sign"></i> <?php printf(__('WooCommerce Multilingual is enabled but not effective. It is not compatible with  <a href="%s">WPML</a> versions prior 2.0.5.', 'woocommerce-multilingual'), $woocommerce_wpml->generate_tracking_link('http://wpml.org/')); ?> <a href="<?php echo $woocommerce_wpml->generate_tracking_link('http://wpml.org/shop/account/',false,'account') ?>" target="_blank"><?php _e('Update WPML', 'woocommerce-multilingual'); ?></a></li>
                    <?php elseif (defined('ICL_SITEPRESS_VERSION')) : ?>
                        <li><i class="icon-ok"></i> <?php printf(__('%s plugin is installed and active.', 'woocommerce-multilingual'), '<strong>WPML</strong>'); ?></li>
                        <?php if($sitepress->setup()): ?>
                        <li><i class="icon-ok"></i> <?php printf(__('%s is set up.', 'woocommerce-multilingual'), '<strong>WPML</strong>'); ?></li>
                        <?php else: ?>
                        <li><i class="icon-warning-sign"></i> <?php printf(__('%s is not set up.', 'woocommerce-multilingual'), '<strong>WPML</strong>'); ?></li>
                        <?php endif; ?>
                    <?php else : ?>
                        <li><i class="icon-warning-sign"></i> <?php printf(__('%s plugin is either not installed or not active.', 'woocommerce-multilingual'), '<strong>WPML</strong>'); ?> <a href="<?php echo $woocommerce_wpml->generate_tracking_link('http://wpml.org/') ?>" target="_blank"><?php _e('Get WPML', 'woocommerce-multilingual'); ?></a></li>
                    <?php endif; ?>
                    <?php if (defined('WPML_MEDIA_VERSION')) : ?>
                        <li><i class="icon-ok"></i> <?php printf(__('%s plugin is installed and active.', 'woocommerce-multilingual'), '<strong>WPML Media</strong>'); ?></li>
                    <?php else : ?>
                        <li><i class="icon-warning-sign"></i> <?php printf(__('%s plugin is either not installed or not active.', 'woocommerce-multilingual'), '<strong>WPML Media</strong>'); ?> <a href="<?php echo $woocommerce_wpml->generate_tracking_link('http://wpml.org/') ?>" target="_blank"><?php _e('Get WPML Media', 'woocommerce-multilingual'); ?></a></li>
                    <?php endif; ?>
                    <?php if (defined('WPML_TM_VERSION')) : ?>
                        <li><i class="icon-ok"></i> <?php printf(__('%s plugin is installed and active.', 'woocommerce-multilingual'), '<strong>WPML Translation Management</strong>'); ?></li>
                    <?php else : ?>
                        <li><i class="icon-warning-sign"></i> <?php printf(__('%s plugin is either not installed or not active.', 'woocommerce-multilingual'), '<strong>WPML Translation Management</strong>'); ?> <a href="<?php echo $woocommerce_wpml->generate_tracking_link('http://wpml.org/') ?>" target="_blank"><?php _e('Get WPML Translation Management', 'woocommerce-multilingual'); ?></a></li>
                    <?php endif; ?>
                    <?php if (defined('WPML_ST_VERSION')) : ?>
                        <li><i class="icon-ok"></i> <?php printf(__('%s plugin is installed and active.', 'woocommerce-multilingual'), '<strong>WPML String Translation</strong>'); ?></li>
                    <?php else : ?>
                        <li><i class="icon-warning-sign"></i> <?php printf(__('%s plugin is either not installed or not active.', 'woocommerce-multilingual'), '<strong>WPML String Translation</strong>'); ?> <a href="<?php echo $woocommerce_wpml->generate_tracking_link('http://wpml.org/') ?>" target="_blank"><?php _e('Get WPML String Translation', 'woocommerce-multilingual'); ?></a></li>
                    <?php endif; ?>
                    <?php
                    global $woocommerce;
                    if (class_exists('Woocommerce') && $woocommerce && isset($woocommerce->version) && version_compare($woocommerce->version, '2.0', '<')) :
                        ?>
                        <li><i class="icon-warning-sign"></i> <?php printf(__('%1$s  is installed, but with incorrect version. You need %1$s %2$s or higher. ', 'woocommerce-multilingual'), '<strong>WooCommerce</strong>', '2.0'); ?> <a href="http://wordpress.org/extend/plugins/woocommerce/" target="_blank"><?php _e('Download WooCommerce', 'woocommerce-multilingual'); ?></a></li>
                    <?php elseif (class_exists('Woocommerce')) : ?>
                        <li><i class="icon-ok"></i> <?php printf(__('%s plugin is installed and active.', 'woocommerce-multilingual'), '<strong>WooCommerce</strong>'); ?></li>
                    <?php else : ?>
                        <li><i class="icon-warning-sign"></i> <?php printf(__('%s plugin is either not installed or not active.', 'woocommerce-multilingual'), '<strong>WooCommerce</strong>'); ?> <a href="http://wordpress.org/extend/plugins/woocommerce/" target="_blank"><?php _e('Download WooCommerce', 'woocommerce-multilingual'); ?></a></li>
                    <?php endif; ?>
                </ul>
            </div> <!-- .wcml-section-content -->

        </div> <!-- .wcml-section -->
    </div>

</div>

