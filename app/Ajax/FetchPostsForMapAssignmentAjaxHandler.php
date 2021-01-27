<?php


namespace WpLandingKit\Ajax;


use WpLandingKit\Facades\Settings;
use WpLandingKit\Framework\Ajax\AjaxHandlerBase;
use WpLandingKit\Framework\Utils\Arr;
use WpLandingKit\Models\Post;
use WpLandingKit\Models\Domain;
use WP_Query;


/**
 * Class FetchPostsForMapAssignmentAjaxHandler
 * @package WpLandingKit\Ajax
 */
class FetchPostsForMapAssignmentAjaxHandler extends AjaxHandlerBase {


	protected $action = 'wp_landing_kit_fetch_posts_for_map_assignment';


	protected $print_inline_script = false;


	public function register() {
		parent::register();
		//add_action( 'admin_head', [ $this, '_print_inline_script' ] );
	}


	/**
	 * Our base method is protected so we need public access. Consider opening up permission on base method to
	 * facilitate flexibility moving forward.
	 */
	public function get_script_vars() {
		return parent::get_script_vars();
	}


	protected function handle_priv() {

		$query_args = [
			'post_type' => Arr::get( $_REQUEST, 'post_type', 'any' ),
			'post_status' => 'publish',
			'posts_per_page' => 30,
			'no_found_rows' => true,
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
		];

		//if ( ! empty( $_REQUEST['selected_id'] ) ) {
		//	$query_args['post__not_in'] = [ $_REQUEST['selected_id'] ];
		//}

		if ( ! empty( $_REQUEST['q'] ) ) {
			$query_args['s'] = $_REQUEST['q'];
		}

		$posts = new WP_Query( $query_args );

		$matches = [];

		if ( $posts->have_posts() ) {
			while ( $posts->have_posts() ) {
				$posts->the_post();

				$post = new Post();
				$post->set_post_object( get_post() );

				$matches[] = [
					'post_id' => esc_attr( get_the_ID() ),
					'title' => strip_tags( get_the_title() ),
				];
			}
		}

		wp_send_json( [
			'matches' => $matches,
		] );
	}


	protected function get_custom_script_vars() {
		$domain = Domain::find( get_the_ID() );

		if ( ! $domain ) {
			return [];
		}

		return [
			'domain_id' => get_the_ID(),
			/**
			 * @deprecated. Need to look through JS and remove any dependencies that might be looking for this before we
			 * remove this entirely.
			 */
			'selected_id' => 0,
		];
	}


}