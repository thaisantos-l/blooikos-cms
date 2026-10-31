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
 * Produção explicitamente habilitada e presa à origem esperada.
 *
 * @return bool
 */
function blooikos_core_rebuild_enabled() {
	return defined( 'BLOOIKOS_REBUILD_ENABLED' ) && true === BLOOIKOS_REBUILD_ENABLED && defined( 'BLOOIKOS_REBUILD_ENVIRONMENT' ) && 'production' === BLOOIKOS_REBUILD_ENVIRONMENT && 'https://cms.blooikos.com.br' === untrailingslashit( home_url() ) && defined( 'BLOOIKOS_REBUILD_GITHUB_TOKEN' ) && is_string( BLOOIKOS_REBUILD_GITHUB_TOKEN ) && '' !== BLOOIKOS_REBUILD_GITHUB_TOKEN;
}
/**
 * IDs externos são strings decimais.
 *
 * @param mixed $value Valor.
 * @return bool
 */
function blooikos_core_rebuild_decimal( $value ) {
	return is_string( $value ) && 1 === preg_match( '/^[1-9][0-9]{0,29}$/D', $value );
}
/**
 * UUID de pedido/ambiente.
 *
 * @param mixed $value Valor.
 * @return bool
 */
function blooikos_core_rebuild_uuid( $value ) {
	return is_string( $value ) && 1 === preg_match( '/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/D', $value );
}
/**
 * Payload fechado, validado antes do HTTP.
 *
 * @param array $request Pedido.
 * @return array
 * @throws RuntimeException Código seguro de diagnóstico.
 */
function blooikos_core_rebuild_payload( $request ) {
	if ( ! blooikos_core_rebuild_uuid( $request['id'] ) || ! blooikos_core_rebuild_uuid( $request['epoch'] ) || ! blooikos_core_rebuild_decimal( $request['version'] ) || ! preg_match( '/^[a-f0-9]{64}$/D', $request['digest'] ) ) {
		throw new RuntimeException( 'INVALID_DISPATCH' );
	}
	return array(
		'ref'    => 'main',
		'inputs' => array(
			'operation'       => 'deploy',
			'content_epoch'   => $request['epoch'],
			'content_version' => $request['version'],
			'content_digest'  => $request['digest'],
			'request_id'      => $request['id'],
		),
	);
}
/** HTTP restrito e limitado. Nunca devolve corpo bruto para log/admin.
 *
 * @param string     $path Caminho API fechado pelo chamador, ou marker.
 * @param array|null $payload POST quando presente.
 * @return array
 * @throws RuntimeException Código seguro de diagnóstico.
 */
function blooikos_core_rebuild_http( $path, $payload = null ) {
	if ( ! blooikos_core_rebuild_enabled() ) {
		return array(
			'code'   => 'DISABLED',
			'status' => 0,
			'data'   => null,
			'delay'  => 60,
		);
	}
	if ( ( $GLOBALS['blooikos_core_rebuild_calls'] ?? 0 ) >= 3 || microtime( true ) >= ( $GLOBALS['blooikos_core_rebuild_deadline'] ?? 0 ) - 11 ) {
		throw new RuntimeException( 'TICK_BUDGET' );
	}
	++$GLOBALS['blooikos_core_rebuild_calls'];
	$public  = in_array( $path, array( 'marker', 'html' ), true );
	$limit   = 'html' === $path ? 2097152 : 262144;
	$url     = $public ? 'https://blooikos.com.br/' . ( 'marker' === $path ? 'blooikos-release.json' : '' ) : 'https://api.github.com/repos/thaisantos-l/blooikos-frontend/' . $path;
	$headers = array(
		'Accept'        => 'application/vnd.github+json',
		'User-Agent'    => 'Blooikos-Rebuild/1',
		'Cache-Control' => 'no-cache',
	);
	if ( ! $public ) {
		$headers['Authorization']        = 'Bearer ' . BLOOIKOS_REBUILD_GITHUB_TOKEN;
		$headers['X-GitHub-Api-Version'] = '2026-03-10';
	}
	$args = array(
		'method'              => $payload ? 'POST' : 'GET',
		'timeout'             => 10,
		'redirection'         => 0,
		'sslverify'           => true,
		'limit_response_size' => $limit + 1,
		'headers'             => $headers,
	);
	if ( $payload ) {
		$args['headers']['Content-Type'] = 'application/json';
		$args['body']                    = wp_json_encode( $payload );
	}
	$response = wp_remote_request( $url, $args );
	if ( is_wp_error( $response ) ) {
		return array(
			'code'   => 'NETWORK_UNCERTAIN',
			'status' => 0,
			'data'   => null,
			'delay'  => 60,
		);
	}
	$status  = wp_remote_retrieve_response_code( $response );
	$body    = wp_remote_retrieve_body( $response );
	$retry   = wp_remote_retrieve_header( $response, 'retry-after' );
	$delay   = ctype_digit( (string) $retry ) ? (int) $retry : max( 0, (int) strtotime( (string) $retry ) - time() );
	$reset   = (int) wp_remote_retrieve_header( $response, 'x-ratelimit-reset' );
	$limited = 429 === $status || ( 403 === $status && ( $retry || '0' === (string) wp_remote_retrieve_header( $response, 'x-ratelimit-remaining' ) ) );
	$code    = $limited ? 'RATE_LIMITED' : ( in_array( $status, array( 401, 403, 404, 422 ), true ) && ! $public ? 'CONFIGURATION_BLOCKED' : ( $status >= 500 || 0 === $status ? 'REMOTE_UNAVAILABLE' : '' ) );
	$cache   = array_map( 'trim', explode( ',', strtolower( (string) wp_remote_retrieve_header( $response, 'cache-control' ) ) ) );
	$fresh   = in_array( 'no-store', $cache, true ) || ( 'html' === $path && in_array( 'no-cache', $cache, true ) && in_array( 'must-revalidate', $cache, true ) );
	if ( $public && 200 === $status && ( ! $fresh || ( 'marker' === $path && ! str_contains( strtolower( (string) wp_remote_retrieve_header( $response, 'x-robots-tag' ) ), 'noindex' ) ) ) ) {
		$code = 'UNCACHEABLE_PUBLICATION_REQUIRED';
	}
	if ( strlen( $body ) > $limit || ( $status >= 300 && $status < 400 ) ) {
		$code = 'INVALID_REMOTE_RESPONSE';
	}
	return array(
		'code'   => $code,
		'status' => $status,
		'data'   => $code || 'html' === $path ? null : json_decode( $body, true, 32, JSON_BIGINT_AS_STRING ),
		'sha256' => ! $code && 200 === $status ? hash( 'sha256', $body ) : '',
		'delay'  => max( 60, $delay, $limited ? $reset - time() : 0 ),
		'paused' => 'paused' === wp_remote_retrieve_header( $response, 'x-blooikos-automation' ),
	);
}
/**
 * Proveniência do run, independente de labels.
 *
 * @param array $run Run.
 * @return bool
 */
function blooikos_core_rebuild_run_valid( $run ) {
	return 'thaisantos-l/blooikos-frontend' === ( $run['repository']['full_name'] ?? '' ) && 'thaisantos-l/blooikos-frontend' === ( $run['head_repository']['full_name'] ?? '' ) && 'main' === ( $run['head_branch'] ?? '' ) && '.github/workflows/deploy-production.yml' === ( $run['path'] ?? '' ) && in_array( $run['event'] ?? '', array( 'push', 'workflow_dispatch' ), true ) && preg_match( '/^[a-f0-9]{40}$/D', $run['head_sha'] ?? '' ) && blooikos_core_rebuild_decimal( (string) ( $run['id'] ?? '' ) ) && blooikos_core_rebuild_decimal( (string) ( $run['run_attempt'] ?? '' ) );
}
/**
 * Marcador v2 de conteúdo esperado.
 *
 * @param array|null $marker Marcador.
 * @param array      $request Pedido.
 * @return bool
 */
function blooikos_core_rebuild_marker_matches( $marker, $request ) {
	return is_array( $marker ) && '1' === ( $marker['protocolVersion'] ?? '' ) && 2 === ( $marker['schemaVersion'] ?? 0 ) && ( $marker['content']['epoch'] ?? '' ) === $request['epoch'] && ( $marker['content']['contentVersion'] ?? '' ) === $request['version'] && ( $marker['content']['contentDigest'] ?? '' ) === $request['digest'] && blooikos_core_rebuild_decimal( $marker['runId'] ?? null ) && blooikos_core_rebuild_decimal( $marker['attempt'] ?? null ) && preg_match( '/^[a-f0-9]{40}$/D', $marker['commitSHA'] ?? '' ) && ( $marker['releaseId'] ?? '' ) === $marker['commitSHA'] . '-' . $marker['runId'] . '-' . $marker['attempt'];
}
