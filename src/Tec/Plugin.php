<?php
/**
 * Plugin Class.
 *
 * @package Tribe\Extensions\Default_Attendee_Fields
 * @since   1.0.0
 *
 */

namespace Tribe\Extensions\Default_Attendee_Fields;

use TEC\Common\Contracts\Service_Provider;

/**
 * Class Plugin
 *
 * @package Tribe\Extensions\Default_Attendee_Fields
 * @since   1.0.0
 *
 */
class Plugin extends Service_Provider {
	/**
	 * Stores the version for the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const VERSION = '1.0.1';

	/**
	 * Stores the base slug for the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const SLUG = 'default-attendee-fields';

	/**
	 * Stores the base slug for the extension.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const FILE = TRIBE_EXTENSION_DEFAULT_ATTENDEE_FIELDS_FILE;

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
		$this->container->singleton( 'extension.default_attendee_fields', $this );
		$this->container->singleton( 'extension.default_attendee_fields.plugin', $this );
		$this->container->register( PUE::class );

		if ( ! $this->check_plugin_dependencies() ) {
			// If the plugin dependency manifest is not met, then bail and stop here.
			return;
		}

		// Do the settings.
		$this->get_settings();

		// Start binds.
		add_action( 'tribe_tickets_ticket_add', [ $this, 'apply_default_fieldset' ], 10, 3 );

		add_action( 'rest_insert_tribe_rsvp_tickets', [ $this, 'apply_default_fieldset_block_editor' ], 10, 3 );
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
		$this->container->singleton( 'extension.default_attendee_fields', $plugin_register );
	}

	/**
	 * Get this plugin's options prefix.
	 *
	 * Settings_Helper will append a trailing underscore before each option.
	 *
	 * @see \Tribe\Extensions\Default_Attendee_Fields\Settings::set_options_prefix()
	 * @return string
	 *
	 */
	private function get_options_prefix() {
		return (string) str_replace( '-', '_', 'tec-labs-default-attendee-fields' );
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
	 * Apply a fieldset to a newly created RSVP or ticket.
	 * Runs directly when Classic editor is used.
	 * Called from `apply_default_fieldset_block_editor` when Block editor is used.
	 *
	 * @since 1.0.0
	 *
	 * @param int                           $post_id The ID of the post / event for which the RSVP / ticket is being created.
	 * @param Tribe__Tickets__Ticket_Object $ticket  The ticket object with all its data.
	 * @param array                         $data    The ticket data sent.
	 */
	function apply_default_fieldset( $post_id, $ticket, $data ) {

		// Bail if it's an update (there is a ticket_id).
		// When creating a new RSVP/ticket, the ticket_id is empty.
		if ( ! empty( $data['ticket_id'] ) ) {
			return;
		}

		$options = $this->get_all_options();

		// If override is not checked and the RSVP / ticket already has a fieldset, then don't override.
		if (
			! $options['override_fieldset']
			&& $this->has_fieldset( $data )
		) {
			return;
		}

		// $ticket->provider_class should be set ...
		if ( isset( $ticket->provider_class ) ) {
			$ticket_type = $this->get_ticket_type( $ticket->provider_class );
		// ... except for Block Editor RSVP
		} elseif ( isset( $data['ticket_provider'] ) ) {
			$ticket_type = $this->get_ticket_type( $data['ticket_provider'] );
		} else {
			return;
		}


		// Checking for ticket provider and fetching the related fieldset ID.
		switch ( $ticket_type ) {
			case "rsvp":
				$default_form_post_id = $options['rsvp_default_fieldset'];
				break;
			case "wooticket":
				$default_form_post_id = $options['wooticket_default_fieldset'];
				break;
			case "eddticket":
				$default_form_post_id = $options['eddticket_default_fieldset'];
				break;
			case "tcticket":
				$default_form_post_id = $options['tcticket_default_fieldset'];
				break;
			default:
				$default_form_post_id = 0;
				break;
		}

		// If there is no default fieldset set up in the options, then bail.
		if (
			empty( $default_form_post_id )
			|| 0 == $default_form_post_id
		) {
			return;
		}

		// Get the fieldset value.
		// Get the postmeta `_tribe_tickets_meta_template` from `$default_form_post_id`.
		$fieldset = get_post_meta( $default_form_post_id, '_tribe_tickets_meta_template', true );

		$ticket_id = isset( $ticket->ID ) ? $ticket->ID : $post_id ;

		// Update postmeta for the RSVP / Ticket with the fieldset.
		if ( ! empty( $fieldset ) ) {
			update_post_meta( $ticket_id, '_tribe_tickets_meta', $fieldset );
			update_post_meta( $ticket_id, '_tribe_tickets_meta_enabled', 'yes' );
		}

	}

	/**
	 * Check if an RSVP / ticket already has a fieldset.
	 *
	 * @param array $data The ticket data.
	 *
	 * @return bool
	 */
	public function has_fieldset( $data ) {
		return
			// The array item exists
			isset( $data['tribe-tickets-input'] )
			// It is an array
			&& is_array( $data['tribe-tickets-input'] )
			// The array has more than one element
			&& count( $data['tribe-tickets-input'] ) > 1;
	}

	/**
	 * Gather the data when an RSVP is created in the block editor.
	 * Then call `apply_default_fieldset` to create the fieldset.
	 *
	 * Note: Woo, EDD and Tickets Commerce tickets are handled differently when created in the block editor.
	 * The `apply_default_fieldset` takes care of those by default.
	 *
	 * @param object $post    Inserted or updated post object.
	 * @param object $request Request object.
	 * @param bool   $create  True when creating a post, false when updating.
	 *
	 * @return bool|void      False when updating.
	 */
	public function apply_default_fieldset_block_editor( $post, $request, $create ) {
		// Bail when updating the RSVP.
		if ( ! $create ) {
			return;
		}

		$data['ticket_provider']     = 'Tribe__Tickets__RSVP';

		// Hand over to `apply_default_fieldset`
		$this->apply_default_fieldset( $post->ID, $request, $data );
	}

	/**
	 * Get the ticket type.
	 * The Classic Editor and the Block Editor handle this slightly differently.
	 *
	 * @param string|null $ticket_provider  The Service Provider Class.
	 *
	 * @return false|string
	 */
	public function get_ticket_type( string $ticket_provider = null ) {
		if ( $ticket_provider == 'Tribe__Tickets__RSVP' ) {
			return "rsvp";
		} elseif ( $ticket_provider == 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main' ) {
			return "wooticket";
		} elseif ( $ticket_provider == 'Tribe__Tickets_Plus__Commerce__EDD__Main' ) {
			return "eddticket";
		} elseif ( $ticket_provider == 'TEC\Tickets\Commerce\Module' ) {
			return "tcticket";
		} else {
			return false;
		}
	}

	/**
	 * Add a `Settings` link to the plugin actions on the plugins page.
	 *
	 * @param $links array The array of links for a plugin on the Plugins page.
	 *
	 * @return array
	 */
	public function plugin_settings_link( $links ) {
		$url           = get_admin_url() . 'edit.php?post_type=tribe_events&page=tribe-common&tab=event-tickets#default-attendee-fields-settings';
		$settings_link = '<a href="' . $url . '">' . __( 'Settings', 'tec-labs-default-attendee-fields' ) . '</a>';
		array_push( $links, $settings_link );

		return $links;
	}
}
