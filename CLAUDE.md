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

## Ground rules

1. **Never invent facts.** This covers listings, testimonials, awards, production statistics, agent profiles, license numbers, office addresses, brokerage approvals, commissions and recruiting benefits. Anything unconfirmed is shown as a `[bracketed placeholder]` or carries a badge: `.badge--demo`, `--verify`, `--proposed` or `--source`.
2. **Confirmed by the client on 2026-10-15:** all data in the publication is correct. That includes the phone **406-250-7620**, **"Top Producer"**, and the Wakefield market figures (Redfin, accessed Oct 10, 2026).
3. **Still unconfirmed:**
   - whether the portraits are real photographs or AI-generated (the publication was made with ChatGPT)
   - team email and domain
   - MA license numbers
   - office address and status
   - REWAP disclosures
   - privacy policy
   - IDX/MLS provider and CRM
   - final community list
4. **Market data:** always show the source, period and geography. Never present the Wakefield figures as live or statewide.
5. **The team and the brokerage are separate brands.** Never redraw the REWAP logo. Show it only on its own ivory plate, or use the typographic "Powered by" lockup.
6. **Accessibility:** WCAG 2.2 AA.
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
- **Logos are generated.** Edit `tools/build_logos.py`, then run `python3 tools/build_logos.py`. It outlines the Cormorant Garamond glyphs from `tools/fonts/`.
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

## Commits

Use short imperative subjects and explain the *why* in the body. Don't commit `.impeccable/review/`, which holds local screenshots.
