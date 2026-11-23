<?php
/**
 * Company-owned social links, independent of the frozen GraphQL v1 snapshot.
 *
 * @package BlooikosCore
 */

/** Validate the optional Instagram destination without inventing a profile.
 *
 * @param mixed $value Stored URL.
 * @return string Empty for an unusable destination.
 */
function blooikos_core_footer_instagram_url( $value ) {
	if ( ! is_string( $value ) || strlen( $value ) > 2048 || preg_match( '/[\s\\\\\x00-\x1f\x7f]/', $value ) || ! filter_var( $value, FILTER_VALIDATE_URL ) ) {
		return '';
	}
	$parts = wp_parse_url( $value );
	if ( ! $parts || 'https' !== ( $parts['scheme'] ?? '' ) || ! in_array( strtolower( $parts['host'] ?? '' ), array( 'instagram.com', 'www.instagram.com' ), true ) || isset( $parts['user'] ) || isset( $parts['pass'] ) || ( isset( $parts['port'] ) && 443 !== $parts['port'] ) ) {
		return '';
	}
	return $value;
}

/** Validate an explicitly entered account label.
 *
 * @param mixed $value Stored handle.
 * @return string Empty for an unusable label.
 */
function blooikos_core_footer_instagram_handle( $value ) {
	return is_string( $value ) && preg_match( '/^@[a-zA-Z0-9._]{1,30}$/D', $value ) ? $value : '';
}

/** Read the selected contact company's own Instagram metadata.
 *
 * @return array|null No fallback to another company or shared profile.
 */
function blooikos_core_footer_contact_instagram() {
	$id = get_option( 'options_blooikos_site_contact_company' );
	if ( ! is_scalar( $id ) || ! ctype_digit( (string) $id ) || (int) $id < 1 ) {
		return null;
	}
	$company = get_post( (int) $id );
	if ( ! $company || 'blooikos_company' !== $company->post_type || 'publish' !== $company->post_status || '' !== $company->post_password ) {
		return null;
	}
	$url   = blooikos_core_footer_instagram_url( get_post_meta( (int) $id, 'blooikos_company_instagram_url', true ) );
	$label = blooikos_core_footer_instagram_handle( get_post_meta( (int) $id, 'blooikos_company_instagram_handle', true ) );
	return $url && $label ? array(
		'companyId' => (string) (int) $id,
		'label'     => $label,
		'href'      => $url,
	) : null;
}

/** Read published companies with their own Instagram, independently of footer selection.
 *
 * @param WP_REST_Request $request Public request.
 * @return WP_REST_Response
 */
function blooikos_core_footer_social_get( $request ) {
	global $wpdb;
	try {
		if ( blooikos_core_get_dependency_issues() ) {
			return blooikos_core_content_error( 'FOOTER_SOCIAL_UNAVAILABLE', 503 );
		}
		$selection = get_option( 'options_blooikos_site_footer_companies', array() );
		// Footer selection can order links, but never gates a filled company profile.
		$available = get_posts(
			array(
				'post_type'      => 'blooikos_company',
				'post_status'    => 'publish',
				'has_password'   => false,
				'posts_per_page' => 100,
				'fields'         => 'ids',
				'orderby'        => array(
					'title' => 'ASC',
					'ID'    => 'ASC',
				),
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Bounded optional social metadata for a small company catalogue.
					array(
						'key'     => 'blooikos_company_instagram_url',
						'value'   => '',
						'compare' => '!=',
					),
				),
			)
		);
		$ordered   = array_merge( is_array( $selection ) ? array_slice( $selection, 0, 6 ) : array(), $available );
		$companies = array();
		$seen      = array();
		foreach ( $ordered as $id ) {
			if ( ! is_scalar( $id ) || ! ctype_digit( (string) $id ) || (int) $id < 1 || isset( $seen[ (int) $id ] ) ) {
				continue;
			}
			$id          = (int) $id;
			$seen[ $id ] = true;
			$company     = get_post( $id );
			// Companies intentionally have no public WP route; publication governs API visibility.
			if ( ! $company || 'blooikos_company' !== $company->post_type || 'publish' !== $company->post_status || '' !== $company->post_password ) {
				continue;
			}
			$url  = blooikos_core_footer_instagram_url( get_post_meta( $id, 'blooikos_company_instagram_url', true ) );
			$name = get_post_meta( $id, 'blooikos_company_short_name', true );
			$name = is_string( $name ) ? trim( sanitize_text_field( $name ) ) : '';
			if ( '' !== $url && '' !== $name ) {
				$companies[] = array(
					'id'           => (string) $id,
					'name'         => $name,
					'instagramUrl' => $url,
				);
				if ( 6 === count( $companies ) ) {
					break;
				}
			}
		}
		$data = array(
			'schemaVersion'    => 1,
			'companies'        => $companies,
			'contactInstagram' => blooikos_core_footer_contact_instagram(),
		);
		$json = wp_json_encode( $data );
		if ( $wpdb->last_error || false === $json || strlen( $json ) > 32768 ) {
			return blooikos_core_content_error( 'FOOTER_SOCIAL_UNAVAILABLE', 503 );
		}
		$etag    = '"' . hash( 'sha256', $json ) . '"';
		$matches = array_map( 'trim', explode( ',', (string) $request->get_header( 'if-none-match' ) ) );
		$matched = in_array( $etag, $matches, true ) || in_array( 'W/' . $etag, $matches, true ) || in_array( '*', $matches, true );
		$result  = new WP_REST_Response( $matched ? null : $data, $matched ? 304 : 200 );
		$result->header( 'ETag', $etag );
		$result->header( 'Cache-Control', 'public, max-age=0, must-revalidate' );
		return $result;
	} catch ( Throwable $error ) {
		return blooikos_core_content_error( 'FOOTER_SOCIAL_UNAVAILABLE', 503 );
	}
}

/** Register the additive, read-only REST contract.
 *
 * @return void
 */
function blooikos_core_footer_social_routes() {
	register_rest_route(
		'blooikos/v1',
		'/footer-social',
		array(
			'methods'             => 'GET, HEAD',
			'callback'            => 'blooikos_core_footer_social_get',
			'permission_callback' => 'blooikos_core_content_permission',
		)
	);
}
add_action( 'rest_api_init', 'blooikos_core_footer_social_routes' );
