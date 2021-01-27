<?php


namespace WpLandingKit\WordPress\AdminPages;


use WpLandingKit\Actions\SetCapabilities;
use WpLandingKit\DomainIntercept\DomainMap;
use WpLandingKit\Framework\Facades\App;
use WpLandingKit\Models\Domain;
use WpLandingKit\Utils\Request;
use WpLandingKit\View\AdminView;


class ToolsPage {


	public function init() {
		add_action( 'admin_menu', [ $this, '_register_page' ] );
	}


	public function _register_page() {
		add_submenu_page(
			'tools.php',
			__( 'WP Landing Kit', 'wp-landing-kit' ),
			__( 'WP Landing Kit', 'wp-landing-kit' ),
			'manage_options',
			'wp-landing-kit',
			[ $this, '_render' ]
		);
	}


	public function _render() {
		$base_url = admin_url( 'tools.php?page=wp-landing-kit' );
		$action = Request::get( 'wplk_tool_action', false );
		?>
        <div class="wrap">

			<?php AdminView::render( 'WplkAdminPageTitle', [ 'title' => 'Tools' ] ); ?>

			<?php if ( ! $action ): ?>
                <div class="card">
                    <h2 class="title">Rebuild Domain Map</h2>
                    <p>
                        The domain map is essential in mapping domains to resources. Without the map, additional domains
                        won't work.
                    </p>
                    <p>
                        Each domain's map configuration is rebuilt when the domain post is saved but there may be times
                        when you may need to rebuild the entire map.
                    </p>
                    <p><a href="<?php echo add_query_arg( 'wplk_tool_action', 'rebuild-map', $base_url ) ?>"
                          class="button button-primary">Rebuild the map</a></p>
                </div>

                <div class="card">
                    <h2 class="title">Reset Custom Administrator Capabilities</h2>
                    <p>
                        In order to manage domains, administrators need some custom capabilities. They won't be able to
                        manage domains without the capabilities.
                    </p>
                    <p>
                        These are set on plugin activation but there may be times when you need to ensure all the base
                        capabilities are set.
                    </p>
                    <p><a href="<?php echo add_query_arg( 'wplk_tool_action', 'reset-capabilities', $base_url ) ?>"
                          class="button button-primary">Reset capabilities</a></p>
                </div>

			<?php elseif ( $action === 'rebuild-map' ): ?>
				<?php
				/** @var DomainMap $map */
				$map = App::make( DomainMap::class );
				$map->reset();
				$domains = Domain::all();
				?>
                <div class="card">
                    <h2 class="title">Rebuilding Domain Map…</h2>
					<?php foreach ( $domains as $domain ): ?>
                        <div>
							<?php echo $domain->post_title ?>
							<?php $map->update_domain( $domain ) ?>
                            — DONE!
                        </div>
					<?php endforeach; ?>
                    <div style="margin:20px 0;">
                        Saving the map
						<?php $map->save(); ?>
                        — DONE!
                    </div>
                    <p><a href="<?php echo $base_url ?>" class="button button-primary">Back to tools</a></p>
                </div>

			<?php elseif ( $action === 'reset-capabilities' ):
				SetCapabilities::run();
				?>
                <div class="card">
                    <h2 class="title">Resetting administrator capabilities…</h2>
					<?php foreach ( SetCapabilities::$roles_and_caps['administrator'] as $cap ): ?>
                        <div style="line-height:2;">
                            <code><?php echo $cap ?></code> …set. <br>
                        </div>
					<?php endforeach; ?>
                    <div style="margin:20px 0;">
                        – DONE!
                    </div>
                    <p><a href="<?php echo $base_url ?>" class="button button-primary">Back to tools</a></p>
                </div>

			<?php endif; ?>

        </div>
		<?php
	}


}