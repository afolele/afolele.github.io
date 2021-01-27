<?php


namespace WpLandingKit\Facades;


use WpLandingKit\Framework\Facades\FacadeBase;
use WpLandingKit\Models\Domain;


/**
 * Class DomainMap
 * @package WpLandingKit\Facades
 *
 * @method static update_domain( Domain $domain )
 * @method static save()
 * @method static get_domain_id( $host_name )
 * @method static reset()
 */
class DomainMap extends FacadeBase {


	protected static function get_facade_accessor() {
		return \WpLandingKit\DomainIntercept\DomainMap::class;
	}


}