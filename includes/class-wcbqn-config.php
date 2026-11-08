<?php
/** Adapted from Advanced Scripts QuickNav 1.3.2, David Decker, GPL-2.0-or-later. See docs/credits.txt. */
/** Effective personal preferences; existing constants take precedence. */
defined( 'ABSPATH' ) || exit;
/** Resolve permissions and site-scoped personal display preferences. */
final class WCBQN_Config {
	public const VERSION = '1.0.1';

	/**
	 * Build the current-site user-metadata key.
	 *
	 * @param string $suffix Metadata-key suffix.
	 * @return string Site-scoped user-metadata key.
	 */
	public static function key( string $suffix ): string { return 'wcbqn_' . $suffix . '_' . get_current_blog_id(); }

	/**
	 * Read personal preferences with conservative defaults.
	 *
	 * @return array Personal counter, expert-links and entry-limit preferences.
	 */
	public static function preferences(): array {
		$value = get_user_meta( get_current_user_id(), self::key( 'preferences' ), true );
		return array_merge( [ 'counter' => false, 'expert' => true, 'search' => true, 'resources' => true, 'limit' => 40, 'toolbar_name' => 'WPCodeBox', 'shortcut_fullscreen' => 'f', 'shortcut_focus' => 'e', 'remember_view' => false ], is_array( $value ) ? array_intersect_key( $value, [ 'counter' => true, 'expert' => true, 'search' => true, 'resources' => true, 'limit' => true, 'toolbar_name' => true, 'shortcut_fullscreen' => true, 'shortcut_focus' => true, 'remember_view' => true ] ) : [] );
	}

	/** Read the opted-in view independently from other preferences. @return string Full, focus or normal (empty); scoped to user and site. */
	public static function remembered_view(): string {
		$value = get_user_meta( get_current_user_id(), self::key( 'last_view' ), true );
		return ! empty( self::preferences()['remember_view'] ) && in_array( $value, [ 'full', 'focus' ], true ) ? $value : '';
	}

	/**
	 * Resolve a bounded personal shortcut key; an empty key disables the action.
	 * @param mixed $key Candidate physical letter key.
	 * @return string Allowed lowercase letter or an empty disabled value.
	 */
	public static function shortcut_key( $key ): string {
		return is_string( $key ) && in_array( $key, [ 'f', 'e', 'j', 'u' ], true ) ? $key : '';
	}

	/**
	 * Normalize a single-line toolbar name; blank values restore the default.
	 * @param mixed $value Candidate personal label or trusted configuration constant.
	 * @return string Plain-text label of at most sixty Unicode characters.
	 */
	public static function clean_toolbar_name( $value ): string {
		$value = is_string( $value ) ? trim( sanitize_text_field( $value ) ) : '';
		$value = wp_html_excerpt( $value, 60, '' );
		return '' !== $value ? $value : 'WPCodeBox';
	}

	/** Resolve the personal toolbar label with legacy constant precedence. @return string Effective plain-text label. */
	public static function toolbar_name(): string {
		return self::clean_toolbar_name( defined( 'WCBQN_NAME_IN_ADMINBAR' ) ? WCBQN_NAME_IN_ADMINBAR : self::preferences()['toolbar_name'] );
	}

	/**
	 * Resolve a boolean configuration constant.
	 *
	 * @param string $constant Configuration constant name.
	 * @param bool $default Fallback flag when the constant is absent.
	 * @return bool Effective boolean value after constant precedence.
	 */
	public static function flag( string $constant, bool $default ): bool {
		if ( ! defined( $constant ) ) { return $default; }
		$value = constant( $constant );
		return true === $value || 1 === $value || in_array( strtolower( (string) $value ), [ 'yes', 'true', '1', 'on' ], true );
	}

	/**
	 * Resolve the required navigation capability.
	 *
	 * @return string Required WordPress capability name.
	 */
	public static function capability(): string {
		return defined( 'WCBQN_VIEW_CAPABILITY' ) && is_string( WCBQN_VIEW_CAPABILITY ) && '' !== WCBQN_VIEW_CAPABILITY ? sanitize_key( WCBQN_VIEW_CAPABILITY ) : 'activate_plugins';
	}

	/**
	 * Check capabilities and the optional user allowlist.
	 *
	 * @return bool Whether the current user may access QuickNav.
	 */
	public static function allowed(): bool {
		if ( ! current_user_can( 'manage_options' ) || ! current_user_can( self::capability() ) ) { return false; }
		if ( ! defined( 'WCBQN_ENABLED_USERS' ) ) { return true; }
		return in_array( get_current_user_id(), array_map( 'absint', (array) WCBQN_ENABLED_USERS ), true );
	}

	/**
	 * Check whether navigation may appear in the current request.
	 *
	 * @return bool Whether the current request may display the toolbar.
	 */
	public static function visible(): bool { return ! is_network_admin() && self::allowed() && ( WCBQN_WPCodeBox_Adapter::available() || WCBQN_Functionality::plugins() ) && is_admin_bar_showing(); }

	/**
	 * Resolve the effective snippet-counter preference.
	 *
	 * @return bool Whether to display the snippet counter.
	 */
	public static function counter(): bool { return self::flag( 'WCBQN_COUNTER', (bool) self::preferences()['counter'] ); }

	/**
	 * Resolve the effective developer-links preference.
	 *
	 * @return bool Whether to display developer links.
	 */
	public static function expert(): bool { return self::flag( 'WCBQN_EXPERT_MODE', (bool) self::preferences()['expert'] ); }

	/** Read the personal resource-links preference. @return bool Whether external resource groups are shown. */
	public static function resources(): bool { return (bool) self::preferences()['resources']; }

	/** Read the personal QuickNav search preference. @return bool Whether enhancement is enabled. */
	public static function search(): bool { return (bool) self::preferences()['search']; }

	/**
	 * Clamp the effective menu-entry budget.
	 *
	 * @return int Entry limit clamped between 10 and 200.
	 */
	public static function limit(): int {
		$value = defined( 'WCBQN_MENU_LIMIT' ) ? WCBQN_MENU_LIMIT : self::preferences()['limit'];
		return max( 10, min( 200, absint( $value ) ) );
	}
}
