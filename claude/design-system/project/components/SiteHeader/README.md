# SiteHeader

The site header in three variants, plus the mobile menu. Block `ot/site-header`.

- `.site-header` (navy) is the default; `.site-header--overlay` sits on a hero photo over a navy scrim and turns solid on scroll; `.site-header--light` is for ivory pages.
- The logo links home; navigation is Buy, Sell, Communities, Team, Journal, Join, Contact; the phone and a primary button sit at the right.
- Below `bp-lg` the menu button opens `.mobile-menu`, a full-screen navy panel that wipes in from the top (`dur-slow`, `ease-out`), traps focus and closes on Escape.
- The current page carries `aria-current="page"` and a gold underline.
