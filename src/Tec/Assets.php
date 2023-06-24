<?php
/**
 * Handles registering all Assets for the Plugin.
 *
 * To remove a Asset you can use the global assets handler:
 *
 * ```php
 *  tribe( 'assets' )->remove( 'asset-name' );
 * ```
 *
 * @since 1.0.0
 *
 * @package Tribe\Extensions\Default_Ticket_Fieldset
 */

namespace Tribe\Extensions\Default_Ticket_Fieldset;

use TEC\Common\Contracts\Service_Provider;

/**
 * Register Assets.
 *
 * @since 1.0.0
 *
 * @package Tribe\Extensions\Default_Ticket_Fieldset
 */
class Assets extends Service_Provider {
	/**
	 * Binds and sets up implementations.
	 *
	 * @since 1.0.0
	 */
	public function register() {
		$this->container->singleton( static::class, $this );
		$this->container->singleton( 'extension.default_ticket_fieldset.assets', $this );

		$plugin = tribe( Plugin::class );

	}
}
