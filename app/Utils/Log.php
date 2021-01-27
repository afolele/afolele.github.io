<?php


namespace WpLandingKit\Utils;


use WP_Error;


class Log {


	public static function notice( $message, ...$vars ) {
		return self::trigger( self::format_message( $message, $vars ), E_USER_NOTICE );
	}


	public static function warning( $message, ...$vars ) {
		return self::trigger( self::format_message( $message, $vars ), E_USER_WARNING );
	}


	public static function error( $message, ...$vars ) {
		return self::trigger( self::format_message( $message, $vars ), E_USER_ERROR );
	}


	/**
	 * Chainable return value handler. Useful for one-line/fluent error handling.
	 *
	 * @param mixed $return The value to return.
	 *
	 * @return mixed
	 */
	public function return( $return ) {
		return $return;
	}


	/**
	 * Chainable return handler for returning nothing. Technically returns null.
	 *
	 * @return void|null
	 */
	public function void() {
		return;
	}


	private static function format_message( $message, array $vars ) {
		// JSON encode anything that needs it.
		$vars = array_map( 'self::encode_if_encodable', $vars );

		return vsprintf( $message, $vars );
	}


	private static function encode_if_encodable( $value ) {
		return ( is_array( $value ) or is_object( $value ) ) ? wp_json_encode( $value ) : $value;
	}


	/**
	 * @param WP_Error|string $message Either a string or a WP_Error object to handle/log.
	 * @param int $type
	 * @param mixed $return The value to return after triggering the error.
	 *
	 * @return Log|mixed
	 */
	private static function trigger( $message, $type, $return = '___instance___' ) {
		if ( is_wp_error( $message ) ) {
			$message = $message->get_error_message();
		}

		// todo - could check is dev environment and potentially throw exceptions here in that situation.
		trigger_error( $message, $type );

		return $return === '___instance___' ? new self : $return;
	}


}