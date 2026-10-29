<?php
/**
 * Plugin Name: Blooikos Core
 * Description: Fundação das regras e integrações do CMS Blooikos.
 * Version: 0.4.1
 * Requires at least: 7.1.3
 * Requires PHP: 8.2.4
 * Text Domain: blooikos-core
 *
 * @package BlooikosCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BLOOIKOS_CORE_VERSION', '0.4.1' );

require_once __DIR__ . '/includes/dependencies.php';
require_once __DIR__ . '/includes/admin-notices.php';
require_once __DIR__ . '/includes/webhook/bootstrap.php';
register_activation_hook( __FILE__, 'blooikos_core_rebuild_activate' );
register_deactivation_hook( __FILE__, 'blooikos_core_rebuild_deactivate' );

/**
 * Verifica dependências uma vez, após a inicialização dos plugins externos.
 *
 * Carrega o modelo editorial somente quando as dependências estão prontas.
 *
 * @return void
 */
function blooikos_core_bootstrap() {
	static $initialized = false;

	if ( $initialized ) {
		return;
	}

	$initialized = true;

	if ( blooikos_core_get_dependency_issues() ) {
		if ( is_admin() ) {
			add_action( 'admin_notices', 'blooikos_core_render_dependency_notice' );
		}
		return;
	}

	require_once __DIR__ . '/includes/post-types/company.php';
	require_once __DIR__ . '/includes/navigation.php';
	require_once __DIR__ . '/includes/acf/options.php';
	require_once __DIR__ . '/includes/acf/company.php';
	require_once __DIR__ . '/includes/acf/home.php';
	require_once __DIR__ . '/includes/acf/site.php';
	require_once __DIR__ . '/includes/acf/validation.php';
	require_once __DIR__ . '/includes/graphql/anonymous.php';
	require_once __DIR__ . '/includes/graphql/registration.php';
	require_once __DIR__ . '/includes/graphql/visibility.php';
	require_once __DIR__ . '/includes/graphql/values.php';
	require_once __DIR__ . '/includes/graphql/validation.php';
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		require_once __DIR__ . '/includes/graphql/cli.php';
	}
}

add_action( 'plugins_loaded', 'blooikos_core_bootstrap', 100 );
