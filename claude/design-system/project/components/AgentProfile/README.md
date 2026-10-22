# AgentProfile

One page per person at `/agents/agent-name/`, built for search, answer engines and conversion. Block `ot/agent-profile`.

- Fields: name, headshot (4:5), role, title, verified MA license, biography (120 to 250 words, first person), specialties, service areas, languages, direct phone, email, socials, partner. Anything unconfirmed renders as a `[placeholder]` with a Verify badge; never write copy for an agent.
- Above the fold: an answer-first summary sentence, then two actions (message, call) and a quick-facts list. A visible "profile updated" date.
- On phones a fixed `.action-bar` keeps Call and Message on screen.
- Below: biography, services linking into Buy and Sell, client stories (empty state until permitted reviews exist), listings the agent may advertise, recent writing, an FAQ, and a form whose lead type names the agent (`agent:<slug>`).
- JSON-LD: `ProfilePage` with a `Person` linked by `@id` to the team and brokerage, plus `FAQPage` and `BreadcrumbList`. No review or rating markup until real reviews exist.
- Future agents start from the template page, which stays unpublished until its checklist (license, headshot, biography, approval) is complete.
