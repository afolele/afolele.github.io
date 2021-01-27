<?php


namespace WpLandingKit\WordPress\Metaboxes;


use WpLandingKit\Models\Domain;
use WpLandingKit\Utils\Request;
use WpLandingKit\View\AdminView;
use WP_Post;


class MappedDomainSettingsMetabox {


	public function init() {
		add_action( 'add_meta_boxes', [ $this, '_register' ] );
	}


	public function prepare_model( Domain $domain ) {
		$data = Request::pull( 'wp_landing_kit.settings', null );
		if ( $data !== null ) {
			$domain->set_settings( $data );

			return true;
		}

		return false;
	}


	public function _register() {
		add_meta_box(
			'wp-landing-kit-domain-settings',
			'Settings',
			[ $this, '_render' ],
			'mapped-domain',
			'normal'
		);
	}


	public function _render( WP_Post $post ) {
		AdminView::render( 'metabox-fields/enforce-protocol-field', [ 'domain' => Domain::make( $post ) ] );
	}


}