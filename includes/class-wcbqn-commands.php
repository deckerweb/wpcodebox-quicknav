<?php
/** Adapted from Advanced Scripts QuickNav 1.3.2, David Decker, GPL-2.0-or-later. See docs/credits.txt. */
/** Native WordPress Command Palette navigation for QuickNav and WPCodeBox. */
defined( 'ABSPATH' ) || exit;

/** Register permission-scoped navigation commands without loading a second palette. */
final class WCBQN_Commands {
	/** Attach late enough to detect the native palette's enqueue state. @return void */
	public function register(): void {
		add_action( 'admin_enqueue_scripts', [ $this, 'assets' ], 100 );
		add_action( 'enqueue_block_editor_assets', [ $this, 'assets' ], 100 );
	}

	/** Load only alongside an existing native palette on website admin screens. @return void */
	public function assets(): void {
		if ( ! WCBQN_Config::allowed() || is_network_admin() || ! wp_script_is( 'wp-commands', 'enqueued' ) || wp_script_is( 'wcbqn-commands', 'enqueued' ) ) { return; }
		wp_enqueue_script( 'wcbqn-commands', plugins_url( 'assets/commands.js', WCBQN_PLUGIN_FILE ), [ 'wp-commands', 'wp-data', 'wp-dom-ready' ], WCBQN_Config::VERSION . '.' . filemtime( dirname( WCBQN_PLUGIN_FILE ) . '/assets/commands.js' ), true );
		wp_localize_script( 'wcbqn-commands', 'WCBQNCommands', [ 'items' => $this->items() ] );
	}

	/**
	 * Build bounded navigation metadata for the current user and website.
	 * @return array Command definitions containing labels and local admin URLs only.
	 */
	public function items(): array {
		if ( ! WCBQN_Config::allowed() ) { return []; }
		$items = [ [ 'name' => 'wcbqn/settings', 'label' => __( 'QuickNav: Open settings', 'wpcodebox-quicknav' ), 'url' => WCBQN_Settings::url() ] ];
		$items[] = [ 'name' => 'wcbqn/functionality', 'label' => __( 'Functionality Plugin overview', 'wpcodebox-quicknav' ), 'url' => WCBQN_Functionality::url() ];
		if ( ! WCBQN_WPCodeBox_Adapter::available() ) { return $items; }
		$items[] = [ 'name' => 'wcbqn/manager', 'label' => __( 'WPCodeBox: Open snippet manager', 'wpcodebox-quicknav' ), 'url' => WCBQN_WPCodeBox_Adapter::url() ];
		$items[] = [ 'name' => 'wcbqn/new-script', 'label' => __( 'WPCodeBox: Add new snippet', 'wpcodebox-quicknav' ), 'url' => WCBQN_WPCodeBox_Adapter::url( 0, 0 ) ];
		if ( WCBQN_Config::search() ) { $items[] = [ 'name' => 'wcbqn/search', 'label' => __( 'WPCodeBox: Search all snippets', 'wpcodebox-quicknav' ), 'url' => WCBQN_WPCodeBox_Adapter::url() . '#wcbqn-search-query', 'focusSearch' => true ]; }
		$saved = get_user_meta( get_current_user_id(), WCBQN_Config::key( 'favorites' ), true );
		if ( ! is_array( $saved ) || ! $saved ) { return $items; }
		$scripts = ( new WCBQN_WPCodeBox_Adapter() )->scripts();
		foreach ( array_slice( WCBQN_Favorites::sanitize( $saved, $scripts ), 0, WCBQN_Config::limit() ) as $id ) {
			$item = $scripts[$id];
			$items[] = [ 'name' => 'wcbqn/favorite-' . $id,
				/* translators: %s: personal favorite script title. */
				'label' => sprintf( __( 'WPCodeBox: Open favorite — %s', 'wpcodebox-quicknav' ), $item['title'] ?: __( 'Untitled snippet', 'wpcodebox-quicknav' ) ),
				'url' => WCBQN_WPCodeBox_Adapter::url( $item['parent'], $id ) ];
		}
		return $items;
	}
}
