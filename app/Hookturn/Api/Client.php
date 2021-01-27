<?php


namespace WpLandingKit\Hookturn\Api;


use WpLandingKit\Hookturn\Stats\Payload;


class Client {


	const BASE_URL = 'https://api.hookturn.io/api';


	public function activate( $data = [] ) {
		$this->checkin( 'activate', $data );
	}


	public function deactivate( $data = [] ) {
		$this->checkin( 'deactivate', $data );
	}


	public function checkin( $event, $data = [] ) {

		// todo - Bind objects to and resolve from the container. Set site diagnostic class as dependency.

		$payload = new Payload();
		$payload->set_extra_data( $data );
		$payload->set_event( $event );

		wp_remote_post( static::BASE_URL . '/checkin', [
			'headers' => [ 'Content-Type: application/json' ],
			'timeout' => 5,
			'blocking' => false,
			'body' => $payload->prepare(),
		] );
	}


}