<?php


namespace WpLandingKit\Http;


use WP;
use WpLandingKit\Framework\Traits\DotNotatedArraySupport;
use WpLandingKit\Framework\Utils\Url;
use WpLandingKit\Framework\Utils\Str;
use WpLandingKit\WordPress\Site;


/**
 * CFW
 *
 * Class Request
 * @package WpLandingKit\DomainIntercept
 */
class Request {


	use DotNotatedArraySupport;


	/**
	 * Our custom query var namespace. Our own custom query variables are all bundled in under this one which is
	 * captured during the `parse_request` action hook and is accessible using the self::get_wplk_var() method.
	 */
	const WPLK_QUERY_VAR = 'wplk';


	/**
	 * @var Site
	 */
	private $site;


	/**
	 * @var Server
	 */
	private $server;


	/**
	 * @var array Landing Kit specific variables.
	 */
	private $wplk = [];


	/**
	 * @param Site $site
	 * @param Server $server
	 */
	public function __construct( Site $site, Server $server ) {
		$this->site = $site;
		$this->server = $server;
	}


	public function listen() {
		add_action( 'parse_request', [ $this, '_capture_wplk_variables' ], 1 );
	}


	/**
	 * Listen in on the `parse_request` action hook and process any WPLK specific variables.
	 *
	 * @param WP $wp
	 */
	public function _capture_wplk_variables( WP $wp ) {
		parse_str( $wp->matched_query, $query );

		if ( ! empty( $query[ self::WPLK_QUERY_VAR ] ) and is_array( $query[ self::WPLK_QUERY_VAR ] ) ) {
			$this->wplk = $query[ self::WPLK_QUERY_VAR ];
		}
	}


	/**
	 * Dot-notated accessor for array keys within the self::$wplk property.
	 *
	 * @param $key
	 * @param null $default
	 *
	 * @return array|mixed|null
	 */
	public function get_wplk_var( $key, $default = null ) {
		return $this->get( $this->wplk, $key, $default );
	}


	public function is_for_primary_domain() {
		return ( $this->site->host() === $this->host() );
	}


	public function host() {
		return Url::get_host( $this->server->http_host() );
	}


	public function path() {
		return parse_url( $this->server->request_uri(), PHP_URL_PATH );
	}


	/**
	 * Treats '/' as a non-path
	 *
	 * @return int
	 */
	public function has_path() {
		$path = Str::unleadingslashit( $this->path() );

		return strlen( $path );
	}


	public function query_string() {
		return $this->server->query_string();
	}


	public function protocol() {
		return is_ssl() ? 'https://' : 'http://';
	}


	public function full_url( $protocol = true ) {
		$url = '';

		if ( $protocol ) {
			$url .= $this->protocol();
		}

		$url .= $this->host();

		if ( $p = $this->path() ) {
			$url .= Str::leadingslashit( $p );
		}

		if ( $q = $this->query_string() ) {
			$url .= "?$q";
		}

		return $url;
	}


}