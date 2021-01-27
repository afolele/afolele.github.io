<?php


namespace WpLandingKit\Hookturn\Stats;


use WpLandingKit\Events\PluginActivated;
use WpLandingKit\Events\PluginDeactivated;
use WpLandingKit\Framework\Container\Plugin;
use WpLandingKit\Hookturn\Api\Client;


class PluginStateChangeListener {


	/**
	 * @var Plugin
	 */
	private $plugin;


	/**
	 * @param Plugin $plugin
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}


	public function handle( $event, $payload = [] ) {

		if ( $event === PluginActivated::class ) {
			$this->handle_PluginActivated_event( $payload[0] );

		} elseif ( $event === PluginDeactivated::class ) {
			$this->handle_PluginDeactivated_event( $payload[0] );
		}

	}


	private function handle_PluginActivated_event( PluginActivated $event ) {
		$this->plugin->make( Client::class )->activate();
	}


	private function handle_PluginDeactivated_event( PluginDeactivated $event ) {
		// If/when we have deactivation surveys in place, consider adding the reason here as additional data passed to
		// the deactivate() method.
		$this->plugin->make( Client::class )->deactivate();
	}


}