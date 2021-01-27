<?php


namespace WpLandingKit\Upgrade;


use Exception;
use WP_Error;
use WpLandingKit\Framework\Facades\App;
use WpLandingKit\WordPress\AdminNotices\UpgradePrompt;
use WpLandingKit\WordPress\AdminPages\UpgradePage;


class Upgrader {


	/** @var string[] Array of upgrade class names in order of newest to oldest. */
	private $classes = [];


	/** @var UpgradeBase[] Array of upgrade objects that are available. */
	private $upgrades = [];


	/** @var UpgradePrompt */
	private $notice;


	/** @var UpgradePage */
	private $page;


	/** @var DbVersion */
	private $db_version;


	/**
	 * @param UpgradePrompt $admin_notice
	 * @param UpgradePage $upgrade_page
	 * @param DbVersion $db_version
	 */
	public function __construct( UpgradePrompt $admin_notice, UpgradePage $upgrade_page, DbVersion $db_version ) {
		$this->notice = $admin_notice;
		$this->page = $upgrade_page;
		$this->db_version = $db_version;
	}


	public function set_classes( array $classes ) {
		$this->classes = $classes;
	}


	public function init() {
		$this->queue_upgrades();

		if ( empty( $this->upgrades ) ) {
			return;
		}

		// Set up and activate the upgrade page.
		$this->page->register_page();
		$this->page->set_upgrader( $this );
		$this->page->on_page_load( function ( UpgradePage $page ) {
			remove_all_actions( 'admin_notices' );
			add_action( 'admin_enqueue_scripts', function () {
				wp_enqueue_script( 'wp-landing-kit-upgrade' );
			} );
		} );

		// Set up and activate the upgrade notice/prompt.
		$this->notice->set_info_url( $this->page->info_url() );
		$this->notice->set_upgrade_url( $this->page->upgrade_url() );
		$this->notice->show();
	}


	/**
	 * @return UpgradeBase|null
	 */
	public function latest_upgrade() {
		foreach ( (array) $this->classes as $classname ) {
			if ( $upgrade = $this->resolve_upgrade( $classname ) ) {
				return $upgrade;
			}
		}

		return null;
	}


	/**
	 * @return UpgradeBase[]|[]
	 */
	public function upgrades() {
		return $this->upgrades ?: [];
	}


	/**
	 * Run an upgrade.
	 *
	 * @param UpgradeBase $upgrade
	 *
	 * @return string|WP_Error|null
	 */
	public function run( UpgradeBase $upgrade ) {
		try {
			$message = $upgrade->run();

			if ( ! $upgrade->is_ajax() ) {
				$this->update_db_version( $upgrade );
			}

		} catch ( Exception $e ) {
			$message = new WP_Error( 'wplk', $e->getMessage() );
		}

		return $message;
	}


	public function update_db_version( UpgradeBase $upgrade ) {
		$this->db_version->set( $upgrade->version() );
	}


	/**
	 * Instantiate all upgrade objects that need to be run and build the upgrade queue in the order that they need to be
	 * executed.
	 */
	private function queue_upgrades() {
		$current_version = $this->db_version->get();

		foreach ( $this->classes as $classname ) {

			if ( ! $upgrade = $this->resolve_upgrade( $classname ) ) {
				continue;
			}

			// Break out of the loop on the first object we find that is older than or equal to the current version.
			// This works fine as we are working backwards through our upgrades from newest to oldest to minimise how
			// many objects need to be instantiated and assessed.
			if ( $current_version >= $upgrade->version() ) {
				break;
			}

			if ( $upgrade->should_run() ) {

				if ( $ajax = $upgrade->ajax_handler() ) {
					$ajax->register();
				}

				array_unshift( $this->upgrades, $upgrade );
			}
		}
	}


	/**
	 * @param $classname
	 *
	 * @return UpgradeBase|null
	 */
	private function resolve_upgrade( $classname ) {
		/** @var UpgradeBase $upgrade */
		$upgrade = App::make( $classname );

		return ( $upgrade instanceof UpgradeBase ) ? $upgrade : null;
	}


}