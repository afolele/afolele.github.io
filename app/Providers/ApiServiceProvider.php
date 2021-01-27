<?php


namespace WpLandingKit\Providers;


use WpLandingKit\Framework\Providers\ServiceProviderBase;


class ApiServiceProvider extends ServiceProviderBase {


	public function register() {

		// These classes aren't handled by our autoloader so we need to include them here.
		include_once $this->app->base_path( 'inc/WPLK_Domain.php' );
		include_once $this->app->base_path( 'inc/WPLK_Mapping.php' );

		$this->app->bind( \WPLK_Domain::class );
		$this->app->bind( \WPLK_Mapping::class );
	}


}