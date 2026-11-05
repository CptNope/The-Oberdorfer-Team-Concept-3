=== The Oberdorfer Team ===
Contributors: oberdorferteam
Requires at least: 7.1
Tested up to: 7.1
Requires PHP: 8.3
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

The block theme for The Oberdorfer Team, a husband-and-wife Massachusetts real estate team operating under REWAP Brokerage LLC.

== Description ==

A full-site-editing block theme that reproduces the team's static example site with the team's
design system: the same tokens, type, components and markup, so the design-system CSS renders
every page as the prototype does.

* Presentation only. Content, custom fields, SEO (titles, meta descriptions, canonical URLs,
  Open Graph, JSON-LD) and the data-driven blocks come from the ot-core plugin. A redesign
  replaces this theme without touching content.
* Requires the ot-core plugin for the community guide templates (archive-ot_community,
  single-ot_community), the agent profile template (single-ot_agent), the office template
  (single-ot_office) and every data section: breadcrumbs, community cards, town stats, the
  market table, agent cards, inquiry forms and the listings placeholder. Without ot-core those
  blocks render nothing.
* Templates: front page, Buy, Sell, Market, Team, Join, Contact, the journal (home), journal
  articles (single), generic pages, search, 404, and the ot-core post types.
* Patterns: one per page section, in the "The Oberdorfer Team" category. The header and footer
  parts are single pattern references, so asset and page URLs resolve on any domain.
* Forms are demonstrations until ot-leads ships: they validate and show a demo confirmation,
  and nothing is sent or stored. Listings show a placeholder until the IDX provider is chosen.
* Unconfirmed facts stay as [bracketed placeholders] or badges, as on the prototype.

Building: theme.json and the shared assets (CSS, JS, icons, illustrations, photos, logos) are
generated and copied by `python3 tools/build_wp.py` in the project repository; do not edit
theme.json by hand. Theme settings to merge live in theme-overrides.json.

Fonts: Cormorant Garamond and Manrope (SIL Open Font License 1.1), loaded from Google Fonts in
this version; self-hosting is a launch task.

== Changelog ==

= 0.2.0 =
* Show or hide guides (Communities > Show or hide guides): a switch for every town and neighborhood guide. A hidden guide returns "not found", leaves the sitemap and llms.txt, and drops out of the index, cards, the market table and links from other guides; nothing is deleted. Hiding a city hides its neighborhoods. Logged-in editors can still preview it.
* Listings: the team's own listings, entered by hand (Listings > Add listing) with status, price, address, guide, beds, baths, area, lot, year, open house, MLS number, brokerage, agent, main photo and more photos. They show on the home page (Featured homes), the Buy page (Our listings) and the matching town or neighborhood guide, each with its own page and a showing-request form, whether or not the IDX switch is on.

= 0.1.2 =
* Listings (IDX) switch, Settings > Listings (IDX): turns off the home search bar, Featured homes, the header search icon, the mobile menu's "Search homes" label and the Buy page search without removing them, with fields for the provider's shortcodes so listings can go live by pasting them and ticking one box. Off by default.

= 0.1.1 =
* Contact details from the coming-soon page: Kaitlyn's and Brandon's direct phones and emails, their "MA Licensed Realtor" title and recognition from their digital cards, the team Instagram, and contact cards (vCards) to save to a phone.

= 0.1.0 =
* First staging release: templates, header and footer parts, and page-section patterns for
  the front page, Buy, Sell, Market, Team, Join, Contact, journal, articles, offices, search
  and 404, built from the static example site.
* Design-system CSS loaded in cascade order, with wp.css resetting WordPress block wrappers.
* Core buttons and button groups render as the design system's bare links on the front end;
  group class tokens add aria-labelledby, roles and data attributes the prototype uses.
* Active header link marked with aria-current="page".
