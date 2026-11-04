<?php
/** Adapted from Advanced Scripts QuickNav 1.3.2, David Decker, GPL-2.0-or-later. See docs/credits.txt. */
/** Project flat upstream metadata into toolbar groups and a guarded folder tree. */
defined( 'ABSPATH' ) || exit;
/** Build bounded navigation projections from indexed metadata. */
final class WCBQN_Navigation_Builder {
	private array $scripts;
	private array $children = [];

	/**
	 * Initialize the component and its request-local dependencies.
	 *
	 * @param array $scripts Indexed script metadata.
	 * @return void No return value.
	 */
	public function __construct( array $scripts ) {
		$this->scripts = $scripts;
		foreach ( $scripts as $item ) { $this->children[$item['parent']][] = $item['term_id']; }
	}

	/**
	 * Return indexed script metadata without exposing source code.
	 *
	 * @return array Indexed script metadata, excluding script source code.
	 */
	public function scripts(): array { return $this->scripts; }

	/**
	 * Add the count navigation section.
	 *
	 * @return int Snippet count excluding folders.
	 */
	public function count(): int { return count( array_filter( $this->scripts, /** Accept snippet metadata, excluding folders. @param array $item Metadata record. @return bool Whether this is a snippet. */ static function( $item ) { return 'folder' !== $item['type']; } ) ); }
	/**
	 * Summarize saved snippet states separately from execution conditions.
	 *
	 * @return array Counts for scripts, saved activation state, folders, blocking and types.
	 */
	public function statistics(): array {
		$stats = [ 'total' => 0, 'active' => 0, 'inactive' => 0, 'blocked' => 0, 'folders' => 0, 'types' => [] ];
		foreach ( $this->scripts as $item ) {
			if ( 'folder' === $item['type'] ) { $stats['folders']++; continue; }
			$stats['total']++;
			$stats[$item['status'] ? 'active' : 'inactive']++;
			if ( $item['status'] && $this->blocked( $item ) ) { $stats['blocked']++; }
			$type = self::type( $item['type'] );
			$stats['types'][$type] = ( $stats['types'][$type] ?? 0 ) + 1;
		}
		arsort( $stats['types'] );
		return $stats;
	}
	/**
	 * Check whether an inactive ancestor blocks a snippet.
	 *
	 * @param array $item Current metadata record.
	 * @return bool Whether an inactive ancestor blocks the script.
	 */
	public function blocked( array $item ): bool {
		return false;
	}

	/**
	 * Resolve the folder path with cycle protection.
	 *
	 * @param array $item Current metadata record.
	 * @return string Folder breadcrumb path, excluding cyclic branches.
	 */
	public function path( array $item ): string {
		$parts = []; $seen = []; $parent = $item['parent'];
		while ( $parent && isset( $this->scripts[$parent] ) && ! isset( $seen[$parent] ) ) {
			$seen[$parent] = true; $folder = $this->scripts[$parent];
			array_unshift( $parts, $folder['title'] ); $parent = $folder['parent'];
		}
		return implode( ' / ', $parts );
	}

	/**
	 * Describe a snippet type for navigation.
	 *
	 * @param string $type Type.
	 * @return string Human-readable script type.
	 */
	public static function type( string $type ): string {
		$types = [ 'php' => 'PHP', 'js' => 'JavaScript', 'ex_js' => __( 'External JavaScript', 'wpcodebox-quicknav' ), 'css' => 'CSS', 'ex_css' => __( 'External CSS', 'wpcodebox-quicknav' ), 'scss' => 'SCSS', 'scssp' => __( 'SCSS partial', 'wpcodebox-quicknav' ), 'less' => 'LESS', 'html' => 'HTML', 'json' => 'JSON', 'txt' => __( 'Text', 'wpcodebox-quicknav' ), 'md' => 'Markdown', 'folder' => __( 'Folder', 'wpcodebox-quicknav' ) ];
		return $types[$type] ?? __( 'Code', 'wpcodebox-quicknav' );
	}

	/**
	 * Describe status, folder and execution metadata.
	 *
	 * @param array $item Current metadata record.
	 * @return string Readable script state and execution context.
	 */
	public function description( array $item ): string {
		$locations = [ 'all' => __( 'everywhere', 'wpcodebox-quicknav' ), 'front' => __( 'front-end', 'wpcodebox-quicknav' ), 'admin' => __( 'administration', 'wpcodebox-quicknav' ), 'shortcode' => __( 'shortcode', 'wpcodebox-quicknav' ) ];
		$parts = [ ! empty( $item['error'] ) ? __( 'Snippet error', 'wpcodebox-quicknav' ) : '', self::type( $item['type'] ), $item['status'] ? __( 'active', 'wpcodebox-quicknav' ) : __( 'inactive', 'wpcodebox-quicknav' ), $this->path( $item ), $locations[$item['location']] ?? $item['location'], $item['hook'] ];
		if ( $this->blocked( $item ) ) { $parts[] = __( 'Disabled by folder', 'wpcodebox-quicknav' ); }
		return implode( ' · ', array_filter( $parts ) );
	}

	/**
	 * Add an escaped script link with a projection-specific ID.
	 *
	 * @param WP_Admin_Bar $bar WordPress admin-bar object to populate.
	 * @param array $item Current metadata record.
	 * @param string $parent Parent toolbar node ID or folder term ID.
	 * @param string $context Navigation projection used in unique IDs.
	 * @return void No return value.
	 */
	private function script_node( $bar, array $item, string $parent, string $context ): void {
		$blocked = $this->blocked( $item );
		$label = '<span class="wcbqn-snippet-name">' . esc_html( $item['title'] ?: __( 'Untitled snippet', 'wpcodebox-quicknav' ) ) . '</span>';
		$label .= ' <span class="wcbqn-type">' . esc_html( self::type( $item['type'] ) ) . '</span>';
		if ( $blocked ) { $label .= ' <span class="wcbqn-blocked">' . esc_html__( 'Disabled by folder', 'wpcodebox-quicknav' ) . '</span>'; }
		else { $label .= ' <span class="wcbqn-status wcbqn-status-' . ( $item['status'] ? 'active' : 'inactive' ) . '">' . esc_html( $item['status'] ? __( 'active', 'wpcodebox-quicknav' ) : __( 'inactive', 'wpcodebox-quicknav' ) ) . '</span>'; }
		// Preserve legacy status-view IDs; each additional projection gets its own ID.
		$id = 'status' === $context ? 'wcbqn-script-' . $item['term_id'] : 'wcbqn-' . $context . '-script-' . $item['term_id'];
		$bar->add_node( [ 'id' => $id, 'parent' => $parent, 'title' => $label, 'href' => esc_url( WCBQN_WPCodeBox_Adapter::url( $item['parent'], $item['term_id'] ) ), 'meta' => [ 'title' => $this->description( $item ), 'class' => 'wcbqn-snippet-item' ] ] );
	}

	/**
	 * Render bounded active and inactive snippet lists.
	 *
	 * @param WP_Admin_Bar $bar WordPress admin-bar object to populate.
	 * @return void No return value.
	 */
	public function statuses( $bar ): void {
		foreach ( [ 'active' => true, 'inactive' => false ] as $name => $status ) {
			$items = array_filter( $this->scripts, /** Select snippets in the captured status projection. @param array $item Metadata record. @return bool Whether the snippet has the selected saved status. */ static function( $item ) use ( $status ) { return 'folder' !== $item['type'] && $status === $item['status']; } );
			$title = $status ? __( 'Active Snippets', 'wpcodebox-quicknav' ) : __( 'Inactive Snippets', 'wpcodebox-quicknav' );
			$bar->add_node( [ 'id' => 'wcbqn-' . $name, 'parent' => 'wcbqn-group-status', 'title' => esc_html( $title . ' (' . count( $items ) . ')' ), 'href' => esc_url( WCBQN_WPCodeBox_Adapter::url() ) ] );
			$i = 0;
			foreach ( $items as $item ) { if ( $i++ >= WCBQN_Config::limit() ) { break; } $this->script_node( $bar, $item, 'wcbqn-' . $name, 'status' ); }
			if ( count( $items ) > WCBQN_Config::limit() ) { $this->more( $bar, 'wcbqn-' . $name, 0 ); }
		}
	}

	/**
	 * Add a link to the remaining native script collection.
	 *
	 * @param WP_Admin_Bar $bar WordPress admin-bar object to populate.
	 * @param string $parent Parent toolbar node ID or folder term ID.
	 * @param int $folder Folder term ID.
	 * @return void No return value.
	 */
	private function more( $bar, string $parent, int $folder ): void {
		$bar->add_node( [ 'id' => $parent . '-more', 'parent' => $parent, 'title' => esc_html__( 'View all in WPCodeBox…', 'wpcodebox-quicknav' ), 'href' => esc_url( WCBQN_WPCodeBox_Adapter::url( $folder ) ) ] );
	}

	/**
	 * Render the current user’s bounded favorites list.
	 *
	 * @param WP_Admin_Bar $bar WordPress admin-bar object to populate.
	 * @return void No return value.
	 */
	public function favorites( $bar ): void {
		$ids = WCBQN_Favorites::ids( $this->scripts );
		$bar->add_group( [ 'id' => 'wcbqn-group-favorites', 'parent' => 'ddw-wpcodebox-quicknav' ] );
		$bar->add_node( [ 'id' => 'wcbqn-favorites', 'parent' => 'wcbqn-group-favorites', 'title' => esc_html__( '★ Favorites', 'wpcodebox-quicknav' ), 'href' => esc_url( WCBQN_Settings::url() ) ] );
		foreach ( array_slice( $ids, 0, WCBQN_Config::limit() ) as $id ) { $this->script_node( $bar, $this->scripts[$id], 'wcbqn-favorites', 'favorite' ); }
		$bar->add_node( [ 'id' => 'wcbqn-favorites-manage', 'parent' => 'wcbqn-favorites', 'title' => esc_html__( 'Manage favorites…', 'wpcodebox-quicknav' ), 'href' => esc_url( WCBQN_Settings::url() ) ] );
	}

	/**
	 * Render a bounded folder tree.
	 *
	 * @param WP_Admin_Bar $bar WordPress admin-bar object to populate.
	 * @return void No return value.
	 */
	public function folders( $bar ): void {
		$bar->add_node( [ 'id' => 'wcbqn-scripts-folders', 'parent' => 'wcbqn-group-folders', 'title' => esc_html__( 'Snippets by Folder', 'wpcodebox-quicknav' ), 'href' => esc_url( WCBQN_WPCodeBox_Adapter::url() ) ] );
		$visited = []; $budget = WCBQN_Config::limit();
		$this->branch( $bar, 0, 'wcbqn-scripts-folders', $visited, $budget, 0 );
		// Keep orphaned items reachable without inventing an upstream folder hierarchy.
		foreach ( $this->scripts as $item ) {
			if ( isset( $visited[$item['term_id']] ) || $budget <= 0 ) { continue; }
			if ( 'folder' !== $item['type'] ) { $this->script_node( $bar, $item, 'wcbqn-scripts-folders', 'folder' ); $visited[$item['term_id']] = true; $budget--; }
		}
		if ( count( $visited ) < count( $this->scripts ) ) { $this->more( $bar, 'wcbqn-scripts-folders', 0 ); }
	}

	/**
	 * Traverse one folder branch with shared cycle and entry guards.
	 *
	 * @param WP_Admin_Bar $bar WordPress admin-bar object to populate.
	 * @param int $folder Folder term ID.
	 * @param string $parent Parent toolbar node ID or folder term ID.
	 * @param array $visited Visited folder IDs, updated during traversal.
	 * @param int $budget Remaining shared entry budget, decremented during traversal.
	 * @param int $depth Current traversal depth.
	 * @return void No return value.
	 */
	private function branch( $bar, int $folder, string $parent, array &$visited, int &$budget, int $depth ): void {
		if ( $depth >= 8 ) { $this->more( $bar, $parent, $folder ); return; }
		foreach ( $this->children[$folder] ?? [] as $id ) {
			if ( isset( $visited[$id] ) ) { continue; }
			if ( $budget <= 0 ) { $this->more( $bar, $parent, $folder ); break; }
			$visited[$id] = true; $budget--; $item = $this->scripts[$id];
			if ( 'folder' !== $item['type'] ) { $this->script_node( $bar, $item, $parent, 'folder' ); continue; }
			$node = 'wcbqn-folder-' . $id;
			$label = '▸ ' . $item['title'] . ( $item['status'] ? '' : ' · ' . __( 'inactive', 'wpcodebox-quicknav' ) );
			$bar->add_node( [ 'id' => $node, 'parent' => $parent, 'title' => esc_html( $label ), 'href' => esc_url( WCBQN_WPCodeBox_Adapter::url( $id ) ), 'meta' => [ 'title' => $this->description( $item ) ] ] );

			$this->branch( $bar, $id, $node, $visited, $budget, $depth + 1 );
		}
	}
}
