<?php
/**
 * Plugin Class.
 *
 * @package Tribe\Extensions\Default_Ticket_Fieldset
 * @since   1.0.0
 *
 */

namespace Tribe\Extensions\Default_Ticket_Fieldset;

/**
 * Class Plugin
 *
 * @package Tribe\Extensions\Default_Ticket_Fieldset
 * @since   1.0.0
 *
 */
class Plugin extends \tad_DI52_ServiceProvider {
	/**
	 * Stores the version for the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const VERSION = '1.0.0';

	/**
	 * Stores the base slug for the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const SLUG = 'default-ticket-fieldset';

	/**
	 * Stores the base slug for the extension.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const FILE = TRIBE_EXTENSION_DEFAULT_TICKET_FIELDSET_FILE;

	/**
	 * @since 1.0.0
	 *
	 * @var string Plugin Directory.
	 */
	public $plugin_dir;

	/**
	 * @since 1.0.0
	 *
	 * @var string Plugin path.
	 */
	public $plugin_path;

	/**
	 * @since 1.0.0
	 *
	 * @var string Plugin URL.
	 */
	public $plugin_url;

	/**
	 * @since 1.0.0
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Setup the Extension's properties.
	 *
	 * This always executes even if the required plugins are not present.
	 *
	 * @since 1.0.0
	 */
	public function register() {
		// Set up the plugin provider properties.
		$this->plugin_path = trailingslashit( dirname( static::FILE ) );
		$this->plugin_dir  = trailingslashit( basename( $this->plugin_path ) );
		$this->plugin_url  = plugins_url( $this->plugin_dir, $this->plugin_path );

		// Register this provider as the main one and use a bunch of aliases.
		$this->container->singleton( static::class, $this );
		$this->container->singleton( 'extension.default_ticket_fieldset', $this );
		$this->container->singleton( 'extension.default_ticket_fieldset.plugin', $this );
		$this->container->register( PUE::class );

		if ( ! $this->check_plugin_dependencies() ) {
			// If the plugin dependency manifest is not met, then bail and stop here.
			return;
		}

		// Do the settings.
		$this->get_settings();

		// Start binds.

		add_action( 'tribe_tickets_ticket_add', [ $this, 'apply_default_fieldset' ], 10, 3 );

		// End binds.

		$this->container->register( Hooks::class );
		$this->container->register( Assets::class );
	}

	/**
	 * Checks whether the plugin dependency manifest is satisfied or not.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Whether the plugin dependency manifest is satisfied or not.
	 */
	protected function check_plugin_dependencies() {
		$this->register_plugin_dependencies();

		return tribe_check_plugin( static::class );
	}

	/**
	 * Registers the plugin and dependency manifest among those managed by Tribe Common.
	 *
	 * @since 1.0.0
	 */
	protected function register_plugin_dependencies() {
		$plugin_register = new Plugin_Register();
		$plugin_register->register_plugin();

		$this->container->singleton( Plugin_Register::class, $plugin_register );
		$this->container->singleton( 'extension.default_ticket_fieldset', $plugin_register );
	}

	/**
	 * Get this plugin's options prefix.
	 *
	 * Settings_Helper will append a trailing underscore before each option.
	 *
	 * @see \Tribe\Extensions\Default_Ticket_Fieldset\Settings::set_options_prefix()
	 * @return string
	 *
	 */
	private function get_options_prefix() {
		return (string) str_replace( '-', '_', 'tec-labs-default-ticket-fieldset' );
	}

	/**
	 * Get Settings instance.
	 *
	 * @return Settings
	 */
	private function get_settings() {
		if ( empty( $this->settings ) ) {
			$this->settings = new Settings( $this->get_options_prefix() );
		}

		return $this->settings;
	}

	/**
	 * Get all of this extension's options.
	 *
	 * @return array
	 */
	public function get_all_options() {
		$settings = $this->get_settings();

		return $settings->get_all_options();
	}

	/**
	 * Get a specific extension option.
	 *
	 * @param        $option
	 * @param string $default
	 *
	 * @return array
	 */
	public function get_option( $option, $default = '' ) {
		$settings = $this->get_settings();

		return $settings->get_option( $option, $default );
	}

	/**
	 * Apply a fieldset to a newly created RSVP or ticket
	 *
	 * @param $post_id
	 * @param $ticket
	 * @param $data
	 */
	function apply_default_fieldset( $post_id, $ticket, $data ) {

		// Run only when the ticket is getting created. Not on update.
		if ( ! empty( $data['ticket_id'] ) ) {
			return;
		}

		$options = $this->get_all_options();

		// If override is not checked and there is a fieldset, then don't override.
		if (
			! $options['override_fieldset']
			&& count( $data['tribe-tickets-input'] ) > 1
		) {
			return;
		}

		if ( $data['ticket_provider'] == Tribe__Tickets__RSVP ) {
			$default_form_post_id = $options['rsvp_default_fieldset'];
		} elseif ( $data['ticket_provider'] == Tribe__Tickets_Plus__Commerce__WooCommerce__Main ) {
			$default_form_post_id = $options['wooticket_default_fieldset'];
		} elseif ( $data['ticket_provider'] == Tribe__Tickets_Plus__Commerce__EDD__Main ) {
			$default_form_post_id = $options['eddticket_default_fieldset'];
		} else {
			return;
		}

		if (
			empty( $default_form_post_id )
			|| ! isset ( $default_form_post_id )
			|| 0 == $default_form_post_id
		) {
			return;
		}

		// Get postmeta _tribe_tickets_meta_template from $default_form_post_id
		$fieldset = get_post_meta( $default_form_post_id, '_tribe_tickets_meta_template', true );

		// update postmeta for the RSVP / Ticket
		if ( ! empty( $fieldset ) ) {
			update_post_meta( $ticket->ID, '_tribe_tickets_meta', $fieldset );
			update_post_meta( $ticket->ID, '_tribe_tickets_meta_enabled', 'yes' );
		}

	}

	public function plugin_settings_link( $links ) {
		$url           = get_admin_url() . 'edit.php?post_type=tribe_events&page=tribe-common&tab=event-tickets';
		$settings_link = '<a href="' . $url . '">' . __( 'Settings', 'tec-labs-default-ticket-fieldset' ) . '</a>';
		array_push( $links, $settings_link );

		return $links;
	}
}
