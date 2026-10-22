# CommunityGuide

One page per town at `/communities/town/`, built from a researched record in `tools/data/towns/`. Block `ot/community-guide`.

- A search-facing h1 ("Living in Worcester, MA") as the hero kicker, the brand-voice headline as display text, and a 40 to 60 word answer as the lead.
- Overview beside `.quick-facts--aside`: county, region, 2020 Census population, rail, highways, the school district's DESE link and the 2025 single-family median.
- Neighborhoods, commute and schools, places and history, a `.market-table` with its source, period and geography, an FAQ, a town form (`community:<slug>`) and a `.source-list` naming every source.
- Fair housing: describe places, housing and transit; never who lives there; never rate schools.
- JSON-LD: `WebPage` about a `City` contained in its county and the state, plus `BreadcrumbList`. No FAQPage markup (Google retired FAQ rich results in May 2026).
