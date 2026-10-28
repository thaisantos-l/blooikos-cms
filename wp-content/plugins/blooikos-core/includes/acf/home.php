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
 * Registra Home — Hero.
 *
 * @return void
 */
function blooikos_core_register_home_hero_fields() {
	acf_add_local_field_group(
		array(
			'key'             => 'group_blooikos_home_hero',
			'title'           => __( 'Home — Hero', 'blooikos-core' ),
			'fields'          => array(
				array(
					'key'          => 'field_blooikos_home_hero_eyebrow',
					'label'        => __( 'Chamada superior', 'blooikos-core' ),
					'name'         => 'blooikos_home_hero_eyebrow',
					'type'         => 'text',
					'required'     => 0,
					'maxlength'    => 100,
					'instructions' => __( 'Até 100 caracteres. Pode ficar vazio durante a preparação do rascunho.', 'blooikos-core' ),
				),
				array(
					'key'          => 'field_blooikos_home_hero_title_before',
					'label'        => __( 'Título — início', 'blooikos-core' ),
					'name'         => 'blooikos_home_hero_title_before',
					'type'         => 'text',
					'required'     => 0,
					'maxlength'    => 100,
					'instructions' => __( 'Até 100 caracteres. Pode ficar vazio durante a preparação do rascunho.', 'blooikos-core' ),
				),
				array(
					'key'          => 'field_blooikos_home_hero_title_highlight',
					'label'        => __( 'Título — destaque', 'blooikos-core' ),
					'name'         => 'blooikos_home_hero_title_highlight',
					'type'         => 'text',
					'required'     => 0,
					'maxlength'    => 100,
					'instructions' => __( 'Até 100 caracteres. Pode ficar vazio durante a preparação do rascunho.', 'blooikos-core' ),
				),
				array(
					'key'          => 'field_blooikos_home_hero_title_after',
					'label'        => __( 'Título — continuação', 'blooikos-core' ),
					'name'         => 'blooikos_home_hero_title_after',
					'type'         => 'text',
					'required'     => 0,
					'maxlength'    => 140,
					'instructions' => __( 'Até 140 caracteres. Pode ficar vazio durante a preparação do rascunho.', 'blooikos-core' ),
				),
				array(
					'key'          => 'field_blooikos_home_hero_description',
					'label'        => __( 'Descrição', 'blooikos-core' ),
					'name'         => 'blooikos_home_hero_description',
					'type'         => 'textarea',
					'required'     => 0,
					'maxlength'    => 400,
					'instructions' => __( 'Até 400 caracteres. Pode ficar vazio durante a preparação do rascunho.', 'blooikos-core' ),
					'new_lines'    => '',
					'rows'         => 4,
				),
				array(
					'key'           => 'field_blooikos_home_hero_background',
					'label'         => __( 'Imagem de fundo', 'blooikos-core' ),
					'name'          => 'blooikos_home_hero_background',
					'type'          => 'image',
					'required'      => 0,
					'return_format' => 'id',
					'preview_size'  => 'medium',
					'library'       => 'all',
					'mime_types'    => 'jpg,jpeg,png,webp',
					'instructions'  => __( 'Fundo decorativo: JPEG, PNG ou WebP. O alt da mídia compartilhada será preservado.', 'blooikos-core' ),
				),
				array(
					'key'           => 'field_blooikos_home_hero_primary_cta',
					'label'         => __( 'Link principal', 'blooikos-core' ),
					'name'          => 'blooikos_home_hero_primary_cta',
					'type'          => 'link',
					'required'      => 0,
					'return_format' => 'array',
					'instructions'  => __( 'Informe título descritivo e HTTPS, caminho / do site ou #âncora. Não use URLs de protótipo.', 'blooikos-core' ),
				),
				array(
					'key'           => 'field_blooikos_home_hero_secondary_cta',
					'label'         => __( 'Link secundário', 'blooikos-core' ),
					'name'          => 'blooikos_home_hero_secondary_cta',
					'type'          => 'link',
					'required'      => 0,
					'return_format' => 'array',
					'instructions'  => __( 'Informe título descritivo e HTTPS, caminho / do site ou #âncora. Não use URLs de protótipo.', 'blooikos-core' ),
				),
			),
			'location'        => array(
				array(
					array(
						'param'    => 'page_type',
						'operator' => '==',
						'value'    => 'front_page',
					),
				),
			),
			'show_in_graphql' => false,
			'show_in_rest'    => false,
			'active'          => true,
			'menu_order'      => 0,
		)
	);
}

add_action( 'acf/init', 'blooikos_core_register_home_hero_fields' );

/**
 * Registra Home — Quem Somos.
 *
 * @return void
 */
function blooikos_core_register_home_about_fields() {
	acf_add_local_field_group(
		array(
			'key'             => 'group_blooikos_home_about',
			'title'           => __( 'Home — Quem Somos', 'blooikos-core' ),
			'fields'          => array(
				array(
					'key'          => 'field_blooikos_home_about_eyebrow',
					'label'        => __( 'Chamada superior', 'blooikos-core' ),
					'name'         => 'blooikos_home_about_eyebrow',
					'type'         => 'text',
					'required'     => 0,
					'maxlength'    => 80,
					'instructions' => __( 'Até 80 caracteres. Pode ficar vazio durante a preparação do rascunho.', 'blooikos-core' ),
				),
				array(
					'key'          => 'field_blooikos_home_about_title',
					'label'        => __( 'Título', 'blooikos-core' ),
					'name'         => 'blooikos_home_about_title',
					'type'         => 'text',
					'required'     => 0,
					'maxlength'    => 160,
					'instructions' => __( 'Até 160 caracteres. Pode ficar vazio durante a preparação do rascunho.', 'blooikos-core' ),
				),
				array(
					'key'          => 'field_blooikos_home_about_body',
					'label'        => __( 'Texto de apresentação', 'blooikos-core' ),
					'name'         => 'blooikos_home_about_body',
					'type'         => 'wysiwyg',
					'required'     => 0,
					'maxlength'    => 3000,
					'instructions' => __( 'Até 3000 caracteres de texto. Use parágrafos, ênfase, listas e links; sem embeds ou shortcodes.', 'blooikos-core' ),
					'toolbar'      => 'basic',
					'media_upload' => 0,
					'tabs'         => 'visual',
				),
				array(
					'key'           => 'field_blooikos_home_about_cta',
					'label'         => __( 'Link', 'blooikos-core' ),
					'name'          => 'blooikos_home_about_cta',
					'type'          => 'link',
					'required'      => 0,
					'return_format' => 'array',
					'instructions'  => __( 'Informe título descritivo e HTTPS, caminho / do site ou #âncora. Não use URLs de protótipo.', 'blooikos-core' ),
				),
				array(
					'key'           => 'field_blooikos_home_about_companies',
					'label'         => __( 'Empresas dos cards', 'blooikos-core' ),
					'name'          => 'blooikos_home_about_companies',
					'type'          => 'relationship',
					'required'      => 0,
					'post_type'     => array(
						'blooikos_company',
					),
					'post_status'   => array(
						'publish',
						'draft',
					),
					'return_format' => 'id',
					'bidirectional' => 0,
					'instructions'  => __( 'Selecione empresas publicadas ou em rascunho que você pode editar. A ordem da seleção será preservada.', 'blooikos-core' ),
					'min'           => 0,
					'filters'       => array(
						'search',
					),
					'max'           => 6,
				),
				array(
					'key'           => 'field_blooikos_home_about_variant',
					'label'         => __( 'Aparência', 'blooikos-core' ),
					'name'          => 'blooikos_home_about_variant',
					'type'          => 'select',
					'required'      => 0,
					'choices'       => array(
						'light' => __( 'Claro', 'blooikos-core' ),
						'muted' => __( 'Suave', 'blooikos-core' ),
					),
					'default_value' => 'muted',
					'return_format' => 'value',
					'multiple'      => 0,
					'allow_null'    => 0,
					'instructions'  => __( 'Combinação de aparência controlada; não aceita CSS ou cores livres.', 'blooikos-core' ),
				),
			),
			'location'        => array(
				array(
					array(
						'param'    => 'page_type',
						'operator' => '==',
						'value'    => 'front_page',
					),
				),
			),
			'show_in_graphql' => false,
			'show_in_rest'    => false,
			'active'          => true,
			'menu_order'      => 1,
		)
	);
}

add_action( 'acf/init', 'blooikos_core_register_home_about_fields' );

/**
 * Registra Home — Imagem institucional.
 *
 * @return void
 */
function blooikos_core_register_home_institutional_fields() {
	acf_add_local_field_group(
		array(
			'key'             => 'group_blooikos_home_institutional',
			'title'           => __( 'Home — Imagem institucional', 'blooikos-core' ),
			'fields'          => array(
				array(
					'key'           => 'field_blooikos_home_institutional_image',
					'label'         => __( 'Imagem', 'blooikos-core' ),
					'name'          => 'blooikos_home_institutional_image',
					'type'          => 'image',
					'required'      => 0,
					'return_format' => 'id',
					'preview_size'  => 'medium',
					'library'       => 'all',
					'mime_types'    => 'jpg,jpeg,png,webp',
					'instructions'  => __( 'JPEG, PNG ou WebP. Imagem informativa exige texto alternativo na Biblioteca de mídia.', 'blooikos-core' ),
				),
				array(
					'key'           => 'field_blooikos_home_institutional_decorative',
					'label'         => __( 'Imagem decorativa', 'blooikos-core' ),
					'name'          => 'blooikos_home_institutional_decorative',
					'type'          => 'true_false',
					'required'      => 0,
					'default_value' => 0,
					'ui'            => 1,
					'instructions'  => __( 'Ative somente se a imagem não transmitir informação. O alt compartilhado não será apagado.', 'blooikos-core' ),
				),
			),
			'location'        => array(
				array(
					array(
						'param'    => 'page_type',
						'operator' => '==',
						'value'    => 'front_page',
					),
				),
			),
			'show_in_graphql' => false,
			'show_in_rest'    => false,
			'active'          => true,
			'menu_order'      => 2,
		)
	);
}

add_action( 'acf/init', 'blooikos_core_register_home_institutional_fields' );

/**
 * Registra Home — Indicadores.
 *
 * @return void
 */
function blooikos_core_register_home_indicators_fields() {
	acf_add_local_field_group(
		array(
			'key'             => 'group_blooikos_home_indicators',
			'title'           => __( 'Home — Indicadores', 'blooikos-core' ),
			'fields'          => array(
				array(
					'key'          => 'field_blooikos_home_indicators_eyebrow',
					'label'        => __( 'Chamada superior', 'blooikos-core' ),
					'name'         => 'blooikos_home_indicators_eyebrow',
					'type'         => 'text',
					'required'     => 0,
					'maxlength'    => 80,
					'instructions' => __( 'Até 80 caracteres. Pode ficar vazio durante a preparação do rascunho.', 'blooikos-core' ),
				),
				array(
					'key'          => 'field_blooikos_home_indicators_title',
					'label'        => __( 'Título', 'blooikos-core' ),
					'name'         => 'blooikos_home_indicators_title',
					'type'         => 'text',
					'required'     => 0,
					'maxlength'    => 160,
					'instructions' => __( 'Até 160 caracteres. Pode ficar vazio durante a preparação do rascunho.', 'blooikos-core' ),
				),
				array(
					'key'          => 'field_blooikos_home_indicators_items',
					'label'        => __( 'Indicadores', 'blooikos-core' ),
					'name'         => 'blooikos_home_indicators_items',
					'type'         => 'repeater',
					'required'     => 0,
					'min'          => 0,
					'max'          => 4,
					'layout'       => 'row',
					'collapsed'    => 'field_blooikos_home_indicators_items_value',
					'button_label' => __( 'Adicionar indicador', 'blooikos-core' ),
					'instructions' => __( 'Até 4 itens. Preserve zeros e prefixos: +300, +100, 03, 02.', 'blooikos-core' ),
					'sub_fields'   => array(
						array(
							'key'          => 'field_blooikos_home_indicators_items_value',
							'label'        => __( 'Valor exibido', 'blooikos-core' ),
							'name'         => 'value',
							'type'         => 'text',
							'required'     => 1,
							'maxlength'    => 20,
							'instructions' => __( 'Até 20 caracteres.', 'blooikos-core' ),
						),
						array(
							'key'          => 'field_blooikos_home_indicators_items_label',
							'label'        => __( 'Rótulo', 'blooikos-core' ),
							'name'         => 'label',
							'type'         => 'text',
							'required'     => 1,
							'maxlength'    => 100,
							'instructions' => __( 'Até 100 caracteres.', 'blooikos-core' ),
						),
						array(
							'key'          => 'field_blooikos_home_indicators_items_description',
							'label'        => __( 'Descrição', 'blooikos-core' ),
							'name'         => 'description',
							'type'         => 'text',
							'required'     => 0,
							'maxlength'    => 180,
							'instructions' => __( 'Até 180 caracteres.', 'blooikos-core' ),
						),
					),
				),
			),
			'location'        => array(
				array(
					array(
						'param'    => 'page_type',
						'operator' => '==',
						'value'    => 'front_page',
					),
				),
			),
			'show_in_graphql' => false,
			'show_in_rest'    => false,
			'active'          => true,
			'menu_order'      => 3,
		)
	);
}

add_action( 'acf/init', 'blooikos_core_register_home_indicators_fields' );

/**
 * Registra Home — Serviços.
 *
 * @return void
 */
function blooikos_core_register_home_services_fields() {
	acf_add_local_field_group(
		array(
			'key'             => 'group_blooikos_home_services',
			'title'           => __( 'Home — Serviços', 'blooikos-core' ),
			'fields'          => array(
				array(
					'key'          => 'field_blooikos_home_services_eyebrow',
					'label'        => __( 'Chamada superior', 'blooikos-core' ),
					'name'         => 'blooikos_home_services_eyebrow',
					'type'         => 'text',
					'required'     => 0,
					'maxlength'    => 80,
					'instructions' => __( 'Até 80 caracteres. Pode ficar vazio durante a preparação do rascunho.', 'blooikos-core' ),
				),
				array(
					'key'          => 'field_blooikos_home_services_title',
					'label'        => __( 'Título', 'blooikos-core' ),
					'name'         => 'blooikos_home_services_title',
					'type'         => 'text',
					'required'     => 0,
					'maxlength'    => 160,
					'instructions' => __( 'Até 160 caracteres. Pode ficar vazio durante a preparação do rascunho.', 'blooikos-core' ),
				),
				array(
					'key'          => 'field_blooikos_home_services_description',
					'label'        => __( 'Descrição da seção', 'blooikos-core' ),
					'name'         => 'blooikos_home_services_description',
					'type'         => 'textarea',
					'required'     => 0,
					'maxlength'    => 600,
					'instructions' => __( 'Até 600 caracteres. Pode ficar vazio durante a preparação do rascunho.', 'blooikos-core' ),
					'new_lines'    => '',
					'rows'         => 4,
				),
				array(
					'key'           => 'field_blooikos_home_services_variant',
					'label'         => __( 'Aparência', 'blooikos-core' ),
					'name'          => 'blooikos_home_services_variant',
					'type'          => 'select',
					'required'      => 0,
					'choices'       => array(
						'light' => __( 'Claro', 'blooikos-core' ),
						'muted' => __( 'Suave', 'blooikos-core' ),
					),
					'default_value' => 'light',
					'return_format' => 'value',
					'multiple'      => 0,
					'allow_null'    => 0,
					'instructions'  => __( 'Combinação de aparência controlada; não aceita CSS ou cores livres.', 'blooikos-core' ),
				),
				array(
					'key'          => 'field_blooikos_home_services_items',
					'label'        => __( 'Serviços', 'blooikos-core' ),
					'name'         => 'blooikos_home_services_items',
					'type'         => 'repeater',
					'required'     => 0,
					'min'          => 0,
					'max'          => 12,
					'layout'       => 'row',
					'collapsed'    => 'field_blooikos_home_services_items_title',
					'button_label' => __( 'Adicionar serviço', 'blooikos-core' ),
					'instructions' => __( 'Até 12 serviços. Cada linha precisa de título e descrição.', 'blooikos-core' ),
					'sub_fields'   => array(
						array(
							'key'          => 'field_blooikos_home_services_items_title',
							'label'        => __( 'Título', 'blooikos-core' ),
							'name'         => 'title',
							'type'         => 'text',
							'required'     => 1,
							'maxlength'    => 100,
							'instructions' => __( 'Até 100 caracteres.', 'blooikos-core' ),
						),
						array(
							'key'          => 'field_blooikos_home_services_items_description',
							'label'        => __( 'Descrição', 'blooikos-core' ),
							'name'         => 'description',
							'type'         => 'textarea',
							'required'     => 1,
							'maxlength'    => 500,
							'instructions' => __( 'Até 500 caracteres.', 'blooikos-core' ),
							'new_lines'    => '',
							'rows'         => 4,
						),
						array(
							'key'           => 'field_blooikos_home_services_items_icon',
							'label'         => __( 'Ícone', 'blooikos-core' ),
							'name'          => 'icon',
							'type'          => 'image',
							'required'      => 0,
							'return_format' => 'id',
							'preview_size'  => 'medium',
							'library'       => 'all',
							'mime_types'    => 'jpg,jpeg,png,webp',
							'instructions'  => __( 'JPEG, PNG ou WebP; ícone decorativo junto ao texto.', 'blooikos-core' ),
						),
					),
				),
			),
			'location'        => array(
				array(
					array(
						'param'    => 'page_type',
						'operator' => '==',
						'value'    => 'front_page',
					),
				),
			),
			'show_in_graphql' => false,
			'show_in_rest'    => false,
			'active'          => true,
			'menu_order'      => 4,
		)
	);
}

add_action( 'acf/init', 'blooikos_core_register_home_services_fields' );

/**
 * Registra Home — Empresas em destaque.
 *
 * @return void
 */
function blooikos_core_register_home_companies_fields() {
	acf_add_local_field_group(
		array(
			'key'             => 'group_blooikos_home_companies',
			'title'           => __( 'Home — Empresas em destaque', 'blooikos-core' ),
			'fields'          => array(
				array(
					'key'           => 'field_blooikos_home_companies_featured',
					'label'         => __( 'Empresas em destaque', 'blooikos-core' ),
					'name'          => 'blooikos_home_companies_featured',
					'type'          => 'relationship',
					'required'      => 0,
					'post_type'     => array(
						'blooikos_company',
					),
					'post_status'   => array(
						'publish',
						'draft',
					),
					'return_format' => 'id',
					'bidirectional' => 0,
					'instructions'  => __( 'Selecione empresas publicadas ou em rascunho que você pode editar. A ordem da seleção será preservada.', 'blooikos-core' ),
					'min'           => 0,
					'filters'       => array(
						'search',
					),
					'max'           => 3,
				),
			),
			'location'        => array(
				array(
					array(
						'param'    => 'page_type',
						'operator' => '==',
						'value'    => 'front_page',
					),
				),
			),
			'show_in_graphql' => false,
			'show_in_rest'    => false,
			'active'          => true,
			'menu_order'      => 5,
		)
	);
}

add_action( 'acf/init', 'blooikos_core_register_home_companies_fields' );

/**
 * Registra Home — Contato.
 *
 * @return void
 */
function blooikos_core_register_home_contact_fields() {
	acf_add_local_field_group(
		array(
			'key'             => 'group_blooikos_home_contact',
			'title'           => __( 'Home — Contato', 'blooikos-core' ),
			'fields'          => array(
				array(
					'key'          => 'field_blooikos_home_contact_eyebrow',
					'label'        => __( 'Chamada superior', 'blooikos-core' ),
					'name'         => 'blooikos_home_contact_eyebrow',
					'type'         => 'text',
					'required'     => 0,
					'maxlength'    => 80,
					'instructions' => __( 'Até 80 caracteres. Pode ficar vazio durante a preparação do rascunho.', 'blooikos-core' ),
				),
				array(
					'key'          => 'field_blooikos_home_contact_title',
					'label'        => __( 'Título', 'blooikos-core' ),
					'name'         => 'blooikos_home_contact_title',
					'type'         => 'text',
					'required'     => 0,
					'maxlength'    => 160,
					'instructions' => __( 'Até 160 caracteres. Pode ficar vazio durante a preparação do rascunho.', 'blooikos-core' ),
				),
				array(
					'key'           => 'field_blooikos_home_contact_variant',
					'label'         => __( 'Aparência', 'blooikos-core' ),
					'name'          => 'blooikos_home_contact_variant',
					'type'          => 'select',
					'required'      => 0,
					'choices'       => array(
						'light' => __( 'Claro', 'blooikos-core' ),
						'muted' => __( 'Suave', 'blooikos-core' ),
					),
					'default_value' => 'muted',
					'return_format' => 'value',
					'multiple'      => 0,
					'allow_null'    => 0,
					'instructions'  => __( 'Combinação de aparência controlada; não aceita CSS ou cores livres.', 'blooikos-core' ),
				),
			),
			'location'        => array(
				array(
					array(
						'param'    => 'page_type',
						'operator' => '==',
						'value'    => 'front_page',
					),
				),
			),
			'show_in_graphql' => false,
			'show_in_rest'    => false,
			'active'          => true,
			'menu_order'      => 6,
		)
	);
}

add_action( 'acf/init', 'blooikos_core_register_home_contact_fields' );
