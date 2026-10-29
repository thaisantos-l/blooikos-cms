<?php
/**
 * Publicação assíncrona do frontend Blooikos.
 *
 * @package BlooikosCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/** Operações locais e worker; nunca imprime configuração privada.
 *
 * @param array $args Ação.
 * @param array $assoc Opções.
 * @return void
 */
function blooikos_core_rebuild_cli( $args, $assoc ) {
	$action = $args[0] ?? '';
	if ( ! in_array( $action, array( 'status', 'tick', 'reconcile', 'retry', 'pause', 'resume' ), true ) || ( isset( $assoc['format'] ) && 'json' !== $assoc['format'] ) || ( isset( $assoc['adopt-published'] ) && 'reconcile' !== $action ) ) {
		WP_CLI::error( 'Use status|tick|reconcile|retry|pause|resume [--format=json]. --adopt-published somente com reconcile e pausa prévia.' );
	}
	try {
		if ( 'tick' === $action ) {
			blooikos_core_rebuild_tick();
		} elseif ( 'status' !== $action ) {
					blooikos_core_rebuild_action( $action, isset( $assoc['adopt-published'] ) );
		}
			WP_CLI::line( wp_json_encode( blooikos_core_rebuild_status(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
	} catch ( Throwable $error ) {
		WP_CLI::error( 'Operação interrompida; consulte status. Lease perdido ou configuração/estado exige reconciliação.' );
	}
}
WP_CLI::add_command(
	'blooikos rebuild',
	'blooikos_core_rebuild_cli',
	array(
		'shortdesc' => 'Fila do frontend. Status é somente leitura; tick pode acessar GitHub quando habilitado. Reconcile marca nova projeção; --adopt-published exige pausa e nenhum run em voo; adota baseline apenas após verificar run/job/marker.',
		'synopsis'  => '<action> [--format=<format>] [--adopt-published]',
	)
);
