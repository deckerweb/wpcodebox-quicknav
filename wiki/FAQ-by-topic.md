# FAQ by topic

[Deutsch](https://github.com/deckerweb/wpcodebox-quicknav/wiki/Fragen-nach-Themen)

## Does active mean executed?

No. Plugin activation comes from WordPress. Enabled include means that a literal include_once entry exists in the generated main file. Conditions, hooks, run type, errors and Safe Mode affect execution. QuickNav does not log actual executions.

## Which WPCodeBox version is supported?

The integration supports only WPCodeBox 2 version 1.4.1 or newer. WPCodeBox V1 is not supported. With WPCodeBox 2 inactive, settings and the Functionality Plugin overview remain available. The QuickNav toolbar remains available when a managed Functionality Plugin is detected. Individual snippets are managed in WPCodeBox; direct editor links appear only when the integration is available.

## Does QuickNav modify generated plugins?

No. QuickNav reads configuration without executing plugin files for inspection. Manage plugins opens native WordPress plugin management to pause the entire Functionality Plugin. The upstream WPCodeBox disable operation can remove the generated plugin; use native WordPress deactivation for a simple pause.

## How do I leave fullscreen and focus?

Use Back to normal view at the top or press Escape. Close an open dialog or editor completion first. Views are temporary and reset on page navigation. Browser fullscreen is not enabled.

## What data is stored?

Favorites and display preferences per user and website, plus dated Functionality Plugin metadata per website. No snippet source or execution logs are stored. Deactivation and uninstall retain these records and all WPCodeBox data. Temporary update data is cleared; Library cleanup respects other installed hosts.

## What connects to external servers?

The Updater can check public stable GitHub releases. Automatic updates are not enabled. The optional Library online catalog is off by default. Requests send standard HTTP/WordPress/PHP metadata, without snippet code or favorites. Resource links contact their destination when opened.

## How does Multisite work?

Favorites, preferences and metadata snapshots remain site-specific. The overview distinguishes site and network activation. It lives in website administration; there is no network-wide snippet collection. Network-active plugins must be deactivated through Network Admin.

[User guide with further details](https://github.com/deckerweb/wpcodebox-quicknav/wiki/User-guide)
