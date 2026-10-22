# SEO workspace: The Oberdorfer Team

The working folder for SEO on **oberdorferteam.com**. Notes, exports, keyword lists, competitor research, briefs and reports live here, so each SEO workflow starts from this context, not from a blank page. This folder is not published to the site.

Set up 2026-10-22 with the HarborRank `seo-project-setup` workflow, in **web-evidence-only** mode: HarborRank was not yet connected, so nothing here contains search volumes, difficulty, rankings or backlink counts.

## Status

| Step | Status | Notes | Next action |
|---|---|---|---|
| 1. Working folder | Done | `seo/`, with `gsc/`, `keywords/`, `competitors/`, `content/`, `outreach/`, `reports/` | None |
| 2. Website scope | Done | oberdorferteam.com (production, WordPress). Prototype at cptnope.github.io/The-Oberdorfer-Team-Concept-3/site/. Massachusetts, US, English. New site, not yet indexed. | Check what oberdorferteam.com serves today (it blocked our fetch with robots.txt) |
| 3. Goals | Drafted | See Goals below | Team to confirm the targets |
| 4. Positioning | Known | See Positioning below | None |
| 5. HarborRank project | **Not connected** | One project on the trial plan: oberdorferteam.com | Connect HarborRank (Customize → Connectors), then create the project |
| 6. Search Console | **Not connected** | Needs the live domain verified | At launch, verify oberdorferteam.com as a Domain property and connect it in HarborRank. Fallback: CSV exports into `gsc/` |
| 7. Existing assets | Done | See Assets below | None |
| 8. First workflow | Recommended | `keyword-research`, then `competitive-landscape` | Needs HarborRank |

## Sites in scope

- **Production:** https://oberdorferteam.com/. Canonical, sitemap and schema URLs in the prototype already point here; WordPress drops the prototype's `/site/` prefix.
- **Prototype:** https://cptnope.github.io/The-Oberdorfer-Team-Concept-3/site/ (every page is `noindex`).
- **Brokerage:** https://rewapbrokerage.com/ (a separate brand; link to it, never compete with it).

## Goals

1. More qualified buyer and seller leads across Massachusetts, starting around the new Worcester office.
2. Within 6 months of launch: all 27 town guides indexed, earning impressions for "living in / moving to (town) MA".
3. Within 12 months: in the Google Maps local pack for "real estate agent Worcester" and "realtor Worcester MA".
4. Leads attributed by source: the form lead types `community:<town>`, `agent:<slug>`, `buyer`, `seller`.

## Positioning

- A husband-and-wife team, Brandon and Kaitlyn Oberdorfer, under REWAP Brokerage LLC.
- Promise: "Luxury at every price point." Luxury means the standard of service and presentation, not price.
- Proof points: "Top Producer" (*Wakefield at Home*, Fall 2026); their own print publication, *Wakefield at Home*.
- Opening a new office in Worcester; serving all of Massachusetts.
- Avoid: invented production statistics, reviews or awards; anything that describes who lives in a town (fair housing). See `CLAUDE.md`.

## Competitors

The team considers **every brokerage and team in the Worcester area** a competitor, since it is opening a new office there. No list yet; build it with `competitive-landscape` once HarborRank is connected.

These are web-search observations only, not rankings (October 22, 2026):

- **Agent searches** ("Worcester MA real estate agent"): directories and lead sites appeared, such as Ramsey Solutions, HomeLight, BiggerPockets, RE/MAX agent pages and RealTrends.
- **Relocation searches** ("moving to (town) MA"): moving-company pages and Livability appeared, with few genuine local guides.
- **Statewide teams** appearing in rankings: RealTrends lists of large Massachusetts teams.

## Assets

- 16 sourced town guides: data in `tools/data/towns/`, pages under `site/communities/`.
- A statewide market page with a 2025 town-by-town table (The Warren Group).
- Massachusetts buying and selling explainers on the Buy and Sell pages.
- Agent profile pages for Brandon and Kaitlyn, plus a template for future agents.
- `site/sitemap.xml` and `site/llms.txt`.
- *Wakefield at Home*, Fall 2026: a linkable print asset.
- The strategy doc: *Oberdorfer Team: Massachusetts SEO Strategy*, in Claude Docs. It has the keyword and page map, tiers and next steps.
- None yet: Search Console data, keyword lists with volumes, rank tracking, backlink data.

## Preferences for SEO work on this project

- Every figure has a source and date. Mark estimates as estimates; never invent volumes or rankings.
- No mass-generated location pages: each town guide is researched and sourced (stay under 30).
- Keep visible FAQs, but no new FAQPage markup (Google retired FAQ rich results on May 7, 2026).
- Save outputs here: keyword lists to `keywords/`, competitor notes to `competitors/`, briefs to `content/`, outreach to `outreach/`, reports to `reports/`, Search Console exports to `gsc/`.

## Next workflow

**`keyword-research`**, to replace estimates with real search data.

- **Seeds:** the 27 town names × "homes for sale", "real estate agent", "realtor", "living in", "moving to".
- **Plus:** "real estate agent Worcester MA", "first time home buyer Massachusetts", "offer to purchase MA", "Title 5 inspection", "smoke detector certificate MA".
- **Then:** `competitive-landscape` for the Worcester market, to name the competitors this README leaves open.
