<?php


namespace WpLandingKit\Listeners;


use WpLandingKit\DomainIntercept\DomainMap;
use WpLandingKit\Models\Domain;


class DomainMapUpdateListener {


	/** @var DomainMap */
	private $domain_map;


	/**
	 * @param DomainMap $domain_map
	 */
	public function __construct( DomainMap $domain_map ) {
		$this->domain_map = $domain_map;
	}


	public function handle( $event, $payload = [] ) {
		$domain = Domain::find( $payload[0]->domain_id );

		if ( ! $domain instanceof Domain ) {
			trigger_error( 'Failed to update map for domain ID: ' . $payload[0]->domain_id . '. Post object was not found.' );

			return;
		}

		$this->domain_map->update_domain( $domain );
		$this->domain_map->save();
	}


}