<?php
/**
 * Plugin Name:       Event Tickets Extension: Default Attendee Fields
 * Plugin URI:        https://theeventscalendar.com/extensions/default-attendee-fields/
 * GitHub Plugin URI: https://github.com/mt-support/tec-labs-default-attendee-fields
 * Description:       Select a fieldset that will be added to all RSVPs and tickets when they are created. You can find the settings under Tickets > Settings > Attendee Registration tab > Default Attendee Fields section.
 * Version:           1.1.0
 * Author:            The Events Calendar
 * Author URI:        https://evnt.is/1971
 * License:           GPL version 3 or any later version
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       tec-labs-default-attendee-fields
 *
 *     This plugin is free software: you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation, either version 3 of the License, or
 *     any later version.
 *
 *     This plugin is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *     GNU General Public License for more details.
 */

/**
 * Define the base file that loaded the plugin for determining plugin path and other variables.
 *
 * @since 1.0.0
 *
 * @var string Base file that loaded the plugin.
 */
define( 'TRIBE_EXTENSION_DEFAULT_ATTENDEE_FIELDS_FILE', __FILE__ );

/**
 * Register and load the service provider for loading the extension.
 *
 * @since 1.0.0
 */
function tribe_extension_default_attendee_fields() {
	// When we don't have autoloader from common we bail.
	if ( ! class_exists( 'Tribe__Autoloader' ) ) {
		return;
	}

	// Register the namespace so we can the plugin on the service provider registration.
	Tribe__Autoloader::instance()->register_prefix(
		'\\Tribe\\Extensions\\Default_Attendee_Fields\\',
		__DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Tec',
		'default-attendee-fields'
	);

	// Deactivates the plugin in case of the main class didn't autoload.
	if ( ! class_exists( '\Tribe\Extensions\Default_Attendee_Fields\Plugin' ) ) {
		tribe_transient_notice(
			'default-attendee-fields',
			'<p>' . esc_html__( 'Couldn\'t properly load "Event Tickets Extension: Default Attendee Fields" the extension was deactivated.', 'tec-labs-default-attendee-fields' ) . '</p>',
			[],
			// 1 second after that make sure the transient is removed.
			1
		);

		if ( ! function_exists( 'deactivate_plugins' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		deactivate_plugins( __FILE__, true );
		return;
	}
	tribe_register_provider( '\Tribe\Extensions\Default_Attendee_Fields\Plugin' );
	add_filter( 'plugin_action_links', 'add_plugin_actions', 10, 2 );
}

// Loads after common is already properly loaded.
add_action( 'tribe_common_loaded', 'tribe_extension_default_attendee_fields' );

/**
 * Add a `Settings` link to the plugin actions on the plugins page.
 *
 * @param array $plugin_actions The array of links for a plugin on the Plugins page.
 * @param string $plugin_file
 *
 * @return array
 */
function add_plugin_actions( array $plugin_actions, string $plugin_file ) {
	if ( ! class_exists( 'Tribe__Tickets_Plus__Main' ) ) {
		return $plugin_actions;
	}

	$new_actions = [];

	if ( trailingslashit( basename( plugin_dir_path( __FILE__ ) ) ) . 'plugin.php' === $plugin_file ) {
		$url                                             = esc_url( admin_url( 'admin.php?page=tec-tickets-settings&tab=attendee-registration#default-attendee-fields-settings' ) );
		$new_actions['tec-labs-default-attendee-fields'] = sprintf( __( '<a href="%s">Settings</a>', 'tec-labs-default-attendee-fields' ), $url );
	}

	return array_merge( $new_actions, $plugin_actions );
}
