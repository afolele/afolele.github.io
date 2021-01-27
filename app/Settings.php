<?php


namespace WpLandingKit;


use WpLandingKit\Edd\RemoteLicenseClient;
use WpLandingKit\Exceptions\LicenseActivationException;
use WpLandingKit\Framework\Facades\App;
use WpLandingKit\Framework\Traits\DotNotatedArraySupport;
use WpLandingKit\Framework\Utils\Arr;


/**
 * Class Settings
 * @package WpLandingKit
 *
 * Manages all plugin settings in one options meta entry.
 */
class Settings {


	use DotNotatedArraySupport {
		DotNotatedArraySupport::get as get__dot_notated;
		DotNotatedArraySupport::set as set__dot_notated;
	}


	/**
	 * @var Framework\Config\Config
	 */
	private $config;


	/**
	 * @var array The plugin settings array as stored in the DB.
	 */
	private $settings = [];


	/**
	 * @param Framework\Config\Config $config
	 */
	public function __construct( Framework\Config\Config $config ) {
		$this->config = $config;
	}


	/**
	 * Load up the settings.
	 */
	public function init() {
		$this->settings = $this->ensure_structure( (array) $this->get_option_raw( [] ) );
		add_action( 'admin_init', [ $this, '_register_setting' ] );
	}


	public function get_option_raw( $default = [] ) {
		return get_option( $this->option_name(), $default );
	}


	/**
	 * Register the option
	 */
	public function _register_setting() {
		register_setting(
			$this->option_group(),
			$this->option_name(),
			[ 'sanitize_callback' => [ $this, '_sanitize_and_format_input' ] ]
		);
	}


	/**
	 * Get a setting from the settings array or return default value. Supports dot-notation for nested array access.
	 *
	 * @param string $name The name of the setting. This is the `id` in the field's definition array.
	 * @param null|mixed $default
	 *
	 * @return mixed|null
	 */
	public function get( $name, $default = null ) {
		return $this->get__dot_notated( $this->settings, $name, $default );
	}


	/**
	 * Set a setting on the settings array. Supports dot-notation for nested array access. This won't persist data to
	 * the database but is useful for overriding settings on the fly.
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function set( $name, $value ) {
		$this->set__dot_notated( $this->settings, $name, $value );
	}


	/**
	 * Return the entire settings data structure.
	 *
	 * @return array
	 */
	public function all() {
		return $this->settings;
	}


	public function option_name() {
		return $this->config->get( 'settings.option_name' );
	}


	public function option_group() {
		return $this->config->get( 'settings.option_group' );
	}


	public function fields() {
		return $this->config->get( 'settings.fields' );
	}


	/**
	 * Mutates form data on its way into the database.
	 *
	 * @param $value
	 *
	 * @return mixed
	 */
	public function _sanitize_and_format_input( $value ) {
		foreach ( $this->fields() as $field ) {
			$type = Arr::get( $field, 'type' );
			$id = Arr::get( $field, 'id' );

			// Note: if any other admin field types need formatting, this is where it should be done.

			// Reformat checkbox group settings data so the array is just an array of checked field names istead of the
			// default `field_name => 'on'` format.
			if ( $type === 'checkbox-group' ) {
				if ( ! isset( $value[ $id ] ) ) {
					$value[ $id ] = [];
				}

				if ( Arr::is_assoc( $value[ $id ] ) ) {
					$value[ $id ] = array_keys( $value[ $id ] );
				}
			}

			// Reformat binary settings so we don't fallback to defaults when an unchecked box is saved - this would
			// clear the option from the db meaning we couldn't ever have a false value if the default is true.
			elseif ( $type === 'binary' ) {
				if ( ! isset( $value[ $id ] ) ) {
					$value[ $id ] = 0;
				}

				if ( $value[ $id ] === 'on' ) {
					$value[ $id ] = 1;
				}
			}

			// Handle license key storage and activation.
			// If value is emtpy here, the license will be deactivated
			if ( $id === 'license_key' ) {
				$value = $this->manage_license( $value );
			}
		}

		return $value;
	}


	/**
	 * Ensure the expected settings are set with their default values if they aren't already in the data retrieved from
	 * the database.
	 *
	 * @param array $settings The settings array retrieved from the database.
	 *
	 * @return array
	 */
	private function ensure_structure( array $settings ) {
		foreach ( $this->fields() as $field ) {

			if ( ! $id = Arr::get( $field, 'id', false ) ) {
				continue;
			}

			if ( ! isset( $settings[ $id ] ) ) {
				$settings[ $id ] = Arr::get( $field, 'default', '' );

			}
		}

		return $settings;
	}


	/**
	 * Handle de/activation of license key and associated settings data points where necessary.
	 *
	 * @param array $value The incoming form data array.
	 *
	 * @return array The incoming form data array, modified where necessary.
	 */
	private function manage_license( $value ) {
		$id = 'license_key';
		$value[ $id ] = trim( $value[ $id ] );
		$is_already_active = $this->get( 'license_is_active', false );
		$stored_license_key = $this->get( $id );
		$incoming_license_key = $value[ $id ];
		$remove_and_deactivate = Arr::get( $value, 'remove_and_deactivate_license', false );

		// We don't need to store this as it is just an action
		unset( $value['remove_and_deactivate_license'] );

		// If user requested license deactivation, handle that
		if ( $remove_and_deactivate ) {
			try {
				$this->deactivate_license( $stored_license_key );
				$value['license_is_active'] = false;
				$value[ $id ] = '';
			} catch ( LicenseActivationException $e ) {
				$this->show_error( 'There was a problem activating that license key. Error reads: ' . $e->getMessage() );
			}

			return $value;
		}

		// If no incoming value and license is inactive, set license inactive state.
		if ( empty( $incoming_license_key ) and ! $is_already_active ) {
			$value['license_is_active'] = false;

			return $value;
		}

		// If no incoming value and license is active, try to deactivate.
		if ( empty( $incoming_license_key ) and $is_already_active ) {
			try {
				$this->deactivate_license( $stored_license_key );
				$value['license_is_active'] = false;
			} catch ( LicenseActivationException $e ) {
				$this->show_error( 'There was a problem deactivating that license key. Reason: ' . $e->getMessage() );
			}

			return $value;
		}

		// If the incoming value is different to the stored value or if the license isn't currently active, activate
		// the incoming license key.
		if ( ( $incoming_license_key !== $stored_license_key ) or ! $is_already_active ) {
			try {
				$this->activate_license( $incoming_license_key );
				$value['license_is_active'] = true;
				$this->show_success( 'License key successfully activated.' );
			} catch ( LicenseActivationException $e ) {
				$value['license_is_active'] = false;
				$this->show_error( 'There was a problem activating that license key. Reason: ' . $e->getMessage() );
			}

			return $value;
		}

		// If the incoming value matches the already stored value
		if ( $incoming_license_key === $stored_license_key ) {
			$value['license_is_active'] = true;
		}

		return $value;
	}


	private function show_error( $message ) {
		add_settings_error( $this->option_name(), '', $message, 'error' );
	}


	private function show_success( $message ) {
		add_settings_error( $this->option_name(), '', $message, 'success' );
	}


	/**
	 * @param $license_key
	 *
	 * @throws LicenseActivationException
	 */
	private function activate_license( $license_key ) {
		try {
			App::make( RemoteLicenseClient::class )->activate( $license_key );
		} catch ( \Exception $e ) {
			throw new LicenseActivationException( $e->getMessage() );
		}
	}


	/**
	 * @param $license_key
	 *
	 * @throws LicenseActivationException
	 */
	private function deactivate_license( $license_key ) {
		try {
			App::make( RemoteLicenseClient::class )->deactivate( $license_key );
		} catch ( \Exception $e ) {
			throw new LicenseActivationException( $e->getMessage() );
		}
	}


}