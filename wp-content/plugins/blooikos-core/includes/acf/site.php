<?php
/**
 * Campos editoriais registrados em PHP, fonte única do modelo.
 *
 * @package BlooikosCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registra Identidade.
 *
 * @return void
 */
function blooikos_core_register_site_identity_fields() {
	acf_add_local_field_group(
		array(
			'key'               => 'group_blooikos_site_identity',
			'graphql_type_name' => 'BlooikosSiteIdentity',
			'graphql_types'     => array( 'BlooikosSettings' ),
			'title'             => __( 'Identidade', 'blooikos-core' ),
			'fields'            => array(
				array(
					'key'                => 'field_blooikos_site_brand_name',
					'show_in_graphql'    => true,
					'graphql_field_name' => 'brandName',
					'graphql_non_null'   => false,
					'label'              => __( 'Nome da marca', 'blooikos-core' ),
					'name'               => 'blooikos_site_brand_name',
					'type'               => 'text',
					'required'           => 0,
					'maxlength'          => 100,
					'instructions'       => __( 'Até 100 caracteres. Pode ficar vazio durante a preparação do rascunho.', 'blooikos-core' ),
				),
				array(
					'key'                => 'field_blooikos_site_logo',
					'show_in_graphql'    => true,
					'graphql_field_name' => 'logo',
					'graphql_non_null'   => false,
					'label'              => __( 'Logo', 'blooikos-core' ),
					'name'               => 'blooikos_site_logo',
					'type'               => 'image',
					'required'           => 0,
					'return_format'      => 'id',
					'preview_size'       => 'medium',
					'library'            => 'all',
					'mime_types'         => 'jpg,jpeg,png,webp',
					'instructions'       => __( 'JPEG, PNG ou WebP. Imagem informativa exige texto alternativo na Biblioteca de mídia.', 'blooikos-core' ),
				),
				array(
					'key'                => 'field_blooikos_site_logo_inverse',
					'show_in_graphql'    => true,
					'graphql_field_name' => 'logoInverse',
					'graphql_non_null'   => false,
					'label'              => __( 'Logo para fundo escuro', 'blooikos-core' ),
					'name'               => 'blooikos_site_logo_inverse',
					'type'               => 'image',
					'required'           => 0,
					'return_format'      => 'id',
					'preview_size'       => 'medium',
					'library'            => 'all',
					'mime_types'         => 'jpg,jpeg,png,webp',
					'instructions'       => __( 'JPEG, PNG ou WebP. Imagem informativa exige texto alternativo na Biblioteca de mídia.', 'blooikos-core' ),
				),
			),
			'location'          => array(
				array(
					array(
						'param'    => 'options_page',
						'operator' => '==',
						'value'    => 'blooikos-settings',
					),
				),
			),
			'show_in_graphql'   => true,
			'show_in_rest'      => false,
			'active'            => true,
			'menu_order'        => 0,
		)
	);
}

add_action( 'acf/init', 'blooikos_core_register_site_identity_fields' );

/**
 * Registra Contato compartilhado.
 *
 * @return void
 */
function blooikos_core_register_site_contact_fields() {
	acf_add_local_field_group(
		array(
			'key'               => 'group_blooikos_site_contact',
			'graphql_type_name' => 'BlooikosSiteContact',
			'graphql_types'     => array( 'BlooikosSettings' ),
			'title'             => __( 'Contato compartilhado', 'blooikos-core' ),
			'fields'            => array(
				array(
					'key'                     => 'field_blooikos_site_contact_company',
					'show_in_graphql'         => true,
					'graphql_field_name'      => 'contactCompany',
					'graphql_non_null'        => false,
					'graphql_connection_type' => 'one_to_one',
					'label'                   => __( 'Empresa de contato', 'blooikos-core' ),
					'name'                    => 'blooikos_site_contact_company',
					'type'                    => 'post_object',
					'required'                => 0,
					'post_type'               => array(
						'blooikos_company',
					),
					'post_status'             => array(
						'publish',
						'draft',
					),
					'return_format'           => 'id',
					'bidirectional'           => 0,
					'instructions'            => __( 'Selecione empresas publicadas ou em rascunho que você pode editar. A ordem da seleção será preservada.', 'blooikos-core' ),
					'allow_null'              => 1,
					'multiple'                => 0,
					'ui'                      => 1,
				),
				array(
					'key'                => 'field_blooikos_site_phone',
					'show_in_graphql'    => true,
					'graphql_field_name' => 'phone',
					'graphql_non_null'   => false,
					'label'              => __( 'Telefone', 'blooikos-core' ),
					'name'               => 'blooikos_site_phone',
					'type'               => 'text',
					'required'           => 0,
					'maxlength'          => 40,
					'instructions'       => __( 'De 8 a 15 dígitos; use apenas números, espaços, parênteses, hífens e +.', 'blooikos-core' ),
				),
				array(
					'key'                => 'field_blooikos_site_email',
					'show_in_graphql'    => true,
					'graphql_field_name' => 'email',
					'graphql_non_null'   => false,
					'label'              => __( 'Email', 'blooikos-core' ),
					'name'               => 'blooikos_site_email',
					'type'               => 'email',
					'required'           => 0,
					'maxlength'          => 254,
					'instructions'       => __( 'Até 254 caracteres. Pode ficar vazio durante a preparação do rascunho.', 'blooikos-core' ),
				),
				array(
					'key'                => 'field_blooikos_site_instagram_url',
					'show_in_graphql'    => true,
					'graphql_field_name' => 'instagramUrl',
					'graphql_non_null'   => false,
					'label'              => __( 'URL do Instagram', 'blooikos-core' ),
					'name'               => 'blooikos_site_instagram_url',
					'type'               => 'url',
					'required'           => 0,
					'maxlength'          => 2048,
					'instructions'       => __( 'URL HTTPS definitiva, até 2048 caracteres.', 'blooikos-core' ),
				),
				array(
					'key'                => 'field_blooikos_site_address',
					'show_in_graphql'    => true,
					'graphql_field_name' => 'address',
					'graphql_non_null'   => false,
					'label'              => __( 'Endereço', 'blooikos-core' ),
					'name'               => 'blooikos_site_address',
					'type'               => 'textarea',
					'required'           => 0,
					'maxlength'          => 500,
					'instructions'       => __( 'Até 500 caracteres. Pode ficar vazio durante a preparação do rascunho.', 'blooikos-core' ),
					'new_lines'          => '',
					'rows'               => 4,
				),
			),
			'location'          => array(
				array(
					array(
						'param'    => 'options_page',
						'operator' => '==',
						'value'    => 'blooikos-settings',
					),
				),
			),
			'show_in_graphql'   => true,
			'show_in_rest'      => false,
			'active'            => true,
			'menu_order'        => 1,
		)
	);
}

add_action( 'acf/init', 'blooikos_core_register_site_contact_fields' );

/**
 * Registra Footer.
 *
 * @return void
 */
function blooikos_core_register_site_footer_fields() {
	acf_add_local_field_group(
		array(
			'key'               => 'group_blooikos_site_footer',
			'graphql_type_name' => 'BlooikosSiteFooter',
			'graphql_types'     => array( 'BlooikosSettings' ),
			'title'             => __( 'Footer', 'blooikos-core' ),
			'fields'            => array(
				array(
					'key'                => 'field_blooikos_site_footer_description',
					'show_in_graphql'    => true,
					'graphql_field_name' => 'footerDescription',
					'graphql_non_null'   => false,
					'label'              => __( 'Descrição do footer', 'blooikos-core' ),
					'name'               => 'blooikos_site_footer_description',
					'type'               => 'textarea',
					'required'           => 0,
					'maxlength'          => 400,
					'instructions'       => __( 'Até 400 caracteres. Pode ficar vazio durante a preparação do rascunho.', 'blooikos-core' ),
					'new_lines'          => '',
					'rows'               => 4,
				),
				array(
					'key'                     => 'field_blooikos_site_footer_companies',
					'show_in_graphql'         => true,
					'graphql_field_name'      => 'footerCompanies',
					'graphql_non_null'        => false,
					'graphql_connection_type' => 'one_to_many',
					'label'                   => __( 'Empresas do footer', 'blooikos-core' ),
					'name'                    => 'blooikos_site_footer_companies',
					'type'                    => 'relationship',
					'required'                => 0,
					'post_type'               => array(
						'blooikos_company',
					),
					'post_status'             => array(
						'publish',
						'draft',
					),
					'return_format'           => 'id',
					'bidirectional'           => 0,
					'instructions'            => __( 'Selecione empresas publicadas ou em rascunho que você pode editar. A ordem da seleção será preservada.', 'blooikos-core' ),
					'min'                     => 0,
					'filters'                 => array(
						'search',
					),
					'max'                     => 6,
				),
				array(
					'key'                => 'field_blooikos_site_copyright_holder',
					'show_in_graphql'    => true,
					'graphql_field_name' => 'copyrightHolder',
					'graphql_non_null'   => false,
					'label'              => __( 'Titular do copyright', 'blooikos-core' ),
					'name'               => 'blooikos_site_copyright_holder',
					'type'               => 'text',
					'required'           => 0,
					'maxlength'          => 160,
					'instructions'       => __( 'Até 160 caracteres. Pode ficar vazio durante a preparação do rascunho.', 'blooikos-core' ),
				),
			),
			'location'          => array(
				array(
					array(
						'param'    => 'options_page',
						'operator' => '==',
						'value'    => 'blooikos-settings',
					),
				),
			),
			'show_in_graphql'   => true,
			'show_in_rest'      => false,
			'active'            => true,
			'menu_order'        => 2,
		)
	);
}

add_action( 'acf/init', 'blooikos_core_register_site_footer_fields' );
