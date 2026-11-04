<?php
/** Adapted from Advanced Scripts QuickNav 1.3.2, David Decker, GPL-2.0-or-later. See docs/credits.txt. */
/** Toolbar presentation and existing optional resource integrations. */
defined( 'ABSPATH' ) || exit;
/** Render QuickNav toolbar groups and non-sensitive diagnostics. */
final class WCBQN_Admin_Bar_Renderer {
	private WCBQN_Navigation_Builder $navigation;

	/** Class constants & variables */
	private const VERSION = WCBQN_Config::VERSION;
	/**
	 * Initialize the component and its request-local dependencies.
	 *
	 * @param WCBQN_Navigation_Builder $navigation Indexed navigation builder.
	 * @return void No return value.
	 */
	public function __construct( WCBQN_Navigation_Builder $navigation ) { $this->navigation = $navigation; }
	
	/**
	 * Is expert mode active?
	 *   Gives some more stuff that is more focused at (plugin/snippet) developers
	 *   and mostly not needed for fast site building.
	 *
	 * @return bool
	 */
	private function is_expert_mode(): bool { return WCBQN_Config::expert(); }
	/**
	 * Resolve supported admin color-scheme colors with safe fallbacks.
	 *
	 * @return array Resolved result.
	 */
	private function get_scheme_colors() {
		
		$scheme_colors = array(
			'fresh' => array(
				'bg'    => '#1d2327',
				'base'  => 'rgba(240,246,252,.6)',
				'hover' => '#72aee6',
			),
			'light' => array(
				'bg'    => '#e5e5e5',
				'base'  => '#999',
				'hover' => '#04a4cc',
			),
			'modern' => array(
				'bg'    => '#1e1e1e',
				'base'  => '#f3f1f1',
				'hover' => '#33f078',
			),
			'blue' => array(
				'bg'    => '#52accc',
				'base'  => '#e5f8ff',
				'hover' => '#fff',
			),
			'coffee' => array(
				'bg'    => '#59524c',
				'base'  => 'hsl(27.6923076923,7%,95%)',
				'hover' => '#c7a589',
			),
			'ectoplasm' => array(
				'bg'    => '#523f6d',
				'base'  => '#ece6f6',
				'hover' => '#a3b745',
			),
			'midnight' => array(
				'bg'    => '#363b3f',
				'base'  => 'hsl(206.6666666667,7%,95%)',
				'hover' => '#e14d43',
			),
			'ocean' => array(
				'bg'    => '#738e96',
				'base'  => '#f2fcff',
				'hover' => '#9ebaa0',
			),
			'sunrise' => array(
				'bg'    => '#cf4944',
				'base'  => 'hsl(2.1582733813,7%,95%)',
				'hover' => 'rgb(247.3869565217,227.0108695652,211.1130434783)',
			),
		);
		
		/** No filter currently b/c of sanitizing issues with the above CSS values */
		//$scheme_colors = (array) apply_filters( 'ddw/quicknav/wcbqn_scheme_colors', $scheme_colors );
		
		return $scheme_colors;
	}
	/**
	 * Add navigation styling for the current admin color scheme.
	 *
	 * @return void No return value.
	 */
	public function enqueue_admin_bar_styles() {
		if ( ! WCBQN_Config::visible() ) { return; }
		
		/**
		 * Depending on user color scheme get proper base and hover color values for the main item (svg) icon.
		 */
		$user_color_scheme = get_user_option( 'admin_color' );
		$user_color_scheme = ( is_admin() || is_network_admin() ) ? $user_color_scheme : 'fresh';  // b/c in frontend there is no 'admin_color'
		$admin_scheme      = $this->get_scheme_colors();
		$user_color_scheme = isset( $admin_scheme[$user_color_scheme] ) ? $user_color_scheme : 'fresh';
		
		$base_color  = $admin_scheme[ $user_color_scheme ][ 'base' ];
		$hover_color = $admin_scheme[ $user_color_scheme ][ 'hover' ];
		
		/**
		 * Build the inline CSS
		 *   NOTE: We need to use 'sprintf()' because of the percentage values and similar!
		 */
		$inline_css = sprintf(
			'
				#wpadminbar .wcbqn-scripts-list .ab-sub-wrapper ul li span.location,
				#wpadminbar .wcbqn-scripts-list .ab-sub-wrapper ul li span.status {
					font-family: monospace;
					font-size: %1$s;
					vertical-align: super;
				}
				
				#wpadminbar .wcbqn-scripts-list .ab-sub-wrapper ul li span.location,
				#wpadminbar .wcbqn-scripts-list .ab-sub-wrapper ul li span.status.inactive {
					/* filter: brightness(%2$s); */
					color: hsl(0, %3$s, %4$s);
				}
				
				#wpadminbar .wcbqn-scripts-list .ab-sub-wrapper ul li span.status.active {
					/* filter: brightness(%2$s); */
					color: hsl(120, %3$s, %7$s);
				}
				
				#wpadminbar .wcbqn-safemode {
					background-color: #9C1005;
				}
				#wpadminbar .wcbqn-safemode:hover,
				#wpadminbar ul li.wcbqn-safemode:hover {
					background: #BD3126;
				}
				#wpadminbar .wcbqn-safemode a {
					color: #FBE4C6;
					font-weight: 700;
				}
				
				#wpadminbar .wcbqn-scripts-list .has-icon .icon-svg svg {
					display: inline-block;
					margin-bottom: 3px;
					vertical-align: middle;
					width: 16px;
					height: 16px;
				}
				
				#wpadminbar .wcbqn-scripts-list .icon-svg.ab-icon svg {
					width: 15px;
					height: 15px;
				}
				
				.wcbqn-scripts-list .ab-item .icon-svg.ab-icon svg {
					color: %5$s;
				}
				
				.wcbqn-scripts-list .ab-item:hover .icon-svg.ab-icon svg {
					color: %6$s;
				}								
			',
			'80%',			// 1
			'120%',			// 2
			'100%',			// 3
			'70%',			// 4
			$base_color,	// 5
			$hover_color,	// 6
			'40%'			// 7
		);
		
		/** Only add the styles if Admin Bar is showing */
		if ( is_admin_bar_showing() ) {
			wp_add_inline_style( 'admin-bar', $inline_css );
		}
	}
	/**
	 * Check the WordPress development-mode flag.
	 *
	 * @return bool Whether WordPress development mode is enabled.
	 */
	private function is_wp_dev_mode_active() {
		
		return defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
	}
	/**
	 * Read the effective WPCodeBox Safe Mode.
	 *
	 * @return bool Effective PHP Safe Mode flag.
	 */
	private function is_safe_mode_active(): bool { return WCBQN_WPCodeBox_Adapter::safe_mode(); }
	
	/**
	 * Get number of all Scripts (without folders). (helper function)
	 *
	 * @return int Number of scripts.
	 */
	private function script_counter(): int { return $this->navigation->count(); }
	/**
	 * Populate the toolbar only for permitted users.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar WordPress admin-bar object to populate.
	 * @return WP_Admin_Bar Resolved result.
	 */
	public function add_admin_bar_menu( $wp_admin_bar ) {
		
		if ( ! WCBQN_Config::visible() ) { return; }

		/** Build the main item title, optional scripts count value */
		$all_scripts = $this->script_counter();
		$counter     = WCBQN_Config::counter() ? ' (' . intval( $all_scripts ) . ')' : '';
		$wcbqn_name   = esc_html( WCBQN_Config::toolbar_name() );
		$wcbqn_name   = $wcbqn_name . $counter;

		/** Default "script icon" */
		$code_icon = '<span class="icon-svg ab-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 16h5a3 3 0 0 0 3-3V9a3 3 0 0 1 3-3h8m-4-4 4 4-4 4m-1 5-2 2 2 2m4-4 2 2-2 2m-2-5-1 6" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span> ';
		
		/** Optional code icon by Remix Icon */
		$remix_icon = '<span class="icon-svg ab-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12L18.3431 17.6569L16.9289 16.2426L21.1716 12L16.9289 7.75736L18.3431 6.34315L24 12ZM2.82843 12L7.07107 16.2426L5.65685 17.6569L0 12L5.65685 6.34315L7.07107 7.75736L2.82843 12ZM9.78845 21H7.66009L14.2116 3H16.3399L9.78845 21Z"></path></svg></span> ';
		
		/** Original blue icon (svg-ed) by Clean Plugins */
		$blue_icon = $code_icon;
		
		/** Consistent warning indicator for the effective Safe Mode. */
		$safemode = ( $this->is_safe_mode_active() ) ? WCBQN_Menu_Icons::safe_mode_indicator() : '';
			
		$title_html = $code_icon . '<span class="ab-label">' . $wcbqn_name . '</span>' . $safemode;
		
		if ( defined( 'WCBQN_ICON' ) && 'blue' === sanitize_key( WCBQN_ICON ) ) {
			$title_html = $blue_icon . '<span class="ab-label">' . $wcbqn_name . '</span>' . $safemode;
		} elseif ( defined( 'WCBQN_ICON' ) && 'remix' === sanitize_key( WCBQN_ICON ) ) {
			$title_html = $remix_icon . '<span class="ab-label">' . $wcbqn_name . '</span>' . $safemode;
		}
		
		/** Add the parent menu item with an icon (main node) */
		$wp_admin_bar->add_node( array(
			'id'    => 'ddw-wpcodebox-quicknav',
			'title' => $title_html,
			'href'  => esc_url( WCBQN_WPCodeBox_Adapter::available() ? WCBQN_WPCodeBox_Adapter::url() : WCBQN_Functionality::url() ),
			'meta'  => array( 'class' => 'wcbqn-scripts-list has-icon' ),
		) );
		
		$wp_admin_bar->add_node( [ 'id' => 'wcbqn-preferences', 'parent' => 'ddw-wpcodebox-quicknav', 'title' => esc_html__( 'QuickNav preferences', 'wpcodebox-quicknav' ), 'href' => esc_url( WCBQN_Settings::url() ) ] );
		$plugins = WCBQN_Functionality::plugins();
		if ( $plugins ) {
			$active = count( array_filter( $plugins, /** Select WordPress-active generated plugins. @param array $plugin Plugin identity. @return bool Activation state. */ static function( array $plugin ): bool { return $plugin['active']; } ) );
			$label = $active ? __( 'Functionality Plugin active', 'wpcodebox-quicknav' ) : __( 'Functionality Plugin inactive', 'wpcodebox-quicknav' );
			if ( defined( 'WPCB_SAFE_MODE' ) && $active ) { $label = __( 'Functionality Plugin: Safe Mode', 'wpcodebox-quicknav' ); }
			$wp_admin_bar->add_node( [ 'id' => 'wcbqn-functionality', 'parent' => 'ddw-wpcodebox-quicknav', 'title' => esc_html( $label ), 'href' => esc_url( WCBQN_Functionality::url() ) ] );
		}
		$this->add_safe_mode_warning( $wp_admin_bar );
		if ( ! WCBQN_WPCodeBox_Adapter::available() ) { return; }
		/** Add submenus */
		$this->add_safemode_submenu( $wp_admin_bar );
		$this->navigation->favorites( $wp_admin_bar );
		$this->add_scripts_by_status_group( $wp_admin_bar );  // group node
		$this->add_scripts_by_folder_group( $wp_admin_bar );  // group node
		$this->add_scripts_new_group( $wp_admin_bar );  // group node
		$this->add_settings_group( $wp_admin_bar );  // group node
		$this->add_library_group( $wp_admin_bar );  // group node
		if ( WCBQN_Config::resources() && ! WCBQN_Config::flag( 'WCBQN_DISABLE_LIBRARY', false ) ) { $this->add_libraries_submenu( $wp_admin_bar ); }
		$this->add_footer_group( $wp_admin_bar );  // group node
		if ( ! WCBQN_Config::flag( 'WCBQN_DISABLE_FOOTER', false ) ) {
			if ( WCBQN_Config::resources() ) { $this->add_links_submenu( $wp_admin_bar ); }
			$this->add_about_submenu( $wp_admin_bar );
		}
	}
	/**
	 * Keep the independent recovery warning visible when only a generated plugin remains.
	 * @param WP_Admin_Bar $wp_admin_bar Toolbar for the authorized current user.
	 * @return void Adds the right-side warning only while Safe Mode applies.
	 */
	private function add_safe_mode_warning( WP_Admin_Bar $wp_admin_bar ): void {
		/** Separate persistent recovery warning: deliberately visible on the toolbar's right. */
		if ( $this->is_safe_mode_active() ) {
			$wp_admin_bar->add_node( [
				'id' => 'wcbqn-safemode-active-topright',
				'parent' => 'top-secondary',
				'title' => strtoupper( esc_html__( 'WPCodeBox Safe Mode active 🔥', 'wpcodebox-quicknav' ) ),
				'href' => 'https://docs.wpcodebox.com/safe_mode',
				'meta' => [ 'class' => 'wcbqn-safemode', 'target' => '_blank', 'rel' => 'noopener noreferrer' ],
			] );
		}

	}
	/**
	 * Add the safemode submenu navigation section.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar WordPress admin-bar object to populate.
	 * @return WP_Admin_Bar Resolved result.
	 */
	private function add_safemode_submenu( $wp_admin_bar ) {
		
		$wp_admin_bar->add_group( array(
			'id'     => 'wcbqn-group-safemode',
			'parent' => 'ddw-wpcodebox-quicknav',
		) );
		
		/** One state-aware recovery link, preserving request-local Safe Mode on opening. */
		$active = $this->is_safe_mode_active();
		$wp_admin_bar->add_node( [
			'id' => 'wcbqn-safe-editor',
			'parent' => 'wcbqn-group-safemode',
			'title' => esc_html( $active ? __( 'Safe Mode active', 'wpcodebox-quicknav' ) : __( 'Open editor in Safe Mode', 'wpcodebox-quicknav' ) ),
			'href' => esc_url( add_query_arg( 'safe_mode', '1', WCBQN_WPCodeBox_Adapter::url() ) ),
			'meta' => [ 'class' => $active ? 'wcbqn-safe-active' : '' ],
		] );


		/** Warning for WordPress' SCRIPT_DEBUG constant */
		if ( $this->is_wp_dev_mode_active() ) {
			$wp_admin_bar->add_node( array(
				'id'     => 'wcbqn-devmode-active',
				'title'  => esc_html__( 'SCRIPT_DEBUG is on ⚠', 'wpcodebox-quicknav' ),
				'href'   => 'https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/#script_debug',
				'parent' => 'wcbqn-group-safemode',
				'meta'   => array( 'class' => 'wcbqn-safemode', 'rel' => 'nofollow noopener noreferrer' ),
			) );
			
			$wp_admin_bar->add_node( array(
				'id'     => 'wcbqn-devmode-active-topright',
				'title'  => strtoupper( esc_html__( 'SCRIPT_DEBUG is on ⚠', 'wpcodebox-quicknav' ) ),
				'href'   => 'https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/#script_debug',
				'parent' => 'top-secondary',	/** Puts the text on the right side of the Toolbar! */
				'meta'   => array( 'class' => 'wcbqn-safemode', 'target' => '_blank', 'rel' => 'nofollow noopener noreferrer' ),
			) );
		}  // end if
	}
	/**
	 * Add the scripts by status group navigation section.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar WordPress admin-bar object to populate.
	 * @return WP_Admin_Bar Resolved result.
	 */
	private function add_scripts_by_status_group( $wp_admin_bar ) {
		
		$wp_admin_bar->add_group( array(
			'id'     => 'wcbqn-group-status',
			'parent' => 'ddw-wpcodebox-quicknav',
		) );
		
		$this->add_scripts_to_admin_bar( $wp_admin_bar );
	}
	/**
	 * Add the scripts to admin bar navigation section.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar WordPress admin-bar object to populate.
	 * @return void No return value.
	 */
	private function add_scripts_to_admin_bar( $wp_admin_bar ): void { $this->navigation->statuses( $wp_admin_bar ); }
	/**
	 * Add the scripts by folder group navigation section.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar WordPress admin-bar object to populate.
	 * @return WP_Admin_Bar Resolved result.
	 */
	private function add_scripts_by_folder_group( $wp_admin_bar ) {
		
		$wp_admin_bar->add_group( array(
			'id'     => 'wcbqn-group-folders',
			'parent' => 'ddw-wpcodebox-quicknav',
		) );
		
		$this->add_scripts_listings_submenu( $wp_admin_bar );
		//$this->add_snippets_type_submenu( $wp_admin_bar );
	}
	/**
	 * Add the scripts listings submenu navigation section.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar WordPress admin-bar object to populate.
	 * @return void No return value.
	 */
	private function add_scripts_listings_submenu( $wp_admin_bar ): void { $this->navigation->folders( $wp_admin_bar ); }
	/**
	 * Add the scripts new group navigation section.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar WordPress admin-bar object to populate.
	 * @return WP_Admin_Bar Resolved result.
	 */
	private function add_scripts_new_group( $wp_admin_bar ) {
		
		$wp_admin_bar->add_group( array(
			'id'     => 'wcbqn-group-new',
			'parent' => 'ddw-wpcodebox-quicknav',
		) );
		
		$this->add_scripts_new_submenu( $wp_admin_bar );
	}
	/**
	 * Add the scripts new submenu navigation section.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar WordPress admin-bar object to populate.
	 * @return WP_Admin_Bar Resolved result.
	 */
	private function add_scripts_new_submenu( $wp_admin_bar ) {
		
		$icon_add = '<span class="icon-svg"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M13.0001 10.9999L22.0002 10.9997L22.0002 12.9997L13.0001 12.9999L13.0001 21.9998L11.0001 21.9998L11.0001 12.9999L2.00004 13.0001L2 11.0001L11.0001 10.9999L11 2.00025L13 2.00024L13.0001 10.9999Z"></path></svg></span> ';
		
		/** Add New Snippet – also by type */
		$wp_admin_bar->add_node( array(
			'id'     => 'wcbqn-add-script',
			'title'  => $icon_add . esc_html__( 'Add New', 'wpcodebox-quicknav' ),
			'href'   => esc_url( WCBQN_WPCodeBox_Adapter::url( 0, 0 ) ),
			'parent' => 'wcbqn-group-new',
			'meta'   => array( 'class' => 'has-icon' ),
		) );
		
		/** WP's own "New Content" section: Add New Script */
		$wp_admin_bar->add_node( array(
			'id'     => 'wcbqn-wpnewcontent-add-script',
			'title'  => esc_html__( 'Code Snippet', 'wpcodebox-quicknav' ),
			'href'   => esc_url( WCBQN_WPCodeBox_Adapter::url( 0, 0 ) ),
			'parent' => 'new-content',
		) );
	}
	/**
	 * Add the settings group navigation section.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar WordPress admin-bar object to populate.
	 * @return WP_Admin_Bar Resolved result.
	 */
	private function add_settings_group( $wp_admin_bar ) {
		
		$wp_admin_bar->add_group( array(
			'id'     => 'wcbqn-group-settings',
			'parent' => 'ddw-wpcodebox-quicknav',
		) );
		
		//$this->add_settings_submenu( $wp_admin_bar );
		$this->add_expert_submenu( $wp_admin_bar );
	}
	/**
	 * Add the expert submenu navigation section.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar WordPress admin-bar object to populate.
	 * @return WP_Admin_Bar Resolved result.
	 */
	private function add_expert_submenu( $wp_admin_bar ) {
		
		if ( ! $this->is_expert_mode() ) return $wp_admin_bar;
		
		$icon_dash = '<span class="icon-svg"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M20 13C20 15.2091 19.1046 17.2091 17.6569 18.6569L19.0711 20.0711C20.8807 18.2614 22 15.7614 22 13 22 7.47715 17.5228 3 12 3 6.47715 3 2 7.47715 2 13 2 15.7614 3.11929 18.2614 4.92893 20.0711L6.34315 18.6569C4.89543 17.2091 4 15.2091 4 13 4 8.58172 7.58172 5 12 5 16.4183 5 20 8.58172 20 13ZM15.293 8.29297 10.793 12.793 12.2072 14.2072 16.7072 9.70718 15.293 8.29297Z"></path></svg></span> ';
		
		if ( defined( 'SYSTEM_DASHBOARD_VERSION' ) ) {
			$wp_admin_bar->add_node( array(
				'id'     => 'wcbqn-system-dashboard',
				'title'  => $icon_dash . esc_html__( 'System Dashboard', 'wpcodebox-quicknav' ),
				'href'   => esc_url( admin_url( 'index.php?page=system-dashboard' ) ),
				'parent' => 'wcbqn-group-settings',
				'meta'   => array( 'class' => 'has-icon' ),
			) );
		}
		
		$icon_info = '<span class="icon-svg"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M20 22H4C3.44772 22 3 21.5523 3 21V3C3 2.44772 3.44772 2 4 2H20C20.5523 2 21 2.44772 21 3V21C21 21.5523 20.5523 22 20 22ZM19 20V4H5V20H19ZM7 6H11V10H7V6ZM7 12H17V14H7V12ZM7 16H17V18H7V16ZM13 7H17V9H13V7Z"></path></svg></span> ';
		
		$wp_admin_bar->add_node( array(
			'id'     => 'wcbqn-sitehealth-info',
			'title'  => $icon_info . esc_html__( 'Site Health Info', 'wpcodebox-quicknav' ),
			'href'   => esc_url( admin_url( 'site-health.php?tab=debug' ) ),
			'parent' => 'wcbqn-group-settings',
			'meta'   => array( 'class' => 'has-icon' ),
		) );
		
		$icon_code = '<span class="icon-svg"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3 3H21C21.5523 3 22 3.44772 22 4V20C22 20.5523 21.5523 21 21 21H3C2.44772 21 2 20.5523 2 20V4C2 3.44772 2.44772 3 3 3ZM4 5V19H20V5H4ZM12 15H18V17H12V15ZM8.66685 12L5.83842 9.17157L7.25264 7.75736L11.4953 12L7.25264 16.2426L5.83842 14.8284L8.66685 12Z"></path></svg></span> ';
		
		if ( defined( 'VARIABLE_INSPECTOR_VERSION' ) ) {
			$wp_admin_bar->add_node( array(
				'id'     => 'wcbqn-variable-inspector',
				'title'  => $icon_code . esc_html__( 'Variable Inspector', 'wpcodebox-quicknav' ),
				'href'   => esc_url( admin_url( 'tools.php?page=variable-inspector' ) ),
				'parent' => 'wcbqn-group-settings',
				'meta'   => array( 'class' => 'has-icon' ),
			) );
		}
		
		$icon_bug = '<span class="icon-svg"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M13 19.9C15.2822 19.4367 17 17.419 17 15V12C17 11.299 16.8564 10.6219 16.5846 10H7.41538C7.14358 10.6219 7 11.299 7 12V15C7 17.419 8.71776 19.4367 11 19.9V14H13V19.9ZM5.5358 17.6907C5.19061 16.8623 5 15.9534 5 15H2V13H5V12C5 11.3573 5.08661 10.7348 5.2488 10.1436L3.0359 8.86602L4.0359 7.13397L6.05636 8.30049C6.11995 8.19854 6.18609 8.09835 6.25469 8H17.7453C17.8139 8.09835 17.88 8.19854 17.9436 8.30049L19.9641 7.13397L20.9641 8.86602L18.7512 10.1436C18.9134 10.7348 19 11.3573 19 12V13H22V15H19C19 15.9534 18.8094 16.8623 18.4642 17.6907L20.9641 19.134L19.9641 20.866L17.4383 19.4077C16.1549 20.9893 14.1955 22 12 22C9.80453 22 7.84512 20.9893 6.56171 19.4077L4.0359 20.866L3.0359 19.134L5.5358 17.6907ZM8 6C8 3.79086 9.79086 2 12 2C14.2091 2 16 3.79086 16 6H8Z"></path></svg></span> ';
		
		/**
		 * We need double check here as there is the "Downdload Manager" plugin
		 *   with the same 'DLM' prefix & constant.
		 */
		if ( defined( 'DLM_SLUG' ) && 'debug-log-manager' === DLM_SLUG ) {
			$wp_admin_bar->add_node( array(
				'id'     => 'wcbqn-debuglog-manager',
				'title'  => $icon_bug . esc_html__( 'Debug Log Manager', 'wpcodebox-quicknav' ),
				'href'   => esc_url( admin_url( 'tools.php?page=debug-log-manager' ) ),
				'parent' => 'wcbqn-group-settings',
				'meta'   => array( 'class' => 'has-icon' ),
			) );
		}
		
		$icon_codebox = '<span class="icon-svg"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3 3H21C21.5523 3 22 3.44772 22 4V20C22 20.5523 21.5523 21 21 21H3C2.44772 21 2 20.5523 2 20V4C2 3.44772 2.44772 3 3 3ZM4 5V19H20V5H4ZM20 12L16.4645 15.5355L15.0503 14.1213L17.1716 12L15.0503 9.87868L16.4645 8.46447L20 12ZM6.82843 12L8.94975 14.1213L7.53553 15.5355L4 12L7.53553 8.46447L8.94975 9.87868L6.82843 12ZM11.2443 17H9.11597L12.7557 7H14.884L11.2443 17Z"></path></svg></span> ';
		
		if ( defined( 'DPDEVKIT_URL' ) && current_user_can( 'manage_options' ) ) {
			$wp_admin_bar->add_node( array(
				'id'     => 'wcbqn-devkitpro',
				'title'  => $icon_codebox . 'DevKit Pro',
				'href'   => esc_url( admin_url( 'admin.php?page=devkit' ) ),
				'parent' => 'wcbqn-group-settings',
				'meta'   => array( 'class' => 'has-icon' ),
			) );
			
			$wp_admin_bar->add_node( array(
				'id'     => 'wcbqn-devkitpro-filemanager',
				'title'  => esc_html__( 'File Manager', 'wpcodebox-quicknav' ),
				'href'   => esc_url( admin_url( 'admin.php?page=devkit&tab=file-manager' ) ),
				'parent' => 'wcbqn-devkitpro',
				'meta'   => array( 'target' => '_blank' ),
			) );
			
			$wp_admin_bar->add_node( array(
				'id'     => 'wcbqn-devkitpro-errorlogs',
				'title'  => esc_html__( 'Error Logs', 'wpcodebox-quicknav' ),
				'href'   => esc_url( admin_url( 'admin.php?page=devkit&tab=error-log' ) ),
				'parent' => 'wcbqn-devkitpro',
				'meta'   => array( 'target' => '_blank' ),
			) );
			
			if ( 'yes' === get_option( 'dpdevkit_adminer' ) ) {
				$wp_admin_bar->add_node( array(
					'id'     => 'wcbqn-devkitpro-adminer',
					'title'  => esc_html__( 'Adminer: Manage DB', 'wpcodebox-quicknav' ),
					'href'   => esc_url( site_url() . '/devkit-adminer' ),
					'parent' => 'wcbqn-devkitpro',
					'meta'   => array( 'target' => '_blank' ),
				) );
			}
			
			$wp_admin_bar->add_node( array(
				'id'     => 'wcbqn-devkitpro-tools',
				'title'  => esc_html__( 'Tools', 'wpcodebox-quicknav' ),
				'href'   => esc_url( admin_url( 'admin.php?page=devkit&tab=tools' ) ),
				'parent' => 'wcbqn-devkitpro',
				'meta'   => array( 'target' => '_blank' ),
			) );
		}
		
	}
	/**
	 * Add the library group navigation section.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar WordPress admin-bar object to populate.
	 * @return WP_Admin_Bar Resolved result.
	 */
	private function add_library_group( $wp_admin_bar ) {
		
		if ( ! WCBQN_Config::resources() || WCBQN_Config::flag( 'WCBQN_DISABLE_LIBRARY', false ) ) {
			return $wp_admin_bar;
		}
		
		$wp_admin_bar->add_group( array(
			'id'     => 'wcbqn-library',
			'parent' => 'ddw-wpcodebox-quicknav',
			'meta'   => array( 'class' => 'ab-sub-secondary' ),
		) );
	}
	/**
	 * Add the libraries submenu navigation section.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar WordPress admin-bar object to populate.
	 * @return WP_Admin_Bar Resolved result.
	 */
	private function add_libraries_submenu( $wp_admin_bar ) {
		
		$icon = '<span class="icon-svg"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12L18.3431 17.6569L16.9289 16.2426L21.1716 12L16.9289 7.75736L18.3431 6.34315L24 12ZM2.82843 12L7.07107 16.2426L5.65685 17.6569L0 12L5.65685 6.34315L7.07107 7.75736L2.82843 12ZM9.78845 21H7.66009L14.2116 3H16.3399L9.78845 21Z"></path></svg></span> ';
		
		$wp_admin_bar->add_node( array(
			'id'     => 'wcbqn-libraries',
			'title'  => $icon . esc_html__( 'Find Snippets', 'wpcodebox-quicknav' ),
			'href'   => '#',
			'parent' => 'wcbqn-library',
			'meta'   => array( 'class' => 'has-icon' ),
		) );
	
		$codelibs = array(
			'codesnippets-cloud' => array(
				'title' => __( 'Code Snippets Cloud', 'wpcodebox-quicknav' ),
				'url'   => 'https://codesnippets.cloud/search',
			),
			'wpsnippets-library' => array(
				'title' => __( 'WP Snippets Library', 'wpcodebox-quicknav' ),
				'url'   => 'https://wpsnippets.org/library/',
			),
			'websquadron-codes' => array(
				'title' => __( 'Codes by Web Squadron', 'wpcodebox-quicknav' ),
				'url'   => 'https://learn.websquadron.co.uk/codes/',
			),
			'wpsnippetclub-archive' => array(
				'title' => __( 'WP SnippetClub Archive', 'wpcodebox-quicknav' ),
				'url'   => 'https://wpsnippet.club/snippet/',
			),
			'dplugins-code' => array(
				'title' => __( 'Snippets Library by dPlugins', 'wpcodebox-quicknav' ),
				'url'   => 'https://code.dplugins.com/',
			),
			'wpcodebin' => array(
				'title' => __( 'WPCodeBin by WPCodeBox', 'wpcodebox-quicknav' ),
				'url'   => 'https://wpcodebin.com/',
			),
			'wpcode-library' => array(
				'title' => __( 'Snippets Library by WPCode', 'wpcodebox-quicknav' ),
				'url'   => 'https://library.wpcode.com/',
			),
		);
	
		/** Make code libs array filterable */
		/**
		 * Filter external snippet-library links. Return the replacement link array.
		 *
		 * @since 1.0.0
		 * @param array $codelibs Existing link definitions.
		 */
		$codelibs = apply_filters( 'wcbqn_resource_links', $codelibs );
		$codelibs = is_array( $codelibs ) ? $codelibs : [];
	
		foreach ( $codelibs as $id => $info ) {
			if ( ! is_array( $info ) || ! is_string( $info['title'] ?? null ) || ! is_string( $info['url'] ?? null ) ) { continue; }
			$wp_admin_bar->add_node( array(
				'id'     => 'wcbqn-library-link-' . sanitize_key( $id ),
				'title'  => esc_html( $info[ 'title' ] ),
				'href'   => esc_url( $info[ 'url' ] ),
				'parent' => 'wcbqn-libraries',
				'meta'   => array( 'target' => '_blank', 'rel' => 'nofollow noopener noreferrer' ),
			) );
		}  // end foreach
	}
	/**
	 * Add the footer group navigation section.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar WordPress admin-bar object to populate.
	 * @return WP_Admin_Bar Resolved result.
	 */
	private function add_footer_group( $wp_admin_bar ) {
		
		if ( WCBQN_Config::flag( 'WCBQN_DISABLE_FOOTER', false ) ) {
			return $wp_admin_bar;
		}
		
		$wp_admin_bar->add_group( array(
			'id'     => 'wcbqn-footer',
			'parent' => 'ddw-wpcodebox-quicknav',
			'meta'   => array( 'class' => 'ab-sub-secondary' ),
		) );
	}
	/**
	 * Add the links submenu navigation section.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar WordPress admin-bar object to populate.
	 * @return WP_Admin_Bar Resolved result.
	 */
	private function add_links_submenu( $wp_admin_bar ) {
		
		$icon = '<span class="icon-svg"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M10 6V8H5V19H16V14H18V20C18 20.5523 17.5523 21 17 21H4C3.44772 21 3 20.5523 3 20V7C3 6.44772 3.44772 6 4 6H10ZM21 3V11H19L18.9999 6.413L11.2071 14.2071L9.79289 12.7929L17.5849 5H13V3H21Z"></path></svg></span> ';
		
		$wp_admin_bar->add_node( array(
			'id'     => 'wcbqn-links',
			'title'  => $icon . esc_html__( 'Links', 'wpcodebox-quicknav' ),
			'href'   => '#',
			'parent' => 'wcbqn-footer',
			'meta'   => array( 'class' => 'has-icon' ),
		) );

		$links = array(
			'wpcodebox-website' => array(
				'title' => __( 'WPCodeBox', 'wpcodebox-quicknav' ),
				'url'   => 'https://wpcodebox.com/',
			),
			'as-emergency' => array(
				'title' => __( 'Emergency Fixes 🔥', 'wpcodebox-quicknav' ),
				'url'   => 'https://docs.wpcodebox.com/safe_mode',
			),
			'cp-blog' => array(
				'title' => __( 'WPCodeBox website', 'wpcodebox-quicknav' ),
				'url'   => 'https://docs.wpcodebox.com/',
			),
			'cp-youtube' => array(
				'title' => __( 'WPCodeBox resources', 'wpcodebox-quicknav' ),
				'url'   => 'https://wpcodebox.com/',
			),
			'cp-fb-group' => array(
				'title' => __( 'WPCodeBox support', 'wpcodebox-quicknav' ),
				'url'   => 'https://wpcodebox.com/contact/',
			),
		);

		/** Make links array filterable */
		/**
		 * Filter external WPCodeBox resource links. Return the replacement link array.
		 *
		 * @since 1.0.0
		 * @param array $links Existing link definitions.
		 */
		$links = apply_filters( 'wcbqn_links', $links );
		$links = is_array( $links ) ? $links : [];
		
		foreach ( $links as $id => $info ) {
			if ( ! is_array( $info ) || ! is_string( $info['title'] ?? null ) || ! is_string( $info['url'] ?? null ) ) { continue; }
			$wp_admin_bar->add_node( array(
				'id'     => 'wcbqn-link-' . sanitize_key( $id ),
				'title'  => esc_html( $info[ 'title' ] ),
				'href'   => esc_url( $info[ 'url' ] ),
				'parent' => 'wcbqn-links',
				'meta'   => array( 'target' => '_blank', 'rel' => 'nofollow noopener noreferrer' ),
			) );
		}  // end foreach
	}
	/**
	 * Add the about submenu navigation section.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar WordPress admin-bar object to populate.
	 * @return WP_Admin_Bar Resolved result.
	 */
	private function add_about_submenu( $wp_admin_bar ) {
		
		$icon = '<span class="icon-svg"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M17.841 15.659L18.017 15.836L18.1945 15.659C19.0732 14.7803 20.4978 14.7803 21.3765 15.659C22.2552 16.5377 22.2552 17.9623 21.3765 18.841L18.0178 22.1997L14.659 18.841C13.7803 17.9623 13.7803 16.5377 14.659 15.659C15.5377 14.7803 16.9623 14.7803 17.841 15.659ZM12 14V16C8.68629 16 6 18.6863 6 22H4C4 17.6651 7.44784 14.1355 11.7508 14.0038L12 14ZM12 1C15.315 1 18 3.685 18 7C18 10.2397 15.4357 12.8776 12.225 12.9959L12 13C8.685 13 6 10.315 6 7C6 3.76034 8.56434 1.12237 11.775 1.00414L12 1ZM12 3C9.78957 3 8 4.78957 8 7C8 9.21043 9.78957 11 12 11C14.2104 11 16 9.21043 16 7C16 4.78957 14.2104 3 12 3Z"></path></svg></span> ';
		
		$wp_admin_bar->add_node( array(
			'id'     => 'wcbqn-about',
			'title'  => $icon . esc_html__( 'About', 'wpcodebox-quicknav' ),
			'href'   => '#',
			'parent' => 'wcbqn-footer',
			'meta'   => array( 'class' => 'has-icon' ),
		) );

		$about_links = array(
			'author' => array(
				'title' => __( 'Author: David Decker', 'wpcodebox-quicknav' ),
				'url'   => 'https://deckerwebplugins.com/',
			),
			'github' => array(
				'title' => __( 'Plugin on GitHub', 'wpcodebox-quicknav' ),
				'url'   => 'https://github.com/deckerweb/wpcodebox-quicknav',
			),
			'kofi' => array(
				'title' => __( 'Buy Me a Coffee', 'wpcodebox-quicknav' ),
				'url'   => 'https://ko-fi.com/deckerweb',
			),
		);

		foreach ( $about_links as $id => $info ) {
			$wp_admin_bar->add_node( array(
				'id'     => 'wcbqn-about-' . sanitize_key( $id ),
				'title'  => esc_html( $info[ 'title' ] ),
				'href'   => esc_url( $info[ 'url' ] ),
				'parent' => 'wcbqn-about',
				'meta'   => array( 'target' => '_blank', 'rel' => 'nofollow noopener noreferrer' ),
			) );
		}  // end foreach
	}
	
	/**
	 * Add additional plugin related info to the Site Health Debug Info section.
	 *
	 * @link https://make.wordpress.org/core/2019/04/25/site-health-check-in-5-2/
	 *
	 * @param array $debug_info Array holding all Debug Info items.
	 * @return array Modified array of Debug Info.
	 */
	public function site_health_debug_info( $debug_info ) {
		$fields = [
			'quicknav' => [ 'label' => __( 'Plugin version', 'wpcodebox-quicknav' ), 'value' => WCBQN_Config::VERSION ],
			'wpcodebox' => [ 'label' => 'WPCodeBox', 'value' => defined( 'WPCODEBOX2_VERSION' ) ? WPCODEBOX2_VERSION : __( 'Not active', 'wpcodebox-quicknav' ) ],
			'capability' => [ 'label' => 'WCBQN_VIEW_CAPABILITY', 'value' => WCBQN_Config::capability() ],
			'safe_mode' => [ 'label' => __( 'Safe Mode', 'wpcodebox-quicknav' ), 'value' => $this->is_safe_mode_active() ? __( 'Enabled', 'wpcodebox-quicknav' ) : __( 'Disabled', 'wpcodebox-quicknav' ) ],
			'limit' => [ 'label' => __( 'Menu entry limit', 'wpcodebox-quicknav' ), 'value' => WCBQN_Config::limit() ],
		];
		$labels = [ 'counter' => __( 'Snippet count', 'wpcodebox-quicknav' ), 'expert' => __( 'Developer links', 'wpcodebox-quicknav' ), 'library' => __( 'Resource links', 'wpcodebox-quicknav' ), 'footer' => __( 'Footer links', 'wpcodebox-quicknav' ) ];
		foreach ( [ 'counter' => WCBQN_Config::counter(), 'expert' => WCBQN_Config::expert(), 'library' => ! WCBQN_Config::flag( 'WCBQN_DISABLE_LIBRARY', false ), 'footer' => ! WCBQN_Config::flag( 'WCBQN_DISABLE_FOOTER', false ) ] as $key => $value ) {
			$fields[$key] = [ 'label' => $labels[$key], 'value' => $value ? __( 'Enabled', 'wpcodebox-quicknav' ) : __( 'Disabled', 'wpcodebox-quicknav' ) ];
		}
		$debug_info['wpcodebox-quicknav'] = [ 'label' => 'WPCodeBox QuickNav', 'fields' => $fields ];
		return $debug_info;
	}
}
