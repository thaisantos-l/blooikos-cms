<?php
/**
 * Consistent, anonymous public content. Runtime recovery never dispatches HTTP.
 *
 * @package BlooikosCore
 */

/** Whether editorial delivery is independent of code releases.
 *
 * @return bool
 */
function blooikos_core_content_runtime() {
	return defined( 'BLOOIKOS_CONTENT_DELIVERY' ) && 'runtime' === BLOOIKOS_CONTENT_DELIVERY;
}

/** Consolidate one stable revision after save, or recover on the next read.
 * Never waits for another writer/lease, loops, calls the worker or dispatches HTTP.
 * The existing fenced lease and sequence comparison discard concurrent edits.
 *
 * @return void
 */
function blooikos_core_content_refresh() {
	global $wpdb;
	if ( ! blooikos_core_content_runtime() || isset( $GLOBALS['blooikos_core_rebuild_fault'] ) ) {
		return;
	}
	$lease = null;
	try {
		$state = blooikos_core_rebuild_state();
		if ( ! $state || $wpdb->last_error || $state['dirtySequence'] === $state['processedSequence'] || blooikos_core_rebuild_writing( true ) || $wpdb->last_error ) {
			return;
		}
		$lease = blooikos_core_rebuild_acquire( false );
		if ( $lease ) {
			blooikos_core_rebuild_consolidate( $lease, true );
		}
	} catch ( Throwable $error ) {
		$GLOBALS['blooikos_core_rebuild_fault'] = 'CONTENT_UNAVAILABLE';
	} finally {
		if ( $lease ) {
			try {
				blooikos_core_rebuild_change(
					static function ( &$state ) {
						$state['lease'] = null;
					},
					$lease
				);
			} catch ( Throwable $error ) {
				$GLOBALS['blooikos_core_rebuild_fault'] = 'CONTENT_UNAVAILABLE';
			}
		}
	}
}

/** Route-local exact allowlist, separate from Contact.
 *
 * @param string $origin Origin header.
 * @return bool
 */
function blooikos_core_content_origin_allowed( $origin ) {
	$parts = wp_parse_url( $origin );
	if ( ! is_array( $parts ) || ! isset( $parts['scheme'], $parts['host'] ) || array_diff( array_keys( $parts ), array( 'scheme', 'host', 'port' ) ) ) {
		return false;
	}
	$canonical = $parts['scheme'] . '://' . $parts['host'] . ( isset( $parts['port'] ) ? ':' . $parts['port'] : '' );
	$local     = 'http' === $parts['scheme'] && 'local' === wp_get_environment_type() && in_array( $parts['host'], array( 'localhost', '127.0.0.1', '[::1]' ), true );
	$origins   = defined( 'BLOOIKOS_CONTENT_ALLOWED_ORIGINS' ) ? BLOOIKOS_CONTENT_ALLOWED_ORIGINS : array( 'https://blooikos.com.br' );
	return $origin === $canonical && ! preg_match( '/[\s,\\\\]/', $origin ) && ( 'https' === $parts['scheme'] || $local ) && is_array( $origins ) && in_array( $origin, $origins, true );
}

/** Read live storage and fail closed, independently of release state.
 *
 * @return array
 * @throws RuntimeException Safe protocol code.
 */
function blooikos_core_content_ready_state() {
	global $wpdb;
	$state = blooikos_core_rebuild_state();
	if ( ! $state || $wpdb->last_error || isset( $GLOBALS['blooikos_core_rebuild_fault'] ) || blooikos_core_get_dependency_issues() ) {
		throw new RuntimeException( 'CONTENT_UNAVAILABLE' );
	}
	$writing = blooikos_core_rebuild_writing();
	if ( $wpdb->last_error ) {
		throw new RuntimeException( 'CONTENT_UNAVAILABLE' );
	}
	if ( $writing || $state['dirtySequence'] !== $state['processedSequence'] || '0' === $state['contentVersion'] || ( $state['profile'] ?? '' ) !== blooikos_core_rebuild_profile() ) {
		throw new RuntimeException( 'CONTENT_NOT_READY' );
	}
	if ( empty( $state['projectionValid'] ) ) {
		throw new RuntimeException( 'CONTENT_UNAVAILABLE' );
	}
	return $state;
}

/** Public permission, including consumers without Origin.
 *
 * @param WP_REST_Request $request Request.
 * @return bool
 */
function blooikos_core_content_permission( $request ) {
	$origin = $request->get_header( 'origin' );
	return ! $origin || blooikos_core_content_origin_allowed( $origin );
}

/** Safe error response without state or payload.
 *
 * @param string $code Protocol code.
 * @param int    $status HTTP status.
 * @return WP_REST_Response
 */
function blooikos_core_content_error( $code, $status ) {
	$response = new WP_REST_Response( array( 'code' => $code ), $status );
	$response->header( 'Cache-Control', 'no-store' );
	if ( 409 === $status ) {
		$response->header( 'Retry-After', blooikos_core_content_runtime() ? '1' : '5' );
	}
	return $response;
}

/** Serve one verified revision; cache is a single bounded, expiring entry.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function blooikos_core_content_get( $request ) {
	try {
		blooikos_core_content_refresh();
		$before  = blooikos_core_content_ready_state();
		$version = blooikos_core_rebuild_version( $before );
		$etag    = '"' . hash( 'sha256', '1:' . $version['epoch'] . ':' . $version['contentDigest'] ) . '"';
		$matches = array_map( 'trim', explode( ',', (string) $request->get_header( 'if-none-match' ) ) );
		if ( in_array( $etag, $matches, true ) || in_array( 'W/' . $etag, $matches, true ) || in_array( '*', $matches, true ) ) {
			// Unchanged polls only read the revision, never GraphQL, payload or media.
			$after = blooikos_core_content_ready_state();
			if ( blooikos_core_rebuild_version( $after ) !== $version || $after['dirtySequence'] !== $before['dirtySequence'] ) {
				return blooikos_core_content_error( 'CONTENT_NOT_READY', 409 );
			}
			$response = new WP_REST_Response( null, 304 );
			$response->header( 'ETag', $etag );
			$response->header( 'Cache-Control', 'public, max-age=0, must-revalidate' );
			return $response;
		}
		$cached   = get_transient( 'blooikos_public_content_v1' );
		$envelope = is_string( $cached ) && strlen( $cached ) <= 2097152 ? json_decode( $cached, true ) : null;
		if ( ! is_array( $envelope ) || ( $envelope['schemaVersion'] ?? null ) !== 1 || ( $envelope['version'] ?? null ) !== $version || ! isset( $envelope['data'] ) || hash( 'sha256', blooikos_core_rebuild_canonical( $envelope['data'] ) ) !== $version['dataDigest'] ) {
			$public = blooikos_core_rebuild_public_data();
			if ( $public['dataDigest'] !== $version['dataDigest'] || $public['operationDigest'] !== $version['operationDigest'] ) {
				return blooikos_core_content_error( 'CONTENT_NOT_READY', 409 );
			}
			$envelope = array(
				'schemaVersion' => 1,
				'version'       => $version,
				'data'          => $public['data'],
			);
		}
		$json = wp_json_encode( $envelope );
		if ( false === $json || strlen( $json ) > 2097152 ) {
			return blooikos_core_content_error( 'CONTENT_UNAVAILABLE', 503 );
		}
		$after = blooikos_core_content_ready_state();
		if ( blooikos_core_rebuild_version( $after ) !== $version || $after['dirtySequence'] !== $before['dirtySequence'] ) {
			return blooikos_core_content_error( 'CONTENT_NOT_READY', 409 );
		}
		if ( $cached !== $json ) {
			set_transient( 'blooikos_public_content_v1', $json, 300 );
		}
		$response = new WP_REST_Response( $envelope, 200 );
		$response->header( 'ETag', $etag );
		$response->header( 'Cache-Control', 'public, max-age=0, must-revalidate' );
		return $response;
	} catch ( Throwable $error ) {
		return blooikos_core_content_error( 'CONTENT_NOT_READY' === $error->getMessage() ? 'CONTENT_NOT_READY' : 'CONTENT_UNAVAILABLE', 'CONTENT_NOT_READY' === $error->getMessage() ? 409 : 503 );
	}
}

/** Restrict methods/parameters before core dispatch, including preflight.
 *
 * @param mixed           $result Prior result.
 * @param WP_REST_Server  $server Server.
 * @param WP_REST_Request $request Request.
 * @return mixed
 */
function blooikos_core_content_guard( $result, $server, $request ) {
	if ( '/blooikos/v1/site-content' !== rtrim( $request->get_route(), '/' ) ) {
		return $result;
	}
	if ( ! blooikos_core_content_permission( $request ) ) {
		return blooikos_core_content_error( 'ORIGIN_NOT_ALLOWED', 403 );
	}
	$method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : $request->get_method();
	if ( ! in_array( $method, array( 'GET', 'HEAD', 'OPTIONS' ), true ) || ! in_array( $request->get_method(), array( 'GET', 'HEAD', 'OPTIONS' ), true ) ) {
		return blooikos_core_content_error( 'METHOD_NOT_ALLOWED', 405 );
	}
	if ( array_diff( array_keys( $request->get_query_params() ), array( 'rest_route' ) ) || '' !== $request->get_body() ) {
		return blooikos_core_content_error( 'INVALID_REQUEST', 400 );
	}
	if ( 'OPTIONS' === $request->get_method() ) {
		$method  = $request->get_header( 'access-control-request-method' );
		$headers = array_filter( array_map( 'trim', explode( ',', strtolower( (string) $request->get_header( 'access-control-request-headers' ) ) ) ) );
		if ( ( $method && ! in_array( $method, array( 'GET', 'HEAD' ), true ) ) || array_diff( $headers, array( 'if-none-match', 'cache-control' ) ) ) {
			return blooikos_core_content_error( 'ORIGIN_NOT_ALLOWED', 403 );
		}
		return new WP_REST_Response( null, 204 );
	}
	return $result;
}

/** Replace broad core CORS only here, including errors and 304.
 *
 * @param bool             $served Served.
 * @param WP_HTTP_Response $result Response.
 * @param WP_REST_Request  $request Request.
 * @return bool
 */
function blooikos_core_content_headers( $served, $result, $request ) {
	if ( '/blooikos/v1/site-content' !== rtrim( $request->get_route(), '/' ) ) {
		return $served;
	}
	foreach ( array( 'Access-Control-Allow-Origin', 'Access-Control-Allow-Credentials', 'Access-Control-Allow-Methods', 'Access-Control-Allow-Headers', 'Access-Control-Expose-Headers' ) as $name ) {
		header_remove( $name );
	}
	$headers = $result->get_headers();
	header( 'Cache-Control: ' . ( $headers['Cache-Control'] ?? 'no-store' ) );
	header( 'X-Robots-Tag: noindex' );
	header( 'Vary: Origin', false );
	$origin = $request->get_header( 'origin' );
	if ( $origin && blooikos_core_content_origin_allowed( $origin ) ) {
		header( 'Access-Control-Allow-Origin: ' . $origin );
		header( 'Access-Control-Allow-Methods: GET, HEAD, OPTIONS' );
		header( 'Access-Control-Allow-Headers: If-None-Match, Cache-Control' );
		header( 'Access-Control-Expose-Headers: ETag, Retry-After' );
	}
	if ( array_diff( array_keys( $request->get_query_params() ), array( 'rest_route' ) ) ) {
		// Core can wrap errors in _envelope (HTTP 200) or JSONP after dispatch.
		// This route has one closed JSON protocol, including rejected parameters.
		$allowed = blooikos_core_content_permission( $request );
		status_header( $allowed ? 400 : 403 );
		header( 'Content-Type: application/json; charset=UTF-8' );
		header( 'Cache-Control: no-store' );
		header_remove( 'ETag' );
		if ( 'HEAD' !== $request->get_method() ) {
			echo wp_json_encode( array( 'code' => $allowed ? 'INVALID_REQUEST' : 'ORIGIN_NOT_ALLOWED' ) );
		}
		return true;
	}
	return $served;
}

/** Register read-only route with explicit public permission.
 *
 * @return void
 */
function blooikos_core_content_routes() {
	register_rest_route(
		'blooikos/v1',
		'/site-content',
		array(
			'methods'             => 'GET, HEAD',
			'callback'            => 'blooikos_core_content_get',
			'permission_callback' => 'blooikos_core_content_permission',
		)
	);
}
add_action( 'rest_api_init', 'blooikos_core_content_routes' );
add_filter( 'rest_pre_dispatch', 'blooikos_core_content_guard', 5, 3 );
add_filter( 'rest_pre_serve_request', 'blooikos_core_content_headers', PHP_INT_MAX, 3 );
