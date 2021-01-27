<?php


namespace WpLandingKit\Providers;


use WpLandingKit\Framework\Container\Plugin;
use WpLandingKit\Framework\Providers\ServiceProviderBase;
use WpLandingKit\PostTypes\MappedDomainPostType;


class AssetServiceProvider extends ServiceProviderBase {


	/**
	 * Redefining the type in this child class purely for the benefit of code inspection. Not ideal, but see the to do
	 * on the \WpLandingKit\Framework\Container\Plugin::url() method for details.
	 *
	 * @var Plugin
	 */
	protected $app;


	public function register() {
		// Do nothing here for now. When we have a robust asset handling system, we'll then bind the assets we need
		// in here.
	}


	public function boot() {

		// todo - this should all be abstracted out of the provider into appropriate objects/systems

		$debug_mode = ( defined( 'SCRIPT_DEBUG' ) and SCRIPT_DEBUG );

		$dir = $this->app->url( 'build' );

		// Timestamp for the version tag when in debug mode, plugin version otherwise.
		$version = $debug_mode
			? time()
			: $this->app->make( 'plugin.version' );

		// No file suffix when in debug mode, '.min' otherwise
		$suffix = $debug_mode ? '' : '.min';

		//add_action( 'wp_enqueue_scripts', function () use ( $dir, $suffix, $version ) {
		//wp_register_style( 'wp-landing-kit', "$dir/css/wp-landing-kit$suffix.css", false, $version );
		//wp_enqueue_style( 'wp-landing-kit' );

		//wp_register_script( 'wp-landing-kit', "$dir/js/wp-landing-kit$suffix.js", false, true );
		//wp_enqueue_script( 'wp-landing-kit' );
		//} );

		add_action( 'admin_enqueue_scripts', function ( $hook_suffix ) use ( $dir, $suffix, $version ) {

			wp_register_script( 'wp-landing-kit-admin', "$dir/js/wp-landing-kit-admin$suffix.js", [ 'jquery' ], $version );
			wp_register_script( 'wp-landing-kit-upgrade', "$dir/js/wp-landing-kit-upgrade$suffix.js", [ 'jquery' ], $version );
			wp_register_style( 'wp-landing-kit-admin', "$dir/css/wp-landing-kit-admin$suffix.css", false, $version );

			wp_enqueue_style( 'wp-landing-kit-admin' );

			if ( get_post_type() === MappedDomainPostType::POST_TYPE ) {
				wp_enqueue_script( 'jquery' );
				wp_enqueue_script( 'jquery-ui-sortable' );
				wp_enqueue_script( 'wp-landing-kit-admin' );
			}

		} );
	}


}