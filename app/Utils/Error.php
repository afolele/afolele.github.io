<?php


namespace WpLandingKit\Utils;


use WP_Error;


class Error {


	const DEFAULT_CODE = 'wplk';


	/**
	 * @param $message
	 * @param mixed ...$vars
	 *
	 * @return WP_Error
	 */
	public static function make( $message, ...$vars ) {
		return new WP_Error( self::DEFAULT_CODE, self::format_message( $message, $vars ) );
	}


	private static function format_message( $message, array $vars ) {
		// JSON encode anything that needs it.
		$vars = array_map( 'self::encode_if_encodable', $vars );

		return vsprintf( $message, $vars );
	}


	private static function encode_if_encodable( $value ) {
		return ( is_array( $value ) or is_object( $value ) ) ? wp_json_encode( $value ) : $value;
	}


}