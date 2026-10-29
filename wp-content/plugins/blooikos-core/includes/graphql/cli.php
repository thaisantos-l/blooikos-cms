<?php
/**
 * Exportação e diagnóstico locais, sem escrita editorial.
 *
 * @package BlooikosCore
 */

// phpcs:disable WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Leitura de artefatos locais, nunca URLs.
// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Diagnósticos CLI de chaves/arquivos/schema, sem renderização HTML.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Diretório portátil dos artefatos do plugin.
 *
 * @return string
 */
function blooikos_core_graphql_contract_directory() {
	return dirname( __DIR__, 2 ) . '/contracts/graphql/v1';
}

/**
 * Serializa JSON determinístico, legível e com newline final.
 *
 * @param array $value Dados sem informações editoriais.
 * @return string
 */
function blooikos_core_graphql_json( $value ) {
	return wp_json_encode( $value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n";
}

/**
 * Confere o mapa contra ACF/schema e prepara o manifesto.
 *
 * @param \GraphQL\Type\Schema $schema Schema efetivo.
 * @param string               $sdl SDL canônico.
 * @param string               $operations Documento completo.
 * @return array
 * @throws RuntimeException Mapeamento incompatível.
 */
function blooikos_core_graphql_manifest( $schema, $sdl, $operations ) {
	$groups = blooikos_core_graphql_groups();
	$fields = blooikos_core_graphql_fields();
	foreach ( $groups as $key => $mapping ) {
		$group = acf_get_field_group( $key );
		$owner = $schema->getType( $mapping['owner'] );
		if ( ! $group || empty( $group['show_in_graphql'] ) || ! empty( $group['show_in_rest'] ) || $group['graphql_type_name'] !== $mapping['type'] || ! $owner || ! $owner->hasField( lcfirst( $mapping['type'] ) ) ) {
			throw new RuntimeException( 'Mapeamento de grupo incompatível: ' . $key );
		}
		$groups[ $key ]['field'] = lcfirst( $mapping['type'] );
	}
	foreach ( $fields as $key => $mapping ) {
		$field = acf_get_field( $key );
		$owner = $schema->getType( $mapping['owner'] );
		if ( ! $field || empty( $field['show_in_graphql'] ) || $field['graphql_field_name'] !== $mapping['name'] || ! empty( $field['graphql_non_null'] ) || ! $owner || ! $owner->hasField( $mapping['name'] ) ) {
			throw new RuntimeException( 'Mapeamento de campo incompatível: ' . $key );
		}
		$type = $owner->getField( $mapping['name'] )->getType();
		if ( $type instanceof \GraphQL\Type\Definition\NonNull ) {
			throw new RuntimeException( 'Campo deveria ser nullable: ' . $key );
		}
		$fields[ $key ]['type']     = (string) $type;
		$fields[ $key ]['nullable'] = true;
		$fields[ $key ]['acfType']  = $field['type'];
		foreach ( array( 'maxlength', 'min', 'max', 'choices', 'default_value', 'mime_types' ) as $property ) {
			if ( isset( $field[ $property ] ) && '' !== $field[ $property ] ) {
				$fields[ $key ][ $property ] = $field[ $property ];
			}
		}
	}
	return array(
		'contractVersion' => BLOOIKOS_CORE_CONTRACT_VERSION,
		// Proveniência do contrato editorial 1.0.0; o módulo operacional 0.4 não o altera.
		'pluginVersion'   => '0.3.0',
		'dependencies'    => array(
			'wordpress'    => get_bloginfo( 'version' ),
			'php'          => PHP_VERSION,
			'wpgraphql'    => WPGRAPHQL_VERSION,
			'acfPro'       => ACF_VERSION,
			'wpgraphqlAcf' => get_plugin_data( WP_PLUGIN_DIR . '/wpgraphql-acf/wpgraphql-acf.php', false, false )['Version'],
		),
		'sha256'          => array(
			'schema.graphql'     => hash( 'sha256', $sdl ),
			'operations.graphql' => hash( 'sha256', $operations ),
		),
		'document'        => 'Single document with shared fragments. Send the complete file and select operationName.',
		'profiles'        => array(
			'poc'  => 'BlooikosHeaderFooter',
			'home' => 'BlooikosHome',
		),
		'groups'          => $groups,
		'fields'          => $fields,
		'responseRules'   => blooikos_core_graphql_response_rules(),
	);
}

/**
 * Valida AST e tipos e produz os três artefatos a partir do schema real.
 *
 * @param string $operations Documento das duas operações.
 * @return array<string,string>
 * @throws RuntimeException Documento incompatível.
 */
function blooikos_core_graphql_artifacts( $operations ) {
	return blooikos_core_graphql_anonymous(
		static function () use ( $operations ) {
			$schema   = \WPGraphQL::get_schema();
			$document = \GraphQL\Language\Parser::parse( $operations );
			$errors   = \GraphQL\Validator\DocumentValidator::validate( $schema, $document );
			if ( $errors ) {
				throw new RuntimeException( 'Operações incompatíveis com o schema: ' . $errors[0]->getMessage() );
			}
			$names = array();
			foreach ( $document->definitions as $definition ) {
				if ( $definition instanceof \GraphQL\Language\AST\OperationDefinitionNode ) {
					$names[] = $definition->name->value ?? '';
				}
			}
			sort( $names );
			if ( array( 'BlooikosHeaderFooter', 'BlooikosHome' ) !== $names ) {
				throw new RuntimeException( 'O documento deve conter as duas operações do contrato.' );
			}
			$sdl = \GraphQL\Utils\SchemaPrinter::doPrint(
				$schema,
				array(
					'sortTypes'       => true,
					'sortFields'      => true,
					'sortArguments'   => true,
					'sortInputFields' => true,
					'sortEnumValues'  => true,
				)
			);
			$sdl = rtrim( str_replace( "\r\n", "\n", $sdl ) ) . "\n";
			return array(
				'schema.graphql'     => $sdl,
				'operations.graphql' => $operations,
				'contract.json'      => blooikos_core_graphql_json( blooikos_core_graphql_manifest( $schema, $sdl, $operations ) ),
			);
		}
	);
}

/**
 * Verifica bytes e hashes sem regenerar arquivos no disco.
 *
 * @param string $directory Diretório independente dos três artefatos.
 * @return void
 * @throws RuntimeException Arquivo ausente ou divergente.
 */
function blooikos_core_graphql_check( $directory ) {
	$operations = file_get_contents( $directory . '/operations.graphql' );
	if ( false === $operations ) {
		throw new RuntimeException( 'Documento de operações ausente.' );
	}
	foreach ( blooikos_core_graphql_artifacts( $operations ) as $file => $expected ) {
		if ( ! is_file( $directory . '/' . $file ) || file_get_contents( $directory . '/' . $file ) !== $expected ) {
			throw new RuntimeException( 'Artefato divergente: ' . $file );
		}
	}
}

/**
 * Exporta em diretório informado explicitamente.
 *
 * @throws RuntimeException Falha local capturada e convertida em erro CLI.
 *
 * @param array $args Argumentos posicionais.
 * @param array $assoc Argumentos nomeados.
 * @return void
 */
function blooikos_core_graphql_export_command( $args, $assoc ) {
	try {
		$directory = $assoc['output-dir'];
		$artifacts = blooikos_core_graphql_artifacts( file_get_contents( blooikos_core_graphql_contract_directory() . '/operations.graphql' ) );
		if ( ! wp_mkdir_p( $directory ) ) {
			throw new RuntimeException( 'Não foi possível criar o diretório de saída.' );
		}
		foreach ( $artifacts as $file => $bytes ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Exportação local explicitamente solicitada por CLI.
			if ( strlen( $bytes ) !== file_put_contents( $directory . '/' . $file, $bytes ) ) {
				throw new RuntimeException( 'Não foi possível gravar o artefato: ' . $file );
			}
		}
		WP_CLI::success( 'Contrato exportado; nenhum conteúdo editorial foi alterado.' );
	} catch ( Throwable $error ) {
		WP_CLI::error( $error->getMessage() );
	}
}

/**
 * Confere contrato versionável contra o ambiente ativo.
 *
 * @return void
 */
function blooikos_core_graphql_check_command() {
	try {
		blooikos_core_graphql_check( blooikos_core_graphql_contract_directory() );
		WP_CLI::success( 'Contrato, operações, mapa e hashes consistentes.' );
	} catch ( Throwable $error ) {
		WP_CLI::error( $error->getMessage() );
	}
}

/**
 * Diagnostica conteúdo público, sem completar ou publicar valores.
 *
 * @param array $args Argumentos posicionais.
 * @param array $assoc Argumentos nomeados.
 * @return void
 */
function blooikos_core_graphql_validate_command( $args, $assoc ) {
	$profile = $assoc['profile'];
	if ( ! in_array( $profile, array( 'poc', 'home' ), true ) || 'json' !== $assoc['format'] ) {
		WP_CLI::error( 'Use --profile=poc|home --format=json.' );
	}
	try {
		$response = blooikos_core_graphql_anonymous(
			static function () use ( $profile ) {
				return graphql(
					array(
						'query'         => file_get_contents( blooikos_core_graphql_contract_directory() . '/operations.graphql' ),
						'operationName' => 'poc' === $profile ? 'BlooikosHeaderFooter' : 'BlooikosHome',
					)
				);
			}
		);
		$result   = blooikos_core_graphql_validate_response( $response, $profile );
	} catch ( Throwable $error ) {
		$result = array(
			'valid'  => false,
			'issues' => array(),
		);
		blooikos_core_graphql_issue( $result['issues'], 'GRAPHQL_ERROR', '$' );
	}
	WP_CLI::line(
		blooikos_core_graphql_json(
			array_merge(
				array(
					'profile'         => $profile,
					'contractVersion' => BLOOIKOS_CORE_CONTRACT_VERSION,
				),
				$result
			)
		)
	);
	WP_CLI::halt( $result['valid'] ? 0 : 1 );
}

WP_CLI::add_command( 'blooikos graphql export', 'blooikos_core_graphql_export_command', array( 'synopsis' => '--output-dir=<directory>' ) );
WP_CLI::add_command( 'blooikos graphql check', 'blooikos_core_graphql_check_command' );
WP_CLI::add_command( 'blooikos graphql validate', 'blooikos_core_graphql_validate_command', array( 'synopsis' => '--profile=<profile> --format=<format>' ) );
