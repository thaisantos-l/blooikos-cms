<?php
/**
 * Mapa público fechado do contrato GraphQL 1.0.0.
 *
 * @package BlooikosCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BLOOIKOS_CORE_CONTRACT_VERSION', '1.0.0' );

/**
 * Retorna os onze grupos autorizados e seus tipos proprietários.
 *
 * @return array
 */
function blooikos_core_graphql_groups() {
	return array(
		'group_blooikos_home_hero'          => array(
			'type'  => 'BlooikosHomeHero',
			'owner' => 'Page',
		),
		'group_blooikos_home_about'         => array(
			'type'  => 'BlooikosHomeAbout',
			'owner' => 'Page',
		),
		'group_blooikos_home_institutional' => array(
			'type'  => 'BlooikosHomeInstitutional',
			'owner' => 'Page',
		),
		'group_blooikos_home_indicators'    => array(
			'type'  => 'BlooikosHomeIndicators',
			'owner' => 'Page',
		),
		'group_blooikos_home_services'      => array(
			'type'  => 'BlooikosHomeServices',
			'owner' => 'Page',
		),
		'group_blooikos_home_companies'     => array(
			'type'  => 'BlooikosHomeCompanies',
			'owner' => 'Page',
		),
		'group_blooikos_home_contact'       => array(
			'type'  => 'BlooikosHomeContact',
			'owner' => 'Page',
		),
		'group_blooikos_company'            => array(
			'type'  => 'BlooikosCompanyFields',
			'owner' => 'BlooikosCompany',
		),
		'group_blooikos_site_identity'      => array(
			'type'  => 'BlooikosSiteIdentity',
			'owner' => 'BlooikosSettings',
		),
		'group_blooikos_site_contact'       => array(
			'type'  => 'BlooikosSiteContact',
			'owner' => 'BlooikosSettings',
		),
		'group_blooikos_site_footer'        => array(
			'type'  => 'BlooikosSiteFooter',
			'owner' => 'BlooikosSettings',
		),
	);
}

/**
 * Retorna as 54 chaves permitidas, sem incluir futuros campos por prefixo.
 *
 * @return array
 */
function blooikos_core_graphql_fields() {
	return array(
		'field_blooikos_home_hero_eyebrow'                 => array(
			'name'  => 'eyebrow',
			'owner' => 'BlooikosHomeHero',
		),
		'field_blooikos_home_hero_title_before'            => array(
			'name'  => 'titleBefore',
			'owner' => 'BlooikosHomeHero',
		),
		'field_blooikos_home_hero_title_highlight'         => array(
			'name'  => 'titleHighlight',
			'owner' => 'BlooikosHomeHero',
		),
		'field_blooikos_home_hero_title_after'             => array(
			'name'  => 'titleAfter',
			'owner' => 'BlooikosHomeHero',
		),
		'field_blooikos_home_hero_description'             => array(
			'name'  => 'description',
			'owner' => 'BlooikosHomeHero',
		),
		'field_blooikos_home_hero_background'              => array(
			'name'  => 'background',
			'owner' => 'BlooikosHomeHero',
		),
		'field_blooikos_home_hero_primary_cta'             => array(
			'name'  => 'primaryCta',
			'owner' => 'BlooikosHomeHero',
		),
		'field_blooikos_home_hero_secondary_cta'           => array(
			'name'  => 'secondaryCta',
			'owner' => 'BlooikosHomeHero',
		),
		'field_blooikos_home_about_eyebrow'                => array(
			'name'  => 'eyebrow',
			'owner' => 'BlooikosHomeAbout',
		),
		'field_blooikos_home_about_title'                  => array(
			'name'  => 'title',
			'owner' => 'BlooikosHomeAbout',
		),
		'field_blooikos_home_about_body'                   => array(
			'name'  => 'body',
			'owner' => 'BlooikosHomeAbout',
		),
		'field_blooikos_home_about_cta'                    => array(
			'name'  => 'cta',
			'owner' => 'BlooikosHomeAbout',
		),
		'field_blooikos_home_about_companies'              => array(
			'name'  => 'companies',
			'owner' => 'BlooikosHomeAbout',
		),
		'field_blooikos_home_about_variant'                => array(
			'name'  => 'variant',
			'owner' => 'BlooikosHomeAbout',
		),
		'field_blooikos_home_institutional_image'          => array(
			'name'  => 'image',
			'owner' => 'BlooikosHomeInstitutional',
		),
		'field_blooikos_home_institutional_decorative'     => array(
			'name'  => 'decorative',
			'owner' => 'BlooikosHomeInstitutional',
		),
		'field_blooikos_home_indicators_eyebrow'           => array(
			'name'  => 'eyebrow',
			'owner' => 'BlooikosHomeIndicators',
		),
		'field_blooikos_home_indicators_title'             => array(
			'name'  => 'title',
			'owner' => 'BlooikosHomeIndicators',
		),
		'field_blooikos_home_indicators_items'             => array(
			'name'  => 'items',
			'owner' => 'BlooikosHomeIndicators',
		),
		'field_blooikos_home_services_eyebrow'             => array(
			'name'  => 'eyebrow',
			'owner' => 'BlooikosHomeServices',
		),
		'field_blooikos_home_services_title'               => array(
			'name'  => 'title',
			'owner' => 'BlooikosHomeServices',
		),
		'field_blooikos_home_services_description'         => array(
			'name'  => 'description',
			'owner' => 'BlooikosHomeServices',
		),
		'field_blooikos_home_services_variant'             => array(
			'name'  => 'variant',
			'owner' => 'BlooikosHomeServices',
		),
		'field_blooikos_home_services_items'               => array(
			'name'  => 'items',
			'owner' => 'BlooikosHomeServices',
		),
		'field_blooikos_home_companies_featured'           => array(
			'name'  => 'featured',
			'owner' => 'BlooikosHomeCompanies',
		),
		'field_blooikos_home_contact_eyebrow'              => array(
			'name'  => 'eyebrow',
			'owner' => 'BlooikosHomeContact',
		),
		'field_blooikos_home_contact_title'                => array(
			'name'  => 'title',
			'owner' => 'BlooikosHomeContact',
		),
		'field_blooikos_home_contact_variant'              => array(
			'name'  => 'variant',
			'owner' => 'BlooikosHomeContact',
		),
		'field_blooikos_company_short_name'                => array(
			'name'  => 'shortName',
			'owner' => 'BlooikosCompanyFields',
		),
		'field_blooikos_company_summary'                   => array(
			'name'  => 'summary',
			'owner' => 'BlooikosCompanyFields',
		),
		'field_blooikos_company_card_icon'                 => array(
			'name'  => 'cardIcon',
			'owner' => 'BlooikosCompanyFields',
		),
		'field_blooikos_company_logo'                      => array(
			'name'  => 'logo',
			'owner' => 'BlooikosCompanyFields',
		),
		'field_blooikos_company_eyebrow'                   => array(
			'name'  => 'eyebrow',
			'owner' => 'BlooikosCompanyFields',
		),
		'field_blooikos_company_heading'                   => array(
			'name'  => 'heading',
			'owner' => 'BlooikosCompanyFields',
		),
		'field_blooikos_company_body'                      => array(
			'name'  => 'body',
			'owner' => 'BlooikosCompanyFields',
		),
		'field_blooikos_company_image'                     => array(
			'name'  => 'image',
			'owner' => 'BlooikosCompanyFields',
		),
		'field_blooikos_company_cta'                       => array(
			'name'  => 'cta',
			'owner' => 'BlooikosCompanyFields',
		),
		'field_blooikos_site_brand_name'                   => array(
			'name'  => 'brandName',
			'owner' => 'BlooikosSiteIdentity',
		),
		'field_blooikos_site_logo'                         => array(
			'name'  => 'logo',
			'owner' => 'BlooikosSiteIdentity',
		),
		'field_blooikos_site_logo_inverse'                 => array(
			'name'  => 'logoInverse',
			'owner' => 'BlooikosSiteIdentity',
		),
		'field_blooikos_site_contact_company'              => array(
			'name'  => 'contactCompany',
			'owner' => 'BlooikosSiteContact',
		),
		'field_blooikos_site_phone'                        => array(
			'name'  => 'phone',
			'owner' => 'BlooikosSiteContact',
		),
		'field_blooikos_site_email'                        => array(
			'name'  => 'email',
			'owner' => 'BlooikosSiteContact',
		),
		'field_blooikos_site_instagram_url'                => array(
			'name'  => 'instagramUrl',
			'owner' => 'BlooikosSiteContact',
		),
		'field_blooikos_site_address'                      => array(
			'name'  => 'address',
			'owner' => 'BlooikosSiteContact',
		),
		'field_blooikos_site_footer_description'           => array(
			'name'  => 'footerDescription',
			'owner' => 'BlooikosSiteFooter',
		),
		'field_blooikos_site_footer_companies'             => array(
			'name'  => 'footerCompanies',
			'owner' => 'BlooikosSiteFooter',
		),
		'field_blooikos_site_copyright_holder'             => array(
			'name'  => 'copyrightHolder',
			'owner' => 'BlooikosSiteFooter',
		),
		'field_blooikos_home_indicators_items_value'       => array(
			'name'  => 'value',
			'owner' => 'BlooikosHomeIndicatorsItems',
		),
		'field_blooikos_home_indicators_items_label'       => array(
			'name'  => 'label',
			'owner' => 'BlooikosHomeIndicatorsItems',
		),
		'field_blooikos_home_indicators_items_description' => array(
			'name'  => 'description',
			'owner' => 'BlooikosHomeIndicatorsItems',
		),
		'field_blooikos_home_services_items_title'         => array(
			'name'  => 'title',
			'owner' => 'BlooikosHomeServicesItems',
		),
		'field_blooikos_home_services_items_description'   => array(
			'name'  => 'description',
			'owner' => 'BlooikosHomeServicesItems',
		),
		'field_blooikos_home_services_items_icon'          => array(
			'name'  => 'icon',
			'owner' => 'BlooikosHomeServicesItems',
		),
	);
}

/**
 * Impede exposição implícita de futuros campos próprios dentro dos grupos.
 *
 * @param array $field Campo carregado pelo ACF.
 * @return array
 */
function blooikos_core_graphql_field_allowlist( $field ) {
	$key = $field['key'] ?? '';
	if ( str_starts_with( $key, 'field_blooikos_' ) && ! isset( blooikos_core_graphql_fields()[ $key ] ) ) {
		$field['show_in_graphql'] = false;
	}
	return $field;
}

add_filter( 'acf/load_field', 'blooikos_core_graphql_field_allowlist', 20 );
