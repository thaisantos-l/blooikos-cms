<?php
/**
 * Empresas reutilizáveis, sem rotas públicas.
 *
 * @package BlooikosCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registra empresas com as capacidades nativas de Pages.
 *
 * @return void
 */
function blooikos_core_register_company() {
	register_post_type(
		'blooikos_company',
		array(
			'labels'                           => array(
				'name'          => __( 'Empresas', 'blooikos-core' ),
				'singular_name' => __( 'Empresa', 'blooikos-core' ),
				'add_new_item'  => __( 'Adicionar empresa', 'blooikos-core' ),
				'edit_item'     => __( 'Editar empresa', 'blooikos-core' ),
				'search_items'  => __( 'Buscar empresas', 'blooikos-core' ),
				'not_found'     => __( 'Nenhuma empresa encontrada.', 'blooikos-core' ),
			),
			'public'                           => false,
			'publicly_queryable'               => false,
			'show_ui'                          => true,
			'show_in_menu'                     => true,
			'show_in_rest'                     => false,
			'show_in_graphql'                  => true,
			'graphql_single_name'              => 'BlooikosCompany',
			'graphql_plural_name'              => 'BlooikosCompanies',
			'graphql_register_root_field'      => false,
			'graphql_register_root_connection' => false,
			'graphql_exclude_mutations'        => array( 'create', 'update', 'delete' ),
			'show_in_nav_menus'                => false,
			'exclude_from_search'              => true,
			'has_archive'                      => false,
			'rewrite'                          => false,
			'query_var'                        => false,
			'hierarchical'                     => false,
			'supports'                         => array( 'title', 'revisions' ),
			'capability_type'                  => 'page',
			'map_meta_cap'                     => true,
		)
	);
}

add_action( 'init', 'blooikos_core_register_company' );
