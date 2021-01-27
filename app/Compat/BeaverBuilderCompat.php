<?php


namespace WpLandingKit\Compat;


use WpLandingKit\Settings;


/**
 * Class BeaverBuilderCompat
 * @package WpLandingKit\Compat
 *
 * Handle any compatibility needs to ensure functionality with Beaver Builder.
 */
class BeaverBuilderCompat {


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
		if ( $this->is_beaver_builder_running() ) {
			add_action( 'wp', [ $this, '_disable_redirect_to_mapped_domains_on_front_builder' ], 1 );
		}
	}


	public function is_beaver_builder_running() {
		return defined( 'FL_BUILDER_VERSION' );
	}


	public function is_beaver_builder_preview_mode() {
		// This is taken directly from the Beaver Builder plugin. This is the same check used throught the plugin to
		// initialise various compatibility handlers so this should be pretty stable. If we start to break on Beaver
		// Builder, then we'll likely need to update this.
		return isset( $_GET['fl_builder'] );
	}


	/**
	 * Disable redirects to mapped domains when loading a page within the context of Divi's front end page builder.
	 */
	public function _disable_redirect_to_mapped_domains_on_front_builder() {
		if ( $this->is_beaver_builder_preview_mode() ) {
			$this->settings->set( 'redirect_mapped_urls_to_domain', false );
		}
	}


}