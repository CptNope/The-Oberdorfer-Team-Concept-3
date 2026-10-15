"""Generate the Oberdorfer Team logo system as outlined SVG.

Usage (from the repo root):  python3 tools/build_logos.py
Writes every SVG in assets/logo/. PNG exports (avatar, favicons) are made
separately by rendering the SVGs in a browser.

Glyph outlines come from the Cormorant Garamond Light subset embedded in
Wakefield at Home (Fall 2026), so the wordmark is drawn in the publication's
exact face. Cormorant Garamond is SIL OFL 1.1.
"""
import os, sys
from fontTools.ttLib import TTFont
from fontTools.pens.svgPathPen import SVGPathPen
from fontTools.pens.transformPen import TransformPen
from fontTools.pens.boundsPen import BoundsPen

_here = os.path.dirname(os.path.abspath(__file__))
FONT_DIR = sys.argv[1] if len(sys.argv) > 1 else os.path.join(_here, "fonts")
OUT = sys.argv[2] if len(sys.argv) > 2 else os.path.join(os.path.dirname(_here), "assets", "logo")
os.makedirs(OUT, exist_ok=True)

NAVY, IVORY, GOLD, INK = "#071D2D", "#F7F3EC", "#B49A65", "#142A38"
CG = TTFont(os.path.join(FONT_DIR, "CormorantGaramond-Light.ttf"))


def text(font, s, size, x=0, y=0, track=0.0):
    """Return (path_d, width, (xmin,ymin,xmax,ymax)) for s, baseline at y."""
    gs, cmap = font.getGlyphSet(), font.getBestCmap()
    upm = font["head"].unitsPerEm
    k = size / upm
    pen = SVGPathPen(gs, ntos=lambda v: (f"{v:.1f}").rstrip("0").rstrip("."))
    bp = BoundsPen(gs)
    cx = 0.0
    for i, ch in enumerate(s):
        g = cmap.get(ord(ch))
        if g is None:
            raise SystemExit(f"missing glyph {ch!r}")
        t = (k, 0, 0, -k, x + cx, y)
        gs[g].draw(TransformPen(pen, t))
        gs[g].draw(TransformPen(bp, t))
        cx += gs[g].width * k
        if i < len(s) - 1:
            cx += track * size
    return pen.getCommands(), cx, bp.bounds


def r(v):
    return f"{v:.2f}".rstrip("0").rstrip(".")


def svg(name, w, h, body, title, bg=None):
    rect = f'<rect width="{r(w)}" height="{r(h)}" fill="{bg}"/>' if bg else ""
    doc = (f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {r(w)} {r(h)}" '
           f'role="img" aria-labelledby="t"><title id="t">{title}</title>{rect}{body}</svg>\n')
    with open(os.path.join(OUT, name), "w") as f:
        f.write(doc)


def centered(font, s, size, cx, baseline, track=0.0):
    _, w, _ = text(font, s, size, 0, 0, track)
    return text(font, s, size, cx - w / 2, baseline, track)


# ---------------------------------------------------------------- wordmark
def primary(color, rule, name, bg=None, title="The Oberdorfer Team"):
    size, pad = 100, 24
    d, w, b = text(CG, "The Oberdorfer Team", size, pad, 0, 0.012)
    top, bottom = b[1], b[3]
    H = (bottom - top) + pad * 2
    d, w, b = text(CG, "The Oberdorfer Team", size, pad, pad - top, 0.012)
    body = f'<path fill="{color}" d="{d}"/>'
    svg(name, w + pad * 2, H, body, title, bg)


# ---------------------------------------------------------------- stacked
def stacked(color, rule, name, bg=None):
    W = 640
    cx = W / 2
    d1, w1, b1 = centered(CG, "THE", 30, cx, 70, 0.42)
    d2, w2, b2 = centered(CG, "Oberdorfer", 128, cx, 190, 0.0)
    d3, w3, b3 = centered(CG, "TEAM", 30, cx, 268, 0.42)
    rl = (W - w2) / 2
    rr = W - rl
    t_left, t_right = b3[0] - 26, b3[2] + 26
    lines = (f'<path stroke="{rule}" stroke-width="1.5" d="M{r(rl)} 256H{r(t_left)}M{r(t_right)} 256H{r(rr)}"/>')
    body = (f'<g fill="{color}"><path d="{d2}"/></g>'
            f'<g fill="{color}" stroke="{color}" stroke-width="0.7"><path d="{d1}"/><path d="{d3}"/></g>{lines}')
    svg(name, W, 300, body, "The Oberdorfer Team", bg)


# ---------------------------------------------------------------- monogram
def mono_body(color, rule, x=0, y=0, s=1.0, double=True):
    """Framed O. Frame proportion follows the 8.5x11 publication page."""
    W, H = 110, 142
    out = []
    out.append(f'<rect x="{r(x+1*s)}" y="{r(y+1*s)}" width="{r((W-2)*s)}" height="{r((H-2)*s)}" fill="none" stroke="{rule}" stroke-width="{r(1.6*s)}"/>')
    if double:
        out.append(f'<rect x="{r(x+7*s)}" y="{r(y+7*s)}" width="{r((W-14)*s)}" height="{r((H-14)*s)}" fill="none" stroke="{rule}" stroke-width="{r(0.8*s)}"/>')
    d, w, b = centered(CG, "O", 104 * s, x + W * s / 2, 0)
    gh = b[3] - b[1]
    base = y + (H * s) / 2 - (b[1] + b[3]) / 2
    d, w, b = centered(CG, "O", 104 * s, x + W * s / 2, base)
    out.append(f'<path fill="{color}" d="{d}"/>')
    return "".join(out), W * s, H * s


def monogram(color, rule, name, bg=None):
    pad = 10 if not bg else 30
    body, w, h = mono_body(color, rule, pad, pad)
    svg(name, w + pad * 2, h + pad * 2, body, "The Oberdorfer Team monogram", bg)


# ---------------------------------------------------------------- compact
def compact(color, rule, name, bg=None):
    mb, mw, mh = mono_body(color, rule, 0, 0, 0.62, double=False)
    d, w, b = text(CG, "THE OBERDORFER TEAM", 30, mw + 26, 0, 0.16)
    base = mh / 2 - (b[1] + b[3]) / 2
    d, w, b = text(CG, "THE OBERDORFER TEAM", 30, mw + 26, base, 0.16)
    body = mb + f'<path fill="{color}" stroke="{color}" stroke-width="0.6" d="{d}"/>'
    svg(name, mw + 26 + w + 2, mh, body, "The Oberdorfer Team", bg)


# ---------------------------------------------------------------- avatar / favicon
def avatar(name):
    S = 800
    mb, mw, mh = mono_body(IVORY, GOLD, 0, 0, 3.2)
    g = f'<g transform="translate({r((S-mw)/2)} {r((S-mh)/2)})">{mb}</g>'
    svg(name, S, S, g, "The Oberdorfer Team", NAVY)


def favicon(name):
    S = 64
    d, w, b = centered(CG, "O", 58, S / 2, 0)
    base = S / 2 - (b[1] + b[3]) / 2
    d, w, b = centered(CG, "O", 58, S / 2, base)
    body = (f'<rect x="7" y="3" width="50" height="58" fill="none" stroke="{GOLD}" stroke-width="2"/>'
            f'<path fill="{IVORY}" d="{d}" stroke="{IVORY}" stroke-width="1.4"/>')
    svg(name, S, S, body, "The Oberdorfer Team", NAVY)


# ---------------------------------------------------------------- brokerage lockup
def lockup(color, rule, sub, name, bg=None):
    W = 1100
    cx = W / 2
    d1, w1, b1 = centered(CG, "The Oberdorfer Team", 96, cx, 110, 0.012)
    d2, _, _ = centered(CG, "POWERED BY", 20, cx, 196, 0.36)
    d3, w3, _ = centered(CG, "REAL ESTATE WEALTH ACQUISITION AND PRESERVATION BROKERAGE", 22, cx, 238, 0.14)
    rl, rr = (W - w1) / 2, (W + w1) / 2
    body = (f'<path fill="{color}" d="{d1}"/>'
            f'<path stroke="{rule}" stroke-width="1.5" d="M{r(rl)} 152H{r(rr)}"/>'
            f'<g fill="{sub}" stroke="{sub}" stroke-width="0.6"><path d="{d2}"/><path d="{d3}"/></g>')
    svg(name, W, 268, body, "The Oberdorfer Team, powered by Real Estate Wealth Acquisition and Preservation Brokerage", bg)


CUR = "currentColor"
primary(NAVY, GOLD, "logo-primary-navy.svg")
primary(GOLD, GOLD, "logo-primary-gold.svg")
primary(IVORY, GOLD, "logo-primary-ivory.svg")
primary(CUR, CUR, "logo-primary-mono.svg")
stacked(NAVY, GOLD, "logo-stacked-navy.svg")
stacked(IVORY, GOLD, "logo-stacked-ivory.svg")
stacked(CUR, CUR, "logo-stacked-mono.svg")
compact(NAVY, GOLD, "logo-compact-navy.svg")
compact(IVORY, GOLD, "logo-compact-ivory.svg")
compact(CUR, CUR, "logo-compact-mono.svg")
monogram(NAVY, GOLD, "monogram-navy.svg")
monogram(IVORY, GOLD, "monogram-ivory.svg")
monogram(CUR, CUR, "monogram-mono.svg")
avatar("social-avatar.svg")
favicon("favicon.svg")
lockup(NAVY, GOLD, INK, "lockup-brokerage-navy.svg")
lockup(GOLD, GOLD, IVORY, "lockup-brokerage-ivory.svg")
print("ok", sorted(os.listdir(OUT)))
