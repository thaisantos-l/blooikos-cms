<?php
/**
 * Strict, plain-text contact input.
 *
 * @package BlooikosCore
 */

/**
 * Validate a single address without silently repairing it.
 *
 * @param mixed $value Address.
 * @return bool
 */
function blooikos_core_contact_email( $value ) {
	return is_string( $value ) && 254 >= strlen( $value ) && ! preg_match( '/[\r\n,;<>]/', $value ) && sanitize_email( $value ) === $value && (bool) is_email( $value );
}

/**
 * Validate visible fields before and after WordPress sanitization.
 *
 * @param array $values Fields.
 * @return array Field error codes.
 */
function blooikos_core_contact_field_errors( $values ) {
	$limits = array(
		'name'    => 120,
		'email'   => 254,
		'phone'   => 40,
		'company' => 120,
		'message' => 3000,
	);
	$errors = array();
	foreach ( $limits as $field => $limit ) {
		$raw    = $values[ $field ];
		$value  = trim( $raw );
		$length = preg_match_all( '/./us', $value );
		if ( '' === $value && 'company' !== $field ) {
			$errors[ $field ] = 'REQUIRED';
		} elseif ( $length > $limit ) {
			$errors[ $field ] = 'TOO_LONG';
		} elseif ( '' !== $value && ( ( 'name' === $field && $length < 2 ) || ( 'message' === $field && $length < 10 ) ) ) {
			$errors[ $field ] = 'TOO_SHORT';
		} elseif ( preg_match( '/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]|<[^>]*>/u', $value ) ) {
			$errors[ $field ] = 'INVALID';
		} elseif ( 'email' === $field && ( preg_match( '/[\r\n]/', $raw ) || ! blooikos_core_contact_email( $value ) ) ) {
			$errors[ $field ] = 'INVALID';
		} elseif ( 'phone' === $field && ( ! preg_match( '/^\+?[0-9 ()-]+$/D', $value ) || ! preg_match( '/^[0-9]{8,15}$/D', preg_replace( '/\D/', '', $value ) ) ) ) {
			$errors[ $field ] = 'INVALID';
		}
	}
	return $errors;
}

/**
 * Parse an object of strings; unknown keys cannot select mail headers.
 *
 * @param string $body Raw JSON.
 * @return array Validation result.
 */
function blooikos_core_contact_validate( $body ) {
	$input = json_decode( $body );
	if ( ! $input instanceof stdClass ) {
		return array( 'code' => 'INVALID_REQUEST' );
	}
	$values  = get_object_vars( $input );
	$allowed = array( 'name', 'email', 'phone', 'company', 'message', 'website' );
	if ( array_diff( array_keys( $values ), $allowed ) ) {
		return array( 'code' => 'INVALID_REQUEST' );
	}
	foreach ( $allowed as $field ) {
		if ( isset( $values[ $field ] ) && ! is_string( $values[ $field ] ) ) {
			return array( 'code' => 'INVALID_REQUEST' );
		}
		if ( array_key_exists( $field, $values ) && null === $values[ $field ] ) {
			return array( 'code' => 'INVALID_REQUEST' );
		}
		$values[ $field ] = $values[ $field ] ?? '';
	}
	if ( '' !== $values['website'] ) {
		return array( 'code' => 'INVALID_REQUEST' );
	}
	$errors = blooikos_core_contact_field_errors( $values );
	if ( $errors ) {
		return array(
			'code'        => 'VALIDATION_ERROR',
			'fieldErrors' => $errors,
		);
	}
	foreach ( $values as $field => $value ) {
		$values[ $field ] = 'message' === $field ? sanitize_textarea_field( $value ) : sanitize_text_field( $value );
	}
	$errors = blooikos_core_contact_field_errors( $values );
	return $errors ? array(
		'code'        => 'VALIDATION_ERROR',
		'fieldErrors' => $errors,
	) : array( 'values' => $values );
}
