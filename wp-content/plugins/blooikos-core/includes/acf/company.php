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
 * Registra Empresa — Dados editoriais.
 *
 * @return void
 */
function blooikos_core_register_company_fields() {
	acf_add_local_field_group(
		array(
			'key'               => 'group_blooikos_company',
			'graphql_type_name' => 'BlooikosCompanyFields',
			'graphql_types'     => array( 'BlooikosCompany' ),
			'title'             => __( 'Empresa — Dados editoriais', 'blooikos-core' ),
			'fields'            => array(
				array(
					'key'                => 'field_blooikos_company_short_name',
					'show_in_graphql'    => true,
					'graphql_field_name' => 'shortName',
					'graphql_non_null'   => false,
					'label'              => __( 'Nome curto', 'blooikos-core' ),
					'name'               => 'blooikos_company_short_name',
					'type'               => 'text',
					'required'           => 0,
					'maxlength'          => 60,
					'instructions'       => __( 'Até 60 caracteres. Pode ficar vazio durante a preparação do rascunho.', 'blooikos-core' ),
				),
				array(
					'key'                => 'field_blooikos_company_summary',
					'show_in_graphql'    => true,
					'graphql_field_name' => 'summary',
					'graphql_non_null'   => false,
					'label'              => __( 'Resumo do card', 'blooikos-core' ),
					'name'               => 'blooikos_company_summary',
					'type'               => 'textarea',
					'required'           => 0,
					'maxlength'          => 300,
					'instructions'       => __( 'Até 300 caracteres. Pode ficar vazio durante a preparação do rascunho.', 'blooikos-core' ),
					'new_lines'          => '',
					'rows'               => 4,
				),
				array(
					'key'                => 'field_blooikos_company_card_icon',
					'show_in_graphql'    => true,
					'graphql_field_name' => 'cardIcon',
					'graphql_non_null'   => false,
					'label'              => __( 'Ícone do card', 'blooikos-core' ),
					'name'               => 'blooikos_company_card_icon',
					'type'               => 'image',
					'required'           => 0,
					'return_format'      => 'id',
					'preview_size'       => 'medium',
					'library'            => 'all',
					'mime_types'         => 'jpg,jpeg,png,webp',
					'instructions'       => __( 'Imagem decorativa junto ao texto: JPEG, PNG ou WebP. Não apaga o alt da mídia.', 'blooikos-core' ),
				),
				array(
					'key'                => 'field_blooikos_company_logo',
					'show_in_graphql'    => true,
					'graphql_field_name' => 'logo',
					'graphql_non_null'   => false,
					'label'              => __( 'Logo', 'blooikos-core' ),
					'name'               => 'blooikos_company_logo',
					'type'               => 'image',
					'required'           => 0,
					'return_format'      => 'id',
					'preview_size'       => 'medium',
					'library'            => 'all',
					'mime_types'         => 'jpg,jpeg,png,webp',
					'instructions'       => __( 'JPEG, PNG ou WebP. Imagem informativa exige texto alternativo na Biblioteca de mídia.', 'blooikos-core' ),
				),
				array(
					'key'                => 'field_blooikos_company_eyebrow',
					'show_in_graphql'    => true,
					'graphql_field_name' => 'eyebrow',
					'graphql_non_null'   => false,
					'label'              => __( 'Segmento de atuação', 'blooikos-core' ),
					'name'               => 'blooikos_company_eyebrow',
					'type'               => 'text',
					'required'           => 0,
					'maxlength'          => 100,
					'instructions'       => __( 'Até 100 caracteres. Pode ficar vazio durante a preparação do rascunho.', 'blooikos-core' ),
				),
				array(
					'key'                => 'field_blooikos_company_heading',
					'show_in_graphql'    => true,
					'graphql_field_name' => 'heading',
					'graphql_non_null'   => false,
					'label'              => __( 'Chamada da seção', 'blooikos-core' ),
					'name'               => 'blooikos_company_heading',
					'type'               => 'text',
					'required'           => 0,
					'maxlength'          => 180,
					'instructions'       => __( 'Até 180 caracteres. Pode ficar vazio durante a preparação do rascunho.', 'blooikos-core' ),
				),
				array(
					'key'                => 'field_blooikos_company_body',
					'show_in_graphql'    => true,
					'graphql_field_name' => 'body',
					'graphql_non_null'   => false,
					'label'              => __( 'Descrição da empresa', 'blooikos-core' ),
					'name'               => 'blooikos_company_body',
					'type'               => 'wysiwyg',
					'required'           => 0,
					'maxlength'          => 3000,
					'instructions'       => __( 'Até 3000 caracteres de texto. Use parágrafos, ênfase, listas e links; sem embeds ou shortcodes.', 'blooikos-core' ),
					'toolbar'            => 'basic',
					'media_upload'       => 0,
					'tabs'               => 'visual',
				),
				array(
					'key'                => 'field_blooikos_company_image',
					'show_in_graphql'    => true,
					'graphql_field_name' => 'image',
					'graphql_non_null'   => false,
					'label'              => __( 'Imagem da empresa', 'blooikos-core' ),
					'name'               => 'blooikos_company_image',
					'type'               => 'image',
					'required'           => 0,
					'return_format'      => 'id',
					'preview_size'       => 'medium',
					'library'            => 'all',
					'mime_types'         => 'jpg,jpeg,png,webp',
					'instructions'       => __( 'JPEG, PNG ou WebP. Imagem informativa exige texto alternativo na Biblioteca de mídia.', 'blooikos-core' ),
				),
				array(
					'key'                => 'field_blooikos_company_cta',
					'show_in_graphql'    => true,
					'graphql_field_name' => 'cta',
					'graphql_non_null'   => false,
					'label'              => __( 'Link da empresa', 'blooikos-core' ),
					'name'               => 'blooikos_company_cta',
					'type'               => 'link',
					'required'           => 0,
					'return_format'      => 'array',
					'instructions'       => __( 'Informe título descritivo e HTTPS, caminho / do site ou #âncora. Não use URLs de protótipo.', 'blooikos-core' ),
				),
			),
			'location'          => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'blooikos_company',
					),
				),
			),
			'show_in_graphql'   => true,
			'show_in_rest'      => false,
			'active'            => true,
		)
	);
}

add_action( 'acf/init', 'blooikos_core_register_company_fields' );
