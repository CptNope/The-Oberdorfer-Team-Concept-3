# Action plan: oberdorferteam.com (from the October 22, 2026 audit)

Each item lists what it rests on, what it depends on, how we would know it failed, and a leading indicator to watch.

## Phase 1: Critical (before launch)

| # | Action | Why (first principle) | Depends on / unblocks | Failure check | Leading indicator |
|---|---|---|---|---|---|
| 1 | Make WordPress output `index, follow` on public pages. Keep `noindex` on demo, template and admin pages, and turn off "Discourage search engines" in Cloudways staging only. | Pages marked noindex never enter Google's index. | Unblocks every other SEO outcome. | Search Console's Pages report shows "Excluded by noindex" for public URLs. | Indexed page count in Search Console in the first two weeks |
| 2 | Ship `robots.txt` with the sitemap URL and an explicit AI crawler policy. | Crawlers need the sitemap location, and answer engines respect stated policy. | Needs the team's call on AI crawlers. | `robots.txt` returns 404, or it blocks `/wp-content/` assets. | Search Console's robots.txt report shows it fetched |
| 3 | Verify oberdorferteam.com in Search Console as a Domain property and submit the sitemap. | No search data exists without it. | Needs DNS access. Unblocks the HarborRank Search Console connection. | The sitemap shows "Couldn't fetch". | Discovered URLs equal 41 or more |

## Phase 2: High impact (weeks 1 to 3)

| # | Action | Why | Depends on | Failure check | Leading indicator |
|---|---|---|---|---|---|
| 4 | Confirm the Worcester office street address and hours. Expand the office page with the address, hours, map, directions and parking, and add them to `RealEstateAgent`. | Map results depend on a verified address and a matching landing page. | Decision: office address (decisions page). | The address differs between the page, the schema and the Business Profile. | Business Profile verification completes |
| 5 | Create and verify a Google Business Profile (category Real Estate Agency) owned by the team email. | It is the strongest input for "real estate agent near me" results. | #4, and the team email decision. | The profile is suspended or unverified. | Profile views and calls in its Performance report |
| 6 | Replace the placeholder agent bios. Add license numbers, titles and individual headshots. | For a home purchase, experience and credentials are the trust signals that count. | The team supplies them. | Brackets remain on the live profiles. | Engagement time on the agent pages (GA4) |
| 7 | Build each town's meta description from its `answer` field (about 155 characters, accurate transit). | Specific, accurate snippets earn clicks, and the current template overstates rail service. | None (`build_site.py` and the `ot-core` SEO fields). | Any description still says "commuter rail" for a town without a station. | Town-page CTR in Search Console |
| 8 | Add `url`, `logo`, `image` and `sameAs` to `RealEstateAgent`. | They let search engines and AI systems tie the brand together across sites. | Logo exists; `sameAs` grows as profiles are created. | Rich Results Test shows warnings on the organization node. | Brand knowledge panel or Business Profile linking to the site |

## Phase 3: Content and authority (month 2)

| # | Action | Why | Depends on | Failure check | Leading indicator |
|---|---|---|---|---|---|
| 9 | Shorten town titles to about 60 characters: "Living in {Town}, MA: Homes, Prices, Commute". | Long titles get cut off in results. | None. | Search Console shows Google rewriting the titles. | Impressions per guide |
| 10 | Fill "Nearby guides" from the same region when a town has fewer than 3 neighbors with guides. Add a "More guides in this region" row. | Internal links spread authority and help discovery. Seven guides have only 2 internal links pointing to them. | None. | Any guide still has fewer than 4 internal links pointing to it. | Crawl stats; impressions on Tier 3 guides |
| 11 | Deepen the four least-unique Tier 1 guides (Reading, Shrewsbury, Westborough, Stoneham) with one more sourced section each, such as a street or village walk or a recent municipal project. | They sit at the 60% unique-content line, and Tier 1 is the priority. | Research per town. | Unique share stays under 60%. | Impressions for "living in {town} MA" |
| 12 | Self-host the fonts; bundle and minify the CSS in the theme; preload the home cover image. | Cuts render-blocking requests from 6 to 1 or 2. | WordPress theme build. | Mobile LCP over 2.5 s in field data. | Core Web Vitals report in Search Console |
| 13 | Add `width` and `height` to the three photos that lack them. | Prevents layout shift. | None. | CLS over 0.1 in field data. | Core Web Vitals report |
| 14 | Build citations: REWAP roster, Zillow, Realtor.com, the local Realtor association, all with the same name, address and phone. | Consistent listings corroborate the Business Profile. | #4, #5. | The address differs on any listing. | Referring domains (HarborRank) |

## Phase 4: Monitor and iterate (ongoing)

- **Weekly for the first 8 weeks:** Search Console indexing, queries and pages. Then run HarborRank `keyword-research` to rank the town tiers by real demand.
- **Quarterly:** refresh the market figures when The Warren Group publishes; update `dateModified` only when the content changes.
- **Before any Boston neighborhood guides:** neighborhood-level sale data with two readings, and confirm what The Warren Group's "BOSTON" row covers. The site will pass 30 location pages at that point; keep each at 60% or more unique content.
