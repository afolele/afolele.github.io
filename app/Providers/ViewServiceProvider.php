<?php


namespace WpLandingKit\Providers;


use WpLandingKit\Framework;
use WpLandingKit\Framework\Container\Container;
use WpLandingKit\Framework\View\ViewRenderer;
use WpLandingKit\View\AdminView;
use WpLandingKit\View\View;


class ViewServiceProvider extends Framework\Providers\ServiceProviderBase {


	/**
	 * Register container bindings for this provider.
	 *
	 * @return void
	 */
	public function register() {

		$this->app['view_renderer'] = function ( Container $plugin ) {
			$renderer = new ViewRenderer();
			$renderer->set_view_base_dir( $plugin->make( 'path.templates' ) . '/public' );
			$renderer->set_view_override_base_dir( get_stylesheet_directory() . '/wp-landing-kit' );
			$renderer->make_all_templates_overridable();

			return $renderer;
		};

		$this->app['admin_view_renderer'] = function ( Container $plugin ) {
			$renderer = new ViewRenderer();
			$renderer->set_view_base_dir( $plugin->make( 'path.templates' ) . '/admin' );

			return $renderer;
		};
	}


	/**
	 * Run any boot routines. All container bindings should have been registered at this point so it is now possible
	 * to interact with bindings safely.
	 */
	public function boot() {
		View::set_view_renderer( $this->app['view_renderer'] );
		AdminView::set_view_renderer( $this->app['admin_view_renderer'] );
	}


}