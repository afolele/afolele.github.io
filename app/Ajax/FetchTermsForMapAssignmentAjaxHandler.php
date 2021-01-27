<?php


namespace WpLandingKit\Ajax;


use WpLandingKit\Facades\Settings;
use WpLandingKit\Framework\Ajax\AjaxHandlerBase;
use WpLandingKit\Framework\Utils\Arr;
use WpLandingKit\Models\Post;
use WpLandingKit\Models\Domain;
use WP_Query;


/**
 * Class FetchTermsForMapAssignmentAjaxHandler
 * @package WpLandingKit\Ajax
 */
class FetchTermsForMapAssignmentAjaxHandler extends AjaxHandlerBase {


	protected $action = 'wp_landing_kit_fetch_terms_for_map_assignment';


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
			'taxonomy' => Arr::get( $_REQUEST, 'taxonomy', null ),
			'orderby' => 'name',
			'order' => 'ASC',
			'hide_empty' => false,
			'number' => '',
			'fields' => 'all',
			//'search' => '',
			'get' => '',
			'update_term_meta_cache' => false,
		];

		//if ( ! empty( $_REQUEST['selected_id'] ) ) {
		//	$query_args['post__not_in'] = [ $_REQUEST['selected_id'] ];
		//}

		if ( ! empty( $_REQUEST['q'] ) ) {
			$query_args['search'] = $_REQUEST['q'];
		}

		/** @var \WP_Term[] $terms */
		$terms = get_terms( $query_args );

		$matches = [];

		foreach ( $terms as $term ) {
			$matches[] = [
				// Note: using same AJAX as post query. We can clean this up later.
				'post_id' => $term->term_id,
				'title' => $term->name,
			];
		}

		wp_send_json( [
			'matches' => $matches,
		] );
	}


}