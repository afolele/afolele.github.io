<?php


namespace WpLandingKit\Providers;


use WpLandingKit\Framework;
use WpLandingKit\Settings;
use WpLandingKit\WordPress\AdminPages\SettingsPage;
use WpLandingKit\WordPress\AdminPages\ToolsPage;


class SettingsProvider extends Framework\Providers\ServiceProviderBase {


	public function register() {
		$this->app->singleton( Settings::class );
		$this->app->singleton( SettingsPage::class );
		$this->app->singleton( ToolsPage::class );
	}


	public function boot() {
		$this->app->make( Settings::class )->init();
		$this->app->make( SettingsPage::class )->init();
		$this->app->make( ToolsPage::class )->init();
	}


}