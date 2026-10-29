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
 * Valida o envelope técnico; ausência editorial é válida em ambos os perfis.
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
	if ( ! array_key_exists( 'data', $response ) || ! is_array( $response['data'] ) ) {
		blooikos_core_graphql_issue( $issues, 'INVALID_CONTENT', '$.data' );
	}
	return array(
		'valid'  => ! $issues,
		'issues' => $issues,
	);
}
