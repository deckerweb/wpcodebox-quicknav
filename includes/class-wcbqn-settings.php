<?php
/** Adapted from Advanced Scripts QuickNav 1.3.2, David Decker, GPL-2.0-or-later. See docs/credits.txt. */
/** WordPress-native personal settings, with deckerweb header and local changelog. */
defined( 'ABSPATH' ) || exit;
/** Render and save capability-protected, site-scoped personal preferences. */
final class WCBQN_Settings {
	/** Registered screen suffix for the selected parent menu. */
	private string $page_hook = '';

	/**
	 * Register the component’s WordPress callbacks.
	 *
	 * @return void No return value.
	 */
	public function register(): void {
		add_action( 'admin_menu', [ $this, 'menu' ], 99 );
		add_action( 'admin_menu', [ $this, 'legacy_url' ], 100 );
		add_action( 'admin_notices', [ $this, 'dependency_notice' ] );
		add_action( 'admin_post_wcbqn_save_preferences', [ $this, 'save' ] );
		add_action( 'admin_post_wcbqn_save_site_settings', [ $this, 'save_site_settings' ] );
		add_action( 'admin_menu', [ $this, 'rename_admin_menu' ], 101 );
		add_action( 'admin_enqueue_scripts', [ $this, 'assets' ] );
	}

	/**
	 * Register or populate the appropriate administrative navigation.
	 *
	 * @return void No return value.
	 */
	public function menu(): void {
		if ( ! WCBQN_Config::allowed() ) { return; }
		$this->page_hook = self::under_wpcodebox()
			? (string) add_submenu_page( 'wpcodebox2', 'WPCodeBox QuickNav', __( 'QuickNav settings', 'wpcodebox-quicknav' ), 'manage_options', 'wpcodebox-quicknav', [ $this, 'page' ] )
			: (string) add_options_page( 'WPCodeBox QuickNav', 'WPCodeBox QuickNav', 'manage_options', 'wpcodebox-quicknav', [ $this, 'page' ] );
		if ( self::under_wpcodebox() ) {
			global $submenu;
			foreach ( $submenu['wpcodebox2'] ?? [] as $index => $item ) {
				if ( 'wpcodebox2' === ( $item[2] ?? '' ) ) { $submenu['wpcodebox2'][$index][0] = __( 'Snippets', 'wpcodebox-quicknav' ); break; }
			}
		}
	}

	/** Determine whether the supported parent owns a top-level menu. @return bool Parent menu mode. */
	public static function under_wpcodebox(): bool {
		return WCBQN_WPCodeBox_Adapter::available() && ! get_option( 'wpcb_show_in_tools', false );
	}

	/**
	 * Normalize the optional site-wide label without inventing a native default.
	 * @param mixed $value Submitted or saved menu label.
	 * @return string Plain-text label of at most sixty Unicode characters, or empty.
	 */
	public static function clean_admin_menu_name( $value ): string {
		return is_string( $value ) ? wp_html_excerpt( trim( sanitize_text_field( $value ) ), 60, '' ) : '';
	}

	/** Apply the site-wide label to the supported parent's existing main menu only. @return void Modifies the menu label, preserving its slug and permissions. */
	public function rename_admin_menu(): void {
		if ( ! self::under_wpcodebox() ) { return; }
		$name = self::clean_admin_menu_name( get_option( 'wcbqn_admin_menu_name', '' ) );
		if ( '' === $name ) { return; }
		global $menu;
		foreach ( $menu ?? [] as $index => $item ) {
			if ( 'wpcodebox2' === ( $item[2] ?? '' ) ) { $menu[$index][0] = esc_html( $name ); break; }
		}
	}

	/** Save the separate website setting after capability, method and nonce validation. @return void Redirects locally after saving; rejects unauthorized requests. */
	public function save_site_settings(): void {
		if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) && is_string( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) || ! WCBQN_Config::allowed() ) { wp_die( esc_html__( 'Permission denied.', 'wpcodebox-quicknav' ), '', [ 'response' => 403 ] ); }
		check_admin_referer( 'wcbqn_site_settings' );
		if ( isset( $_POST['admin_menu_name'] ) && is_string( $_POST['admin_menu_name'] ) ) {
			$name = self::clean_admin_menu_name( sanitize_text_field( wp_unslash( $_POST['admin_menu_name'] ) ) );
			update_option( 'wcbqn_admin_menu_name', $name, false );
		}
		wp_safe_redirect( add_query_arg( 'saved', '1', self::url() ) ); exit;
	}

	/** Build the current settings URL for toolbar, commands, saves and plugin links. @return string Local URL. */
	public static function url(): string {
		return add_query_arg( 'page', 'wpcodebox-quicknav', admin_url( self::under_wpcodebox() ? 'admin.php' : 'options-general.php' ) );
	}

	/** Keep bookmarked settings URLs usable after changing menu mode. @return void May redirect locally. */
	public function legacy_url(): void {
		global $pagenow;
		if ( ! WCBQN_Config::allowed() || ! isset( $_GET['page'] ) || ! is_string( $_GET['page'] ) || 'wpcodebox-quicknav' !== sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) { return; }
		$expected = self::under_wpcodebox() ? 'admin.php' : 'options-general.php';
		if ( in_array( $pagenow, [ 'admin.php', 'options-general.php' ], true ) && $pagenow !== $expected ) {
			wp_safe_redirect( self::url() ); exit;
		}
	}

	/**
	 * Enqueue local assets only in the relevant administrative context.
	 *
	 * @param string $hook Administrative screen hook suffix.
	 * @return void No return value.
	 */
	public function assets( string $hook ): void {
		if ( '' === $this->page_hook || $this->page_hook !== $hook ) { return; }
		{
			wp_enqueue_style( 'wcbqn-settings', plugins_url( 'assets/settings.css', WCBQN_PLUGIN_FILE ), [], WCBQN_Config::VERSION );
			wp_enqueue_script( 'wcbqn-settings', plugins_url( 'assets/settings.js', WCBQN_PLUGIN_FILE ), [], WCBQN_Config::VERSION, true );
		}
	}

	/**
	 * Validate a POST request and persist the current user’s site preferences.
	 *
	 * @return void No return value.
	 */
	public function save(): void {
		if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) && is_string( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) || ! WCBQN_Config::allowed() ) { wp_die( esc_html__( 'Permission denied.', 'wpcodebox-quicknav' ), '', [ 'response' => 403 ] ); }
		check_admin_referer( 'wcbqn_preferences' );
		$scripts = ( new WCBQN_WPCodeBox_Adapter() )->scripts();
		$ids = isset( $_POST['favorites'] ) && is_array( $_POST['favorites'] ) ? map_deep( wp_unslash( $_POST['favorites'] ), 'sanitize_text_field' ) : [];
		if ( count( array_unique( array_filter( $ids, 'is_scalar' ), SORT_REGULAR ) ) > 100 ) { wp_die( esc_html__( 'Please select no more than 100 favorites.', 'wpcodebox-quicknav' ) ); }
		$shortcuts = [];
		foreach ( [ 'shortcut_fullscreen', 'shortcut_focus' ] as $key ) {
			$shortcuts[$key] = isset( $_POST[$key] ) && is_string( $_POST[$key] ) ? WCBQN_Config::shortcut_key( sanitize_key( wp_unslash( $_POST[$key] ) ) ) : WCBQN_Config::shortcut_key( WCBQN_Config::preferences()[$key] );
		}
		if ( '' !== $shortcuts['shortcut_fullscreen'] && $shortcuts['shortcut_fullscreen'] === $shortcuts['shortcut_focus'] ) { wp_die( esc_html__( 'Choose different keys for fullscreen and editor focus, or disable one of them.', 'wpcodebox-quicknav' ) ); }
		if ( WCBQN_WPCodeBox_Adapter::available() ) { update_user_meta( get_current_user_id(), WCBQN_Config::key( 'favorites' ), WCBQN_Favorites::sanitize( $ids, $scripts ) ); }
		$limit = isset( $_POST['limit'] ) && is_scalar( $_POST['limit'] ) ? absint( $_POST['limit'] ) : 40;
		$toolbar_name = defined( 'WCBQN_NAME_IN_ADMINBAR' ) || ! isset( $_POST['toolbar_name'] ) ? WCBQN_Config::preferences()['toolbar_name'] : WCBQN_Config::clean_toolbar_name( is_string( $_POST['toolbar_name'] ) ? sanitize_text_field( wp_unslash( $_POST['toolbar_name'] ) ) : '' );

		update_user_meta( get_current_user_id(), WCBQN_Config::key( 'preferences' ), [ 'counter' => ! empty( $_POST['counter'] ), 'expert' => ! empty( $_POST['expert'] ), 'search' => ! empty( $_POST['search'] ), 'resources' => isset( $_POST['resources'] ) && is_string( $_POST['resources'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['resources'] ) ), 'limit' => max( 10, min( 200, $limit ) ), 'toolbar_name' => $toolbar_name, 'remember_view' => ! empty( $_POST['remember_view'] ) ] + $shortcuts );
		if ( empty( $_POST['remember_view'] ) ) { delete_user_meta( get_current_user_id(), WCBQN_Config::key( 'last_view' ) ); }
		wp_safe_redirect( add_query_arg( 'saved', '1', self::url() ) ); exit;
	}


	/**
	 * Explain the paused integration without interrupting administrative navigation.
	 *
	 * @param bool $inline Whether to render within the QuickNav settings screen.
	 * @return void Outputs an escaped notice for permitted administrators.
	 */
	public function dependency_notice( bool $inline = false ): void {
		if ( ! WCBQN_Config::allowed() || WCBQN_WPCodeBox_Adapter::available() ) { return; }
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $inline && ( ! $screen || ! in_array( $screen->id, [ 'plugins', 'plugins-network' ], true ) ) ) { return; }
		if ( ! function_exists( 'get_plugins' ) ) { require_once ABSPATH . 'wp-admin/includes/plugin.php'; }
		$installed = false;
		foreach ( get_plugins() as $plugin ) {
			if ( in_array( $plugin['Name'] ?? '', [ 'WPCodeBox', 'WPCodeBox 2' ], true ) ) { $installed = true; break; }
		}
		$message = $installed
			? __( 'WPCodeBox is installed but inactive or unavailable. Activate WPCodeBox 2 version 1.4.1 or newer to use snippet navigation. Display preferences remain available. QuickNav remains active and your saved preferences are kept.', 'wpcodebox-quicknav' )
			: __( 'WPCodeBox is not installed yet. Install and activate WPCodeBox 2 version 1.4.1 or newer to use snippet navigation. Display preferences remain available. QuickNav remains active and your saved preferences are kept.', 'wpcodebox-quicknav' );
		echo '<div class="notice notice-warning' . ( $inline ? ' inline' : '' ) . '"><p><strong>WPCodeBox QuickNav:</strong> ' . esc_html( $message ) . ' <a href="' . esc_url( self_admin_url( 'plugins.php' ) ) . '">' . esc_html__( 'Manage plugins', 'wpcodebox-quicknav' ) . '</a></p></div>';
	}


	/**
	 * Explain the active Safe Mode and the effective source of its setting.
	 *
	 * @return void Outputs a modest inline warning only when Safe Mode is active.
	 */
	public function safe_mode_notice(): void {
		if ( ! WCBQN_WPCodeBox_Adapter::available() || ! WCBQN_WPCodeBox_Adapter::safe_mode() ) { return; }
		$source = defined( 'WPCB_SAFE_MODE' )
			? __( 'Enabled by the WPCB_SAFE_MODE constant, usually set in wp-config.php. WPCodeBox enables Safe Mode whenever this constant is defined, including when its value is false.', 'wpcodebox-quicknav' )
			: __( 'Enabled for this editor request through safe_mode=1.', 'wpcodebox-quicknav' );
		echo '<div class="notice notice-warning inline wcbqn-safe-mode" role="status"><p><strong>' . esc_html__( 'WPCodeBox Safe Mode is active.', 'wpcodebox-quicknav' ) . '</strong> ' . esc_html( $source ) . '</p><p>' . esc_html__( 'Snippet execution is paused by WPCodeBox. QuickNav navigation and your preferences remain available.', 'wpcodebox-quicknav' ) . '</p></div>';
	}

	/**
	 * Match local documentation to the loaded interface language before request defaults.
	 *
	 * @return bool Whether the loaded host catalog or fallback request locale is German.
	 */
	public static function german(): bool {
		if ( function_exists( 'get_translations_for_domain' ) ) {
			$catalog = get_translations_for_domain( 'wpcodebox-quicknav' );
			$headers = $catalog->headers;
			$language = $headers['Language'] ?? $headers['language'] ?? '';
			if ( is_string( $language ) && '' !== $language ) { return 0 === strpos( strtolower( $language ), 'de' ); }
		}
		return 0 === strpos( strtolower( determine_locale() ), 'de' );
	}

	/**
	 * Resolve the localized user-guide URL.
	 *
	 * @return string URL of the corresponding language guide.
	 */
	public static function document_url(): string {
		return plugins_url( self::german() ? 'docs/guide-de.html' : 'docs/guide.html', WCBQN_PLUGIN_FILE );
	}

	/**
	 * Render the capability-protected personal settings screen.
	 *
	 * @return void No return value.
	 */
	public function page(): void {
		if ( ! WCBQN_Config::allowed() ) { return; }
		$nav = new WCBQN_Navigation_Builder( ( new WCBQN_WPCodeBox_Adapter() )->scripts() );
		$scripts = $nav->scripts(); $favorites = WCBQN_Favorites::ids( $scripts ); $prefs = WCBQN_Config::preferences();
		echo '<div class="wrap wcbqn-settings"><header class="wcbqn-page-heading">';
		{ echo '<img src="' . esc_url( plugins_url( 'assets/quicknav-icon.svg', WCBQN_PLUGIN_FILE ) ) . '" alt="" width="56" height="56">'; }
		echo '<div><h1>WPCodeBox QuickNav</h1><p>' . esc_html__( 'Your snippets. One click away.', 'wpcodebox-quicknav' ) . '</p></div></header><hr class="wp-header-end">';
		if ( isset( $_GET['saved'] ) ) { echo '<div class="notice notice-success inline"><p>' . esc_html__( 'Your settings have been saved.', 'wpcodebox-quicknav' ) . '</p></div>'; }
		if ( ! WCBQN_WPCodeBox_Adapter::available() ) {
			$this->dependency_notice( true );

		}
		$this->safe_mode_notice();
		echo '<p><a class="button" href="' . esc_url( WCBQN_Functionality::url() ) . '">' . esc_html__( 'Functionality Plugin overview', 'wpcodebox-quicknav' ) . '</a></p>';
		echo '<p>' . esc_html__( 'These preferences apply only to your account on this website. Constants in wp-config.php take precedence over display preferences.', 'wpcodebox-quicknav' ) . '</p>';
		if ( WCBQN_WPCodeBox_Adapter::available() ) { $this->statistics( $nav, count( $favorites ) ); }
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="wcbqn_save_preferences">';
		wp_nonce_field( 'wcbqn_preferences' );
		echo '<section class="wcbqn-card"><h2>' . esc_html__( 'Display preferences', 'wpcodebox-quicknav' ) . '</h2>';
		echo '<p><label for="wcbqn-toolbar-name">' . esc_html__( 'Toolbar entry name', 'wpcodebox-quicknav' ) . '</label><br><input class="regular-text" type="text" id="wcbqn-toolbar-name" name="toolbar_name" maxlength="60" value="' . esc_attr( WCBQN_Config::toolbar_name() ) . '" aria-describedby="wcbqn-toolbar-name-help"' . ( defined( 'WCBQN_NAME_IN_ADMINBAR' ) ? ' disabled' : '' ) . '></p><p id="wcbqn-toolbar-name-help" class="description">' . esc_html__( 'Personal name of the main toolbar entry on this website. Leave blank to restore WPCodeBox. The optional snippet count is appended automatically.', 'wpcodebox-quicknav' ) . '</p>';
		if ( defined( 'WCBQN_NAME_IN_ADMINBAR' ) ) { echo '<p class="description"><code>WCBQN_NAME_IN_ADMINBAR</code> · ' . esc_html__( 'This name is controlled by a configuration constant. Your saved personal name is retained.', 'wpcodebox-quicknav' ) . '</p>'; }
		foreach ( [ 'counter' => [ 'WCBQN_COUNTER', __( 'Show snippet count in the toolbar', 'wpcodebox-quicknav' ), WCBQN_Config::counter() ], 'expert' => [ 'WCBQN_EXPERT_MODE', __( 'Show developer links', 'wpcodebox-quicknav' ), WCBQN_Config::expert() ] ] as $key => $field ) {
			$locked = defined( $field[0] );
			echo '<label class="wcbqn-option"><input type="checkbox" name="' . esc_attr( $key ) . '" value="1" ' . checked( $field[2], true, false ) . ( $locked ? ' disabled' : '' ) . '> ' . esc_html( $field[1] ) . ( $locked ? ' <code>' . esc_html( $field[0] ) . '</code>' : '' ) . '</label>';
			if ( $locked && $prefs[$key] ) { echo '<input type="hidden" name="' . esc_attr( $key ) . '" value="1">'; }
		}
		echo '<label class="wcbqn-option"><input type="checkbox" name="search" value="1" ' . checked( WCBQN_Config::search(), true, false ) . '> ' . esc_html__( 'Show QuickNav search in WPCodeBox', 'wpcodebox-quicknav' ) . '</label>';
		echo '<label class="wcbqn-option"><input type="checkbox" name="resources" value="1" ' . checked( WCBQN_Config::resources(), true, false ) . '> ' . esc_html__( 'Show resource links in the toolbar', 'wpcodebox-quicknav' ) . '</label><p class="description">' . esc_html__( 'Controls Find snippets and Links for your account on this website. Safe Mode, snippet navigation and About remain available.', 'wpcodebox-quicknav' ) . '</p>';
		echo '<p><label for="wcbqn-limit">' . esc_html__( 'Menu entry limit', 'wpcodebox-quicknav' ) . '</label> <input id="wcbqn-limit" type="number" min="10" max="200" name="limit" value="' . esc_attr( WCBQN_Config::limit() ) . '"' . ( defined( 'WCBQN_MENU_LIMIT' ) ? ' disabled' : '' ) . '></p>';
		if ( defined( 'WCBQN_MENU_LIMIT' ) ) { echo '<input type="hidden" name="limit" value="' . esc_attr( $prefs['limit'] ) . '"><code>WCBQN_MENU_LIMIT</code>'; }
		echo '<p class="description">' . esc_html__( 'Applies to each status list, favorites, and the folder tree. Additional entries remain accessible in WPCodeBox.', 'wpcodebox-quicknav' ) . '</p></section>';
		echo '<section class="wcbqn-card wcbqn-shortcut-settings" aria-labelledby="wcbqn-shortcut-heading"><h2 id="wcbqn-shortcut-heading">' . esc_html__( 'Workspace keyboard shortcuts', 'wpcodebox-quicknav' ) . '</h2><p class="description">' . esc_html__( 'Use Control + Alt + Shift plus the selected letter (Control + Option + Shift on Mac). Personal to this website. Choose Off if your system, keyboard layout or assistive software uses the combination.', 'wpcodebox-quicknav' ) . '</p>';
		foreach ( [ 'shortcut_fullscreen' => __( 'Workspace fullscreen', 'wpcodebox-quicknav' ), 'shortcut_focus' => __( 'Editor focus', 'wpcodebox-quicknav' ) ] as $key => $label ) {
			echo '<p><label for="wcbqn-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label> <select id="wcbqn-' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '">';
			foreach ( [ '' => __( 'Off', 'wpcodebox-quicknav' ), 'f' => 'F', 'e' => 'E', 'j' => 'J', 'u' => 'U' ] as $value => $caption ) { echo '<option value="' . esc_attr( $value ) . '"' . selected( WCBQN_Config::shortcut_key( $prefs[$key] ), $value, false ) . '>' . esc_html( $caption ) . '</option>'; }
			echo '</select></p>';
		}
		echo '<label class="wcbqn-option"><input type="checkbox" name="remember_view" value="1" ' . checked( ! empty( $prefs['remember_view'] ), true, false ) . '> ' . esc_html__( 'Remember my last workspace view', 'wpcodebox-quicknav' ) . '</label><p class="description">' . esc_html__( 'Optional, for your account on this website. Reopen WPCodeBox in the last selected normal, fullscreen or editor-focus view. Snippet content and the native pane settings are not saved by QuickNav.', 'wpcodebox-quicknav' ) . '</p></section>';
		if ( WCBQN_WPCodeBox_Adapter::available() ) {
		echo '<section class="wcbqn-card"><h2>' . esc_html__( 'Personal favorites', 'wpcodebox-quicknav' ) . '</h2><p>' . esc_html__( 'Select up to 100 snippets. Deleted snippets disappear from the list automatically. The limit above controls how many favorites appear in the toolbar.', 'wpcodebox-quicknav' ) . '</p>';
		$this->favorites_preview( $nav, $favorites );
		echo '<h3>' . esc_html__( 'Choose favorites', 'wpcodebox-quicknav' ) . '</h3>';
		echo '<label class="wcbqn-filter-label" for="wcbqn-favorite-filter">' . esc_html__( 'Filter by title or folder', 'wpcodebox-quicknav' ) . '</label> <input type="search" id="wcbqn-favorite-filter" autocomplete="off"><p id="wcbqn-filter-status" role="status" aria-live="polite"></p><div class="wcbqn-favorite-list">';
		$count = 0;
		foreach ( $scripts as $id => $item ) {
			if ( 'folder' === $item['type'] ) { continue; } $count++;
			$title = $item['title'] ?: __( 'Untitled snippet', 'wpcodebox-quicknav' );
			echo '<label class="wcbqn-favorite" data-wcbqn-search="' . esc_attr( $title . ' ' . $nav->path( $item ) ) . '" data-wcbqn-id="' . esc_attr( $id ) . '"><input type="checkbox" name="favorites[]" value="' . esc_attr( $id ) . '" ' . checked( in_array( $id, $favorites, true ), true, false ) . '><span><strong>' . esc_html( $title ) . '</strong><small>' . esc_html( $nav->description( $item ) ) . '</small></span></label>';
		}
		if ( ! $count ) { echo '<p>' . esc_html__( 'No snippets yet. Create your first snippet in WPCodeBox.', 'wpcodebox-quicknav' ) . '</p>'; }
		echo '</div></section>'; } submit_button( __( 'Save my preferences', 'wpcodebox-quicknav' ) ); echo '</form>';
		echo '<section class="wcbqn-card" aria-labelledby="wcbqn-site-settings-title"><h2 id="wcbqn-site-settings-title">' . esc_html__( 'Website settings', 'wpcodebox-quicknav' ) . '</h2><p>' . esc_html__( 'These settings apply to all users of this website and are saved separately from personal preferences.', 'wpcodebox-quicknav' ) . '</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="wcbqn_save_site_settings">';
		wp_nonce_field( 'wcbqn_site_settings' );
		echo '<p><label for="wcbqn-admin-menu-name">' . esc_html__( 'Admin main menu name', 'wpcodebox-quicknav' ) . '</label><br><input type="text" class="regular-text" id="wcbqn-admin-menu-name" name="admin_menu_name" maxlength="60" value="' . esc_attr( self::clean_admin_menu_name( get_option( 'wcbqn_admin_menu_name', '' ) ) ) . '" aria-describedby="wcbqn-admin-menu-name-help"></p><p class="description" id="wcbqn-admin-menu-name-help">' . esc_html__( 'Optional name for the WPCodeBox main menu. Leave blank to keep its original name. Applies when WPCodeBox has its own main menu; the Snippets submenu and personal toolbar name stay separate.', 'wpcodebox-quicknav' ) . '</p>';
		submit_button( __( 'Save website settings', 'wpcodebox-quicknav' ), 'secondary', 'wcbqn-save-site-settings' );
		echo '</form></section>';
		$this->footer();
		
		echo '</div>';
	}

	/**
	 * Summarize saved snippet states separately from execution conditions.
	 *
	 * @param WCBQN_Navigation_Builder $nav Indexed navigation builder.
	 * @param int $favorites Selected favorite IDs or their total count.
	 * @return void No return value.
	 */
	private function statistics( WCBQN_Navigation_Builder $nav, int $favorites ): void {
		$stats = $nav->statistics();
		$metrics = [ 'total' => __( 'All snippets', 'wpcodebox-quicknav' ), 'active' => __( 'Active', 'wpcodebox-quicknav' ), 'inactive' => __( 'Inactive', 'wpcodebox-quicknav' ), 'folders' => __( 'Folders', 'wpcodebox-quicknav' ), 'favorites' => __( 'Saved favorites', 'wpcodebox-quicknav' ) ];
		$stats['favorites'] = $favorites;
		echo '<section class="wcbqn-card wcbqn-overview" aria-labelledby="wcbqn-overview-title"><h2 id="wcbqn-overview-title">' . esc_html__( 'Your snippet overview', 'wpcodebox-quicknav' ) . '</h2><dl class="wcbqn-stats">';
		foreach ( $metrics as $key => $label ) {
			echo '<div class="wcbqn-stat wcbqn-stat-' . esc_attr( $key ) . '"><dt>' . esc_html( $label ) . '</dt><dd>' . esc_html( $stats[$key] ) . '</dd></div>';
		}
		echo '</dl><div class="wcbqn-status-bar" aria-hidden="true"><span style="width:' . esc_attr( $stats['total'] ? round( 100 * $stats['active'] / $stats['total'], 2 ) : 0 ) . '%"></span></div><p class="description">' . esc_html__( 'Active and inactive refer to saved snippet status. Conditions, run type, Safe Mode and execution location can affect execution. WPCodeBox folders do not disable snippets.', 'wpcodebox-quicknav' ) . '</p>';
		if ( $stats['types'] ) {
			echo '<div class="wcbqn-type-summary" aria-label="' . esc_attr__( 'Snippet types', 'wpcodebox-quicknav' ) . '">';
			foreach ( $stats['types'] as $type => $count ) { echo '<span>' . esc_html( $type ) . ' <strong>' . esc_html( $count ) . '</strong></span>'; }
			echo '</div>';
		}
		echo '</section>';
	}

	/**
	 * Render safe direct-editor links for the favorites selection.
	 *
	 * @param WCBQN_Navigation_Builder $nav Indexed navigation builder.
	 * @param array $favorites Selected favorite IDs or their total count.
	 * @return void No return value.
	 */
	private function favorites_preview( WCBQN_Navigation_Builder $nav, array $favorites ): void {
		$scripts = $nav->scripts();
		echo '<div class="wcbqn-selected-heading"><h3>' . esc_html__( 'Your favorites', 'wpcodebox-quicknav' ) . '</h3><span id="wcbqn-selected-count" aria-live="polite">' . esc_html( count( $favorites ) ) . '</span></div><p class="description">' . esc_html__( 'Open a favorite directly from its card. This preview follows your selection; save to update the toolbar.', 'wpcodebox-quicknav' ) . '</p><div class="wcbqn-selected-grid" id="wcbqn-selected-grid">';
		// Server-render every safe link once. JavaScript only changes card visibility.
		foreach ( $scripts as $id => $item ) {
			if ( 'folder' === $item['type'] ) { continue; }
			$title = $item['title'] ?: __( 'Untitled snippet', 'wpcodebox-quicknav' );
			echo '<a class="wcbqn-selected-card" data-wcbqn-favorite-card="' . esc_attr( $id ) . '" href="' . esc_url( WCBQN_WPCodeBox_Adapter::url( $item['parent'], $id ) ) . '"' . ( in_array( $id, $favorites, true ) ? '' : ' hidden' ) . '><span class="wcbqn-selected-icon" aria-hidden="true">★</span><span><strong>' . esc_html( $title ) . '</strong><small>' . esc_html( $nav->description( $item ) ) . '</small></span><span aria-hidden="true">↗</span></a>';
		}
		echo '</div><p id="wcbqn-selected-empty" class="wcbqn-empty"' . ( $favorites ? ' hidden' : '' ) . '>' . esc_html__( 'No favorites selected yet. Choose snippets below to build your quick-access list.', 'wpcodebox-quicknav' ) . '</p>';
	}

	/**
	 * Render plugin information and the accessible local changelog.
	 *
	 * @return void No return value.
	 */
	public function footer(): void {
		$changelog = plugins_url( self::german() ? 'docs/changelog-de.txt' : 'docs/changelog.txt', WCBQN_PLUGIN_FILE );
		echo '<footer class="wcbqn-footer" aria-label="' . esc_attr__( 'Plugin information', 'wpcodebox-quicknav' ) . '"><div><strong>WPCodeBox QuickNav</strong> <span>' . esc_html__( 'Version', 'wpcodebox-quicknav' ) . ' ' . esc_html( WCBQN_Config::VERSION ) . '</span> · <a href="' . esc_url( $changelog ?: self::document_url() ) . '" data-wcbqn-changelog>' . esc_html__( 'Changelog', 'wpcodebox-quicknav' ) . '</a> · <a href="' . esc_url( self::document_url() ) . '">' . esc_html__( 'Documentation', 'wpcodebox-quicknav' ) . '</a><p>' . esc_html__( 'Your snippets. One click away.', 'wpcodebox-quicknav' ) . '</p></div><div><span>© 2026 <a href="https://deckerwebplugins.com/' . ( self::german() ? 'de/' : 'en/' ) . '" target="_blank" rel="noopener noreferrer">David Decker – DECKERWEB</a></span><a href="https://deckerwebplugins.com/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Plugin website', 'wpcodebox-quicknav' ) . '</a></div></footer>';
		{ $file = dirname( WCBQN_PLUGIN_FILE ) . '/docs/' . ( self::german() ? 'changelog-de.txt' : 'changelog.txt' ); $content = is_readable( $file ) ? file_get_contents( $file ) : ''; }
		echo '<dialog id="wcbqn-changelog" hidden aria-labelledby="wcbqn-changelog-title"><header><h2 id="wcbqn-changelog-title">WPCodeBox QuickNav · ' . esc_html__( 'Changelog', 'wpcodebox-quicknav' ) . '</h2><form method="dialog"><button class="button" type="submit" value="close" data-wcbqn-close autofocus>' . esc_html__( 'Close', 'wpcodebox-quicknav' ) . '</button></form></header><div class="ddw-changelog-content" tabindex="0">' . wp_kses_post( Deckerweb_Changelog_Renderer_V1::render( $content ) ) . '</div></dialog>';
	}
}
