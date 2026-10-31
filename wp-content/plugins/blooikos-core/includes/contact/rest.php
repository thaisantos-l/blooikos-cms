<?php
/**
 * Anonymous contact REST route with exact, route-local CORS.
 *
 * @package BlooikosCore
 */

/**
 * Keep request metadata through WordPress route attribute replacement.
 *
 * @param WP_REST_Request $request Request.
 * @return array Server UUID and monotonic request start.
 */
function blooikos_core_contact_timing( $request ) {
	static $timings;
	if ( null === $timings ) {
		$timings = new WeakMap();
	}
	if ( ! isset( $timings[ $request ] ) ) {
		$timings[ $request ] = array( wp_generate_uuid4(), microtime( true ) );
	}
	return $timings[ $request ];
}

/**
 * Check a canonical origin against the instance's exact allowlist.
 *
 * @param string $origin Origin header.
 * @return bool
 */
function blooikos_core_contact_origin_allowed( $origin ) {
	if ( ! is_string( $origin ) || '' === $origin ) {
		return false;
	}
	$parts = wp_parse_url( $origin );
	if ( ! is_array( $parts ) || ! isset( $parts['scheme'], $parts['host'] ) || array_diff( array_keys( $parts ), array( 'scheme', 'host', 'port' ) ) ) {
		return false;
	}
	$canonical = $parts['scheme'] . '://' . $parts['host'] . ( isset( $parts['port'] ) ? ':' . $parts['port'] : '' );
	if ( $origin !== $canonical || preg_match( '/[\s,\\\\]/', $origin ) ) {
		return false;
	}
	$secure  = 'https' === $parts['scheme'];
	$local   = 'http' === $parts['scheme'] && 'local' === wp_get_environment_type() && in_array( $parts['host'], array( 'localhost', '127.0.0.1', '[::1]' ), true );
	$origins = defined( 'BLOOIKOS_CONTACT_ALLOWED_ORIGINS' ) ? BLOOIKOS_CONTACT_ALLOWED_ORIGINS : array( 'https://blooikos.com.br' );
	return ( $secure || $local ) && is_array( $origins ) && in_array( $origin, $origins, true );
}

/**
 * Public permission callback; origin is required, never an authentication claim.
 *
 * @param WP_REST_Request $request Request.
 * @return bool
 */
function blooikos_core_contact_permission( $request ) {
	return blooikos_core_contact_origin_allowed( $request->get_header( 'origin' ) );
}

/**
 * Return only protocol metadata; never echo the submitted payload.
 *
 * @param WP_REST_Request $request Request.
 * @param string          $code Result code.
 * @param int             $status HTTP status.
 * @param array           $extra Field codes or retry duration.
 * @return WP_REST_Response
 */
function blooikos_core_contact_response( $request, $code, $status, $extra = array() ) {
	$timing     = blooikos_core_contact_timing( $request );
	$request_id = $timing[0];
	$started    = $timing[1];
	$data       = array_merge(
		array(
			'ok'        => 200 === $status,
			'code'      => $code,
			'requestId' => $request_id,
		),
		$extra
	);
	$response   = new WP_REST_Response( $data, $status );
	if ( isset( $extra['retryAfter'] ) ) {
		$response->header( 'Retry-After', (string) $extra['retryAfter'] );
	}
	if ( 'POST' === $request->get_method() ) {
		blooikos_core_contact_record( $request_id, $code, (int) round( ( microtime( true ) - $started ) * 1000 ) );
	}
	return $response;
}

/**
 * Early gates also cover malformed JSON and OPTIONS before core dispatch.
 *
 * @param mixed           $result Existing result.
 * @param WP_REST_Server  $server REST server.
 * @param WP_REST_Request $request Request.
 * @return mixed
 */
function blooikos_core_contact_guard( $result, $server, $request ) {
	if ( '/blooikos/v1/contact' !== rtrim( $request->get_route(), '/' ) || ! in_array( $request->get_method(), array( 'POST', 'OPTIONS' ), true ) ) {
		return $result;
	}
	blooikos_core_contact_timing( $request );
	if ( ! blooikos_core_contact_permission( $request ) ) {
		return blooikos_core_contact_response( $request, 'ORIGIN_NOT_ALLOWED', 403 );
	}
	if ( 'OPTIONS' === $request->get_method() ) {
		$method  = $request->get_header( 'access-control-request-method' );
		$headers = array_filter( array_map( 'trim', explode( ',', strtolower( (string) $request->get_header( 'access-control-request-headers' ) ) ) ) );
		if ( ( $method && 'POST' !== $method ) || array_diff( $headers, array( 'content-type' ) ) ) {
			return blooikos_core_contact_response( $request, 'ORIGIN_NOT_ALLOWED', 403 );
		}
		return new WP_REST_Response( null, 204 );
	}
	if ( strlen( $request->get_body() ) > 32768 ) {
		return blooikos_core_contact_response( $request, 'PAYLOAD_TOO_LARGE', 413 );
	}
	if ( ! preg_match( '/^application\/json(?:\s*;\s*charset=utf-8)?$/iD', (string) $request->get_header( 'content-type' ) ) ) {
		return blooikos_core_contact_response( $request, 'UNSUPPORTED_MEDIA_TYPE', 415 );
	}
	if ( ! json_decode( $request->get_body() ) instanceof stdClass ) {
		return blooikos_core_contact_response( $request, 'INVALID_REQUEST', 400 );
	}
	return $result;
}

/**
 * Submit a validated contact request to the current configured recipient.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function blooikos_core_contact_submit( $request ) {
	$validated = blooikos_core_contact_validate( $request->get_body() );
	if ( isset( $validated['code'] ) ) {
		return blooikos_core_contact_response( $request, $validated['code'], 400, isset( $validated['fieldErrors'] ) ? array( 'fieldErrors' => $validated['fieldErrors'] ) : array() );
	}
	$recipient = get_option( 'options_blooikos_site_email', '' );
	if ( ! blooikos_core_contact_email( $recipient ) ) {
		return blooikos_core_contact_response( $request, 'CONTACT_UNAVAILABLE', 503 );
	}
	$reservation = blooikos_core_contact_reserve();
	if ( $reservation ) {
		return blooikos_core_contact_response( $request, $reservation['code'], isset( $reservation['retryAfter'] ) ? 429 : 503, isset( $reservation['retryAfter'] ) ? array( 'retryAfter' => $reservation['retryAfter'] ) : array() );
	}
	$values  = $validated['values'];
	$message = "Nome: {$values['name']}\nE-mail: {$values['email']}\nTelefone: {$values['phone']}\nEmpresa: {$values['company']}\n\nMensagem:\n{$values['message']}";
	try {
		$accepted = wp_mail( array( $recipient ), 'Contato pelo site Blooikos', $message, array( 'Content-Type: text/plain; charset=UTF-8', 'Reply-To: ' . $values['email'] ) );
	} catch ( Throwable $exception ) {
		$accepted = false;
	}
	return blooikos_core_contact_response( $request, true === $accepted ? 'CONTACT_ACCEPTED' : 'MAIL_UNAVAILABLE', true === $accepted ? 200 : 503 );
}

/**
 * Replace core's broad CORS only on this exact route, including errors.
 * Runs after rest_send_cors_headers; does not affect other APIs.
 *
 * @param bool             $served Whether response is already served.
 * @param WP_HTTP_Response $result Response.
 * @param WP_REST_Request  $request Request.
 * @return bool
 */
function blooikos_core_contact_headers( $served, $result, $request ) {
	if ( '/blooikos/v1/contact' !== rtrim( $request->get_route(), '/' ) ) {
		return $served;
	}
	foreach ( array( 'Access-Control-Allow-Origin', 'Access-Control-Allow-Credentials', 'Access-Control-Allow-Methods', 'Access-Control-Allow-Headers', 'Access-Control-Expose-Headers' ) as $header ) {
		header_remove( $header );
	}
	header( 'Cache-Control: no-store' );
	header( 'X-Robots-Tag: noindex' );
	header( 'Vary: Origin', false );
	$origin = $request->get_header( 'origin' );
	if ( blooikos_core_contact_origin_allowed( $origin ) ) {
		header( 'Access-Control-Allow-Origin: ' . $origin );
		header( 'Access-Control-Allow-Methods: POST, OPTIONS' );
		header( 'Access-Control-Allow-Headers: Content-Type' );
		header( 'Access-Control-Expose-Headers: Retry-After' );
	}
	return $served;
}

/** Register the contact route. */
function blooikos_core_contact_register() {
	register_rest_route(
		'blooikos/v1',
		'/contact',
		array(
			'methods'             => 'POST',
			'callback'            => 'blooikos_core_contact_submit',
			'permission_callback' => 'blooikos_core_contact_permission',
		)
	);
}

add_action( 'rest_api_init', 'blooikos_core_contact_register' );
add_filter( 'rest_pre_dispatch', 'blooikos_core_contact_guard', 5, 3 );
add_filter( 'rest_pre_serve_request', 'blooikos_core_contact_headers', PHP_INT_MAX, 3 );
