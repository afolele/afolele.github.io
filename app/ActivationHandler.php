<?php


namespace WpLandingKit;


use WpLandingKit\Actions\SetCapabilities;
use WpLandingKit\Events\PluginActivated;
use WpLandingKit\Framework\Container\Plugin;


class ActivationHandler {


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


	public function activate() {
		$this->plugin->make( 'events' )->dispatch( new PluginActivated() );

		// Run activation routines here.
		SetCapabilities::run();

		// Alternatively, register your own listeners against the PluginActivated event and list these in the
		// \WpLandingKit\Providers\EventServiceProvider::$listen array.
	}


}