# Research brief for town and neighborhood guides

Every community guide on the demo site is built from a researched JSON record. This brief is the
contract for writing or deepening one. Read it in full before starting, and read one finished record
(`tools/data/towns/reading.json` is the model) to see the voice and depth expected.

## Ground rules (non-negotiable)

1. **Every fact has a source URL** in the record, from a page you actually opened in this session.
   Prefer official sources: the town or city website, mass.gov (including the DLS Municipal Databank,
   MassGIS and MHC/MACRIS), MBTA, MassDOT, DCR, DESE, the National Register / NPS, the U.S. Census
   Bureau, regional planning agencies (CMRPC, MAPC and others), the Boston Planning Department, and
   town housing production plans and master plans. Wikipedia is acceptable for history and
   background, but cross-check numbers and dates with a primary source where one exists.
2. **Never invent.** If you cannot confirm something, leave it out and say so in `notes`. No
   estimates, no "about" figures that are not in a source, and no hours, prices or business details
   that could change unless dated and sourced.
3. **Fair housing.** The guides describe places, housing, transit, history and amenities only.
   - Never describe who lives somewhere: no demographics, race, religion, national origin, family
     status, age groups, "young professionals", "families", "retirees", "students live here",
     "diverse", "quiet neighbors" and so on. Naming an institution is fine ("home to Clark
     University"); saying who populates an area is not.
   - Never call an area "safe", "exclusive", "family-friendly", "up-and-coming", "desirable",
     "good" or "bad", and never characterize crime.
   - Never rate or rank schools. Name the district and link its DESE profile only.
   - Avoid steering language such as "perfect for", "ideal for" or "you'll fit in".
4. **Voice.** Plain, concrete, editorial. Specific street names, buildings, parks, dates and housing
   types beat adjectives. No marketing superlatives ("stunning", "vibrant", "hidden gem", "nestled").
   American English, sentence case, Oxford comma not required. No em dashes; use commas, colons or
   parentheses.
5. **Market data.** Town prices come only from The Warren Group, MA December 2025 Sales, Prices by
   Town (single-family and condo, calendar years 2024 and 2025):
   https://www.thewarrengroup.com/wp-content/uploads/2026/01/MA-December-2025-Sales_Town.pdf
   Record the exact row text you read in `market.reading_1` (and `reading_2` if you read it twice).
   Earlier fetches of this PDF truncated the condo table at Brookline; if you can read condo rows
   (try fetching again, or a page range), fill them, otherwise leave them `null` and say so.
6. **Property tax.** FY2026 residential tax rate per $1,000 from the Massachusetts DLS Municipal
   Databank ("Tax Rates by Class") or the municipality's assessor page, with the URL. Note any
   residential exemption (Boston, Cambridge, Somerville and some others have one). If FY2026 is not
   yet published, use FY2025 and say so in `tax.fy`.

## Town record schema (`tools/data/towns/<slug>.json`)

```json
{
  "slug": "auburn", "name": "Auburn", "county": "Worcester County",
  "region": "Central Massachusetts",
  "population_2020": 16889, "population_source": "<url>",
  "official_site": "<url>",
  "headline": ["Two short lines for the hero,", "concrete, no superlatives."],
  "answer": "2 to 3 sentences, about 60 to 80 words: what the town is, its housing and how it connects. Starts with '<Name> is ...'. This is the search snippet and the AI answer, so make it factual and self-contained.",
  "meta": "(optional) a 150 to 160 character meta description if the answer's first sentence is too long or too short",
  "overview": ["3 paragraphs, 90 to 150 words each: origins and growth; built form and landmarks; housing and planning today (zoning, MBTA Communities status, recent developments, with dates)."],
  "housing": {"text": "2 to 4 sentences on housing stock: types, age, share single-family, owner occupancy, with figures from a housing production plan, master plan or Census table.", "source": "<url>"},
  "villages": [{"name": "Neighborhood or village", "line": "One or two sentences: where it is and what is built there.", "source": "<url>"}],
  "commute": {"rail": [{"line": "Framingham/Worcester Line", "stations": ["Grafton"]}], "rail_source": "<url>", "highways": ["I-90", "I-290"], "text": "2 to 3 sentences: rail, highways and regional bus (WRTA, MetroWest RTA and so on), with no drive-time claims unless sourced."},
  "schools": {"district": "Auburn Public Schools", "dese_url": "<DESE profile URL for the district>"},
  "places": [{"name": "Park, trail, museum, landmark", "line": "One or two concrete sentences.", "source": "<url>"}],
  "history": [{"year": "1778", "text": "One sentence.", "source": "<url>"}],
  "market": {"source": "The Warren Group, MA December 2025 Sales, Prices by Town", "source_url": "<pdf url>", "period": "Calendar year 2025", "sf_sales_2025": 0, "sf_sales_2024": 0, "sf_median_2025": 0, "sf_median_2024": 0, "condo_sales_2025": null, "condo_median_2025": null, "condo_median_2024": null, "reading_1": "exact row text"},
  "tax": {"fy": "FY2026", "residential_rate": 0.0, "commercial_rate": 0.0, "residential_exemption": false, "source": "<url>", "note": "optional"},
  "nearby": ["slug", "slug", "slug", "slug"],
  "search_notes": "What currently ranks for '<Name> MA real estate agent' and 'moving to <Name> MA', and the gap a sourced guide can fill.",
  "faq": [{"q": "Question a buyer would search", "a": "Sourced answer, 1 to 3 sentences."}],
  "notes": "Everything you could not confirm, what you left out and why, and which sources you used for what."
}
```

Depth target: 3 overview paragraphs, 4 to 6 villages or neighborhoods, 6 to 8 places, 3 to 5 history
entries, 4 to 6 FAQs (always include the median price question answered from the Warren Group row,
the commuter rail question, the school district question pointing to DESE, and the property tax
rate question). FAQ answers must not contradict the rest of the record.

## Neighborhood record schema (`tools/data/neighborhoods/<city>/<slug>.json`)

Neighborhood pages sit under their city's guide (`/communities/worcester/tatnuck/`). They follow the
town schema with these differences:

- add `"parent": "worcester"` (or `"boston"`), and `"city_name"`;
- `population_2020` only if a source gives it for the same boundary (the Boston Planning Department
  publishes neighborhood profiles); otherwise omit it;
- `boundaries`: `{"text": "One or two sentences on roughly where it is, by streets, parks or landmarks.", "source": "<url>"}`;
- `villages` becomes `areas`: smaller sections, squares or historic districts within the neighborhood (2 to 4);
- `market`: Warren Group town rows only cover whole municipalities. Check whether the PDF lists this
  neighborhood separately (it lists some cities by section); if it does, fill the row, otherwise set
  `"market": null`;
- `tax`: omit (the city's rate applies; the page links to the city guide);
- `nearby`: other neighborhood slugs in the same city;
- `schools`: the city's district, with a note in `notes` that assignment is by address and set by the district.

Depth target: 2 to 3 overview paragraphs, 2 to 4 areas, 5 to 7 places, 2 to 4 history entries,
3 to 5 FAQs.

## How to deliver

- Write valid JSON (UTF-8, two-space indent) to the path given in your task. Run
  `python3 -c "import json;json.load(open('<path>'))"` to check it parses.
- When deepening an existing record, keep every field and fact that is still correct, add to it, and
  append a dated paragraph to `notes` saying what you added and from which sources.
- Do not edit any other file (the generator, other records, the site). Do not run git.
- Finish with a short report: what you wrote, anything you could not confirm, and any fair-housing
  judgment calls you made.

## Fact-checking a record

A fact-checker did not write the record and verifies it independently.

1. See what changed: `git diff 50a0ef3 -- <path>` (50a0ef3 is the last commit before the October 22,
   2026 research round; a new record shows as entirely added). Check every added or changed fact;
   spot-check the rest.
2. For each fact, open its cited source with WebFetch and confirm the source says it: numbers,
   dates, names, addresses, unit counts and rates exactly. Web search may be unavailable; work from
   the cited URLs and the official sites they link to. Do not use browser tools.
3. If a fact is wrong, correct it from the source. If it cannot be confirmed from any page you can
   open, remove it (or soften it to what the source does say). Never add a new fact without a source.
4. Re-read every sentence for the fair-housing rules and the voice rules above, and fix any slip.
5. Check internal consistency: the FAQ answers, `answer`, `meta` and overview must agree with each
   other and with `market` and `tax`.
6. Append to `notes`: "Fact-check 2026-10-22: <what you verified, what you corrected, what you
   removed, and anything still unconfirmed>."
7. Keep valid JSON and the file's existing formatting (edit values in place; do not re-indent).
