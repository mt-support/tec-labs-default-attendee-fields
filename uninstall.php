<?php
/**
 * Uninstall script for Event Tickets Extension: Default Attendee Fields
 *
 * This file is called when the plugin is deleted via the WordPress admin.
 * It will remove all plugin settings if the user has enabled the
 * "Remove the extension settings on deletion" option.
 *
 * @package Tribe\Extensions\Default_Attendee_Fields
 * @since   1.2.0
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Only proceed if Tribe Common is available
if ( ! class_exists( 'Tribe__Settings_Manager' ) ) {
	return;
}

// Get the options prefix used by the plugin
$options_prefix = str_replace( '-', '_', 'tec-labs-default-attendee-fields' ) . '_';

// Get all tribe options
$tribe_options = Tribe__Settings_Manager::get_options();

if ( ! is_array( $tribe_options ) ) {
	return;
}

// Check if the remove_settings_on_delete option is enabled
$remove_settings_key = $options_prefix . 'remove_settings_on_delete';
$should_remove = isset( $tribe_options[ $remove_settings_key ] ) && $tribe_options[ $remove_settings_key ];

if ( ! $should_remove ) {
	return;
}

// Remove all plugin settings
foreach ( $tribe_options as $key => $value ) {
	if ( 0 === strpos( $key, $options_prefix ) ) {
		tribe_delete_option( $key );
	}
}
