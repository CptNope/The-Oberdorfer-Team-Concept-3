# Design

<!-- Visual system for The Oberdorfer Team. Product truth lives in PRODUCT.md. -->

## World

The visual world continues the team's print publication, *Wakefield at Home*, Fall 2026. It is an editorial luxury magazine on a New England architectural grid.

Direction: **Concept A, Editorial Heritage, on Concept B's architectural grid**. The user chose it on 2026-10-15.

- **Light scene:** ivory paper read by daylight. Navy is reserved for "cover" moments: heroes, statistics, closings, navigation and the footer.
- **Restraint:** gold appears only as a line, a frame or a small label on navy.

## Tokens

The source of truth is `assets/css/tokens.css`. Every component reads the semantic tokens. `.surface-inverse` remaps them for navy grounds, and `.surface-muted` remaps them for sandstone.

| Role | Token | Value | Notes |
|---|---|---|---|
| Primary | `--ot-navy` | `#071D2D` | Sampled from p1, p4, p7, p8 |
| Ground | `--ot-ivory` | `#F7F3EC` | Sampled from p2–p6 |
| Body text | `--ot-ink` | `#142A38` | 13.4:1 on ivory |
| Accent line | `--ot-gold` | `#B49A65` | Text on navy only (6.3:1); 2.45:1 on ivory, so lines only there |
| Accent text | `--ot-gold-ink` | `#7A6438` | Gold-toned text on ivory (5.1:1) |
| Surface | `--ot-sandstone` | `#E9E2D5` | Bands and callouts |
| Secondary | `--ot-slate` | `#59656A` | Captions (5.4:1) |
| On-navy secondary | `--ot-mist` | `#A3AEB3` | 7.6:1 |
| Status | `--success` / `--warning` / `--error` | `#2F5D45` / `#7A4E0E` / `#9A2B22` | Each ≥ 6:1 on its tint |

## Typography

The source fonts were verified from the PDF's embedded font data.

- **Display:** Cormorant Garamond.
  - Headlines use weight 300 from 30px up.
  - Smaller serif text uses 400–500.
  - Italic is used for the answering line of a headline and for captions.
- **Text:** Manrope.
  - Body 400, navigation 500, labels and buttons 600.
  - Labels and buttons are uppercase with 0.14–0.18em tracking.
- **Scale:** fluid `clamp()` steps.
  - Display XL is 52→96px; body is 16px at 1.65 line height on a 68ch measure.
  - Statistics use tabular lining numerals.
- **Headline construction:** two short sentences on two lines, the second answering the first (class `.answer`).

## Layout

- **Grid:** 12 columns.
  - Gutter 16→32px, page margin 16→80px, maximum content width 1312px.
  - Editorial splits are 6 + 5 with an offset.
  - Sections are 64→136px tall, with more space above a heading than below.
- **Corners:** square everywhere (`--radius: 0`). There are no shadows except on paper mockups.
- **Signatures:**
  - **Folio frame** (`.folio-frame`): an inset 1px gold double frame. Use one per view.
  - **Running head and folio bar:** page furniture taken from the publication.
  - **Dimension rule** (`.dim-rule`): taken from the p7 house linework.
  - **Line illustrations:** colonial, triple-decker, Cape and townscape.

## Components

The components live in `assets/css/components.css`, and each one maps to a planned `ot/*` Gutenberg block:

- Buttons and links
- Site header: navy, overlay and light variants, plus the mobile menu
- Cover hero
- Property search bar
- Cards: listing, agent, community and article
- Agent profile (`ot/agent-profile`): one page per person at `/agents/agent-name/`, with placeholder values, a pending headshot, a publish checklist and a phone action bar
- Market statistics panel
- Forms, including validation states
- Editorial furniture: triad, steps, pull quote, testimonial, FAQ and breadcrumbs
- Lead capture: CTA panel, newsletter, office card and map plate
- Footer with brokerage attribution

The REWAP logo is never redrawn. It always sits on its own ivory plate.

## Admin screens (`ot-leads`): the Leads workspace

Redesigned 2026-11-10 as a branded workspace inside wp-admin. The team chose the **Day log** layout; the direction contract is in `.impeccable/surfaces/wordpress-ot-leads-includes-admin-php.md`. All styles are in `wordpress/ot-leads/assets/admin.css`, scoped to `body.ot-ws`, so the rest of wp-admin is untouched. WordPress's forms, nonces and list table stay underneath.

- **Shell** (`ot_leads_shell()` in `admin.php`):
  - A 216px navy rail lists Today, All leads, Pipeline, Approvals and Reports, then Sequences, Newsletter and Settings. Gold count badges show who is waiting and how many emails need approval. The active item has a 1px gold inset rule.
  - The page is ivory. WordPress's own menu folds to icons on these screens.
  - At 960px and below, the rail becomes a top strip that scrolls sideways. It fades at the right edge, and the current screen scrolls into view.
- **Today is home**, and the Leads menu opens on it.
  - **Headline:** one Cormorant sentence, with its answer in gold-ink italic ("Three people are waiting. *Start with Dana.*").
  - **Under it:** the date and the agent chips (Everyone, Team, Brandon, Kaitlyn) on one line.
  - **Three groups:** waiting for a first reply, follow-ups due, and back on the site. Each has an uppercase hairline heading with its count.
  - **Each row:** the time, the person's name in Cormorant and one meta line. Then Call, Text and Email, with the person's preferred way filled navy. Then **Log**, a `<details>` disclosure that opens with the top-down clip wipe and holds the one-tap outcomes.
  - **Late and overdue rows** get a pale red tint (`--ws-err-tint`) plus a red outlined tag. They never get a stripe.
  - **Setup warnings** sit below the groups.
  - **Side column:** This week, Reply speed and emails to approve.
- **Type:**
  - Cormorant Garamond 400/500/italic for screen titles, people's names and panel titles.
  - Manrope 400–700 for all UI text: 14px body, 12.5px buttons, 11px tracked uppercase group labels.
  - Both faces are self-hosted in `assets/fonts` (OFL), so staff screens make no third-party font requests.
  - Numerals are tabular.
- **Components:**
  - **Panels:** paper (`#FFFDF9`) panels with a sandstone hairline, square corners.
  - **Buttons:** paper buttons, or navy for primary actions; 34px tall on desktop, 40px on phones.
  - **Chips:** outlined; the current one is filled navy.
  - **Status tags:** outlined text — navy for new, red for late or failed, gold-ink for warm, green for done or consented. Never colour alone.
  - **Tables:** sandstone table heads.
  - **Timeline:** square gold markers on a hairline.
  - **Shadows:** none.
- **Lead screen:**
  - **Header:** the name in Cormorant, with tags for stage, intent, type, agent and waiting time, and Call/Text/Email at the right.
  - **Wide column (first on phones):** Log a touch, any possible duplicate, then the Timeline with its note form.
  - **Side column:** Status ("Why it was lost" appears only when the stage is Lost), Contact, AI summary, Tasks, Email nurture and Their data.
- **Other screens:**
  - All leads keeps the WordPress list table, restyled. Its saved views are chips; on phones the stage and age sit in the name cell.
  - Pipeline columns are sandstone and snap one column at a time on phones.
  - Approvals shows the rendered email, with the HTML behind "Edit the email (HTML)".
  - Follow-up emails (the sequences) are written for non-technical editors. Each series has an On/Off switch and a "Wait for my OK / Send it on its own" choice. Its emails form a timeline of rows ("Right away", "2 days later"); each row opens to edit the timing, choose the kind of email from three explained cards, and write the message in plain text with [First name]-style placeholder buttons, beside a live preview for a sample person.
  - Reports figures use Cormorant numerals.
- **AI-drafted content** is always labelled "AI draft", with its grounding sources one click away.

## Agent profiles, search and conversion

Brandon and Kaitlyn each have a profile (`site/agents/brandon-oberdorfer/`, `site/agents/kaitlyn-oberdorfer/`); the joint page `site/agents/brandon-kaitlyn-oberdorfer/` tells their story together and links to both. `site/agents/agent-template/` is the profile every future agent receives, unpublished until its checklist is complete. All three come from one data model (`AGENTS` in `tools/build_site.py`).

- **SEO:** one person per page, named in the title, `h1` and meta description; breadcrumbs; a canonical URL; internal links from the team page, the joint page, the footer and the partner card.
- **AEO:** an answer-first summary sentence, a quick-facts list, an FAQ in plain language, and a visible "profile updated" date.
- **GEO:** consistent entity facts across pages; JSON-LD `ProfilePage` > `Person`, linked by `@id` to the team (`RealEstateAgent`) and the brokerage, with `spouse` between Brandon and Kaitlyn; `FAQPage`; `site/llms.txt` summarising the team, its people and its pages; `site/sitemap.xml`.
- **Conversion:** message and call above the fold, a fixed action bar on phones, services linking into Buy and Sell, and a form whose lead type names the agent (`agent:<slug>`).
- **Integrity:** unconfirmed fields (title, license, direct line, email, specialties, languages, biography) render as `[placeholders]`. The individual photos are interim crops of the team portrait. No review or rating markup until real, permitted reviews exist.

## Motion

There is one gesture: gold rules draw from left to right with the exponential ease-out `cubic-bezier(0.16, 1, 0.3, 1)`, and content settles 14px beneath them.

- The button underline draws on hover.
- Menus and contents panels open with a top-down clip wipe.
- The page has no parallax and no looping animation.
- `prefers-reduced-motion` removes all movement.

## Imagery

- Use natural-light architecture and lifestyle photography, credited and licensed.
- Overlays are navy scrims only.
- Image ratios are 16:9, 4:5, 4:3, 3:4, 3:2 and 9:16. Set a `--focal` point for each image so mobile crops keep the subject.
- Don't present AI-generated images as real people, homes or places.
- Use line illustrations as labeled placeholders until photography exists.

## Content integrity

Use badge classes `.badge--source`, `--proposed`, `--verify` and `--demo` to mark the status of content.

- Demo listings and agents are always labeled.
- Market data always carries its source, period and geography.

## Example website

The example website lives in `site/` and is generated by `tools/build_site.py`. Its shared partials (header, footer, cards and forms) mirror the future WordPress template parts. Page layouts are in `assets/css/site.css`, and behaviour that runs on demo data is in `assets/js/site-demo.js`.

## Completeness (v1.1, 2026-10-22)

`design-system/index.html` documents every component class used on the example site. It has 20 chapters:

- **Foundations:** color, type, space and lines, layout and breakpoints, icons.
- **Components:** buttons, navigation, page headers, search and results, cards, listing detail, market statistics, forms, feedback and overlays, editorial content, lead capture, footer.
- **Behaviour and implementation:** motion and accessibility, token exports, WordPress mapping with a component inventory.

New in v1.1:

- **Feedback components:** alerts (info, success, warning, error, all dismissible), a toast, a native `<dialog>`, pagination, a skeleton loading state and an agent mini card.
- **Breakpoint tokens:** `--bp-sm` 36rem, `--bp-md` 52rem, `--bp-lg` 64rem, `--bp-xl` 90rem.
- **Toast layer:** `--z-toast` 95.
- **Token exports:** `assets/tokens/tokens.json` (W3C Design Tokens) and `assets/tokens/theme.json` (WordPress v3), generated by `tools/export_tokens.py`.
