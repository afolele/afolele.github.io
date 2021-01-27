<?php


namespace WpLandingKit\Providers;


use WpLandingKit\Framework;
use WpLandingKit\PostTypes\MappedDomainPostType;
use WpLandingKit\WordPress\AdminColumns\MappedDomainAdminColumns;
use WpLandingKit\WordPress\AdminColumns\PostAdminColumns;
use WpLandingKit\WordPress\Metaboxes\MappedDomainMappingsMetabox;
use WpLandingKit\WordPress\Metaboxes\MappedDomainSettingsMetabox;


class PostTypeServiceProvider extends Framework\PostTypes\PostTypeServiceProvider {


	protected $post_types = [
		MappedDomainPostType::class
	];


	public function register() {
		parent::register();

		$this->app->singleton( MappedDomainMappingsMetabox::class );
		$this->app->singleton( MappedDomainSettingsMetabox::class );
		$this->app->singleton( MappedDomainAdminColumns::class );
		$this->app->singleton( PostAdminColumns::class );
	}


	public function boot() {
		parent::boot();

		add_action( 'init', function () {
			$this->app->make( PostAdminColumns::class )->init();
		} );
	}


}