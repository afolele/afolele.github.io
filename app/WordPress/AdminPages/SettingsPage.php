<?php


namespace WpLandingKit\WordPress\AdminPages;


use WpLandingKit\Framework\Utils\Arr;
use WpLandingKit\Settings;
use WpLandingKit\View\AdminView;
use WP_Post_Type;


class SettingsPage {


	const PAGE_SLUG = 'wp_landing_kit_plugin_settings';


	const GROUP_GENERAL = 'general';
	const GROUP_DOMAIN_GLOBAL = 'domain-global';
	const GROUP_LICENSE = 'license';


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
		add_action( 'admin_init', [ $this, '_register_settings' ] );
		add_action( 'admin_menu', [ $this, '_register_settings_page' ] );
	}


	public function _register_settings() {
		$this->register_settings_sections();
		$this->register_settings_fields();
	}


	public function _register_settings_page() {
		add_options_page(
			__( 'WP Landing Kit', 'wp-landing-kit' ),
			__( 'WP Landing Kit', 'wp-landing-kit' ),
			'manage_options',
			self::PAGE_SLUG,
			function () {
				AdminView::render( 'settings-page', [
					'page_slug' => self::PAGE_SLUG,
					'option_group' => $this->settings->option_group(),
				] );
			}
		);
	}


	private function register_settings_sections() {
		add_settings_section(
			$this->settings->option_group() . '-' . self::GROUP_LICENSE,
			__( 'License Settings', 'wp-landing-kit' ),
			function ( $section ) {
				echo '<p class="description">To receive updates and support, enter your license key below.</p>';
			},
			self::PAGE_SLUG
		);

		add_settings_section(
			$this->settings->option_group() . '-' . self::GROUP_GENERAL,
			__( 'General Settings', 'wp-landing-kit' ),
			function ( $section ) {
			},
			self::PAGE_SLUG
		);

		add_settings_section(
			$this->settings->option_group() . '-' . self::GROUP_DOMAIN_GLOBAL,
			__( 'Global Domain Settings', 'wp-landing-kit' ),
			function ( $section ) {
				echo '<p class="description">Global settings for domains. Domain-level settings take precedence over these.</p>';
			},
			self::PAGE_SLUG
		);
	}


	private function register_settings_fields() {
		$fields = $this->settings->fields();

		foreach ( $fields as $field ) {
			add_settings_field(
				Arr::get( $field, 'id' ),
				Arr::get( $field, 'title' ),
				function ( $args ) use ( $field ) {

					// This will, ideally, be moved to a system/framework at some stage
					$field = $this->apply_dynamic_option_mods( $field );

					$type = Arr::get( $field, 'type' );

					AdminView::render( "settings-fields/{$type}", [
						'field' => $field,
						'option_name' => $this->settings->option_name(),
						'setting' => $this->settings->get( $field['id'] )
					] );
				},
				self::PAGE_SLUG,
				$this->settings->option_group() . '-' . Arr::get( $field, 'group' ),
				Arr::get( $field, 'args' )
			);
		}
	}


	private function apply_dynamic_option_mods( $field ) {
		if ( $field['id'] === 'mappable_post_types' ) {

			$public_types = array_filter( get_post_types( [ 'public' => true ], 'objects' ), function ( WP_Post_Type $type ) {
				return ! in_array( $type->name, [ 'page', 'post', 'attachment' ] );
			} );

			if ( ! $public_types ) {
				return $field;
			}

			/** @var WP_Post_Type $type */
			foreach ( $public_types as $type ) {
				$field['args']['options'][] = [
					'id' => $type->name,
					'class' => '',
					'key' => $type->name,
					'label' => $type->labels->singular_name,
				];
			}
		}

		return $field;
	}


}