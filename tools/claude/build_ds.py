#!/usr/bin/env python3
"""Generate the Claude Design System files for The Oberdorfer Team
from the repo at REPO into OUT/project. Also writes a local check copy."""
import json, os, re, sys, html
from bs4 import BeautifulSoup

REPO = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
OUT = os.path.join(REPO, "claude", "design-system")
P = os.path.join(OUT, "project")
NOW = "2026-10-22T16:43:15Z"
BLOBS = json.load(open(os.path.join(OUT, "blobs.json")))

def w(rel, text):
    path = os.path.join(P, rel)
    os.makedirs(os.path.dirname(path), exist_ok=True)
    open(path, "w").write(text)

# ------------------------------------------------------------------ parse tokens.css
CSS = open(f"{REPO}/assets/css/tokens.css").read()
root = CSS[CSS.index(":root {"):CSS.index("\n}\n", CSS.index(":root {"))]
VARS, NOTES = {}, {}
for name, value in re.findall(r"--([a-z0-9-]+):\s*([^;]+);", root):
    VARS[name] = re.sub(r"/\*.*?\*/", "", value).strip()
def block(sel):
    i = CSS.index(sel); j = CSS.index("}", i)
    return dict((n, re.sub(r"/\*.*?\*/", "", v).strip()) for n, v in re.findall(r"--([a-z0-9-]+):\s*([^;]+);", CSS[i:j]))
NAVY = block(".surface-inverse,\n[data-surface=\"navy\"]")
SAND = block(".surface-muted,\n[data-surface=\"sandstone\"]")

def hexrgb(h):
    h = h.lstrip("#"); return tuple(int(h[i:i+2], 16) for i in (0, 2, 4))
def resolve(v, scope=None):
    scope = scope or {}
    m = re.fullmatch(r"var\(--([a-z0-9-]+)\)", v)
    if m:
        n = m.group(1); return resolve(scope.get(n, VARS[n]), scope)
    return v
def mix(v, scope=None):
    m = re.fullmatch(r"color-mix\(in srgb, var\(--([a-z0-9-]+)\) (\d+)%, var\(--([a-z0-9-]+)\)\)", v)
    a = hexrgb(resolve(f"var(--{m.group(1)})", scope)); b = hexrgb(resolve(f"var(--{m.group(3)})", scope)); p = int(m.group(2)) / 100
    return "#" + "".join("%02x" % round(a[i] * p + b[i] * (1 - p)) for i in range(3))
def colorval(v, scope=None):
    v = v.strip()
    m = re.fullmatch(r"var\(--([a-z0-9-]+)\)", v)
    if m: return "{%s}" % m.group(1)
    if v.startswith("color-mix"): return mix(v, scope)
    m = re.fullmatch(r"rgb\((\d+) (\d+) (\d+) / ([\d.]+)\)", v)
    if m: return "rgba(%s, %s, %s, %s)" % m.groups()
    return v.lower()
def rgb_to_hexa(s):
    return re.sub(r"rgb\((\d+) (\d+) (\d+) / ([\d.]+)\)",
                  lambda m: "#%02x%02x%02x%02x" % (int(m.group(1)), int(m.group(2)), int(m.group(3)), round(float(m.group(4)) * 255)), s)

def lum(h):
    def c(x):
        x /= 255; return x / 12.92 if x <= 0.03928 else ((x + 0.055) / 1.055) ** 2.4
    r, g, b = hexrgb(h); return 0.2126 * c(r) + 0.7152 * c(g) + 0.0722 * c(b)
def ratio(a, b):
    la, lb = sorted([lum(a), lum(b)], reverse=True); return (la + 0.05) / (lb + 0.05)

# ------------------------------------------------------------------ tokens.json
U = {  # usage notes, naming grounds for text colours
 "ot-navy": "Midnight Navy, the brand's primary. Cover moments only: heroes, statistics panels, navigation, CTA panels, the footer. Display text on ivory (15.5:1).",
 "ot-navy-800": "Raised navy surface: cards and inputs inside navy sections.",
 "ot-navy-700": "Navy hover state and hairline borders on navy.",
 "ot-ink": "Text Ink. Body copy on ivory (13.4:1), paper and sandstone.",
 "ot-ivory": "Warm Ivory, the page ground. Also primary text and buttons on navy (15.5:1).",
 "ot-paper": "Lifted ivory for inputs, raised cards and the REWAP logo plate.",
 "ot-sandstone": "Sandstone bands: history band, sell pathway, muted sections.",
 "ot-sandstone-2": "Deeper sandstone for borders on sandstone grounds.",
 "ot-gold": "Antique Gold. Lines, frames, rules and small labels on navy only (6.3:1 on navy). Never text on ivory: 2.45:1 there.",
 "ot-gold-ink": "Gold Ink. Gold-toned text (eyebrow labels, accents) on ivory (5.1:1) and paper.",
 "ot-gold-pale": "Pale gold: hairlines on navy, focus ring on navy, link hover on navy.",
 "ot-slate": "Slate. Captions and metadata on ivory (5.4:1).",
 "ot-mist": "Mist. Secondary text on navy (7.6:1).",
 "surface-page": "The page ground: ivory in light sections, navy in navy, sandstone in sandstone.",
 "surface-raised": "Cards, inputs and panels that sit on the page ground.",
 "surface-muted": "Quiet bands and callouts.",
 "surface-inverse": "Navy ground for cover moments inside a light page.",
 "surface-inverse-raised": "Raised panels inside navy sections.",
 "surface-overlay": "Navy photo scrim and modal backdrop. Overlays are navy only.",
 "text-display": "Headlines and display numerals on `surface-page` (15.5:1 light, 15.5:1 navy).",
 "text-body": "Body copy on `surface-page` and `surface-raised` (13.4:1 on ivory, 13.0:1 on navy).",
 "text-muted": "Captions, metadata and helper text on `surface-page` (5.4:1 ivory, 4.7:1 sandstone default, 7.6:1 navy).",
 "text-accent": "Eyebrow labels and accents on `surface-page`: gold ink on ivory (5.1:1), gold on navy (6.3:1).",
 "text-on-inverse": "Text placed on `surface-inverse` from a light context.",
 "text-on-inverse-muted": "Secondary text on `surface-inverse` (7.6:1).",
 "text-on-inverse-accent": "Gold labels on `surface-inverse` (6.3:1).",
 "text-link": "Inline links on `surface-page`, underlined with a gold rule.",
 "text-link-hover": "Link hover colour on `surface-page`.",
 "rule-gold": "The publication's 1px gold rule: section rules, folio frames, dimension rules, button underline.",
 "rule-hair": "Quieter hairline: dividers between rows and list items.",
 "border-subtle": "Card and table borders.",
 "border-strong": "Strong borders: selected chips, emphasised rows.",
 "border-input": "Input borders (3.3:1 on ivory, meets WCAG 1.4.11).",
 "border-on-inverse": "Borders on navy.",
 "focus-ring": "Solid 2px focus outline, offset 3px: navy on light grounds (15.5:1), pale gold on navy.",
 "focus-ring-inverse": "Focus ring for controls on navy.",
 "hover-tint": "Hover wash for rows and quiet buttons on light grounds.",
 "hover-tint-inverse": "Hover wash on navy.",
 "press-tint": "Pressed wash on light grounds.",
 "disabled-bg": "Disabled control fill.",
 "disabled-fg": "Disabled control text (3.5:1; disabled is exempt but stays legible).",
 "disabled-border": "Disabled control border.",
 "input-bg": "Text field fill.",
 "input-fg": "Text field value on `input-bg`.",
 "input-placeholder": "Placeholder text on `input-bg` (4.7:1 on paper).",
 "input-border": "Text field border at rest.",
 "input-border-hover": "Text field border on hover.",
 "input-border-focus": "Text field border on focus.",
 "success": "Success text and icon on `success-bg` (6.3:1). Always paired with an icon and words.",
 "success-bg": "Success alert and form-status ground.",
 "warning": "Warning text and icon on `warning-bg` (6.0:1). Always paired with an icon and words.",
 "warning-bg": "Warning alert ground; also the Verify badge.",
 "error": "Error text, icon and invalid-field border on `error-bg` and ivory (6.2:1). Always paired with an icon and words.",
 "error-bg": "Error alert and invalid-field message ground.",
 "info": "Info text and icon on `info-bg`.",
 "info-bg": "Info alert ground.",
}
color_names = [k for k in VARS if k.startswith("ot-")] + [k for k in VARS if re.match(r"(surface|text|rule|border|focus-ring|hover|press|disabled|input|success|warning|error|info)", k)]
colors = []
for n in color_names:
    if n in ("focus-width", "focus-offset", "rule"): continue
    light = colorval(VARS[n])
    val = light
    per = {}
    if n in NAVY: per["navy"] = colorval(NAVY[n], NAVY)
    if n in SAND: per["sandstone"] = colorval(SAND[n], SAND)
    if per: val = {"light": light, **per}
    colors.append({"name": n, "value": val, "usage": U.get(n, "")})
missing = [c["name"] for c in colors if not c["usage"]]
assert not missing, missing

def lengths(prefix, note_fn=lambda k, v: ""):
    return [{"name": k, "value": v, "usage": note_fn(k, v)} for k, v in VARS.items() if k.startswith(prefix)]

STEP_USE = {
 "display-xl": "Cover hero headline (52→96px).", "display-l": "Page hero headline on the home page (44→76px).",
 "display-m": "Chapter and section display headline (36→56px).", "h1": "Inner page title (34→52px).", "h2": "Section title (30→44px).",
 "h3": "Card group and block title (24→34px).", "h4": "Card title (21→26px).", "h5": "Small serif heading, 20px, weight 500.",
 "h6": "Sans label heading, 13px, weight 600.", "lead": "Lead paragraph under a headline (18→21px), weight 300.",
 "body-l": "Large body copy, 18px.", "body": "Body copy, 16px at 1.65 on a 68ch measure.", "body-s": "Small body copy, 14px.",
 "caption": "Captions, credits and metadata, 13px.", "label": "Running heads and eyebrow labels, 12px uppercase.",
 "stat": "Statistic numerals on navy panels (40→64px).",
}
fluid = [{"name": k, "value": v, "usage": STEP_USE[k[5:]]} for k, v in VARS.items() if k.startswith("step-")]
LAYOUT_USE = {
 "section-y": "Vertical padding of every section (64→136px).", "gutter": "Grid gutter (16→32px).", "page-margin": "Page side margin (16→80px).",
 "content-max": "Maximum content width (1312px).", "measure": "Body copy line length.", "measure-narrow": "Lead and intro line length.",
 "hairline": "Hairline width.", "rule": "Gold rule width.", "frame-inset": "Inset of the folio frame from the edge (12→28px).",
 "frame-gap": "Gap between the two lines of the double folio frame.", "tick": "End-tick height of the dimension rule.",
 "focus-width": "Focus outline width.", "focus-offset": "Focus outline offset.", "grid-columns": "Columns in the page grid.",
}
layout = [{"name": k, "value": VARS[k], "usage": LAYOUT_USE[k]} for k in LAYOUT_USE]
SPACE_USE = ["Icon-to-label gap.", "Inside controls; tight stacks.", "Between related rows.", "Default stack gap; card padding on phones.",
             "Card padding; gaps between fields.", "Between groups inside a section.", "Between blocks in a section.",
             "Above section heads.", "Large editorial jumps.", "Cover-scale breathing room."]
tokens = {
 "version": 1,
 "meta": {"source": "github", "repo": "CptNope/The-Oberdorfer-Team-Concept-3", "ref": "main@87e58c4", "file": "assets/css/tokens.css"},
 "color": {
   "themes": [{"id": "light", "name": "Ivory"}, {"id": "navy", "name": "Navy (surface-inverse)"}, {"id": "sandstone", "name": "Sandstone (surface-muted)"}],
   "note": "Sampled from the print publication Wakefield at Home, Fall 2026. Ivory is the page; navy is the surface-inverse remap used for cover moments; sandstone is the surface-muted remap. Components read the semantic tokens only.",
   "tokens": colors},
 "type": {
   "fonts": [],
   "families": {"display": VARS["font-display"], "sans": VARS["font-sans"]},
   "groups": [
     {"name": "Display", "family": "display", "note": "Cormorant Garamond, verified from the PDF's embedded fonts. Light 300 from 30px up; italic for the answering line and captions. Sizes are the 1440px end of each fluid step.",
      "styles": [
        {"name": "type-display-xl", "fontSize": "96px", "lineHeight": 1.02, "fontWeight": 300, "letterSpacing": "-0.02em", "sample": "Luxury at every price point.", "usage": "Class `.display-xl`, step-display-xl. The cover headline, once per page."},
        {"name": "type-display-l", "fontSize": "76px", "lineHeight": 1.04, "fontWeight": 300, "letterSpacing": "-0.016em", "sample": "Husband. Wife. Your Team.", "usage": "Class `.display-l`, step-display-l."},
        {"name": "type-display-m", "fontSize": "56px", "lineHeight": 1.06, "fontWeight": 300, "letterSpacing": "-0.012em", "sample": "Close to Boston. Closer to what matters.", "usage": "Class `.display-m`, step-display-m. Chapter and section headlines."},
        {"name": "type-h1", "fontSize": "52px", "lineHeight": 1.08, "fontWeight": 300, "letterSpacing": "-0.012em", "sample": "Know the market. Love the home.", "usage": "`h1` / `.h1`, step-h1. Inner page titles."},
        {"name": "type-h2", "fontSize": "44px", "lineHeight": 1.1, "fontWeight": 300, "letterSpacing": "-0.012em", "sample": "A personal standard of service.", "usage": "`h2` / `.h2`, step-h2."},
        {"name": "type-h3", "fontSize": "34px", "lineHeight": 1.12, "fontWeight": 400, "letterSpacing": "-0.012em", "sample": "The people behind the promise.", "usage": "`h3` / `.h3`, step-h3."},
        {"name": "type-h4", "fontSize": "26px", "lineHeight": 1.35, "fontWeight": 400, "letterSpacing": "-0.012em", "sample": "Lake Quannapowitt", "usage": "`h4` / `.h4`, step-h4. Card titles."},
        {"name": "type-h5", "fontSize": "20px", "lineHeight": 1.35, "fontWeight": 500, "letterSpacing": "0", "sample": "Personal", "usage": "`h5` / `.h5`, step-h5."},
        {"name": "type-stat", "fontSize": "64px", "lineHeight": 1, "fontWeight": 300, "letterSpacing": "-0.01em", "sample": "$786,979", "usage": "`.stat__value`, step-stat. Ivory on navy, tabular lining numerals."},
      ]},
     {"name": "Text", "family": "sans", "note": "Manrope. Body 400, navigation 500, labels and buttons 600 uppercase with 0.14–0.18em tracking.",
      "styles": [
        {"name": "type-lead", "fontSize": "21px", "lineHeight": 1.6, "fontWeight": 300, "sample": "Thoughtful service. Creative marketing. A personal connection to every move.", "usage": "`.lead`, step-lead, on measure-narrow."},
        {"name": "type-body-l", "fontSize": "18px", "lineHeight": 1.65, "fontWeight": 400, "sample": "For the home you have. For the life you are building.", "usage": "`.body-l`."},
        {"name": "type-body", "fontSize": "16px", "lineHeight": 1.65, "fontWeight": 400, "sample": "A useful snapshot helps you ask better questions.", "usage": "Default body copy on a 68ch measure."},
        {"name": "type-body-s", "fontSize": "14px", "lineHeight": 1.6, "fontWeight": 400, "sample": "Two partners invested in your goals.", "usage": "`.body-s`."},
        {"name": "type-caption", "fontSize": "13px", "lineHeight": 1.5, "fontWeight": 400, "sample": "Source: Redfin, Wakefield, MA. Accessed Oct 10, 2026.", "usage": "`.caption` in text-muted. Credits, sources, metadata."},
        {"name": "type-h6", "fontSize": "13px", "lineHeight": 1.35, "fontWeight": 600, "letterSpacing": "0.06em", "sample": "Personal · Purposeful · Prepared", "usage": "`h6` / `.h6`, the sans small heading."},
        {"name": "type-label", "fontSize": "12px", "lineHeight": 1.4, "fontWeight": 600, "letterSpacing": "0.18em", "sample": "WAKEFIELD AT HOME", "usage": "`.label`, uppercase, in text-accent. Running heads and eyebrows."},
        {"name": "type-button", "fontSize": "12.5px", "lineHeight": 1, "fontWeight": 600, "letterSpacing": "0.14em", "sample": "REQUEST A CONSULTATION", "usage": "`.btn` labels, uppercase."},
      ]},
   ]},
 "spacing": {"note": "4px base with editorial jumps.", "tokens": [{"name": f"space-{i}", "value": VARS[f"space-{i}"], "usage": SPACE_USE[i - 1]} for i in range(1, 11)]},
 "radius": {"note": "Square corners throughout, as in print.", "tokens": [{"name": "radius", "value": "0", "usage": "Every component. Never round a corner."}]},
 "shadow": {"note": "Print has no shadows; the screen uses them only where something floats.",
            "tokens": [{"name": "shadow-1", "value": rgb_to_hexa(VARS["shadow-1"]), "usage": "Sticky header after scroll, raised paper mockups."},
                       {"name": "shadow-2", "value": rgb_to_hexa(VARS["shadow-2"]), "usage": "Dialog, toast and the mobile menu."}]},
 "fluidType": {"note": "Fluid clamp() steps from 360px to 1440px viewports. The type styles show the 1440px end.", "tokens": fluid},
 "lineHeight": {"tokens": lengths("leading-", lambda k, v: {"leading-display": "Display headlines.", "leading-heading": "Headings.", "leading-body": "Body copy.", "leading-tight": "Small headings, card meta."}[k])},
 "letterSpacing": {"tokens": lengths("tracking-", lambda k, v: {"tracking-display": "Serif headlines.", "tracking-label": "Uppercase labels and running heads.", "tracking-button": "Button labels."}[k])},
 "fontWeight": {"tokens": lengths("weight-", lambda k, v: {"weight-display": "Every headline 30px and up: Light, as in the publication.", "weight-heading": "Smaller serif headings.", "weight-heading-strong": "h5 and serif emphasis.", "weight-body": "Body copy.", "weight-label": "Labels and buttons."}[k])},
 "layout": {"note": "12-column grid; editorial splits are 6 + 5 with an offset.", "tokens": layout},
 "zIndex": {"tokens": lengths("z-", lambda k, v: {"z-nav": "Sticky header.", "z-overlay": "Mobile menu and contents panel.", "z-modal": "Dialogs.", "z-toast": "Toasts, above everything."}[k])},
 "breakpoint": {"note": "Reference values; media queries use them literally.", "tokens": lengths("bp-", lambda k, v: {"bp-sm": "576px: cards go one to two up.", "bp-md": "832px: 12-column splits collapse to one column.", "bp-lg": "1024px: desktop navigation appears.", "bp-xl": "1440px: design reference width."}[k])},
 "duration": {"note": "One gesture: gold rules draw left to right, content settles beneath. Reduced motion removes it.", "tokens": lengths("dur-", lambda k, v: {"dur-fast": "Hover colour changes.", "dur-base": "Button underline draw.", "dur-slow": "Menus, dialogs, toasts, reveal.", "dur-reveal": "Section rule draw."}[k])},
 "easing": {"tokens": lengths("ease-", lambda k, v: {"ease-out": "The exponential settle used for every entrance.", "ease-in-out": "Panels that open and close."}[k])},
}
w("tokens.json", json.dumps(tokens, indent=2, ensure_ascii=False))

# contrast report for text tokens in each theme
def val(name, theme):
    t = next(c for c in colors if c["name"] == name); v = t["value"]
    v = v.get(theme, v["light"]) if isinstance(v, dict) else v
    while v.startswith("{"): v = val(v[1:-1], theme)
    return v
for th in ("light", "navy", "sandstone"):
    g = val("surface-page", th)
    print(th, {n: round(ratio(val(n, th), g), 2) for n in ("text-display", "text-body", "text-muted", "text-accent", "text-link")})

# ------------------------------------------------------------------ compile tokens.css (local check only)
def compile_css():
    out = ["/* local check */"]
    def cv(v): return re.sub(r"^\{(.+)\}$", r"var(--\1)", v)
    for i, th in enumerate(tokens["color"]["themes"]):
        sel = ':root, [data-theme="light"]' if i == 0 else f'[data-theme="{th["id"]}"]'
        lines = []
        for c in colors:
            v = c["value"]
            if isinstance(v, dict):
                if th["id"] in v: lines.append(f"--{c['name']}: {cv(v[th['id']])};")
            elif i == 0: lines.append(f"--{c['name']}: {cv(v)};")
        if i == 0:
            lines += [f"--{s['name']}: {s['value']};" for s in tokens["shadow"]["tokens"]]
        out.append(sel + " {" + " ".join(lines) + "}")
    lines = []
    for fam, d in tokens.items():
        if fam in ("version", "meta", "color", "type", "shadow"): continue
        lines += [f"--{t['name']}: {t['value']};" for t in d["tokens"]]
    lines += [f"--font-{k}: {v};" for k, v in tokens["type"]["families"].items()]
    out.append(":root {" + " ".join(lines) + "}")
    return "\n".join(out)
os.makedirs(f"{OUT}/check", exist_ok=True)
open(f"{OUT}/check/tokens.css", "w").write(compile_css())

# ------------------------------------------------------------------ bundle.css
ds_html = open(f"{REPO}/design-system/index.html").read()
inline_style = re.search(r"<style>(.*?)</style>", ds_html, re.S).group(1)
rest_tokens = CSS[CSS.index("\n}\n", CSS.index(":root {")) + 3:]
def blobify_css(s):
    return s.replace('url("../img/photo/breakheart-reservation.webp")', f'url("/_blob/{BLOBS["Photography/breakheart-reservation.webp"][0]}")') \
            .replace('url("../assets/img/photo/lake-quannapowitt.webp")', f'url("/_blob/{BLOBS["Photography/lake-quannapowitt.webp"][0]}")')
parts = [
 "@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,300;1,400&family=Manrope:wght@300;400;500;600;700&display=swap');",
 "/* The Oberdorfer Team: component styles from CptNope/The-Oberdorfer-Team-Concept-3 (main@87e58c4).\n   Token values live in tokens.json; this sheet keeps only what the page does not declare:\n   the navy and sandstone section remaps, then base, components, page layouts and documentation helpers. */",
 "/* ---- tokens.css: section remaps ---- */\n" + rest_tokens.strip(),
 "/* ---- base.css ---- */\n" + open(f"{REPO}/assets/css/base.css").read().split("*/", 1)[1].strip(),
 "/* ---- components.css ---- */\n" + open(f"{REPO}/assets/css/components.css").read().split("*/", 1)[1].strip(),
 "/* ---- site.css ---- */\n" + open(f"{REPO}/assets/css/site.css").read().split("*/", 1)[1].strip(),
 "/* ---- docs.css ---- */\n" + open(f"{REPO}/assets/css/docs.css").read().split("*/", 1)[1].strip(),
 "/* ---- design-system page helpers ---- */\n" + inline_style.strip(),
 "/* ---- preview frame ---- */\n.reveal, .reveal-rule { opacity: 1 !important; transform: none !important; }\nbody.pv-body { padding: var(--space-5); }\nbody.pv-bleed { padding: 0; }\n.pv > .block__body { margin: 0; }\n.pv-stack { display: grid; gap: var(--space-6); }\n.pv-sub { margin-bottom: var(--space-3); }",
]
bundle = blobify_css("\n\n".join(parts) + "\n")
assert "</style" not in bundle.lower()
w("components/bundle.css", bundle)

# ------------------------------------------------------------------ sprite
def symbols(path):
    s = open(path).read()
    return dict(re.findall(r'(<symbol id="([^"]+)".*?</symbol>)', s, re.S)[i][::-1] for i in range(len(re.findall(r'<symbol id="', s))))
SYM = {}
SYM.update(symbols(f"{REPO}/assets/img/icons.svg"))
SYM.update(symbols(f"{REPO}/assets/img/illustrations.svg"))

ASSET_MAP = {
 "../assets/img/photo/": "Photography/", "../assets/img/rewap-logo.webp": "Brokerage/rewap-logo.webp",
 "../assets/img/source-pages/": "Publication/", "../assets/logo/": "Logos/", "../assets/publications/": "Publication/",
}
def rewrite(h):
    h = re.sub(r'href="\.\./assets/img/(?:icons|illustrations)\.svg#([^"]+)"', r'href="#\1"', h)
    def rep(m):
        attr, path = m.group(1), m.group(2)
        for pre, grp in ASSET_MAP.items():
            if path.startswith(pre):
                key = grp + path[len(pre):] if pre.endswith("/") else grp
                if key in BLOBS: return f'{attr}="/_blob/{BLOBS[key][0]}"'
        if path.startswith("../"): return f'{attr}="#"'
        return m.group(0)
    h = re.sub(r'(src|href)="(\.\./[^"]*)"', rep, h)
    h = re.sub(r'(srcset)="[^"]*"', "", h)
    return blobify_css(h)

# ------------------------------------------------------------------ pieces from the design-system page
soup = BeautifulSoup(ds_html, "html.parser")
PIECES = {}
for sec in soup.select("section.chapter"):
    cid = sec["id"]
    els = []
    for c in sec.find_all(recursive=False):
        if c.name == "div" and "container" in c.get("class", []):
            els += c.find_all(recursive=False)
        else:
            els.append(c)
    for c in els:
        cl = c.get("class", [])
        if "chapter__head" in cl or "folio-bar" in cl: continue
        h = c.select_one(":scope > .block__label h3")
        if h:
            body = c.select_one(":scope > .block__body")
            PIECES[(cid, h.get_text(strip=True))] = body
        else:
            PIECES.setdefault((cid, cl[0] if cl else c.name), c)

def piece(cid, key):
    return PIECES[(cid, key)]

# ------------------------------------------------------------------ cards
# (name, group, [(chapter, key)], bleed, readme)
C = []
def card(name, group, keys, readme, bleed=False, subtitle=None):
    C.append(dict(name=name, group=group, keys=keys, readme=readme.strip(), bleed=bleed, subtitle=subtitle))

card("Button", "Actions", [("buttons", "On ivory"), ("buttons", "On navy")], """
Square, tracked, uppercase action with a gold rule that draws under the label on hover. Block `ot/button`.

- `.btn` is the primary: navy on ivory, ivory on navy. Use one primary per view, for the thing the page is for.
- `.btn--secondary` is the outlined companion; `.btn--gold` is reserved for the phone call on navy (`Call 406-250-7620`).
- `.btn--small` for dense rows (listing cards, showing panels). `.link-arrow` for "read more" style links, with the `arrow` icon.
- States: hover draws the underline (`rule-gold`, `dur-base`, `ease-out`); focus shows the 2px `focus-ring` at `focus-offset`; loading sets `aria-busy="true"`; disabled uses `disabled-bg` / `disabled-fg`.
- Labels are verbs that name the result: "Request a consultation", "Schedule a showing", "Get a market review", "Send my question". Never "Submit", "Click here", "Learn more" or "Unlock your dream home".
- Corners stay square (`radius` 0).
""")
card("Lines", "Brand", [("space", "Lines")], """
The publication's linework: the gold rule, the hairline, the dimension rule and the folio frame.

- `.rule` is a 1px `rule-gold` line; `.rule--hair` uses `rule-hair` for quiet dividers.
- `.dim-rule` is the architectural dimension line with end ticks (`tick`), from the p7 house drawing. Use it to close a promise band or a statement.
- `.folio-frame` is the inset double gold frame of the cover (`frame-inset`, `frame-gap`). Use one per view, on cover moments only.
- Depth comes from rules and grounds, never from shadows or rounded corners.
""")
card("Icons", "Brand", [("icons", "icon-grid")], """
The icon set: 24 single-weight line icons drawn at 1.25px on a 24px grid, used from one SVG sprite (`assets/img/icons.svg`, ids `i-*`).

- Inline as `<svg class="icon" aria-hidden="true"><use href="…icons.svg#i-search"/></svg>`; icons inherit `currentColor`.
- Always pair an icon with words, except the icon buttons (menu, close, save) which carry an `aria-label`.
- The arrow is wide (34×12) and only follows a text link. The `eho` mark is the Equal Housing Opportunity logo and appears in the footer.
- Standalone SVG copies are in the Icons asset group, drawn in `ot-navy`.
""")
card("Illustrations", "Brand", [("icons", "Illustrations")], """
Line drawings of Massachusetts house types (colonial, triple-decker, Cape, townscape) in the publication's line.

- Use them as labeled placeholders for demo listings and community cards until licensed photography arrives.
- Draw them in `ot-gold` on `ot-navy`, or `ot-navy` on `ot-sandstone`. Never fill them or add colour.
- They are not photographs of real homes: never caption them as a property.
""")
card("SiteHeader", "Navigation", [("navigation", "block")], """
The site header in three variants, plus the mobile menu. Block `ot/site-header`.

- `.site-header` (navy) is the default; `.site-header--overlay` sits on a hero photo over a navy scrim and turns solid on scroll; `.site-header--light` is for ivory pages.
- The logo links home; navigation is Buy, Sell, Communities, Team, Journal, Join, Contact; the phone and a primary button sit at the right.
- Below `bp-lg` the menu button opens `.mobile-menu`, a full-screen navy panel that wipes in from the top (`dur-slow`, `ease-out`), traps focus and closes on Escape.
- The current page carries `aria-current="page"` and a gold underline.
""")
card("Breadcrumbs", "Navigation", [("editorial", "Breadcrumbs")], """
Trail at the top of every inner page. Block `ot/breadcrumbs`.

- `nav.breadcrumbs` with an ordered list; the current page is plain text with `aria-current="page"`.
- Output BreadcrumbList structured data alongside.
""")
card("Pagination", "Navigation", [("search", "Pagination")], """
For journal archives and long result sets. Block `ot/pagination`.

- Previous and Next are text; page numbers are set in the display serif; the current page carries a gold rule and `aria-current="page"`.
- Hide the steps that cannot be taken rather than disabling them.
""")
card("Tabs", "Navigation", [("forms", "Tabs")], """
One contact page, four inquiry types (buyer, seller, general, joining the team). Block `ot/tabs`.

- ARIA tabs: arrow keys move between tabs, the selected tab carries a gold rule. `#seller` in the URL opens that tab.
- Use only for parallel forms or views; never to hide primary content.
""")
card("CoverHero", "Headers", [("hero", "hero-cover"), ("hero", "caption")], """
The home page cover: a navy field inside the folio frame, the two-line answering headline and the primary actions. Block `ot/hero-cover`.

- Headline in `.display-xl` with the `.answer` construction: two short lines, the second answering the first ("Luxury at / every price point.").
- One folio frame per view. The photograph sits beside the type, never under it without a navy scrim (`surface-overlay`).
- Use once per site, on the home page.
""")
card("PageHero", "Headers", [("hero", "Inner page hero")], """
The quieter header for every other page: breadcrumbs, a two-line headline, a lead and an optional action. Block `ot/page-hero`.

- Variants: ivory (default), navy (`.surface-inverse`) and photo.
- Headline in `h1` with `.answer`; lead in `.lead` on `measure-narrow`.
""")
card("SectionHead", "Headers", [("hero", "Section head")], """
Opens every section inside a page: running head, two-line title, optional lead and an aside link. Block `ot/section-head`.

- The running head is a `.label` in `text-accent` over a gold rule.
- The aside link is a `.link-arrow`.
""")
card("PropertySearch", "Search", [("search", "search-bar"), ("search", "filters"), ("search", "form-note")], """
The property search bar and quick filters. Block `ot/property-search`.

- Fields: location, price, beds, type, then the search button. Every field has a visible label.
- Quick filters are toggle chips under the bar.
- Results come only from an authorized IDX/MLS feed; the prototype searches labeled demo data and says so in the note under the bar.
""")
card("ResultsBar", "Search", [("search", "Results bar")], """
Live result count (announced politely), sort and the saved count. Block `ot/results`.

- The count lives in an `aria-live="polite"` region.
- The saved count is per browser in the prototype; say so.
""")
card("ChipFilter", "Search", [("search", "Chip filter")], """
Single-choice category filter for the journal. Block `ot/chip-filter`.

- Buttons with `aria-pressed`; the pressed chip fills navy.
""")
card("LoadingEmpty", "Search", [("search", "Loading and empty")], """
Skeleton loading and the empty result. Block `ot/results`.

- The skeleton is a quiet shimmer that stays still under reduced motion.
- An empty result always offers a way out: widen the search or talk to Brandon and Kaitlyn.
""")
card("ListingCard", "Cards", [("cards", "Listing card")], """
A property in a grid of results. Block `ot/listing-card`.

- Image 4:3, price in the display serif, address, then beds, baths and area with icons, and a save button (`aria-pressed`).
- Every demo listing carries the `.badge--demo` badge and obviously placeholder details. An invented listing is never presented as a real property.
- Live cards add the MLS courtesy line, listing brokerage and data date.
""")
card("AgentCard", "Cards", [("cards", "Agent card"), ("cards", "Open seat")], """
People on the team. Block `ot/agent-card`.

- Brandon and Kaitlyn lead as one card, the way the publication presents them: "Husband. Wife. Your Team."
- Future agents use the same card. Fields for unverified details (license numbers, email, titles) stay empty, never invented.
- `.seat` is the honest empty state for the agent directory: an open seat that links to Join, not a stock photo.
""")
card("AgentProfile", "Cards", [("cards", "Agent profile")], """
One page per person at `/agents/agent-name/`, built for search, answer engines and conversion. Block `ot/agent-profile`.

- Fields: name, headshot (4:5), role, title, verified MA license, biography (120 to 250 words, first person), specialties, service areas, languages, direct phone, email, socials, partner. Anything unconfirmed renders as a `[placeholder]` with a Verify badge; never write copy for an agent.
- Above the fold: an answer-first summary sentence, then two actions (message, call) and a quick-facts list. A visible "profile updated" date.
- On phones a fixed `.action-bar` keeps Call and Message on screen.
- Below: biography, services linking into Buy and Sell, client stories (empty state until permitted reviews exist), listings the agent may advertise, recent writing, an FAQ, and a form whose lead type names the agent (`agent:<slug>`).
- JSON-LD: `ProfilePage` with a `Person` linked by `@id` to the team and brokerage, plus `FAQPage` and `BreadcrumbList`. No review or rating markup until real reviews exist.
- Future agents start from the template page, which stays unpublished until its checklist (license, headshot, biography, approval) is complete.
""")
card("CommunityCard", "Cards", [("cards", "Community card")], """
Portrait 3:4 card for the Massachusetts community hub. Block `ot/community-card`.

- Real local photography with the source credited; the line illustration until it arrives.
- Meta line: county and 2025 single-family median. The hub groups cards by region.
- The final community list is not confirmed; the hub carries a `.badge--proposed`.
""")
card("CommunityGuide", "Cards", [("cards", "Community guide")], """
One page per town at `/communities/town/`, built from a researched record in `tools/data/towns/`. Block `ot/community-guide`.

- A search-facing h1 ("Living in Worcester, MA") as the hero kicker, the brand-voice headline as display text, and a 40 to 60 word answer as the lead.
- Overview beside `.quick-facts--aside`: county, region, 2020 Census population, rail, highways, the school district's DESE link and the 2025 single-family median.
- Neighborhoods, commute and schools, places and history, a `.market-table` with its source, period and geography, an FAQ, a town form (`community:<slug>`) and a `.source-list` naming every source.
- Fair housing: describe places, housing and transit; never who lives there; never rate schools.
- JSON-LD: `WebPage` about a `City` contained in its county and the state, plus `BreadcrumbList`. No FAQPage markup (Google retired FAQ rich results in May 2026).
""")
card("ArticleCard", "Cards", [("cards", "Article card")], """
A journal story. Block `ot/article-card`.

- Image, category label, headline in the display serif, date and reading time.
- Titles come from the editorial voice: "The best plans are close to home".
""")
card("PropertyGallery", "Listing", [("cards", "Property gallery")], """
One lead image and two supporting views on the listing page. Block `ot/listing-detail`.

- The last tile opens the full gallery and shows the photo count.
- Every image has alt text describing the room or view.
""")
card("ListingFacts", "Listing", [("listing", "Key facts"), ("listing", "Specification table")], """
Key facts and the specification table on the listing page. Block `ot/listing-detail`.

- `.facts`: four numbers under a gold rule; 2 × 2 on phones.
- `.spec-table`: MLS fields, rendered from the feed, never retyped by hand.
""")
card("AgentMini", "Listing", [("listing", "Agent mini and showing panel")], """
The sticky aside on the listing page: request a showing, then the people behind it. Block `ot/listing-detail`.
""")
card("ListingAttribution", "Listing", [("listing", "Attribution")], """
Every live listing names its source: MLS courtesy line, listing brokerage and data date. The placeholders show where they go until the IDX provider is chosen.
""")
card("MarketStats", "Data", [("market", "split")], """
The navy statistics panel from page 4 of the publication. Block `ot/market-stats`.

- Numerals in `.stat__value` (display serif, tabular lining figures, ivory on navy); labels in gold.
- Always show source, period and geography: "Redfin, Wakefield, MA, accessed Oct 10, 2026". Never present the Wakefield figures as live or statewide.
- Confirmed figures: median sale price $786,979 (3 months ending Aug 2026); 21 median days on market; $483 per sq ft; 103.3% sale-to-list.
""")
card("FormField", "Forms", [("forms", "Field states")], """
Text fields, selects, choices and checkboxes with their states. Block `ot/inquiry-form`.

- Every field has a visible label; optional fields say "Optional".
- States: rest (`input-border`), hover (`input-border-hover`), focus (`input-border-focus` + `focus-ring`), invalid (`error` border and an icon-led message), disabled.
- Error messages say what to do: "Enter an email address like name@example.com."
""")
card("InquiryForm", "Forms", [("forms", "Working example")], """
A complete inquiry form: the seller consultation. Block `ot/inquiry-form`.

- Real constraint validation with branded messages; the prototype transmits nothing and says so.
- Lead routing stays configurable: the CRM is not selected.
""")
card("FormStatus", "Forms", [("forms", "Submission states")], """
The message under the submit button after sending. `.form-status` receives focus so screen readers hear it.

- Success and error each carry an icon, a bold first line and a next step.
""")
card("Alert", "Feedback", [("feedback", "Alerts")], """
Section-level messages: info, success, warning and error, all dismissible. Block `ot/alert`.

- Status colours stay muted heritage tones (`success`, `warning`, `error`, `info` on their `-bg`) and always pair with an icon and words.
""")
card("Toast", "Feedback", [("feedback", "Toast")], """
A brief confirmation after an action ("Saved to your list"), announced politely and gone after five seconds.

- Navy, gold top rule, `shadow-2`, `z-toast`. One at a time.
""")
card("Dialog", "Feedback", [("feedback", "Dialog")], """
Native `<dialog>` for tasks that need protected focus. Block `ot/dialog`.

- Focus is trapped, Escape closes it, focus returns to the trigger. Backdrop is `surface-overlay`.
- Never for marketing pop-ups.
""")
card("PrototypeNotice", "Feedback", [("feedback", "Prototype notice")], """
The ribbon on every example-site page that says the site is a prototype with demo content. Removed at launch.
""")
card("Badge", "Feedback", [], """
Content-status badges: `.badge--demo`, `.badge--verify`, `.badge--proposed` and `.badge--source`.

- Anything unconfirmed carries one: demo listings, proposed communities, details awaiting verification.
- Square, uppercase `type-label` style.
""")
card("Triad", "Editorial", [("editorial", "Principles triad")], """
Three columns under one gold rule each, from page 2 of the publication. Block `ot/triad`.

- The source principles: **Personal** (Two partners invested in your goals.), **Purposeful** (A plan shaped around your home and life.), **Prepared** (Clear guidance and attention to the details.).
""")
card("Steps", "Editorial", [("editorial", "Numbered steps")], """
Numbered steps from page 7. Block `ot/steps`. Use numbers only when the order matters.
""")
card("PullQuote", "Editorial", [("editorial", "Pull quote and testimonial")], """
Pull quote and testimonial. Block `ot/quote`.

- Testimonials appear only with the client's written permission. Until then the block shows its empty state; never write one.
""")
card("Faq", "Editorial", [("editorial", "FAQ accordion")], """
Native `<details>` accordion with FAQPage structured data. Block `ot/faq`.
""")
card("Pathways", "Editorial", [("editorial", "Buy and sell pathways")], """
Two doors, navy and sandstone, each with a line drawing behind. Block `ot/pathways`.
""")
card("PromiseBand", "Editorial", [("editorial", "Promise band")], """
A centred statement between denser sections, closed with a dimension rule. Block `ot/promise`.

- Use the source line: "Luxury at every price point."
""")
card("FeatureArticle", "Editorial", [("editorial", "Feature article and archive card")], """
The journal's lead story, and a past issue offered as a download (the *Wakefield at Home* PDF). Block `ot/publication`.
""")
card("GuideList", "Editorial", [("editorial", "Field guide list")], """
`.guide-list`: large serif numerals for a guide the reader works through.
""")
card("HistoryBand", "Editorial", [("editorial", "History band")], """
`.history`: dates as display numerals on sandstone, from page 3. Block `ot/history`.
""")
card("Letter", "Editorial", [("editorial", "Letter and signature")], """
The leadership letter: a gold drop cap and an italic signature. Block `ot/letter`.
""")
card("OnThisPage", "Editorial", [("editorial", "On-this-page list and values list")], """
Quiet navigation inside long pages, and a list of single words set large (the team's values).
""")
card("CtaPanel", "Conversion", [("conversion", "cols")], """
The closing CTA panel and the office card. Blocks `ot/cta-panel` and `ot/office`.

- CTA panel: navy inside a folio frame, the source line "For the home you have. For the life you are building.", one primary action and the phone.
- Office card: the office address, hours and status are unconfirmed and stay as `[bracketed placeholders]` with `.badge--verify`.
""")
card("MapPlate", "Conversion", [("conversion", "Map")], """
A static, styled map plate instead of a third-party embed: faster, private and on brand. An interactive map loads only when the visitor asks for it.
""")
card("ContactLines", "Conversion", [("conversion", "Contact lines")], """
`.contact-line`: icon, label, detail. The phone 406-250-7620 is confirmed; email and address stay as placeholders until confirmed.
""")
card("SiteFooter", "Footer", [("footer", "site-footer")], """
The footer with brokerage attribution and the Equal Housing Opportunity notice. Block `ot/site-footer`.

- The team and REWAP Brokerage are separate brands. The REWAP logo sits only on its own ivory plate, never redrawn or recoloured; otherwise use the typographic "Powered by REWAP Brokerage LLC" lockup.
- Never imply the team is an independently licensed brokerage. License numbers and disclosures stay as placeholders until confirmed.
""", bleed=True)

# Badge card has no piece of its own: build from markup
BADGE_HTML = '<div class="cluster"><span class="badge badge--demo">Demo</span><span class="badge badge--verify">Verify</span><span class="badge badge--proposed">Proposed</span><span class="badge badge--source">Source</span></div>'

def sprite_for(h):
    ids = sorted(set(re.findall(r'href="#([a-z0-9-]+)"', h)) & set(SYM))
    if not ids: return ""
    return '<svg xmlns="http://www.w3.org/2000/svg" style="display:none" aria-hidden="true">' + "".join(SYM[i] for i in ids) + "</svg>\n"

made = []
for c in C:
    if c["name"] == "Badge":
        inner = BADGE_HTML
    else:
        bits = []
        for k in c["keys"]:
            el = piece(*k)
            bits.append(str(el))
        inner = "\n".join(bits)
        if len(bits) > 1: inner = '<div class="pv-stack">' + "\n".join(f"<div>{b}</div>" for b in bits) + "</div>"
    if c["name"] == "Toast":
        inner += '\n<div class="toast" role="status" style="margin-top:var(--space-5);max-width:28rem;animation:none"><svg class="icon" aria-hidden="true"><use href="../assets/img/icons.svg#i-check"/></svg><span>Search saved (demonstration).</span><button type="button">Dismiss</button></div>'
    if c["name"] == "Dialog":
        inner = inner.replace('class="dialog" id="ds-dialog"', 'class="dialog" id="ds-dialog" open style="position:static;margin:var(--space-5) 0 0;animation:none"')
    inner = rewrite(inner)
    body_cls = "pv-bleed" if c["bleed"] else "pv-body"
    doc = (f'<!-- @dsCard group="{c["group"]}" height=HEIGHT -->\n<!doctype html>\n<html lang="en">\n<head><meta charset="utf-8"><title>{c["name"]}</title></head>\n'
           f'<body class="{body_cls}">\n{sprite_for(inner)}<div class="pv">\n{inner}\n</div>\n</body>\n</html>\n')
    c["doc"] = doc
    w(f"components/{c['name']}/README.md", f"# {c['name']}\n\n{c['readme']}\n")
    made.append(c)
json.dump([{"name": c["name"], "group": c["group"], "bleed": c["bleed"]} for c in made], open(f"{OUT}/check/cards.json", "w"))
for c in made:
    open(f"{OUT}/check/{c['name']}.html", "w").write(c["doc"])
print("cards", len(made))
