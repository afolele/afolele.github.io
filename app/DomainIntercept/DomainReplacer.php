<?php


namespace WpLandingKit\DomainIntercept;


use WpLandingKit\Framework\Utils\Arr;
use WpLandingKit\Framework\Utils\Url;


class DomainReplacer {


	private $target_hosts = [];
	private $new_host;
	private $protocol = 'http://';


	/**
	 * An array of hosts that will be replaced. If a string is passed, it will converted to an array containing one host
	 * name.
	 *
	 * @param array|string $hosts
	 */
	public function set_target_hosts( $hosts ) {
		$this->target_hosts = Arr::wrap( $hosts );
	}


	/**
	 * @param mixed $host
	 */
	public function set_new_host( $host ) {
		$this->new_host = $host;
	}


	/**
	 * @param string $protocol The full protocol including ://
	 */
	public function set_protocol( $protocol ) {
		if ( in_array( $protocol, [ 'http', 'https' ] ) ) {
			$protocol .= '://';
		}

		$this->protocol = $protocol;
	}


	public function run() {
		if ( ! $this->new_host or ! $this->protocol or ! $this->target_hosts ) {
			trigger_error( __CLASS__ . ' class failed to run due to missing/empty parameter/s' );

			return;
		}

		add_filter( 'option_siteurl', [ $this, '_modify_url' ] );
		add_filter( 'option_home', [ $this, '_modify_url' ] );
		add_filter( 'script_loader_src', [ $this, '_modify_asset_url' ], 10, 2 );
		add_filter( 'style_loader_src', [ $this, '_modify_asset_url' ], 10, 2 );
		add_filter( 'stylesheet_directory_uri', [ $this, '_modify_url' ] );
		add_filter( 'template_directory_uri', [ $this, '_modify_url' ] );
		// todo - Look into replacing media source URLs as well.
		//add_filter( 'wp_get_attachment_image_src', [ $this, '_modify_url' ] );
	}


	/**
	 * Generic filter for handling URLs.
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	public function _modify_url( $url ) {
		return $this->replace_url( $url );
	}


	/**
	 * Note: Whilst implementation is currently identical to \WpLandingKit\DomainIntercept\DomainReplacer::_modify_url(),
	 * this provides a good place to offer a filter for bypassing assets based on their handle should we need to offer
	 * that capability.
	 *
	 * @param string $url
	 * @param string $handle
	 *
	 * @return string
	 */
	public function _modify_asset_url( $url, $handle ) {
		return $this->replace_url( $url );
	}


	/**
	 * The replacement handler.
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	private function replace_url( $url ) {
		if ( ! $this->url_has_targeted_host( $url ) ) {
			return $url;
		}

		if ( $new_url = Url::replace_host( $url, $this->new_host ) ) {
			return Url::set_protocol( $new_url, $this->protocol );
		}

		return $url;
	}


	/**
	 * Check if given URL has a host name that we need to replace. If no host name is found, return false.
	 *
	 * @param $url
	 *
	 * @return bool
	 */
	private function url_has_targeted_host( $url ) {
		if ( $host = Url::get_host( $url ) ) {
			return in_array( $host, $this->target_hosts );
		}

		return false;
	}


	/**
	 * @param $host
	 *
	 * @deprecated 1.1.3. Will be removed in version 1.3.
	 */
	public function set_host( $host ) {
		_deprecated_function(
			'\WpLandingKit\DomainIntercept\DomainReplacer::set_host()',
			'1.1.3',
			'\WpLandingKit\DomainIntercept\DomainReplacer::set_new_host()'
		);
		$this->set_new_host( $host );
	}


}