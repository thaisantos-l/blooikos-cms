<?php
/**
 * Publicação assíncrona do frontend Blooikos.
 *
 * @package BlooikosCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/profile.php';
require_once __DIR__ . '/projection.php';
require_once __DIR__ . '/github.php';
require_once __DIR__ . '/worker.php';
require_once __DIR__ . '/rest.php';
require_once __DIR__ . '/admin.php';

/**
 * Agenda própria de um minuto.
 *
 * @param array $schedules Agenda.
 * @return array
 */
function blooikos_core_rebuild_schedule( $schedules ) {
	$schedules['blooikos_minute'] = array(
		'interval' => 60,
		'display'  => 'Blooikos: um minuto',
	);
	return $schedules;
}
/**
 * Inicializa sem HTTP; migração também em upgrade ativo.
 *
 * @return void
 */
function blooikos_core_rebuild_bootstrap() {
	try {
		if ( ! blooikos_core_rebuild_migrate() ) {
			$GLOBALS['blooikos_core_rebuild_fault'] = 'STORAGE_UNAVAILABLE';
			return;
		}
		blooikos_core_rebuild_sync_profile();
		require_once __DIR__ . '/events.php';
		if ( ! wp_next_scheduled( 'blooikos_core_rebuild_tick' ) ) {
			wp_schedule_event( time() + 60, 'blooikos_minute', 'blooikos_core_rebuild_tick' );
		}
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			require_once __DIR__ . '/cli.php';
		}
	} catch ( Throwable $error ) {
		$GLOBALS['blooikos_core_rebuild_fault'] = 'STORAGE_UNAVAILABLE';
	}
}
/**
 * Desativa somente esta agenda, mantendo fila e conteúdo.
 *
 * @return void
 */
function blooikos_core_rebuild_deactivate() {
	if ( blooikos_core_rebuild_state() ) {
		blooikos_core_rebuild_change(
			static function ( &$state ) {
				$state['reconcileRequested'] = true;
			}
		);
	}
	wp_clear_scheduled_hook( 'blooikos_core_rebuild_tick' );
}
/**
 * Tick do cron sem fatal público; diagnóstico fechado.
 *
 * @return void
 */
function blooikos_core_rebuild_cron() {
	try {
		blooikos_core_rebuild_tick();
	} catch ( Throwable $error ) {
		$GLOBALS['blooikos_core_rebuild_fault'] = 'WORKER_INTERRUPTED';
	}
}
// phpcs:ignore WordPress.WP.CronInterval.CronSchedulesInterval -- SPEC 08 exige tick de 60 s; lease e budget limitam trabalho.
add_filter( 'cron_schedules', 'blooikos_core_rebuild_schedule' );
add_action( 'init', 'blooikos_core_rebuild_bootstrap', 100 );
add_action( 'blooikos_core_rebuild_tick', 'blooikos_core_rebuild_cron' );

/** Reativação preserva a fila e exige reconciliação antes do próximo dispatch.
 *
 * @return void
 */
function blooikos_core_rebuild_activate() {
	if ( blooikos_core_rebuild_migrate() ) {
		blooikos_core_rebuild_change(
			static function ( &$state ) {
				$state['reconcileRequested'] = true;
			}
		);
	}
}
