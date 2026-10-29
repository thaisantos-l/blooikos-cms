<?php
/**
 * Publicação assíncrona do frontend Blooikos.
 *
 * @package BlooikosCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/** Próxima tentativa com jitter e limite remoto obrigatório.
 *
 * @param int $attempt Tentativa.
 * @param int $remote Atraso imposto pelo servidor.
 * @return int
 */
function blooikos_core_rebuild_retry_at( $attempt, $remote = 0 ) {
	$seconds = 60 * ( 2 ** min( 3, max( 0, $attempt - 1 ) ) );
	return time() + max( $remote, $seconds + wp_rand( 0, (int) floor( $seconds / 5 ) ) );
}
/** Atualiza pedido somente com a posse atual, sem tocar dirty/ack concorrentes.
 *
 * @param array  $request Pedido.
 * @param array  $lease Lease.
 * @param string $error Código seguro.
 * @return void
 */
function blooikos_core_rebuild_save_request( $request, $lease, $error = '' ) {
	blooikos_core_rebuild_change(
		static function ( &$state ) use ( $request, $error ) {
			$state['request']   = $request;
			$state['lastError'] = $error;
			if ( 'CONFIGURATION_BLOCKED' === $error ) {
				$state['blocked'] = $error;
			}
			if ( 'PUBLISHER_PAUSED' === $error ) {
				$state['pauseReason'] = $error;
			}
			if ( $error ) {
				$state['logs'][] = array(
					'at'   => time(),
					'code' => $error,
				);
			}
		},
		$lease
	);
}
/** Consolida apenas projeção completa e sequência estável.
 *
 * @param array $lease Lease.
 * @return void
 */
function blooikos_core_rebuild_consolidate( $lease ) {
	$state = blooikos_core_rebuild_state();
	if ( $state['dirtySequence'] === $state['processedSequence'] || blooikos_core_rebuild_writing( true ) || ( time() - $state['lastChangedAt'] < 60 && time() - $state['firstPendingAt'] < 300 ) ) {
		return;
	}
	try {
		$projection = blooikos_core_rebuild_projection( $state['mediaRevision'], $state['mediaDigest'] ?? '' );
	} catch ( Throwable $error ) {
			blooikos_core_rebuild_change(
				static function ( &$current ) use ( $state, $error ) {
					if ( $current['dirtySequence'] === $state['dirtySequence'] ) {
						$current['processedSequence'] = $state['dirtySequence'];
						$codes                        = array( 'DEPENDENCIES_UNAVAILABLE', 'GRAPHQL_RESPONSE_INVALID', 'MEDIA_UNAVAILABLE', 'MEDIA_FILE_LIMIT', 'MEDIA_BYTE_LIMIT', 'INVALID_CANONICAL_KEY', 'INVALID_CANONICAL_NUMBER', 'INVALID_CANONICAL_VALUE' );
						$current['lastError']         = in_array( $error->getMessage(), $codes, true ) ? $error->getMessage() : 'PROJECTION_FAILED';
						$current['projectionValid']   = false;
					}
				},
				$lease
			);
			return;
	}
		blooikos_core_rebuild_change(
			static function ( &$current ) use ( $state, $projection ) {
				if ( $current['dirtySequence'] !== $state['dirtySequence'] || blooikos_core_rebuild_writing() ) {
					return;
				}
				$current['processedSequence'] = $state['dirtySequence'];
				$current['projectionValid']   = true;
				if ( $projection['contentDigest'] !== $current['contentDigest'] ) {
					$current['contentVersion'] = blooikos_core_rebuild_increment( $current['contentVersion'] );
					foreach ( $projection as $key => $value ) {
						$current[ $key ] = $value;
					}
				}
				if ( in_array( $current['lastError'], array( '', 'EDITORIAL_INVALID', 'DEPENDENCIES_UNAVAILABLE', 'GRAPHQL_RESPONSE_INVALID', 'PROJECTION_FAILED', 'MEDIA_UNAVAILABLE', 'MEDIA_FILE_LIMIT', 'MEDIA_BYTE_LIMIT' ), true ) ) {
					$current['lastError'] = '';
				}
			},
			$lease
		);
}
/** Inicia pedido durável depois de verificar publicação por push/manual.
 *
 * @param array $state Estado.
 * @param array $lease Lease.
 * @return void
 */
function blooikos_core_rebuild_start( $state, $lease ) {
	$request = array(
		'id'         => wp_generate_uuid4(),
		'epoch'      => $state['epoch'],
		'version'    => $state['contentVersion'],
		'digest'     => $state['contentDigest'],
		'stage'      => 'dispatch',
		'delivery'   => 0,
		'executions' => 0,
		'nextAt'     => time(),
		'startedAt'  => time(),
		'run'        => '',
		'attempt'    => '',
		'source'     => 'editorial',
		'page'       => 1,
	);
	$remote  = blooikos_core_rebuild_http( 'marker' );
	if ( $remote['paused'] ?? false ) {
		blooikos_core_rebuild_save_request( null, $lease, 'PUBLISHER_PAUSED' );
		return;
	}
	$marker_content = $remote['data']['content'] ?? null;
	if ( is_array( $marker_content ) && ( ( $marker_content['epoch'] ?? '' ) !== $state['epoch'] || ( strlen( (string) ( $marker_content['contentVersion'] ?? '' ) ) > strlen( $state['contentVersion'] ) || ( strlen( (string) ( $marker_content['contentVersion'] ?? '' ) ) === strlen( $state['contentVersion'] ) && strcmp( (string) $marker_content['contentVersion'], $state['contentVersion'] ) > 0 ) ) ) ) {
		blooikos_core_rebuild_change(
			static function ( &$current ) {
				$current['blocked'] = 'EPOCH_RECONCILIATION_REQUIRED';
			},
			$lease
		);
		return;
	}
	if ( blooikos_core_rebuild_marker_matches( $remote['data'], $request ) ) {
		$request['source']  = 'observed';
		$request['run']     = $remote['data']['runId'];
		$request['attempt'] = $remote['data']['attempt'];
		$request['stage']   = 'run';
	}
	blooikos_core_rebuild_save_request( $request, $lease );
}
/** Aceite somente da versão provada; não limpa alterações posteriores.
 *
 * @param array $request Pedido.
 * @param array $marker Marcador verificado.
 * @param array $lease Lease.
 * @return void
 */
function blooikos_core_rebuild_acknowledge( $request, $marker, $lease ) {
	blooikos_core_rebuild_change(
		static function ( &$state ) use ( $request, $marker ) {
			if ( 'baseline' === ( $request['source'] ?? '' ) ) {
				$state['epoch'] = $request['epoch'];
				if ( strlen( $state['contentVersion'] ) < strlen( $request['version'] ) || ( strlen( $state['contentVersion'] ) === strlen( $request['version'] ) && strcmp( $state['contentVersion'], $request['version'] ) < 0 ) ) {
					$state['contentVersion'] = $request['version'];
				}
				if ( $state['contentDigest'] !== $request['digest'] ) {
					$state['contentVersion'] = blooikos_core_rebuild_increment( $state['contentVersion'] );
				}
				$state['dirtySequence']      = blooikos_core_rebuild_increment( $state['dirtySequence'] );
				$state['firstPendingAt']     = time() - 300;
				$state['blocked']            = '';
				$state['adoptPublished']     = false;
				$state['reconcileRequested'] = false;
			}
			$state['published']  = array(
				'epoch'     => $request['epoch'],
				'version'   => $request['version'],
				'digest'    => $request['digest'],
				'run'       => $request['run'],
				'attempt'   => $request['attempt'],
				'releaseId' => $marker['releaseId'],
				'at'        => time(),
			);
			$state['request']    = null;
			$state['reconciled'] = true;
			$state['lastError']  = '';
			$state['logs'][]     = array(
				'at'   => time(),
				'code' => 'PUBLICATION_CONFIRMED',
			);
		},
		$lease
	);
}
/** Reconcilia uma etapa por tick; máximo três HTTP, nenhum sleep.
 *
 * @param array $request Pedido.
 * @param array $lease Lease.
 * @return void
 */
function blooikos_core_rebuild_process( $request, $lease ) {
	if ( $request['nextAt'] > time() || 'failed' === $request['stage'] ) {
		return;
	}
	$stage = $request['stage'];
	if ( 'dispatch' === $stage ) {
		$current = blooikos_core_rebuild_state();
		if ( $current['pauseReason'] || $current['blocked'] || ! blooikos_core_rebuild_enabled() ) {
			return;
		}
		if ( $request['version'] !== $current['contentVersion'] || empty( $current['projectionValid'] ) || $current['dirtySequence'] !== $current['processedSequence'] ) {
			blooikos_core_rebuild_save_request( null, $lease );
			return;
		}
		if ( $request['delivery'] >= 5 ) {
			$request['stage'] = 'failed';
			blooikos_core_rebuild_save_request( $request, $lease, 'DELIVERY_EXHAUSTED' );
			return;
		}
		++$request['delivery'];
		$request['stage']     = 'uncertain';
		$request['startedAt'] = time();
		$request['nextAt']    = blooikos_core_rebuild_retry_at( $request['delivery'] );
		blooikos_core_rebuild_save_request( $request, $lease ); // Persistir ANTES do envio: queda não autoriza repetir cegamente.
		$remote = blooikos_core_rebuild_http( 'actions/workflows/deploy-production.yml/dispatches', blooikos_core_rebuild_payload( $request ) );
		$id     = (string) ( $remote['data']['workflow_run_id'] ?? '' );
		if ( 200 === $remote['status'] && blooikos_core_rebuild_decimal( $id ) && ( $remote['data']['run_url'] ?? '' ) === 'https://api.github.com/repos/thaisantos-l/blooikos-frontend/actions/runs/' . $id && ( $remote['data']['html_url'] ?? '' ) === 'https://github.com/thaisantos-l/blooikos-frontend/actions/runs/' . $id ) {
			$request['run']   = $id;
			$request['stage'] = 'run';
		}
		if ( 'CONFIGURATION_BLOCKED' === $remote['code'] ) {
			$request['stage'] = 'failed';
		}
		$request['nextAt'] = blooikos_core_rebuild_retry_at( $request['delivery'], $remote['delay'] );
		blooikos_core_rebuild_save_request( $request, $lease, $remote['code'] );
		return;
	}
	if ( 'uncertain' === $stage ) {
		$remote = blooikos_core_rebuild_http( 'actions/workflows/deploy-production.yml/runs?event=workflow_dispatch&branch=main&per_page=100&page=' . (int) $request['page'] . '&created=' . rawurlencode( '>=' . gmdate( 'Y-m-d\TH:i:s\Z', $request['startedAt'] - 5 ) ) );
		if ( 200 === $remote['status'] && is_array( $remote['data']['workflow_runs'] ?? null ) ) {
			$runs = $remote['data']['workflow_runs'];
			foreach ( $runs as $run ) {
				if ( blooikos_core_rebuild_run_valid( $run ) && 'workflow_dispatch' === $run['event'] && ( $run['display_title'] ?? '' ) === 'Blooikos content ' . $request['id'] && strtotime( $run['created_at'] ?? '' ) >= $request['startedAt'] - 5 ) {
					$request['run']   = (string) $run['id'];
					$request['stage'] = 'run';
					break;
				}
			}
			if ( 'uncertain' === $request['stage'] ) {
				if ( count( $runs ) === 100 ) {
					++$request['page'];
				} elseif ( time() - $request['startedAt'] >= 120 ) {
								$request['stage'] = 'dispatch';
								$request['page']  = 1;
				}
			}
		}
		$request['nextAt'] = time() + $remote['delay'];
		blooikos_core_rebuild_save_request( $request, $lease, $remote['code'] );
		return;
	}
	$remote = blooikos_core_rebuild_http( 'actions/runs/' . $request['run'] );
	if ( $remote['code'] || 200 !== $remote['status'] ) {
		$request['nextAt'] = time() + $remote['delay'];
		blooikos_core_rebuild_save_request( $request, $lease, $remote['code'] ? $remote['code'] : 'INVALID_REMOTE_RESPONSE' );
		return;
	}
	$run = $remote['data'];
	if ( ! is_array( $run ) || ! blooikos_core_rebuild_run_valid( $run ) || (string) $run['id'] !== $request['run'] || ( 'editorial' === $request['source'] && ( 'workflow_dispatch' !== $run['event'] || ( $run['display_title'] ?? '' ) !== 'Blooikos content ' . $request['id'] ) ) ) {
		$request['stage'] = 'failed';
		blooikos_core_rebuild_save_request( $request, $lease, 'RUN_PROVENANCE_MISMATCH' );
		return;
	}
	if ( $request['attempt'] && $request['attempt'] !== (string) $run['run_attempt'] ) {
		$request['stage'] = 'failed';
		blooikos_core_rebuild_save_request( $request, $lease, 'RUN_ATTEMPT_CHANGED' );
		return;
	}
	$request['attempt'] = (string) $run['run_attempt'];
	$request['sha']     = $run['head_sha'];
	$request['nextAt']  = time() + 60;
	if ( 'completed' !== $run['status'] ) {
		blooikos_core_rebuild_save_request( $request, $lease, time() - $request['startedAt'] > 3600 ? 'RUN_DELAYED' : '' );
		return;
	}
	$jobs_response = blooikos_core_rebuild_http( 'actions/runs/' . $request['run'] . '/attempts/' . $request['attempt'] . '/jobs?per_page=100' );
	if ( 200 !== $jobs_response['status'] || ! is_array( $jobs_response['data']['jobs'] ?? null ) || ( $jobs_response['data']['total_count'] ?? 101 ) > 100 ) {
		$request['nextAt'] = time() + $jobs_response['delay'];
		blooikos_core_rebuild_save_request( $request, $lease, $jobs_response['code'] ? $jobs_response['code'] : 'JOBS_UNVERIFIED' );
		return;
	}
	$publish   = false;
	$editorial = false;
	foreach ( $jobs_response['data']['jobs'] as $job ) {
		if ( (string) ( $job['run_id'] ?? '' ) !== $request['run'] || (string) ( $job['run_attempt'] ?? '' ) !== $request['attempt'] || ( $job['head_sha'] ?? '' ) !== $request['sha'] ) {
			continue;
		}
		if ( 'publish' === $job['name'] && 'success' === $job['conclusion'] ) {
			$publish = true;
		}
		if ( 'build' === $job['name'] ) {
			foreach ( $job['steps'] ?? array() as $step ) {
				if ( in_array( $step['name'], array( 'Editorial content rejected', 'Validate CMS content' ), true ) && 'failure' === $step['conclusion'] ) {
							$editorial = true;
				}
			}
		}
	}

	$marker = blooikos_core_rebuild_http( 'marker' );
	if ( ( $marker['paused'] ?? false ) && 'baseline' !== $request['source'] ) {
		blooikos_core_rebuild_save_request( $request, $lease, 'PUBLISHER_PAUSED' );
		return;
	}
	$data = $marker['data'];
	if ( 200 === $marker['status'] && blooikos_core_rebuild_marker_matches( $data, $request ) ) {
		if ( 'success' === $run['conclusion'] && $publish && $data['runId'] === $request['run'] && $data['attempt'] === $request['attempt'] && $data['commitSHA'] === $request['sha'] && ( 'editorial' !== $request['source'] || ( $data['requestId'] ?? null ) === $request['id'] ) ) {
			blooikos_core_rebuild_acknowledge( $request, $data, $lease );
			return;
		}
		if ( $data['runId'] !== $request['run'] ) {
			// Push/manual pode ter publicado enquanto este pedido estava na fila.
			// O próximo tick ainda precisa provar run, attempt e job desta outra execução.
			$request['source']  = 'observed';
			$request['run']     = $data['runId'];
			$request['attempt'] = $data['attempt'];
			$request['stage']   = 'run';
			blooikos_core_rebuild_save_request( $request, $lease, 'PUBLICATION_UNVERIFIED' );
			return;
		}
	}
	if ( 'success' === $run['conclusion'] && $publish ) {
		$request['nextAt'] = time() + $marker['delay'];
		blooikos_core_rebuild_save_request( $request, $lease, 'PUBLICATION_UNVERIFIED' );
		return;
	}

	if ( ! $editorial && $request['executions'] < 2 ) {
		++$request['executions'];
		$request['run']      = '';
		$request['attempt']  = '';
		$request['stage']    = 'dispatch';
		$request['delivery'] = 0;
		$request['nextAt']   = blooikos_core_rebuild_retry_at( $request['executions'] );
	} else {
		$request['stage'] = 'failed';
	}
	blooikos_core_rebuild_save_request( $request, $lease, $editorial ? 'EDITORIAL_BUILD_FAILED' : 'PUBLICATION_FAILED' );
}
/**
 * Worker limitado, sem HTTP no salvamento e com posse verificada em toda escrita.
 *
 * @return void
 */
function blooikos_core_rebuild_tick() {
	$GLOBALS['blooikos_core_rebuild_calls']    = 0;
	$GLOBALS['blooikos_core_rebuild_deadline'] = microtime( true ) + 45;
	$lease                                     = blooikos_core_rebuild_acquire();
	if ( ! $lease ) {
		return;
	}
	try {
		blooikos_core_rebuild_consolidate( $lease );
		$state = blooikos_core_rebuild_state();
		if ( blooikos_core_rebuild_enabled() && ! empty( $state['adoptPublished'] ) ) {
			if ( $state['request'] && 'baseline' === $state['request']['source'] ) {
				blooikos_core_rebuild_process( $state['request'], $lease );
				return;
			}
			$remote  = blooikos_core_rebuild_http( 'marker' );
			$marker  = $remote['data'];
			$request = array(
				'id'         => wp_generate_uuid4(),
				'epoch'      => $marker['content']['epoch'] ?? '',
				'version'    => $marker['content']['contentVersion'] ?? '',
				'digest'     => $marker['content']['contentDigest'] ?? '',
				'stage'      => 'run',
				'delivery'   => 0,
				'executions' => 2,
				'nextAt'     => time(),
				'startedAt'  => time(),
				'run'        => $marker['runId'] ?? '',
				'attempt'    => $marker['attempt'] ?? '',
				'source'     => 'baseline',
				'page'       => 1,
			);
			if ( 200 === $remote['status'] && blooikos_core_rebuild_uuid( $request['epoch'] ) && blooikos_core_rebuild_decimal( $request['version'] ) && blooikos_core_rebuild_marker_matches( $marker, $request ) ) {
				blooikos_core_rebuild_save_request( $request, $lease );
			}
			return;
		}

		if ( blooikos_core_rebuild_enabled() && ! empty( $state['reconcileRequested'] ) ) {
			$remote = blooikos_core_rebuild_http( 'marker' );
			if ( 200 === $remote['status'] && ! ( $remote['paused'] ?? false ) ) {
				blooikos_core_rebuild_change(
					static function ( &$current ) {
						$current['reconcileRequested'] = false;
						if ( 'PUBLISHER_PAUSED' === $current['pauseReason'] ) {
							$current['pauseReason'] = '';
						}
					},
					$lease
				);
			}
			return;
		}
		if ( ! blooikos_core_rebuild_enabled() || $state['pauseReason'] || $state['blocked'] ) {
			return;
		}
		if ( $state['request'] ) {
			$request = $state['request'];
			if ( 'failed' === $request['stage'] && $request['version'] !== $state['contentVersion'] && ! empty( $state['projectionValid'] ) ) {
				blooikos_core_rebuild_save_request( null, $lease );
			} else {
							blooikos_core_rebuild_process( $request, $lease );
			}
				return;
		}
		if ( empty( $state['projectionValid'] ) || $state['dirtySequence'] !== $state['processedSequence'] || '0' === $state['contentVersion'] || blooikos_core_rebuild_writing() ) {
			return;
		}
		if ( $state['published'] && $state['published']['epoch'] === $state['epoch'] && $state['published']['version'] === $state['contentVersion'] && $state['published']['digest'] === $state['contentDigest'] ) {
			return;
		}
		blooikos_core_rebuild_start( $state, $lease );
	} finally {
		blooikos_core_rebuild_change(
			static function ( &$state ) {
				$state['lease'] = null;
			},
			$lease
		);
	}
}
