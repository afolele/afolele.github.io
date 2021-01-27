<?php


namespace WpLandingKit\Utils;


/**
 * CFW
 *
 * Class Json
 *
 * Utility methods for morking with JSON.
 *
 * @package WpLandingKit\Utils
 */
class Json {


	/**
	 * Check to see if a string is JSON.
	 *
	 * @param $string
	 *
	 * @return bool
	 */
	public static function is_json( $string ) {
		json_decode( $string );

		return ( json_last_error() == JSON_ERROR_NONE );
	}


	/**
	 * @param mixed $data The value being encoded.
	 * @param int $options JSON encode option bitmask.
	 * @param int $depth Set the maximum depth. Must be greater than zero.
	 *
	 *
	 * @return false|string
	 * @link http://www.php.net/manual/en/function.json-encode.php
	 */
	public static function encode( $data, $options = 0, $depth = 512 ) {
		$data_encoded = function_exists( 'wp_json_encode' )
			? wp_json_encode( $data, $options, $depth )
			: json_encode( $data, $options, $depth );

		if ( JSON_ERROR_NONE !== json_last_error() ) {
			trigger_error( 'Failed to encode JSON. Error reads: ' . json_last_error_msg() );
		}

		return $data_encoded;
	}


	/**
	 * @param string $json JSON data to parse.
	 * @param bool $assoc When true, returned objects will be converted into associative arrays.
	 * @param int $depth User specified recursion depth.
	 * @param int $options Bitmask of JSON decode options.
	 *
	 * @return mixed
	 * @link http://www.php.net/manual/en/function.json-decode.php
	 */
	public static function decode( $json, $assoc = false, $depth = 512, $options = 0 ) {
		$json_decoded = json_decode( $json, $assoc, $depth, $options );

		if ( JSON_ERROR_NONE !== json_last_error() ) {
			trigger_error( 'Failed to decode JSON. Error reads: ' . json_last_error_msg() );
		}

		return $json_decoded;
	}


}