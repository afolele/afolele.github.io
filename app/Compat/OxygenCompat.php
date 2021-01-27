<?php


namespace WpLandingKit\Compat;


use WpLandingKit\Framework\Utils\Arr;
use WpLandingKit\Settings;


/**
 * Class OxygenCompat
 * @package WpLandingKit\Compat
 *
 * Handle any compatibility needs to ensure functionality with Oxygen.
 */
class OxygenCompat {


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
		if ( $this->is_oxygen_running() ) {
			add_action( 'wp', [ $this, '_disable_redirect_to_mapped_domains_on_front_builder' ], 1 );
		}
	}


	public function is_oxygen_running() {
		return defined( 'CT_VERSION' );
	}


	public function is_oxygen_preview_mode() {
		// Taken straight from Oxygen. This is exactly how they internally detect their own editor so if things should
		// change at any point and redirects start breaking Oxygen, we'll likely need to change this check.
		return isset( $_GET['ct_builder'] ) && $_GET['ct_builder'];
	}


	public function is_oxygen_ajax_action() {
		$action = Arr::get( $_GET, 'action', false );

		// There is potential for this to inadvertently disable redirects where some other plugin/site is using a URL
		// with a query string parameter starting with ct_. This is an edge-case, however, so we'll leave this open for
		// now. If this becomes a problem, we'll have to look at some more precise options for checking. Possibilities:
		// 1. Add a check to see if the `nonce` query param is also set. We could potentially also check for `post_id`.
		// 2. Round up all of Oxygen's AJAX action names and checking explicitly for them here.
		// Note, the the wp_doing_ajax() function doesn't work here as the request is made using WP's AJAX API.
		return $action and (strpos( $action, 'ct_' ) === 0 or strpos( $action, 'oxy_' ) === 0);
	}


	/**
	 * Disable redirects to mapped domains when loading a page within the context of Divi's front end page builder.
	 */
	public function _disable_redirect_to_mapped_domains_on_front_builder() {
		if ( $this->is_oxygen_preview_mode() or $this->is_oxygen_ajax_action() ) {
			$this->settings->set( 'redirect_mapped_urls_to_domain', false );
		}
	}


}