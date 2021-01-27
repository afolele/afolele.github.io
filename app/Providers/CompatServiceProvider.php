<?php


namespace WpLandingKit\Providers;


use WpLandingKit\Compat\BeaverBuilderCompat;
use WpLandingKit\Compat\BrizyCompat;
use WpLandingKit\Compat\DiviCompat;
use WpLandingKit\Compat\ElementorCompat;
use WpLandingKit\Compat\OxygenCompat;
use WpLandingKit\Compat\WpPreviewCompat;
use WpLandingKit\Framework\Providers\ServiceProviderBase;


class CompatServiceProvider extends ServiceProviderBase {


	public function register() {
		$this->app->bind( DiviCompat::class );
		$this->app->bind( ElementorCompat::class );
		$this->app->bind( BrizyCompat::class );
		$this->app->bind( BeaverBuilderCompat::class );
		$this->app->bind( WpPreviewCompat::class );
		$this->app->bind( OxygenCompat::class );
	}


	public function boot() {
		$this->app->make( DiviCompat::class )->init();
		$this->app->make( ElementorCompat::class )->init();
		$this->app->make( BrizyCompat::class )->init();
		$this->app->make( BeaverBuilderCompat::class )->init();
		$this->app->make( WpPreviewCompat::class )->init();
		$this->app->make( OxygenCompat::class )->init();
	}


}