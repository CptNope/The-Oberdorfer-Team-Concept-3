# WordPress build: theme `oberdorfer` and plugin `ot-core`

**Status:** v0.1.0, pre-release for staging (started 2026-10-24). Targets WordPress 7.1 and PHP 8.3 or later (Cloudways runs 8.4).

This is the contract between the block theme and the `ot-core` plugin. Read `docs/wordpress-architecture.md` first for the layer rule; this file pins the names both sides use.

## What ships in v0.1.0

| Package | Source | Built zip | Install |
|---|---|---|---|
| Block theme `oberdorfer` | `wordpress/oberdorfer/` | `wordpress/dist/oberdorfer.zip` | Appearance > Themes > Add New > Upload |
| Plugin `ot-core` | `wordpress/ot-core/` | `wordpress/dist/ot-core.zip` | Plugins > Add New > Upload. Can also run as a must-use plugin (see its readme). |

`python3 tools/build_wp.py` builds both: it writes the theme's `theme.json` from `assets/tokens/theme.json`, copies the shared CSS, JS, fonts and images into the theme, snapshots the researched content into `ot-core/data/`, and zips both packages.

Out of scope for v0.1.0: `ot-blocks` (editable structured blocks), `ot-leads` (forms store nothing yet; forms show the prototype's demo behavior), IDX (the Buy page and listing sections show a placeholder), journal posts.

## URLs

| URL | What | Source |
|---|---|---|
| `/` | Front page (static page "Home") | theme `templates/front-page.html` |
| `/buy/`, `/sell/`, `/market/`, `/team/`, `/join/`, `/contact/` | Pages | theme `templates/page-<slug>.html` |
| `/journal/` | Posts page | theme `templates/home.html` |
| `/communities/` | Community archive | theme `templates/archive-ot_community.html` |
| `/communities/<town>/` and `/communities/<city>/<neighborhood>/` | Community guides (hierarchical CPT) | theme `templates/single-ot_community.html` |
| `/agents/<slug>/` | Agent profiles | theme `templates/single-ot_agent.html` |
| `/office/<slug>/` | Offices | theme `templates/single-ot_office.html` |

## Content model (ot-core)

**Post types**

| Post type | Rewrite slug | Hierarchical | Archive |
|---|---|---|---|
| `ot_community` | `communities` | yes (neighborhood = child of its city) | `/communities/` |
| `ot_agent` | `agents` | no | none (the Team page lists agents) |
| `ot_office` | `office` | no | none |

**Taxonomy** `ot_region` on `ot_community` (terms: `central`, `metrowest`, `north`, `boston`, `southshore`, `cape`, `southcoast`, `west`, `berkshires`; term meta `ot_order` and `ot_blurb`).

**Post meta** (all `show_in_rest`, single, no leading underscore, so Block Bindings `core/post-meta` can read them)

| Key | Post types | Type | Holds |
|---|---|---|---|
| `ot_record` | community | string (JSON) | The full researched record from `tools/data/towns/` or `tools/data/neighborhoods/`; the guide blocks render from it |
| `ot_headline` | community, agent | string | The display line; lines separated by a newline |
| `ot_answer` | community, agent | string | The answer-first summary |
| `ot_kicker` | community, agent | string | The search-facing h1, e.g. "Living in Worcester, MA" |
| `ot_seo_title`, `ot_seo_description` | all | string | Title tag and meta description |
| `ot_county`, `ot_population` | community | string, integer | Quick facts used by bindings and cards |
| `ot_role`, `ot_job_title`, `ot_phone`, `ot_email`, `ot_instagram`, `ot_card_url`, `ot_photo` | agent | string | Profile facts (confirmed values only; empty renders a placeholder) |
| `ot_street`, `ot_city`, `ot_postcode` | office | string | Address |

**Blocks** (registered by ot-core, server-rendered with PHP, no build step; each has a small editor script that previews with `ServerSideRender`)

| Block | Attributes | Renders |
|---|---|---|
| `ot/breadcrumbs` | none | Breadcrumb trail for the current page, plus BreadcrumbList JSON-LD |
| `ot/page-hero` | none | The hero of the current community or agent (kicker h1, headline, answer) |
| `ot/community-section` | `section`: `about`, `neighborhoods`, `commute`, `places`, `market`, `questions`, `contact`, `sources` | One section of a community guide, exactly as the prototype renders it (town or neighborhood logic chosen from the post) |
| `ot/community-index` | none | The archive body: region tabs, region card grids, neighborhood index |
| `ot/community-cards` | `slugs` (array) | Community cards for the given slugs (front page) |
| `ot/market-table` | none | The all-towns comparison table on the Market page |
| `ot/town-stats` | `slugs` (array) | Stat tiles for town medians (front page) |
| `ot/agent-profile` | `section`: `hero`, `about`, `help`, `stories`, `questions`, `contact` | One section of an agent profile |
| `ot/agent-cards` | none | Agent cards plus the open seat card (Team page) |
| `ot/inquiry-form` | `context`, `title`, `topics` (array), `submit` | The prototype's inquiry form; demo behavior until `ot-leads` |
| `ot/listings-placeholder` | `heading` | Where IDX listings will appear: the provider's shortcode for that placement when listings are on and one is set, the placeholder when none is set, nothing when listings are off |

ot-core also outputs: title tags and meta descriptions, canonical URLs on `https://oberdorferteam.com/`, Open Graph tags, the JSON-LD graph (RealEstateAgent, Person/ProfilePage, City/Place WebPage), removes the users sitemap, and serves `/llms.txt`.

**Importer:** Tools > Import Oberdorfer content, or `wp ot import`. It creates or updates (by slug) every community, both agents and the Worcester office, the region terms, and the pages listed above, sets the static front page and the posts page, and sets pretty permalinks. Running it again updates in place.

## Editing on the page (v0.3.0)

`ot_community`, `ot_agent`, `ot_listing` and `ot_office` support the editor with `default-mode` `template-locked`, so each opens showing its whole template (`includes/editing.php`). The post's own text in the templates is core blocks bound with Block Bindings (`core/post-meta`):

| Template | Bound block | Meta key |
|---|---|---|
| `single-ot_community` | h1 heading, headline paragraph, summary paragraph | `ot_kicker`, `ot_headline`, `ot_answer` |
| `single-ot_agent` | role label, summary paragraph | `ot_role`, `ot_summary` |

The title is `core/post-title`; the biography and a listing's description are `core/post-content`. WordPress disables template blocks in this mode, so `assets/editor.js` sets every block with a `core/post-meta` binding, and every `ot/*` block, to the `contentOnly` editing mode: bound text can be typed into, and an `ot/*` section shows where it is edited when selected. Their settings panels show only in the Site Editor. The agent "Agent details" box posts the value each field had on load and skips unchanged fields, so it does not overwrite page edits saved in the same request. New templates must bind with `core/post-meta` keys registered with `show_in_rest`.

The importer stores `_ot_import_<key>` (md5 of what it wrote) and updates a field only while it still matches; `ot_record` always refreshes.

## Editable agent profiles

Each agent's edit screen has an "Agent details" box (`includes/agent-fields.php`): role, title, summary, direct phone, email, specialties, languages, service area, license number, recognition (`Award | Source` per line), digital card, headshot and caption. The biography is the post body. `ot_agent_rec()` merges these over the imported record; an emptied field is marked `<key>_cleared` so the import does not show through; the importer seeds agent meta once and never overwrites it. Empty fields render the [bracketed placeholder] with a "To confirm" badge. The joint profile's introduction is its post body.

## Show or hide guides

Communities > Show or hide guides has a checkbox for every town and neighborhood (meta `ot_hidden`, never touched by the importer). A hidden guide returns 404 to visitors (logged-in editors can still preview it), leaves the sitemap and `/llms.txt`, and drops out of everything built from the guide list: the index and region counts, cards, the market table, town stats, nearby links and the city's neighborhood list. List items linking to it (the footer's town list) are removed. Hiding a city hides its neighborhoods.

## The team's own listings

Post type `ot_listing` (Listings in the admin, `/listings/<slug>/`): title = address line, body = property story, featured image = main photo, and a "Listing details" box (status: coming soon, for sale, under agreement, sold; price; street, unit, town guide, ZIP; home type, style, beds, baths, area, lot, year; open house; MLS number; listing brokerage, default REWAP Brokerage LLC; listing agent; featured; more photos). Blocks: `ot/team-listings` (`limit`, `town` = guide path; a city matches its neighborhoods) and `ot/listing` (`section`: `hero`, `details`, `more`), plus the guide section `homes`. Theme sections tagged `ot-team-listings` show only when there are listings; `ot-homes` (Featured homes) shows when there are listings or IDX is on. Sold listings stay at their URL but leave the grids. Listings are independent of the IDX switch.

## Listings (IDX) switch

Until the IDX provider is chosen, Settings > Listings (IDX) in ot-core keeps everything that searches or shows homes off the site without removing it. Off by default. While off, the server drops:

- blocks whose class list includes `ot-idx` (the theme's Featured homes section and the Buy page's search section);
- elements marked `data-ot-idx` inside core/html blocks (the header search icon, the home search bar, the mobile menu's "Search homes" label);
- the `ot/listings-placeholder` block.

The same screen holds the provider's shortcodes (Buy page search; home page featured homes). Going live: install the provider's plugin, paste its shortcodes, tick "Show listings", save, purge Breeze and Cloudflare. `define( 'OT_IDX_ENABLED', true );` (or false) in wp-config.php overrides the checkbox. New listing or search UI in the theme must carry `ot-idx` or `data-ot-idx` so the switch covers it.

## Theme

- `theme.json` is generated; never edit it by hand.
- Styles: the design system's own CSS (`tokens.css`, `base.css`, `components.css`, `site.css`) plus `wp.css` for block wrapper resets. The theme does not restyle core blocks with new values; it applies the design-system classes.
- Parts: `header.html`, `footer.html` (each a single pattern reference, so PHP can resolve asset and page URLs).
- Patterns: one per page section, named on the FSE canvas boards (`oberdorfer/<name>`).
- JavaScript: `site.js` (menu, accordions and filters) and `site-demo.js` (demo form behavior, until `ot-leads`).

## Releases

Each release is a GitHub release tagged `vX.Y.Z` with `oberdorfer.zip` and `ot-core.zip` attached. Bump the version in `wordpress/oberdorfer/style.css`, `wordpress/ot-core/ot-core.php` (header and `OT_CORE_VERSION`), both `readme.txt` files and `VERSION` in `tools/build_wp.py`; add notes in `wordpress/release-notes/v<version>.md`; update `wordpress/release.json`; push to `main`. The workflow `.github/workflows/wp-release.yml` builds both zips, tags `v<version>` and publishes the release. Pre-releases are for staging only.

### v0.1.0 (pre-release, 2026-10-25)

- Block theme: 17 templates, header and footer parts, 49 patterns named after the FSE canvas boards, `theme.json` from the design tokens, the design-system CSS and JS. Every block passes WordPress 7.1's block validator.
- `ot-core`: post types, meta, eleven server-rendered blocks, titles, descriptions, canonicals on oberdorferteam.com, JSON-LD, sitemap adjustments, `/llms.txt` and the importer (33 town guides, 28 neighborhood guides, three agent profiles, the Worcester office and eight pages).
- Tested on WordPress 7.1.3 (PHP 8.3, SQLite) installed from the zips: every page type returns 200 (404 for missing pages) with no PHP notices, and the guides match the prototype side by side at 1440 and fit a 390-pixel phone without horizontal scroll.
- Known gaps: forms are demonstrations until `ot-leads`; listings wait for IDX; journal posts are not imported; guide fields are edited as data until `ot-blocks`; fonts load from Google Fonts (self-host before launch); the prototype's buyer, seller and recruiting form fields (timing, preapproval, licensing) are reduced to the standard inquiry form.
