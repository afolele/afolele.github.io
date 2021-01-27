<?php


namespace WpLandingKit;


use WpLandingKit\Events\PluginDeactivated;
use WpLandingKit\Framework\Container\Plugin;


class DeactivationHandler {


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


	public function deactivate() {
		$this->plugin->make( 'events' )->dispatch( new PluginDeactivated() );

		// Run deactivation routines here.

		// Alternatively, register your own listeners against the PluginDeactivated event and list these in the
		// \WpLandingKit\Providers\EventServiceProvider::$listen array.
	}


}