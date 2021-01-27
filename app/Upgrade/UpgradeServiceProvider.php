<?php


namespace WpLandingKit\Upgrade;


use WpLandingKit\Events\PluginActivated;
use WpLandingKit\Facades\Events;
use WpLandingKit\Framework\Providers\ServiceProviderBase;
use WpLandingKit\Providers\AjaxServiceProvider;
use WpLandingKit\Upgrade\Upgrades;
use WpLandingKit\Upgrade\Upgrades\Ajax;
use WpLandingKit\WordPress\AdminNotices\UpgradePrompt;
use WpLandingKit\WordPress\AdminPages\UpgradePage;


/**
 * Class UpgradeServiceProvider
 * @package WpLandingKit\Upgrade
 */
class UpgradeServiceProvider extends ServiceProviderBase {


	/**
	 * Array of upgrade class names to run in order of newest to oldest. Each class MUST extend the UpgradeBase class.
	 *
	 * Note: AJAX handlers are registered in the \WpLandingKit\Providers\AjaxServiceProvider
	 *
	 * @var array e.g; [
	 *      SomeRecentUpgrade::class,
	 *      SomePreviousUpgrade::class,
	 *      SomeEarlyUpgrade::class,
	 *  ]
	 */
	private $upgrades = [
		Upgrades\VersionOneDotTwoCapabilityMods::class,
		Upgrades\VersionOneDotOneDataMigration::class,
	];


	public function register() {
		$this->app->singleton( UpgradeButtonView::class );
		$this->app->singleton( UpgradePrompt::class );
		$this->app->singleton( UpgradePage::class );
		$this->app->singleton( DbVersion::class );
		$this->app->singleton( DbVersionInitListener::class );

		$this->app->singleton( Upgrader::class, function () {
			$instance = new Upgrader(
				$this->app->make( UpgradePrompt::class ),
				$this->app->make( UpgradePage::class ),
				$this->app->make( DbVersion::class )
			);
			$instance->set_classes( $this->upgrades );

			return $instance;
		} );

		foreach ( $this->upgrades as $class ) {
			$this->app->singleton( $class );
		}

		Events::listen( PluginActivated::class, DbVersionInitListener::class );
	}


	public function boot() {
		add_action( 'admin_menu', [ $this->app->make( Upgrader::class ), 'init' ] );
	}


}