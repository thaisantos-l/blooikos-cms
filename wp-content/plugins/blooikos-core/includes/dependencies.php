<?php
/**
 * Diagnóstico das dependências carregadas, sem consultas ou persistência.
 *
 * @package BlooikosCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retorna dependências ausentes ou abaixo do baseline adotado.
 *
 * Chamar após plugins_loaded, na prioridade 100 ou posterior. O resultado é
 * mantido apenas em memória nesta requisição; não verifica chaves de licença.
 *
 * @return array<string, array{name: string, minimum: string, reason: string}> Diagnósticos por dependência.
 */
function blooikos_core_get_dependency_issues() {
	static $issues = null;

	if ( null !== $issues ) {
		return $issues;
	}

	$dependencies = array(
		'wpgraphql'     => array(
			'name'      => 'WPGraphQL',
			'minimum'   => '2.25.0',
			'available' => class_exists( 'WPGraphQL', false ) && function_exists( 'graphql' ),
			'version'   => defined( 'WPGRAPHQL_VERSION' ) ? WPGRAPHQL_VERSION : '',
		),
		'acf-pro'       => array(
			'name'      => 'ACF PRO',
			'minimum'   => '6.8.10',
			'available' => defined( 'ACF_PRO' ) && ACF_PRO
				&& function_exists( 'acf_add_local_field_group' )
				&& function_exists( 'acf_add_options_page' ),
			'version'   => defined( 'ACF_VERSION' ) ? ACF_VERSION : '',
		),
		'wpgraphql-acf' => array(
			'name'      => 'WPGraphQL for ACF',
			'minimum'   => '3.0.0',
			'available' => class_exists( 'WPGraphQLAcf', false ) && function_exists( 'graphql_acf_init' ),
			'version'   => defined( 'WPGRAPHQL_FOR_ACF_VERSION' ) ? WPGRAPHQL_FOR_ACF_VERSION : '',
		),
	);

	$issues = array();

	foreach ( $dependencies as $id => $dependency ) {
		$reason = '';

		if ( ! $dependency['available'] ) {
			$reason = 'missing';
		} elseif ( ! is_string( $dependency['version'] ) || '' === $dependency['version']
			|| version_compare( $dependency['version'], $dependency['minimum'], '<' ) ) {
			$reason = 'incompatible';
		}

		if ( $reason ) {
			$issues[ $id ] = array(
				'name'    => $dependency['name'],
				'minimum' => $dependency['minimum'],
				'reason'  => $reason,
			);
		}
	}

	return $issues;
}
