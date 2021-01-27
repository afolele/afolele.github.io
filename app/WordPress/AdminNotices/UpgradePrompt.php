<?php


namespace WpLandingKit\WordPress\AdminNotices;


use WpLandingKit\Framework\Facades\App;
use WpLandingKit\Upgrade\UpgradeButtonView;


class UpgradePrompt {


	private $info_url = '';
	private $upgrade_url = '';


	/** @var UpgradeButtonView */
	private $button;


	/**
	 * @param UpgradeButtonView $button
	 */
	public function __construct( UpgradeButtonView $button ) {
		$this->button = $button;
	}


	/**
	 * @param string $info_url
	 */
	public function set_info_url( $info_url ) {
		$this->info_url = $info_url;
	}


	/**
	 * @param string $upgrade_url
	 */
	public function set_upgrade_url( $upgrade_url ) {
		$this->upgrade_url = $upgrade_url;
	}


	public function show() {
		add_action( 'admin_notices', [ $this, '_print_notice' ] );
		add_action( 'admin_footer', [ $this, '_print_confirmation_js' ], 20 );
	}


	public function hide() {
		remove_action( 'admin_notices', [ $this, '_print_notice' ] );
	}


	public function _print_notice() {
		$logo = App::make( 'app' )->url( 'assets/img/wp-landing-kit-icon.svg' );
		$title = App::make( 'plugin.name' );

		?>
        <div class="WplkUpgradeNotice notice notice-info">
            <div class="WplkUpgradeNotice__logo">
                <img src="<?php echo $logo ?>" alt="<?php echo esc_attr( $title ) ?>">
            </div>
            <div class="WplkUpgradeNotice__info">
                <h2 class="WplkUpgradeNotice__title">Database Upgrade Required</h2>
                <p>
                    This version of <em><?php echo $title ?></em> requires a database upgrade.
					<?php if ( $this->info_url ): ?>
                        To learn more about this upgrade, <a href="<?php echo esc_attr( $this->info_url ) ?>">click
                            here</a>.
					<?php endif; ?>
                </p>
            </div>
            <div class="WplkUpgradeNotice__actions">
				<?php if ( $this->upgrade_url ): ?>
					<?php echo $this->button->button( $this->upgrade_url ) ?>
				<?php endif; ?>
            </div>
        </div>
		<?php
	}


	public function _print_confirmation_js() {
		echo $this->button->script();
	}


}