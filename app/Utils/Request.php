<?php


namespace WpLandingKit\Utils;


use WpLandingKit\Framework\Utils\Arr;


class Request {


	public static function pull( $key, $default = null ) {
		return Arr::pull( $_REQUEST, $key, $default );
	}


	public static function get( $key, $default = null ) {
		return Arr::get_deep( $_REQUEST, $key, $default );
	}


	public static function all() {
		return $_REQUEST;
	}


	public static function get_post( $key, $default = null ) {
		return Arr::get_deep( $_POST, $key, $default );
	}


	public static function get_get( $key, $default = null ) {
		return Arr::get_deep( $_GET, $key, $default );
	}


}