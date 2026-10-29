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
 * Lista fechada de opções usadas pela operação poc.
 *
 * @return array
 */
function blooikos_core_rebuild_options() {
	return array( 'blooikos_site_brand_name', 'blooikos_site_logo', 'blooikos_site_logo_inverse', 'blooikos_site_contact_company', 'blooikos_site_phone', 'blooikos_site_email', 'blooikos_site_instagram_url', 'blooikos_site_address', 'blooikos_site_footer_description', 'blooikos_site_footer_companies', 'blooikos_site_copyright_holder' );
}
/**
 * Dependências pequenas da POC, incluindo referências que ficaram indisponíveis.
 *
 * @return array
 */
function blooikos_core_rebuild_dependencies() {
	$deps         = array(
		'companies' => array_map( 'intval', array_merge( (array) get_option( 'options_blooikos_site_footer_companies', array() ), (array) get_option( 'options_blooikos_site_contact_company', array() ) ) ),
		'media'     => array_map( 'intval', array( get_option( 'options_blooikos_site_logo' ), get_option( 'options_blooikos_site_logo_inverse' ) ) ),
	);
	$deps['home'] = 'home-hero' === blooikos_core_rebuild_profile() && 'page' === get_option( 'show_on_front' ) ? (int) get_option( 'page_on_front' ) : 0;
	if ( $deps['home'] ) {
		$deps['media'][] = (int) get_post_meta( $deps['home'], 'blooikos_home_hero_background', true );
	}
	return $deps;
}
/**
 * Opção relevante; referências ACF não ampliam a lista de campos.
 *
 * @param string $name Nome.
 * @return bool
 */
function blooikos_core_rebuild_option_relevant( $name ) {
	return in_array( preg_replace( '/^_?options_/', '', $name ), blooikos_core_rebuild_options(), true ) || ( 'home-hero' === blooikos_core_rebuild_profile() && in_array( $name, array( 'page_on_front', 'show_on_front' ), true ) );
}
/**
 * Filtro antes da persistência.
 *
 * @param mixed  $value Novo.
 * @param string $name Opção.
 * @param mixed  $old Anterior.
 * @return mixed
 */
function blooikos_core_rebuild_option( $value, $name, $old ) {
	$location_change = false;
	if ( 'theme_mods_' . get_option( 'stylesheet' ) === $name ) {
		foreach ( array( 'blooikos_primary', 'blooikos_footer' ) as $location ) {
			if ( ( $value['nav_menu_locations'][ $location ] ?? null ) !== ( $old['nav_menu_locations'][ $location ] ?? null ) ) {
				$location_change = true;
			}
		}
	}
	if ( $value !== $old && ( blooikos_core_rebuild_option_relevant( $name ) || $location_change ) ) {
		blooikos_core_rebuild_begin();
	}
	return $value;
}
/**
 * Antes de adicionar/excluir opção.
 *
 * @param string $name Opção.
 * @return void
 */
function blooikos_core_rebuild_option_lifecycle( $name ) {
	if ( blooikos_core_rebuild_option_relevant( $name ) ) {
		blooikos_core_rebuild_begin();
	} }

/** Post dependente, sem revisões/autosaves e sem exigir visibilidade após exclusão.
 *
 * @param int $id Post.
 * @return bool
 */
function blooikos_core_rebuild_post_relevant( $id ) {
	$deps = blooikos_core_rebuild_dependencies();
	if ( wp_is_post_revision( $id ) || wp_is_post_autosave( $id ) ) {
		return false;
	}
	return ( $deps['home'] && (int) $id === $deps['home'] ) || in_array( (int) $id, $deps['companies'], true ) || in_array( (int) $id, $deps['media'], true ) || 'nav_menu_item' === get_post_type( $id );
}
/** Metas consumidas; candidatos sem efeito são eliminados na projeção.
 *
 * @param mixed  $check Resultado de outro filtro.
 * @param int    $id Post.
 * @param string $key Meta.
 * @return mixed
 */
function blooikos_core_rebuild_meta( $check, $id, $key ) {
	$keys = array( 'blooikos_company_short_name', 'blooikos_company_cta', '_blooikos_company_short_name', '_blooikos_company_cta', '_wp_attachment_image_alt', '_wp_attachment_metadata', '_wp_attached_file', '_menu_item_type', '_menu_item_menu_item_parent', '_menu_item_object_id', '_menu_item_object', '_menu_item_target', '_menu_item_url' );
	if ( 'home-hero' === blooikos_core_rebuild_profile() && blooikos_core_rebuild_dependencies()['home'] === (int) $id ) {
		$keys = array_merge(
			$keys,
			blooikos_core_rebuild_hero_fields(),
			array_map(
				static function ( $field ) {
					return '_' . $field;
				},
				blooikos_core_rebuild_hero_fields()
			)
		);
	}
	if ( in_array( $key, $keys, true ) && blooikos_core_rebuild_post_relevant( $id ) ) {
		blooikos_core_rebuild_begin();
	}
	return $check;
}
/** Antes de gravar título/slug/status/senha e itens de menus.
 *
 * @param array $data Dados.
 * @param array $postarr Argumentos.
 * @return array
 */
function blooikos_core_rebuild_post( $data, $postarr ) {
	$id = (int) ( $postarr['ID'] ?? 0 );
	if ( 'nav_menu_item' === $data['post_type'] || ( $id && blooikos_core_rebuild_post_relevant( $id ) ) ) {
		$old = get_post( $id );
		foreach ( array( 'post_title', 'post_name', 'post_status', 'post_password', 'menu_order', 'post_parent', 'post_mime_type', 'guid' ) as $key ) {
			if ( ! $old || (string) ( $data[ $key ] ?? '' ) !== (string) $old->$key ) {
				blooikos_core_rebuild_begin();
				break;
			}
		}
	}
	return $data;
}
/**
 * Exclusão antes de remover referências.
 *
 * @param int $id Post.
 * @return void
 */
function blooikos_core_rebuild_delete( $id ) {
	if ( blooikos_core_rebuild_post_relevant( $id ) ) {
		blooikos_core_rebuild_begin();
	} }
/**
 * Alteração de arquivo pela API, inclusive mesma URL.
 *
 * @param mixed $value Valor.
 * @param int   $id Mídia.
 * @return mixed
 */
function blooikos_core_rebuild_media( $value, $id ) {
	if ( in_array( (int) $id, blooikos_core_rebuild_dependencies()['media'], true ) ) {
		blooikos_core_rebuild_begin( true );
	}
	return $value;
}
/**
 * Relação de menu antes de SQL.
 *
 * @param int    $id Objeto.
 * @param mixed  $terms Termos.
 * @param string $taxonomy Taxonomia.
 * @return void
 */
function blooikos_core_rebuild_terms( $id, $terms, $taxonomy ) {
	if ( 'nav_menu' === $taxonomy ) {
		blooikos_core_rebuild_begin();
	} }
/**
 * Exclusão de menu antes de SQL.
 *
 * @param int    $id Termo.
 * @param string $taxonomy Taxonomia.
 * @return void
 */
function blooikos_core_rebuild_term_delete( $id, $taxonomy ) {
	if ( 'nav_menu' === $taxonomy ) {
		blooikos_core_rebuild_begin();
	} }

add_filter( 'pre_update_option', 'blooikos_core_rebuild_option', 10, 3 );
add_action( 'add_option', 'blooikos_core_rebuild_option_lifecycle' );
add_action( 'delete_option', 'blooikos_core_rebuild_option_lifecycle' );
foreach ( array( 'add_post_metadata', 'update_post_metadata', 'delete_post_metadata' ) as $blooikos_core_hook ) {
	add_filter( $blooikos_core_hook, 'blooikos_core_rebuild_meta', 10, 3 );
}
add_filter( 'wp_insert_post_data', 'blooikos_core_rebuild_post', 10, 2 );
add_action( 'before_delete_post', 'blooikos_core_rebuild_delete' );
add_action( 'delete_attachment', 'blooikos_core_rebuild_delete' );
add_filter( 'wp_update_attachment_metadata', 'blooikos_core_rebuild_media', 10, 2 );
add_filter( 'update_attached_file', 'blooikos_core_rebuild_media', 10, 2 );
add_action( 'add_term_relationship', 'blooikos_core_rebuild_terms', 10, 3 );
add_action( 'delete_term_relationships', 'blooikos_core_rebuild_terms', 10, 3 );
add_action( 'pre_delete_term', 'blooikos_core_rebuild_term_delete', 10, 2 );
add_action( 'shutdown', 'blooikos_core_rebuild_finish', PHP_INT_MAX );

/** Marca a edição nativa antes de o WordPress gravar bytes da imagem.
 *
 * @param mixed  $override Resultado anterior do filtro.
 * @param string $filename Destino.
 * @param object $image Editor nativo.
 * @param string $mime_type MIME.
 * @param int    $post_id Anexo.
 * @return mixed
 */
function blooikos_core_rebuild_image_write( $override, $filename, $image, $mime_type, $post_id ) {
	blooikos_core_rebuild_media( null, $post_id );
	return $override;
}
add_filter( 'wp_save_image_editor_file', 'blooikos_core_rebuild_image_write', 10, 5 );
