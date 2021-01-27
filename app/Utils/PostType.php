<?php


namespace WpLandingKit\Utils;


use WpLandingKit\Framework\Utils\Arr;


class PostType {


	/**
	 * @param string $type The name of a registered post type.
	 *
	 * @return string
	 */
	public static function get_name( $type ) {
		if ( $type_obj = get_post_type_object( $type ) ) {
			$labels = get_post_type_labels( $type_obj );

			return Arr::get( (array) $labels, 'singular_name' );
		}

		return '';
	}


}