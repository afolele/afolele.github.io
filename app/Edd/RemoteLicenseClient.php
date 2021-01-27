<?php


namespace WpLandingKit\Edd;


use Exception;


/**
 * Class RemoteLicenseClient
 * @package WpLandingKit\Edd
 *
 * Handles license validation, activation, and deactivation against the remote site.
 */
class RemoteLicenseClient {


	/**
	 * @var string The URL of the remote store.
	 */
	private $store_url = '';


	/**
	 * @var string The name of the product in the remote store.
	 */
	private $item_name = '';


	/**
	 * @var string The issued license key.
	 */
	private $license_key = '';


	/**
	 * @param string $store_url
	 */
	public function set_store_url( $store_url ) {
		$this->store_url = $store_url;
	}


	/**
	 * @param string $item_name
	 */
	public function set_item_name( $item_name ) {
		$this->item_name = $item_name;
	}


	/**
	 * @param string $license_key
	 */
	public function set_license_key( $license_key ) {
		$this->license_key = $license_key;
	}


	/**
	 * Check the validity of the license key on the remote site.
	 *
	 * @param string $license_key Specific license to check. Defaults to $this->license_key.
	 *
	 * @throws Exception
	 */
	public function validate( $license_key = '' ) {
		$response = wp_remote_post( $this->store_url, [
			'timeout' => 15,
			'sslverify' => false,
			'body' => [
				'edd_action' => 'check_license',
				'license' => $this->license_key_or_die( $license_key ),
				'item_name' => urlencode( $this->item_name ),
				'url' => home_url()
			]
		] );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();

		} else {
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			if ( $license_data->license !== 'valid' ) {
				$error_message = __( 'License key is invalid.' );
			}
		}

		if ( ! empty( $error_message ) ) {
			throw new Exception( $error_message );
		}
	}


	/**
	 * Activate the license key on the remote site.
	 *
	 * @param string $license_key Specific license to check. Defaults to $this->license_key.
	 *
	 * @throws Exception
	 */
	public function activate( $license_key = '' ) {
		$response = wp_remote_post( $this->store_url, [
				'timeout' => 15,
				'sslverify' => false,
				'body' => [
					'edd_action' => 'activate_license',
					'license' => $this->license_key_or_die( $license_key ),
					'item_name' => urlencode( $this->item_name ),
					'url' => home_url()
				]
			]
		);

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();

		} elseif ( 200 !== ( $code = wp_remote_retrieve_response_code( $response ) ) ) {
			$error_message = sprintf( __( 'A %d error occurred during the remote request, please try again.' ), $code );

		} else {
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			if ( false === $license_data->success ) {
				switch ( $license_data->error ) {
					case 'expired' :
						$error_message = sprintf(
							__( 'Your license key expired on %s.' ),
							date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
						);
						break;
					case 'revoked' :
						$error_message = __( 'Your license key has been disabled.' );
						break;
					case 'missing' :
						$error_message = __( 'Invalid license.' );
						break;
					case 'invalid' :
					case 'site_inactive' :
						$error_message = __( 'Your license is not active for this URL.' );
						break;
					case 'item_name_mismatch' :
						$error_message = sprintf( __( 'This appears to be an invalid license key for %s.' ), $this->item_name );
						break;
					case 'no_activations_left':
						$error_message = __( 'Your license key has reached its activation limit.' );
						break;
					default :
						$error_message = __( 'An error occurred, please try again.' );
						break;
				}
			}
		}

		if ( ! empty( $error_message ) ) {
			throw new Exception( $error_message );
		}
	}


	/**
	 * Deactivate the license key on the remote site.
	 *
	 * @param string $license_key Specific license to check. Defaults to $this->license_key.
	 *
	 * @throws Exception
	 */
	public function deactivate( $license_key = '' ) {
		$response = wp_remote_post( $this->store_url, [
				'timeout' => 15,
				'sslverify' => false,
				'body' => [
					'edd_action' => 'deactivate_license',
					'license' => $this->license_key_or_die( $license_key ),
					'item_name' => urlencode( $this->item_name ),
					'url' => home_url()
				]
			]
		);

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();

		} elseif ( 200 !== ( $code = wp_remote_retrieve_response_code( $response ) ) ) {
			$error_message = sprintf( __( 'A %d error occurred during the remote request, please try again.' ), $code );

		} else {
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			if ( $license_data->license !== 'deactivated' ) {
				$error_message = __( 'License key failed to deactivate.' );
			}
		}

		if ( ! empty( $error_message ) ) {
			throw new Exception( $error_message );
		}
	}


	/**
	 * Return a license key to use in API requests or throw an exception.
	 *
	 * @param string $license_key
	 *
	 * @return string
	 * @throws Exception
	 */
	private function license_key_or_die( $license_key = '' ) {
		if ( $license_key ) {
			return $license_key;
		}

		if ( $this->license_key ) {
			return $this->license_key;
		}

		throw new Exception( 'No license key available. Either set the license key on the object or pass a license key to the method called.' );
	}


}