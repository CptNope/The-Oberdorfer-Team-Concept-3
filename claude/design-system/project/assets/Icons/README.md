# Icons

Standalone copies of the 24-icon sprite (`assets/img/icons.svg` in the repo, ids `i-*`). Single-weight line icons, 1.25px stroke on a 24px grid (the arrow is 34×12).

These copies are drawn in `ot-navy` ink, because an `<img>` cannot inherit colour. In code use the sprite with `<use>` so icons take `currentColor`.

- `search`, `menu`, `close`, `chevron`: header and navigation controls (icon-only buttons need an `aria-label`).
- `arrow`: follows a text link only (`.link-arrow`).
- `phone`, `mail`, `pin`, `map`, `calendar`, `door`: contact lines, office card, showings.
- `heart`: save a listing (`aria-pressed`).
- `bed`, `bath`, `area`, `grid`: listing facts.
- `user`: agent placeholders and the open seat.
- `check`, `alert`, `info`: success, warning or error, and info messages, always with words.
- `copy`, `download`, `external`: utility actions.
- `eho`: the Equal Housing Opportunity mark, required in every footer.
