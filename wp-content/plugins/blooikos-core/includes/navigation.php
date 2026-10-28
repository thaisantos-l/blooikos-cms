<?php
/**
 * Localizações dos menus nativos.
 *
 * @package BlooikosCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Habilita navegação nativa sem alterar o tema.
 *
 * @return void
 */
function blooikos_core_register_navigation() {
	register_nav_menus(
		array(
			'blooikos_primary' => __( 'Blooikos — Principal', 'blooikos-core' ),
			'blooikos_footer'  => __( 'Blooikos — Footer', 'blooikos-core' ),
		)
	);
}

add_action( 'after_setup_theme', 'blooikos_core_register_navigation' );

/**
 * Impede a exposição automática dos menus Blooikos pelo WPGraphQL.
 *
 * Menus ligados a localizações tornam-se públicos por padrão no WPGraphQL.
 * O contrato da SPEC 03 decidirá sua exposição; os demais menus não mudam.
 *
 * @param bool   $is_private Privacidade determinada anteriormente.
 * @param string $model_name Nome do modelo WPGraphQL.
 * @param object $data Termo do menu ou post do item.
 * @return bool
 */
function blooikos_core_keep_navigation_private( $is_private, $model_name, $data ) {
	if ( ! in_array( $model_name, array( 'MenuObject', 'MenuItemObject' ), true ) ) {
		return $is_private;
	}
	$locations = get_nav_menu_locations();
	$menu_ids  = array();
	foreach ( array( 'blooikos_primary', 'blooikos_footer' ) as $location ) {
		if ( ! empty( $locations[ $location ] ) ) {
			$menu_ids[] = (int) $locations[ $location ];
		}
	}
	if ( 'MenuObject' === $model_name ) {
		return $is_private || in_array( (int) $data->term_id, $menu_ids, true );
	}
	$item_menus = wp_get_object_terms( $data->ID, 'nav_menu', array( 'fields' => 'ids' ) );
	return $is_private || ( ! is_wp_error( $item_menus ) && (bool) array_intersect( $menu_ids, $item_menus ) );
}

add_filter( 'graphql_data_is_private', 'blooikos_core_keep_navigation_private', 10, 3 );
