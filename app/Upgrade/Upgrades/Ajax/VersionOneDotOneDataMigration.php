<?php


namespace WpLandingKit\Upgrade\Upgrades\Ajax;


use WpLandingKit\Framework\Facades\App;
use WpLandingKit\Models\Domain;
use WpLandingKit\Upgrade\Upgrader;
use WpLandingKit\Upgrade\Upgrades;
use WpLandingKit\Utils\Request;


class VersionOneDotOneDataMigration extends UpgradeAjaxHandlerBase {


	protected $action = 'wplk_version_one_dot_one_data_migration';


	/** @var Upgrades\VersionOneDotOneDataMigration */
	private $upgrade;

	/** @var Upgrader */
	private $upgrader;


	protected function handle_priv() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_send_json_error( [
					'status' => 'failed',
					'response' => [
						'info' => __( 'You do not have permission to update plugins.', 'wp-landing-kit' ),
					],
					'next' => null,
				]
			);
		}

		$this->upgrade = App::make( Upgrades\VersionOneDotOneDataMigration::class );
		$this->upgrader = App::make( Upgrader::class );

		$stage = Request::get( 'stage', 'initial' );
		$json = $this->$stage();

		if ( $json['status'] === 'failed' ) {
			wp_send_json_error( $json );

		} else {
			wp_send_json_success( $json );
		}
	}


	private function initial() {
		$domains = $this->upgrade->query_eligible_domains( [
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'fields' => 'ids'
		] );

		$number_to_migrate = $domains->found_posts;

		if ( $number_to_migrate > 0 ) {
			return [
				'status' => 'in_progress', // done, in_progress, failed
				'response' => [
					'info' => sprintf( _n( 'Found %s domain to migrate.', 'Found %s domains to migrate.', $number_to_migrate, 'wp-landing-kit' ), $number_to_migrate ),
					'progress' => '1',
				],
				'next' => [
					'ajax_url' => $this->get_url(),
					'action' => $this->get_action(),
					'stage' => 'migrate_domains',
					'nonce' => $this->get_nonce(),
				],
			];

		} else {
			return [
				'status' => 'in_progress', // done, in_progress, failed
				'response' => [
					'info' => __( 'No domains to migrate. Updating DB version and wrapping up…', 'wp-landing-kit' ),
					'progress' => '99',
				],
				'next' => [
					'ajax_url' => $this->get_url(),
					'action' => $this->get_action(),
					'stage' => 'complete',
					'nonce' => $this->get_nonce(),
				],
			];
		}
	}


	private function migrate_domains() {
		// get a batch of eligible domains
		$domains = $this->upgrade->query_eligible_domains( [
			'update_post_term_cache' => false,
			'numberposts' => 10,
		] );

		$total = Request::get( 'additional.total', $domains->found_posts );
		$migrated = Request::get( 'additional.migrated', 0 );

		if ( $domains->have_posts() ) {
			foreach ( $domains->get_posts() as $post ) {
				$domain = Domain::make( $post );

				$this->upgrade->migrate_domain_post_meta( $domain );

				if ( $mapped_post = $domain->mapped_post() ) {
					$this->upgrade->remove_mapped_post_meta( $mapped_post );
				}

				$this->upgrade->remove_domain_post_meta( $domain );

				$migrated ++;
			}

			$progress = $migrated / $total * 100;
			if ( $progress > 98 ) {
				$progress = 98;
			}

			return [
				'status' => 'in_progress', // done, in_progress, failed
				'response' => [
					'info' => sprintf( __( '%s of %s domains migrated.', 'wp-landing-kit' ), $migrated, $total ),
					'progress' => $progress,
				],
				'next' => [
					'ajax_url' => $this->get_url(),
					'action' => $this->get_action(),
					'stage' => 'migrate_domains',
					'nonce' => $this->get_nonce(),
					'additional' => [
						'total' => $total,
						'migrated' => $migrated,
					],
				],
			];

		} else if ( $migrated ) { // No domains left to migrate but we have migrated some.
			return [
				'status' => 'in_progress', // done, in_progress, failed
				'response' => [
					'info' => sprintf( _n( '%s domain migrated. Rebuilding map cache…', '%s domains migrated. Rebuilding map cache…', $migrated, 'wp-landing-kit' ), $migrated ),
					'progress' => '98',
				],
				'next' => [
					'ajax_url' => $this->get_url(),
					'action' => $this->get_action(),
					'stage' => 'rebuild_cache',
					'nonce' => $this->get_nonce(),
					'additional' => [
						'total' => $total,
						'migrated' => $migrated,
					],
				],
			];

		} else { // No domains have been migrated at all.
			return [
				'status' => 'in_progress', // done, in_progress, failed
				'response' => [
					'info' => __( 'No domains to migrate. Updating DB version and wrapping up…', 'wp-landing-kit' ),
					'progress' => '99',
				],
				'next' => [
					'ajax_url' => $this->get_url(),
					'action' => $this->get_action(),
					'stage' => 'complete',
					'nonce' => $this->get_nonce(),
				],
			];
		}
	}


	private function rebuild_cache() {
		$this->upgrade->rebuild_domain_map();

		return [
			'status' => 'in_progress', // done, in_progress, failed
			'response' => [
				'info' => 'Map cache rebuilt. Updating DB version and wrapping up…',
				'progress' => '99',
			],
			'next' => [
				'ajax_url' => $this->get_url(),
				'action' => $this->get_action(),
				'stage' => 'complete',
				'nonce' => $this->get_nonce(),
			]
		];
	}


	private function complete() {
		$this->upgrader->update_db_version( $this->upgrade );

		return [
			'status' => 'done', // done, in_progress, failed
			'response' => [
				'info' => 'Migration is complete.',
				'progress' => '100',
			],
			'next' => null
		];
	}

}