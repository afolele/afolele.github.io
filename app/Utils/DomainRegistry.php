<?php


namespace WpLandingKit\Utils;


use WpLandingKit\Models\Domain;
use WPLK_Domain;


class DomainRegistry {


	/** @var WPLK_Domain[] */
	private static $instances = [];


	/**
	 * @param WPLK_Domain $domain
	 */
	public static function set( WPLK_Domain $domain ) {
		self::$instances[ (int) $domain->post_id() ] = $domain;
		self::$instances[ $domain->host() ] = $domain;
	}


	/**
	 * @param int|string $domain The domain ID, host name, or URL containing the host name.
	 *
	 * @return WPLK_Domain|false
	 */
	public static function get( $domain ) {
		if ( is_numeric( $domain ) and $id = (int) $domain ) {
			if ( isset( self::$instances[ $id ] ) and self::$instances[ $id ] instanceof WPLK_Domain ) {
				return self::$instances[ $id ];
			}
		}

		if ( is_string( $domain ) and $host = Domain::post_type_obj()->sanitize_title( $domain ) ) {
			if ( isset( self::$instances[ $host ] ) and self::$instances[ $host ] instanceof WPLK_Domain ) {
				return self::$instances[ $host ];
			}
		}

		return false;
	}


	/**
	 * @param int|string|WPLK_Domain $domain The domain ID, host name, URL containing the host name, or the domain object.
	 *
	 * @return bool
	 */
	public static function purge( $domain ) {
		if ( ! $domain instanceof WPLK_Domain ) {
			$domain = self::get( $domain );
		}

		if ( ! $domain instanceof WPLK_Domain ) {
			return false;
		}

		unset( self::$instances[ $domain->post_id() ] );
		unset( self::$instances[ $domain->host() ] );

		return true;
	}


	public static function reset() {
		self::$instances = [];
	}


}