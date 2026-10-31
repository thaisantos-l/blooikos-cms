<?php
/**
 * Publicação assíncrona do frontend Blooikos.
 *
 * @package BlooikosCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Diagnóstico sem HTTP, token ou payload editorial.
 *
 * @return array
 */
function blooikos_core_rebuild_status() {
	$state = blooikos_core_rebuild_state();
	if ( ! $state ) {
		return array(
			'status' => 'Indisponível',
			'error'  => 'STORAGE_UNAVAILABLE',
		);
	}
	$configuration = array(
		'activationRequested'   => defined( 'BLOOIKOS_REBUILD_ENABLED' ) && true === BLOOIKOS_REBUILD_ENABLED,
		'productionEnvironment' => defined( 'BLOOIKOS_REBUILD_ENVIRONMENT' ) && 'production' === BLOOIKOS_REBUILD_ENVIRONMENT,
		'productionOrigin'      => 'https://cms.blooikos.com.br' === untrailingslashit( home_url() ),
		'tokenPresent'          => defined( 'BLOOIKOS_REBUILD_GITHUB_TOKEN' ) && is_string( BLOOIKOS_REBUILD_GITHUB_TOKEN ) && '' !== BLOOIKOS_REBUILD_GITHUB_TOKEN,
	);
	$status        = ! blooikos_core_rebuild_enabled() ? ( $configuration['activationRequested'] ? 'Configuração incompleta' : 'Desativado' ) : 'Aguardando consolidação';
	if ( blooikos_core_rebuild_enabled() ) {
		if ( $state['pauseReason'] ) {
			$status = 'Pausado';
		} elseif ( $state['blocked'] || $state['lastError'] ) {
					$status = 'Falhou ou aguarda verificação';
		} elseif ( $state['request'] ) {
				$status = $state['request']['run'] ? 'Construindo/publicando' : 'Aguardando execução';
		} elseif ( $state['published'] && 'artifact-http-v1' === ( $state['published']['verification'] ?? '' ) && ! empty( $state['projectionValid'] ) && ! empty( $state['reconciled'] ) && empty( $state['reconcileRequested'] ) && $state['published']['epoch'] === $state['epoch'] && $state['published']['digest'] === $state['contentDigest'] && $state['published']['version'] === $state['contentVersion'] && $state['processedSequence'] === $state['dirtySequence'] && ! blooikos_core_rebuild_writing() ) {
						$status = 'Publicado';
		}
	}
	return array(
		'status'           => $status,
		'enabled'          => blooikos_core_rebuild_enabled(),
		'configuration'    => $configuration,
		'epoch'            => $state['epoch'],
		'desiredVersion'   => $state['contentVersion'],
		'published'        => $state['published'],
		'pendingChanges'   => $state['dirtySequence'] !== $state['processedSequence'],
		'newerPending'     => $state['request'] && ( $state['request']['epoch'] !== $state['epoch'] || $state['request']['digest'] !== $state['contentDigest'] || $state['request']['version'] !== $state['contentVersion'] || $state['dirtySequence'] !== $state['processedSequence'] ),
		'lastTickAt'       => $state['lastTickAt'],
		'schedulerDelayed' => time() - $state['lastTickAt'] > 180,
		'nextAttemptAt'    => $state['request']['nextAt'] ?? null,
		'runId'            => $state['request']['run'] ?? '',
		'error'            => $state['blocked'] ? $state['blocked'] : $state['lastError'],
		'pauseReason'      => $state['pauseReason'],
		'logs'             => $state['logs'],
	);
}
/** Ações locais. Reconcile força nova projeção sem zerar contadores.
 *
 * @param string $action Ação permitida.
 * @param bool   $adopt_published Adota baseline publicado após prova do run.
 * @return void
 * @throws RuntimeException Código seguro de diagnóstico.
 */
function blooikos_core_rebuild_action( $action, $adopt_published = false ) {
	if ( ! in_array( $action, array( 'reconcile', 'retry', 'pause', 'resume' ), true ) ) {
		throw new RuntimeException( 'INVALID_ACTION' );
	}
	blooikos_core_rebuild_change(
		static function ( &$state ) use ( $action, $adopt_published ) {
			if ( 'pause' === $action ) {
				$state['pauseReason'] = 'OPERATOR_PAUSED';
			}
			if ( 'resume' === $action ) {
				if ( 'PUBLISHER_PAUSED' === $state['pauseReason'] ) {
					throw new RuntimeException( 'RESUME_PUBLISHER_MANUALLY_FIRST' );
				}
				$state['pauseReason'] = '';
			}
			if ( 'retry' === $action ) {
				if ( 'EPOCH_RECONCILIATION_REQUIRED' === $state['blocked'] ) {
					throw new RuntimeException( 'EXPLICIT_EPOCH_RECONCILIATION_REQUIRED' );
				}
				$state['blocked']   = '';
				$state['lastError'] = '';
				if ( $state['request'] ) {
					$state['request']['stage']           = $state['request']['run'] ? 'run' : ( $state['request']['delivery'] ? 'uncertain' : 'dispatch' );
					$state['request']['nextAt']          = time();
					$state['request']['delivery']        = 'uncertain' === $state['request']['stage'] ? max( 1, $state['request']['delivery'] ) : 0;
					$state['request']['executions']      = 0;
					$state['request']['lookupResumedAt'] = time();
					unset( $state['request']['confirmationStartedAt'], $state['request']['confirmationAttempts'], $state['request']['proof'] );
				}
			}
			if ( 'reconcile' === $action || 'retry' === $action ) {
				if ( $adopt_published ) {
					if ( ! $state['pauseReason'] || $state['request'] ) {
						throw new RuntimeException( 'PAUSE_AND_RESOLVE_RUN_BEFORE_RECONCILIATION' );
					}
					$state['adoptPublished'] = true;
				}$state['dirtySequence']     = blooikos_core_rebuild_increment( $state['dirtySequence'] );
				$state['firstPendingAt']     = time() - 300;
				$state['reconcileRequested'] = true;
			}
			$state['logs'][] = array(
				'at'   => time(),
				'code' => strtoupper( $action ),
			);
		}
	);
}
/**
 * Menu nativo do painel.
 *
 * @return void
 */
function blooikos_core_rebuild_admin_menu() {
	add_management_page( 'Publicação do frontend', 'Publicação do frontend', 'manage_options', 'blooikos-publication', 'blooikos_core_rebuild_admin_page' );
}
/**
 * Página sem conteúdo não escapado.
 *
 * @return void
 */
function blooikos_core_rebuild_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Acesso negado.', 'blooikos-core' ) );
	}
	$status                   = blooikos_core_rebuild_status();
	$status['lastProcessing'] = ! empty( $status['lastTickAt'] ) ? wp_date( 'd/m/Y H:i:s T', $status['lastTickAt'] ) : 'Ainda não executado';
	$status['nextAttempt']    = ! empty( $status['nextAttemptAt'] ) ? wp_date( 'd/m/Y H:i:s T', $status['nextAttemptAt'] ) : 'Sem tentativa agendada';
	echo '<div class="wrap" style="max-width:70rem;overflow-wrap:anywhere"><h1>Publicação do frontend</h1><p>Salvar no CMS registra a mudança. O site só é marcado como publicado após verificar a release disponível.</p>';
	echo '<h2>' . esc_html( $status['status'] ) . '</h2><dl>';
	$labels = array(
		'lastProcessing'   => 'Último processamento',
		'nextAttempt'      => 'Próxima tentativa',
		'desiredVersion'   => 'Versão consolidada',
		'pendingChanges'   => 'Alterações salvas aguardando consolidação',
		'newerPending'     => 'Há versão posterior ao build atual',
		'schedulerDelayed' => 'Agendamento sem sinal há mais de três minutos',
		'error'            => 'Diagnóstico',
		'pauseReason'      => 'Motivo da pausa',
	);
	foreach ( $labels as $key => $label ) {
		echo '<dt><strong>' . esc_html( $label ) . '</strong></dt><dd>' . esc_html( is_bool( $status[ $key ] ?? null ) ? ( $status[ $key ] ? 'Sim' : 'Não' ) : ( $status[ $key ] ?? '—' ) ) . '</dd>';
	}
	echo '<dt><strong>Versão publicada confirmada</strong></dt><dd>' . esc_html( $status['published']['version'] ?? 'Ainda não confirmada' ) . '</dd></dl>';
	if ( ! empty( $status['runId'] ) && blooikos_core_rebuild_decimal( $status['runId'] ) ) {
		echo '<p><a href="' . esc_url( 'https://github.com/thaisantos-l/blooikos-frontend/actions/runs/' . $status['runId'] ) . '">Consultar execução no GitHub</a></p>';
	}
	foreach ( array(
		'activationRequested'   => 'Ativação solicitada',
		'productionEnvironment' => 'Ambiente de produção',
		'productionOrigin'      => 'Origem de produção',
		'tokenPresent'          => 'Credencial configurada',
	) as $key => $label ) {
		if ( isset( $status['configuration'][ $key ] ) ) {
			echo '<p>' . esc_html( $label ) . ': ' . esc_html( $status['configuration'][ $key ] ? 'Sim' : 'Não' ) . '</p>';
		}
	}
	echo '<p>Estas ações são de recuperação. Uma edição normal não exige tentar novamente ou reconciliar. Uma pausa informada pela hospedagem deve ser resolvida pelo responsável antes da retomada.</p>';
	foreach ( array(
		'retry'     => 'Tentar novamente',
		'pause'     => 'Pausar',
		'resume'    => 'Retomar',
		'reconcile' => 'Reconciliar',
	) as $action => $label ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline-block;margin:0 .5rem 1rem 0"><input type="hidden" name="action" value="blooikos_core_rebuild"><input type="hidden" name="rebuild_action" value="' . esc_attr( $action ) . '">';
		wp_nonce_field( 'blooikos_core_rebuild' );
		echo '<button class="button" type="submit">' . esc_html( $label ) . '</button></form>';
	}
	echo '</div>';
}
/**
 * Capability e nonce antes de qualquer alteração.
 *
 * @return void
 */
function blooikos_core_rebuild_admin_action() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Acesso negado.', 'blooikos-core' ), '', array( 'response' => 403 ) );
	}
	check_admin_referer( 'blooikos_core_rebuild' );
	$action = isset( $_POST['rebuild_action'] ) ? sanitize_key( wp_unslash( $_POST['rebuild_action'] ) ) : '';
	try {
		blooikos_core_rebuild_action( $action );
	} catch ( Throwable $error ) {
		wp_die( esc_html__( 'A ação não pôde ser concluída. Confira o estado e a pausa no publicador.', 'blooikos-core' ), '', array( 'response' => 409 ) );
	}
		wp_safe_redirect( admin_url( 'tools.php?page=blooikos-publication' ) );
		exit;
}
add_action( 'admin_menu', 'blooikos_core_rebuild_admin_menu' );
add_action( 'admin_post_blooikos_core_rebuild', 'blooikos_core_rebuild_admin_action' );
