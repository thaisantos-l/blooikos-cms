<?php
/**
 * Validação pura do envelope público nos perfis poc e home.
 *
 * @package BlooikosCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Regras compartilhadas com o manifesto portátil; não leem conteúdo do banco.
 *
 * @return array
 */
function blooikos_core_graphql_response_rules() {
	return array(
		'Identity'              => array(
			'brandName'   => array(
				'kind'     => 'text',
				'max'      => 100,
				'required' => true,
			),
			'logo'        => array(
				'kind'        => 'image',
				'required'    => true,
				'informative' => true,
			),
			'logoInverse' => array(
				'kind'        => 'image',
				'required'    => false,
				'informative' => true,
			),
		),
		'SiteContact'           => array(
			'contactCompany' => array(
				'kind'     => 'company_one',
				'required' => false,
			),
			'phone'          => array(
				'kind'     => 'phone',
				'max'      => 40,
				'required' => false,
			),
			'email'          => array(
				'kind'     => 'email',
				'max'      => 254,
				'required' => false,
			),
			'instagramUrl'   => array(
				'kind'     => 'https',
				'max'      => 2048,
				'required' => false,
			),
			'address'        => array(
				'kind'     => 'text',
				'max'      => 500,
				'required' => false,
			),
		),
		'Footer'                => array(
			'footerDescription' => array(
				'kind'     => 'text',
				'max'      => 400,
				'required' => true,
			),
			'copyrightHolder'   => array(
				'kind'     => 'text',
				'max'      => 160,
				'required' => false,
			),
			'footerCompanies'   => array(
				'kind'     => 'connection',
				'type'     => 'CompanyIdentity',
				'min'      => 1,
				'max'      => 6,
				'required' => true,
			),
		),
		'Settings'              => array(
			'blooikosSiteIdentity' => array(
				'kind'     => 'object',
				'type'     => 'Identity',
				'required' => true,
			),
			'blooikosSiteContact'  => array(
				'kind'     => 'object',
				'type'     => 'SiteContact',
				'required' => false,
			),
			'blooikosSiteFooter'   => array(
				'kind'     => 'object',
				'type'     => 'Footer',
				'required' => true,
			),
		),
		'CompanyIdentity'       => array(
			'id'                    => array(
				'kind'     => 'text',
				'max'      => 0,
				'required' => true,
			),
			'title'                 => array(
				'kind'     => 'text',
				'max'      => 0,
				'required' => true,
			),
			'slug'                  => array(
				'kind'     => 'text',
				'max'      => 0,
				'required' => false,
			),
			'blooikosCompanyFields' => array(
				'kind'     => 'object',
				'type'     => 'CompanyIdentityFields',
				'required' => true,
			),
		),
		'CompanyIdentityFields' => array(
			'shortName' => array(
				'kind'     => 'text',
				'max'      => 60,
				'required' => false,
			),
			'cta'       => array(
				'kind'     => 'link',
				'required' => false,
			),
		),
		'CompanyCardFields'     => array(
			'shortName' => array(
				'kind'     => 'text',
				'max'      => 60,
				'required' => false,
			),
			'cta'       => array(
				'kind'     => 'link',
				'required' => false,
			),
			'summary'   => array(
				'kind'     => 'text',
				'max'      => 300,
				'required' => true,
			),
			'cardIcon'  => array(
				'kind'        => 'image',
				'required'    => false,
				'informative' => false,
			),
		),
		'CompanySectionFields'  => array(
			'shortName' => array(
				'kind'     => 'text',
				'max'      => 60,
				'required' => false,
			),
			'cta'       => array(
				'kind'     => 'link',
				'required' => false,
			),
			'eyebrow'   => array(
				'kind'     => 'text',
				'max'      => 100,
				'required' => false,
			),
			'heading'   => array(
				'kind'     => 'text',
				'max'      => 180,
				'required' => true,
			),
			'body'      => array(
				'kind'     => 'html',
				'max'      => 3000,
				'required' => true,
			),
			'logo'      => array(
				'kind'        => 'image',
				'required'    => true,
				'informative' => true,
			),
			'image'     => array(
				'kind'        => 'image',
				'required'    => true,
				'informative' => true,
			),
		),
		'Hero'                  => array(
			'eyebrow'        => array(
				'kind'     => 'text',
				'max'      => 100,
				'required' => false,
			),
			'titleBefore'    => array(
				'kind'     => 'text',
				'max'      => 100,
				'required' => true,
			),
			'titleHighlight' => array(
				'kind'     => 'text',
				'max'      => 100,
				'required' => false,
			),
			'titleAfter'     => array(
				'kind'     => 'text',
				'max'      => 140,
				'required' => false,
			),
			'description'    => array(
				'kind'     => 'text',
				'max'      => 400,
				'required' => true,
			),
			'background'     => array(
				'kind'        => 'image',
				'required'    => true,
				'informative' => false,
			),
			'primaryCta'     => array(
				'kind'     => 'link',
				'required' => true,
			),
			'secondaryCta'   => array(
				'kind'     => 'link',
				'required' => false,
			),
		),
		'About'                 => array(
			'eyebrow'   => array(
				'kind'     => 'text',
				'max'      => 80,
				'required' => false,
			),
			'title'     => array(
				'kind'     => 'text',
				'max'      => 160,
				'required' => true,
			),
			'body'      => array(
				'kind'     => 'html',
				'max'      => 3000,
				'required' => true,
			),
			'cta'       => array(
				'kind'     => 'link',
				'required' => false,
			),
			'variant'   => array(
				'kind'     => 'variant',
				'required' => true,
			),
			'companies' => array(
				'kind'     => 'connection',
				'type'     => 'CompanyCard',
				'min'      => 1,
				'max'      => 6,
				'required' => true,
			),
		),
		'Institutional'         => array(
			'decorative' => array(
				'kind'     => 'boolean',
				'required' => true,
			),
			'image'      => array(
				'kind'        => 'image',
				'required'    => true,
				'informative' => true,
			),
		),
		'Indicator'             => array(
			'value'       => array(
				'kind'     => 'text',
				'max'      => 20,
				'required' => true,
			),
			'label'       => array(
				'kind'     => 'text',
				'max'      => 100,
				'required' => true,
			),
			'description' => array(
				'kind'     => 'text',
				'max'      => 180,
				'required' => false,
			),
		),
		'Indicators'            => array(
			'eyebrow' => array(
				'kind'     => 'text',
				'max'      => 80,
				'required' => false,
			),
			'title'   => array(
				'kind'     => 'text',
				'max'      => 160,
				'required' => true,
			),
			'items'   => array(
				'kind'     => 'list',
				'type'     => 'Indicator',
				'min'      => 1,
				'max'      => 4,
				'required' => true,
			),
		),
		'Service'               => array(
			'title'       => array(
				'kind'     => 'text',
				'max'      => 100,
				'required' => true,
			),
			'description' => array(
				'kind'     => 'text',
				'max'      => 500,
				'required' => true,
			),
			'icon'        => array(
				'kind'        => 'image',
				'required'    => false,
				'informative' => false,
			),
		),
		'Services'              => array(
			'eyebrow'     => array(
				'kind'     => 'text',
				'max'      => 80,
				'required' => false,
			),
			'title'       => array(
				'kind'     => 'text',
				'max'      => 160,
				'required' => true,
			),
			'description' => array(
				'kind'     => 'text',
				'max'      => 600,
				'required' => false,
			),
			'variant'     => array(
				'kind'     => 'variant',
				'required' => true,
			),
			'items'       => array(
				'kind'     => 'list',
				'type'     => 'Service',
				'min'      => 1,
				'max'      => 12,
				'required' => true,
			),
		),
		'Companies'             => array(
			'featured' => array(
				'kind'     => 'connection',
				'type'     => 'CompanySection',
				'min'      => 1,
				'max'      => 3,
				'required' => true,
			),
		),
		'Contact'               => array(
			'eyebrow' => array(
				'kind'     => 'text',
				'max'      => 80,
				'required' => false,
			),
			'title'   => array(
				'kind'     => 'text',
				'max'      => 160,
				'required' => true,
			),
			'variant' => array(
				'kind'     => 'variant',
				'required' => true,
			),
		),
		'Home'                  => array(
			'__typename'                => array(
				'kind'     => 'literal',
				'value'    => 'Page',
				'required' => true,
			),
			'isFrontPage'               => array(
				'kind'     => 'literal',
				'value'    => true,
				'required' => true,
			),
			'blooikosHomeHero'          => array(
				'kind'     => 'object',
				'type'     => 'Hero',
				'required' => true,
			),
			'blooikosHomeAbout'         => array(
				'kind'     => 'object',
				'type'     => 'About',
				'required' => true,
			),
			'blooikosHomeInstitutional' => array(
				'kind'     => 'object',
				'type'     => 'Institutional',
				'required' => true,
			),
			'blooikosHomeIndicators'    => array(
				'kind'     => 'object',
				'type'     => 'Indicators',
				'required' => true,
			),
			'blooikosHomeServices'      => array(
				'kind'     => 'object',
				'type'     => 'Services',
				'required' => true,
			),
			'blooikosHomeCompanies'     => array(
				'kind'     => 'object',
				'type'     => 'Companies',
				'required' => true,
			),
			'blooikosHomeContact'       => array(
				'kind'     => 'object',
				'type'     => 'Contact',
				'required' => true,
			),
		),
		'MenuItem'              => array(
			'id'       => array(
				'kind'     => 'text',
				'max'      => 0,
				'required' => true,
			),
			'label'    => array(
				'kind'     => 'text',
				'max'      => 0,
				'required' => true,
			),
			'url'      => array(
				'kind'     => 'destination',
				'required' => true,
			),
			'target'   => array(
				'kind' => 'target',
			),
			'parentId' => array(
				'kind' => 'no_parent',
			),
			'order'    => array(
				'kind'     => 'order',
				'required' => true,
			),
		),
		'CompanyCard'           => array(
			'id'                    => array(
				'kind'     => 'text',
				'max'      => 0,
				'required' => true,
			),
			'title'                 => array(
				'kind'     => 'text',
				'max'      => 0,
				'required' => true,
			),
			'slug'                  => array(
				'kind'     => 'text',
				'max'      => 0,
				'required' => false,
			),
			'blooikosCompanyFields' => array(
				'kind'     => 'object',
				'type'     => 'CompanyCardFields',
				'required' => true,
			),
		),
		'CompanySection'        => array(
			'id'                    => array(
				'kind'     => 'text',
				'max'      => 0,
				'required' => true,
			),
			'title'                 => array(
				'kind'     => 'text',
				'max'      => 0,
				'required' => true,
			),
			'slug'                  => array(
				'kind'     => 'text',
				'max'      => 0,
				'required' => false,
			),
			'blooikosCompanyFields' => array(
				'kind'     => 'object',
				'type'     => 'CompanySectionFields',
				'required' => true,
			),
		),
	);
}

/**
 * Acrescenta diagnóstico seguro, sem copiar valores do payload.
 *
 * @param array  $issues Lista de problemas.
 * @param string $code Código estável.
 * @param string $path Caminho público.
 * @return void
 */
function blooikos_core_graphql_issue( &$issues, $code, $path ) {
	$messages = array(
		'GRAPHQL_ERROR'            => 'A consulta GraphQL retornou erro.',
		'MISSING_REQUIRED_CONTENT' => 'Conteúdo essencial ausente.',
		'INVALID_CONTENT'          => 'O valor não atende ao contrato.',
		'INCOMPLETE_COLLECTION'    => 'A coleção está incompleta ou excede o limite.',
	);
	$issues[] = array(
		'code'    => $code,
		'path'    => $path,
		'message' => $messages[ $code ],
	);
}

/**
 * Verifica objeto selecionado pela operação, incluindo seus opcionais.
 *
 * @param mixed  $value Objeto de resposta.
 * @param string $type Nome no mapa de regras.
 * @param string $path Caminho público.
 * @param array  $issues Diagnósticos acumulados.
 * @return void
 */
function blooikos_core_graphql_validate_object( $value, $type, $path, &$issues ) {
	if ( ! is_array( $value ) ) {
		blooikos_core_graphql_issue( $issues, 'INVALID_CONTENT', $path );
		return;
	}
	$rules = blooikos_core_graphql_response_rules()[ $type ];
	foreach ( $rules as $name => $rule ) {
		if ( 'Institutional' === $type && 'image' === $name ) {
			$rule['informative'] = true !== ( $value['decorative'] ?? false );
		}
		blooikos_core_graphql_validate_value( $value[ $name ] ?? null, $rule, $path . '.' . $name, $issues );
	}
}

/**
 * Confere imagens fornecidas e seus metadados, sem buscar a URL.
 *
 * @param mixed  $value Conexão de imagem.
 * @param array  $rule Regra de obrigatoriedade/alt.
 * @param string $path Caminho público.
 * @param array  $issues Diagnósticos.
 * @return void
 */
function blooikos_core_graphql_validate_image( $value, $rule, $path, &$issues ) {
	if ( ! is_array( $value ) || ! array_key_exists( 'node', $value ) ) {
		blooikos_core_graphql_issue( $issues, 'INVALID_CONTENT', $path );
		return;
	}
	$node = $value['node'];
	if ( null === $node ) {
		if ( $rule['required'] ) {
			blooikos_core_graphql_issue( $issues, 'MISSING_REQUIRED_CONTENT', $path . '.node' );
		}
		return;
	}
	$url   = $node['sourceUrl'] ?? null;
	$parts = is_string( $url ) ? wp_parse_url( $url ) : false;
	$valid = is_array( $node ) && is_string( $url ) && filter_var( $url, FILTER_VALIDATE_URL ) && in_array( $parts['scheme'] ?? '', array( 'http', 'https' ), true ) && in_array( $node['mimeType'] ?? '', array( 'image/jpeg', 'image/png', 'image/webp' ), true );
	$valid = $valid && is_int( $node['mediaDetails']['width'] ?? null ) && $node['mediaDetails']['width'] > 0 && is_int( $node['mediaDetails']['height'] ?? null ) && $node['mediaDetails']['height'] > 0;
	if ( $rule['informative'] ) {
		$valid = $valid && is_string( $node['altText'] ?? null ) && '' !== trim( wp_strip_all_tags( $node['altText'] ) );
	}
	if ( ! $valid ) {
		blooikos_core_graphql_issue( $issues, 'INVALID_CONTENT', $path . '.node' );
	}
}

/**
 * Confere coleções e paginação; não usa truncamento como fallback.
 *
 * @param mixed  $value Coleção recebida.
 * @param array  $rule Regra do contexto.
 * @param string $path Caminho.
 * @param array  $issues Diagnósticos.
 * @return void
 */
function blooikos_core_graphql_validate_collection( $value, $rule, $path, &$issues ) {
	$connection = 'connection' === $rule['kind'];
	$nodes      = $connection && is_array( $value ) ? ( $value['nodes'] ?? null ) : $value;
	if ( ! is_array( $nodes ) || ! array_is_list( $nodes ) ) {
		blooikos_core_graphql_issue( $issues, 'INVALID_CONTENT', $path );
		return;
	}
	if ( count( $nodes ) < $rule['min'] ) {
		blooikos_core_graphql_issue( $issues, 'MISSING_REQUIRED_CONTENT', $path );
	}
	if ( count( $nodes ) > $rule['max'] || ( $connection && false !== ( $value['pageInfo']['hasNextPage'] ?? null ) ) ) {
		blooikos_core_graphql_issue( $issues, 'INCOMPLETE_COLLECTION', $path );
	}
	$ids   = array();
	$order = 0;
	foreach ( $nodes as $index => $node ) {
		$item_path = $path . ( $connection ? '.nodes' : '' ) . '[' . $index . ']';
		blooikos_core_graphql_validate_object( $node, $rule['type'], $item_path, $issues );
		if ( isset( $node['id'] ) ) {
			if ( ! is_string( $node['id'] ) || in_array( $node['id'], $ids, true ) ) {
				blooikos_core_graphql_issue( $issues, 'INVALID_CONTENT', $item_path . '.id' );
			}
			$ids[] = $node['id'];
		}
		if ( 'MenuItem' === $rule['type'] && is_array( $node ) ) {
			if ( ( $node['order'] ?? 0 ) <= $order ) {
				blooikos_core_graphql_issue( $issues, 'INVALID_CONTENT', $item_path . '.order' );
			}
			$order = $node['order'] ?? 0;
		}
	}
}

/**
 * Verifica um valor conforme seu tipo, sem efeitos de escrita.
 *
 * @param mixed  $value Valor da resposta.
 * @param array  $rule Regra pública.
 * @param string $path Caminho público.
 * @param array  $issues Diagnósticos.
 * @return void
 */
function blooikos_core_graphql_validate_value( $value, $rule, $path, &$issues ) {
	if ( null === $value || '' === $value ) {
		if ( ! empty( $rule['required'] ) ) {
			blooikos_core_graphql_issue( $issues, 'MISSING_REQUIRED_CONTENT', $path );
		}
		return;
	}
	$valid = true;
	switch ( $rule['kind'] ) {
		case 'object':
			blooikos_core_graphql_validate_object( $value, $rule['type'], $path, $issues );
			return;
		case 'connection':
		case 'list':
			blooikos_core_graphql_validate_collection( $value, $rule, $path, $issues );
			return;
		case 'image':
			blooikos_core_graphql_validate_image( $value, $rule, $path, $issues );
			return;
		case 'company_one':
			if ( ! is_array( $value ) || ! array_key_exists( 'node', $value ) ) {
				$valid = false;
			} elseif ( null !== $value['node'] ) {
				foreach ( array( 'id', 'title' ) as $key ) {
					blooikos_core_graphql_validate_value(
						$value['node'][ $key ] ?? null,
						array(
							'kind'     => 'text',
							'required' => true,
						),
						$path . '.node.' . $key,
						$issues
					);
				}
			}
			break;
		case 'link':
			$valid = true === blooikos_core_validate_link( $value ) && ! empty( $value['url'] );
			break;
		case 'variant':
			$valid = is_array( $value ) && array_is_list( $value ) && 1 === count( $value ) && in_array( $value[0], array( 'light', 'muted' ), true );
			break;
		case 'boolean':
			$valid = is_bool( $value );
			break;
		case 'literal':
			$valid = $value === $rule['value'];
			break;
		case 'destination':
			$valid = blooikos_core_valid_url( $value );
			break;
		case 'target':
			$valid = '_blank' === $value;
			break;
		case 'no_parent':
			$valid = false;
			break;
		case 'order':
			$valid = is_int( $value ) && $value > 0;
			break;
		default:
			$valid = is_string( $value ) && '' !== trim( wp_strip_all_tags( $value ) );
			$text  = is_string( $value ) ? $value : '';
			if ( 'html' === $rule['kind'] ) {
				$valid = $valid && blooikos_core_sanitize_rich_text( $text ) === $text;
				$text  = html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES, 'UTF-8' );
			}
			if ( ! empty( $rule['max'] ) ) {
				$valid = $valid && mb_strlen( $text ) <= $rule['max'];
			}
			if ( 'email' === $rule['kind'] ) {
				$valid = $valid && (bool) is_email( $text );
			}
			if ( 'https' === $rule['kind'] ) {
				$valid = $valid && blooikos_core_valid_url( $text, true );
			}
			if ( 'phone' === $rule['kind'] ) {
				$valid = $valid && preg_match( '/^[0-9 ()+\-]+$/D', $text ) && preg_match( '/^[0-9]{8,15}$/D', preg_replace( '/[ ()+\-]/', '', $text ) );
			}
	}
	if ( ! $valid ) {
		blooikos_core_graphql_issue( $issues, 'INVALID_CONTENT', $path );
	}
}

/**
 * Valida o envelope completo; o perfil poc não lê nem exige Home.
 *
 * @param mixed  $response Envelope GraphQL decodificado.
 * @param string $profile poc ou home.
 * @return array
 */
function blooikos_core_graphql_validate_response( $response, $profile ) {
	$issues = array();
	if ( ! in_array( $profile, array( 'poc', 'home' ), true ) || ! is_array( $response ) ) {
		blooikos_core_graphql_issue( $issues, 'INVALID_CONTENT', '$' );
		return array(
			'valid'  => false,
			'issues' => $issues,
		);
	}
	$errors = $response['errors'] ?? array();
	if ( ! is_array( $errors ) ) {
		blooikos_core_graphql_issue( $issues, 'INVALID_CONTENT', '$.errors' );
		$errors = array();
	}
	foreach ( $errors as $error ) {
		$path       = 'data';
		$error_path = is_array( $error ) && is_array( $error['path'] ?? null ) ? $error['path'] : array();
		foreach ( $error_path as $part ) {
			if ( is_int( $part ) || ( is_string( $part ) && preg_match( '/^[_A-Za-z][_0-9A-Za-z]*$/D', $part ) ) ) {
				$path .= is_int( $part ) ? '[' . $part . ']' : '.' . $part;
			}
		}
		blooikos_core_graphql_issue( $issues, 'GRAPHQL_ERROR', $path );
	}
	$data = $response['data'] ?? array();
	if ( ! is_array( $data ) ) {
		blooikos_core_graphql_issue( $issues, 'INVALID_CONTENT', '$.data' );
		$data = array();
	}
	blooikos_core_graphql_validate_value(
		$data['blooikosSettings'] ?? null,
		array(
			'kind'     => 'object',
			'type'     => 'Settings',
			'required' => true,
		),
		'data.blooikosSettings',
		$issues
	);
	foreach ( array( 'primaryMenu', 'footerMenu' ) as $menu ) {
		blooikos_core_graphql_validate_value(
			$data[ $menu ] ?? null,
			array(
				'kind'     => 'connection',
				'type'     => 'MenuItem',
				'min'      => 1,
				'max'      => 20,
				'required' => true,
			),
			'data.' . $menu,
			$issues
		);
	}
	if ( 'home' === $profile ) {
		blooikos_core_graphql_validate_value(
			$data['home'] ?? null,
			array(
				'kind'     => 'object',
				'type'     => 'Home',
				'required' => true,
			),
			'data.home',
			$issues
		);
	}
	return array(
		'valid'  => ! $issues,
		'issues' => $issues,
	);
}
