<?php


namespace WpLandingKit\Upgrade;


use WpLandingKit\Settings;


/**
 * Class DbVersionInitListener
 * @package WpLandingKit\Upgrade
 *
 * Handle initial storage of database version number. Facilitate the following scenarios:
 *  - User is activating plugin for the first time.
 *  - User is reactivating the plugin after the ugprade system has been added via a plugin update.
 *  - User is reactivating the plugin with the upgrade system already in place.
 */
class DbVersionInitListener {


	/** @var Upgrader */
	private $upgrader;


	/** @var Settings */
	private $settings;


	/** @var DbVersion */
	private $db_version;


	/**
	 * @param Upgrader $upgrader
	 * @param Settings $settings
	 * @param DbVersion $db_version
	 */
	public function __construct( Upgrader $upgrader, Settings $settings, DbVersion $db_version ) {
		$this->upgrader = $upgrader;
		$this->settings = $settings;
		$this->db_version = $db_version;
	}


	/**
	 * @param string $event
	 * @param array $payload
	 */
	public function handle( $event, $payload = [] ) {
		// If we already have a DB number in the database, we don't need to do anything here.
		if ( $this->already_initialised() ) {
			return;
		}

		// If reactivating with no DB version number in the DB, assume the upgrade system has been added during a plugin
		// update. Set DB version to 0 in this case to allow all available upgrades to run.
		if ( $this->is_reactivation() ) {
			$this->db_version->set(0);

			return;
		}

		// On new activations, there should be no database upgrades that need to run so set to either the latest
		// available upgrade number or 0 if there are none.
		$this->db_version->set( $this->get_latest_upgrade_version() );
	}


	private function already_initialised() {
		return null !== $this->db_version->get( null );
	}


	/**
	 * If we have some saved settings, we can assume this is a plugin reactivation.
	 *
	 * @return bool
	 */
	private function is_reactivation() {
		return null !== $this->settings->get_option_raw( null );
	}


	private function get_latest_upgrade_version() {
		if ( $latest = $this->upgrader->latest_upgrade() ) {
			return $latest->version();
		}

		return 0;
	}


}