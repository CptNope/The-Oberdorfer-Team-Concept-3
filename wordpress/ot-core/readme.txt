=== Oberdorfer Core ===
Contributors: oberdorferteam
Requires at least: 7.1
Tested up to: 7.1
Requires PHP: 8.3
Stable tag: 0.2.1
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

Listings are off by default. Settings > Listings (IDX) switches the home search and listings on, and holds the IDX provider's shortcodes for the Buy page and the home page.

If an SEO plugin is installed, only one of the two may print meta tags: add `define( 'OT_CORE_SEO_OUTPUT', false );` to wp-config.php to switch this plugin's output off.

== Changelog ==

= 0.2.1 =
* Editable agent profiles: an "Agent details" box on each agent (role, title, summary, direct phone, email, specialties, languages, service area, license number, recognition, digital card, headshot, photo caption). The biography is the main editor. Saved values override the imported data, emptied fields stay empty, and re-running the importer never overwrites them. The joint profile's introduction is its main editor.

= 0.2.0 =
* Show or hide guides (Communities > Show or hide guides): a switch for every town and neighborhood guide. A hidden guide returns "not found", leaves the sitemap and llms.txt, and drops out of the index, cards, the market table and links from other guides; nothing is deleted. Hiding a city hides its neighborhoods. Logged-in editors can still preview it.
* Listings: the team's own listings, entered by hand (Listings > Add listing) with status, price, address, guide, beds, baths, area, lot, year, open house, MLS number, brokerage, agent, main photo and more photos. They show on the home page (Featured homes), the Buy page (Our listings) and the matching town or neighborhood guide, each with its own page and a showing-request form, whether or not the IDX switch is on.

= 0.1.2 =
* Listings (IDX) switch, Settings > Listings (IDX): turns off the home search bar, Featured homes, the header search icon, the mobile menu's "Search homes" label and the Buy page search without removing them, with fields for the provider's shortcodes so listings can go live by pasting them and ticking one box. Off by default.

= 0.1.1 =
* Contact details from the coming-soon page: Kaitlyn's and Brandon's direct phones and emails, their "MA Licensed Realtor" title and recognition from their digital cards, the team Instagram, and contact cards (vCards) to save to a phone.

= 0.1.0 =
* First pre-release for staging: post types, meta, server-rendered blocks, search output, llms.txt and the importer for 33 town guides, 28 neighborhood guides, three agent profiles, the Worcester office and the site's pages.
