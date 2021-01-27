<?php


namespace WpLandingKit\WordPress\AdminColumns;


use WpLandingKit\DomainIntercept\DomainMap;
use WpLandingKit\Facades\Settings;
use WpLandingKit\Framework\Utils\Arr;


class PostAdminColumns {


	/** @var DomainMap */
	private $map;


	/**
	 * @param DomainMap $map
	 */
	public function __construct( DomainMap $map ) {
		$this->map = $map;
	}


	public function init() {
		foreach ( $this->supported_post_types() as $post_type ) {
			add_filter( "manage_{$post_type}_posts_columns", [ $this, '_configure_columns' ] );
			add_action( "manage_{$post_type}_posts_custom_column", [ $this, '_populate_columns' ], 10, 2 );
		}

		add_action( 'admin_head', function () {
			?>
            <style>
                .wplk-posts-column-icon.dashicons {
                    width: 1em;
                    height: 1em;
                    line-height: 1em;
                    font-size: 1em;
                    vertical-align: middle;
                    position: relative;
                    top: -1px;
                }

                .wplk-mapped-url {
                    white-space: nowrap;
                    overflow: hidden;
                }
            </style>
			<?php
		} );
	}


	public function _configure_columns( $defaults ) {
		// Determine the index just after the 'title' column. If it doesn't exist, add our column to the end.
		$index = Arr::get_assoc_key_index( $defaults, 'title' ) ?: count( $defaults );

		return Arr::splice_assoc( $defaults, [ 'mapped_urls' => 'Mapped URLs <span class="dashicons dashicons-external wplk-posts-column-icon"></span>' ], $index + 1 );
	}


	public function _populate_columns( $column_name, $post_id ) {
		if ( $column_name === 'mapped_urls' ) {

			$urls = $this->map->get_urls_for_post_id( $post_id );

			if ( $urls ) {
				foreach ( $urls as $url ) {
					?>
                    <div class="wplk-mapped-url">
                        <a href="<?php echo esc_attr( $url ) ?>"
                           title="<?php echo esc_attr( $url ) ?>"
                           target="_blank"
                           rel="noopener noreferrer"><?= $url ?></a>
                    </div>
					<?php
				}
			} else {
				echo '—';
			}
		}
	}


	private function supported_post_types() {
		return Settings::get( 'mappable_post_types' );
	}


}