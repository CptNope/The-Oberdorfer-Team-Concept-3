The Oberdorfer Team is a husband-and-wife Massachusetts real estate team, Brandon and Kaitlyn Oberdorfer, operating under REWAP Brokerage LLC. This system evolves the team's print publication, *Wakefield at Home*, Fall 2026: an editorial luxury magazine set on a New England architectural grid. Evolve that look; never replace it.

## Voice and content

Speak like a neighbour who knows the market: warm, plain, specific and useful. Luxury means the standard of service and presentation, never the price of the property.

- **Be useful first.** Every paragraph should help someone decide or act: "Ask for a property-specific market review before deciding what to offer."
- **Show the craft, don't name it.** Leave out "luxurious", "stunning" and "dream home". Name the real things instead: the light, the layout, the walk to the lake.
- **Use short sentences that answer.** Build headlines as two short lines, the second moving the first forward ("Close to Boston. / Closer to what matters."). Use the `.answer` class; set the answering line in italic when it helps.
- **Source and date every fact.** Name the source, the period and the geography. "Figures may be revised" is part of the voice, not fine print.
- Keep the source lines word for word:
  - "Luxury at every price point." (the primary promise)
  - "Husband. Wife. Your Team."
  - "Thoughtful service. Creative marketing. A personal connection to every move."
  - "For the home you have. For the life you are building."
  - "A personal standard of service."
- The three pillars: **Personal** (Two partners invested in your goals.), **Purposeful** (A plan shaped around your home and life.), **Prepared** (Clear guidance and attention to the details.).
- Sentence case for headlines and body. Uppercase belongs only to `type-label` running heads, eyebrows and `type-button` labels. No emoji.
- Calls to action name the result: "Request a consultation", "Get a market review", "Schedule a showing", "Call Brandon and Kaitlyn: 406-250-7620". Never "Submit", "Click here", "Learn more", "Get started now!" or "Unlock your dream home".
- By channel: the website is clear, warm and scannable and leads with the reader's goal ("Selling this spring? Start with the right comparisons."); listings are concrete and sensory, with even more restraint for luxury ("An 1890 Queen Anne, restored with care."); agent biographies are first person and verified facts only.

## Content integrity

Never invent facts: listings, testimonials, awards, production statistics, agent profiles, license numbers, office addresses, brokerage approvals, commissions or recruiting benefits.

- Show anything unconfirmed as a `[bracketed placeholder]` or with a badge: `.badge--demo`, `.badge--verify`, `.badge--proposed` or `.badge--source`.
- Confirmed by the client: everything in the publication, including the phone **406-250-7620**, "Top Producer" and the Wakefield market figures.
- Still unconfirmed: whether the portraits are real photographs or AI-generated; team email and domain; MA license numbers; office address and status; REWAP disclosures; privacy policy; IDX/MLS provider and CRM; the final community list.
- Market data always carries its source, period and geography: "Source: Redfin, Wakefield, MA. Accessed Oct 10, 2026." Never present the Wakefield figures as live or statewide.
- Demo listings carry the Demo badge and obviously placeholder details. A testimonial appears only with the client's written permission.

## Color

Ivory is the page, read by daylight. Navy is reserved for cover moments: heroes, statistics, closings, navigation and the footer. Gold appears only as a line, a frame or a small label on navy.

- Components read the semantic tokens, never the `ot-*` primitives directly. Wrap navy sections in `.surface-inverse` (the **Navy** theme) and sandstone bands in `.surface-muted` (the **Sandstone** theme); the same component then works on every ground.
- Page ground `surface-page`; cards and inputs `surface-raised`; headlines `text-display`; body `text-body`; captions `text-muted`; eyebrows `text-accent`.
- `ot-gold` is never text on ivory (2.45:1). Use `ot-gold-ink` (5.1:1) for gold-toned text on light grounds; `ot-gold` is text only on navy (6.3:1).
- Rules are `rule-gold` (1px); quieter dividers `rule-hair`; card and table edges `border-subtle`; input edges `border-input` (3.3:1).
- Status colours are muted heritage tones: `success`, `warning`, `error`, `info`, each on its `-bg`, each above 6:1. Always pair them with an icon and words; never signal by colour alone.
- Overlays are navy only: `surface-overlay` for photo scrims and the dialog backdrop.

## Typography

- Display: **Cormorant Garamond**, verified from the publication's embedded fonts. Every headline from 30px up is Light 300 (`weight-display`). Smaller serif text uses 400–500. Italic is for the answering line and for captions.
- Text: **Manrope**. Body 400, navigation 500, labels and buttons 600.
- Sizes are fluid `clamp()` steps from 360px to 1440px (`step-*`): `type-display-xl` 52→96px for the cover, `type-h1` 34→52px for inner page titles, `type-body` 16px at 1.65 on a 68ch `measure`.
- Labels (`type-label`) are uppercase at 0.18em tracking in `text-accent`; buttons (`type-button`) at 0.14em.
- Statistics use `type-stat` with tabular lining numerals (`.tabular`).
- Load both families from Google Fonts. Production self-hosts them.

## Space, layout and shape

- Space on a 4px base with editorial jumps: `space-1` (4px) to `space-10` (128px). Sections breathe with `section-y` (64→136px), with more space above a heading than below.
- A 12-column grid: `gutter` 16→32px, `page-margin` 16→80px, `content-max` 1312px. Editorial splits are 6 + 5 with an offset, image left, text from column 8 (`.split`).
- Corners are square everywhere: `radius` 0. Never round a corner.
- Depth comes from rules and grounds. Shadows only where something floats: `shadow-1` for the sticky header, `shadow-2` for the dialog, toast and mobile menu.
- Breakpoints: `bp-sm` 576px (cards one to two up), `bp-md` 832px (splits collapse), `bp-lg` 1024px (desktop navigation), `bp-xl` 1440px (reference width).

## Lines and signatures

The publication's linework is the brand's ornament. Use it sparingly.

- **Gold rule** (`.rule`): 1px `rule-gold` above section heads, triad columns and key facts.
- **Folio frame** (`.folio-frame`): the cover's inset double gold frame, `frame-inset` from the edge with a `frame-gap` of 6px. One per view, on cover moments only.
- **Running head and folio bar** (`.running-head`, `.folio-bar`): page furniture taken from the publication.
- **Dimension rule** (`.dim-rule`): the architectural line with end ticks (`tick`) from the p7 house drawing. It closes a promise band or a statement.

## Imagery

- Use natural-light architecture and lifestyle photography, credited and licensed. Credit every image in a `.caption` beside it.
- Overlay photographs only with the navy scrim (`surface-overlay`), never a coloured gradient.
- Ratios: 16:9, 4:5, 4:3, 3:4, 3:2 and 9:16. Set a `--focal` point per image so phone crops keep the subject.
- Never present AI-generated images as real people, homes or places. The team portraits are pending confirmation and carry a Verify badge until the client confirms them.
- Until photography exists, use the line illustrations (colonial, triple-decker, Cape, townscape) as labeled placeholders: `ot-gold` on `ot-navy`, or `ot-navy` on `ot-sandstone`.

## Iconography

- One icon set: 24 single-weight line icons drawn at 1.25px on a 24px grid, used from the SVG sprite `assets/img/icons.svg` (`i-*` ids) so they inherit `currentColor`. The Icons asset group holds standalone copies drawn in `ot-navy`.
- Size icons at 1em to 1.25em beside text; always pair them with words. Icon-only buttons (menu, close, save, search) carry an `aria-label`.
- The arrow (34×12) only follows a text link (`.link-arrow`). The Equal Housing Opportunity mark (`eho`) appears in every footer.
- No emoji, no filled or duotone icons, no icon fonts.

## Logos and the brokerage

- Every logo is outlined from the Cormorant Garamond Light glyphs in the publication. Never set the wordmark in live type, redraw, stretch or recolour it.
- Use the primary horizontal logo (`logo-primary-*`) on website headers, signage and print covers; the stacked and compact versions where width is short; the monogram (its frame is the cover's inset border, in the 8.5 × 11 page proportion) for favicons and avatars.
- Approved colours: navy on ivory or sandstone; gold or ivory on navy; ivory on photography over a navy scrim; single colour (`*-mono`) for production methods.
- Clear space: at least the cap height of the "T" on all sides. On covers, the inset frame counts as the edge.
- Minimum sizes: primary 120px wide (1.25 in); stacked 90px (0.9 in); compact 24px tall; monogram 20px tall (0.25 in), single frame below 32px.
- The team and REWAP Brokerage are separate brands. Never redraw or recolour the REWAP logo; show it only on its own ivory plate (`surface-raised`), or use the typographic "Powered by REWAP Brokerage LLC" lockup. Never imply the team is an independently licensed brokerage.

## Motion

- One gesture: gold rules draw from left to right with `ease-out` (cubic-bezier(0.16, 1, 0.3, 1)) and content settles 14px beneath them over `dur-slow`.
- The button underline draws on hover (`dur-base`). Menus and the contents panel open with a top-down clip wipe.
- No parallax, no looping animation. `prefers-reduced-motion` removes all movement.

## Accessibility

- Target WCAG 2.2 AA. Text pairs hold 4.5:1 in every theme (see each token's note); borders, focus rings and meaningful icons hold 3:1.
- Keep focus visible: a solid 2px `focus-ring` at `focus-offset` 3px, navy on light grounds and `ot-gold-pale` on navy.
- Every field has a visible label. Errors say what to do and pair `error` with an icon.
- Dialogs trap focus and return it; toasts and result counts announce politely; the mobile menu closes on Escape.
- Every footer carries the Equal Housing Opportunity notice.
