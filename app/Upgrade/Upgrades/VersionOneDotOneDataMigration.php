<?php


namespace WpLandingKit\Upgrade\Upgrades;


use Exception;
use WpLandingKit\DomainIntercept\DomainMap;
use WpLandingKit\Framework\Facades\App;
use WpLandingKit\Models\Domain;
use WpLandingKit\Models\Post;
use WpLandingKit\PostTypes\MappedDomainPostType;
use WpLandingKit\Upgrade\UpgradeBase;
use WpLandingKit\View\AdminView;


class VersionOneDotOneDataMigration extends UpgradeBase {


	protected $ajax_handler = Ajax\VersionOneDotOneDataMigration::class;


	/**
	 * Return the version number of this upgrade. Use the current UNIX timestamp for this. Most readable approach is to
	 * use PHP's `strtotime()` function with a human-readable date. e.g; `strtotime( '1 January 2020' )`.
	 *
	 * @return integer
	 */
	public function version() {
		return strtotime( '17 March 2020' );
	}


	/**
	 * Run the upgrade and report on status.
	 *
	 * @return null|string Null or HTML markup on success.
	 * @throws Exception on failure.
	 */
	public function run() {
		AdminView::render( 'WplkAjaxUpgrade', [ 'ajax' => $this->ajax_handler() ] );

		return null;
	}


	/**
	 * The upgrade title.
	 *
	 * @return string
	 */
	public function title() {
		return __( 'Migrate domain map data to new structure.', 'wp-landing-kit' );
	}


	public function explanation() {
		ob_start();
		?>
        <p>
			<?php _e( 'In order to support the new levels of flexibility available in version 1.1, the domain mapping data needs to
            be restructured. The following actions will be performed during this upgrade:', 'wp-landing-kit' ) ?>
        </p>
        <ul>
            <li><?php _e( 'Move domain post meta to a data structure within the domain post content.', 'wp-landing-kit' ) ?></li>
            <li><?php _e( 'Remove irrelevant meta from mapped post objects.', 'wp-landing-kit' ) ?></li>
            <li><?php _e( 'Build a domain map used for faster domain lookups.', 'wp-landing-kit' ) ?></li>
        </ul>
		<?php
		return ob_get_clean();
	}


	public function query_eligible_domains( $args = [] ) {
		return new \WP_Query( wp_parse_args( $args, [
			'post_type' => MappedDomainPostType::POST_TYPE,
			'post_status' => 'any',
			'meta_query' => [
				// if the old meta data exists, the domain is eligible.
				'relation' => 'OR',
				[
					'key' => 'enforced_protocol',
					'compare' => 'EXISTS',
				],
				[
					'key' => 'mapped_post_id',
					'compare' => 'EXISTS',
				]
			]
		] ) );
	}


	public function migrate_domain_post_meta( Domain $domain ) {
		// Only migrate data if the post doesn't already have mapping data in the new format. This ensures
		// we don't override a domain where a user has gone and saved/updated a domain.
		if (
			empty( $domain->post_content )
			and $mapped_id = get_post_meta( $domain->ID, 'mapped_post_id', true )
			    and $mapping = $this->build_mapping_for_post_id( $mapped_id )
		) {
			$mappings = [];

			// Root mapping
			$mappings[ $mapping['mapping_id'] ] = $mapping;

			// Fallback mapping (this is the default)
			$id = uniqid();
			$mappings[ $id ] = [
				'mapping_id' => $id,
				'action' => 'redirect',
				'redirect_url' => '/',
				'redirect_status' => '302',
			];
			$domain->set_mappings( $mappings );
		}

		if ( empty( $domain->post_content ) ) {
			$domain->set_enforced_protocol( get_post_meta( $domain->ID, 'enforced_protocol', true ) ?: 'none' );
		}

		$domain->save();
	}


	public function remove_domain_post_meta( Domain $domain ) {
		delete_post_meta( $domain->ID, 'mapped_post_id' );
		delete_post_meta( $domain->ID, 'enforced_protocol' );
	}


	public function remove_mapped_post_meta( Post $post ) {
		delete_post_meta( $post->ID, 'mapped_domain_id' );
	}


	public function rebuild_domain_map() {
		/** @var DomainMap $map */
		$map = App::make( DomainMap::class );
		$map->reset();
		$domains = Domain::all();
		foreach ( $domains as $domain ) {
			$map->update_domain( $domain );
		}
		$map->save();
	}


	private function build_mapping_for_post_id( $post_id ) {
		if ( ! $post = get_post( $post_id ) ) {
			return false;
		}

		$type = get_post_type( $post );
		$id = uniqid();

		if ( $type === 'page' ) {
			return [
				'mapping_id' => $id,
				'action' => 'map_to_resource',
				'resource_type' => 'single-page',
				'page_id' => $post_id,
				'redirect_url' => '',
				'redirect_status' => '301',
			];
		}

		return [
			'mapping_id' => $id,
			'action' => 'map_to_resource',
			'resource_type' => 'single-post',
			'post_type' => $type,
			'p' => $post_id,
			'redirect_url' => '',
			'redirect_status' => '301',
		];
	}


}