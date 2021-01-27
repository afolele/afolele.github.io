<?php


namespace WpLandingKit\PostTypes;


use WP_Post;
use WpLandingKit\Events\UpdateDomainMap;
use WpLandingKit\Framework;
use WpLandingKit\Framework\Events\Dispatcher;
use WpLandingKit\Framework\Facades\App;
use WpLandingKit\Framework\Utils\Url;
use WpLandingKit\Models\Domain;
use WpLandingKit\Utils\DomainRegistry;
use WpLandingKit\WordPress\AdminColumns\MappedDomainAdminColumns;
use WpLandingKit\WordPress\Metaboxes\MappedDomainMappingsMetabox;
use WpLandingKit\WordPress\Metaboxes\MappedDomainSettingsMetabox;


/**
 * Class MappedDomainPostType
 * @package WpLandingKit\PostTypes
 */
class MappedDomainPostType extends Framework\PostTypes\PostTypeBase {


	const POST_TYPE = 'mapped-domain';


	/** @var Dispatcher */
	private $dispatcher;


	private $metaboxes = [
		MappedDomainMappingsMetabox::class,
		MappedDomainSettingsMetabox::class,
	];


	/**
	 * @param Dispatcher $dispatcher
	 */
	public function __construct( Dispatcher $dispatcher ) {
		$this->dispatcher = $dispatcher;
	}


	protected function after_register() {
		add_filter( 'wp_insert_post_data', [ $this, '_filter_post_title_on_insert' ], 10, 2 );
		add_filter( 'enter_title_here', [ $this, '_filter_new_post_title_placeholder' ], 10, 2 );
		add_filter( 'post_updated_messages', [ $this, '_filter_updated_messages' ] );
		$this->init_metaboxes();
		App::make( MappedDomainAdminColumns::class )->init();

		// Note: this might make more sense as its own class as there are potentially a few methods within this one
		// handler method.
		add_action( 'save_post_' . self::POST_TYPE, [ $this, '_save_post_data' ], 10, 3 );
		add_action( 'trashed_post', [ $this, '_update_domain_map' ] );
		add_action( 'untrashed_post', [ $this, '_update_domain_map' ] );
		add_action( 'delete_post', [ $this, '_update_domain_map' ] );

		add_action( 'transition_post_status', [ $this, '_trigger_post_status_change_hooks' ], 10, 3 );
		add_action( 'delete_post', [ $this, '_trigger_domain_deleted_api_hooks' ] );
	}


	/**
	 * Returns only the host name from a given domain.
	 *
	 * @param string $domain
	 *
	 * @return string
	 */
	public function sanitize_title( $domain ) {
		return Url::get_host( $domain );
	}


	public function _filter_post_title_on_insert( $data, $postarr ) {
		if ( $data['post_type'] === Domain::post_type() ) {
			$data['post_title'] = $this->sanitize_title( $data['post_title'] );
		}

		return $data;
	}


	public function _filter_new_post_title_placeholder( $text, WP_Post $post ) {
		if ( get_post_type( $post ) === self::POST_TYPE ) {
			return 'Enter domain e.g; mydomain.com';
		}

		return $text;
	}


	/**
	 * Set the post updated messages.
	 *
	 * @param array $messages Post updated messages.
	 *
	 * @return array Messages for the `mapped-domain` post type.
	 */
	public function _filter_updated_messages( $messages ) {
		global $post;

		$messages[ self::POST_TYPE ] = [
			0 => '', // Unused. Messages start at index 1.
			/* translators: %s: post permalink */
			1 => sprintf( __( 'Domain updated.', 'wp-landing-kit' ) ),
			2 => __( 'Custom field updated.', 'wp-landing-kit' ),
			3 => __( 'Custom field deleted.', 'wp-landing-kit' ),
			4 => __( 'Domain updated.', 'wp-landing-kit' ),
			/* translators: %s: date and time of the revision */
			5 => isset( $_GET['revision'] ) ? sprintf( __( 'Domain restored to revision from %s', 'wp-landing-kit' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			/* translators: %s: post permalink */
			6 => __( 'Domain published.', 'wp-landing-kit' ),
			7 => __( 'Domain saved.', 'wp-landing-kit' ),
			/* translators: %s: post permalink */
			8 => __( 'Domain submitted.', 'wp-landing-kit' ),
			/* translators: 1: Publish box date format, see https://secure.php.net/date 2: Post permalink */
			9 => sprintf(
				__( 'Domain scheduled for: <strong>%1$s</strong>.', 'wp-landing-kit' ),
				date_i18n( __( 'M j, Y @ G:i', 'wp-landing-kit' ), strtotime( $post->post_date ) )
			),
			/* translators: %s: post permalink */
			10 => __( 'Domain draft updated.', 'wp-landing-kit' ),
		];

		return $messages;
	}


	public function _save_post_data( $post_id, WP_Post $post, $update ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( $parent_id = wp_is_post_revision( $post_id ) ) {
			$post = get_post( $parent_id );
		}

		/** @var Domain $domain */
		$domain = Domain::make( $post );

		$should_update = false;

		// Give metaboxes their opportunity to move data from request to the model.
		foreach ( $this->metaboxes as $class ) {
			if ( App::make( $class )->prepare_model( $domain ) ) {
				$should_update = true;
			}
		}

		if ( $should_update ) {
			$domain->save();
			$this->dispatcher->dispatch( new UpdateDomainMap( $domain->ID ) );
		}
	}


	function _trigger_post_status_change_hooks( $new_status, $old_status, $post ) {
		if (
			$old_status === 'new'
			or $new_status === $old_status
			or get_post_type( $post ) !== self::POST_TYPE
		) {
			return;
		}

		$domain = Domain::make( $post );
		$host = $domain->host();
		$post_id = $domain->ID;

		if ( $domain->is_active() ) {
			/**
			 * Fires only when domain status is changed TO publish.
			 *
			 * @var string $host
			 * @var int $post_id
			 */
			do_action( 'wp_landing_kit/domain_enabled', $host, $post_id );

		} elseif ( $old_status === 'publish' ) {
			/**
			 * Fires when domain status is changed FROM publish TO any other status.
			 *
			 * @var string $host
			 * @var int $post_id
			 */
			do_action( 'wp_landing_kit/domain_disabled', $host, $post_id );
		}
	}


	public function _trigger_domain_deleted_api_hooks( $post_id ) {
		/** @var Domain $domain */
		if (
			get_post_type( $post_id ) !== self::POST_TYPE
			or ! $domain = Domain::find( $post_id )
		) {
			return;
		}

		/**
		 * Create a closure and enclose a reference to itself so that it can unhook itself after it runs.
		 *
		 * Note that WordPress offers us both the `delete_post` and `deleted_post` hooks but each have different args.
		 *  - `delete_post` runs before the post object is deleted and has both the post_id and the post object.
		 *  - `deleted_post` runs after the post object is deleted and only has the post_id.
		 *
		 * We are creating a closure here on the `delete_post` hook which encloses the host name of the domain that is
		 * about to be deleted. The closure is dynamically hooked to `deleted_post` below. The closure also encloses a
		 * reference to itself so it can unhook itself as soon as it does its job.
		 *
		 * @param int $post_id
		 */
		$after_delete_fn = function ( $post_id ) use ( $domain, &$after_delete_fn ) {
			$host = $domain->host();
			/**
			 * @var string $host
			 * @var int $post_id
			 */
			do_action( 'wp_landing_kit/domain_deleted', $host, $post_id );
			remove_action( 'deleted_post', $after_delete_fn );
		};

		add_action( 'deleted_post', $after_delete_fn );
	}


	public function _update_domain_map( $post_id ) {
		if ( get_post_type( $post_id ) === self::POST_TYPE ) {
			$this->dispatcher->dispatch( new UpdateDomainMap( $post_id ) );
			DomainRegistry::purge( $post_id );
		}
	}


	private function init_metaboxes() {
		array_map( function ( $class ) {
			App::make( $class )->init();
		}, $this->metaboxes );
	}


}