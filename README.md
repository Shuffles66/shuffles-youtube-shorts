# Shuffles Youtube Shorts

A WordPress plugin for responsive YouTube Shorts galleries that automatically refresh from a nominated channel or playlist.

## Features

- Automatic public YouTube Shorts feed fallback with no API key required for channel galleries.
- Optional YouTube Data API v3 key stored server-side in WordPress.
- Channel ID, `@handle`, channel URL, playlist ID, and playlist URL support.
- Automatic refresh through configurable WordPress transient caching.
- Responsive vertical-video gallery that inherits theme colour and background values.
- Single-player coordination: starting one Short pauses all other embedded Shorts.
- Shortcode support plus an Elementor widget in a dedicated Shuffles category.
- GitHub-hosted update metadata and changelog.
- Privacy-enhanced `youtube-nocookie.com` embeds.
- Branded plugin icon and a polished release-history display in WordPress.

## Shortcode

```text
[shuffles_youtube_shorts channel="@ByronRides" playlist="" cols="4" rows="2" show_titles="yes"]
```

The `playlist` attribute takes priority over `channel`. Using a curated Shorts playlist gives the most exact result because the YouTube Data API does not expose an `isShort` field. Channel mode selects public, embeddable uploads up to three minutes long.

## Release process

1. Update the `Version` header in `shuffles-youtube-shorts.php`.
2. Update `version` and compatibility values in `update.json`.
3. Add release notes to `CHANGELOG.md`.
4. Commit and push the release source.
5. Create a Git tag named `vX.Y.Z` and publish the matching GitHub release. The included workflow packages the plugin and attaches `shuffles-youtube-shorts.zip` to the release.

Installed sites read `update.json` from the `main` branch and download the matching release asset.

## License

GPL-2.0-or-later.

