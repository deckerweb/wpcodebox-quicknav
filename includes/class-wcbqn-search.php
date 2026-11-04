<?php
/** Adapted from Advanced Scripts QuickNav 1.3.2, David Decker, GPL-2.0-or-later. See docs/credits.txt. */
/** Cross-folder metadata search and personal favorites, without upstream writes. */
defined( 'ABSPATH' ) || exit;

/** Own the search assets and permission-protected metadata endpoint. */
final class WCBQN_Search {
	/** Register screen-specific assets and authenticated AJAX only. @return void */
	public function register(): void {
		add_action( 'admin_enqueue_scripts', [ $this, 'assets' ] );
		add_action( 'wp_ajax_wcbqn_search_scripts', [ $this, 'ajax' ] );
		add_action( 'wp_ajax_wcbqn_search_favorite', [ $this, 'favorite' ] );
	}

	/**
	 * Load local enhancement assets only on the supported upstream admin screen.
	 * @param string $hook WordPress admin screen hook suffix.
	 * @return void No output or assets on unrelated screens or without permission.
	 */
	public function assets( string $hook ): void {
		if ( ! in_array( $hook, [ 'tools_page_wpcodebox2', 'toplevel_page_wpcodebox2' ], true ) || ! WCBQN_Config::allowed() || ! WCBQN_WPCodeBox_Adapter::available() ) { return; }
		wp_enqueue_script( 'wcbqn-navigation', plugins_url( 'assets/navigation.js', WCBQN_PLUGIN_FILE ), [], WCBQN_Config::VERSION, true );
		wp_localize_script( 'wcbqn-navigation', 'WCBQNNavigation', [ 'newIntent' => isset( $_GET['wcbqn_new'] ) && '1' === $_GET['wcbqn_new'] ] );
		if ( ! WCBQN_Config::search() ) { return; }
		$base = dirname( WCBQN_PLUGIN_FILE );
		wp_enqueue_style( 'wcbqn-search', plugins_url( 'assets/search.css', WCBQN_PLUGIN_FILE ), [], WCBQN_Config::VERSION . '.' . filemtime( $base . '/assets/search.css' ) );
		wp_enqueue_script( 'wcbqn-search', plugins_url( 'assets/search.js', WCBQN_PLUGIN_FILE ), [], WCBQN_Config::VERSION . '.' . filemtime( $base . '/assets/search.js' ), true );
		wp_localize_script( 'wcbqn-search', 'WCBQNSearch', [
			'newIntent' => isset( $_GET['wcbqn_new'] ) && '1' === $_GET['wcbqn_new'], 'folder' => isset( $_GET['wcbqn_folder'] ) && is_scalar( $_GET['wcbqn_folder'] ) ? absint( $_GET['wcbqn_folder'] ) : 0, 'url' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'wcbqn_search' ),
			'favoriteNonce' => wp_create_nonce( 'wcbqn_search_favorite' ),
			'typeLabel' => __( 'Snippet type', 'wpcodebox-quicknav' ),
			'statusLabel' => __( 'Snippet status', 'wpcodebox-quicknav' ),
			'types' => [ 'all' => __( 'All types', 'wpcodebox-quicknav' ), 'php' => 'PHP', 'css' => 'CSS / SCSS', 'js' => 'JavaScript', 'html' => 'HTML', 'other' => __( 'Other types', 'wpcodebox-quicknav' ) ],
			'states' => [ 'all' => __( 'All statuses', 'wpcodebox-quicknav' ), 'active' => __( 'Active', 'wpcodebox-quicknav' ), 'inactive' => __( 'Inactive', 'wpcodebox-quicknav' ) ],
			'addFavorite' => __( 'Add to favorites', 'wpcodebox-quicknav' ),
			'removeFavorite' => __( 'Remove from favorites', 'wpcodebox-quicknav' ),
			'favoriteSaved' => __( 'Favorites updated.', 'wpcodebox-quicknav' ),
			'favoriteError' => __( 'The favorite could not be saved. Please try again.', 'wpcodebox-quicknav' ),
			'darkMode' => (bool) get_option( 'wpcb_dark_mode', true ),
			'label' => __( 'Search all snippets', 'wpcodebox-quicknav' ),
			'help' => __( 'Search titles and folder paths across all folders. Snippet contents are not searched.', 'wpcodebox-quicknav' ),
			'clear' => __( 'Clear search', 'wpcodebox-quicknav' ),
			'loading' => __( 'Searching…', 'wpcodebox-quicknav' ),
			'empty' => __( 'No matching snippets.', 'wpcodebox-quicknav' ),
			/* translators: %d: total matching scripts. */
			'count' => __( 'Matches: %d', 'wpcodebox-quicknav' ),
			'limited' => __( 'Showing the first 50 matches. Refine your search for more precise results.', 'wpcodebox-quicknav' ),
			'error' => __( 'Search is unavailable. Try again or reload this page. The original snippet list remains available.', 'wpcodebox-quicknav' ),
		] );
	}

	/**
	 * Search fresh metadata without exposing snippet contents or modifying state.
	 * @return void Sends JSON and terminates the authenticated AJAX request.
	 */
	public function ajax(): void {
		if ( ! WCBQN_Config::allowed() ) { wp_send_json_error( null, 403 ); }
		if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) && is_string( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) ) { wp_send_json_error( null, 405 ); }
		check_ajax_referer( 'wcbqn_search', 'nonce' );
		if ( ! WCBQN_WPCodeBox_Adapter::available() ) { wp_send_json_error( null, 409 ); }
		$folder = isset( $_POST['folder'] ) && is_string( $_POST['folder'] ) ? absint( $_POST['folder'] ) : 0;
		$query = '';
		if ( isset( $_POST['query'] ) && ! is_string( $_POST['query'] ) ) { wp_send_json_error( null, 400 ); }
		$query = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '';
		if ( strlen( $query ) > 400 ) { wp_send_json_error( null, 400 ); }
		if ( ( isset( $_POST['type'] ) && ! is_string( $_POST['type'] ) ) || ( isset( $_POST['state'] ) && ! is_string( $_POST['state'] ) ) ) { wp_send_json_error( null, 400 ); }
		$type = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : 'all'; $state = isset( $_POST['state'] ) ? sanitize_text_field( wp_unslash( $_POST['state'] ) ) : 'all';
		if ( ! is_string( $type ) || ! in_array( $type, [ 'all', 'php', 'css', 'js', 'html', 'other' ], true ) || ! is_string( $state ) || ! in_array( $state, [ 'all', 'active', 'inactive' ], true ) ) { wp_send_json_error( null, 400 ); }
		wp_send_json_success( $this->find( $query, ( new WCBQN_WPCodeBox_Adapter()  )->scripts(), $type, $state, $folder ) );
	}

	/**
	 * Set a favorite for the authenticated user/site; never accept a target user ID.
	 * @return void Sends JSON after a nonce-protected metadata-only preference write.
	 */
	public function favorite(): void {
		if ( ! WCBQN_Config::allowed() ) { wp_send_json_error( null, 403 ); }
		if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) && is_string( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) ) { wp_send_json_error( null, 405 ); }
		check_ajax_referer( 'wcbqn_search_favorite', 'nonce' );
		if ( ! WCBQN_WPCodeBox_Adapter::available() ) { wp_send_json_error( null, 409 ); }
		if ( ! isset( $_POST['id'], $_POST['selected'] ) || ! is_string( $_POST['id'] ) || ! is_string( $_POST['selected'] ) || ! preg_match( '/\A[0-9]{1,18}\z/D', sanitize_text_field( wp_unslash( $_POST['id'] ) ) ) || ! in_array( wp_unslash( $_POST['selected'] ), [ '0', '1' ], true ) ) { wp_send_json_error( null, 400 ); }
		$id = sanitize_text_field( wp_unslash( $_POST['id'] ) ); $selected = sanitize_text_field( wp_unslash( $_POST['selected'] ) );
		if ( ! is_string( $id ) || ! ctype_digit( $id ) || strlen( $id ) > 18 || (int) $id < 1 || ! in_array( $selected, [ '0', '1' ], true ) ) { wp_send_json_error( null, 400 ); }
		$scripts = ( new WCBQN_WPCodeBox_Adapter() )->scripts(); $id = (int) $id;
		if ( ! isset( $scripts[$id] ) || 'folder' === $scripts[$id]['type'] ) { wp_send_json_error( null, 404 ); }
		$key = WCBQN_Config::key( 'favorites' ); $user = get_current_user_id();
		// Use the previous metadata value to detect overlapping changes where supported.
		for ( $attempt = 0; $attempt < 3; $attempt++ ) {
			$old = get_user_meta( $user, $key, true );
			$ids = WCBQN_Favorites::sanitize( is_array( $old ) ? $old : [], $scripts );
			$has = in_array( $id, $ids, true );
			if ( $has === ( '1' === $selected ) ) { wp_send_json_success( [ 'id' => $id, 'favorite' => $has ] ); }
			if ( '1' === $selected && count( $ids ) >= 100 ) { wp_send_json_error( [ 'message' => __( 'Please select no more than 100 favorites.', 'wpcodebox-quicknav' ) ], 409 ); }
			$next = '1' === $selected ? array_merge( $ids, [ $id ] ) : array_values( array_diff( $ids, [ $id ] ) );
			$exists = metadata_exists( 'user', $user, $key );
			$saved = $exists ? update_user_meta( $user, $key, $next, $old ) : add_user_meta( $user, $key, $next, true );
			if ( $saved ) { wp_send_json_success( [ 'id' => $id, 'favorite' => '1' === $selected ] ); }
			wp_cache_delete( $user, 'user_meta' );
		}
		wp_send_json_error( null, 409 );
	}

	/**
	 * Resolve a stable type group without searching source code.
	 * @param string $type Native WPCodeBox MIME/type identifier.
	 * @return string Group used by the type filter.
	 */
	private static function group( string $type ): string {
		if ( 'php' === $type ) { return 'php'; }
		if ( in_array( $type, [ 'css', 'scss', 'scssp', 'less', 'ex_css' ], true ) ) { return 'css'; }
		if ( in_array( $type, [ 'js', 'ex_js' ], true ) ) { return 'js'; }
		return 'html' === $type ? 'html' : 'other';
	}

	/**
	 * Match words and filters, then project only the first fifty sorted matches.
	 * @param string $query Case/accent-insensitive whitespace-separated words.
	 * @param array $scripts Indexed metadata from the adapter, excluding source code.
	 * @param string $type Type group, or all.
	 * @param string $state Saved activation state, blocked, or all.
	 * @param int $folder Exact upstream folder ID, zero for every folder.
	 * @return array Total count and at most fifty source-free result records.
	 */
	public function find( string $query, array $scripts, string $type = 'all', string $state = 'all', int $folder = 0 ): array {
		$words = preg_split( '/\s+/u', strtolower( remove_accents( trim( $query ) ) ), -1, PREG_SPLIT_NO_EMPTY );
		if ( ! $folder && ! $words && 'all' === $type && 'all' === $state ) { return [ 'total' => 0, 'items' => [] ]; }
		$nav = new WCBQN_Navigation_Builder( $scripts ); $matches = []; $paths = []; $blocks = [];
		foreach ( $scripts as $id => $item ) {
			if ( $folder && $item['parent'] !== -$folder ) { continue; }
			if ( 'folder' === $item['type'] || ( 'all' !== $type && self::group( $item['type'] ) !== $type ) ) { continue; }
			if ( ( 'active' === $state && ! $item['status'] ) || ( 'inactive' === $state && $item['status'] ) ) { continue; }
			$parent = $item['parent'];
			$blocks[$parent] = $blocks[$parent] ?? $nav->blocked( $item );
			if ( 'blocked' === $state && ( ! $item['status'] || ! $blocks[$parent] ) ) { continue; }
			$paths[$parent] = $paths[$parent] ?? $nav->path( $item );
			$haystack = strtolower( remove_accents( $item['title'] . ' ' . $paths[$parent] ) );
			foreach ( $words ?: [] as $word ) { if ( false === strpos( $haystack, $word ) ) { continue 2; } }
			$matches[] = $id;
		}
		usort( $matches,
			/** Sort matching IDs by display title and stable ID. @param int $a First ID. @param int $b Second ID. @return int Sort order. */
			static function( int $a, int $b ) use ( $scripts ): int { return strcasecmp( $scripts[$a]['title'], $scripts[$b]['title'] ) ?: $a <=> $b; }
		);
		$favorites = array_fill_keys( WCBQN_Favorites::ids( $scripts ), true ); $items = [];
		foreach ( array_slice( $matches, 0, 50 ) as $id ) {
			$item = $scripts[$id];
			$items[] = [ 'id' => $id, 'title' => $item['title'] ?: __( 'Untitled snippet', 'wpcodebox-quicknav' ), 'path' => $paths[$item['parent']],
				'url' => WCBQN_WPCodeBox_Adapter::url( $item['parent'], $item['term_id'] ), 'type' => WCBQN_Navigation_Builder::type( $item['type'] ), 'favorite' => isset( $favorites[$id] ),
				'status' => ! $item['status'] ? __( 'Inactive', 'wpcodebox-quicknav' ) : ( $blocks[$item['parent']] ? __( 'Active, blocked by folder', 'wpcodebox-quicknav' ) : __( 'Active', 'wpcodebox-quicknav' ) ) ];
		}
		return [ 'total' => count( $matches ), 'items' => $items ];
	}
}
