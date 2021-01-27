<?php


namespace WpLandingKit\Facades;


use WpLandingKit\Framework\Events\Dispatcher;
use WpLandingKit\Framework\Facades\FacadeBase;


/**
 * Class Events
 * @package WpLandingKit\Facades
 *
 * @method static listen( $events, $listener )
 * @method static dispatch( $event, $payload = [] )
 * @method static make_listener( $listener )
 */
class Events extends FacadeBase {


	protected static function get_facade_accessor() {
		return Dispatcher::class;
	}


}