<?php


namespace WpLandingKit\Edd;


use WpLandingKit\Facades\Settings;
use WpLandingKit\Framework\Facades\App;
use WpLandingKit\Framework\Facades\Config;
use WpLandingKit\Framework\Providers\ServiceProviderBase;


class EddServiceProvider extends ServiceProviderBase {


	public function register() {

		$this->app->singleton( RemoteLicenseClient::class, function () {
			$instance = new RemoteLicenseClient();
			$instance->set_item_name( Config::get( 'edd.item_name' ) );
			$instance->set_store_url( Config::get( 'edd.store_url' ) );

			return $instance;
		} );

		$this->app->singleton( PluginUpdater::class, function () {
			$instance = new PluginUpdater(
				Config::get( 'edd.store_url' ),
				App::make( 'plugin.file' ),
				[
					'item_name' => Config::get( 'edd.item_name' ),
					'version' => App::make( 'plugin.version' ),
					'license' => Settings::get( 'license_key' ),
					'author' => App::make( 'plugin.author' ),
					'beta' => false,
				]
			);

			return $instance;
		} );
	}


	public function boot() {
		add_action( 'admin_init', [ $this->app->make( PluginUpdater::class ), 'init' ] );
	}


}