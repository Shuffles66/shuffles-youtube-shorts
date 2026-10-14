=== Shuffles Youtube Shorts ===
Contributors: shuffles
Tags: youtube, shorts, video, gallery, elementor
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

Responsive YouTube Shorts galleries that automatically refresh from a channel or playlist.

== Description ==

Shuffles Youtube Shorts provides a secure, server-side YouTube Data API integration for responsive vertical-video galleries. Configure a default channel or curated Shorts playlist, then display the gallery with a shortcode or the included Elementor widget.

Features:

* Server-side API key storage.
* Automatic refresh with configurable caching.
* Channel IDs, @handles, channel URLs, playlist IDs, and playlist URLs.
* Responsive 9:16 video grids that inherit theme colours and backgrounds.
* Configurable columns, rows, and titles.
* Privacy-enhanced youtube-nocookie.com embeds.
* Elementor widget integration when Elementor is active.

== Installation ==

1. Upload and activate the plugin.
2. Open YouTube Shorts in the WordPress admin menu.
3. Add a YouTube Data API v3 key and a default channel or playlist.
4. Test and save the connection.
5. Add `[shuffles_youtube_shorts cols="4" rows="2"]` to a page.

== Shortcode ==

`[shuffles_youtube_shorts channel="@ByronRides" playlist="" cols="4" rows="2" show_titles="yes"]`

The playlist takes priority when both channel and playlist are supplied.

== Changelog ==

= 1.0.0 =
* Initial release.
