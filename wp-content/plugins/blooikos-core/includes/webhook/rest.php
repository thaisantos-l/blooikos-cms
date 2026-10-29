<?php
/**
 * Publicação assíncrona do frontend Blooikos.
 *
 * @package BlooikosCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Endpoint GET de metadados, sem consolidação ou HTTP.
 *
 * @return WP_REST_Response
 */
function blooikos_core_rebuild_rest() {
	$state    = blooikos_core_rebuild_state();
	$ready    = $state && ! empty( $state['projectionValid'] ) && '0' !== $state['contentVersion'] && $state['dirtySequence'] === $state['processedSequence'] && ! blooikos_core_rebuild_writing();
	$response = new WP_REST_Response( $ready ? blooikos_core_rebuild_version( $state ) : array( 'code' => 'CONTENT_NOT_READY' ), $ready ? 200 : 409 );
	$response->header( 'Cache-Control', 'no-store, max-age=0' );
	$response->header( 'X-Robots-Tag', 'noindex' );
	return $response;
}
/**
 * Registra somente leitura pública, nunca callback de build.
 *
 * @return void
 */
function blooikos_core_rebuild_routes() {
	register_rest_route(
		'blooikos/v1',
		'/content-version',
		array(
			'methods'             => 'GET',
			'callback'            => 'blooikos_core_rebuild_rest',
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'blooikos_core_rebuild_routes' );
