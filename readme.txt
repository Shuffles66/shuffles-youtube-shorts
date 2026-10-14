=== Shuffles Youtube Shorts ===
Contributors: shuffles
Tags: youtube, shorts, video, gallery, elementor
Requires at least: 6.2
Tested up to: 7.1.3
Requires PHP: 7.4
Stable tag: 1.0.5
License: GPLv2 or later

Responsive YouTube Shorts galleries that automatically refresh from a channel or playlist.

== Description ==

Shuffles Youtube Shorts provides responsive vertical-video galleries from the public YouTube Shorts feed, with optional server-side YouTube Data API integration. Configure a default channel or curated Shorts playlist, then display the gallery with a shortcode or the included Elementor widget.

Features:

* Automatic public-feed fallback when an API key is missing or invalid.
* Optional server-side API key storage.
* Automatic refresh with configurable caching.
* Channel IDs, @handles, channel URLs, exact playlist titles, playlist IDs, and playlist URLs.
* Responsive 9:16 video grids that inherit theme colours and backgrounds.
* Configurable columns, rows, and titles.
* Privacy-enhanced youtube-nocookie.com embeds.
* Single-player coordination automatically pauses other Shorts when a video starts.
* Elementor widget integration in a dedicated Shuffles category when Elementor is active.

== Installation ==

1. Upload and activate the plugin.
2. Open YouTube Shorts in the WordPress admin menu.
3. Add a default channel or playlist. A YouTube Data API v3 key is optional for channel galleries.
4. Test and save the connection.
5. Add `[shuffles_youtube_shorts cols="4" rows="2"]` to a page.

== Shortcode ==

`[shuffles_youtube_shorts channel="@ByronRides" playlist="" cols="4" rows="2" show_titles="yes"]`

The playlist takes priority when both channel and playlist are supplied. An exact public playlist title is resolved inside the nominated channel.

== Changelog ==

= 1.0.5 =
* Fixed title and message contrast when a theme uses a CSS gradient over a different solid fallback colour.
* Evaluates every gradient colour stop and selects a theme colour that remains readable across the full background.
* Preserved the 1.0.4 playlist-title resolution and all existing settings.

= 1.0.4 =
* Added exact playlist-title support when a channel is also provided, including public-page resolution when the YouTube API is unavailable or restricted.
* Fixed theme contrast for validation, status, and error messages as well as successful gallery titles.
* Clarified playlist guidance in settings, shortcode help, and Elementor.
* Marked compatibility through WordPress 7.1.3 after validation on Byron Rides.

= 1.0.3 =
* Fixed unreadable video titles by selecting the highest-contrast colour from the active theme palette.
* Reworked the plugin artwork as a vivid, recognisable vertical-video and shuffle mark.
* Added 128px and 256px PNG icon fallbacks so WordPress update screens no longer show the generic plugin icon.
* Corrected the WordPress admin-menu icon size, colour, opacity, and hover treatment.
* Linked the Shuffles author credit to the official GitHub profile on the Plugins screen.

= 1.0.2 =
* Only one Short plays at a time; starting another sends a pause command to every other Shuffles player on the page.
* Added dynamic-player discovery for multiple galleries and Elementor-rendered content.
* Added a dedicated Shuffles Elementor category, current and legacy registration support, duplicate-registration protection, and current method signatures.
* Added a custom plugin icon and a responsive, card-based release history in the settings area.
* Reduced GitHub metadata caching to fifteen minutes and made Check again clear the plugin's update and changelog caches.
* Preserved all existing shortcodes and settings; reload an already-open Elementor editor after upgrading.

= 1.0.1 =
* Added public Shorts feed fallback so a missing or invalid API key no longer breaks channel galleries.
* Added site origin and referrer headers for API keys configured with HTTP-referrer restrictions.

= 1.0.0 =
* Initial release.
