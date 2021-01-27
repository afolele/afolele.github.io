<?php


namespace WpLandingKit;


use WpLandingKit\Framework\AutoLoader;


/**
 * Class Plugin
 * @package WpLandingKit
 *
 * An isolated static class for starting up the plugin with sanity checks, error notice hooking when the plugin can't
 * run, and an action hook for extending the plugin.
 */
class Plugin {


	/**
	 * @var Framework\Container\Plugin The main plugin instance
	 */
	private static $instance;


	/**
	 * @var bool Whether or not static::run_checks() has been run
	 */
	private static $has_run_checks = false;


	/**
	 * @var array An array of pre-init check failures
	 */
	private static $check_failures = [];


	/**
	 * @var bool Whether or not the plugin has started
	 */
	private static $has_started = false;


	/**
	 * Turn the key. This is the only thing we need to call from the main plugin file to run the plugin.
	 */
	public static function start() {

		if ( static::$has_started ) {
			return;
		}

		static::$has_started = true;

		if ( $instance = Plugin::get_instance() ) {

			static::register_state_change_hooks();

			// Initialise the plugin on plugins loaded. All this really does is provide a hook
			// for registering/extending service providers and then boots all providers.
			add_action( 'plugins_loaded', function () use ( $instance ) {

				load_plugin_textdomain( $instance->make( 'plugin.textdomain' ) );

				// Perfect opportunity for plugins to register their own service providers or
				// extend/override any service providers already registered into the plugin's
				// service container.
				do_action( 'wp_landing_kit/init', $instance );

				$instance->boot();
			} );

		} else {

			// Print error message to site admin indicating why the plugin cannot run in the current environment.
			add_action( 'admin_notices', [ __CLASS__, '_print_admin_error_notice' ] );

		}
	}


	/**
	 * Get the main plugin instance. If not yet available, initialise first. This will instantiate and return a single
	 * instance of the plugin provided the plugin can run in this environment.
	 *
	 * @return Framework\Container\Plugin
	 */
	public static function get_instance() {

		if ( static::$has_run_checks === false ) {
			static::run_checks();
		}

		if ( ! static::$instance and static::is_runnable() ) {

			$plugin_dir = plugin_dir_path( WP_LANDING_KIT_PLUGIN_MAIN_FILE );

			// Core helper functions
			require_once $plugin_dir . 'framework/helpers.php';

			// Set up our PSR-4 autoloader
			require_once $plugin_dir . 'framework/AutoLoader.php';
			$autoloader = new AutoLoader();
			$autoloader->register();
			$autoloader->addNamespace( 'WpLandingKit\Tests\Framework', $plugin_dir . 'tests/framework' );
			$autoloader->addNamespace( 'WpLandingKit\Tests', $plugin_dir . 'tests/app' );
			$autoloader->addNamespace( 'WpLandingKit\Framework', $plugin_dir . 'framework' );
			$autoloader->addNamespace( 'WpLandingKit', $plugin_dir . 'app' );

			// Create our main instance
			static::$instance = new Framework\Container\Plugin( [
				'plugin.file' => WP_LANDING_KIT_PLUGIN_MAIN_FILE,
				'plugin.dir' => untrailingslashit( plugin_dir_path( WP_LANDING_KIT_PLUGIN_MAIN_FILE ) ),
				'plugin.url' => untrailingslashit( plugin_dir_url( WP_LANDING_KIT_PLUGIN_MAIN_FILE ) ),
				'plugin.name' => 'WP Landing Kit',
				'plugin.version' => WP_LANDING_KIT_PLUGIN_VERSION,
				'plugin.author' => 'Circa75 & Hookturn',
				'plugin.textdomain' => 'wp-landing-kit',
			] );

			static::$instance->bootstrap();
		}

		return static::$instance;
	}


	/**
	 * @return array An array of reasons why this plugin can't run in this environment. Empty array if no issues found.
	 */
	public static function get_failures() {
		return static::$check_failures ?: [];
	}


	/**
	 * Hooked function that drops an admin error notice when the plugin can activate but cannot run.
	 */
	public static function _print_admin_error_notice() {
		$message = __( 'The <strong>WP Landing Kit</strong> plugin, whilst active, is currently not running due to the following:', 'wp-landing-kit' );
		$message .= PHP_EOL;
		$message .= PHP_EOL;

		$warn_icon = '<span class="dashicons dashicons-warning" style="color:#CB423B"></span>';

		if ( $failures = Plugin::get_failures() ) {
			foreach ( $failures as $failure ) {
				$message .= "&nbsp;$warn_icon&nbsp;$failure";
				$message .= PHP_EOL;
			}
		} else {
			$message .= "&nbsp;$warn_icon&nbsp; Could not determine reason for failure. Check your errors logs for errors. If you cannot resolve the issue, please contact plugin support for guidance.";
			$message .= PHP_EOL;
		}

		$message .= PHP_EOL;
		$message .= '<small><em>';
		$message .= __( 'Note: plugin de-/activation hooks are disabled until any issues are resolved. Once resolved, reactivate the plugin to ensure it is set up correctly.', 'wp-landing-kit' );
		$message .= '</em></small>';

		$markup = sprintf( '<div class="error">%s</div>', wpautop( $message ) );

		echo wp_kses_post( $markup );
	}


	/**
	 * Regiser de/activation hooks and call handlers
	 */
	protected static function register_state_change_hooks() {
		register_activation_hook( WP_LANDING_KIT_PLUGIN_MAIN_FILE, function () {
			static::$instance[ ActivationHandler::class ]->activate();
		} );

		register_deactivation_hook( WP_LANDING_KIT_PLUGIN_MAIN_FILE, function () {
			static::$instance[ DeactivationHandler::class ]->deactivate();
		} );
	}


	/**
	 * Check if the plugin can run in this environment.
	 *
	 * @return bool
	 */
	protected static function is_runnable() {
		if ( ! static::$has_run_checks ) {
			wp_die( 'WP Landing Kit plugin cannot run before \WpLandingKit\Plugin::run_checks() method is invoked.' );
		}

		return count( static::$check_failures ) === 0;
	}


	/**
	 * Run pre-init checks
	 */
	protected static function run_checks() {
		static::$check_failures = [];

		// Check the PHP version
		if ( false === version_compare( PHP_VERSION, WP_LANDING_KIT_MIN_PHP_VERSION, '>=' ) ) {
			static::$check_failures[] = sprintf(
				__( 'The PHP version on your server (v%s) does not meet the minimum requirement (v%s)', 'wp-landing-kit' ),
				PHP_VERSION,
				WP_LANDING_KIT_MIN_PHP_VERSION
			);
		}

		// Add any additional checks here. Append any problems found to the $failures array so that site administrators can
		// understand why the plugin isn't running on their system. Environmential constraints, plugin dependencies, and
		// core version requirements are good things to check here. Avoid checking for anything that is resource or time
		// intensive however as they will slow everything down.
		// e.g;
		//      if ( $something_failed ) {
		//      	static::$check_failures[] = __( 'Some error message', 'wp-landing-kit' );
		//      }

		static::$has_run_checks = true;
	}


}