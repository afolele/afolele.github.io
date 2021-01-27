<?php


namespace WpLandingKit\Utils;


class Redirect {


	public static function to( $url, $status = 302, $die = true, $x_redirect_by = 'WordPress/LandingKit' ) {
		$success = wp_redirect( $url, $status, $x_redirect_by );

		if ( $success && $die ) {
			die();
		}
	}


}