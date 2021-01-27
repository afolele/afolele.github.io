<?php


namespace WpLandingKit\Models;


use WpLandingKit\Framework;


/**
 * Class Post
 * @package WpLandingKit\Models
 *
 * @deprecated Soft deprecation at this stage. We'll likely remove this object entirely.
 */
class Post extends Framework\Models\PostModelBase {


	public function type_name() {
		if ( $type_obj = get_post_type_object( $this->post->post_type ) ) {
			$labels = get_post_type_labels( $type_obj );

			return Framework\Utils\Arr::get( (array) $labels, 'singular_name' );
		}

		return '';
	}


	public function has_mapped_domain() {
		return ! empty( $this->mapped_domain() );
	}


	public function mapped_domain() {
		if ( $id = get_post_meta( $this->ID, 'mapped_domain_id', true ) ) {
			return Domain::find( $id );
		}

		return null;
	}


	public function mapped_domain_id() {
		if ( $domain = $this->mapped_domain() ) {
			return $domain->ID;
		}

		return null;
	}


	public function mapped_domain_name() {
		if ( $domain = $this->mapped_domain() ) {
			return $domain->host();
		}

		return '';
	}


	public function remove_mapped_domain() {
		return delete_post_meta( $this->ID, 'mapped_domain_id' );
	}


	public function set_mapped_domain( Domain $domain ) {
		return update_post_meta( $this->ID, 'mapped_domain_id', $domain->ID );
	}


}