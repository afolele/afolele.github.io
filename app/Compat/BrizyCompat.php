<?php


namespace WpLandingKit\Compat;


use WpLandingKit\Settings;


/**
 * Class BrizyCompat
 * @package WpLandingKit\Compat
 *
 * Handle any compatibility needs to ensure functionality with Brizy.
 */
class BrizyCompat {


	/**
	 * @var Settings
	 */
	private $settings;


	/**
	 * @param Settings $settings
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}


	public function init() {
		if ( $this->is_brizy_running() ) {
			add_action( 'wp', [ $this, '_disable_redirect_to_mapped_domains_on_front_builder' ], 1 );
		}
	}


	public function is_brizy_running() {
		return defined( 'BRIZY_VERSION' );
	}


	public function is_brizy_preview_mode() {
		// Taken straight from Brizy. This is exactly how they internally detect their own editor so if things should
		// change at any point and redirects start breaking Brizy, we'll likely need to change this check.
		return isset( $_GET['brizy-edit'] ) || isset( $_GET['brizy-edit-iframe'] );
	}


	/**
	 * Disable redirects to mapped domains when loading a page within the context of Divi's front end page builder.
	 */
	public function _disable_redirect_to_mapped_domains_on_front_builder() {
		if ( $this->is_brizy_preview_mode() ) {
			$this->settings->set( 'redirect_mapped_urls_to_domain', false );
		}
	}


}