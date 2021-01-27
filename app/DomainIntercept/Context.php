<?php


namespace WpLandingKit\DomainIntercept;


use WP;
use WpLandingKit\Http\Request;
use WpLandingKit\Models\Domain;
use WpLandingKit\Utils\Redirect;
use WpLandingKit\WordPress\Site;


class Context {


	/**
	 * @var Domain
	 */
	private $domain;


	/**
	 * @var Request
	 */
	private $request;


	/**
	 * @var DomainReplacer
	 */
	private $replacer;


	/**
	 * @var Site
	 */
	private $site;


	/**
	 * @param Request $request
	 * @param DomainReplacer $replacer
	 * @param Site $site
	 */
	public function __construct( Request $request, DomainReplacer $replacer, Site $site ) {
		$this->request = $request;
		$this->replacer = $replacer;
		$this->site = $site;
	}


	/**
	 * Override the context to use a specified mapped domain.
	 *
	 * @param Domain $domain
	 */
	public function override( Domain $domain ) {
		$this->domain = $domain;
		$this->request->listen();
		add_filter( 'pre_option_rewrite_rules', [ $this, '_set_rewrite_rules' ] );
		add_action( 'parse_request', [ $this, '_handle_custom_query_variable_mapping' ] );
		add_filter( 'template_redirect', [ $this, '_handle_matched_redirect_rules' ] );
		// Remove the canonical redirect — this ensures we don't load up other random posts/pages that WordPress
		// resolves to when fielding requests on secondary domains.
		remove_filter( 'template_redirect', 'redirect_canonical' );

		$this->run_replacer();
	}


	/**
	 * Set the redirect rules for the given domain.
	 *
	 * @param array $rules
	 *
	 * @return array
	 */
	public function _set_rewrite_rules( $rules ) {
		return $this->domain->rewrite_rules();
	}


	/**
	 * Convert any custom query variables to their WP core equivalent.
	 *
	 * @param WP $wp
	 */
	public function _handle_custom_query_variable_mapping( WP $wp ) {
		// Taxonomies don't have a query var that will load their archive via the term ID. This isn't ideal as slugs can
		// change in the WP admin. So, to work around this, we have our own custom query vars in place that we can then
		// map on to the WordPress core request accordingly.
		if (
			$tax = $this->request->get_wplk_var( 'taxonomy' ) and
			$id = $this->request->get_wplk_var( 'term_id' ) and
			is_string( $slug = get_term_field( 'slug', $id, $tax ) )
		) {
			switch ( $tax ) {
				// Categories have specific handling
				case 'category':
					$wp->query_vars['category_name'] = $slug;
					break;
				// Tags have specific handling
				case 'post_tag':
					$wp->query_vars['tag'] = $slug;
					break;
				// Other taxonomies
				default:
					$wp->query_vars[ $tax ] = $slug;
			}

			return;
		}

	}


	/**
	 * If the request matches a rule that has been configured to redirect, handle the redirect.
	 */
	public function _handle_matched_redirect_rules() {
		if ( $this->request->get_wplk_var( 'redirect.to' ) ) {
			Redirect::to(
				$this->request->get_wplk_var( 'redirect.to' ),
				$this->request->get_wplk_var( 'redirect.status', 301 )
			);
		}
	}


	private function run_replacer() {
		$this->replacer->set_target_hosts( [ $this->site->host() ] );
		$this->replacer->set_new_host( $this->domain->host() );
		$this->replacer->set_protocol( $this->domain->protocol() );
		$this->replacer->run();
	}


}