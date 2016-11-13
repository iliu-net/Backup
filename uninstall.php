<?php

if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit;

// Load helper functions
require_once( 'functions.php' );

// Clear scheduled hook.
if ( wp_next_scheduled( 'backup_schedule' ) ) {
	wp_clear_scheduled_hook( 'backup_schedule' );
}

// Delete all files created by the plugin.
if ( defined( 'BACKUP_LOCAL_FOLDER' ) )
	$folder = BACKUP_LOCAL_FOLDER;
else {
	$options = is_multisite() ? get_site_option('backup_options') : get_option( 'backup_options' );
	$folder = $options['local_folder'];
}
$folder = absolute_path( $folder, ABSPATH );
if ( @file_exists( $folder . '/.backup' ) )
	delete_path( $folder, true );

// Delete options.
if (is_multisite()) {
  delete_site_option( 'backup_options' );
} else {
  delete_option( 'backup_options' );
}