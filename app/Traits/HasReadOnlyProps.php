<?php


namespace WpLandingKit\Traits;


/**
 * Trait HasReadOnlyProps
 * @package WpLandingKit\Traits
 *
 * CFW — Build this out so that it allows explicit defining of which properties/visibilities are allowed read-only
 * access. Move this to FW when complete.
 */
trait HasReadOnlyProps {


	/**
	 * Allow read-only access to private/protected properties.
	 *
	 * @param $name
	 *
	 * @return mixed|null
	 */
	public function __get( $name ) {

		// CFW - add a check here to see if requested property is available as a read only property before returning.

		return property_exists( $this, $name ) ? $this->$name : null;
	}


}