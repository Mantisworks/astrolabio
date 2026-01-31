=== Astrolabio ===
Contributors: nasa_studiastronomici
Tags: astronomy, map, star chart, messier, ecliptic
Requires at least: 5.0
Tested up to: 6.9
Stable tag: 1.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Interactive star map and observation report generator for real-time celestial tracking.

== Description ==

Astrolabio is the official tool of the **Nuova Associazione Studi Astronomici**. It transforms your WordPress site into an interactive astrolabe. 

The plugin calculates the real-time position of stars, planets, and Deep Sky Objects (DSO) based on the user's GPS coordinates. It features full mobile support with pinch-to-zoom, map panning, ecliptic path calculation, and the generation of high-quality printable observation reports.

== Installation ==

1. Upload the `astrolabio` folder to the `/wp-content/plugins/` directory.
2. Ensure the `/data/` folder contains the necessary GeoJSON files (`stars.6.geojson`, `messier.geojson`, `milkyway.geojson`, etc.).
3. Activate the plugin through the 'Plugins' menu in WordPress.
4. Insert the shortcode `[astro_observatory]` into any page or post.

== Frequently Asked Questions ==

= How do I update my position? =
Click the "AGGIORNA GPS" button within the plugin interface to automatically sync your latitude, longitude, and local time.

= Does it work on smartphones? =
Yes, Astrolabio supports native touch events, including pinch-to-zoom and finger dragging.

== Screenshots ==

1. The celestial map interface featuring the altazimuth grid and the ecliptic line.
2. The printable observation report with the Nuova Associazione Studi Astronomici header.
3. The celestial objects table sorted by magnitude (brightness).

== Changelog ==

= 1.5 =
* Fixed "no_short_description_present" warning for WordPress.org repository.
* Updated "Tested up to" header to version 6.9.

= 1.4 =
* Implemented security hardening with output escaping (esc_attr, esc_url).
* Switched to current_time() for improved timezone compatibility.

= 1.0 =
* Enabled mouse panning for desktop users.
* Optimized printing margins for the NASA official report.
* Added the "Astrolabio" administration menu in the WordPress backend.
* Introduced Touch and Pinch-to-zoom support for mobile devices.
* Added Ecliptic path and altazimuth grid visualization.