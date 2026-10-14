# Changelog

All notable changes to Shuffles Youtube Shorts are documented here. GitHub is the source of truth for release notes, and this file is also displayed in the plugin settings screen.

## 1.0.1 — 2026-10-14

- Added an automatic public YouTube Shorts feed fallback when an API key is missing, malformed, rejected, or unavailable.
- Made API keys optional for channel galleries while preserving YouTube Data API support and server-side key storage.
- Improved the connection tester so it reports whether the API or public feed supplied the videos.
- Prevented a bad saved credential from breaking an otherwise public channel gallery.
- Added site origin and referrer headers so keys restricted to the Byron Rides web origin can be used for server-side API requests.

## 1.0.0 — 2026-10-14

- Initial release.
- Added automatically refreshed YouTube Shorts feeds from channel IDs, @handles, channel URLs, playlist IDs, and playlist URLs.
- Added a responsive, theme-aware 9:16 gallery with configurable columns, rows, and titles.
- Added secure server-side YouTube Data API key storage and a connection tester.
- Added the `[shuffles_youtube_shorts]` shortcode and an optional Elementor widget.
- Added GitHub-hosted version checks and changelog display.
- Added privacy-enhanced YouTube embeds and transient caching to reduce API quota usage.

