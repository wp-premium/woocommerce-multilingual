<?php
define( 'WP_INSTALLER_VERSION', '1.8.2' );

include_once dirname( __FILE__ ) . '/includes/functions-core.php';
include_once dirname( __FILE__ ) . '/includes/class-wp-installer.php';

include_once WP_Installer()->plugin_path() . '/includes/class-wp-installer-api.php';
include_once WP_Installer()->plugin_path() . '/includes/class-translation-service-info.php';
include_once WP_Installer()->plugin_path() . '/includes/class-installer-dependencies.php';
include_once WP_Installer()->plugin_path() . '/includes/class-wp-installer-channels.php';

include_once WP_Installer()->plugin_path() . '/includes/functions-templates.php';

// Initialization
WP_Installer();
WP_Installer_Channels();


