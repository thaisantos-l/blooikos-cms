<?php
/** Leitura pública compartilhada.
 *
 * @package BlooikosCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; }
/**
 * Executa uma leitura anônima e restaura o usuário mesmo em caso de erro.
 *
 * @param callable $callback Leitura local.
 * @return mixed
 */
function blooikos_core_graphql_anonymous( $callback ) {
	$user_id = get_current_user_id();
	wp_set_current_user( 0 );
	try {
		return $callback();
	} finally {
		wp_set_current_user( $user_id );
	}
}
