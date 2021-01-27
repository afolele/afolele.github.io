<?php


namespace WpLandingKit\DomainIntercept;


use WpLandingKit\Framework\Utils\Arr;
use WpLandingKit\Models\Domain;


class DomainMap {


	/**
	 * @var array [
	 *      'domains' => [
	 *          '{domain_post_id}' => 'mydomain.com',
	 *          '{domain_post_id}' => 'mydomain2.com',
	 *      ],
	 *      'posts' => [
	 *          '{post_id}' => [
	 *              '{domain_post_id}:/some/url',
	 *              '{domain_post_id}:/some/other/url',
	 *          ],
	 *      ],
	 *      'terms' => [
	 *          '{term_id}' => [
	 *              '{domain_post_id}:/some/url',
	 *              '{domain_post_id}:/some/other/url',
	 *          ],
	 *      ],
	 *      'post_type_archives' => [
	 *          '{post_type}' => [
	 *              '{domain_post_id}:/some/url'
	 *              '{domain_post_id}:/some/other/url'
	 *          ],
	 *      ]
	 * ]
	 */
	private $map = [
		'domains' => [],
		'posts' => [],
		'terms' => [],
		'post_type_archives' => [],
	];


	/**
	 * @var array Track whether or not mappings have been loaded.
	 */
	private $loaded = [
		'domains' => false,
		'posts' => false,
		'terms' => false,
		'post_type_archives' => false,
	];


	public function init() {
		$this->load_domain_mappings();

		// At this stage, we only need these in the admin.
		if ( is_admin() ) {
			$this->load_post_mappings();
			$this->load_term_mappings();
		}
	}


	public function load_domain_mappings() {
		$this->load_mappings_for( 'domains' );
	}


	public function load_post_mappings() {
		$this->load_mappings_for( 'posts' );
	}


	public function load_term_mappings() {
		$this->load_mappings_for( 'terms' );
	}


	public function load_post_type_archive_mappings() {
		$this->load_mappings_for( 'post_type_archives' );
	}


	public function reset() {
		$this->map = [
			'domains' => [],
			'posts' => [],
			'terms' => [],
			'post_type_archives' => [],
		];
		$this->loaded = [
			'domains' => false,
			'posts' => false,
			'terms' => false,
			'post_type_archives' => false,
		];
	}


	public function update_domain( Domain $domain ) {
		$this->unmap_domain( $domain );

		if ( $domain->is_active() ) {
			$this->map_domain( $domain );
		}
	}


	public function get_domain_id( $host_name ) {
		if ( $domains = Arr::get( $this->map, 'domains', [] ) ) {
			return Arr::get( array_flip( $domains ), $host_name, null );
		}

		return null;
	}


	public function save() {
		update_option( 'wplk-map-domains', $this->map['domains'], true );
		update_option( 'wplk-map-posts', $this->map['posts'], false );
		update_option( 'wplk-map-terms', $this->map['terms'], false );
		update_option( 'wplk-map-post_type_archives', $this->map['post_type_archives'], false );
	}


	public function get_first_url_for_post_id( $id ) {
		if ( $mappings = Arr::get_deep( $this->map, "posts.$id", [] ) ) {
			return $this->resolve_url_for_mapping( $mappings[0] );
		}

		return '';
	}


	/**
	 * Resolve all mapped URLs for a given post ID.
	 *
	 * @param int $id The post ID.
	 *
	 * @return array An array of mapped URLs.
	 */
	public function get_urls_for_post_id( $id ) {
		if ( ! $mappings = Arr::get_deep( $this->map, "posts.$id", [] ) ) {
			return [];
		}

		return array_filter(
			array_map( [ $this, 'resolve_url_for_mapping' ], $mappings )
		);
	}


	public function get_first_url_for_term_id( $id ) {
		if ( $mappings = Arr::get_deep( $this->map, "terms.$id", [] ) ) {
			return $this->resolve_url_for_mapping( $mappings[0] );
		}

		return '';
	}


	public function get_first_url_for_post_type_archive( $type ) {
		if ( $mappings = Arr::get_deep( $this->map, "post_type_archives.$type", [] ) ) {
			return $this->resolve_url_for_mapping( $mappings[0] );
		}

		return '';
	}


	/**
	 * Load up mappings for a given type provided they haven't already been loaded.
	 *
	 * @param string $type One of the keys within $this->map.
	 */
	private function load_mappings_for( $type ) {
		if ( $this->loaded[ $type ] ) {
			return;
		}

		$this->map[ $type ] = get_option( "wplk-map-{$type}", [] );
		$this->loaded[ $type ] = true;
	}


	/**
	 * Convert the mapped string — e.g; '{domain_post_id}:/some/url' — to a full URL
	 *
	 * @param string $mapping
	 *
	 * @return string
	 */
	private function resolve_url_for_mapping( $mapping ) {
		$mapping = explode( ':', $mapping );
		$domain_id = $mapping[0];
		$path = $mapping[1];
		$host = $this->map['domains'][ $domain_id ];

		if ( $domain = Domain::find( $domain_id ) ) {
			return $domain->protocol() . $host . $path;
		}

		return '';
	}


	private function map_domain( Domain $domain ) {
		$this->map['domains'][ $domain->ID ] = $domain->host();

		foreach ( $domain->mappings() as $m ) {
			if ( Arr::get( $m, 'action' ) !== 'map_to_resource' ) {
				continue;
			}

			switch ( Arr::get( $m, 'resource_type' ) ) {
				case 'single-post':
					$id = Arr::get( $m, 'p' );
					$path = Arr::get( $m, 'url_path', '' );
					if ( $id ) {
						$this->map['posts'][ $id ][] = "$domain->ID:/$path";
					}
					break;

				case 'single-page':
					$id = Arr::get( $m, 'page_id' );
					$path = Arr::get( $m, 'url_path', '' );
					if ( $id ) {
						$this->map['posts'][ $id ][] = "$domain->ID:/$path";
					}
					break;

				case 'taxonomy-term-archive':
					$id = Arr::get( $m, 'term_id' );
					$path = Arr::get( $m, 'url_path', '' );
					if ( $id ) {
						$this->map['terms'][ $id ][] = "$domain->ID:/$path";
					}
					break;

				case 'post-type-archive':
					$type = Arr::get( $m, 'post_type' );
					$path = Arr::get( $m, 'url_path', '' );
					if ( $type ) {
						$this->map['post_type_archives'][ $type ][] = "$domain->ID:/$path";
					}
					break;
			}

		}
	}


	private function unmap_domain( Domain $domain ) {
		if ( ! $this->has_domain_mapped( $domain ) ) {
			return;
		}

		// Remove the domain mapping itself.
		unset( $this->map['domains'][ $domain->ID ] );

		// Inline utility for stripping. This is fine for now but we should tidy up later.
		$strip_from = function ( $key ) use ( $domain ) {
			foreach ( $this->map[ $key ] as $id => $urls ) {
				// Filter out mappings that start with the domain ID we are removing.
				$remaining = array_filter( $urls, function ( $url ) use ( $domain ) {
					$m = explode( ':', $url );

					return $m[0] != $domain->ID;
				} );
				// if there are any remaining mappings, leave them, otherwise unset the post ID from the mappings.
				if ( $remaining ) {
					$this->map[ $key ][ $id ] = $remaining;
				} else {
					unset( $this->map[ $key ][ $id ] );
				}
			}
		};

		// Remove mappings.
		$strip_from( 'posts' );
		$strip_from( 'terms' );
		$strip_from( 'post_type_archives' );
	}


	private function has_domain_mapped( Domain $domain ) {
		return Arr::get_deep( $this->map, "domains.{$domain->ID}", false );
	}


}