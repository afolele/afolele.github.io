<?php


namespace WpLandingKit\Models;


use WP_Error;
use WP_Post;
use WpLandingKit\Facades\Settings;
use WpLandingKit\Framework;
use WpLandingKit\Framework\Facades\App;
use WpLandingKit\Framework\Traits\DotNotatedArraySupport;
use WpLandingKit\Framework\Utils\Arr;
use WpLandingKit\PostTypes;
use WpLandingKit\Utils\RewriteRulesGenerator;
use WpLandingKit\Utils\Json;


class Domain extends Framework\Models\PostModelBase {


	use DotNotatedArraySupport {
		DotNotatedArraySupport::get as get__dot_notated;
		DotNotatedArraySupport::set as set__dot_notated;
	}


	const TYPE_CLASS = PostTypes\MappedDomainPostType::class;
	const DEFAULT_PROTOCOL = 'none';
	const ENFORCABLE_PROTOCOLS = [ 'http', 'https' ];


	private $config = [
		'settings' => [],
		'mappings' => []
	];


	/**
	 * @var array Dynamic default config to account for dynamic IDs. This only gets built once per object.
	 * @see \WpLandingKit\Models\Domain::default_config();
	 */
	private $default_config = [];


	/**
	 * CFW - This should go in the PostModelBase class.
	 *
	 * Generate a new object on the fly.
	 *
	 * @param array $props Array of object properties to hydrate on the generated object. This will fill any properties
	 * where defined on the object and fallback to the underlying WP_Post object properties where not. Undefined props
	 * are overloaded on the WP_Post object.
	 *
	 * @return Domain
	 */
	public static function generate( array $props = [] ) {
		$post = new \stdClass();
		$post->post_status = 'auto-draft';
		$post->post_type = static::post_type();
		$domain = static::make( new WP_Post( $post ) );

		// Hydrate Domain object with properties.
		foreach ( $props as $name => $value ) {
			$domain->$name = $value;
		}

		return $domain;
	}


	/**
	 * CFW — perhaps add query args as optional parameter and move this method to PostModelBase
	 *
	 * @param array $query_args Additional query args, if needed.
	 *
	 * @return array
	 */
	public static function all( $query_args = [] ) {
		$posts = get_posts( wp_parse_args( $query_args, [
			'post_type' => self::post_type(),
			'numberposts' => - 1,
			'post_status' => 'publish',
			'no_found_rows' => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		] ) );

		return $posts
			? array_map( 'self::make', $posts )
			: [];
	}


	/**
	 * @param string $domain
	 *
	 * @return Domain|null
	 */
	public static function find_by_domain( $domain ) {
		$domain = self::post_type_obj()->sanitize_title( $domain );

		if ( ! $domain ) {
			return null;
		}

		$post = get_page_by_title( $domain, OBJECT, static::post_type() );

		if ( ! $post instanceof WP_Post ) {
			return null;
		}

		return self::make( $post );
	}


	/**
	 * Check if domain is currently actively intercepting requests.
	 *
	 * @return bool
	 */
	public function is_active() {
		return $this->post_status === 'publish';
	}


	/**
	 * Set a state of active. An active domain will intercept requests.
	 * This doesn't persist until the object is saved using $this->save().
	 *
	 * @return $this
	 */
	public function activate() {
		$this->post_status = 'publish';

		return $this;
	}


	/**
	 * Set a state of inactive. An inactive domain will not intercept requests.
	 * This doesn't persist until the object is saved using $this->save().
	 *
	 * @return $this
	 */
	public function deactivate() {
		$this->post_status = 'draft';

		return $this;
	}


	/**
	 * Set up this object immediately after the WP_Post object is set in static::make().
	 */
	public function setup() {
		$this->config = Json::is_json( $this->post_content )
			? Json::decode( $this->post_content, true )
			: $this->default_config();
	}


	/**
	 * Get the whole config or specific key/nested-key target.
	 *
	 * @param null|string $key
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function config( $key = null, $default = false ) {
		return $key
			? $this->get__dot_notated( $this->config, $key, $default )
			: $this->config;
	}


	/**
	 * @param string $host_name The host name. e.g; mydomain.com. This can safely include protocols, paths, query
	 * strings, etc, as the property mutation setter will remove anything that isn't a host name.
	 *
	 * @return $this
	 *
	 * @see \WpLandingKit\Models\Domain::set_post_title_attribute();
	 */
	public function set_host( $host_name ) {
		$this->post_title = $host_name;

		return $this;
	}


	/**
	 * Set mappings array. If more than two mappings are passed in, the first will be the root, the last will be the
	 * fallback, and any in between will be dynamic mappings. If empty array is passed, defaults will be applied as the
	 * domain model always needs to have root and fallback mappings. If only one is passed, it will be interpreted as
	 * the root mapping and fallback will be applied from defaults.
	 *
	 * @param array $mappings
	 */
	public function set_mappings( array $mappings ) {
		switch ( count( $mappings ) ) {
			case 0:
				$mappings = $this->default_config( 'mappings' );
				break;
			case 1:
				$mappings[] = array_slice( $this->default_config( 'mappings' ), - 1, 1, true );

		}
		$this->config['mappings'] = $mappings;
	}


	/**
	 * Take a given settings array, merge it with the defaults, then set it on this object.
	 *
	 * @param array $settings
	 */
	public function set_settings( array $settings ) {
		$this->config['settings'] = wp_parse_args( $settings, $this->default_config( 'settings', [] ) );
	}


	/**
	 * Get domain mappings. This includes the root and the fallback mappings which are both required for a domain to
	 * function. The first mapping returned in the array will be root mapping, the last will be the fallback. All
	 * mappings between those two are custom URL handlers.
	 *
	 * @return array[]
	 */
	public function mappings() {
		return $this->config( 'mappings', [] );
	}


	/**
	 * Return only the dynamic mappings. i.e; remove the fixed 'root' and 'fallback' mappings.
	 *
	 * @return array[]
	 */
	public function dynamic_mappings() {
		$m = $this->mappings();
		array_shift( $m ); // remove the root mapping (first position)
		array_pop( $m ); // remove the fallback mapping (last position)

		return $m;
	}


	public function root_mapping() {
		$mappings = $this->mappings();

		return is_array( $m = array_shift( $mappings ) ) ? $m : [];
	}


	public function fallback_mapping() {
		$mappings = $this->mappings();

		return is_array( $m = array_pop( $mappings ) ) ? $m : [];
	}


	/**
	 * @return int|WP_Error
	 */
	public function save() {
		$this->post_content = Json::encode( $this->config );
		$saved = parent::save();

		if ( ! is_wp_error( $saved ) ) {
			$this->set_post_object( get_post( $saved ) );
		}

		return $saved;
	}


	/**
	 * Array of [pattern => rewrite] pairs specific to this domain.
	 *
	 * @return array
	 */
	public function rewrite_rules() {
		return RewriteRulesGenerator::make( $this ) ?: [];
	}


	/**
	 * @return string
	 */
	public function host() {
		return $this->post_title;
	}


	/**
	 * @param string $protocol 'none', 'http', or 'https'.
	 *
	 * @return $this
	 */
	public function set_enforced_protocol( $protocol ) {
		$protocol = strtolower( $protocol );
		$acceptable = array_merge( self::ENFORCABLE_PROTOCOLS, [ self::DEFAULT_PROTOCOL ] );

		if ( in_array( $protocol, $acceptable ) ) {
			$this->config['settings']['enforced_protocol'] = $protocol;
		}

		return $this;
	}


	/**
	 * Return the Domain's configured protocol setting.
	 *
	 * @return string 'none' (default), 'http', or 'https'.
	 */
	public function enforced_protocol() {
		return $this->config( 'settings.enforced_protocol', self::DEFAULT_PROTOCOL );
	}


	/**
	 * Check if the domain is enforcing a particular protocol.
	 *
	 * @return bool
	 */
	public function is_enforcing_protocol() {
		return in_array( $this->enforced_protocol(), self::ENFORCABLE_PROTOCOLS );
	}


	/**
	 * Build the protocol URL prefix.
	 *
	 * @return string
	 */
	public function protocol() {
		// If enforcing protocol, return that
		if ( $this->is_enforcing_protocol() ) {
			return $this->enforced_protocol() . '://';
		}

		// If globally enforcing protocol, return that
		$global = Settings::get( 'enforce_protocol_on_domains', self::DEFAULT_PROTOCOL );
		if ( $global !== self::DEFAULT_PROTOCOL ) {
			return $global . '://';
		}

		return is_ssl() ? 'https://' : 'http://';
	}


	/**
	 * Mutate the post_title on the decorated post object when set directly on this object. This ensures the post title
	 * only ever has the actual host name.
	 *
	 * @param string $value
	 */
	public function set_post_title_attribute( $value ) {
		$this->post->post_title = self::post_type_obj()->sanitize_title( $value );
	}


	/**
	 * CFW - Would make sense on the PostModelBase class.
	 *
	 * @return PostTypes\MappedDomainPostType
	 */
	public static function post_type_obj() {
		return App::make( static::TYPE_CLASS );
	}


	/**
	 * Default config. This will generate the defaults only once per object and cache the data in $this->default_config.
	 *
	 * @param string|null $key
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	private function default_config( $key = null, $default = [] ) {
		if ( empty( $this->default_config ) ) {
			$root_id = uniqid();
			$fallback_id = uniqid();

			$this->default_config = [
				'settings' => [
					'enforced_protocol' => self::DEFAULT_PROTOCOL,
				],
				'mappings' => [
					$root_id => [
						'mapping_id' => $root_id,
						'action' => 'map_to_resource',
						'resource_type' => 'single-page',
						'page_id' => '0', // Default to 0 which does nothing.
					],
					$fallback_id => [
						'mapping_id' => $fallback_id,
						'action' => 'redirect',
						'redirect_url' => '/',
						'redirect_status' => '302',
					],
				],
			];
		}

		return $key ? Arr::get_deep( $this->default_config, $key, $default ) : $this->default_config;
	}


	// =≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=
	// DEPRECATED METHODS AND PROPS FROM HERE DOWN — DO NOT USE
	// =≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=≠=


	/**
	 * @var Post
	 * @deprecated
	 */
	private $mapped_post = null;


	/**
	 * @return Post|null
	 * @deprecated
	 */
	public function mapped_post() {
		_deprecated_function( __METHOD__, '1.2', 'NO REPLACEMENT — WILL BE REMOVED IN NEXT VERSION' );
		if ( $this->mapped_post ) {
			return $this->mapped_post;
		}

		if ( $id = absint( get_post_meta( $this->post->ID, 'mapped_post_id', true ) ) ) {
			$this->mapped_post = Post::find( $id );
		}

		return $this->mapped_post;
	}


	/**
	 * @return int
	 * @deprecated
	 */
	public function mapped_post_id() {
		_deprecated_function( __METHOD__, '1.2', 'NO REPLACEMENT — WILL BE REMOVED IN NEXT VERSION' );
		if ( $this->mapped_post() ) {
			return $this->mapped_post->ID;
		}

		return null;
	}


	/**
	 * @return string|null
	 * @deprecated
	 */
	public function mapped_post_title() {
		_deprecated_function( __METHOD__, '1.2', 'NO REPLACEMENT — WILL BE REMOVED IN NEXT VERSION' );
		if ( $post = $this->mapped_post() ) {
			return $post->post_title;
		}

		return null;
	}


	/**
	 * @return string|null
	 * @deprecated
	 */
	public function mapped_post_type() {
		_deprecated_function( __METHOD__, '1.2', 'NO REPLACEMENT — WILL BE REMOVED IN NEXT VERSION' );
		if ( $post = $this->mapped_post() ) {
			return $post->post_type;
		}

		return null;
	}


	/**
	 * @return mixed|string|null
	 * @deprecated
	 */
	public function mapped_post_type_name() {
		_deprecated_function( __METHOD__, '1.2', 'NO REPLACEMENT — WILL BE REMOVED IN NEXT VERSION' );
		if ( $post = $this->mapped_post() ) {
			return $post->type_name();
		}

		return null;
	}


	/**
	 * @param Post $post
	 *
	 * @return bool|int
	 * @deprecated
	 */
	public function set_mapped_post( Post $post ) {
		_deprecated_function( __METHOD__, '1.2', 'NO REPLACEMENT — WILL BE REMOVED IN NEXT VERSION' );

		return update_post_meta( $this->ID, 'mapped_post_id', $post->ID );
	}


	/**
	 * @return bool
	 * @deprecated
	 */
	public function remove_mapping() {
		_deprecated_function( __METHOD__, '1.2', 'NO REPLACEMENT — WILL BE REMOVED IN NEXT VERSION' );

		return delete_post_meta( $this->ID, 'mapped_post_id' );
	}


	/**
	 * @return bool
	 * @deprecated
	 */
	public function is_mapped_to_post() {
		_deprecated_function( __METHOD__, '1.2', 'NO REPLACEMENT — WILL BE REMOVED IN NEXT VERSION' );

		return (bool) $this->mapped_post_id();
	}


}