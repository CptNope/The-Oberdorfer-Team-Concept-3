=== Oberdorfer Core ===
Contributors: oberdorferteam
Requires at least: 7.1
Tested up to: 7.1
Requires PHP: 8.3
Stable tag: 0.7.0
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

= 0.7.0 =
* Testimonials (new menu): a client's words show on their agent's profile only once the client agreed to be quoted on this website and REWAP approved it, word for word, with a link to the original review. Each screen says what is still needed.
* "Reviews on other sites" (Agent details): links to an agent's reviews on RateMyAgent, Zillow or Google under "Client stories", without copying them. Brandon's 13 RateMyAgent reviews are linked.
* Brandon's 13 RateMyAgent reviews are added as hidden draft testimonials (title, site, link; no text) for the team to ask each client.
* The Client stories section is left out of a profile with neither, instead of showing an empty state. No review or rating markup is published.

= 0.6.2 =
* Agent profiles: the Specialties line is left out until specialties are entered in "Agent details" (it used to show a "to confirm" placeholder).

= 0.6.1 =
* Agent profiles: the biography has a visible "Click here and write …'s biography" box under "Meet {name}." (added once to profiles with no biography yet), so it is clear where to type.
* In the editor, the shared profile text shows the agent's first name instead of {first name}, as the site does.
* An empty intro-video section no longer shows a grey placeholder box on a profile in the editor.

= 0.6.0 =
* Forms are real when the new Oberdorfer Leads plugin is active: the inquiry form gains the email-updates consent box and spam protection through two filters (ot_inquiry_form_attr, ot_inquiry_form_extra); without the plugin it stays a demonstration.
* The phone field's format check works in current browsers (its pattern was invalid under the stricter rules Chrome now applies).
* Staging banner reworded: forms are no longer demonstrations.

= 0.5.0 =
* Agent listing pages at /agents/<agent>/listings/: the homes each agent is listing or co-listing, recently sold homes and the towns they serve. Their own title, description, canonical, breadcrumbs and CollectionPage JSON-LD; listed in the sitemap and llms.txt once they have listings, noindex until then. Profiles show a strip of current listings.
* Listings: video tour (YouTube, Vimeo) and 3D tour (Matterport, iGuide, Kuula, CloudPano) that load only when a visitor presses play; links to the listing's Facebook, Instagram and TikTok posts; floor plan and brochure; request-a-showing and watch-the-tour buttons; open house with add-to-calendar (.ics and Google Calendar), hidden once it is over; share (phone share sheet, copy link, Facebook, email, text); a payment estimate; a phone action bar. Co-listing agent field; the Offer names the listing agent in JSON-LD.
* Agent details: introduction video and towns served.

= 0.4.4 =
* Community cards: Wakefield's card shows a house drawing (the triple-decker) like every other town instead of the lake photo, so the grid reads as one set. The photo stays at the top of the Wakefield guide.

= 0.4.3 =
* The theme's stylesheets and scripts now carry the theme version, so browsers and server caches pick up each update. They were tagged 0.1.0 since the first release, so staging kept serving old CSS (the 0.4.2 drawing fix did not show).

= 0.4.2 =
* House drawings: on community cards the drawing is centered above the town name instead of pinned to the top-left corner (the photo rule was stretching it), and on listing pages it fits its frame instead of being cut off at the bottom.

= 0.4.1 =
* Editor only: the home cover photo and the design-system buttons (header phone, link arrows) now look in the editor as they do on the site.

= 0.4.0 =
* Every main page is edited in the page itself. Home, Buy, Sell, Market, Team, Join and Contact now hold their sections in the page body: open the page under Pages, click any heading, paragraph, button or photo, type, Save. The importer copies the theme's sections into each page once and never over the team's edits; a page whose body is empty still shows its sections. Links and images in the bodies are root-relative, so they move between Local, staging and oberdorferteam.com.
* Text that was raw HTML is now ordinary blocks: the market figure tiles, the buying and selling steps, the Massachusetts seller checklist (a table block), the contact lines, the home cover photo (an image block that can be replaced) and the header's navigation links and phone button.
* The mobile menu is built from the header's navigation links, so one edit changes both; "Search homes" still follows the IDX switch.
* The standard agent profile sections (about heading, how we can help, client stories, contact) are blocks in the agent template, edited once in Appearance > Editor for both profiles; {first name} is replaced with each agent's first name. The questions and the message form stay built from each profile.
* Guide research sections stay protected, as before.

= 0.3.0 =
* Edit on the page. Guides, agent profiles, listings and the office open in the editor showing the whole page. The text that belongs to the post (a guide's h1, headline and summary; an agent's role line and summary) is an ordinary block bound to the post's own field with the Block Bindings API (core/post-meta), so the team clicks it and types. The biography and a listing's description are the post body. Sections drawn from research data stay previews and, when clicked, say where they are edited.
* A short notice at the top of each editor explains how the page works; a "Start here" panel on the dashboard links the common tasks. Comments are hidden and Posts are called Journal.
* The "Agent details" box saves only the fields changed in it, so it never overwrites text typed on the page.
* The importer remembers what it last wrote to each field and leaves anything edited on the site alone (guides as well as agents). The research record still refreshes.
* Section settings are hidden on guides, profiles and listings; in the Site Editor each section is labelled.

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
