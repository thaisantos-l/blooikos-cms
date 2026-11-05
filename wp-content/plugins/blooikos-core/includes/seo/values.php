<?php
/**
 * Public SEO projection, independent of editorial content revisions.
 *
 * @package BlooikosCore
 */

/** Normalize resolved text, never HTML or unresolved Yoast templates.
 *
 * @param mixed $value Source value.
 * @return string
 */
function blooikos_core_seo_text( $value ) {
	if ( ! is_string( $value ) || preg_match( '/%%[^%]+%%/', $value ) ) {
		return '';
	}
	return mb_substr( trim( wp_strip_all_tags( html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) ), 0, 2000 );
}

/** Select public image properties without carrying attachment metadata.
 *
 * @param mixed $value Image from the public Yoast surface.
 * @return array|null
 */
function blooikos_core_seo_image( $value ) {
	if ( is_string( $value ) ) {
		$value = array( 'url' => $value );
	}
	if ( ! is_array( $value ) || empty( $value['url'] ) || ! is_string( $value['url'] ) ) {
		return null;
	}
	$image = array( 'url' => esc_url_raw( $value['url'], array( 'https', 'http' ) ) );
	if ( ! $image['url'] ) {
		return null;
	}
	foreach ( array( 'width', 'height' ) as $dimension ) {
		if ( isset( $value[ $dimension ] ) && is_numeric( $value[ $dimension ] ) && $value[ $dimension ] > 0 ) {
			$image[ $dimension ] = (int) $value[ $dimension ];
		}
	}
	$image['alt'] = blooikos_core_seo_text( $value['alt'] ?? '' );
	return $image;
}

/** Only the configured site representative, never an incidental post author.
 *
 * @param object $meta Public Yoast metadata surface.
 * @return array|null
 */
function blooikos_core_seo_entity( $meta ) {
	$reference = $meta->site_represents_reference;
	$type      = 'company' === $meta->site_represents ? 'Organization' : ( 'person' === $meta->site_represents ? 'Person' : '' );
	if ( ! $type || ! is_array( $reference ) || empty( $reference['@id'] ) ) {
		return null;
	}
	foreach ( (array) $meta->schema as $node ) {
		if ( ! is_array( $node ) || ( $node['@id'] ?? '' ) !== $reference['@id'] || ! in_array( $type, (array) ( $node['@type'] ?? array() ), true ) ) {
			continue;
		}
		$name = blooikos_core_seo_text( $node['name'] ?? '' );
		if ( ! $name ) {
			return null;
		}
		$logo = $node['logo'] ?? array();
		return array(
			'type'    => $type,
			'name'    => $name,
			'logoUrl' => is_array( $logo ) ? esc_url_raw( $logo['contentUrl'] ?? $logo['url'] ?? '', array( 'https', 'http' ) ) : '',
			'sameAs'  => array_values( array_slice( array_filter( (array) ( $node['sameAs'] ?? array() ), 'is_string' ), 0, 10 ) ),
		);
	}
	return null;
}

/** Read native settings and the published front page using Yoast's public API.
 * No HTTP, indexation jobs, options writes or content-version changes.
 *
 * @return array
 * @throws RuntimeException Safe infrastructure failure.
 */
function blooikos_core_seo_read() {
	global $wpdb;
	$home_id   = (int) get_option( 'page_on_front' );
	$page      = 'page' === get_option( 'show_on_front' ) && $home_id > 0 ? get_post( $home_id ) : null;
	$published = $page && 'page' === $page->post_type && 'publish' === $page->post_status && '' === $page->post_password;
	$meta      = $published && function_exists( 'YoastSEO' ) ? YoastSEO()->meta->for_post( $page->ID ) : null;
	$custom    = $meta ? get_post_meta( $page->ID, '_yoast_wpseo_canonical', true ) : '';
	$robots    = $meta ? (array) $meta->robots : array();
	$images    = $meta ? (array) $meta->open_graph_images : array();
	$brand     = get_option( 'options_blooikos_site_brand_name', '' );
	$name      = blooikos_core_seo_text( get_option( 'blogname', '' ) );
	$fallback  = '' !== $name ? $name : blooikos_core_seo_text( $brand );
	$site_name = blooikos_core_seo_text( $meta ? $meta->site_name : '' );
	$title     = blooikos_core_seo_text( $meta ? $meta->title : '' );
	$result    = array(
		'schemaVersion' => 1,
		'home'          => array(
			'published' => (bool) $published,
			'id'        => $published ? (int) $page->ID : null,
		),
		'site'          => array(
			'name'        => '' !== $site_name ? $site_name : $fallback,
			'description' => blooikos_core_seo_text( get_option( 'blogdescription', '' ) ),
			'iconUrl'     => get_site_icon_url(),
			'language'    => str_replace( '_', '-', get_locale() ),
		),
		'seo'           => array(
			'title'             => '' !== $title ? $title : $fallback,
			'description'       => blooikos_core_seo_text( $meta ? $meta->description : '' ),
			'canonical'         => is_string( $custom ) && '' !== trim( $custom ) ? $custom : ( $meta ? (string) $meta->canonical : '' ),
			'canonicalIsCustom' => is_string( $custom ) && '' !== trim( $custom ),
			'robots'            => array(
				'index'  => (bool) $published && '1' === (string) get_option( 'blog_public' ) && ! in_array( 'noindex', $robots, true ),
				'follow' => ! in_array( 'nofollow', $robots, true ),
			),
			'openGraph'         => array(
				'title'       => blooikos_core_seo_text( $meta ? $meta->open_graph_title : '' ),
				'description' => blooikos_core_seo_text( $meta ? $meta->open_graph_description : '' ),
				'image'       => blooikos_core_seo_image( reset( $images ) ),
			),
			'twitter'           => array(
				'title'       => blooikos_core_seo_text( $meta ? $meta->twitter_title : '' ),
				'description' => blooikos_core_seo_text( $meta ? $meta->twitter_description : '' ),
				'image'       => blooikos_core_seo_image( $meta ? $meta->twitter_image : null ),
			),
			'entity'            => $meta ? blooikos_core_seo_entity( $meta ) : null,
		),
	);
	if ( $wpdb->last_error ) {
		throw new RuntimeException( 'SEO_UNAVAILABLE' );
	}
	return $result;
}

/** Resolve lazy Yoast surfaces without persisting on-demand indexables.
 * The public API still resolves values; these request-local filters only prevent saves.
 *
 * @return array
 */
function blooikos_core_seo_payload() {
	$prevent_save = static function () {
		return false;
	};
	add_filter( 'Yoast\WP\SEO\should_index_indexables', $prevent_save, PHP_INT_MAX );
	add_filter( 'wpseo_should_save_indexable', $prevent_save, PHP_INT_MAX );
	try {
		return blooikos_core_seo_read();
	} finally {
		remove_filter( 'Yoast\WP\SEO\should_index_indexables', $prevent_save, PHP_INT_MAX );
		remove_filter( 'wpseo_should_save_indexable', $prevent_save, PHP_INT_MAX );
	}
}
