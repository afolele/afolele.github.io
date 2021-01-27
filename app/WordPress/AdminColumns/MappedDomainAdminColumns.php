<?php


namespace WpLandingKit\WordPress\AdminColumns;


use WpLandingKit\Models\Domain;


class MappedDomainAdminColumns {


	public function init() {
		$post_type = Domain::post_type();
		add_filter( "manage_{$post_type}_posts_columns", [ $this, '_configure_columns' ] );
		add_action( "manage_{$post_type}_posts_custom_column", [ $this, '_populate_columns' ], 10, 2 );
	}


	public function _configure_columns( $defaults ) {
		unset( $defaults['date'] );
		$defaults['number_of_mappings'] = 'Mappings';

		return $defaults;
	}


	public function _populate_columns( $column_name, $post_id ) {
		if ( $column_name === 'number_of_mappings' ) {
			$domain = Domain::make( get_post() );

			echo count( $domain->mappings() );
		}
	}


}