<?php
/** Read-only WPCodeBox 2 metadata boundary. No premium source is included. */
defined( 'ABSPATH' ) || exit;

/** Map independent snippet/folder IDs to navigation records without reading code. */
final class WCBQN_WPCodeBox_Adapter {
	/** Check the supported active plugin and version. @return bool Whether integration is available. */
	public static function available(): bool {
		if ( ! defined( 'WPCODEBOX2_VERSION' ) || ! defined( 'WPCODEBOX2_PATH' ) || version_compare( WPCODEBOX2_VERSION, '1.4.1', '<' ) || ! class_exists( '\Wpcb2\Service\SafeMode' ) ) { return false; }
		// Constants survive switch_to_blog(); activation state belongs to the current website.
		if ( ! get_option( 'wpcb2_version', false ) ) { return false; }
		$file = plugin_basename( trailingslashit( WPCODEBOX2_PATH ) . 'wpcodebox2.php' );
		$active = (array) get_option( 'active_plugins', [] );
		$network = is_multisite() ? (array) get_site_option( 'active_sitewide_plugins', [] ) : [];
		return in_array( $file, $active, true ) || isset( $network[$file] );
	}
	/** Read the effective upstream safe mode, including request-local recovery. @return bool Effective state. */
	public static function safe_mode(): bool {
		return defined( 'WPCB_SAFE_MODE' ) || ( self::available() && \Wpcb2\Service\SafeMode::isEnabled() );
	}
	/**
	 * Resolve the active editor location and supported QuickNav navigation intent.
	 * @param int $parent Negative folder navigation ID, or zero for root.
	 * @param int|null $edit Positive snippet ID, zero for a new snippet, null for the collection.
	 * @return string Site-local editor URL; folder/new intents are handled by local enhancement.
	 */
	public static function url( int $parent = 0, ?int $edit = null ): string {
		$args = [ 'page' => 'wpcodebox2' ];
		if ( null !== $edit && $edit > 0 ) { $args['snippet'] = $edit; }
		if ( 0 === $edit ) { $args['wcbqn_new'] = 1; }
		if ( $parent < 0 && null === $edit && WCBQN_Config::search() ) { $args['wcbqn_folder'] = abs( $parent ); }
		return add_query_arg( $args, admin_url( get_option( 'wpcb_show_in_tools', false ) ? 'tools.php' : 'admin.php' ) );
	}
	/**
	 * Load only whitelisted metadata from this site's upstream tables, once per site/request.
	 * @return array Indexed records; folders use negative IDs so independent table IDs cannot collide.
	 */
	public function scripts(): array {
		if ( ! self::available() || ! WCBQN_Config::allowed() ) { return []; }
		static $cache = [];
		$site = get_current_blog_id();
		if ( isset( $cache[$site] ) ) { return $cache[$site]; }
		global $wpdb;
		// Identifier placeholders keep site-specific table names separate from SQL syntax.
		$folders = $wpdb->get_results( $wpdb->prepare( 'SELECT id, name FROM %i ORDER BY folder_order, id', $wpdb->prefix . 'wpcb_folders' ), ARRAY_A );
		$snippets = $wpdb->get_results( $wpdb->prepare( 'SELECT id, title, enabled, codeType, folderId, location, error, renderType, runType FROM %i ORDER BY snippet_order, id', $wpdb->prefix . 'wpcb_snippets' ), ARRAY_A );
		$result = [];
		foreach ( is_array( $folders ) ? $folders : [] as $folder ) {
			$id = -absint( $folder['id'] );
			if ( ! $id ) { continue; }
			$result[$id] = [ 'term_id' => $id, 'parent' => 0, 'title' => (string) $folder['name'], 'type' => 'folder', 'status' => true, 'location' => '', 'hook' => '' ];
		}
		foreach ( is_array( $snippets ) ? $snippets : [] as $item ) {
			$id = absint( $item['id'] );
			if ( ! $id ) { continue; }
			$parent = -absint( $item['folderId'] );
			$result[$id] = [ 'term_id' => $id, 'parent' => isset( $result[$parent] ) ? $parent : 0, 'title' => (string) $item['title'], 'type' => (string) $item['codeType'], 'status' => ! empty( $item['enabled'] ), 'location' => (string) $item['location'], 'hook' => '', 'error' => ! empty( $item['error'] ), 'render' => (string) $item['renderType'], 'run' => (string) $item['runType'] ];
		}
		$cache[$site] = $result;
		return $result;
	}
}
