<?php
/**
 * Additive public SEO endpoint and independent CMS HTML indexation protection.
 *
 * @package BlooikosCore
 */

/** Read one bounded public projection; infrastructure failures are not empty content.
 *
 * @return WP_REST_Response
 */
function blooikos_core_seo_get() {
	try {
		$data = blooikos_core_seo_payload();
		$json = wp_json_encode( $data );
		if ( false === $json || strlen( $json ) > 65536 ) {
			return blooikos_core_content_error( 'SEO_UNAVAILABLE', 503 );
		}
		$response = new WP_REST_Response( $data, 200 );
		$response->header( 'Cache-Control', 'no-store' );
		$response->header( 'X-Robots-Tag', 'noindex' );
		return $response;
	} catch ( Throwable $error ) {
		return blooikos_core_content_error( 'SEO_UNAVAILABLE', 503 );
	}
}

/** Restrict methods/parameters before core dispatch, including preflight.
 *
 * @param mixed           $result Prior result.
 * @param WP_REST_Server  $server Server.
 * @param WP_REST_Request $request Request.
 * @return mixed
 */
function blooikos_core_seo_guard( $result, $server, $request ) {
	if ( '/blooikos/v1/site-seo' !== rtrim( $request->get_route(), '/' ) ) {
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
function blooikos_core_seo_headers( $served, $result, $request ) {
	if ( '/blooikos/v1/site-seo' !== rtrim( $request->get_route(), '/' ) ) {
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

/** Register the read-only route, independent of optional Yoast/ACF availability.
 *
 * @return void
 */
function blooikos_core_seo_routes() {
	register_rest_route(
		'blooikos/v1',
		'/site-seo',
		array(
			'methods'             => 'GET, HEAD',
			'callback'            => 'blooikos_core_seo_get',
			'permission_callback' => 'blooikos_core_content_permission',
		)
	);
}

/** Protect CMS HTML without changing Yoast's editorial directives or WP options.
 * template_redirect is not run for the REST API, admin or GraphQL requests.
 *
 * @return void
 */
function blooikos_core_seo_html_headers() {
	if ( ! is_admin() && ! wp_is_json_request() && ! is_feed() && ! headers_sent() ) {
		header( 'X-Robots-Tag: noindex, nofollow' );
	}
}
add_action( 'rest_api_init', 'blooikos_core_seo_routes' );
add_filter( 'rest_pre_dispatch', 'blooikos_core_seo_guard', 5, 3 );
add_filter( 'rest_pre_serve_request', 'blooikos_core_seo_headers', PHP_INT_MAX, 3 );
add_action( 'template_redirect', 'blooikos_core_seo_html_headers', 0 );
