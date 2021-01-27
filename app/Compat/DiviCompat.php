<?php


namespace WpLandingKit\Compat;


use WpLandingKit\Settings;
use function et_core_is_fb_enabled;


/**
 * Class DiviCompat
 * @package WpLandingKit\Compat
 *
 * Handle any Divi-related compatibility
 */
class DiviCompat {


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
		add_action( 'wp', [ $this, '_disable_redirect_to_mapped_domains_on_front_builder' ], 1 );
	}


	/**
	 * Disable redirects to mapped domains when loading a page within the context of Divi's front end page builder.
	 */
	public function _disable_redirect_to_mapped_domains_on_front_builder() {
		if ( function_exists( 'et_core_is_fb_enabled' ) and et_core_is_fb_enabled() ) {
			$this->settings->set( 'redirect_mapped_urls_to_domain', false );
		}
	}


}