# IDX and listing data: options still to decide

**Status:** open decision, 2026-10-22. **MLS:** MLS Property Information Network (MLS PIN).

**Sources:**
- *Real Estate Technology Launch Options* (JAT, October 17, 2026, https://cptnope.github.io/The-Oberdorfer-Team-2/), which compares 18 platforms.
- MLS PIN Data Services and Showcase IDX pricing, both checked on 2026-10-22.

Prices are planning figures from published vendor pages, not quotes. Confirm every number in writing for the team's MLS PIN account before signing.

## What is already decided

These decisions in `docs/wordpress-architecture.md` and `docs/leads-crm-nurture-spec.md` narrow the IDX choice:

1. **The website is a custom WordPress block theme on Cloudways.** Hosted all-in-one platforms (website plus IDX plus CRM) would replace it. They are benchmarks, not candidates.
2. **The CRM and nurturing are built in (`ot-leads`).** An IDX product mainly needs to supply search and listing pages, and to pass lead events (registrations, saved searches, listing inquiries) into `ot-leads`. Paying for a vendor's nurture engine duplicates that work.
3. **Search visibility comes from the town guides and agent pages, with listings on top.** Listing pages that live on oberdorferteam.com and can be indexed are a plus, not a requirement for launch.
4. **Listing data stays in its licensed layer.** `ot-leads` stores only references (MLS number, address, the URL viewed), never a copy of MLS data. MLS PIN's display and attribution rules apply wherever listings appear.

## The options

| Option | Monthly (published) | Listing pages on our domain and indexable | Lead events into `ot-leads` | Fits our build | Main caution |
|---|---|---|---|---|---|
| **A. MLS PIN free IDX** (IDX Links to Pinergy search, or Manual IDX Download) | $0 | No: links out to MLS PIN's search pages | No | Stopgap only | Visitors leave the site; weakest experience and no lead capture on the search itself |
| **B. IDX Broker Core** | $87 ($80 for 2 agents + $7 MLS PIN fee); $35 connection, $100 setup may be waived | Confirm: ask how detail pages are served and whether they are crawlable on our domain | Confirm the API and webhooks | Good: lowest cost, keeps our theme | Not an unrestricted MLS feed; the CRM and nurture are ours to build (already planned) |
| **C. IDX Broker Engage** | $126 ($119 + $7) | As B | As B | Good | Adds market reports and natural-language search; still an IDX service |
| **D. Showcase IDX Essentials** | $94.95, 2 seats | Yes: Showcase says listings "live on your domain" with "100% indexable pages" | Confirm the webhook or CRM connection | Very good: a WordPress plugin, styled by our theme | MLS pass-through fees may apply; confirm MLS PIN at checkout |
| **E. Showcase IDX Premium** | $124.95, 5 seats | Yes, plus indexable XML sitemaps | Advanced routing and more CRM connections | Very good as the team grows | Same MLS confirmation |
| **F. Realtyna MLS On The Fly** | $449 for MLS PIN (Tier 5) | Yes, rendered by our own templates through its API | Ours to build | Best fit for full design control without running our own feed | Cost; the API does not grant resale or sublicensing |
| **G. Direct MLS PIN feed** (RESO Web API, custom engine in a new `ot-idx` plugin) | $100 brokerage access, or $525 vendor access | Yes, fully ours | Native | Highest control | Needs MLS PIN approval of a Broker Data Access Agreement; we carry compliance, uptime and security; reselling to other brokerages needs the vendor agreement |
| **RealScout Build** | $299, 2 licenses, 12-month term | Not a website replacement | Its own nurture engine | Duplicates `ot-leads` nurture | Only if mature buyer collaboration is needed on day one |
| **Follow Up Boss Grow** | $138 (2 × $69) | No IDX | Already planned as an `ot-leads` connector | CRM only | Optional, not an IDX choice |

**Benchmarks, not candidates** (they replace the custom WordPress site):
- iHomefinder (quote)
- AgentFire ($165 plus $800 setup; its theme cannot move hosts)
- Real Geeks ($409 plus $500 setup)
- Placester, Luxury Presence, Lofty, Sierra Interactive, BoldTrail, CINC and Ylopo (all by quote)

## Recommended path, for the team to decide

1. **Launch: Showcase IDX Essentials (D)**, or IDX Broker Core (B) if its listing pages prove crawlable on our domain.
   - D is preferred because it is a WordPress plugin. Our theme styles its output with `ot/listing-card`, and its listing pages are indexable on oberdorferteam.com.
   - It costs about $8 a month more than B.
2. **Grow: Showcase IDX Premium (E)** when the team passes two agents.
3. **Own it later, if the platform is meant to become a product: Realtyna (F), or the direct MLS PIN feed (G).**
   - Only after MLS PIN approves the intended use in writing, and only once the town guides and `ot-leads` are proven.
   - Build an `ot-idx` plugin that keeps the listing data separate from the CRM.
4. **Avoid at launch:**
   - RealScout, because it duplicates `ot-leads`.
   - All-in-one platforms, because they replace the site.

**Interim option:** if launch comes before an IDX contract is signed, use MLS PIN's free IDX Links (A) and keep the site's search bar pointing at it. Switch as soon as the vendor is live.

## How the IDX plugs into our layers

| Concern | Where |
|---|---|
| Vendor plugin or script | Third-party plugin (B to F), or our own `ot-idx` plugin (G) |
| Look and feel of search, results and listing pages | Theme: `ot/listing-card`, the search bar and the listing template styled to the design system |
| Lead events (registration, saved search, favorite, showing request) | Vendor webhook or API into `ot-leads`, lead type `showing` or `buyer` with the MLS number as a reference |
| Listing references in nurture emails | `ot-leads` AI grounding pack: IDX listings only, with MLS attribution (spec §9) |
| Town guide "Homes for sale" sections | Vendor widget or API filtered by town, with attribution and update time |
| MLS compliance (attribution, data freshness, disclaimers) | The IDX layer plus the listing template; reviewed by the broker |

## Questions to put to every vendor before signing

From the JAT comparison, adapted to this build:

1. **The exact two-agent total:** both seats, hosting, IDX, setup, MLS fees, contact limits. What is it at 5, 10 and 25 agents?
2. **MLS PIN:** is REWAP Brokerage approved, and how long does onboarding take?
3. **Listing SEO:** do property detail pages live on oberdorferteam.com, and is their HTML crawlable? Is there a sitemap?
4. **Webhooks and API:** can registrations, saved searches and inquiries be sent to our WordPress `ot-leads` in real time? What is restricted?
5. **Exports:** can contacts, saved searches, consent and activity be exported if we switch?
6. **Theming:** can our theme fully style search and listing pages, or only through the vendor's settings?
7. **Alerts:** are saved-search email alerts included, and can they be sent from our domain?
8. **Exit:** who owns the URLs, content, data and integrations at cancellation?

## Decisions needed

| Decision | Owner | Blocks |
|---|---|---|
| Which IDX at launch (D or B, or A as interim) | Brandon and Kaitlyn, with REWAP | Buy page, listing template, town guide listings |
| MLS PIN approval for REWAP and the chosen vendor | REWAP broker of record | Any live listings |
| Whether the long-term goal includes a licensable product (path F or G) | Team | Whether to plan `ot-idx` |
| Vendor answers to the questions above, in writing | Team | Signing |
