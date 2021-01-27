<?php


namespace WpLandingKit\Actions;


use WP_Error;


/**
 * Class SetCapabilities
 * @package WpLandingKit\Actions
 *
 * Sets all custom capabilities for managing domains by administrators.
 */
class SetCapabilities {


	public static $roles_and_caps = [
		'administrator' => [
			'edit_domain',
			'read_domain',
			'delete_domain',
			'create_domains',
			'edit_domains',
			'edit_others_domains',
			'delete_domains',
			'publish_domains',
			'read_private_domains',
			'delete_private_domains',
			'delete_published_domains',
			'delete_others_domains',
			'edit_private_domains',
			'edit_published_domains',
		]
	];


	/**
	 * @return true|WP_Error
	 */
	public static function run() {
		foreach ( self::$roles_and_caps as $role => $caps ) {

			if ( ! $role = get_role( $role ) ) {
				continue;
			}

			foreach ( $caps as $cap ) {
				$role->add_cap( $cap );
			}
		}

		return true;
	}


}