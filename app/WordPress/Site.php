<?php


namespace WpLandingKit\WordPress;


use WpLandingKit\Framework\Utils\Url;


/**
 * CFW
 *
 * Class Site
 * @package WpLandingKit\WordPress
 *
 * This takes snapshots of the site data
 */
class Site {


	private $site_url;
	private $home_url;
	private $is_ssl;


	/**
	 * Prime all data points on the object.
	 */
	public function prime() {
		$this->site_url();
		$this->home_url();
		$this->is_ssl();
	}


	/**
	 * Return the host name, without slashes, configured on the current WordPress install.
	 *
	 * @return string
	 */
	public function host() {
		return Url::get_host( $this->site_url() );
	}


	/**
	 * @return bool
	 */
	public function is_ssl() {
		if ( ! $this->is_ssl ) {
			$this->is_ssl = is_ssl();
		}

		return $this->is_ssl;
	}


	public function site_url() {
		if ( ! $this->site_url ) {
			$this->site_url = site_url();
		}

		return $this->site_url;
	}


	public function home_url() {
		if ( ! $this->home_url ) {
			$this->home_url = home_url();
		}

		return $this->home_url;
	}


}