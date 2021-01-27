<?php


namespace WpLandingKit\DomainIntercept;


use WpLandingKit\Framework\Providers\ServiceProviderBase;
use WpLandingKit\Http\Request;
use WpLandingKit\Http\Server;
use WpLandingKit\WordPress\Site;


class DomainInterceptProvider extends ServiceProviderBase {


	public function register() {
		$this->app->singleton( Site::class ); // CFW - this doesn't belong here, consider moving to a more relevant provider
		$this->app->singleton( Server::class ); // CFW
		$this->app->singleton( Request::class ); // CFW
		$this->app->singleton( RequestInterceptor::class );
		$this->app->singleton( Context::class );
		$this->app->singleton( DomainMap::class );
		$this->app->singleton( DomainReplacer::class );
	}


	public function boot() {
		$this->app->make( RequestInterceptor::class )->listen();
		$this->app->make( DomainMap::class )->init();
	}


}