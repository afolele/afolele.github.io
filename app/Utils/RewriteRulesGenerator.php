<?php


namespace WpLandingKit\Utils;


use WpLandingKit\Framework\Utils\Arr;
use WpLandingKit\Models\Domain;
use WpLandingKit\Http\Request;


/**
 * Class RewriteRulesGenerator
 * @package WpLandingKit\Utils
 *
 * Generates the rewrite rules for a given Domain based on the Domain object's settings.
 */
class RewriteRulesGenerator {


	const ROOT_MATCH = '$';
	const FALLBACK_MATCH = '^.+';

	// This does not currently take into consideration modifications to the pagination permalink structure.
	const PAGINATION_PART_MATCH = 'page/?([0-9]{1,})';


	/** @var Domain */
	private $domain = null;
	private $rules = [];


	/**
	 * @param Domain $domain
	 */
	public function __construct( Domain $domain ) {
		$this->domain = $domain;
	}


	public static function make( Domain $domain ) {
		$instance = new static( $domain );
		$instance->generate();

		return $instance->to_array();
	}


	public function to_array() {
		return $this->rules;
	}


	public function generate() {
		if ( empty( $this->domain->config( 'mappings', null ) ) ) {
			$this->rules = [];

			return;
		}

		$this->handle_root_mapping();
		$this->handle_dynamic_mappings();
		$this->handle_fallback_mapping();
	}


	private function handle_root_mapping() {
		if ( $m = $this->domain->root_mapping() ) {
			$query = $this->build_query( $m );
			$this->append_rule( self::ROOT_MATCH, $query );

			if ( $this->mapping_supports_pagination( $m ) ) {
				$this->append_rule( '^' . self::PAGINATION_PART_MATCH . '/?$', add_query_arg( 'paged', '$matches[1]', $query ) );
			}
		}
	}


	private function handle_dynamic_mappings() {
		foreach ( $this->domain->dynamic_mappings() as $m ) {
			$query = $this->build_query( $m );

			if ( $this->mapping_supports_pagination( $m ) ) {
				$this->append_rule(
					$this->build_paginated_match( $m ),
					add_query_arg( 'paged', '$matches[1]', $query ) );
			}

			$this->append_rule( $this->build_match( $m ), $query );
		}
	}


	private function handle_fallback_mapping() {
		if ( $m = $this->domain->fallback_mapping() ) {
			$query = $this->build_query( $m );

			if ( $this->mapping_supports_pagination( $m ) ) {
				$this->append_rule(
					self::FALLBACK_MATCH . '/' . self::PAGINATION_PART_MATCH . '/?$',
					add_query_arg( 'paged', '$matches[1]', $query ) );
			}

			$this->append_rule( self::FALLBACK_MATCH, $query );
		}
	}


	/**
	 * This could do with refinement as it could also pay to check if the mapped resources support pagination or not.
	 *
	 * @param array $mapping
	 *
	 * @return bool
	 */
	private function mapping_supports_pagination( array $mapping ) {
		if ( 'map_to_resource' !== Arr::get( $mapping, 'action' ) ) {
			return false;
		}

		$type_is_supported = in_array( Arr::get( $mapping, 'resource_type' ), [
			'post-type-archive',
			'taxonomy-term-archive'
		] );

		if ( ! $type_is_supported ) {
			return false;
		}

		return Arr::get( $mapping, 'do_pagination', false );
	}


	private function build_match( array $mapping ) {
		// If the URL is empty, there isn't a match to be built.
		if ( empty( $url_path = Arr::get( $mapping, 'url_path', '' ) ) ) {
			return '';
		}

		// If the mapping is marked as regex, don't modify it aside from adding pagination support.
		if ( Arr::get( $mapping, 'is_regex', false ) ) {
			return $url_path;
		}

		// If we are this far in, regex isn't being used so format the string accordingly.
		// Note: if we need to process match strings further, this is where we would handle it.
		$match = untrailingslashit( $url_path );

		// todo - evaluate whether we should enforce the optional trailing slash
		return "^$match/?$";
	}


	private function build_paginated_match( array $mapping ) {
		// If the URL is empty, there isn't a match to be built.
		if ( empty( $url_path = Arr::get( $mapping, 'url_path', '' ) ) ) {
			return '';
		}

		// If the mapping is marked as regex, don't modify it aside from adding pagination support.
		if ( Arr::get( $mapping, 'is_regex', false ) ) {
			return rtrim( $url_path, '/$' ) . '/' . self::PAGINATION_PART_MATCH;
		}

		// If we are this far in, regex isn't being used so format the string accordingly.
		// Note: if we need to process match strings further, this is where we would handle it.
		$match = untrailingslashit( $url_path ) . '/' . self::PAGINATION_PART_MATCH;

		// todo - evaluate whether we should enforce the optional trailing slash
		return "^$match/?$";
	}


	private function build_query( array $mapping ) {
		$action = Arr::get( $mapping, 'action', 'map_to_resource' );

		switch ( $action ) {
			case 'redirect':
				$args = $this->get_redirect_args( $mapping );
				break;
			case 'map_to_resource':
				$args = $this->get_rewrite_args( $mapping );
				break;
			default:
				$args = [];
		}

		return empty( $args ) ? '' : add_query_arg( $args, 'index.php' );
	}


	private function get_redirect_args( array $mapping ) {
		$url = Arr::get( $mapping, 'redirect_url' );
		$status = Arr::get( $mapping, 'redirect_status', 301 );

		if ( empty( $url ) || empty( $status ) ) {
			return [];
		}

		return [
			Request::WPLK_QUERY_VAR => [
				'redirect' => [ 'to' => $url, 'status' => $status, ]
			]
		];
	}


	private function get_rewrite_args( array $mapping ) {
		$args = [];

		switch ( Arr::get( $mapping, 'resource_type' ) ) {
			case 'single-post':
				$args['p'] = Arr::get( $mapping, 'p' );
				$args['post_type'] = Arr::get( $mapping, 'post_type' );
				break;

			case 'single-page':
				$args['page_id'] = Arr::get( $mapping, 'page_id' );
				break;

			case 'post-type-archive':
				$args['post_type'] = Arr::get( $mapping, 'post_type' );
				break;

			case 'taxonomy-term-archive':
				$args[ Request::WPLK_QUERY_VAR ]['taxonomy'] = Arr::get( $mapping, 'taxonomy' );
				$args[ Request::WPLK_QUERY_VAR ]['term_id'] = Arr::get( $mapping, 'term_id' );
				break;
		}

		return $args;
	}


	/**
	 * Appends a rule to the rules array provided all necessary data points are available to do so.
	 *
	 * @param $regex
	 * @param $query
	 */
	private function append_rule( $regex, $query ) {
		if ( $regex and $query ) {
			$this->rules[ $regex ] = $query;
		}
	}


}