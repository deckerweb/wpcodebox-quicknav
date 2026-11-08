<?php
/**
 * Plugin Name: WPCodeBox QuickNav
 * Plugin URI: https://deckerwebplugins.com/en/plugins/wpcodebox-quicknav/
 * Description: Reach WPCodeBox snippets through personal favorites, status lists, folders and search in the WordPress toolbar.
 * Version: 1.0.1
 * Requires at least: 6.9
 * Requires PHP: 8.3
 * Author: David Decker – DECKERWEB
 * Author URI: https://deckerwebplugins.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wpcodebox-quicknav
 * Domain Path: /languages/
 * Update URI: https://github.com/deckerweb/wpcodebox-quicknav
 * GitHub Plugin URI: https://github.com/deckerweb/wpcodebox-quicknav
 *
 * Copyright © 2026 David Decker – DECKERWEB.
 * SPDX-License-Identifier: GPL-2.0-or-later
 */
defined( 'ABSPATH' ) || exit;
if ( version_compare( PHP_VERSION, '8.3', '<' ) ) {
	add_action( 'admin_notices',
		/**
		 * Explain the unsupported PHP runtime without loading plugin components.
		 * @return void Outputs an escaped administrative notice.
		 */
		static function() { echo '<div class="notice notice-error"><p>' . esc_html__( 'WPCodeBox QuickNav requires PHP 8.3 or newer.', 'wpcodebox-quicknav' ) . '</p></div>'; } );
	return;
}
// Keep duplicate bootstrap execution from registering hooks twice.
if ( class_exists( 'DDW_WPCodeBox_QuickNav', false ) ) { return; }
define( 'WCBQN_PLUGIN_FILE', __FILE__ );
foreach ( [ 'config', 'adapter', 'favorites', 'navigation', 'menu-icons', 'renderer', 'settings', 'search', 'commands', 'functionality', 'workspace', 'plugin' ] as $wcbqn_component ) { require_once __DIR__ . '/includes/class-wcbqn-' . $wcbqn_component . '.php'; }
require_once __DIR__ . '/includes/deckerweb-changelog-v1.php';
require_once __DIR__ . '/includes/deckerweb-plugin-library/bootstrap.php';
deckerweb_library_register_v2( __FILE__, [], __DIR__ . '/includes/deckerweb-plugin-library' );
new DDW_WPCodeBox_QuickNav();
