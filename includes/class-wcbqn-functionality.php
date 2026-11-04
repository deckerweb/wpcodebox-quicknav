<?php
/** Read-only inventory for managed WPCodeBox Functionality Plugins. */
defined( 'ABSPATH' ) || exit;

/** Discover generated plugins without loading their PHP or changing their state. */
final class WCBQN_Functionality {
	/** Actual screen suffix returned by WordPress for the selected parent. */
	private string $page_hook = '';
	/** Register the independent overview. @return void */
	public function register(): void {
		add_action( 'admin_menu', [ $this, 'menu' ], 99 );
		add_action( 'admin_enqueue_scripts', [ $this, 'assets' ] );
		add_action( 'admin_post_wcbqn_refresh_functionality', [ $this, 'refresh' ] );
	}

	/** Register a site-local overview under the available parent. @return void */
	public function menu(): void {
		if ( ! WCBQN_Config::allowed() ) { return; }
		$this->page_hook = (string) add_submenu_page( WCBQN_Settings::under_wpcodebox() ? 'wpcodebox2' : 'options-general.php', __( 'Functionality Plugin', 'wpcodebox-quicknav' ), __( 'Functionality Plugin', 'wpcodebox-quicknav' ), 'manage_options', 'wcbqn-functionality', [ $this, 'page' ] );
	}

	/** Resolve the current overview URL. @return string Local administrative URL. */
	public static function url(): string {
		return add_query_arg( 'page', 'wcbqn-functionality', admin_url( WCBQN_Settings::under_wpcodebox() ? 'admin.php' : 'options-general.php' ) );
	}

	/** Enqueue the shared presentation on this screen only. @param string $hook Screen suffix. @return void */
	public function assets( string $hook ): void {
		if ( '' === $this->page_hook || $this->page_hook !== $hook || ! WCBQN_Config::allowed() ) { return; }
		wp_enqueue_style( 'wcbqn-settings', plugins_url( 'assets/settings.css', WCBQN_PLUGIN_FILE ), [], WCBQN_Config::VERSION );
		wp_enqueue_script( 'wcbqn-settings', plugins_url( 'assets/settings.js', WCBQN_PLUGIN_FILE ), [], WCBQN_Config::VERSION, true );
		wp_enqueue_script( 'wcbqn-functionality', plugins_url( 'assets/functionality.js', WCBQN_PLUGIN_FILE ), [], WCBQN_Config::VERSION, true );
		wp_localize_script( 'wcbqn-functionality', 'WCBQNFunctionality', [
			/* translators: 1: visible entries, 2: all entries. */
			'shown' => __( '%1$d of %2$d entries shown.', 'wpcodebox-quicknav' ),
			'copied' => __( 'File path copied.', 'wpcodebox-quicknav' ),
			'copyError' => __( 'Automatic copying is unavailable. Select and copy the path below.', 'wpcodebox-quicknav' ),
			'pathLabel' => __( 'File path to copy', 'wpcodebox-quicknav' ),
		] );
	}

	/**
	 * Read a bounded local file, rejecting symlinks outside the supplied root.
	 * @param string $root Allowed directory.
	 * @param string $relative Relative path without traversal.
	 * @return string File contents, or an empty string when unavailable.
	 */
	public static function read( string $root, string $relative ): string {
		if ( ! preg_match( '~\A[a-zA-Z0-9_./-]+\z~D', $relative ) || str_contains( $relative, '..' ) || str_starts_with( $relative, '/' ) ) { return ''; }
		$base = realpath( $root ); $path = realpath( $root . '/' . $relative );
		if ( ! $base || ! $path || ! str_starts_with( $path, $base . DIRECTORY_SEPARATOR ) || ! is_file( $path ) || ! is_readable( $path ) || filesize( $path ) > 1048576 ) { return ''; }
		$value = file_get_contents( $path );
		return is_string( $value ) ? $value : '';
	}

	/**
	 * Discover structurally recognized managed plugins once per request.
	 * Activation comes from WordPress, independently of directory existence.
	 * @return array Plugin identities and actual site/network activation states.
	 */
	public static function plugins(): array {
		if ( ! WCBQN_Config::allowed() || is_network_admin() ) { return []; }
		static $cache = [];
		$site = get_current_blog_id();
		if ( isset( $cache[$site] ) ) { return $cache[$site]; }
		if ( ! function_exists( 'get_plugins' ) ) { require_once ABSPATH . 'wp-admin/includes/plugin.php'; }
		$result = []; $active = (array) get_option( 'active_plugins', [] ); $network = is_multisite() ? (array) get_site_option( 'active_sitewide_plugins', [] ) : [];
		foreach ( get_plugins() as $file => $data ) {
			if ( 'plugin.php' !== basename( $file ) || '.' === dirname( $file ) ) { continue; }
			$root = WP_PLUGIN_DIR . '/' . dirname( $file );
			if ( ! is_readable( $root . '/signatures.php' ) || ! is_readable( $root . '/WFPCore/Preconditions.php' ) ) { continue; }
			$main = self::read( $root, 'plugin.php' );
			if ( ! str_contains( $main, 'WFPCore/Preconditions.php' ) || ! str_contains( $main, 'Snippets will go before this line' ) ) { continue; }
			$result[$file] = [ 'file' => $file, 'name' => $data['Name'], 'version' => $data['Version'], 'root' => $root, 'main' => $main, 'network' => isset( $network[$file] ), 'active' => isset( $network[$file] ) || in_array( $file, $active, true ) ];
		}
		return $cache[$site] = $result;
	}

	/**
	 * Parse only literal snippet includes and whole-line disabled includes.
	 * No PHP is evaluated; unsupported expressions are deliberately excluded.
	 * @param string $source Main plugin source.
	 * @return array Relative snippet paths and configured inclusion flags.
	 */
	public static function components( string $source ): array {
		$tokens = token_get_all( $source ); $result = []; $total = count( $tokens );
		foreach ( $tokens as $i => $token ) {
			if ( ! is_array( $token ) ) { continue; }
			if ( T_COMMENT === $token[0] && preg_match( '~\A//\s*include_once\s+[\'\"](snippets/[a-zA-Z0-9_./-]+)[\'\"];\s*\z~D', trim( $token[1] ), $match ) ) {
				if ( ! str_contains( $match[1], '..' ) && ! isset( $result[$match[1]] ) ) { $result[$match[1]] = false; }
			}
			if ( T_INCLUDE_ONCE !== $token[0] ) { continue; }
			$j = $i + 1;
			while ( $j < $total && is_array( $tokens[$j] ) && T_WHITESPACE === $tokens[$j][0] ) { $j++; }
			if ( ! isset( $tokens[$j] ) || ! is_array( $tokens[$j] ) || T_CONSTANT_ENCAPSED_STRING !== $tokens[$j][0] ) { continue; }
			$literal = $tokens[$j][1]; $path = substr( $literal, 1, -1 ); $j++;
			while ( $j < $total && is_array( $tokens[$j] ) && T_WHITESPACE === $tokens[$j][0] ) { $j++; }
			if ( ';' === ( $tokens[$j] ?? null ) && preg_match( '~\Asnippets/[a-zA-Z0-9_./-]+\z~D', $path ) && ! str_contains( $path, '..' ) ) { $result[$path] = true; }
		}
		return $result;
	}

	/**
	 * Associate signed snippet IDs with metadata using the active upstream filename helper.
	 * Only unambiguous filenames with an ID in the generated signature manifest are linked.
	 * @param array $plugin Recognized generated plugin.
	 * @param array $paths Current configured component paths.
	 * @return array Source-free metadata indexed by generated relative path.
	 */
	private static function metadata( array $plugin, array $paths ): array {
		if ( ! WCBQN_WPCodeBox_Adapter::available() || ! class_exists( \Wpcb2\FunctionalityPlugin\Service\Slugifier::class ) ) { return []; }
		$raw = self::read( $plugin['root'], 'signatures.php' );
		// The manifest starts with a PHP guard and then a JSON object; never include it.
		$offset = strpos( $raw, "\n" );
		$signatures = false === $offset ? [] : json_decode( substr( $raw, $offset + 1 ), true );
		if ( ! is_array( $signatures ) ) { return []; }
		$records = ( new WCBQN_WPCodeBox_Adapter() )->scripts();
		$slugger = new \Wpcb2\FunctionalityPlugin\Service\Slugifier(); $candidates = [];
		foreach ( $records as $id => $record ) {
			if ( $id < 1 || ! isset( $signatures[$id] ) ) { continue; }
			$name = $slugger->slugify( $record['title'] ); $folder = $records[$record['parent']]['title'] ?? '';
			$base = 'snippets/' . ( '' !== $folder ? $slugger->slugify( $folder ) . '/' : '' ) . $name;
			$extension = in_array( $record['type'], [ 'scss', 'less' ], true ) ? 'css' : $record['type'];
			foreach ( [ $base . '.php', $base . '_enqueue_' . $extension . '.php', $base . '_inline_' . $extension . '.php' ] as $path ) {
				if ( ! array_key_exists( $path, $paths ) ) { continue; }
				$candidates[$path][] = [ 'id' => $id, 'title' => sanitize_text_field( $record['title'] ), 'type' => sanitize_key( $record['type'] ), 'folder' => sanitize_text_field( $folder ), 'run' => sanitize_key( $record['run'] ), 'location' => sanitize_key( $record['location'] ) ];
			}
		}
		$result = [];
		foreach ( $candidates as $path => $items ) { if ( 1 === count( $items ) ) { $result[$path] = $items[0]; } }
		return $result;
	}

	/**
	 * Produce a current inventory and retain a dated, site-scoped metadata snapshot.
	 * Called only on the authorized overview, never on ordinary front-end requests.
	 * @param array $plugin Recognized generated plugin.
	 * @param bool $refresh Force a dated metadata capture, or recheck offline files without replacing source metadata.
	 * @return array Components, metadata, snapshot and source-availability information.
	 */
	public static function inventory( array $plugin, bool $refresh = false ): array {
		$paths = self::components( $plugin['main'] );
		$key = 'wcbqn_fp_snapshot_' . substr( hash( 'sha256', $plugin['file'] ), 0, 20 );
		$snapshot = get_option( $key, [] ); $snapshot = is_array( $snapshot ) ? $snapshot : [];
		$fingerprint = hash( 'sha256', $plugin['main'] . self::read( $plugin['root'], 'signatures.php' ) );
		$source = WCBQN_WPCodeBox_Adapter::available();
		if ( $refresh ) { $snapshot['checked'] = time(); }
		if ( $source ) {
			$metadata = self::metadata( $plugin, $paths );
			if ( $refresh || ( $snapshot['metadata'] ?? null ) !== $metadata || ( $snapshot['fingerprint'] ?? '' ) !== $fingerprint ) {
				$snapshot = array_merge( $snapshot, [ 'time' => time(), 'fingerprint' => $fingerprint, 'metadata' => $metadata ] );
				update_option( $key, $snapshot, false );
			}
		} else { $metadata = (array) ( $snapshot['metadata'] ?? [] ); }
		if ( $refresh && ! $source ) { update_option( $key, $snapshot, false ); }
		$changed = ! empty( $snapshot['fingerprint'] ) && ( $snapshot['fingerprint'] ?? '' ) !== $fingerprint;
		return compact( 'paths', 'metadata', 'snapshot', 'source', 'changed' );
	}

	/**
	 * Describe the current operating mode without claiming actual snippet execution.
	 * @param bool $source Whether the supported WPCodeBox 2 integration is available.
	 * @param bool $active Actual WordPress activation of the generated plugin.
	 * @param bool $safe Whether the generated plugin's global Safe Mode applies.
	 * @return array Localized mode label, explanation and visual severity.
	 */
	public static function operating_state( bool $source, bool $active, bool $safe ): array {
		if ( $active && $safe ) { return [ 'label' => __( 'Safe Mode: execution paused', 'wpcodebox-quicknav' ), 'text' => __( 'The Functionality Plugin is active, but WPCB_SAFE_MODE pauses its generated snippet execution. Defining this constant also enables Safe Mode when its value is false.', 'wpcodebox-quicknav' ), 'level' => 'warning' ]; }
		if ( ! $active ) { return [ 'label' => __( 'Functionality Plugin inactive', 'wpcodebox-quicknav' ), 'text' => __( 'WordPress does not load the configured includes through this plugin. This status does not describe code provided by other plugins.', 'wpcodebox-quicknav' ), 'level' => 'inactive' ]; }
		if ( $source ) { return [ 'label' => __( 'WPCodeBox 2 and Functionality Plugin active', 'wpcodebox-quicknav' ), 'text' => __( 'WPCodeBox 2 is available for managing snippets; the generated plugin is active in WordPress. Configured includes are listed below.', 'wpcodebox-quicknav' ), 'level' => 'ready' ]; }
		return [ 'label' => __( 'Functionality Plugin active without WPCodeBox 2', 'wpcodebox-quicknav' ), 'text' => __( 'The generated plugin remains active independently. Activate supported WPCodeBox 2 to manage individual snippets; saved metadata remains available here.', 'wpcodebox-quicknav' ), 'level' => 'standalone' ];
	}

	/**
	 * Offer a local next action without changing plugin or snippet state.
	 * @param bool $source Whether supported WPCodeBox management is available.
	 * @param bool $active Whether the generated plugin is WordPress-active.
	 * @return array Localized label, action label and safe local destination.
	 */
	public static function management_state( bool $source, bool $active ): array {
		if ( $source ) { return [ 'label' => __( 'Management through WPCodeBox available', 'wpcodebox-quicknav' ), 'action' => __( 'Manage snippets in WPCodeBox', 'wpcodebox-quicknav' ), 'url' => WCBQN_WPCodeBox_Adapter::url() ]; }
		return [ 'label' => $active ? __( 'Standalone operation', 'wpcodebox-quicknav' ) : __( 'WPCodeBox management unavailable', 'wpcodebox-quicknav' ), 'action' => __( 'Manage plugins', 'wpcodebox-quicknav' ), 'url' => self_admin_url( 'plugins.php' ) ];
	}

	/**
	 * Build and sort source-free file records, putting missing enabled includes first.
	 * @param array $plugin Recognized plugin identity and confined root.
	 * @param array $inventory Parsed inclusion configuration and metadata.
	 * @return array Ordered records and counts of all/enabled/commented/missing entries.
	 */
	public static function records( array $plugin, array $inventory ): array {
		$rows = []; $counts = [ 'all' => 0, 'enabled' => 0, 'commented' => 0, 'missing' => 0, 'enabled_missing' => 0 ];
		$base = realpath( $plugin['root'] );
		foreach ( $inventory['paths'] as $path => $included ) {
			$item = $inventory['metadata'][$path] ?? []; $resolved = realpath( $plugin['root'] . '/' . $path );
			$present = $base && $resolved && str_starts_with( $resolved, $base . DIRECTORY_SEPARATOR ) && is_file( $resolved ) && is_readable( $resolved );
			$rows[] = [ 'path' => $path, 'included' => $included, 'present' => $present, 'item' => $item, 'title' => $item['title'] ?? basename( $path ) ];
			$counts['all']++; $counts[$included ? 'enabled' : 'commented']++;
			if ( ! $present ) { $counts['missing']++; if ( $included ) { $counts['enabled_missing']++; } }
		}
		usort( $rows,
			/** Sort missing and enabled records ahead of ordinary commented entries. @param array $a First record. @param array $b Second record. @return int Relative order, preserving ties. */
			static function( array $a, array $b ): int { return ( ( $a['present'] ? 2 : 0 ) + ( $a['included'] ? 0 : 1 ) ) <=> ( ( $b['present'] ? 2 : 0 ) + ( $b['included'] ? 0 : 1 ) ); }
		);
		return compact( 'rows', 'counts' );
	}

	/**
	 * Validate an explicit metadata/file refresh; never regenerate or modify generated files.
	 * @return void Updates only QuickNav metadata, then redirects to the local overview.
	 */
	public function refresh(): void {
		if ( ! WCBQN_Config::allowed() || 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) && is_string( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) ) { wp_die( esc_html__( 'Permission denied.', 'wpcodebox-quicknav' ), '', [ 'response' => 403 ] ); }
		check_admin_referer( 'wcbqn_refresh_functionality' );
		foreach ( self::plugins() as $plugin ) { self::inventory( $plugin, true ); }
		wp_safe_redirect( add_query_arg( 'wcbqn_refreshed', '1', self::url() ) ); exit;
	}

	/**
	 * Render one progressive-enhancement inventory table with confined file paths.
	 * @param array $plugin Recognized plugin identity.
	 * @param array $inventory Source availability and dated metadata.
	 * @param array $records Ordered file records and summary counts.
	 * @return void Outputs escaped filters, records and copy controls.
	 */
	private function table( array $plugin, array $inventory, array $records ): void {
		$id = 'wcbqn-fp-' . substr( hash( 'sha256', $plugin['file'] ), 0, 12 );
		if ( $records['rows'] ) {
			echo '<div class="wcbqn-fp-controls" hidden><label for="' . esc_attr( $id . '-search' ) . '">' . esc_html__( 'Search snippets and files', 'wpcodebox-quicknav' ) . '<input type="search" id="' . esc_attr( $id . '-search' ) . '" data-wcbqn-fp-search autocomplete="off" maxlength="200" aria-controls="' . esc_attr( $id ) . '"></label><label for="' . esc_attr( $id . '-filter' ) . '">' . esc_html__( 'Filter entries', 'wpcodebox-quicknav' ) . '<select id="' . esc_attr( $id . '-filter' ) . '" data-wcbqn-fp-filter aria-label="' . esc_attr__( 'Filter entries', 'wpcodebox-quicknav' ) . '" aria-controls="' . esc_attr( $id ) . '">';
			foreach ( [ 'all' => __( 'All entries', 'wpcodebox-quicknav' ), 'enabled' => __( 'Enabled includes', 'wpcodebox-quicknav' ), 'commented' => __( 'Commented out', 'wpcodebox-quicknav' ), 'missing' => __( 'Missing or unreadable files', 'wpcodebox-quicknav' ) ] as $key => $label ) { echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</option>'; }
			echo '</select></label><button type="button" class="button" data-wcbqn-fp-clear>' . esc_html__( 'Reset filters', 'wpcodebox-quicknav' ) . '</button></div><p class="description" data-wcbqn-fp-count role="status" aria-live="polite"></p>';
		}
		echo '<div class="wcbqn-fp-table"><table id="' . esc_attr( $id ) . '" class="widefat striped"><thead><tr>';
		foreach ( [ __( 'Snippet / file', 'wpcodebox-quicknav' ), __( 'Type / folder', 'wpcodebox-quicknav' ), __( 'Configured inclusion', 'wpcodebox-quicknav' ), __( 'File status', 'wpcodebox-quicknav' ) ] as $label ) { echo '<th scope="col">' . esc_html( $label ) . '</th>'; }
		echo '</tr></thead><tbody>';
		foreach ( $records['rows'] as $row ) {
			$item = $row['item']; $type = isset( $item['type'] ) ? WCBQN_Navigation_Builder::type( $item['type'] ) : __( 'Unknown', 'wpcodebox-quicknav' );
			$search = $row['title'] . ' ' . $row['path'] . ' ' . $type . ' ' . ( $item['folder'] ?? '' );
			echo '<tr data-wcbqn-fp-row data-search="' . esc_attr( $search ) . '" data-included="' . ( $row['included'] ? '1' : '0' ) . '" data-missing="' . ( $row['present'] ? '0' : '1' ) . '"' . ( $row['present'] ? '' : ' class="wcbqn-fp-missing"' ) . '><td><strong>';
			if ( $inventory['source'] && isset( $item['id'] ) ) { echo '<a href="' . esc_url( WCBQN_WPCodeBox_Adapter::url( 0, absint( $item['id'] ) ) ) . '">' . esc_html( $row['title'] ) . '</a>'; } else { echo esc_html( $row['title'] ); }
			echo '</strong><br><code>' . esc_html( $row['path'] ) . '</code> <button type="button" class="button-link wcbqn-copy-path" data-wcbqn-copy-path="' . esc_attr( $row['path'] ) . '" hidden>' . esc_html__( 'Copy file path', 'wpcodebox-quicknav' ) . '</button></td><td>' . esc_html( $type ) . '<br>' . esc_html( $item['folder'] ?? '' ) . '</td><td>' . esc_html( $row['included'] ? __( 'Enabled include', 'wpcodebox-quicknav' ) : __( 'Commented out', 'wpcodebox-quicknav' ) ) . '</td><td><strong>' . esc_html( $row['present'] ? __( 'Readable', 'wpcodebox-quicknav' ) : __( 'Missing or unreadable', 'wpcodebox-quicknav' ) ) . '</strong></td></tr>';
		}
		echo '<tr data-wcbqn-fp-empty' . ( $records['rows'] ? ' hidden' : '' ) . '><td colspan="4">' . esc_html( $records['rows'] ? __( 'No entries match these filters.', 'wpcodebox-quicknav' ) : __( 'No supported literal snippet includes were found.', 'wpcodebox-quicknav' ) ) . '</td></tr></tbody></table></div><p data-wcbqn-copy-status role="status" aria-live="polite"></p>';
	}

	/** Render the independent, capability-protected status overview. @return void */
	public function page(): void {
		if ( ! WCBQN_Config::allowed() ) { return; }
		echo '<div class="wrap wcbqn-settings"><header class="wcbqn-page-heading"><img src="' . esc_url( plugins_url( 'assets/quicknav-icon.svg', WCBQN_PLUGIN_FILE ) ) . '" alt="" width="56" height="56"><div><h1>' . esc_html__( 'Functionality Plugin', 'wpcodebox-quicknav' ) . '</h1><p>' . esc_html__( 'Website functions at a glance.', 'wpcodebox-quicknav' ) . '</p></div></header><hr class="wp-header-end">';
		echo '<p>' . esc_html__( 'This overview reads the generated configuration. It does not prove that a snippet executed on this request. Hooks, conditions and Safe Mode can prevent execution.', 'wpcodebox-quicknav' ) . '</p>';
		echo '<p class="wcbqn-state-help"><strong>' . esc_html__( 'Commented out', 'wpcodebox-quicknav' ) . ':</strong> ' . esc_html__( 'The load instruction is disabled, so this Functionality Plugin does not include the snippet file. The file is not deleted.', 'wpcodebox-quicknav' ) . '</p>';
		echo '<details class="wcbqn-state-details"><summary>' . esc_html__( 'What do the other states mean?', 'wpcodebox-quicknav' ) . '</summary><p><strong>' . esc_html__( 'Enabled include', 'wpcodebox-quicknav' ) . ':</strong> ' . esc_html__( 'The file is configured to load through the active Functionality Plugin. Conditions, hooks and Safe Mode still determine execution.', 'wpcodebox-quicknav' ) . '</p><p><strong>' . esc_html__( 'Readable', 'wpcodebox-quicknav' ) . ':</strong> ' . esc_html__( 'The file exists and can be read. This does not confirm that its code ran successfully.', 'wpcodebox-quicknav' ) . '</p></details>';
		if ( defined( 'WPCB_SAFE_MODE' ) ) { echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'Safe Mode is enabled by WPCB_SAFE_MODE. Generated snippet execution is paused, even if the constant is false.', 'wpcodebox-quicknav' ) . '</p></div>'; }
		if ( isset( $_GET['wcbqn_refreshed'] ) && is_string( $_GET['wcbqn_refreshed'] ) && '1' === $_GET['wcbqn_refreshed'] ) { echo '<div class="notice notice-success inline"><p>' . esc_html__( 'Information refreshed. Generated plugin files were not changed.', 'wpcodebox-quicknav' ) . '</p></div>'; }
		echo '<form class="wcbqn-fp-refresh" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="wcbqn_refresh_functionality">';
		wp_nonce_field( 'wcbqn_refresh_functionality' ); submit_button( __( 'Refresh information', 'wpcodebox-quicknav' ), 'secondary', 'submit', false );
		echo '<span class="description">' . esc_html__( 'Recheck files and capture available WPCodeBox 2 metadata. No plugin regeneration.', 'wpcodebox-quicknav' ) . '</span></form>';
		$plugins = self::plugins();
		if ( ! $plugins ) { echo '<section class="wcbqn-card"><p>' . esc_html__( 'No managed WPCodeBox Functionality Plugin was detected. Independently exported plugins are not inventoried.', 'wpcodebox-quicknav' ) . '</p></section>'; }
		foreach ( $plugins as $plugin ) {
			$inventory = self::inventory( $plugin ); $records = self::records( $plugin, $inventory );
			$mode = self::operating_state( $inventory['source'], $plugin['active'], defined( 'WPCB_SAFE_MODE' ) );
			echo '<section class="wcbqn-card wcbqn-fp-inventory"><h2>' . esc_html( $plugin['name'] ) . ' <span class="wcbqn-fp-state">' . esc_html( $plugin['active'] ? __( 'Active', 'wpcodebox-quicknav' ) : __( 'Inactive', 'wpcodebox-quicknav' ) ) . '</span></h2><p><code>' . esc_html( $plugin['file'] ) . '</code> · ' . esc_html__( 'Version', 'wpcodebox-quicknav' ) . ' ' . esc_html( $plugin['version'] ) . ' · ' . esc_html( $plugin['network'] ? __( 'Network activation', 'wpcodebox-quicknav' ) : __( 'Website scope', 'wpcodebox-quicknav' ) ) . '</p>';
			echo '<div class="wcbqn-operating-state wcbqn-mode-' . esc_attr( $mode['level'] ) . '"><h3>' . esc_html__( 'Operating status', 'wpcodebox-quicknav' ) . ': ' . esc_html( $mode['label'] ) . '</h3><p>' . esc_html( $mode['text'] ) . '</p></div>';
			$management = self::management_state( $inventory['source'], $plugin['active'] );
			echo '<p class="wcbqn-management"><strong>' . esc_html( $management['label'] ) . '</strong> <a href="' . esc_url( $management['url'] ) . '">' . esc_html( $management['action'] ) . '</a></p><dl class="wcbqn-fp-summary">';
			foreach ( [ 'all' => __( 'Snippet files', 'wpcodebox-quicknav' ), 'enabled' => __( 'Enabled includes', 'wpcodebox-quicknav' ), 'commented' => __( 'Commented out', 'wpcodebox-quicknav' ), 'missing' => __( 'Missing or unreadable files', 'wpcodebox-quicknav' ) ] as $key => $label ) { echo '<div><dt>' . esc_html( $label ) . '</dt><dd>' . esc_html( $records['counts'][$key] ) . '</dd></div>'; }
			echo '</dl>';
			if ( $records['counts']['enabled_missing'] ) { echo '<div class="notice notice-error inline"><p>' . esc_html__( 'Enabled includes reference missing or unreadable files. Review these entries first in WPCodeBox 2.', 'wpcodebox-quicknav' ) . '</p></div>'; }
			if ( $inventory['changed'] ) { echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'The generated configuration has changed since the metadata snapshot. Saved titles may be outdated; filenames and inclusion states below are current.', 'wpcodebox-quicknav' ) . '</p></div>'; }
			if ( ! empty( $inventory['snapshot']['time'] ) ) {
				/* translators: %s: localized metadata capture date and time. */
				echo '<p class="description">' . esc_html( sprintf( __( 'Metadata snapshot: %s. Titles and types are associated by signed IDs and matching filenames; this is not a source-code synchronization check.', 'wpcodebox-quicknav' ), wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $inventory['snapshot']['time'] ) ) ) . '</p>';
			}
			if ( ! empty( $inventory['snapshot']['checked'] ) ) {
				/* translators: %s: localized time of the last explicit refresh. */
				echo '<p class="description">' . esc_html( sprintf( __( 'Last manual refresh: %s', 'wpcodebox-quicknav' ), wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $inventory['snapshot']['checked'] ) ) ) . '</p>';
			}
			$this->table( $plugin, $inventory, $records );
			echo '<p class="description">' . esc_html__( 'Only literal snippet includes in the main plugin file are listed. Assets and other resources are not separate execution entries.', 'wpcodebox-quicknav' ) . '</p></section>';
		}
		echo '<p>' . esc_html__( 'To pause all functions, deactivate the Functionality Plugin through WordPress plugin management. QuickNav does not edit or delete generated files.', 'wpcodebox-quicknav' ) . '</p><p class="wcbqn-fp-actions"><a class="button" href="' . esc_url( self_admin_url( 'plugins.php' ) ) . '">' . esc_html__( 'Manage plugins', 'wpcodebox-quicknav' ) . '</a> ';
		if ( WCBQN_WPCodeBox_Adapter::available() ) { echo '<a class="button" href="' . esc_url( WCBQN_WPCodeBox_Adapter::url() ) . '">' . esc_html__( 'Manage snippets in WPCodeBox', 'wpcodebox-quicknav' ) . '</a> '; }
		echo '<a href="' . esc_url( WCBQN_Settings::url() ) . '">' . esc_html__( 'QuickNav settings', 'wpcodebox-quicknav' ) . '</a></p>';
		( new WCBQN_Settings() )->footer(); echo '</div>';
	}
}
