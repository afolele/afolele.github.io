<?php


namespace WpLandingKit\Compat;


use WpLandingKit\Settings;


/**
 * Class WpPreviewCompat
 * @package WpLandingKit\Compat
 *
 * Handle any compatibility needs to ensure functionality with the WordPress Customizer.
 */
class WpPreviewCompat {


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
		if ( is_preview() or is_customize_preview() ) {
			$this->settings->set( 'redirect_mapped_urls_to_domain', false );
		}
	}


}