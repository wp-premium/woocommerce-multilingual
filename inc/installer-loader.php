<?php

if( file_exists( WCML_PLUGIN_PATH . '/embedded/installer/loader.php' ) ){

    include WCML_PLUGIN_PATH . '/embedded/installer/loader.php' ;
    $args = array(
        'plugins_install_tab' => 1
    );
    WP_Installer_Setup( $wp_installer_instance, $args );

}