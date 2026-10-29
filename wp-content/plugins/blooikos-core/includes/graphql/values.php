<?php
/**
 * Leitura sem efeitos editoriais, antes dos formatadores do conector.
 *
 * @package BlooikosCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lê subcampo de linha ou campo do nó sem the_content/shortcodes/embeds.
 *
 * @param mixed $source Origem do resolver.
 * @param array $field Campo permitido.
 * @return mixed
 */
function blooikos_core_graphql_raw_value( $source, $field ) {
	foreach ( array( $field['key'], $field['name'] ) as $key ) {
		if ( is_array( $source ) && array_key_exists( $key, $source ) ) {
			return $source[ $key ];
		}
	}
	$node = $source['node'] ?? null;
	$id   = \WPGraphQL\Acf\Utils::get_node_acf_id( $node );
	return null === $id ? null : get_field( $field['key'], $id, false );
}

/**
 * Normaliza valores escalares preservando zero e false.
 *
 * @param mixed $value Valor bruto.
 * @param array $field Campo ACF.
 * @return mixed
 */
function blooikos_core_graphql_scalar( $value, $field ) {
	$type   = $field['type'];
	$absent = null === $value || false === $value || ( is_string( $value ) && '' === trim( $value ) );
	if ( 'true_false' === $type ) {
		if ( ! $absent && ! in_array( $value, array( 0, 1, '0', '1', true ), true ) ) {
			blooikos_core_graphql_fail();
		}
		return (bool) $value;
	}
	if ( 'select' === $type ) {
		if ( $absent ) {
			return null;
		}
		if ( is_array( $value ) ) {
			$value = 1 === count( $value ) ? reset( $value ) : null;
		}
		if ( ! is_string( $value ) || ! isset( $field['choices'][ $value ] ) ) {
			blooikos_core_graphql_fail();
		}
		return array( $value );
	}
	if ( $absent ) {
		return null;
	}
	if ( 'link' === $type ) {
		if ( ! is_array( $value ) || ! is_string( $value['title'] ?? null ) || '' === trim( $value['title'] ) || ! is_string( $value['url'] ?? null ) || '' === trim( $value['url'] ) || true !== blooikos_core_validate_link( $value ) ) {
			return null;
		}
		return blooikos_core_sanitize_field( $value, 0, $field );
	}
	if ( ! is_scalar( $value ) || true !== blooikos_core_validate_text( $value, $field ) ) {
		blooikos_core_graphql_fail();
	}
	$value = blooikos_core_sanitize_field( (string) $value, 0, $field );
	return '' === trim( $value ) ? null : $value;
}

/**
 * Mantém repetidores vazios como listas e rejeita dados legados acima do limite.
 *
 * @param mixed $value Linhas brutas.
 * @param array $field Campo repeater.
 * @return array
 */
function blooikos_core_graphql_rows( $value, $field ) {
	if ( null === $value || false === $value || '' === $value ) {
		return array();
	}
	if ( ! is_array( $value ) ) {
		blooikos_core_graphql_fail();
	}
	if ( count( $value ) > $field['max'] ) {
		blooikos_core_graphql_fail( 'BLOOIKOS_LIMIT_EXCEEDED' );
	}
	foreach ( $value as $row ) {
		if ( ! is_array( $row ) ) {
			blooikos_core_graphql_fail();
		}
	}
	return array_values( $value );
}

/**
 * Intercepta somente grupos/campos do mapa fechado, antes do conector.
 *
 * @param mixed  $unresolved Sentinela WPGraphQL; preservada nos demais campos.
 * @param mixed  $source Origem do resolver.
 * @param array  $args Argumentos públicos.
 * @param mixed  $context Contexto WPGraphQL.
 * @param mixed  $info Informações da resolução.
 * @param string $type_name Tipo proprietário.
 * @param string $field_name Nome público do campo.
 * @return mixed
 */
function blooikos_core_graphql_resolve( $unresolved, $source, $args, $context, $info, $type_name, $field_name ) {
	foreach ( blooikos_core_graphql_groups() as $group ) {
		if ( 'Page' !== $group['owner'] || $group['owner'] !== $type_name || lcfirst( $group['type'] ) !== $field_name ) {
			continue;
		}
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Propriedade pública do modelo WPGraphQL.
		$id   = $source->databaseId ?? 0;
		$post = get_post( $id );
		if ( ! $id || 'page' !== get_option( 'show_on_front' ) || (int) get_option( 'page_on_front' ) !== (int) $id || ( ! blooikos_core_graphql_is_published( $post ) && ! current_user_can( 'edit_post', $id ) ) ) {
			return null;
		}
		return $unresolved;
	}
	foreach ( blooikos_core_graphql_fields() as $key => $mapping ) {
		if ( $mapping['owner'] !== $type_name || $mapping['name'] !== $field_name ) {
			continue;
		}
		$field = acf_get_field( $key );
		$value = blooikos_core_graphql_raw_value( $source, $field );
		switch ( $field['type'] ) {
			case 'relationship':
			case 'post_object':
				return blooikos_core_graphql_company_connection( $value, $field, $source, $args, $context, $info );
			case 'image':
				return blooikos_core_graphql_image_connection( $value, $source, $args, $context, $info );
			case 'repeater':
				return blooikos_core_graphql_rows( $value, $field );
			default:
				return blooikos_core_graphql_scalar( $value, $field );
		}
	}
	return $unresolved;
}

add_filter( 'graphql_pre_resolve_field', 'blooikos_core_graphql_resolve', 10, 7 );
