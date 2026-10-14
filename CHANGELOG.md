# Release notes

> Clear, practical updates for the people who run Byron Rides—not a wall of developer jargon.

## 1.0.3 — 15 October 2026

### Theme integration

- Fixed dark video titles disappearing against the Byron Rides purple gallery section.
- The plugin now measures the gallery background and selects the highest-contrast colour from the active WordPress, Astra, or Elementor palette. It only falls back to black or white when no theme colour meets readable contrast.
- Title typography, spacing, and transparent card backgrounds still inherit the active theme; only the unreadable text colour is corrected.

### Brand icon

- Reworked the icon as a vivid purple, pink, cyan, and yellow vertical-video mark with a bold play button and shuffle arrows that remain legible at WordPress menu size.
- Added WordPress-compatible 128px and 256px PNG fallbacks alongside the scalable SVG, preventing update and plugin-information screens from reverting to the generic plugin icon.
- Corrected the admin-menu icon dimensions, full-colour opacity, and hover treatment throughout WordPress.
- Added the official Shuffles GitHub profile as the plugin Author URI, making the author name clickable on the WordPress Plugins screen.

### Upgrade impact

- No shortcode or settings changes are required.
- Existing galleries are recoloured automatically after the updated front-end script loads.

## 1.0.2 — 15 October 2026

### Playback behaviour

- Only one Short can play at a time. When a viewer starts another Short, the plugin sends a pause command to every other Shuffles player on the page.
- Enabled YouTube’s JavaScript player controls on each privacy-enhanced `youtube-nocookie.com` embed and supplies the site origin for secure player communication.
- Added automatic player discovery for galleries inserted or refreshed after page load, including Elementor editor previews and pages containing more than one gallery.

### Elementor integration

- Added a dedicated **Shuffles** category to Elementor’s widget panel. The widget is named **Shuffles YouTube Shorts** and is searchable using YouTube, Shorts, video, gallery, or Shuffles.
- Registered the widget with Elementor’s current widget manager while retaining a guarded legacy fallback for older installations.
- Prevented duplicate registration when more than one Elementor lifecycle hook fires.
- Updated the widget class method signatures for compatibility with current Elementor releases.

### Updates and release notes

- Added a custom purple-and-red vertical-video icon to the WordPress admin menu, plugin setup screen, and update metadata.
- Rebuilt the settings-page release history as responsive cards with version badges, dates, and grouped changes that remain readable on narrow screens.
- Reduced GitHub update metadata and changelog caching from six hours to fifteen minutes.
- WordPress **Check again** now clears this plugin’s cached GitHub metadata and release notes before checking, allowing a newly published version to appear immediately.

### Upgrade impact

- No shortcode changes are required, and existing channel, playlist, column, row, title, cache, and API-key settings are preserved.
- The public YouTube feed fallback introduced in 1.0.1 remains available when the API key is missing or rejected.
- After upgrading, reload any Elementor editor tab that was already open so its widget panel can discover the new Shuffles category.

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

