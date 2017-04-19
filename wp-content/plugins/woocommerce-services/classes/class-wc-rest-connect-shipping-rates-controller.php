<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Shipping_Rates_Controller' ) ) {
	return;
}

class WC_REST_Connect_Shipping_Rates_Controller extends WC_REST_Connect_Base_Controller {

	protected $method = 'POST';
	protected $rest_base = 'connect/shipping-rates';

	/**
	 *
	 * @param WP_REST_Request $request - See WC_Connect_API_Client::get_label_rates()
	 * @return array|WP_Error
	 */
	public function run( $request ) {
		$payload = $request->get_json_params();

		// This is the earliest point in the printing label flow where we are sure that
		// the merchant wants to ship from this exact address (normalized or otherwise)
		$this->settings_store->update_origin_address( $payload[ 'origin' ] );
		$this->settings_store->update_destination_address( $payload[ 'orderId' ], $payload[ 'destination' ] );

		// Hardcode USPS rates for now
		$payload[ 'carrier' ] = 'usps';
		unset( $payload[ 'orderId' ] );
		$response = $this->api_client->get_label_rates( $payload );

		if ( is_wp_error( $response ) ) {
			$error = new WP_Error(
				$response->get_error_code(),
				$response->get_error_message(),
				array( 'message' => $response->get_error_message() )
			);
			$this->logger->debug( $error, __CLASS__ );
			return $error;
		}

		return array(
			'success' => true,
			'rates'   => property_exists( $response, 'rates' ) ? $response->rates : new stdClass(),
		);
	}
}
