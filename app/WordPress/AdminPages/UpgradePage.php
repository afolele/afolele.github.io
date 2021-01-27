<?php


namespace WpLandingKit\WordPress\AdminPages;


use WpLandingKit\PostTypes\MappedDomainPostType;
use WpLandingKit\Upgrade\UpgradeButtonView;
use WpLandingKit\Upgrade\Upgrader;
use WpLandingKit\Utils\Request;
use WpLandingKit\View\AdminView;


class UpgradePage {


	const SLUG = 'wplk-upgrade';
	const VIEW_QUERY_VAR = 'wplk_upgrade_view';


	private $page;


	/** @var Upgrader */
	private $upgrader;


	/** @var UpgradeButtonView */
	private $button;


	/**
	 * @param UpgradeButtonView $button
	 */
	public function __construct( UpgradeButtonView $button ) {
		$this->button = $button;
	}


	public function set_upgrader( Upgrader $upgrader ) {
		$this->upgrader = $upgrader;
	}


	public function register_page() {
		return $this->page = add_submenu_page(
			'edit.php?post_type=mapped-domain',
			__( 'Upgrade Database', 'wp-landing-kit' ),
			__( 'Upgrade Database', 'wp-landing-kit' ),
			'manage_options',
			self::SLUG,
			[ $this, '_render' ]
		);
	}


	public function on_page_load( Callable $callback ) {
		if ( ! $this->page ) {
			return;
		}

		add_action( 'load-' . $this->page, function () use ( $callback ) {
			$callback( $this );
		} );
	}


	public function upgrade_url() {
		return add_query_arg( [ self::VIEW_QUERY_VAR => 'run' ], $this->info_url() );
	}


	public function info_url() {
		return add_query_arg( [
			'post_type' => MappedDomainPostType::POST_TYPE,
			'page' => self::SLUG,
		], admin_url( 'edit.php' ) );
	}


	public function _render() {
		?>
        <div class="wrap">
			<?php
			AdminView::render( 'WplkAdminPageTitle', [ 'title' => 'Database Upgrade' ] );

			if ( Request::get( self::VIEW_QUERY_VAR ) === 'run' ) {
				$this->run_upgrades();
			} else {
				$this->show_available_updates();
				add_action( 'admin_footer', [ $this, '_print_confirmation_js' ], 20 );
			}
			?>
        </div>
		<?php
	}


	private function run_upgrades() {
		?>
        <h2>Running database upgrades…</h2>
		<?php foreach ( $this->upgrader->upgrades() as $upgrade ): ?>
            <div class="WplkCard card wplk-m-0-first-last">
                <h2 class="WplkCard__title title"><?php echo $upgrade->title() ?></h2>

				<?php $upgrade_status = $this->upgrader->run( $upgrade ); ?>

				<?php if ( is_wp_error( $upgrade_status ) ): ?>
                    <div class="WplkCard__error wplk-m-0-first-last">
						<?php echo $upgrade_status->get_error_message() ?>
                    </div>
				<?php elseif ( $upgrade_status ): ?>
                    <div class="WplkCard__success wplk-m-0-first-last">
						<?php echo $upgrade_status ?>
                    </div>
				<?php endif; ?>

            </div>
			<?php
			// Stop processing the loop on the first error.
			if ( is_wp_error( $upgrade_status ) ) {
				break;
			}
		endforeach;
	}


	private function show_available_updates() {
		?>
        <h2>The following database upgrades need to be performed:</h2>

		<?php foreach ( $this->upgrader->upgrades() as $upgrade ): ?>
            <div class="WplkCard card wplk-m-0-first-last">
                <h2 class="WplkCard__title title"><?php echo $upgrade->title() ?></h2>
				<?php if ( $upgrade->explanation() or $upgrade->description() ): ?>
                    <div class="WplkCard__descr wplk-m-0-first-last">
						<?php echo $upgrade->explanation() ?: $upgrade->description(); ?>
                    </div>
				<?php endif; ?>
            </div>
		<?php endforeach; ?>

        <div style="margin-top:1.65em;">
			<?php echo $this->button->button( $this->upgrade_url() ) ?>
        </div>
		<?php
	}


	public function _print_confirmation_js() {
		echo $this->button->script();
	}


}