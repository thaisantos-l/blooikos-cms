<?php
/**
 * Validação de formulário e sanitização dos campos próprios.
 *
 * A função update_field() não executa a validação de formulário: importadores devem
 * validar explicitamente seus dados antes da gravação.
 *
 * @package BlooikosCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Vincula callbacks somente aos onze grupos locais conhecidos.
 *
 * @return void
 */
function blooikos_core_register_validation() {
	$groups = array(
		'company',
		'home_hero',
		'home_about',
		'home_institutional',
		'home_indicators',
		'home_services',
		'home_companies',
		'home_contact',
		'site_identity',
		'site_contact',
		'site_footer',
	);
	foreach ( $groups as $group ) {
		blooikos_core_register_field_validation( 'group_blooikos_' . $group );
	}
}

/**
 * Inclui também subcampos, sem filtros globais por tipo ACF.
 *
 * @param string $parent_key Chave de grupo ou repeater local.
 * @return void
 */
function blooikos_core_register_field_validation( $parent_key ) {
	foreach ( acf_get_local_fields( $parent_key ) as $field ) {
		add_filter( 'acf/validate_value/key=' . $field['key'], 'blooikos_core_validate_field', 20, 3 );
		add_filter( 'acf/update_value/key=' . $field['key'], 'blooikos_core_sanitize_field', 20, 3 );
		if ( in_array( $field['type'], array( 'relationship', 'post_object' ), true ) ) {
			add_filter( 'acf/fields/' . $field['type'] . '/query/key=' . $field['key'], 'blooikos_core_company_query' );
		}
		if ( 'repeater' === $field['type'] ) {
			blooikos_core_register_field_validation( $field['key'] );
		}
	}
}

/**
 * Mantém o seletor limitado mesmo que receba parâmetros diferentes da UI.
 *
 * @param array $args Argumentos da consulta ACF.
 * @return array
 */
function blooikos_core_company_query( $args ) {
	$args['post_type']      = array( 'blooikos_company' );
	$args['post_status']    = array( 'publish', 'draft' );
	$args['posts_per_page'] = 20;
	return $args;
}

/**
 * Valida também rascunhos antes de o ACF persistir seus campos.
 *
 * As telas nativas verificam nonce e capacidade antes de acf/save_post.
 * O ACF pula a validação usual de rascunhos; este guard verifica somente
 * valores próprios enviados, sem exigir campos opcionais ou publicar posts.
 *
 * @return void
 */
function blooikos_core_validate_editorial_save() {
	$errors = array();
	foreach ( acf_get_local_field_groups() as $group ) {
		if ( ! str_starts_with( $group['key'], 'group_blooikos_' ) ) {
			continue;
		}
		foreach ( acf_get_local_fields( $group['key'] ) as $local_field ) {
			$key = $local_field['key'];
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verificado pela tela nativa antes deste hook.
			if ( ! isset( $_POST['acf'][ $key ] ) ) {
				continue;
			}
			$field = acf_get_field( $key );
			// O valor bruto precisa ser validado antes da sanitização na persistência.
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$value = wp_unslash( $_POST['acf'][ $key ] );
			acf_reset_validation_errors();
			acf_validate_value( $value, $field, 'acf[' . $key . ']' );
			$field_errors = acf_get_validation_errors();
			if ( ! is_array( $field_errors ) ) {
				continue;
			}
			foreach ( $field_errors as $error ) {
				$errors[] = $field['label'] . ': ' . $error['message'];
			}
		}
	}
	if ( $errors ) {
		$message = '<p>' . esc_html__( 'Os campos Blooikos não foram gravados. Volte ao formulário e corrija:', 'blooikos-core' ) . '</p><ul>';
		foreach ( array_unique( $errors ) as $error ) {
			$message .= '<li>' . esc_html( $error ) . '</li>';
		}
		$message .= '</ul>';
		wp_die(
			wp_kses_post( $message ),
			esc_html__( 'Revise os campos Blooikos', 'blooikos-core' ),
			array(
				'response'  => 400,
				'back_link' => true,
			)
		);
	}
}

/**
 * Preserva erros anteriores e verifica valores fornecidos.
 *
 * @param bool|string $valid Resultado anterior do ACF.
 * @param mixed       $value Valor recebido.
 * @param array       $field Definição local do campo.
 * @return bool|string
 */
function blooikos_core_validate_field( $valid, $value, $field ) {
	if ( true !== $valid ) {
		return $valid;
	}
	if ( '' === $value || null === $value || false === $value || array() === $value ) {
		return $valid;
	}

	switch ( $field['type'] ) {
		case 'relationship':
		case 'post_object':
			return blooikos_core_validate_companies( $value, $field );
		case 'image':
			return blooikos_core_validate_image( $value, $field );
		case 'link':
			return blooikos_core_validate_link( $value );
		case 'repeater':
			return blooikos_core_validate_rows( $value, $field );
		case 'select':
			return is_string( $value ) && isset( $field['choices'][ $value ] ) ? true : __( 'Selecione uma aparência disponível.', 'blooikos-core' );
		case 'true_false':
			return in_array( $value, array( 0, 1, '0', '1', true ), true ) ? true : __( 'Selecione sim ou não.', 'blooikos-core' );
		default:
			return blooikos_core_validate_text( $value, $field );
	}
}

/**
 * Verifica texto, telefone, email e URL sem converter entradas inválidas.
 *
 * @param mixed $value Valor recebido.
 * @param array $field Definição local.
 * @return bool|string
 */
function blooikos_core_validate_text( $value, $field ) {
	if ( ! is_scalar( $value ) ) {
		return __( 'Informe um valor textual.', 'blooikos-core' );
	}
	$text = (string) $value;
	if ( 'wysiwyg' === $field['type'] ) {
		$text = html_entity_decode( wp_strip_all_tags( blooikos_core_sanitize_rich_text( $text ) ), ENT_QUOTES, 'UTF-8' );
	}
	if ( ! empty( $field['maxlength'] ) && mb_strlen( $text ) > $field['maxlength'] ) {
		/* translators: %d: maximum character count. */
		return sprintf( __( 'Use no máximo %d caracteres.', 'blooikos-core' ), $field['maxlength'] );
	}
	if ( 'field_blooikos_site_phone' === $field['key'] ) {
		$digits = preg_replace( '/[ ()+\-]/', '', $text );
		if ( ! preg_match( '/^[0-9 ()+\-]+$/D', $text ) || ! preg_match( '/^[0-9]{8,15}$/D', $digits ) ) {
			return __( 'Informe de 8 a 15 dígitos, usando apenas espaços, parênteses, hífens e + como separadores.', 'blooikos-core' );
		}
	}
	if ( 'email' === $field['type'] && ! is_email( $text ) ) {
		return __( 'Informe um email válido.', 'blooikos-core' );
	}
	if ( 'url' === $field['type'] && ! blooikos_core_valid_url( $text, true ) ) {
		return __( 'Informe uma URL HTTPS definitiva.', 'blooikos-core' );
	}
	return true;
}

/**
 * Aceita somente os destinos editoriais previstos no contrato.
 *
 * @param mixed $url URL recebida.
 * @param bool  $https_only Restringir a HTTPS.
 * @return bool
 */
function blooikos_core_valid_url( $url, $https_only = false ) {
	if ( ! is_string( $url ) || '' === $url || preg_match( '/[\s\\\\\x00-\x1f\x7f]/', $url ) ) {
		return false;
	}
	if ( ! $https_only && preg_match( '/^#[a-zA-Z][a-zA-Z0-9_-]*$/D', $url ) ) {
		return true;
	}
	if ( ! $https_only && '/' === $url[0] && ! str_starts_with( $url, '//' ) ) {
		return true;
	}
	$parts = wp_parse_url( $url );
	if ( ! $parts || 'https' !== ( $parts['scheme'] ?? '' ) || empty( $parts['host'] ) || isset( $parts['user'] ) || isset( $parts['pass'] ) || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
		return false;
	}
	return ! preg_match( '/(^|\.)(figma\.com|figma\.site)$/i', $parts['host'] );
}

/**
 * Valida destino, título e comportamento do link.
 *
 * @param mixed $value Valor do link.
 * @return bool|string
 */
function blooikos_core_validate_link( $value ) {
	if ( is_array( $value ) && '' === ( $value['url'] ?? '' ) && '' === ( $value['title'] ?? '' ) && '' === ( $value['target'] ?? '' ) ) {
		return true;
	}
	if ( ! is_array( $value ) || ! isset( $value['url'], $value['title'] ) || ! is_string( $value['title'] ) || '' === trim( sanitize_text_field( $value['title'] ) ) || ! blooikos_core_valid_url( $value['url'] ) || ! in_array( $value['target'] ?? '', array( '', '_blank' ), true ) ) {
		return __( 'Informe um título descritivo, um destino HTTPS, /caminho ou #âncora e uma abertura válida.', 'blooikos-core' );
	}
	return true;
}

/**
 * Confere limites, duplicatas e autorização das entidades selecionadas.
 *
 * @param mixed $value IDs selecionados, em ordem.
 * @param array $field Campo relationship ou post_object.
 * @return bool|string
 */
function blooikos_core_validate_companies( $value, $field ) {
	$ids = 'post_object' === $field['type'] ? array( $value ) : $value;
	if ( ! is_array( $ids ) || count( $ids ) > ( $field['max'] ?? 1 ) ) {
		return __( 'A seleção excede o limite de empresas deste campo.', 'blooikos-core' );
	}
	$seen = array();
	foreach ( $ids as $id ) {
		if ( ! is_scalar( $id ) || ! ctype_digit( (string) $id ) || (int) $id < 1 ) {
			return __( 'Selecione um ID de empresa válido.', 'blooikos-core' );
		}
		$id      = (int) $id;
		$company = get_post( $id );
		if ( isset( $seen[ $id ] ) || ! $company || 'blooikos_company' !== $company->post_type || ! in_array( $company->post_status, array( 'draft', 'publish' ), true ) || ! current_user_can( 'edit_post', $id ) ) {
			return __( 'Selecione empresas distintas, publicadas ou em rascunho, que você pode editar.', 'blooikos-core' );
		}
		$seen[ $id ] = true;
	}
	return true;
}

/**
 * Confere o arquivo selecionado e o alt de imagens informativas.
 *
 * @param mixed $value ID da mídia.
 * @param array $field Campo de imagem.
 * @return bool|string
 */
function blooikos_core_validate_image( $value, $field ) {
	if ( ! is_scalar( $value ) || ! ctype_digit( (string) $value ) || ! wp_attachment_is_image( (int) $value ) || ! in_array( get_post_mime_type( (int) $value ), array( 'image/jpeg', 'image/png', 'image/webp' ), true ) || ! current_user_can( 'upload_files' ) || ! current_user_can( 'edit_post', (int) $value ) || 'trash' === get_post_status( (int) $value ) ) {
		return __( 'Selecione uma imagem JPEG, PNG ou WebP da biblioteca que você pode editar.', 'blooikos-core' );
	}
	$informative = in_array(
		$field['key'],
		array( 'field_blooikos_company_logo', 'field_blooikos_company_image', 'field_blooikos_site_logo', 'field_blooikos_site_logo_inverse' ),
		true
	);
	if ( 'field_blooikos_home_institutional_image' === $field['key'] ) {
		// O ACF confere o nonce do formulário antes destes filtros; ler só o booleano irmão.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$decorative  = isset( $_POST['acf']['field_blooikos_home_institutional_decorative'] ) && '1' === $_POST['acf']['field_blooikos_home_institutional_decorative'];
		$informative = ! $decorative;
	}
	if ( $informative && '' === trim( (string) get_post_meta( (int) $value, '_wp_attachment_image_alt', true ) ) ) {
		return __( 'Preencha o texto alternativo desta imagem na Biblioteca de mídia.', 'blooikos-core' );
	}
	return true;
}

/**
 * Confere tamanho da coleção e subcampos obrigatórios de linhas existentes.
 *
 * @param mixed $value Linhas do repeater.
 * @param array $field Definição local.
 * @return bool|string
 */
function blooikos_core_validate_rows( $value, $field ) {
	if ( ! is_array( $value ) ) {
		return __( 'Informe uma lista de itens válida.', 'blooikos-core' );
	}
	unset( $value['acfcloneindex'] );
	if ( count( $value ) > $field['max'] ) {
		/* translators: %d: maximum number of items. */
		return sprintf( __( 'Adicione no máximo %d itens.', 'blooikos-core' ), $field['max'] );
	}
	foreach ( $value as $row ) {
		if ( ! is_array( $row ) ) {
			return __( 'Cada item deve conter seus campos editoriais.', 'blooikos-core' );
		}
		foreach ( $field['sub_fields'] as $sub_field ) {
			if ( $sub_field['required'] && ( ! isset( $row[ $sub_field['key'] ] ) || ! is_scalar( $row[ $sub_field['key'] ] ) || '' === trim( sanitize_text_field( $row[ $sub_field['key'] ] ) ) ) ) {
				return __( 'Preencha os campos obrigatórios de cada item adicionado.', 'blooikos-core' );
			}
		}
	}
	return true;
}

/**
 * Limita o rich text a marcação editorial sem conteúdo executável.
 *
 * @param string $value Texto recebido.
 * @return string
 */
function blooikos_core_sanitize_rich_text( $value ) {
	$value = preg_replace( '/\[[^\]]*\]/', '', $value );
	$value = wp_kses(
		$value,
		array(
			'p'      => array(),
			'br'     => array(),
			'strong' => array(),
			'em'     => array(),
			'ul'     => array(),
			'ol'     => array(),
			'li'     => array(),
			'a'      => array(
				'href'  => true,
				'title' => true,
			),
		),
		array( 'https' )
	);
	$html  = new WP_HTML_Tag_Processor( $value );
	while ( $html->next_tag( 'a' ) ) {
		if ( ! blooikos_core_valid_url( $html->get_attribute( 'href' ) ) ) {
			$html->remove_attribute( 'href' );
		}
	}
	return $html->get_updated_html();
}

/**
 * Evita autoembeds e execução de shortcodes na leitura dos dois rich texts.
 *
 * O ACF não oferece variação por chave para pre_format_value. O filtro retorna
 * imediatamente para qualquer campo fora destas duas chaves explícitas.
 *
 * @param mixed      $formatted Resultado antecipado de outro filtro.
 * @param mixed      $value Valor armazenado.
 * @param int|string $post_id Destino ACF.
 * @param array      $field Campo sendo formatado.
 * @return mixed
 */
function blooikos_core_format_rich_text( $formatted, $value, $post_id, $field ) {
	if ( null !== $formatted || ! in_array( $field['key'], array( 'field_blooikos_company_body', 'field_blooikos_home_about_body' ), true ) ) {
		return $formatted;
	}
	return is_string( $value ) ? blooikos_core_sanitize_rich_text( $value ) : '';
}

/**
 * Sanitiza na persistência, sem substituir validação de domínio.
 *
 * @param mixed      $value Valor recebido.
 * @param int|string $post_id Destino nativo ACF.
 * @param array      $field Definição local.
 * @return mixed
 */
function blooikos_core_sanitize_field( $value, $post_id, $field ) {
	if ( 'wysiwyg' === $field['type'] ) {
		return is_string( $value ) ? blooikos_core_sanitize_rich_text( $value ) : '';
	}
	if ( 'textarea' === $field['type'] ) {
		return is_scalar( $value ) ? sanitize_textarea_field( $value ) : '';
	}
	if ( in_array( $field['type'], array( 'text', 'email', 'url' ), true ) ) {
		return is_scalar( $value ) ? sanitize_text_field( $value ) : '';
	}
	if ( 'link' === $field['type'] && is_array( $value ) ) {
		return array(
			'url'    => blooikos_core_valid_url( $value['url'] ?? '' ) ? esc_url_raw( $value['url'], array( 'https' ) ) : '',
			'title'  => sanitize_text_field( $value['title'] ?? '' ),
			'target' => '_blank' === ( $value['target'] ?? '' ) ? '_blank' : '',
		);
	}
	return $value;
}

add_action( 'acf/init', 'blooikos_core_register_validation', 20 );
add_filter( 'acf/pre_format_value', 'blooikos_core_format_rich_text', 10, 4 );
add_action( 'acf/save_post', 'blooikos_core_validate_editorial_save', 5 );
