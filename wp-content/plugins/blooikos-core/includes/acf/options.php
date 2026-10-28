<?php
/**
 * Configurações globais administradas pelo ACF.
 *
 * @package BlooikosCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Restringe opções aos administradores e evita autoload dos valores.
 *
 * @return void
 */
function blooikos_core_register_options() {
	acf_add_options_page(
		array(
			'page_title'      => __( 'Configurações Blooikos', 'blooikos-core' ),
			'menu_title'      => __( 'Blooikos', 'blooikos-core' ),
			'menu_slug'       => 'blooikos-settings',
			'capability'      => 'manage_options',
			'redirect'        => false,
			'post_id'         => 'options',
			'autoload'        => false,
			'show_in_graphql' => false,
		)
	);
}

add_action( 'acf/init', 'blooikos_core_register_options' );
