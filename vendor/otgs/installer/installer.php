<?php
define( 'WP_INSTALLER_VERSION', '1.8.8' );

include_once dirname( __FILE__ ) . '/includes/functions-core.php';
include_once dirname( __FILE__ ) . '/includes/class-wp-installer.php';

include_once WP_Installer()->plugin_path() . '/includes/class-wp-installer-api.php';
include_once WP_Installer()->plugin_path() . '/includes/class-translation-service-info.php';
include_once WP_Installer()->plugin_path() . '/includes/class-installer-dependencies.php';
include_once WP_Installer()->plugin_path() . '/includes/class-wp-installer-channels.php';

include_once WP_Installer()->plugin_path() . '/includes/class-otgs-installer-filename-hooks.php';
include_once WP_Installer()->plugin_path() . '/includes/class-otgs-installer-php-functions.php';

include_once WP_Installer()->plugin_path() . '/includes/functions-templates.php';

// Initialization
WP_Installer();
WP_Installer_Channels();

$filename_hooks = new OTGS_Installer_Filename_Hooks( new OTGS_Installer_PHP_Functions() );
$filename_hooks->add_hooks();