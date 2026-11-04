<?php
/** Optional workspace views without recreating the upstream editor. */
defined( 'ABSPATH' ) || exit;

/** Enqueue display-only controls on the supported WPCodeBox editor. */
final class WCBQN_Workspace {
	/** Register scoped assets. @return void */
	public function register(): void {
		add_action( 'admin_enqueue_scripts', [ $this, 'assets' ] );
		add_action( 'wp_ajax_wcbqn_workspace_view', [ $this, 'save_view' ] );
	}

	/** Save only the opted-in user's current-site view. @return void Returns a JSON response after POST, capability, nonce and value checks. */
	public function save_view(): void {
		$method = isset( $_SERVER['REQUEST_METHOD'] ) && is_string( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
		if ( 'POST' !== $method || ! WCBQN_Config::allowed() || ! WCBQN_WPCodeBox_Adapter::available() ) { wp_send_json_error( [], 403 ); }
		check_ajax_referer( 'wcbqn_workspace_view', 'nonce' );
		$value = isset( $_POST['view'] ) && is_string( $_POST['view'] ) ? sanitize_text_field( wp_unslash( $_POST['view'] ) ) : 'invalid';
		if ( ! in_array( $value, [ '', 'full', 'focus' ], true ) ) { wp_send_json_error( [], 400 ); }
		$prefs = WCBQN_Config::preferences();
		if ( empty( $prefs['remember_view'] ) ) { wp_send_json_error( [], 409 ); }
		update_user_meta( get_current_user_id(), WCBQN_Config::key( 'last_view' ), $value );
		wp_send_json_success();
	}

	/**
	 * Load local controls independently of the optional QuickNav search.
	 * @param string $hook Current WordPress screen suffix.
	 * @return void No output on unrelated screens or unsupported integrations.
	 */
	public function assets( string $hook ): void {
		if ( ! in_array( $hook, [ 'tools_page_wpcodebox2', 'toplevel_page_wpcodebox2' ], true ) || ! WCBQN_Config::allowed() || ! WCBQN_WPCodeBox_Adapter::available() ) { return; }
		wp_enqueue_style( 'wcbqn-workspace', plugins_url( 'assets/workspace.css', WCBQN_PLUGIN_FILE ), [], WCBQN_Config::VERSION );
		wp_enqueue_script( 'wcbqn-workspace', plugins_url( 'assets/workspace.js', WCBQN_PLUGIN_FILE ), [], WCBQN_Config::VERSION, true );
		$records = ( new WCBQN_WPCodeBox_Adapter() )->scripts();
		$types = [];
		foreach ( [ 'php', 'js', 'ex_js', 'css', 'ex_css', 'scss', 'scssp', 'less', 'html', 'json', 'txt', 'md' ] as $type ) { $types[$type] = WCBQN_Navigation_Builder::type( $type ); }
		wp_localize_script( 'wcbqn-workspace', 'WCBQNWorkspace', [
			'rememberView' => ! empty( WCBQN_Config::preferences()['remember_view'] ),
			'lastView' => WCBQN_Config::remembered_view(),
			'viewNonce' => wp_create_nonce( 'wcbqn_workspace_view' ),
			'viewSaveError' => __( 'The view changed, but could not be remembered. Please try again.', 'wpcodebox-quicknav' ),
			'openView' => __( 'Open view', 'wpcodebox-quicknav' ),
			'copyLink' => __( 'Copy snippet link', 'wpcodebox-quicknav' ),
			'copiedLink' => __( 'Snippet link copied.', 'wpcodebox-quicknav' ),
			'copyLinkHelp' => __( 'Save a local snippet before copying its link.', 'wpcodebox-quicknav' ),
			'linkLabel' => __( 'Link to the current snippet', 'wpcodebox-quicknav' ),
			'copyLinkError' => __( 'Copy the selected link manually.', 'wpcodebox-quicknav' ),
			'editorUrl' => WCBQN_WPCodeBox_Adapter::url(),
			'shortcuts' => [ 'full' => WCBQN_Config::shortcut_key( WCBQN_Config::preferences()['shortcut_fullscreen'] ), 'focus' => WCBQN_Config::shortcut_key( WCBQN_Config::preferences()['shortcut_focus'] ) ],
			'help' => __( 'Keyboard shortcuts', 'wpcodebox-quicknav' ),
			'close' => __( 'Close', 'wpcodebox-quicknav' ),
			'helpQuickNav' => __( 'QuickNav views', 'wpcodebox-quicknav' ),
			'helpNative' => __( 'WPCodeBox and editor', 'wpcodebox-quicknav' ),
			'helpNote' => __( 'Native shortcuts depend on the current editor and context. QuickNav does not change them. Customize or disable QuickNav shortcuts in your personal settings.', 'wpcodebox-quicknav' ),
			'helpDocs' => __( 'Complete editor shortcut reference', 'wpcodebox-quicknav' ),
			'helpPreferences' => __( 'Customize QuickNav shortcuts', 'wpcodebox-quicknav' ),
			'preferencesUrl' => WCBQN_Settings::url(),
			'off' => __( 'Off', 'wpcodebox-quicknav' ),
			'saveSnippet' => __( 'Save snippet', 'wpcodebox-quicknav' ),
			'formatCode' => __( 'Format code', 'wpcodebox-quicknav' ),
			'runSnippet' => __( 'Run snippet', 'wpcodebox-quicknav' ),
			'toggleSettings' => __( 'Toggle snippet settings', 'wpcodebox-quicknav' ),
			'toggleList' => __( 'Toggle snippet list', 'wpcodebox-quicknav' ),
			'togglePanes' => __( 'Toggle both side panes', 'wpcodebox-quicknav' ),
			'findCode' => __( 'Find in code', 'wpcodebox-quicknav' ),
			'commandPalette' => __( 'Editor command palette', 'wpcodebox-quicknav' ),
			'lineComment' => __( 'Toggle line comment', 'wpcodebox-quicknav' ),
			'localSnippetIds' => array_values( array_filter( array_map( 'intval', array_keys( $records ) ),
				/** Keep only known local snippet IDs; folder IDs are negative. @param int $id Metadata identity. @return bool Whether the ID belongs to a snippet. */
				static fn( int $id ): bool => $id > 0
			) ),
			'types' => $types, 'favorites' => WCBQN_Favorites::ids( $records ),
			'ajaxUrl' => admin_url( 'admin-ajax.php' ), 'favoriteNonce' => wp_create_nonce( 'wcbqn_search_favorite' ),
			'untitled' => __( 'Untitled snippet', 'wpcodebox-quicknav' ),
			'newSnippet' => __( 'New snippet', 'wpcodebox-quicknav' ),
			'addFavorite' => __( 'Add to favorites', 'wpcodebox-quicknav' ),
			'removeFavorite' => __( 'Remove from favorites', 'wpcodebox-quicknav' ),
			'favoriteSaved' => __( 'Favorites updated.', 'wpcodebox-quicknav' ),
			'favoriteError' => __( 'The favorite could not be saved. Please try again.', 'wpcodebox-quicknav' ),
			'favoriteHelp' => __( 'Save a local snippet before adding it to favorites.', 'wpcodebox-quicknav' ),
			'fullscreen' => __( 'Workspace fullscreen', 'wpcodebox-quicknav' ),
			'focus' => __( 'Editor focus', 'wpcodebox-quicknav' ),
			'exit' => __( 'Back to normal view', 'wpcodebox-quicknav' ),
			'label' => __( 'WPCodeBox workspace views', 'wpcodebox-quicknav' ),
			'normal' => __( 'Normal view restored.', 'wpcodebox-quicknav' ),
			'active' => __( 'Press Escape or use Back to normal view to leave this view. Native editor controls remain available.', 'wpcodebox-quicknav' ),
			'overview' => __( 'Functionality Plugin', 'wpcodebox-quicknav' ), 'overviewUrl' => WCBQN_Functionality::url(),
		] );
	}
}
