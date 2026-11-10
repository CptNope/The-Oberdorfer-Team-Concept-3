=== The Oberdorfer Team ===
Contributors: oberdorferteam
Requires at least: 7.1
Tested up to: 7.1
Requires PHP: 8.3
Stable tag: 0.8.1
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

= 0.8.1 =
* Header: the staging ribbon ("Staging preview: not yet public…") is removed, ahead of launch.

= 0.8.0 =
* New Privacy policy page (pattern privacy-policy, from data/privacy-policy.json): what the site collects, the one cookie set after a form is sent, who data is shared with, email and texts, retention, choices, security, children, Do Not Track and contact. The footer's Privacy link goes there.
* Contact page: the privacy note links to the policy; the fair-housing placeholder is removed; the team line is marked on Kaitlyn's number.
* Plain pages keep their text at reading width.

= 0.7.6 =
* Footer: removed "Information is deemed reliable but not guaranteed." It is not a legal requirement; hand-posted listings carry their own line, and the IDX feed brings the MLS's wording.

= 0.7.5 =
* Footer: the brokerage line now reads "operating under REWAP Brokerage LLC, a licensed Massachusetts real estate broker". The brokerage-license-number and [MLS name] placeholders are removed: Massachusetts requires the brokerage's name and broker status on every page, not its license number, and the MLS credit returns with the IDX feed.

= 0.7.4 =
* Version moves with ot-core 0.7.4. No theme changes.

= 0.7.3 =
* Our Team: the filter bar (specialty, service area, language) and the profile count are removed while the team has two agents.

= 0.7.2 =
* Version moves with ot-core 0.7.2. No theme changes.

= 0.7.1 =
* The team line is now Kaitlyn's 978-257-4938 (header, mobile menu, calls to action, footer, contact page, schema, lead messages). Brandon's 406-250-7620 stays as his direct line on his card and profile, and each agent card shows that agent's own number.

= 0.7.0 =
* The agent profile's Client stories section is now drawn by ot-core (testimonials and review links), and left out when there is nothing to show.

= 0.6.2 =
* Version moves with ot-core 0.6.2. No theme changes.

= 0.6.1 =
* Version moves with ot-core 0.6.1 (agent biography box in the editor). No theme changes.

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
* First staging release: templates, header and footer parts, and page-section patterns for
  the front page, Buy, Sell, Market, Team, Join, Contact, journal, articles, offices, search
  and 404, built from the static example site.
* Design-system CSS loaded in cascade order, with wp.css resetting WordPress block wrappers.
* Core buttons and button groups render as the design system's bare links on the front end;
  group class tokens add aria-labelledby, roles and data attributes the prototype uses.
* Active header link marked with aria-current="page".
