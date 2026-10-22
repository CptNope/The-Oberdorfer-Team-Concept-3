# CLAUDE.md

These are instructions for Claude (and any other agent or developer) working in this repository.

## What this is

This repository holds the brand identity, brand book, design system and example website for **The Oberdorfer Team**. The team is a husband-and-wife Massachusetts real estate team, led by Brandon and Kaitlyn Oberdorfer, operating under **REWAP Brokerage LLC**.

The visual language evolves the team's print publication, *Wakefield at Home*, Fall 2026 (`source/`). It must be evolved, never replaced. The site is a static prototype; production will be a custom WordPress theme.

## Read these first

| File | Holds |
|---|---|
| `PRODUCT.md` | Product truth: users, positioning, brand commitments, evidence on hand, what must not be invented |
| `DESIGN.md` | The visual system: tokens, type, layout, components, motion, imagery |
| `source/creative-brief.md` | The full client brief, with 19 phases and 10 deliverables |
| `.impeccable/surfaces/index-html.md` | The direction contract for the documentation pages |
| `brand-book/` § 20 Governance | Approvals, the "never publish" list and open items |
| `docs/wordpress-architecture.md` | What goes in the theme, the `ot-core` must-use plugin, `ot-blocks`, `ot-leads` and third-party code |
| `docs/leads-crm-nurture-spec.md` | The built-in CRM, CRM connectors and AI nurture |
| `docs/idx-options.md` | IDX and MLS PIN options still to decide, with costs, fit and vendor questions |

## Ground rules

1. **Never invent facts.** This covers listings, testimonials, awards, production statistics, agent profiles, license numbers, office addresses, brokerage approvals, commissions and recruiting benefits. Anything unconfirmed is shown as a `[bracketed placeholder]` or carries a badge: `.badge--demo`, `--verify`, `--proposed` or `--source`.
2. **Confirmed by the client on 2026-10-15:** all data in the publication is correct. That includes the phone **406-250-7620**, **"Top Producer"**, and the Wakefield market figures (Redfin, accessed Oct 10, 2026). **Confirmed 2026-10-22:** the team is opening its office in **Worcester**, serves all of Massachusetts, and its production domain is **oberdorferteam.com** (canonical, sitemap and schema URLs use it, without the prototype's `/site/` prefix).
3. **Still unconfirmed:**
   - whether the portraits are real photographs or AI-generated (the publication was made with ChatGPT)
   - team email (the domain, oberdorferteam.com, is confirmed)
   - MA license numbers
   - office street address, hours and status (the city, Worcester, is confirmed)
   - REWAP disclosures
   - privacy policy
   - IDX/MLS provider (options in `docs/idx-options.md`); outside CRM (optional; the built-in `ot-leads` is planned)
   - final community list (the 16 guides in `tools/data/communities.json` are a proposed list)
   - Brandon's and Kaitlyn's individual biographies, titles, direct lines, emails, specialties, languages and headshots (their profiles use placeholders and interim crops of the team portrait)
4. **Market data:** always show the source, period and geography. Never present the Wakefield figures as live or statewide. Town figures are The Warren Group's calendar-year 2025 single-family table; the statewide figures are its January 20, 2026 release.
5. **Community guides follow fair housing rules.** Describe places, housing, transit, history and amenities. Never describe who lives somewhere, never call an area "safe", "exclusive" or "family-friendly", and never rate schools: link to the district's DESE profile instead. Every fact carries a source in the town's record.
6. **The team and the brokerage are separate brands.** Never redraw the REWAP logo. Show it only on its own ivory plate, or use the typographic "Powered by" lockup.
7. **Accessibility:** WCAG 2.2 AA.
   - Gold `#B49A65` is **not** a text color on ivory. Use `--ot-gold-ink` there.
   - Respect `prefers-reduced-motion`.
   - Keep focus rings visible.

## Working on the code

- **Edit tokens, not values.** All color, type and space comes from `assets/css/tokens.css`. Components read semantic tokens. Use `.surface-inverse` for navy sections and `.surface-muted` for sandstone ones.
- **Know which CSS file to change:**
  - `base.css`: type and primitives
  - `components.css`: components, each one planned as an `ot/*` Gutenberg block
  - `docs.css`: the documentation layout
  - `mockups.css`: brand-book mockups
  - `site.css`: example-site page layouts
- **The example site is generated.** Edit `tools/build_site.py`, never `site/**/index.html` by hand. Then run `python3 tools/build_site.py`.
- **Community guides are data.** Each town is a researched record in `tools/data/towns/<slug>.json` (every fact with its source URL). Edit the record, then run `python3 tools/data/assemble_communities.py` and rebuild the site. Region order and tiers live in that script.
- **SEO conventions:** every page passes an `h1=` to `page_hero` (the search-facing heading; the brand line stays as the display text), a title ending in "The Oberdorfer Team", and a meta description of about 150 to 160 characters. Google retired FAQ rich results on May 7, 2026: keep visible FAQs, but don't add new FAQPage markup.
- **Layer rule for the WordPress build:** if losing it on a theme change would lose data or break saved content, it does not go in the theme. Content model, fields and SEO go in `ot-core`; custom blocks in `ot-blocks`; forms, leads and nurture in `ot-leads`. AI-written lead messages follow the same no-invented-facts and fair-housing rules as the site.
- **Custom fields in the WordPress build:** no ACF or other fields plugin. Register each agent, community and office field as post meta (`register_post_meta`, `show_in_rest`), and bind it to core blocks in templates and patterns with the Block Bindings API (WordPress 6.5+). Target PHP 8.4 on Cloudways.
- **Logos are generated.** Edit `tools/build_logos.py`, then run `python3 tools/build_logos.py`. It outlines the Cormorant Garamond glyphs from `tools/fonts/`.
- **House illustrations are generated.** Edit `tools/build_illustrations.py`, then run `python3 tools/build_illustrations.py`. It writes the colonial, triple-decker, Cape and townscape symbols to `assets/img/illustrations.svg` with three stroke weights (1.2 outlines, 0.8 trim, 0.55 detail).
- **Token exports are generated.** After you change `tokens.css`, run `python3 tools/export_tokens.py`. It rewrites `assets/tokens/tokens.json` and `assets/tokens/theme.json`.
- **New components** go in `components.css` and must be documented in `design-system/index.html`. Show every state, and add a row to the WordPress mapping and the component inventory.
- **To preview,** run `python3 -m http.server 8765` and open <http://localhost:8765/>.
- **To take screenshots,** run `python3 tools/screenshot.py http://localhost:8765/site/ out.png 1440 --full`. Check desktop at 1440 and mobile at 390, and confirm that `scrollWidth` equals `clientWidth`.
- **Keep links relative.** The site is served from a GitHub Pages project subpath, so root-relative `/` links break.
- **Deploys run on every push to `main`** through `.github/workflows/pages.yml`. Only `index.html`, `assets/`, `discovery/`, `brand-book/`, `design-system/` and `site/` are published.
  - Never link pages to `source/`, `tools/`, `docs/` or Markdown files; they are not on the live site.
  - Downloadable PDFs belong in `assets/publications/`, as web-compressed copies.
  - If you add a new top-level folder that pages need, add it to the workflow's `cp` line.
- **Design workflow:** the Impeccable design skill was used. `PRODUCT.md`, `DESIGN.md` and `.impeccable/` are its project files.

## Claude artifacts

Two Claude artifacts are built from this repo. Their source lives in `claude/` (see `claude/README.md`); it is not published to Pages.

- **Design system** ([artifact](https://claude.ai/artifact/YE21oQ5cfN4i2RqN51Wwj4)), in `claude/design-system/project/`. Rebuild it with `tools/claude/build_ds.py`, `check_ds.py` and `finalize_ds.py` after changing `tokens.css`, `components.css` or `design-system/index.html`, then republish only the changed files.
- **FSE template boards** ([canvas](https://claude.ai/artifact/Lzv2vEyR3YjDWmhdJZxZs6)), in `claude/fse-templates/project/`. There is one board per block-theme template, annotated with its patterns and blocks. When a site page changes, update its board too; the phone boards follow the desktop ones automatically.
- Keep the artifacts and the repo in step: a change made on an artifact's page should be read back into `claude/`, and a change here should be republished.
- The same ground rules apply on the boards: placeholders and badges for anything unconfirmed, and sources on every market figure.

## Commits

Use short imperative subjects and explain the *why* in the body. Don't commit `.impeccable/review/`, which holds local screenshots.
