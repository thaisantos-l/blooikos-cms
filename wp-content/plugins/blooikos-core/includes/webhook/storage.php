<?php
/**
 * Publicação assíncrona do frontend Blooikos.
 *
 * @package BlooikosCore
 */

// phpcs:disable WordPress.DB.DirectDatabaseQuery -- Fila transacional própria; cache de objeto quebraria CAS e detecção de escritores.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Nome prefixado da tabela própria.
 *
 * @return string
 */
function blooikos_core_rebuild_table() {
	global $wpdb;
	return $wpdb->prefix . 'blooikos_rebuild';
}

/**
 * Incremento decimal sem conversão para float.
 *
 * @param string $value Contador.
 * @return string
 */
function blooikos_core_rebuild_increment( $value ) {
	for ( $i = strlen( $value ) - 1; $i >= 0; --$i ) {
		if ( '9' !== $value[ $i ] ) {
			$value[ $i ] = (string) ( (int) $value[ $i ] + 1 );
			return $value;
		}
		$value[ $i ] = '0';
	}
	return '1' . $value;
}

/**
 * Estado inicial, nunca contém conteúdo ou credencial.
 *
 * @return array
 */
function blooikos_core_rebuild_initial() {
	return array(
		'schemaVersion'     => 1,
		'profile'           => 'poc',
		'requestedProfile'  => 'poc',
		'projectionValid'   => false,
		'epoch'             => wp_generate_uuid4(),
		'dirtySequence'     => '1',
		'processedSequence' => '0',
		'contentVersion'    => '0',
		'mediaRevision'     => '0',
		'firstPendingAt'    => time(),
		'lastChangedAt'     => time(),
		'contentDigest'     => '',
		'dataDigest'        => '',
		'operationDigest'   => '',
		'lease'             => null,
		'fence'             => '0',
		'request'           => null,
		'published'         => null,
		'pauseReason'       => '',
		'blocked'           => '',
		'lastError'         => '',
		'lastTickAt'        => 0,
		'logs'              => array(),
		'reconciled'        => false,
	);
}

/**
 * Upgrade idempotente, inclusive com plugin já ativo.
 *
 * @return bool
 */
function blooikos_core_rebuild_migrate() {
	global $wpdb;
	if ( is_multisite() ) {
		return false;
	}
	if ( '1' !== get_option( 'blooikos_rebuild_schema' ) ) {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table   = blooikos_core_rebuild_table();
		$collate = $wpdb->get_charset_collate();
		dbDelta( "CREATE TABLE $table (\n row_key varchar(64) NOT NULL,\n body longtext NOT NULL,\n PRIMARY KEY  (row_key)\n) ENGINE=InnoDB $collate;" );
		$result = $wpdb->query( $wpdb->prepare( 'INSERT IGNORE INTO %i (row_key,body) VALUES (%s,%s)', $table, 'state', wp_json_encode( blooikos_core_rebuild_initial() ) ) );
		if ( false === $result ) {
			return false;
		}
		$engine = $wpdb->get_row( $wpdb->prepare( 'SHOW TABLE STATUS LIKE %s', $wpdb->esc_like( $table ) ), ARRAY_A );
		if ( ! $engine || 'InnoDB' !== $engine['Engine'] ) {
			return false;
		}
		update_option( 'blooikos_rebuild_schema', '1', false );
	}
	return is_array( blooikos_core_rebuild_state() );
}

/**
 * Leitura pequena, sem cache de objeto e sem efeitos.
 *
 * @return array|null
 */
function blooikos_core_rebuild_state() {
	global $wpdb;
	$body = $wpdb->get_var( $wpdb->prepare( 'SELECT body FROM %i WHERE row_key=%s', blooikos_core_rebuild_table(), 'state' ) );
	return $body ? json_decode( $body, true ) : null;
}

/** Transação curta: serializa somente a atualização local, nunca rede.
 *
 * @param callable   $callback Recebe estado por referência.
 * @param array|null $lease Posse exigida para worker.
 * @return mixed
 * @throws RuntimeException Falha ou perda de posse.
 * @throws Throwable Erro propagado depois do rollback.
 */
function blooikos_core_rebuild_change( $callback, $lease = null ) {
	global $wpdb;
	$wpdb->query( 'START TRANSACTION' );
	try {
		$raw   = $wpdb->get_var( $wpdb->prepare( 'SELECT body FROM %i WHERE row_key=%s FOR UPDATE', blooikos_core_rebuild_table(), 'state' ) );
		$state = json_decode( (string) $raw, true );
		if ( ! is_array( $state ) ) {
			throw new RuntimeException( 'STORAGE_UNAVAILABLE' );
		}
		if ( $lease && ( $state['lease']['owner'] !== $lease['owner'] || $state['lease']['fence'] !== $lease['fence'] || $state['lease']['expiresAt'] <= time() ) ) {
			throw new RuntimeException( 'LEASE_LOST' );
		}
		$result        = $callback( $state );
		$state['logs'] = array_slice(
			array_values(
				array_filter(
					$state['logs'],
					static function ( $event ) {
						return $event['at'] >= time() - 30 * DAY_IN_SECONDS;
					}
				)
			),
			-100
		);
		if ( false === $wpdb->query( $wpdb->prepare( 'UPDATE %i SET body=%s WHERE row_key=%s', blooikos_core_rebuild_table(), wp_json_encode( $state ), 'state' ) ) ) {
			throw new RuntimeException( 'STORAGE_UNAVAILABLE' );
		}
		$wpdb->query( 'COMMIT' );
		return $result;
	} catch ( Throwable $error ) {
		$wpdb->query( 'ROLLBACK' );
		throw $error;
	}
}

/**
 * Adquire lease com fencing monotônico.
 *
 * @return array|null
 */
function blooikos_core_rebuild_acquire() {
	return blooikos_core_rebuild_change(
		static function ( &$state ) {
			if ( $state['lease'] && $state['lease']['expiresAt'] > time() ) {
				return null;
			}
			$state['fence']      = blooikos_core_rebuild_increment( $state['fence'] );
			$state['lease']      = array(
				'owner'     => wp_generate_uuid4(),
				'fence'     => $state['fence'],
				'expiresAt' => time() + 90,
			);
			$state['lastTickAt'] = time();
			return $state['lease'];
		}
	);
}

/** Marca pequena antes da primeira escrita; lock da conexão detecta processo morto.
 * O lock não bloqueia outros editores. Não expira enquanto a conexão estiver viva.
 *
 * @param bool $media Alteração de arquivo referenciado.
 * @return void
 * @throws RuntimeException Código seguro de diagnóstico.
 */
function blooikos_core_rebuild_begin( $media = false ) {
	global $wpdb;
	try {
		if ( empty( $GLOBALS['blooikos_core_rebuild_writer'] ) ) {
			$id   = wp_generate_uuid4();
			$lock = 'blooikos-' . hash( 'sha256', DB_NAME . blooikos_core_rebuild_table() . $id );
			$lock = substr( $lock, 0, 64 );
			if ( '1' !== (string) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s,0)', $lock ) ) ) {
				throw new RuntimeException( 'WRITER_LOCK_FAILED' );
			}
			$GLOBALS['blooikos_core_rebuild_writer'] = array(
				'id'   => $id,
				'lock' => $lock,
			);
			blooikos_core_rebuild_change(
				static function ( &$state ) use ( $id, $lock, $wpdb ) {
					if ( false === $wpdb->query(
						$wpdb->prepare(
							'INSERT INTO %i (row_key,body) VALUES (%s,%s)',
							blooikos_core_rebuild_table(),
							'writer:' . $id,
							wp_json_encode(
								array(
									'lock'      => $lock,
									'startedAt' => time(),
								)
							)
						)
					) ) {
						throw new RuntimeException( 'STORAGE_UNAVAILABLE' );
					}
					if ( $state['dirtySequence'] === $state['processedSequence'] ) {
						$state['firstPendingAt'] = time();
					}
					$state['dirtySequence'] = blooikos_core_rebuild_increment( $state['dirtySequence'] );
					$state['lastChangedAt'] = time();
				}
			);
		}
		if ( $media && empty( $GLOBALS['blooikos_core_rebuild_media_marked'] ) ) {
			$GLOBALS['blooikos_core_rebuild_media_marked'] = true;
			blooikos_core_rebuild_change(
				static function ( &$state ) {
					$state['mediaSequence'] = blooikos_core_rebuild_increment( $state['mediaSequence'] ?? '0' );
				}
			);
		}
	} catch ( Throwable $error ) {
		$GLOBALS['blooikos_core_rebuild_fault'] = 'STORAGE_UNAVAILABLE';
	}
}

/**
 * Fecha mutação somente no fim da requisição; múltiplos campos são indivisíveis.
 *
 * @return void
 */
function blooikos_core_rebuild_finish() {
	global $wpdb;
	$writer = $GLOBALS['blooikos_core_rebuild_writer'] ?? null;
	if ( ! $writer ) {
		return;
	}
	try {
		blooikos_core_rebuild_change(
			static function ( &$state ) use ( $writer, $wpdb ) {
				$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE row_key=%s', blooikos_core_rebuild_table(), 'writer:' . $writer['id'] ) );
				$state['dirtySequence'] = blooikos_core_rebuild_increment( $state['dirtySequence'] );
				$state['lastChangedAt'] = time();
			}
		);
	} catch ( Throwable $error ) {
		$GLOBALS['blooikos_core_rebuild_fault'] = 'STORAGE_UNAVAILABLE';
	}
	$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $writer['lock'] ) );
	unset( $GLOBALS['blooikos_core_rebuild_writer'], $GLOBALS['blooikos_core_rebuild_media_marked'] );
}

/** Worker recupera somente locks cuja conexão morreu, sem timeout de editor.
 *
 * @param bool $recover Remove órfãos; endpoint apenas lê.
 * @return bool
 */
function blooikos_core_rebuild_writing( $recover = false ) {
	global $wpdb;
	$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT row_key,body FROM %i WHERE row_key LIKE %s LIMIT 101', blooikos_core_rebuild_table(), 'writer:%' ), ARRAY_A );
	if ( count( $rows ) > 100 && ! $recover ) {
		return true;
	}
	foreach ( $rows as $row ) {
		$writer = json_decode( $row['body'], true );
		if ( ! $recover || $wpdb->get_var( $wpdb->prepare( 'SELECT IS_USED_LOCK(%s)', $writer['lock'] ) ) ) {
			return true;
		}
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE row_key=%s', blooikos_core_rebuild_table(), $row['row_key'] ) );
	}
	return false;
}
