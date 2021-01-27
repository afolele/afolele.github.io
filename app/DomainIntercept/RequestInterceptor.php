<?php


namespace WpLandingKit\DomainIntercept;


use WpLandingKit\Framework\Utils\Url;
use WpLandingKit\Http\Request;
use WpLandingKit\Models\Domain;
use WpLandingKit\Facades\Settings;
use WpLandingKit\Utils\Redirect;
use WpLandingKit\WordPress\Site;


class RequestInterceptor {


	/**
	 * @var Request
	 */
	private $request;


	/**
	 * @var Context
	 */
	private $context;


	/**
	 * @var Domain
	 */
	private $domain;


	/**
	 * @var Site
	 */
	private $site;


	/**
	 * @var DomainMap
	 */
	private $map;


	/**
	 * @param Request $request
	 * @param Context $context
	 * @param Site $site
	 * @param DomainMap $map
	 */
	public function __construct( Request $request, Context $context, Site $site, DomainMap $map ) {
		$this->request = $request;
		$this->context = $context;
		$this->site = $site;
		$this->map = $map;
	}


	/**
	 * Hook into WordPress and listen in for any requests that need handling.
	 */
	public function listen() {
		// Snapshot site data on the Site object so that we are always viewing the main site's data points, not the
		// modified context we end up setting in \WpLandingKit\DomainIntercept\RequestInterceptor::_intercept_mapped_domain()
		$this->site->prime();

		if ( ! is_admin() ) {
			add_action( 'init', [ $this, '_intercept_mapped_domain' ] );
			add_action( 'wp', [ $this, '_redirect_to_secondary_domains' ] );
		}
	}


	/**
	 * Detect the requested domain and set up the site accordingly where a configured domain is found.
	 */
	public function _intercept_mapped_domain() {
		// Don't process requests on the primary domain.
		if ( $this->request->is_for_primary_domain() ) {
			return;
		}

		if ( $domain_id = $this->map->get_domain_id( $this->request->host() ) ) {
			$this->domain = Domain::find( $domain_id );
		}

		if ( ! $this->domain or ! $this->domain->is_active() ) {
			/**
			 * Fire action when domain cannot be located. This fires when a request is made for a domain that hasn't
			 * been added in the WordPress admin. You can use this hook to redirect, log & track un-managed domains, etc.
			 */
			do_action( 'wp_landing_kit/unmapped_domain_requested', $this->request->host() );

			return;
		}

		$this->enforce_protocol();

		$this->context->override( $this->domain );
	}


	/**
	 * Intercept any requests coming in on the primary site domain for pages that have a mapped domain and redirect
	 * through to the first mapped domain found.
	 */
	public function _redirect_to_secondary_domains() {
		if ( ! $this->request->is_for_primary_domain() ) {
			return;
		}

		if ( ! Settings::get( 'redirect_mapped_urls_to_domain' ) ) {
			return;
		}

		// Redirect to first found mapped URL depending on the resource. We've chosen to defer loading type-specific
		// mappings to here as we don't need everything on the front end. This approach ensures the most efficiency we
		// can muster until we break mappings out into a custom DB table.
		if ( is_singular() ) {
			$this->map->load_post_mappings();
			$url = $this->map->get_first_url_for_post_id( get_queried_object_id() );

		} elseif ( is_post_type_archive() or is_home() ) {
			$this->map->load_post_type_archive_mappings();
			$url = $this->map->get_first_url_for_post_type_archive( get_post_type() );

		} elseif ( is_tax() ) {
			$this->map->load_term_mappings();
			$url = $this->map->get_first_url_for_term_id( get_queried_object_id() );

		} else {
			$url = false;
		}

		/**
		 * Filter redirect args. Use this filter in combination with WordPress' internal functions to override where
		 * the main site pages should be redirected to.
		 */
		$redirect = apply_filters( 'wp_landing_kit/mapped_redirect_args', [ 'url' => $url, 'status' => 301 ] );

		if ( isset( $redirect['url'] ) and $redirect['url'] ) {
			$status = empty( $redirect['status'] ) ? 301 : $redirect['status'];
			Redirect::to( $url, $status );
		}
	}


	/**
	 * If protocol is being enforced and the request's protocol is incorrect, redirect.
	 */
	private function enforce_protocol() {
		if ( $this->domain->protocol() === $this->request->protocol() ) {
			return;
		}

		$url = Url::replace_host( $this->request->full_url(), $this->domain->host() );

		$url = Url::set_protocol( $url, $this->get_redirect_protocol() );

		Redirect::to( $url );
	}


	/**
	 * Get the protocol needed for the redirect. If the domain has one, use that. If not, fallback to the request.
	 *
	 * @return string
	 */
	private function get_redirect_protocol() {
		return $this->domain->protocol() ?: $this->request->protocol();
	}


}