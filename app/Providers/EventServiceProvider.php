<?php


namespace WpLandingKit\Providers;


use WpLandingKit\Events\UpdateDomainMap;
use WpLandingKit\Framework;
use WpLandingKit\Events\PluginActivated;
use WpLandingKit\Events\PluginDeactivated;
use WpLandingKit\Hookturn\Stats\PluginStateChangeListener;
use WpLandingKit\Listeners\DomainMapUpdateListener;


class EventServiceProvider extends Framework\Events\EventServiceProvider {


	protected $listen = [
		PluginActivated::class => [
			PluginStateChangeListener::class,
		],
		PluginDeactivated::class => [
			PluginStateChangeListener::class,
		],
		UpdateDomainMap::class => [
			DomainMapUpdateListener::class,
		],
	];


}