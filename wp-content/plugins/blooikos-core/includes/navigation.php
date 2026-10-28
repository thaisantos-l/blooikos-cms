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
