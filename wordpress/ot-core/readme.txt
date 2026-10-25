=== Oberdorfer Core ===
Contributors: oberdorferteam
Requires at least: 7.1
Tested up to: 7.1
Requires PHP: 8.3
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

The content model, search output and importer for oberdorferteam.com.

== Description ==

Oberdorfer Core holds everything on oberdorferteam.com that must survive a change of theme:

* Post types: community guides (`ot_community`, hierarchical: a neighborhood is a child of its city), agents (`ot_agent`) and offices (`ot_office`), with the region taxonomy (`ot_region`).
* Registered post meta (shown in the REST API, readable by Block Bindings). Each guide keeps its full researched record, with a source for every fact.
* Server-rendered blocks in the "The Oberdorfer Team" category: breadcrumbs, guide and profile sections, the community index and cards, the all-towns market table, town median stats, agent cards, the inquiry form and the listings placeholder.
* Title tags, meta descriptions, canonical URLs on https://oberdorferteam.com/, Open Graph tags, the JSON-LD graph, sitemap adjustments and /llms.txt.
* An importer for the researched content (Tools > Import Oberdorfer content, or `wp ot import`).

It is built to pair with the Oberdorfer block theme, which supplies the design system's CSS, icons and illustrations. The theme's templates place these blocks.

What it does not do yet (v0.1.0): forms show the prototype's demo behavior and send nothing (the `ot-leads` plugin will store and route inquiries); listings wait for the IDX provider; guide fields are edited as data, not in a field editor (the `ot-blocks` plugin will add one).

== Installation ==

1. Upload `ot-core.zip` under Plugins > Add New > Upload Plugin, and activate it.
2. Install and activate the Oberdorfer theme.
3. Go to Tools > Import Oberdorfer content and click Import now. It creates the guides, agents, office and pages, sets the front page and the Journal page, and sets permalinks to "Post name".
4. On a staging site, keep Settings > Reading > "Discourage search engines" checked. Canonical URLs already point at oberdorferteam.com.

To run it as a must-use plugin (it then cannot be deactivated from the admin): copy the `ot-core` folder to `wp-content/mu-plugins/ot-core/` and copy `mu-plugin/ot-core-loader.php` to `wp-content/mu-plugins/`. Must-use plugins are not updated automatically; replace the folder when a new version ships.

If an SEO plugin is installed, only one of the two may print meta tags: add `define( 'OT_CORE_SEO_OUTPUT', false );` to wp-config.php to switch this plugin's output off.

== Changelog ==

= 0.1.0 =
* First pre-release for staging: post types, meta, server-rendered blocks, search output, llms.txt and the importer for 33 town guides, 28 neighborhood guides, three agent profiles, the Worcester office and the site's pages.
