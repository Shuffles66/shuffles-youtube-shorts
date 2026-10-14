=== Shuffles Youtube Shorts ===
Contributors: shuffles
Tags: youtube, shorts, video, gallery, elementor
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later

Responsive YouTube Shorts galleries that automatically refresh from a channel or playlist.

== Description ==

Shuffles Youtube Shorts provides responsive vertical-video galleries from the public YouTube Shorts feed, with optional server-side YouTube Data API integration. Configure a default channel or curated Shorts playlist, then display the gallery with a shortcode or the included Elementor widget.

Features:

* Automatic public-feed fallback when an API key is missing or invalid.
* Optional server-side API key storage.
* Automatic refresh with configurable caching.
* Channel IDs, @handles, channel URLs, playlist IDs, and playlist URLs.
* Responsive 9:16 video grids that inherit theme colours and backgrounds.
* Configurable columns, rows, and titles.
* Privacy-enhanced youtube-nocookie.com embeds.
* Elementor widget integration when Elementor is active.

== Installation ==

1. Upload and activate the plugin.
2. Open YouTube Shorts in the WordPress admin menu.
3. Add a default channel or playlist. A YouTube Data API v3 key is optional for channel galleries.
4. Test and save the connection.
5. Add `[shuffles_youtube_shorts cols="4" rows="2"]` to a page.

== Shortcode ==

`[shuffles_youtube_shorts channel="@ByronRides" playlist="" cols="4" rows="2" show_titles="yes"]`

The playlist takes priority when both channel and playlist are supplied.

== Changelog ==

= 1.0.1 =
* Added public Shorts feed fallback so a missing or invalid API key no longer breaks channel galleries.
* Added site origin and referrer headers for API keys configured with HTTP-referrer restrictions.

= 1.0.0 =
* Initial release.
