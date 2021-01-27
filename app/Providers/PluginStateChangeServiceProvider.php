<?php


namespace WpLandingKit\Providers;


use WpLandingKit\ActivationHandler;
use WpLandingKit\DeactivationHandler;
use WpLandingKit\Framework;


class PluginStateChangeServiceProvider extends Framework\Providers\ServiceProviderBase {


	/**
	 * Register container bindings for this provider.
	 *
	 * @return void
	 */
	public function register() {
		$this->app->bind( ActivationHandler::class );
		$this->app->bind( DeactivationHandler::class );
	}


	/**
	 * Run any boot routines. All container bindings should have been registered at this point so it is now possible
	 * to interact with bindings safely.
	 */
	public function boot() {
		// Run any boot processes here
	}


}