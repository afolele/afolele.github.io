<?php


namespace WpLandingKit\Providers;


use WpLandingKit\Framework;


class TaxonomyServiceProvider extends Framework\Providers\ServiceProviderBase {


	/**
	 * Register container bindings for this provider.
	 *
	 * @return void
	 */
	public function register() {
	}


	/**
	 * Run any boot routines. All container bindings should have been registered at this point so it is now possible
	 * to interact with bindings safely.
	 */
	public function boot() {
		add_action( 'init', [ $this, '_register_taxonomies' ] );
	}


	public static function _register_taxonomies() {
		// register taxonomies here
	}


}