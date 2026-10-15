# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Stack

Prototype: static HTML/CSS/JS (user choice, 2026-10-15). CSS custom-property tokens and components named and structured as future Gutenberg blocks/patterns so they port directly into a custom WordPress theme.

Production (stated in brief, not yet built): custom WordPress theme, native Gutenberg, reusable blocks and patterns, ACF or Carbon Fields where useful, PHP server rendering, modular JS. Custom post types planned: Agents, Communities, Offices, Testimonials, Publications (optional). Journal uses standard posts. Listings come only from an authorized IDX/MLS integration (provider not selected). CRM not selected; lead routing must stay configurable.

## Users

- **Home buyers across Massachusetts:** first-time, move-up, luxury and relocating households. They need to understand a market, find a home, and trust a person to guide them.
- **Home sellers:** they want pricing confidence, strong presentation and a clear plan that fits their timing.
- **Investors:** a secondary buyer audience, served where relevant.
- **Prospective agents:** licensed agents weighing whether to join a growing team.
- **Readers of the team's local editorial content:** "At Home" publications and the journal. Often the first touchpoint before any transaction.
- **Team administrators:** non-developers who add agents, communities, offices and articles.

## Product Purpose

The brand identity, brand book, design system and website for **The Oberdorfer Team**, a husband-and-wife real estate team led by Brandon and Kaitlyn Oberdorfer. The site turns real estate interest into personal client relationships (buyer, seller, showing and agent-specific inquiries). It also recruits agents and carries an ongoing local editorial publication.

Success means:
- The site reads as the same brand as the *Wakefield at Home — Fall 2026* publication.
- It serves Massachusetts statewide, not just Wakefield.
- It shows Brandon and Kaitlyn prominently.
- Agents, offices and communities can be added without development work.

## Positioning

A personal, two-partner team that offers the presentation and preparation of a premium firm at every transaction size. The brand promise is **"Luxury at every price point."** In this promise, luxury means the standard of service and presentation, not property value.

Two things set the team apart:
- **Couple-led personal service:** "Husband. Wife. Your Team."
- **Magazine-quality local editorial publishing** as a relationship-building channel.

## Operating Context

- **Brokerage:** The team operates under **REWAP Brokerage LLC** (Real Estate Wealth Acquisition and Preservation Brokerage, rewapbrokerage.com).
  - The team and the brokerage are separate brands with separate websites.
  - Never imply the team is an independently licensed brokerage.
  - Materials carry the attribution "Powered by" REWAP, as in the source publication.
- **Service area:** statewide Massachusetts, including Worcester and Greater Boston, with the Wakefield/North Shore area as established editorial ground (confirmed 2026-10-15). The specific town list is not finalized.
- **Planned team office:** expected to be a team office under the principal brokerage. Its formal status, address and advertising requirements are unconfirmed.
- **Growth:** the team plans to recruit additional agents.
- **Seasonal print publications:** 8.5×11 in, saddle-stitched, 8 pages, community-specific. *Wakefield at Home* is the first, and the website journal extends it.
- **Before production:** Massachusetts advertising rules, brokerage approval and MLS attribution requirements must be confirmed.

## Capabilities and Constraints

**Prototype must demonstrate:**
- Responsive navigation and property search UI
- Agent directory and agent page template (`/agents/agent-name/`)
- Community landing page
- Journal
- Recruiting page
- Office page
- Contact page with buyer, seller, general and recruiting forms, including realistic form states

**Demo content rules:**
- The prototype must not depend on a live MLS API or real client data.
- Demo listings and data must be clearly labeled as demos.
- An invented listing must never be presented as a real property.

**Open decisions (record, do not invent):**
- IDX provider and integration method; whether the team may use the brokerage's MLS permissions
- CRM selection
- Office address, status and timing
- Final community list
- Umbrella editorial title ("Massachusetts at Home" is a creative option, not approved)
- Data source for market statistics

**Terminology:** The Oberdorfer Team; Brandon & Kaitlyn Oberdorfer; REWAP Brokerage; *[Town] at Home* (publication); Journal / At Home (editorial section).

## Brand Commitments

**Source-confirmed copy** from the publication. Preserve these lines:
- "Luxury at every price point." (primary promise)
- "Husband. Wife. Your Team."
- "Thoughtful service. Creative marketing. A personal connection to every move."
- "For the home you have. For the life you are building."
- Service principles: **Personal** ("Two partners invested in your goals."), **Purposeful** ("A plan shaped around your home and life."), **Prepared** ("Clear guidance and attention to the details.")
- "A husband-and-wife team. A shared commitment."
- "The people behind the promise." / "A personal standard of service."

**Values:** care, honesty, hard work, showing up for the people who count on them, personal service, thoughtful guidance, attention to detail, creative marketing.

**Voice:** warm, plain-spoken, helpful and quietly confident. It favors practical guidance ("A useful snapshot helps you ask better questions") over hype. It cites sources and dates for facts. Avoid exaggerated luxury language.

**Binding visual constraint (user-stated):** evolve the *Wakefield at Home* visual language; do not replace it.
- Palette: deep navy, warm ivory, restrained antique gold
- Type: large editorial serif headlines
- Layout: magazine-like, with restrained decoration and high-quality photography
- PDF-embedded fonts (verified): Cormorant Garamond Light / Light Italic and Manrope ExtraLight
- Visual-world details belong in DESIGN.md, not here.

**REWAP logo:** must not be redesigned without approval.

## Evidence on Hand

- `source/Wakefield_At_Home_8_Page_Print_Ready.pdf`: the 8-page Fall 2026 publication.
  - It contains a team photo of Brandon & Kaitlyn and Wakefield editorial content.
  - Photos: Lake Quannapowitt and Breakheart images are Wikimedia CC BY-SA 4.0 and need attribution. The p.6 food image is AI-generated and must not be presented as photography of real people or places.
  - Market figures: Wakefield only, Redfin, accessed 2026-10-10. Median sale price $786,979 (3 months ending Aug 2026); 21 median days on market (Aug 2026); $483/sq ft (rolling 3-month); 103.3% sale-to-list (Aug 2026). These are historical, Wakefield only, and not live.
- `source/creative-brief.md`: the full creative and technical brief.
- **Confirmed 2026-10-15:** the user confirmed that all data in the publication is correct, including the phone number 406-250-7620, "Top Producer" and the market figures. Brandon produced the publication with ChatGPT.

**Absent, so use labeled placeholders only:**
- Additional team photography
- Team email and MA license numbers or titles
- Client testimonials
- Years of experience and production statistics
- Agent profiles beyond Brandon and Kaitlyn
- Office address
- Recruiting terms, commissions, benefits and training details
- Awards

None of these may be fabricated. "Top Producer" is confirmed and may be used as it appears in the publication.

## Product Principles

1. **Personal before polished.** Every surface leads back to Brandon, Kaitlyn and the people they serve. Sophistication supports warmth and never replaces it.
2. **Luxury is a service standard, not a price bracket.** A first-time buyer and a luxury seller get the same presentation and care.
3. **Useful, sourced, dated.** Market and community content teaches and cites its sources. No hype, no undated statistics, no mass-generated location pages.
4. **Built to grow.** Agents, offices, communities and publications are reusable models an administrator manages without a developer.
5. **Truthful by construction.** Unverified claims, listings, testimonials and credentials appear only as clearly labeled placeholders. The team brand stays distinct from REWAP Brokerage.

## Accessibility & Inclusion

- Target WCAG 2.2 AA.
- Full keyboard navigation and accessible form labels and feedback.
- Honor reduced-motion preferences.
- Check contrast on every palette pairing, especially gold and slate on ivory and navy.
- Equal Housing Opportunity notice is required, as in the source.
