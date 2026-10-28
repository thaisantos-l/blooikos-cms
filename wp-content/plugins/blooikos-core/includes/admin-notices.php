<?php
/**
 * Avisos informativos no painel, restritos a administradores.
 *
 * @package BlooikosCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renderiza o diagnóstico sem contaminar CLI, AJAX ou respostas públicas.
 *
 * As traduções são resolvidas apenas durante a renderização do painel.
 *
 * @return void
 */
function blooikos_core_render_dependency_notice() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) || wp_doing_ajax()
		|| ( defined( 'WP_CLI' ) && WP_CLI ) ) {
		return;
	}

	$issues = blooikos_core_get_dependency_issues();

	if ( ! $issues ) {
		return;
	}

	?>
	<div class="notice notice-warning" role="status">
		<p><strong><?php esc_html_e( 'Blooikos Core: integrações indisponíveis.', 'blooikos-core' ); ?></strong></p>
		<ul>
			<?php foreach ( $issues as $issue ) : ?>
				<li>
					<?php
					if ( 'missing' === $issue['reason'] ) {
						/* translators: 1: Plugin name. 2: Minimum supported version. */
						$message = sprintf( __( '%1$s: instale e ative a versão %2$s ou superior.', 'blooikos-core' ), $issue['name'], $issue['minimum'] );
					} else {
						/* translators: 1: Plugin name. 2: Minimum supported version. */
						$message = sprintf( __( '%1$s: atualize para a versão %2$s ou superior.', 'blooikos-core' ), $issue['name'], $issue['minimum'] );
					}
					echo esc_html( $message );
					?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php
}
