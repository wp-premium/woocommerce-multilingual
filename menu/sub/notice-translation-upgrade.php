<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

$notices = implode(', ', $notices);

?>

<div id="wcml_translations_message" class="message error">
    <p><?php printf( __( '<strong>WooCommerce Translation Available</strong> &#8211; Install or update your <code>%s</code> translations to version <code>%s</code>.', 'woocommerce-multilingual' ), $notices, WC_VERSION ); ?></p>

    <p>
        <?php if ( is_multisite() ) : ?>
            <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=wc-status&tab=tools&action=translation_upgrade' ), 'debug_action' ); ?>" class="button-primary"><?php _e( 'Update Translation', 'woocommerce-multilingual' ); ?></a>
        <?php else : ?>
            <a href="<?php echo wp_nonce_url( add_query_arg( array( 'action' => 'do-translation-upgrade' ), admin_url( 'update-core.php' ) ), 'upgrade-translations' ); ?>" class="button-primary"><?php _e( 'Update Translation', 'woocommerce-multilingual' ); ?></a>
        <?php endif; ?>
        <a href="javascript:void(0);" class="button"><?php _e( 'Hide This Message', 'woocommerce-multilingual' ); ?></a>
    </p>
</div>


<script type="text/javascript">
    jQuery(document).ready(function(){
        jQuery('#wcml_translations_message').on('click',function(){
            jQuery.ajax({
                type : "post",
                url : ajaxurl,
                data : {
                    action: "hide_wcml_translations_message",
                    wcml_nonce: "<?php echo wp_create_nonce('hide_wcml_translations_message'); ?>"
                },
                success: function(response) {
                    jQuery('#wcml_translations_message').remove();
                }
            });
        });

    });
</script>