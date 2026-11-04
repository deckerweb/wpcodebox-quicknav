<?php
/** Adapted from Advanced Scripts QuickNav 1.3.2, David Decker, GPL-2.0-or-later. See docs/credits.txt. */
/** Hook registration; adapters are resolved only after active plugins have loaded. */
defined( 'ABSPATH' ) || exit;
/** Register the host lifecycle and lazily resolve navigation metadata. */
final class DDW_WPCodeBox_QuickNav {
	private ?WCBQN_Admin_Bar_Renderer $renderer = null;

	/**
	 * Initialize the component and its request-local dependencies.
	 *
	 * @return void No return value.
	 */
	public function __construct() {
		add_action( 'admin_bar_menu', [ $this, 'menu' ], 999 );
		add_action( 'admin_enqueue_scripts', [ $this, 'assets' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'assets' ] );
		add_filter( 'debug_information', [ $this, 'diagnostics' ], 9 );
		( new WCBQN_Settings() )->register();
		( new WCBQN_Functionality() )->register();
		( new WCBQN_Workspace() )->register();
		{ ( new WCBQN_Search() )->register(); ( new WCBQN_Commands() )->register(); }
		{
			add_action( 'init', [ $this, 'updates' ] );
			add_action( 'init', [ $this, 'translations' ], 0 );
			add_filter( 'plugin_action_links_' . plugin_basename( WCBQN_PLUGIN_FILE ), [ $this, 'links' ] );
			add_filter( 'plugin_row_meta', [ $this, 'meta' ], 10, 2 );
		}
	}

	/**
	 * Lazily construct request-local metadata navigation.
	 *
	 * @return WCBQN_Admin_Bar_Renderer Request-local toolbar renderer.
	 */
	private function renderer(): WCBQN_Admin_Bar_Renderer {
		if ( null === $this->renderer ) { $this->renderer = new WCBQN_Admin_Bar_Renderer( new WCBQN_Navigation_Builder( ( new WCBQN_WPCodeBox_Adapter() )->scripts() ) ); }
		return $this->renderer;
	}

	/**
	 * Load the existing host textdomain.
	 *
	 * @return void No return value.
	 */
	public function translations(): void { load_plugin_textdomain( 'wpcodebox-quicknav', false, dirname( plugin_basename( WCBQN_PLUGIN_FILE ) ) . '/languages' ); }

	/**
	 * Register or populate the appropriate administrative navigation.
	 *
	 * @param WP_Admin_Bar $bar WordPress admin-bar object to populate.
	 * @return void No return value.
	 */
	public function menu( $bar ): void { if ( ! WCBQN_Config::visible() ) { return; } $this->renderer()->add_admin_bar_menu( $bar ); WCBQN_Menu_Icons::apply( $bar ); }

	/**
	 * Enqueue local assets only in the relevant administrative context.
	 *
	 * @return void No return value.
	 */
	public function assets(): void {
		if ( ! WCBQN_Config::visible() ) { return; }
		$this->renderer()->enqueue_admin_bar_styles();
		{ wp_enqueue_style( 'wcbqn-toolbar', plugins_url( 'assets/toolbar.css', WCBQN_PLUGIN_FILE ), [ 'admin-bar' ], WCBQN_Config::VERSION ); }
	}

	/**
	 * Extend Site Health with non-sensitive configuration information.
	 *
	 * @param array $info Existing Site Health data.
	 * @return array Existing Site Health data plus QuickNav diagnostics.
	 */
	public function diagnostics( array $info ): array { return $this->renderer()->site_health_debug_info( $info ); }

	/**
	 * Load and register the host’s pinned GitHub update adapter.
	 *
	 * @return void No return value.
	 */
	public function updates(): void {
		require_once dirname( WCBQN_PLUGIN_FILE ) . '/includes/class-wcbqn-github-updates.php';
		( new \Deckerweb\WPCodeBoxQuickNav\GitHubUpdates() )->boot();
	}

	/**
	 * Prepend the permitted settings action link.
	 *
	 * @param array $links Existing plugin-row links to preserve.
	 * @return array Existing links with the permitted settings shortcut prepended.
	 */
	public function links( array $links ): array {
		if ( WCBQN_Config::allowed() ) { array_unshift( $links, '<a href="' . esc_url( WCBQN_Settings::url() ) . '">' . esc_html__( 'Settings', 'wpcodebox-quicknav' ) . '</a>' ); }
		return $links;
	}

	/**
	 * Extend the plugin-row information links.
	 *
	 * @param array $links Existing plugin-row links to preserve.
	 * @param string $file Installed plugin basename.
	 * @return array Filtered plugin-row information links.
	 */
	public function meta( array $links, string $file ): array {
		if ( $file !== plugin_basename( WCBQN_PLUGIN_FILE ) ) { return $links; }
		$links[] = '<a href="' . esc_url( WCBQN_Settings::document_url() ) . '">' . esc_html__( 'Documentation', 'wpcodebox-quicknav' ) . '</a>';
		$links[] = '<a href="https://ko-fi.com/deckerweb" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Support the project', 'wpcodebox-quicknav' ) . '</a>';
		$links[] = '<a href="https://eepurl.com/gbAUUn" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Newsletter', 'wpcodebox-quicknav' ) . '</a>';
		/**
		 * Filter plugin-row metadata links. Return the replacement link array.
		 *
		 * @since 1.0.0
		 * @param array $links Existing link definitions.
		 */
		return apply_filters( 'wcbqn_pluginrow_meta', $links );
	}
}
