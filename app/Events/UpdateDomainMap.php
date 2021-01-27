<?php


namespace WpLandingKit\Events;


class UpdateDomainMap {


	public $domain_id;


	/**
	 * @param $domain_id
	 */
	public function __construct( $domain_id ) {
		$this->domain_id = $domain_id;
	}


}