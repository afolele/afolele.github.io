<?php


namespace WpLandingKit\Upgrade;


class DbVersion {


	const DB_VERSION_KEY = 'wp_landing_kit_plugin_db_version';


	public function set( $number ) {
		update_option( self::DB_VERSION_KEY, $number );
	}


	public function get( $default = 0 ) {
		return get_option( self::DB_VERSION_KEY, $default );
	}


}