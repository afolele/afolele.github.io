<?php


namespace WpLandingKit\Compat;


use WpLandingKit\Settings;


/**
 * Class ElementorCompat
 * @package WpLandingKit\Compat
 *
 * Handle any compatibility needs to ensure functionality with Elementor.
 */
class ElementorCompat {


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
		if ( $this->is_elementor_running() ) {
			add_action( 'wp', [ $this, '_disable_redirect_to_mapped_domains_on_front_builder' ], 1 );
		}
	}


	public function is_elementor_running() {
		return defined( 'ELEMENTOR_VERSION' );
	}


	public function is_elementor_preview_mode() {
		if (
			! class_exists( '\Elementor\Plugin' ) or
			! property_exists( '\Elementor\Plugin', 'instance' ) or
			! property_exists( \Elementor\Plugin::$instance, 'preview' ) or
			! method_exists( \Elementor\Plugin::$instance->preview, 'is_preview_mode' )
		) {
			return false;
		}

		return \Elementor\Plugin::$instance->preview->is_preview_mode();
	}


	/**
	 * Disable redirects to mapped domains when loading a page within the context of Divi's front end page builder.
	 */
	public function _disable_redirect_to_mapped_domains_on_front_builder() {
		if ( $this->is_elementor_preview_mode() ) {
			$this->settings->set( 'redirect_mapped_urls_to_domain', false );
		}
	}


}