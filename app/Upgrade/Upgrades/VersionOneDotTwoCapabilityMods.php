<?php


namespace WpLandingKit\Upgrade\Upgrades;


use Exception;
use WpLandingKit\Actions\SetCapabilities;
use WpLandingKit\Upgrade\UpgradeBase;


class VersionOneDotTwoCapabilityMods extends UpgradeBase {


	public function version() {
		return strtotime( '23 September 2020' );
	}


	public function run() {
		$run = SetCapabilities::run();

		if ( is_wp_error( $run ) ) {
			throw new Exception( 'Failed to set capabilities. Error reads: ' . $run->get_error_message() );
		}

		return 'Administrator capabilities are set';
	}


	public function title() {
		return __( 'Set up domain-specific capabilities for administrators.', 'wp-landing-kit' );
	}


	public function explanation() {
		ob_start();
		?>
        <p>
			<?php _e( 'In order to better facilitate use case scenarios utilising the PHP API in version 1.2, some 
			base capabilities need to be set for user roles. This upgrade will add the following capabilities to the 
			administrator user role:', 'wp-landing-kit' ) ?>
        </p>
        <ul>
			<?php foreach ( SetCapabilities::$roles_and_caps['administrator'] as $cap ): ?>
                <li><?php echo $cap ?></li>
			<?php endforeach; ?>
        </ul>
		<?php
		return ob_get_clean();
	}
}