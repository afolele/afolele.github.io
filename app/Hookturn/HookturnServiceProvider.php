<?php


namespace WpLandingKit\Hookturn;


use WpLandingKit\Framework\Providers\ServiceProviderBase;
use WpLandingKit\Hookturn\Api\Client;
use WpLandingKit\Hookturn\Stats\PluginStateChangeListener;


class HookturnServiceProvider extends ServiceProviderBase {


	public function register() {
		$this->app->singleton( PluginStateChangeListener::class );
		$this->app->singleton( Client::class );
	}


}