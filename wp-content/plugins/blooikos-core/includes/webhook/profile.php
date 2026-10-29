<?php
/**
 * Perfis limitados do rebuild, sem alterar o contrato editorial 1.0.0.
 *
 * @package BlooikosCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Identificador operacional estável; não seleciona conteúdo na UI.
 *
 * @return string
 */
function blooikos_core_rebuild_profile() {
	return 'home-hero';
}

/**
 * Migra a consulta automaticamente uma vez, sem reetiquetar a versão publicada.
 *
 * @return void
 */
function blooikos_core_rebuild_sync_profile() {
	$projection = hash( 'sha256', 'BlooikosHome:optional-v1:' . hash_file( 'sha256', dirname( __DIR__, 2 ) . '/contracts/graphql/v1/operations.graphql' ) );
	$state      = blooikos_core_rebuild_state();
	if ( ( $state['requestedProjection'] ?? '' ) === $projection ) {
		return;
	}
	blooikos_core_rebuild_change(
		static function ( &$current ) use ( $projection ) {
			if ( ( $current['requestedProjection'] ?? '' ) === $projection ) {
				return;
			}
			if ( $current['dirtySequence'] === $current['processedSequence'] ) {
				$current['firstPendingAt'] = time();
			}
			$current['dirtySequence']       = blooikos_core_rebuild_increment( $current['dirtySequence'] );
			$current['lastChangedAt']       = time();
			$current['requestedProfile']    = 'home-hero';
			$current['requestedProjection'] = $projection;
			$current['projectionValid']     = false;
		}
	);
}
