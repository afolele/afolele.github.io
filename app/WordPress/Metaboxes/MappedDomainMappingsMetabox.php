<?php


namespace WpLandingKit\WordPress\Metaboxes;


use WpLandingKit\Models\Domain;
use WpLandingKit\Utils\Request;
use WpLandingKit\View\AdminView;
use WP_Post;


class MappedDomainMappingsMetabox {


	public function init() {
		add_action( 'add_meta_boxes', [ $this, '_register' ] );
	}


	public function prepare_model( Domain $domain ) {
		$data = Request::pull( 'wp_landing_kit.mappings', null );
		if ( $data !== null ) {
			$domain->set_mappings( $data );

			return true;
		}

		return false;
	}


	public function _register() {
		add_meta_box(
			'wp-landing-kit-domain-mappings',
			'Mappings',
			[ $this, '_render' ],
			'mapped-domain',
			'normal'
		);
	}


	public function _render( WP_Post $post ) {
		AdminView::render( 'metabox-fields/domain-mappings', [ 'domain' => Domain::make( $post ) ] );
	}


}