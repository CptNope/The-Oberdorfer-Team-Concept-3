# SEO audit: oberdorferteam.com (prototype build)

**Date:** October 22, 2026
**Audited:** the built prototype in `site/`, 48 pages, which is identical to https://cptnope.github.io/The-Oberdorfer-Team-Concept-3/site/. The production domain is not live yet, so this audit is about **launch readiness**: what the WordPress build must carry over and what it must fix.
**Business type:** Local service (real estate team). Signals: a phone number on every page, a Worcester office, a statewide service area and `RealEstateAgent` schema.

**Method and limits**
- The crawl ran from disk, covering every `index.html` under `site/`. A script checked titles, descriptions, H1s, canonicals, robots, JSON-LD parsing, internal links, image attributes and word counts.
- Performance is **lab data only**: headless Chromium, with mobile throttled to 4× CPU, 150 ms latency and 1.6 Mbps. There is no field data (CrUX) and no INP, because the site has no real traffic.
- Not measured, because neither Search Console nor HarborRank is connected and the domain isn't live: rankings, search volumes, backlinks, indexing status and Google Business Profile.

## SEO Health Score: 79 / 100

| Category | Weight | Score | Basis |
|---|---|---|---|
| Technical SEO | 22% | 80 | Clean canonicals, sitemap and links. Every page is `noindex`, which is intentional on the prototype but must not reach production. There is no `robots.txt`. |
| Content quality | 23% | 75 | Sourced town guides of 990 to 1,600 words. Agent bios are still placeholders. The office and team pages are thin. |
| On-page SEO | 20% | 72 | 36 of 48 titles are over 60 characters. The town descriptions come from one template. Some guides have only 2 internal links pointing to them. |
| Schema | 10% | 80 | A consistent `@id` graph with no parse errors. The organization record has no `url`, `logo`, `image` or `sameAs`. |
| Performance | 10% | 90 | Mobile LCP 0.7 to 2.0 s, CLS 0. Six render-blocking stylesheets. |
| AI search readiness | 10% | 85 | Answer-first leads, sources on every fact and `llms.txt`. No AI crawler policy yet. |
| Images | 5% | 85 | Alt text everywhere. 3 photos lack width and height. |

## Executive summary

**What already works**
- No broken internal links and no orphan pages; each of the 48 pages has exactly one H1.
- No duplicate titles or meta descriptions.
- Every canonical points to the matching oberdorferteam.com URL, without the prototype's `/site/` prefix.
- The sitemap lists 41 URLs and leaves out the 6 demo listings and the agent template.
- The 27 town guides are researched and sourced. Each opens with an answer-first lead, carries a dated market table with its source, and follows the fair-housing rules.
- In 5-word sequences, 59% to 77% of each guide's text appears in no other guide. That clears the 60% bar for unique content on location pages, though four Tier 1 guides sit at or just under it.
- Fast and stable: mobile LCP under 2.1 s even with throttling, and zero layout shift on the four pages measured.

**Top 5 issues**
1. **Critical, at launch:** all 48 pages carry `<meta name="robots" content="noindex">`. That is correct for the prototype. If it carries over into the WordPress theme, nothing will be indexed.
2. **High:** the Worcester office has no street address. The office page has 128 words, the `RealEstateAgent` schema gives only the city, and no Google Business Profile exists. That blocks the 12-month goal of appearing in the map results for "real estate agent Worcester".
3. **High:** agent pages have placeholder biographies and no license numbers. These are the site's main trust signals for a YMYL purchase.
4. **Medium:** all 27 town meta descriptions share one template that promises "commuter rail", including for towns with no station, such as Great Barrington, Barnstable, Falmouth and Plymouth.
5. **Medium:** 7 town guides have only 2 internal links pointing to them (the hub and the market table). The "Nearby guides" lists mostly name towns that have no guide, so Tier 3 towns get few links.

**Top 5 quick wins**
1. Build each town's meta description from its own `answer` field, so it is specific and accurate.
2. Shorten town titles to about 60 characters, keeping the town first.
3. Add `url`, `logo`, `image` and `sameAs` to the `RealEstateAgent` node.
4. When a town has fewer than 3 nearby guides, fill its "Nearby guides" list with other guides from the same region.
5. Add `width` and `height` to the 3 photos that lack them.

## Technical SEO (80)

| Check | Result |
|---|---|
| Indexability | All 48 pages have `noindex` (prototype by design). **Critical for launch:** WordPress must output `index, follow` on public pages and keep `noindex` on demo or template content only. |
| Canonicals | 48 of 48 self-referencing on oberdorferteam.com. |
| Sitemap | 41 URLs, all with `lastmod` 2026-10-22; demo listings and the agent template are excluded. At launch, core `wp-sitemap.xml` plus `ot-core` takes over (see `docs/wordpress-architecture.md`). |
| robots.txt | None in the prototype (GitHub Pages serves the project subpath). Production needs one that points to the sitemap and states the AI crawler policy (see AI search readiness). |
| Internal links | 0 broken; 0 orphans. |
| Language | `lang="en"` on every page. |
| HTTPS, redirects, security headers | Not testable until the domain is live. Cloudways covers SSL; check HSTS and the redirect from `www` to the bare domain at launch. |

## Content quality (75)

| Area | Finding |
|---|---|
| Town guides | 27 guides; min 990, median 1,178, max 1,600 words of main content. Each fact carries a source, every guide has a dated "Researched by The Oberdorfer Team" line, and schools are linked to DESE only. **Strong.** |
| Uniqueness (location-page gate) | Share of 5-word sequences found in no other guide: lowest Reading, Shrewsbury and Westborough (59%), Stoneham (60%), Grafton (61%); highest Barnstable and Great Barrington (77%). Shared template headings and the contact form account for most of the overlap. The 27 guides are under the 30-page warning, but Boston neighborhood guides would cross it. |
| Agent profiles (E-E-A-T) | Biographies, titles, specialties and license numbers are placeholders, and the headshots are interim crops. For a YMYL decision such as buying a home, real experience and credentials matter most. Blocked on the team. |
| Thin pages | Office 128 words, team 160, journal 254, join 337. The office page will stay thin until the address, hours and directions are confirmed. |
| Demo listings | 6 pages at about 290 words. They are `noindex` and left out of the sitemap, correctly; the IDX vendor replaces them. |
| Testimonials and awards | None shown, which is correct: none are confirmed. Reviews should come from a Google Business Profile once it exists. |

## On-page SEO (72)

| Check | Finding |
|---|---|
| Titles | 36 of 48 are over 60 characters (max 79). Town titles run 66 to 76, for example "Great Barrington, MA Town Guide: Homes, Commute and Prices \| Oberdorfer Team". The town name comes first, so cutting from the end loses only the brand. Suggested pattern: "Living in {Town}, MA: Homes, Prices, Commute". |
| Meta descriptions | 13 pages fall outside 120 to 165 characters: the 6 demo listings, buy (181), market (172), sell (169), contact (166), journal (117), and the joint and template agent pages (112). Town descriptions all share one template, and "commuter rail" is inaccurate for towns without a station. |
| H1 | Exactly one per page; on town pages it is the search-facing "Living in {Town}, MA". |
| Internal linking | The hub, market table and footer link to every guide. Links between guides are thin: Great Barrington, Pittsfield, Lowell, Holden, Leominster, Plymouth and Barnstable each have 2 internal links pointing to them. The footer links 6 towns from every page. |

## Schema and structured data (80)

- **Present:** `RealEstateAgent` (`#team`) with a REWAP `parentOrganization`; `BreadcrumbList` with item URLs; `WebPage` + `City` (with `containedInPlace` and `sameAs` to the official site) on towns; `Dataset` on the market page; `ItemList` on the hub; `Person` + `ProfilePage` on agents; `Article` on the journal post. Zero JSON-LD parse errors.
- **Missing on `RealEstateAgent`:** `url`, `logo`, `image`, `sameAs` (Google Business Profile, social profiles once they exist), and `email` (unconfirmed). Add a street address and `openingHoursSpecification` once confirmed.
- **Info:** `FAQPage` markup exists on Brandon's and Kaitlyn's profiles and on Sell. Google retired FAQ rich results on May 7, 2026; leave it in place, but add none to new templates (already the rule).

## Performance (90, lab only)

| Page | Mobile LCP | Desktop LCP | CLS | Weight | Requests |
|---|---|---|---|---|---|
| Home | 2,028 ms | 376 ms | 0 | 437 KB | 16 |
| Worcester guide | 788 ms | 368 ms | 0 | 252 KB | 14 |
| Buy | 724 ms | 356 ms | 0 | 252 KB | 14 |
| Communities hub | 676 ms | 296 ms | 0 | 255 KB | 14 |

- Six render-blocking stylesheets: Google Fonts plus `tokens`, `base`, `components`, `docs` and `site`. The site pages load `docs.css`, which is for the documentation pages, and the WordPress theme should bundle and minify the rest.
- Self-hosting Cormorant Garamond and Manrope (with `font-display: swap` and preloads) removes a third-party round trip and the Google Fonts privacy question.
- Home LCP is the cover portrait, which already has `fetchpriority="high"`; in the theme, also preload it with `<link rel="preload">` so it starts before the CSS.
- INP can't be measured in the lab. Check it in CrUX or Search Console after launch.

## AI search readiness (85)

- **Strengths:** each page opens with an answer-first lead, every market figure gives its source, period and geography, and pages show dated "updated" lines. `llms.txt` lists the people and pages with production URLs, and entity links (`@id`, `sameAs` to official town sites) are already in the schema.
- **Gap:** there is no AI crawler policy. Production `robots.txt` should explicitly allow or deny GPTBot, ClaudeBot, PerplexityBot, Google-Extended and similar crawlers. That is a business decision; allowing them suits a site whose aim is to be cited.
- **Gap:** the brand barely appears on other sites yet. Answer engines rely on outside sources, so a Business Profile, REWAP's agent pages and local press about *Wakefield at Home* matter.

## Images (85)

- Every `<img>` has alt text (decorative ones use `alt=""`).
- Missing `width` and `height`: `lake-quannapowitt.webp`, `breakheart-reservation.webp` and `team-portrait.webp`, where they appear as cards or thumbnails on 15 pages. CLS measured 0, but explicit sizes guard against shifts on slow connections.
- WebP throughout. Community photography is still line drawings; real photos will help both engagement and image search.

## Local SEO (business type: local service)

| Signal | Status |
|---|---|
| Google Business Profile | **None.** It is the main input for appearing in the map results. Verify it at the Worcester office (see the decisions page). |
| Name, address, phone | The name and phone (406-250-7620) are consistent everywhere. **No street address** anywhere. |
| Local landing page | The office page exists but has 128 words; it needs the address, hours, a map, directions and parking once confirmed. |
| Reviews | None yet; that is correct, as none are confirmed. Start asking closed clients once the Business Profile exists. |
| Citations | Not checked, because the domain isn't live. At launch: REWAP's agent roster, Zillow and Realtor.com agent profiles, and the local Realtor association, all with identical name, address and phone. |
| Phone area code | 406 is a Montana code. It is confirmed and must stay consistent; a local 508 or 774 line is an open decision. |

## Re-check after the quick wins (same day, 6:15 p.m.)

The quick wins from the action plan (#7 to #13 and the launch safeguards) were applied in the prototype generator and the four Tier 1 guides were deepened and fact-checked. A re-crawl and re-test of the 48 pages:

| Category | Before | After | What changed |
|---|---|---|---|
| Technical SEO | 80 | 82 | Launch checklist and a draft `robots.txt` in `docs/wordpress-architecture.md`. The prototype is still `noindex`, by design. |
| Content quality | 75 | 78 | Reading, Shrewsbury, Westborough and Stoneham each gained a sourced section: their unique share rose from 59 to 60% to 75 to 77%. The lowest guide is now 71%. Agent bios and the office page are still waiting on the team. |
| On-page SEO | 72 | 88 | Every title is 62 characters or fewer. Every indexable description is 120 to 160 characters, and town descriptions come from each town's own answer. Each guide links to its region, and Barnstable is in the market table. |
| Schema | 80 | 85 | `RealEstateAgent` has `url`, `logo` and `image`. `address` and `sameAs` still wait on the office and the Business Profile. |
| Performance | 90 | 92 | Render-blocking stylesheets went from 6 to 5 (`docs.css` is gone from site pages), and the hero image is preloaded. Home mobile LCP is 1.9 s and CLS is 0. Fonts are still on Google Fonts, because the font packages were not reachable from the build workspace; the theme self-hosts them (launch checklist). |
| AI search readiness | 85 | 85 | The crawler policy is now a decision on the decisions page (recommended: allow). |
| Images | 85 | 95 | Every `<img>` has `width` and `height`. |

**SEO Health Score: 79 → 85.**

Still open: the six demo listings and the agent template keep short descriptions. They are `noindex` and the IDX vendor replaces the listings. Items #1 to #6 and #14 in the action plan wait on the WordPress build, the live domain, or details from the team.

## What this audit could not measure

Rankings, search volume, keyword difficulty, backlinks, indexing status, field Core Web Vitals and Business Profile data are all **unknown**. Connect HarborRank and, at launch, Search Console to fill them in.
