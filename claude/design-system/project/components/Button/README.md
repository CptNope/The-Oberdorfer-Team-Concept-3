# Button

Square, tracked, uppercase action with a gold rule that draws under the label on hover. Block `ot/button`.

- `.btn` is the primary: navy on ivory, ivory on navy. Use one primary per view, for the thing the page is for.
- `.btn--secondary` is the outlined companion; `.btn--gold` is reserved for the phone call on navy (`Call 406-250-7620`).
- `.btn--small` for dense rows (listing cards, showing panels). `.link-arrow` for "read more" style links, with the `arrow` icon.
- States: hover draws the underline (`rule-gold`, `dur-base`, `ease-out`); focus shows the 2px `focus-ring` at `focus-offset`; loading sets `aria-busy="true"`; disabled uses `disabled-bg` / `disabled-fg`.
- Labels are verbs that name the result: "Request a consultation", "Schedule a showing", "Get a market review", "Send my question". Never "Submit", "Click here", "Learn more" or "Unlock your dream home".
- Corners stay square (`radius` 0).
