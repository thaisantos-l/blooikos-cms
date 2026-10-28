<?php
/**
 * Visibilidade e conexões do modelo público Blooikos.
 *
 * @package BlooikosCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reconhece publicação sem senha sem ampliar as flags WordPress do CPT.
 *
 * @param WP_Post|null $post Post consultado.
 * @return bool
 */
function blooikos_core_graphql_is_published( $post ) {
	return $post instanceof WP_Post && 'publish' === $post->post_status && '' === $post->post_password;
}

/**
 * Abre somente empresas publicadas; demais casos mantêm capabilities nativas.
 *
 * @param bool|null $is_private Decisão anterior.
 * @param string    $model Nome do modelo nativo.
 * @param mixed     $data Dados internos do modelo.
 * @return bool|null
 */
function blooikos_core_graphql_company_visibility( $is_private, $model, $data ) {
	if ( 'PostObject' === $model && $data instanceof WP_Post && 'blooikos_company' === $data->post_type && blooikos_core_graphql_is_published( $data ) ) {
		return false;
	}
	return $is_private;
}

/**
 * Produz erro público sem IDs, títulos, status ou detalhes internos.
 *
 * @param string $code Código estável do contrato.
 * @return void
 * @throws \GraphQL\Error\Error Erro seguro de conteúdo.
 */
function blooikos_core_graphql_fail( $code = 'BLOOIKOS_CONTENT_INVALID' ) {
	$messages = array(
		'BLOOIKOS_CONTENT_INVALID'       => __( 'Um valor editorial não atende ao contrato.', 'blooikos-core' ),
		'BLOOIKOS_REFERENCE_UNAVAILABLE' => __( 'Uma referência editorial não está disponível publicamente.', 'blooikos-core' ),
		'BLOOIKOS_LIMIT_EXCEEDED'        => __( 'Uma coleção editorial excede o limite do contrato.', 'blooikos-core' ),
	);
	// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Erro GraphQL serializado em JSON, com mensagem e código de uma lista fechada.
	throw new \GraphQL\Error\Error( $messages[ $code ], null, null, null, null, null, array( 'code' => $code ) );
}

/**
 * Verifica toda a seleção antes de paginar, evitando omissão silenciosa.
 *
 * @param mixed $value Valor bruto ACF.
 * @param array $field Definição do campo.
 * @return int[]
 */
function blooikos_core_graphql_company_ids( $value, $field ) {
	if ( null === $value || false === $value || '' === $value || array() === $value ) {
		return array();
	}
	$ids = 'post_object' === $field['type'] ? array( $value ) : $value;
	if ( ! is_array( $ids ) ) {
		blooikos_core_graphql_fail( 'BLOOIKOS_REFERENCE_UNAVAILABLE' );
	}
	if ( count( $ids ) > ( $field['max'] ?? 1 ) ) {
		blooikos_core_graphql_fail( 'BLOOIKOS_LIMIT_EXCEEDED' );
	}
	foreach ( $ids as $id ) {
		if ( ! is_scalar( $id ) || ! ctype_digit( (string) $id ) || (int) $id < 1 ) {
			blooikos_core_graphql_fail( 'BLOOIKOS_REFERENCE_UNAVAILABLE' );
		}
	}
	$ids = array_map( 'intval', $ids );
	if ( count( array_unique( $ids ) ) !== count( $ids ) ) {
		blooikos_core_graphql_fail( 'BLOOIKOS_REFERENCE_UNAVAILABLE' );
	}
	// Cache nativo: uma leitura em lote, inclusive metadados reutilizados pelos campos.
	_prime_post_caches( $ids, false, true );
	foreach ( $ids as $id ) {
		$post = get_post( $id );
		if ( ! blooikos_core_graphql_is_published( $post ) || 'blooikos_company' !== $post->post_type ) {
			blooikos_core_graphql_fail( 'BLOOIKOS_REFERENCE_UNAVAILABLE' );
		}
	}
	return $ids;
}

/**
 * Usa paginação/cursores/loaders nativos, sem aceitar filtros concorrentes.
 *
 * @param mixed $value Valor ACF.
 * @param array $field Campo permitido.
 * @param mixed $source Origem GraphQL.
 * @param array $args Argumentos da conexão.
 * @param mixed $context Contexto WPGraphQL.
 * @param mixed $info Informações da resolução.
 * @return mixed
 */
function blooikos_core_graphql_company_connection( $value, $field, $source, $args, $context, $info ) {
	$ids = blooikos_core_graphql_company_ids( $value, $field );
	if ( ! $ids ) {
		return null;
	}
	$args['where'] = array( 'in' => $ids );
	$resolver      = new \WPGraphQL\Data\Connection\PostObjectConnectionResolver( $source, $args, $context, $info, 'blooikos_company' );
	if ( 'post_object' === $field['type'] ) {
		$resolver->one_to_one();
	}
	return $resolver->get_connection();
}

/**
 * Mantém a autorização nativa da mídia e de seu pai.
 *
 * @param mixed $value ID armazenado.
 * @param mixed $source Origem GraphQL.
 * @param array $args Argumentos.
 * @param mixed $context Contexto.
 * @param mixed $info Informações da resolução.
 * @return mixed
 */
function blooikos_core_graphql_image_connection( $value, $source, $args, $context, $info ) {
	if ( null === $value || false === $value || '' === $value ) {
		return null;
	}
	if ( ! is_scalar( $value ) || ! ctype_digit( (string) $value ) || ! wp_attachment_is_image( (int) $value ) || ! in_array( get_post_mime_type( (int) $value ), array( 'image/jpeg', 'image/png', 'image/webp' ), true ) ) {
		blooikos_core_graphql_fail();
	}
	$resolver = new \WPGraphQL\Data\Connection\PostObjectConnectionResolver( $source, $args, $context, $info, 'attachment' );
	return $resolver->one_to_one()->set_query_arg( 'p', (int) $value )->get_connection();
}

add_filter( 'graphql_pre_model_data_is_private', 'blooikos_core_graphql_company_visibility', 10, 3 );
