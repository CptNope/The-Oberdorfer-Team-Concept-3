#!/usr/bin/env python3
import json, os, re
REPO = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
OUT = os.path.join(REPO, "claude", "design-system"); P = f"{OUT}/project"; CK = f"{OUT}/check"
NOW = "2026-10-22T16:43:15Z"
B = json.load(open(f"{OUT}/blobs.json"))
def w(rel, text):
    path = os.path.join(P, rel); os.makedirs(os.path.dirname(path), exist_ok=True); open(path, "w").write(text)

# ---------------------------------------------------------------- heights into markers
H = json.load(open(f"{CK}/heights.json"))
for c in json.load(open(f"{CK}/cards.json")):
    doc = open(f"{CK}/{c['name']}.html").read().replace("height=HEIGHT", f"height={min(4000, H[c['name']] + 8)}")
    w(f"components/{c['name']}/preview.html", doc)

# ---------------------------------------------------------------- asset group READMEs
GROUP_NOTES = {
"Logos": """# Logos

The Oberdorfer Team wordmark, outlined from the Cormorant Garamond Light glyphs embedded in *Wakefield at Home*, Fall 2026, so it matches the cover exactly. Never set it in live type, redraw, stretch or recolour it. Clear space: the cap height of the "T" on all sides.

Each file is single-ink; the ink is in its name:

- `logo-primary-navy.svg`: primary horizontal, `ot-navy` ink. Website headers on ivory, signage, print. Minimum 120px wide.
- `logo-primary-ivory.svg`: primary, `ot-ivory` ink. Headers and covers on navy or photography over a navy scrim.
- `logo-primary-gold.svg`: primary, `ot-gold` ink. On navy only, as on the cover and back cover.
- `logo-primary-mono.svg`: primary, black ink, for single-colour production.
- `logo-stacked-navy.svg`, `logo-stacked-ivory.svg`, `logo-stacked-mono.svg`: stacked, for square and narrow spaces. Minimum 90px wide.
- `logo-compact-navy.svg`, `logo-compact-ivory.svg`, `logo-compact-mono.svg`: one-line compact, for the mobile header and the documentation bar. Minimum 24px tall.
- `monogram-navy.svg`, `monogram-ivory.svg`, `monogram-mono.svg`: the O inside the cover's inset frame, in the 8.5 × 11 page proportion. Avatars and small marks; minimum 20px tall, single frame below 32px.
- `favicon.svg`, `favicon-32.png`, `apple-touch-icon.png`: browser and home-screen icons (navy ground, gold monogram).
- `social-avatar.svg`, `social-avatar.png`: square profile image for social accounts.
- `lockup-brokerage-navy.svg`, `lockup-brokerage-ivory.svg`: the team wordmark with the "Powered by" REWAP attribution, for print backs and the footer. The REWAP logo inside is the brokerage's own artwork on its ivory plate.

All marks are proposed and await the team's approval.
""",
"Icons": """# Icons

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
""",
"Illustrations": """# Illustrations

Line drawings of Massachusetts house types in the publication's line, from the house drawing on page 7. Single-ink, drawn here in `ot-navy`; on navy grounds use them in `ot-gold` from the sprite.

- `colonial.svg`: the colonial. Demo listings and Wakefield community cards.
- `triple-decker.svg`: the triple-decker of Worcester and Greater Boston.
- `cape.svg`: the Cape.
- `townscape.svg`: a New England town skyline. Community cards and the buy pathway.

Use them as labeled placeholders until licensed photography arrives. They are not photographs of real homes; never caption one as a property.
""",
"Photography": """# Photography

Images from *Wakefield at Home*, Fall 2026. Credit every image beside it in a `.caption`.

- `cover-portrait.webp`: Brandon and Kaitlyn, the publication's cover portrait. Home cover hero. Whether it is a real photograph or AI-generated is not yet confirmed: carry a Verify badge until the team confirms it.
- `team-portrait.webp`: Brandon and Kaitlyn, the publication's team photo. Agent card and profile. Same caveat as the cover portrait.
- `brandon-oberdorfer.webp`, `kaitlyn-oberdorfer.webp`: interim headshots for the individual agent profiles, cropped from `team-portrait.webp`. Same caveat as the team portrait; replace with individual headshots (4:5, natural light) when supplied.
- `lake-quannapowitt.webp`: Lake Quannapowitt Panorama, RedSoxFan274 / Wikimedia Commons, CC BY-SA 4.0, cropped. Wakefield community and photo heroes.
- `breakheart-reservation.webp`: Breakheart Reservation, Joseph Reagle / Wikimedia Commons, CC BY-SA 4.0, cropped. Seasonal features and journal.

Overlay these only with the navy scrim (`surface-overlay`). The publication's page 6 food image is AI-generated and is not included.
""",
"Publication": """# Publication

The source publication: *Wakefield at Home*, Fall 2026, 8 pages, 8.5 × 11 in, saddle-stitched. Every colour, typeface and line in this system was taken from it.

- `wakefield-at-home-fall-2026.pdf`: the web-compressed PDF (3 MB). Offered as the past-issue download on the journal.
- `page-1.webp` to `page-8.webp`: page renders, used as the archive card cover (page 1) and as reference. Page 1 cover, 2 principles, 3 history, 4 market statistics, 5 to 6 local living, 7 house linework and steps, 8 back cover.

Market figures inside are Wakefield only, from Redfin, accessed Oct 10, 2026, and historical.
""",
"Brokerage": """# Brokerage

- `rewap-logo.webp`: the logo of REWAP Brokerage LLC (Real Estate Wealth Acquisition and Preservation Brokerage), the team's principal brokerage. Third-party artwork: never redraw, recolour, crop or restyle it. Show it only on its own ivory plate (`surface-raised` / `ot-paper`), as in the footer, or use the typographic "Powered by REWAP Brokerage LLC" lockup instead.

The team and the brokerage are separate brands. Never imply the team is an independently licensed brokerage.
""",
}
for g, t in GROUP_NOTES.items():
    w(f"assets/{g}/README.md", t)

# ---------------------------------------------------------------- cover
COVER = """<!-- @dsCard height=336 -->
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>The-Oberdorfer-Team-Concept-3</title>
<style>
  html, body { margin:0; height:100%; }
  body { background:var(--surface-page); color:var(--text-display); font-family:var(--font-sans); overflow:hidden; padding:0; }
  .cover { position:relative; width:960px; height:336px; overflow:hidden; background:var(--surface-page); }
  .band { position:absolute; top:0; left:0; width:960px; height:112px; }
  .band svg { display:block; width:960px; height:112px; }
  .deep { fill:var(--text-display); }
  .sand { fill:var(--ot-sandstone); }
  .gold { fill:var(--ot-gold); }
  .pale { fill:var(--ot-gold-pale); }
  .blk { rx:var(--radius); }
  .frame { fill:none; stroke:var(--rule-gold); stroke-width:var(--rule); }
  .frame.inner { opacity:.45; }
  .dim { fill:none; stroke:var(--ot-gold-ink); stroke-width:var(--rule); }
  .words { position:absolute; left:var(--space-6); right:var(--space-6); bottom:var(--space-6); }
  .name { margin:0; font-family:var(--font-display); font-weight:var(--weight-display); font-size:64px; line-height:.95; letter-spacing:-0.012em; color:var(--text-display); }
  .name span { display:block; }
  .tag { margin:var(--space-2) 0 0 2px; font-size:14px; line-height:20px; color:var(--text-muted); max-width:440px; }
</style>
</head>
<body>
<div class="cover">
<div class="band" aria-hidden="true">
<svg viewBox="0 0 960 112" width="960" height="112">
<!--
  blocks      deep ink (text-display: ot-navy on ivory, ot-ivory on navy) 448×112 · ot-sandstone 320×112 · ot-gold 48×112 · ot-gold-pale 144×112 — a full-width band 112 tall (space-9 + space-4), 39% of 960×336
  arrangement a strip of unequal bands bleeding off the top, the way the publication stacks its navy cover over ivory pages; widths 7 × space-8, 5 × space-8, space-7, 3 × space-7
  pattern     editorial row ("borders not shadows", Cormorant Light, a 1px gold rule language): the folio frame (rule-gold, inset space-4 with the 6px frame-gap, inner line at .45) on the deep block, and one dimension rule with tick-high end ticks across the sandstone (ot-gold-ink) — three rules, from the p1 cover and the p7 house drawing
  scales      space-4 / space-6 / space-7 / space-8 / space-9, frame-gap 6, tick 9, rule 1px, radius 0 (square, as in print)
-->
<rect class="deep blk" x="0" y="0" width="448" height="112"/>
<rect class="sand blk" x="448" y="0" width="320" height="112"/>
<rect class="gold blk" x="768" y="0" width="48" height="112"/>
<rect class="pale blk" x="816" y="0" width="144" height="112"/>
<rect class="frame" x="16.5" y="16.5" width="415" height="79"/>
<rect class="frame inner" x="22.5" y="22.5" width="403" height="67"/>
<path class="dim" d="M480.5 56.5H736.5M480.5 52v9M736.5 52v9"/>
</svg>
</div>
<div class="words">
<h1 class="name"><span>The-Oberdorfer-</span><span>Team-Concept-3</span></h1>
<p class="tag">Luxury at every price point.</p>
</div>
</div>
</body>
</html>
"""
w("components/Cover/preview.html", COVER)

# ---------------------------------------------------------------- index
TILE = {"Logos": "m", "Icons": "xs", "Illustrations": "m", "Photography": "l", "Publication": "m", "Brokerage": "m"}
ORDER = {
 "Logos": ["logo-primary-navy.svg", "logo-primary-ivory.svg", "logo-primary-gold.svg", "logo-primary-mono.svg", "logo-stacked-navy.svg", "logo-stacked-ivory.svg", "logo-stacked-mono.svg",
           "logo-compact-navy.svg", "logo-compact-ivory.svg", "logo-compact-mono.svg", "monogram-navy.svg", "monogram-ivory.svg", "monogram-mono.svg",
           "lockup-brokerage-navy.svg", "lockup-brokerage-ivory.svg", "favicon.svg", "favicon-32.png", "apple-touch-icon.png", "social-avatar.svg", "social-avatar.png"],
 "Photography": ["cover-portrait.webp", "team-portrait.webp", "brandon-oberdorfer.webp", "kaitlyn-oberdorfer.webp", "lake-quannapowitt.webp", "breakheart-reservation.webp"],
 "Illustrations": ["colonial.svg", "triple-decker.svg", "cape.svg", "townscape.svg"],
 "Publication": ["wakefield-at-home-fall-2026.pdf"] + [f"page-{i}.webp" for i in range(1, 9)],
}
groups = ["Logos", "Icons", "Illustrations", "Photography", "Publication", "Brokerage"]
ag = {}
for g in groups:
    files = {k.split("/", 1)[1]: v for k, v in B.items() if k.startswith(g + "/")}
    order = ORDER.get(g, sorted(files))
    assert sorted(order) == sorted(files), (g, set(order) ^ set(files))
    ag[g] = {"name": g, "tile": TILE[g], "order": order,
             "files": {n: {"name": n, "blob": files[n][0], "size": files[n][1], "type": files[n][2]} for n in order}}
index = {
 "v": 3, "layout": "files", "createdOnFiles": {"v": 1, "at": NOW},
 "title": "The-Oberdorfer-Team-Concept-3", "namespace": "OberdorferTeam", "libraries": [],
 "sections": {}, "groups": groups, "assetGroups": ag, "blobs": {},
 "docs": {"readme": "project/README.md", "sections": []},
 "lastChange": {"by": "Claude", "via": "Claude Code · CptNope/The-Oberdorfer-Team-Concept-3@87e58c4", "at": NOW,
                "note": "Built from the repo: tokens, brand book, 48 component cards, 62 assets and the cover."},
}
w("design-system.json", json.dumps(index, indent=2))
print("ok")
