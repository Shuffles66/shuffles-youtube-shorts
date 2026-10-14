# Release notes

> Clear, practical updates for the people who run Byron Rides—not a wall of developer jargon.

## 1.0.2 — 14 October 2026

### New

- Introduced a custom Shuffles Youtube Shorts icon across the WordPress admin menu, setup screen, and update details.
- Added a redesigned release-history experience with version badges and clearly grouped changes.

### Improved

- Made release notes easier to scan before upgrading, with responsive cards that inherit the WordPress admin palette.
- Reduced GitHub metadata and changelog cache time from six hours to fifteen minutes.

### Fixed

- WordPress “Check again” now clears the plugin’s GitHub metadata and changelog caches, so newly published updates appear immediately.

## 1.0.1 — 14 October 2026

### Reliability

- Added an automatic public YouTube Shorts feed fallback when an API key is missing, malformed, rejected, quota-limited, or unavailable.
- Prevented a bad saved credential from breaking an otherwise public channel gallery.
- Added site origin and referrer headers so keys restricted to the Byron Rides web origin can be used for server-side API requests.

### Improved

- Made API keys optional for channel galleries while preserving YouTube Data API support and server-side key storage.
- Updated the connection tester to report whether the YouTube Data API or public Shorts feed supplied the videos.

## 1.0.0 — 14 October 2026

### Foundation

- Launched automatically refreshed YouTube Shorts feeds for channel IDs, @handles, channel URLs, playlist IDs, and playlist URLs.
- Added a responsive, theme-aware 9:16 gallery with configurable columns, rows, and titles.
- Added secure server-side YouTube Data API key storage and a connection tester.
- Added the `[shuffles_youtube_shorts]` shortcode and an optional Elementor widget.
- Added GitHub-hosted version checks, release notes, privacy-enhanced embeds, and transient caching.

