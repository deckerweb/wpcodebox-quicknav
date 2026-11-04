<?php
/** Consistent Lucide toolbar artwork shared with Bricks QuickNav. @package WPCodeBoxQuickNav */
defined( 'ABSPATH' ) || exit;

/** Decorate the first submenu level without changing destinations or accessible labels. */
final class WCBQN_Menu_Icons {

	/**
	 * Return the fixed icon vocabulary, matching equivalent Bricks QuickNav items.
	 * @return array<string,string> Stable node IDs and bundled Lucide symbols.
	 */
	public static function mapping(): array {
		return [
			'wcbqn-preferences' => 'settings', 'wcbqn-functionality' => 'package',
			'wcbqn-safe-editor' => 'triangle-alert',
			'wcbqn-devmode-active' => 'triangle-alert', 'wcbqn-favorites' => 'star',
			'wcbqn-active' => 'code', 'wcbqn-inactive' => 'code',
			'wcbqn-scripts-folders' => 'folder', 'wcbqn-add-script' => 'plus',
			'wcbqn-system-dashboard' => 'layout-list', 'wcbqn-sitehealth-info' => 'info',
			'wcbqn-variable-inspector' => 'code', 'wcbqn-debuglog-manager' => 'file-text',
			'wcbqn-devkitpro' => 'code', 'wcbqn-library' => 'package',
			'wcbqn-libraries' => 'layers', 'wcbqn-links' => 'external-link',
			'wcbqn-about' => 'circle-user-round',
		];
	}

	/**
	 * Load only allowlisted local artwork, once per symbol and request.
	 * @param string $symbol Bundled symbol from the fixed vocabulary.
	 * @return string Decorative SVG wrapper, or an empty fallback if unavailable.
	 */
	private static function markup( string $symbol ): string {
		static $cache = [];
		if ( ! in_array( $symbol, self::mapping(), true ) ) { return ''; }
		if ( ! isset( $cache[$symbol] ) ) {
			$file = dirname( WCBQN_PLUGIN_FILE ) . '/assets/lucide/' . $symbol . '.svg';
			$svg = is_readable( $file ) ? file_get_contents( $file ) : '';
			$cache[$symbol] = $svg ? '<span class="wcbqn-menu-icon" aria-hidden="true">' . $svg . '</span> ' : '';
		}
		return $cache[$symbol];
	}

	/** Return the shared warning symbol and a screen-reader status for the main item. @return string Safe local markup. */
	public static function safe_mode_indicator(): string {
		return '<span class="wcbqn-safe-indicator" aria-hidden="true">' . self::markup( 'triangle-alert' ) . '</span><span class="screen-reader-text"> ' . esc_html__( 'Safe Mode active', 'wpcodebox-quicknav' ) . '</span>';
	}

	/**
	 * Apply icons to the first submenu level, resolving WordPress groups as transparent.
	 * @param WP_Admin_Bar $bar Fully populated toolbar; only this plugin's subtree is modified.
	 * @return void Preserves labels, metadata, destinations, the main logo and unrelated menus.
	 */
	public static function apply( WP_Admin_Bar $bar ): void {
		$nodes = $bar->get_nodes();
		$map = self::mapping();
		foreach ( $nodes ?: [] as $id => $node ) {
			if ( ! empty( $node->group ) || 'ddw-wpcodebox-quicknav' === $id ) { continue; }
			$parent = $node->parent;
			$seen = [];
			$first = true;
			while ( isset( $nodes[$parent] ) && 'ddw-wpcodebox-quicknav' !== $parent && ! isset( $seen[$parent] ) ) {
				$seen[$parent] = true;
				if ( empty( $nodes[$parent]->group ) ) { $first = false; }
				$parent = $nodes[$parent]->parent;
			}
			if ( 'ddw-wpcodebox-quicknav' !== $parent ) { continue; }
			$title = preg_replace( '~<span\b[^>]*class=["\'][^"\']*\b(?:icon-svg|wcbqn-menu-icon)\b[^"\']*["\'][^>]*>.*?</span>\s*~s', '', $node->title );
			if ( 'wcbqn-favorites' === $id ) { $title = preg_replace( '/^★\s*/u', '', $title ); }
			$bar->add_node( [ 'id' => $id, 'title' => ( $first ? self::markup( $map[$id] ?? 'puzzle' ) : '' ) . $title ] );
		}
	}
}
