<?php
/**
 * Publicação assíncrona do frontend Blooikos.
 *
 * @package BlooikosCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/** Canonical JSON v1: ASCII keys sorted, UTF-8 strings, ordered lists, safe integers.
 *
 * @param mixed $value JSON value.
 * @return string
 * @throws RuntimeException Valor fora do protocolo.
 */
function blooikos_core_rebuild_canonical( $value ) {
	$object = is_object( $value );
	if ( $object ) {
		$value = get_object_vars( $value );
	}
	if ( is_array( $value ) ) {
		$list = ! $object && array_is_list( $value );
		if ( ! $list ) {
			ksort( $value, SORT_STRING );
		}
		$parts = array();
		foreach ( $value as $key => $item ) {
			if ( ! $list && ! preg_match( '/^[A-Za-z_][A-Za-z_0-9]*$/D', (string) $key ) ) {
				throw new RuntimeException( 'INVALID_CANONICAL_KEY' );
			}
			$parts[] = ( $list ? '' : wp_json_encode( (string) $key ) . ':' ) . blooikos_core_rebuild_canonical( $item );
		}
		return ( $list ? '[' : '{' ) . implode( ',', $parts ) . ( $list ? ']' : '}' );
	}
	if ( is_float( $value ) || ( is_int( $value ) && abs( $value ) > 9007199254740991 ) ) {
		throw new RuntimeException( 'INVALID_CANONICAL_NUMBER' );
	}
	$json = wp_json_encode( $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_LINE_TERMINATORS );
	if ( false === $json ) {
		throw new RuntimeException( 'INVALID_CANONICAL_VALUE' );
	}
	return $json;
}
/**
 * Projeção pública sem herdar admin ou WP-CLI.
 *
 * @param string $media_revision Marcador.
 * @param string $previous_media_digest Hash anterior dos arquivos locais.
 * @return array
 * @throws RuntimeException Código seguro de diagnóstico.
 */
function blooikos_core_rebuild_projection( $media_revision, $previous_media_digest = '' ) {
	if ( blooikos_core_get_dependency_issues() || ! function_exists( 'blooikos_core_graphql_anonymous' ) ) {
		throw new RuntimeException( 'DEPENDENCIES_UNAVAILABLE' );
	}
	acf_get_store( 'values' )->reset(); // Um tick não herda valores lidos por outra tarefa no mesmo processo.
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Artefato local versionado, sem URL.
	$operation = file_get_contents( dirname( __DIR__, 2 ) . '/contracts/graphql/v1/operations.graphql' );
	$response  = blooikos_core_graphql_anonymous(
		static function () use ( $operation ) {
			return graphql(
				array(
					'query'         => $operation,
					'operationName' => 'BlooikosHeaderFooter',
				)
			);
		}
	);
	$result    = blooikos_core_graphql_validate_response( $response, 'poc' );
	if ( ! $result['valid'] ) {
		throw new RuntimeException( 'EDITORIAL_INVALID' );
	}
	$media_digest = blooikos_core_rebuild_media_digest();
	if ( $media_digest !== $previous_media_digest ) {
		$media_revision = blooikos_core_rebuild_increment( $media_revision );
	}
	$data_digest      = hash( 'sha256', blooikos_core_rebuild_canonical( $response['data'] ) );
	$operation_digest = hash( 'sha256', $operation );
	$digest           = hash(
		'sha256',
		blooikos_core_rebuild_canonical(
			array(
				'protocolVersion' => '1',
				'profile'         => 'poc',
				'operationDigest' => $operation_digest,
				'dataDigest'      => $data_digest,
				'mediaRevision'   => $media_revision,
			)
		)
	);
	return array(
		'mediaDigest'     => $media_digest,
		'mediaRevision'   => $media_revision,
		'dataDigest'      => $data_digest,
		'operationDigest' => $operation_digest,
		'contentDigest'   => $digest,
	);
}
/**
 * Resposta de metadados estável.
 *
 * @param array $state Estado consolidado.
 * @return array
 */
function blooikos_core_rebuild_version( $state ) {
	return array_intersect_key( $state, array_flip( array( 'epoch', 'contentVersion', 'contentDigest', 'dataDigest', 'operationDigest', 'mediaRevision' ) ) ) + array(
		'protocolVersion' => '1',
		'profile'         => 'poc',
	);
}

/** Hash dos dois arquivos referenciados, executado somente pelo worker.
 *
 * @return string
 * @throws RuntimeException Mídia ausente, externa ao diretório ou grande demais.
 */
function blooikos_core_rebuild_media_digest() {
	$uploads = wp_get_upload_dir();
	$root    = realpath( $uploads['basedir'] );
	$hashes  = array();
	foreach ( blooikos_core_rebuild_dependencies()['media'] as $id ) {
		if ( ! $id ) {
			$hashes[] = null;
			continue;
		}
		$file = get_attached_file( $id, true );
		$real = $file ? realpath( $file ) : false;
		if ( ! $root || ! $real || ! str_starts_with( $real, $root . DIRECTORY_SEPARATOR ) || ! is_file( $real ) || filesize( $real ) > 16777216 ) {
			throw new RuntimeException( 'MEDIA_UNAVAILABLE' );
		}
		$files    = array( $real );
		$metadata = wp_get_attachment_metadata( $id );
		foreach ( $metadata['sizes'] ?? array() as $size ) {
			$files[] = dirname( $real ) . '/' . $size['file']; }
		if ( count( $files ) > 33 ) {
			throw new RuntimeException( 'MEDIA_FILE_LIMIT' ); }
		sort( $files, SORT_STRING );
		$bytes = 0;
		foreach ( $files as $file ) {
			$resolved = realpath( $file );
			if ( ! $resolved || ! str_starts_with( $resolved, $root . DIRECTORY_SEPARATOR ) || ! is_file( $resolved ) ) {
				throw new RuntimeException( 'MEDIA_UNAVAILABLE' ); }
			$bytes += filesize( $resolved );
			if ( $bytes > 16777216 ) {
				throw new RuntimeException( 'MEDIA_BYTE_LIMIT' ); }
			$hashes[] = hash_file( 'sha256', $resolved );
		}
	}
	return hash( 'sha256', blooikos_core_rebuild_canonical( $hashes ) );
}
