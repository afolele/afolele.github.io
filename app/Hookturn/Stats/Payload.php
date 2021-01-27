<?php


namespace WpLandingKit\Hookturn\Stats;


use WpLandingKit\Framework\Facades\App;


class Payload {

	// todo - site as dependency
	// todo - server as dependency?

	private $plugin_name;
	private $plugin_version;
	private $site_url;
	private $event;
	private $php_version;
	private $rdbms;
	private $rdbms_version;
	private $wp_version;
	private $is_multisite;
	private $webserver;
	private $wp_memory_limit;
	private $php_time_limit;
	private $acf_version;
	private $is_acf_json_enabled;
	private $woocommerce_version;
	private $is_using_external_object_cache;
	private $active_plugins;
	private $extra;


	public function set_extra_data( array $data ) {
		$this->extra;
	}


	public function set_event( $event ) {
		$this->event = $event;
	}


	public function prepare() {
		return [
			'plugin_name' => App::make( 'plugin.name' ),
			'plugin_version' => App::make( 'plugin.version' ),
			'site_url' => site_url(),
			'event' => $this->event(),
			'php_version' => $this->php_version(),
			'rdbms' => $this->rdbms(),
			'rdbms_version' => $this->rdbms_version(),
			'wp_version' => $this->wp_version(),
			'is_multisite' => $this->is_multisite(),
			'webserver' => $this->webserver(),
			'wp_memory_limit' => $this->wp_memory_limit(),
			'php_time_limit' => $this->php_time_limit(),
			'acf_version' => $this->acf_version(),
			'is_acf_json_enabled' => $this->is_using_acf_json(),
			'woocommerce_version' => $this->woocommerce_version(),
			'is_using_external_object_cache' => $this->is_using_external_object_cache(),
			'active_plugins' => $this->active_plugins(),
			'extra' => $this->extra(),
		];
	}


	private function active_plugins() {
		$active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );

		$active_sitewide_plugins = get_site_option( 'active_sitewide_plugins' );

		if ( ! is_array( $active_plugins ) ) {
			$active_plugins = [];
		}

		if ( ! is_array( $active_sitewide_plugins ) ) {
			$active_sitewide_plugins = [];
		}

		$active_plugins = array_merge( $active_plugins, array_keys( $active_sitewide_plugins ) );

		$active_plugins = array_map( function ( $element ) {
			$bits = explode( '/', $element );

			return $bits[0];
		}, $active_plugins );

		return $active_plugins;
	}


	private function extra() {

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			$this->extra['wp_cli_action'] = true;
		}

		return $this->extra;
	}


	private function rdbms() {
		global $wpdb;

		$version = $wpdb->use_mysqli
			? mysqli_get_server_info( $wpdb->dbh )
			: mysql_get_server_info();

		if ( stristr( $version, 'mariadb' ) ) {
			return 'mariadb';
		}

		if ( $wpdb->is_mysql ) {
			return 'mysql';
		}

		return '';
	}


	private function php_version() {
		return function_exists( 'phpversion' )
			? phpversion()
			: '';
	}


	private function is_multisite() {
		return is_multisite();
	}


	private function webserver() {
		return ! empty( $_SERVER['SERVER_SOFTWARE'] ) ? $_SERVER['SERVER_SOFTWARE'] : '';
	}


	private function wp_memory_limit() {
		return esc_html( WP_MEMORY_LIMIT );
	}


	private function php_time_limit() {
		return function_exists( 'ini_get' )
			? esc_html( ini_get( 'max_execution_time' ) )
			: '';
	}


	private function rdbms_version() {
		global $wpdb;

		if ( method_exists( $wpdb, 'db_version' ) ) {
			return $wpdb->db_version();
		}

		return $wpdb->use_mysqli
			? mysqli_get_server_info( $wpdb->dbh )
			: mysql_get_server_info();
	}


	private function event() {
		if ( $this->event ) {
			return $this->event;
		}

		return '';
	}


	private function wp_version() {
		return get_bloginfo( 'version' );
	}


	private function acf_version() {
		if ( function_exists( 'acf_get_setting' ) ) {
			return acf_get_setting( 'version' );
		}

		return '';
	}


	private function is_using_acf_json() {
		if ( function_exists( 'acf_get_setting' ) ) {
			return acf_get_setting( 'json' );
		}

		return false;
	}


	private function is_using_external_object_cache() {
		return wp_using_ext_object_cache();
	}


	private function woocommerce_version() {
		if ( class_exists( '\WooCommerce' ) ) {
			global $woocommerce;

			return $woocommerce->version;
		}

		return '';
	}


}