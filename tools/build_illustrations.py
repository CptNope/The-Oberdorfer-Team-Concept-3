#!/usr/bin/env python3
"""Usage (from the repo root):  python3 tools/build_illustrations.py

Draw the four Massachusetts house illustrations as an SVG sprite.

Line hierarchy (non-scaling strokes, so they stay hairline at every size):
  .5 main  1.2  outlines, roofs, ground
  .3 mid   0.8  trim, sills, lintels, porches, doors
  .1 fine  0.55 muntins, balusters, clapboard and shingle courses
"""
import sys

def f(n):
    return ("%.2f" % n).rstrip("0").rstrip(".")

class D:
    def __init__(self):
        self.g = {"main": [], "mid": [], "fine": []}
    def p(self, d, w="main"):
        self.g[w].append(d)
    def rect(self, x, y, wd, h, w="main"):
        self.p(f"M{f(x)} {f(y)}h{f(wd)}v{f(h)}h{f(-wd)}Z", w)
    def line(self, x1, y1, x2, y2, w="main"):
        self.p(f"M{f(x1)} {f(y1)}L{f(x2)} {f(y2)}", w)
    def svg(self, sid):
        out = [f'  <symbol id="{sid}" viewBox="0 0 400 260">',
               '    <g fill="none" stroke="currentColor" stroke-linecap="square" stroke-linejoin="miter" vector-effect="non-scaling-stroke">']
        for k, wd in (("main", 1.2), ("mid", 0.8), ("fine", 0.55)):
            if self.g[k]:
                out.append(f'      <path stroke-width="{wd}" d="{"".join(self.g[k])}"/>')
        out += ["    </g>", "  </symbol>"]
        return "\n".join(out)

# ---------------------------------------------------------------- shared parts
def window(d, cx, top, w, h, panes_up=3, panes_dn=3, lintel=True, sill=True, shutters=False):
    x = cx - w / 2
    d.rect(x, top, w, h, "mid")
    d.rect(x + 2, top + 2, w - 4, h - 4, "fine")            # sash frame
    mid = top + h / 2
    d.line(x + 2, mid, x + w - 2, mid, "mid")                 # meeting rail
    ix, iw = x + 2, w - 4
    for half, n in ((0, panes_up), (1, panes_dn)):
        y0 = top + 2 if half == 0 else mid
        y1 = mid if half == 0 else top + h - 2
        cols = 3 if n >= 3 else 2
        for c in range(1, cols):
            xx = ix + iw * c / cols
            d.line(xx, y0, xx, y1, "fine")
        rows = 2 if n >= 6 else 1
        for r in range(1, rows):
            yy = y0 + (y1 - y0) * r / rows
            d.line(ix, yy, ix + iw, yy, "fine")
    if lintel:
        d.p(f"M{f(x-3)} {f(top-2)}h{f(w+6)}v-4h{f(-w-6)}Z", "mid")
    if sill:
        d.p(f"M{f(x-3)} {f(top+h)}h{f(w+6)}v3h{f(-w-6)}Z", "mid")
    if shutters:
        for sx in (x - 7.5, x + w + 1.5):
            d.rect(sx, top, 6, h, "fine")
            d.line(sx + 1.5, top + h / 2, sx + 4.5, top + h / 2, "fine")

def steps(d, cx, base, widths, rise=4):
    y = base
    for wd in widths:
        d.p(f"M{f(cx-wd/2)} {f(y)}V{f(y-rise)}H{f(cx+wd/2)}V{f(y)}", "mid")
        y -= rise
    return y

def tree(d, cx, base, h, w):
    """Architectural entourage tree: an egg-shaped crown with the branch
    structure drawn through it in hairline, as in elevation drawings."""
    rx, ry = w / 2, h * 0.36
    cy = base - h + ry
    cb = cy + ry                                   # crown bottom
    d.p(f"M{f(cx-rx)} {f(cy+ry*0.12)}C{f(cx-rx)} {f(cy-ry*0.7)} {f(cx-rx*0.45)} {f(cy-ry)} {f(cx)} {f(cy-ry)}"
        f"C{f(cx+rx*0.45)} {f(cy-ry)} {f(cx+rx)} {f(cy-ry*0.7)} {f(cx+rx)} {f(cy+ry*0.12)}"
        f"C{f(cx+rx)} {f(cy+ry*0.75)} {f(cx+rx*0.5)} {f(cb)} {f(cx)} {f(cb)}"
        f"C{f(cx-rx*0.5)} {f(cb)} {f(cx-rx)} {f(cy+ry*0.75)} {f(cx-rx)} {f(cy+ry*0.12)}Z", "mid")
    d.line(cx, base, cx, cb, "mid")
    d.p(f"M{f(cx)} {f(cb)}V{f(cy-ry*0.45)}"
        f"M{f(cx)} {f(cy+ry*0.5)}L{f(cx-rx*0.5)} {f(cy-ry*0.15)}"
        f"M{f(cx)} {f(cy+ry*0.2)}L{f(cx+rx*0.5)} {f(cy-ry*0.35)}"
        f"M{f(cx)} {f(cy-ry*0.1)}L{f(cx-rx*0.3)} {f(cy-ry*0.55)}", "fine")

def shrub(d, x0, x1, base, h):
    """One low clipped mound."""
    d.p(f"M{f(x0)} {f(base)}C{f(x0)} {f(base-h*1.3)} {f(x1)} {f(base-h*1.3)} {f(x1)} {f(base)}", "mid")

def fence(d, x0, x1, base, h=16, pitch=7):
    d.line(x0, base - h * 0.35, x1, base - h * 0.35, "fine")
    d.line(x0, base - h * 0.75, x1, base - h * 0.75, "fine")
    x = x0
    while x <= x1 + 0.01:
        d.p(f"M{f(x)} {f(base)}V{f(base-h+3)}L{f(x+1.5)} {f(base-h)}L{f(x+3)} {f(base-h+3)}V{f(base)}", "fine")
        x += pitch

GROUND = 240

# ---------------------------------------------------------------- colonial (Georgian centre-hall, hip roof)
def colonial():
    import math
    d = D()
    d.line(16, GROUND, 384, GROUND)
    L, R, top, base = 86, 314, 122, 230
    d.p(f"M{L-4} {GROUND}V{base}H{R+4}V{GROUND}", "mid")            # foundation
    d.p(f"M{L} {base}V{top}M{R} {top}V{base}")                        # walls
    d.p(f"M{L+3} {base}V{top}M{R-3} {top}V{base}", "fine")          # corner boards
    d.rect(L - 6, top - 6, R - L + 12, 6, "mid")                       # cornice
    d.line(L, top + 3, R, top + 3, "fine")
    # hip roof with a doubled edge, as on p7
    eL, eR, ey, ry, rL, rR = L - 10, R + 10, top - 6, 68, 142, 258
    d.p(f"M{eL} {ey}L{rL} {ry}H{rR}L{eR} {ey}")
    d.p(f"M{eL+10} {ey}L{rL+3} {ry+5}H{rR-3}L{eR-10} {ey}", "fine")
    for cx in (166, 234):                                               # chimneys on the ridge
        d.p(f"M{cx-7} {ry}V{ry-22}H{cx+7}V{ry}")
        d.rect(cx - 10, ry - 26, 20, 4, "mid")
    d.line(L, 172, R, 172, "mid")                                       # belt course
    bays = [116, 158, 200, 242, 284]
    for cx in bays:
        window(d, cx, 134, 22, 30, 6, 6, shutters=True)
    for cx in bays:
        if cx != 200:
            window(d, cx, 186, 22, 34, 6, 6, shutters=True)
    # entrance: panelled door, fanlight, pilasters, flat entablature
    dx, dw, dt = 200, 22, 197
    d.rect(dx - dw / 2, dt, dw, base - dt, "mid")
    d.line(dx, dt, dx, base, "fine")
    for yy, hh in ((201, 12), (217, 9)):
        d.rect(dx - 8.5, yy, 6.5, hh, "fine"); d.rect(dx + 2, yy, 6.5, hh, "fine")
    d.p(f"M{dx-dw/2} {dt}A{dw/2} {dw/2} 0 0 1 {dx+dw/2} {dt}", "mid")
    for ang in (-50, -25, 0, 25, 50):
        t = math.radians(ang - 90)
        d.line(dx, dt, dx + math.cos(t) * (dw / 2 - 1.5), dt + math.sin(t) * (dw / 2 - 1.5), "fine")
    for px in (dx - dw / 2 - 8, dx + dw / 2 + 2):
        d.rect(px, 184, 6, base - 184, "mid")
    d.rect(dx - dw / 2 - 11, 180, dw + 22, 4, "mid")
    d.rect(dx - dw / 2 - 13, 177, dw + 26, 3, "mid")
    steps(d, dx, GROUND, [50, 42, 34], rise=3.3)
    for x0, x1 in ((L + 6, 132), (138, 176), (224, 262), (268, R - 6)):
        shrub(d, x0, x1, base, 7)
    tree(d, 44, GROUND, 128, 44)
    tree(d, 358, GROUND, 108, 38)
    return d

# ---------------------------------------------------------------- triple-decker (front gable, stacked porches)
def triple_decker():
    d = D()
    d.line(16, GROUND, 384, GROUND)
    L, R, base = 124, 276, 228
    floors = [228, 178, 128, 80]
    PR = 196                                                              # porch bay | window bay
    d.p(f"M{L-3} {GROUND}V{base}H{R+3}V{GROUND}", "mid")
    d.p(f"M{L} {base}V{floors[-1]}M{R} {floors[-1]}V{base}")
    # front gable: doubled rake, cornice returns, paired attic window
    apex = 30
    d.p(f"M{L-10} {floors[-1]+1}L200 {apex}L{R+10} {floors[-1]+1}")
    d.p(f"M{L+4} {floors[-1]-3}L200 {apex+8}L{R-4} {floors[-1]-3}", "fine")
    d.p(f"M{L-10} {floors[-1]+1}H{L+12}M{R-12} {floors[-1]+1}H{R+10}", "mid")
    for cx in (192, 208):
        d.rect(cx - 6, 50, 12, 18, "mid"); d.line(cx - 6, 59, cx + 6, 59, "fine")
    d.p("M183 46h34v-3h-34ZM183 68h34v3h-34Z", "fine")
    # right bay: two windows a floor, clapboard courses that stop at the frames
    for i in range(3):
        bot, top = floors[i], floors[i + 1]
        wt, wh = top + 11, 30
        holes = []
        for cx in (218, 254):
            window(d, cx, wt, 20, wh, 2, 2)
            holes.append((cx - 13, cx + 13))
        y = top + 6
        while y < bot - 2:
            if wt - 7 <= y <= wt + wh + 4:
                xs = [PR + 1] + [v for h in holes for v in h] + [R - 1]
                for k in range(0, len(xs), 2):
                    d.line(xs[k], y, xs[k + 1], y, "fine")
            else:
                d.line(PR + 1, y, R - 1, y, "fine")
            y += 6
        d.line(L, bot, R, bot, "mid")
    d.line(L, floors[-1], R, floors[-1], "mid")
    # left bay: three stacked porches, each with its door and window behind
    for i in range(3):
        bot, top = floors[i], floors[i + 1]
        rail = bot - 14
        d.p(f"M{L} {top+4}H{PR}", "mid")                                  # porch beam
        d.p(f"M{L+4} {top+4}V{bot}M{PR-4} {top+4}V{bot}M{PR} {top}V{bot}", "mid")   # columns + wall edge
        d.p(f"M140 {rail}V{top+12}H156V{rail}", "fine")                   # door
        d.rect(166, top + 14, 18, rail - top - 20, "fine")                  # window
        segs = [(L + 4, PR - 4)] if i else [(L + 4, 138), (158, PR - 4)]
        for x0, x1 in segs:
            d.line(x0, rail, x1, rail, "mid")
            d.line(x0, bot - 3, x1, bot - 3, "fine")
            x = x0 + 4
            while x < x1 - 1:
                d.line(x, rail, x, bot - 3, "fine"); x += 4.5
    steps(d, 148, GROUND, [24, 22, 20], rise=4)
    shrub(d, 200, 236, base, 7); shrub(d, 240, 272, base, 6)
    tree(d, 64, GROUND, 124, 46)
    tree(d, 336, GROUND, 92, 34)
    return d

# ---------------------------------------------------------------- Cape (1½ storey, side gable, centre chimney)
def cape():
    d = D()
    d.line(16, GROUND, 384, GROUND)
    L, R, top, base = 96, 304, 162, 230
    d.p(f"M{L-3} {GROUND}V{base}H{R+3}V{GROUND}", "mid")
    d.p(f"M{L} {base}V{top}M{R} {top}V{base}")
    d.p(f"M{L+3} {base}V{top}M{R-3} {top}V{base}", "fine")
    eave, ridge = top - 4, 98
    d.p(f"M{L-8} {eave}H{R+8}L{R+4} {ridge}H{L-4}Z")
    d.line(L - 2, ridge + 4, R + 2, ridge + 4, "fine")
    d.rect(L - 2, top - 4, R - L + 4, 4, "mid")
    for y in range(ridge + 13, eave - 6, 9):                              # shingle courses
        for a, b in ((L - 3, 125), (165, 235), (275, R + 3)):
            d.line(a, y, b, y, "fine")
    d.p(f"M191 {ridge}V{ridge-28}H209V{ridge}")                           # centre chimney
    d.rect(188, ridge - 32, 24, 4, "mid")
    for cx in (145, 255):                                                 # gable dormers
        d.p(f"M{cx-17} {eave-4}V122L{cx} 106L{cx+17} 122V{eave-4}")
        d.p(f"M{cx-20} 123L{cx} 103L{cx+20} 123", "mid")
        window(d, cx, 126, 16, 20, 2, 2, lintel=False)
    for cx in (122, 160, 240, 278):
        window(d, cx, 176, 22, 34, 6, 6)
    d.rect(189, 186, 22, base - 186, "mid")                               # door
    for yy, hh in ((191, 14), (210, 14)):
        d.rect(192, yy, 7, hh, "fine"); d.rect(201, yy, 7, hh, "fine")
    d.rect(189, 177, 22, 8, "fine")                                        # transom lights
    for xx in (193.4, 197.8, 202.2, 206.6):
        d.line(xx, 177, xx, 185, "fine")
    for px in (181, 213):
        d.rect(px, 177, 6, base - 177, "mid")
    d.rect(178, 172, 44, 5, "mid")
    steps(d, 200, GROUND, [40, 32], rise=5)
    for x0, x1 in ((L + 6, 138), (144, 174), (226, 256), (262, R - 6)):
        shrub(d, x0, x1, base, 7)
    fence(d, 28, 82, GROUND, 15); fence(d, 318, 372, GROUND, 15)
    d.p("M22 240V220h5v20M84 240V220h5v20M311 240V220h5v20M373 240V220h5v20", "mid")
    return d

# ---------------------------------------------------------------- townscape (a New England common on the lake)
def townscape():
    d = D()
    G = 206
    d.line(0, G, 400, G)
    for y, a, b, w in ((216, 20, 380, "mid"), (225, 56, 344, "mid"), (234, 104, 296, "fine"), (243, 160, 240, "fine")):
        d.line(a, y, b, y, w)
    # saltbox
    d.p(f"M44 {G}V152L74 130L96 145V{G}M96 145L110 156V{G}")
    d.p("M40 155L74 127L114 159", "fine")
    d.p("M66 134V112H76V126", "mid")
    for cx in (56, 84):
        d.rect(cx - 5, 160, 10, 14, "fine"); d.rect(cx - 5, 184, 10, 14, "fine")
    d.rect(100, 184, 6, G - 184, "fine")
    # town hall with cupola
    d.p(f"M140 {G}V134H202V{G}")
    d.p("M136 134L171 114L206 134")
    d.p("M145 131L171 117L197 131", "fine")
    d.rect(165, 98, 12, 14, "mid"); d.p("M162 98L171 88L180 98", "mid"); d.line(171, 88, 171, 80, "fine")
    for cx in (152, 190):
        d.rect(cx - 5, 146, 10, 16, "fine"); d.rect(cx - 5, 176, 10, 16, "fine")
    d.rect(164, 146, 14, 16, "fine")
    d.p(f"M164 {G}V180A7 7 0 0 1 178 180V{G}", "mid")
    # meeting house and steeple
    cx = 250
    d.p(f"M220 {G}V138H280V{G}")
    d.p("M216 138L250 116L284 138")
    d.p("M224 135L250 119L276 135", "fine")
    d.p(f"M238 {G}V180A12 12 0 0 1 262 180V{G}", "mid")
    d.line(cx, 168, cx, G, "fine")
    for wx in (230, 270):
        d.p(f"M{wx-5} 186V160A5 5 0 0 1 {wx+5} 160V186Z", "fine")
    d.p("M240 124V96H260V124")
    d.rect(237, 92, 26, 4, "mid")
    d.p("M242 92V74H258V92")
    d.p("M246 90V80A4 4 0 0 1 254 80V90", "fine")
    d.rect(240, 70, 20, 4, "mid")
    d.p("M244 70V60H256V70", "mid")
    d.p("M243 60L250 18L257 60")
    d.line(250, 18, 250, 10, "fine")
    # bandstand on the common
    d.p(f"M314 {G}V196H358V{G}", "mid")
    for x in range(318, 356, 4):
        d.line(x, 196, x, G - 1, "fine")
    for x in (316, 330, 342, 356):
        d.line(x, 196, x, 174, "mid")
    d.p("M310 174H362L336 152Z")
    d.line(314, 177, 358, 177, "fine")
    d.line(336, 152, 336, 145, "fine")
    # trees in the gaps
    tree(d, 22, G, 66, 26)
    tree(d, 124, G, 60, 20)
    tree(d, 297, G, 58, 20)
    tree(d, 380, G, 50, 20)
    return d

HEAD = '''<svg xmlns="http://www.w3.org/2000/svg">
  <!--
    Architectural line drawings in the manner of the publication's p7 house
    linework: doubled roof lines, hairline trim, stepped entries. They are
    ILLUSTRATIONS used as placeholders for demo listings and community cards;
    they must be replaced with real, licensed photography and must never be
    presented as a specific property.
    Stroke is currentColor; set color on the <svg> that uses them.
    Generated by tools/build_illustrations.py — edit that, not this file.
  -->
'''
def sprite():
    return HEAD + "\n".join([colonial().svg("colonial"), triple_decker().svg("triple-decker"), cape().svg("cape"), townscape().svg("townscape")]) + "\n</svg>\n"

if __name__ == "__main__":
    import os
    out = sys.argv[1] if len(sys.argv) > 1 else os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), "assets/img/illustrations.svg")
    open(out, "w").write(sprite())
    print("wrote", out)
