<?php
/**
 * Plugin Name: Blooikos Core
 * Description: Fundação das regras e integrações do CMS Blooikos.
 * Version: 0.1.0
 * Requires at least: 7.1.3
 * Requires PHP: 8.2.4
 * Text Domain: blooikos-core
 *
 * @package BlooikosCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BLOOIKOS_CORE_VERSION', '0.1.0' );

require_once __DIR__ . '/includes/dependencies.php';
require_once __DIR__ . '/includes/admin-notices.php';

/**
 * Verifica dependências uma vez, após a inicialização dos plugins externos.
 *
 * Nenhuma integração de domínio ou persistência é criada nesta fundação.
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
}

add_action( 'plugins_loaded', 'blooikos_core_bootstrap', 100 );
