<?php
/**
 * Settings Object.
 *
 * @package Tribe\Extensions\Default_Attendee_Fields
 * @since   1.0.0
 *
 */

namespace Tribe\Extensions\Default_Attendee_Fields;

use Tribe__Settings_Manager;

/**
 * Do the Settings.
 */
class Settings {

	/**
	 * The Settings Helper class.
	 *
	 * @var Settings_Helper
	 */
	protected $settings_helper;

	/**
	 * The prefix for our settings keys.
	 *
	 * @see get_options_prefix() Use this method to get this property's value.
	 *
	 * @var string
	 */
	private $options_prefix = '';

	/**
	 * Settings constructor.
	 *
	 * @param string $options_prefix Recommended: the plugin text domain, with hyphens converted to underscores.
	 */
	public function __construct( $options_prefix ) {
		$this->settings_helper = new Settings_Helper();

		$this->set_options_prefix( $options_prefix );

		// Add settings specific to OSM
		add_action( 'admin_init', [ $this, 'add_settings' ] );
	}

	/**
	 * Allow access to set the Settings Helper property.
	 *
	 * @see get_settings_helper()
	 *
	 * @param Settings_Helper $helper
	 *
	 * @return Settings_Helper
	 */
	public function set_settings_helper( Settings_Helper $helper ) {
		$this->settings_helper = $helper;

		return $this->get_settings_helper();
	}

	/**
	 * Allow access to get the Settings Helper property.
	 *
	 * @see set_settings_helper()
	 */
	public function get_settings_helper() {
		return $this->settings_helper;
	}

	/**
	 * Set the options prefix to be used for this extension's settings.
	 *
	 * Recommended: the plugin text domain, with hyphens converted to underscores.
	 * Is forced to end with a single underscore. All double-underscores are converted to single.
	 *
	 * @see get_options_prefix()
	 *
	 * @param string $options_prefix
	 */
	private function set_options_prefix( $options_prefix = '' ) {
		if ( empty( $opts_prefix ) ) {
			$opts_prefix = str_replace( '-', '_', 'tec-labs-default-attendee-fields' ); // The text domain.
		}

		$opts_prefix = $opts_prefix . '_';

		$this->options_prefix = str_replace( '__', '_', $opts_prefix );
	}

	/**
	 * Get this extension's options prefix.
	 *
	 * @see set_options_prefix()
	 *
	 * @return string
	 */
	public function get_options_prefix() {
		return $this->options_prefix;
	}

	/**
	 * Given an option key, get this extension's option value.
	 *
	 * This automatically prepends this extension's option prefix so you can just do `$this->get_option( 'a_setting' )`.
	 *
	 * @see tribe_get_option()
	 *
	 * @param string $key
	 * @param string $default
	 *
	 * @return mixed
	 */
	public function get_option( $key = '', $default = '' ) {
		$key = $this->sanitize_option_key( $key );

		return tribe_get_option( $key, $default );
	}

	/**
	 * Get an option key after ensuring it is appropriately prefixed.
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	private function sanitize_option_key( $key = '' ) {
		$prefix = $this->get_options_prefix();

		if ( 0 === strpos( $key, $prefix ) ) {
			$prefix = '';
		}

		return $prefix . $key;
	}

	/**
	 * Get an array of all of this extension's options without array keys having the redundant prefix.
	 *
	 * @return array
	 */
	public function get_all_options() {
		$raw_options = $this->get_all_raw_options();

		$result = [];

		$prefix = $this->get_options_prefix();

		foreach ( $raw_options as $key => $value ) {
			$abbr_key            = str_replace( $prefix, '', $key );
			$result[ $abbr_key ] = $value;
		}

		return $result;
	}

	/**
	 * Get an array of all of this extension's raw options (i.e. the ones starting with its prefix).
	 *
	 * @return array
	 */
	public function get_all_raw_options() {
		$tribe_options = Tribe__Settings_Manager::get_options();

		if ( ! is_array( $tribe_options ) ) {
			return [];
		}

		$result = [];

		foreach ( $tribe_options as $key => $value ) {
			if ( 0 === strpos( $key, $this->get_options_prefix() ) ) {
				$result[ $key ] = $value;
			}
		}

		return $result;
	}

	/**
	 * Given an option key, delete this extension's option value.
	 *
	 * This automatically prepends this extension's option prefix so you can just do `$this->delete_option( 'a_setting' )`.
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function delete_option( $key = '' ) {
		$key = $this->sanitize_option_key( $key );

		$options = Tribe__Settings_Manager::get_options();

		unset( $options[ $key ] );

		return Tribe__Settings_Manager::set_options( $options );
	}

	/**
	 * Adds a new section of fields to Events > Settings > General tab, appearing after the "Map Settings" section
	 * and before the "Miscellaneous Settings" section.
	 */
	public function add_settings() {
		$ticket_fieldsets = $this->get_ticket_fieldsets();

		$fields = [
			'default-fieldset-heading' => [
				'type' => 'html',
				'html' => $this->get_default_fieldset_intro_text(),
			],
			'rsvp_default_fieldset'    => [
				'type'            => 'dropdown',
				'label'           => esc_html_x( 'RSVP', 'Setting label', 'tec-labs-default-attendee-fields' ),
				'tooltip'         => esc_html_x( 'The Attendee Fields to be added when an RSVP is created.', 'Setting description', 'tec-labs-default-attendee-fields' ),
				'validation_type' => 'options',
				'options'         => $ticket_fieldsets,
			],
		];

		if ( class_exists( 'WooCommerce' ) ) {
			$fields['wooticket_default_fieldset'] = [
				'type'            => 'dropdown',
				'label'           => esc_html_x( 'WooCommerce ticket', 'Setting label', 'tec-labs-default-attendee-fields' ),
				'tooltip'         => sprintf(
									// Translators: %s Name of the eCommerce platform.
										esc_html_x(
											'The Attendee Fields to be added when a ticket with %s is created.',
											'Setting description',
											'tec-labs-default-attendee-fields'
										),
										'WooCommerce'
				),
				'validation_type' => 'options',
				'options'         => $ticket_fieldsets,
			];
		}

		if ( class_exists( 'Easy_Digital_Downloads' ) ) {
			$fields['eddticket_default_fieldset'] = [
				'type'            => 'dropdown',
				'label'           => esc_html_x( 'EDD ticket', 'Setting label', 'tec-labs-default-attendee-fields' ),
				'tooltip'         => sprintf(
									// Translators: %s Name of the eCommerce platform.
										esc_html_x(
											'The Attendee Fields to be added when a ticket with %s is created.',
											'Setting description',
											'tec-labs-default-attendee-fields'
										),
										'Easy Digital Downloads'
				),
				'validation_type' => 'options',
				'options'         => $ticket_fieldsets,

			];
		}

		$fields['override_fieldset'] = [
			'type'            => 'checkbox_bool',
			'label'           => esc_html_x( 'Override fieldsets', 'Setting label', 'tec-labs-default-attendee-fields' ),
			'tooltip'         => esc_html_x( 'Enable if you want to force the selected fieldsets on ticket creation.', 'Setting description', 'tec-labs-default-attendee-fields' ),
			'validation_type' => 'boolean',
			'default'         => false,
		];

		$this->settings_helper->add_fields(
			$this->prefix_settings_field_keys( $fields ), // fields
			'attendee-registration',
			'ticket-attendee-page-id',
			false
		);
	}

	/**
	 * Add the options prefix to each of the array keys.
	 *
	 * @param array $fields
	 *
	 * @return array
	 */
	private function prefix_settings_field_keys( array $fields ) {
		$prefixed_fields = array_combine(
			array_map(
				function ( $key ) {
					return $this->get_options_prefix() . $key;
				}, array_keys( $fields )
			),
			$fields
		);

		return (array) $prefixed_fields;
	}

	/**
	 * Here is an example of getting some HTML for the Settings Header.
	 *
	 * @return string
	 */
	private function get_default_fieldset_intro_text() {
		$result = '<h3 id="default-attendee-fields-settings">' . esc_html_x( 'Default Attendee Fields for Collecting Attendee Registration Information', 'Settings header', 'tec-labs-default-attendee-fields' ) . '</h3>';
		$result .= '<div style="margin-left: 20px;">';
		$result .= '<p>';
		$result .= sprintf(
            // Translators: %1$s opening <a> tag with URL, %2$s closing </a> tag
			esc_html_x( 'You can choose a %1$sticket fieldset%2$s (attendee fields) that will be added to every newly created RSVP or ticket. The fields will be added to RSVPs and tickets created both on the backend or through the Community Events submission form.', 'Setting section description', 'tec-labs-default-attendee-fields' ),
			'<a href="' . get_site_url() . '/wp-admin/edit.php?post_type=ticket-meta-fieldset">',
			'</a>'
		);
		$result .= ' ';
		$result .= esc_html_x( 'If a fieldset is already being added to a ticket manually, then the defaults will not be applied, unless the override setting is enabled.', 'Setting section description', 'tec-labs-default-attendee-fields' );
		$result .= '<br>';
		$result .= sprintf(
			// Translators: %1$s opening <a> tag with URL, %2$s closing </a> tag
			esc_html_x(
				'You can create ticket fieldsets for attendee information collection %1$shere%2$s.',
				'Setting section description',
				'tec-labs-default-attendee-fields'
			),
			'<a href="' . get_site_url() . '/wp-admin/edit.php?post_type=ticket-meta-fieldset">',
			'</a>'
		);
		$result .= '</p>';
		$result .= '</div>';

		return $result;
	}

	/**
	 * Get the list of the fieldsets.
	 *
	 * @since 1.0.0
	 * @return array
	 *
	 */
	private function get_ticket_fieldsets() {
		$fieldset_class = new \Tribe__Tickets_Plus__Meta__Fieldset;
		$fieldsets      = $fieldset_class->get_fieldsets();

		$dropdown = [
			'' => esc_html_x( 'No default fieldset', 'Default option', 'tec-labs-default-attendee-fields' )
		];

		foreach ( $fieldsets as $fieldset ) {
			$dropdown[ $fieldset->ID ] = $fieldset->post_title;
		}

		return $dropdown;
	}

}
