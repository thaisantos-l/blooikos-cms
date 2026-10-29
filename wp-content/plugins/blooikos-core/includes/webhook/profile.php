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
 * Configuração privada; instalações existentes permanecem em poc.
 *
 * @return string
 * @throws RuntimeException Perfil fora da allowlist.
 */
function blooikos_core_rebuild_profile() {
	$profile = defined( 'BLOOIKOS_REBUILD_PROFILE' ) ? BLOOIKOS_REBUILD_PROFILE : 'poc';
	if ( ! in_array( $profile, array( 'poc', 'home-hero' ), true ) ) {
		throw new RuntimeException( 'INVALID_REBUILD_PROFILE' );
	}
	return $profile;
}

/**
 * Troca de configuração torna a fotografia pendente, sem reetiquetá-la.
 *
 * @return void
 */
function blooikos_core_rebuild_sync_profile() {
	$profile = blooikos_core_rebuild_profile();
	$state   = blooikos_core_rebuild_state();
	if ( ( $state['requestedProfile'] ?? 'poc' ) === $profile ) {
		return;
	}
	blooikos_core_rebuild_change(
		static function ( &$current ) use ( $profile ) {
			if ( ( $current['requestedProfile'] ?? 'poc' ) === $profile ) {
				return;
			}
			if ( $current['dirtySequence'] === $current['processedSequence'] ) {
				$current['firstPendingAt'] = time();
			}
			$current['dirtySequence']    = blooikos_core_rebuild_increment( $current['dirtySequence'] );
			$current['lastChangedAt']    = time();
			$current['requestedProfile'] = $profile;
			$current['projectionValid']  = false;
		}
	);
}

/**
 * Somente os oito campos e suas referências ACF da Home inicial.
 *
 * @return array
 */
function blooikos_core_rebuild_hero_fields() {
	return array_map(
		static function ( $name ) {
			return 'blooikos_home_hero_' . $name;
		},
		array( 'eyebrow', 'title_before', 'title_highlight', 'title_after', 'description', 'background', 'primary_cta', 'secondary_cta' )
	);
}

/**
 * Reusa regras editoriais do Hero e chrome, sem exigir seções futuras.
 *
 * @param array $response Envelope anônimo.
 * @return array
 */
function blooikos_core_rebuild_validate_hero( $response ) {
	$result = blooikos_core_graphql_validate_response( $response, 'poc' );
	$issues = $result['issues'];
	$home   = $response['data']['home'] ?? null;
	if ( ! is_array( $home ) || 'Page' !== ( $home['__typename'] ?? '' ) || true !== ( $home['isFrontPage'] ?? false ) || ! is_string( $home['id'] ?? null ) || '' === trim( $home['id'] ) ) {
		blooikos_core_graphql_issue( $issues, 'MISSING_REQUIRED_CONTENT', 'data.home' );
	}
	blooikos_core_graphql_validate_value(
		$home['blooikosHomeHero'] ?? null,
		array(
			'kind'     => 'object',
			'type'     => 'Hero',
			'required' => true,
		),
		'data.home.blooikosHomeHero',
		$issues
	);
	return array(
		'valid'  => ! $issues,
		'issues' => $issues,
	);
}
