#!/usr/bin/env python3
"""Export assets/css/tokens.css to portable token files.

Usage (from the repo root):  python3 tools/export_tokens.py

Writes:
  assets/tokens/tokens.json  W3C Design Tokens Community Group format, for
                             Figma (Tokens Studio), Style Dictionary and other
                             design tools.
  assets/tokens/theme.json   WordPress theme.json (version 3) presets: palette,
                             font families, fluid font sizes, spacing sizes and
                             layout. Copy it into the custom theme's root.

tokens.css stays the single source of truth: change a value there, then run
this script.
"""
import json, os, re

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
CSS = open(os.path.join(ROOT, "assets/css/tokens.css")).read()
OUT = os.path.join(ROOT, "assets/tokens")
os.makedirs(OUT, exist_ok=True)

root_block = CSS[CSS.index(":root {"):CSS.index("\n}\n", CSS.index(":root {"))]
VARS = {}
for name, value in re.findall(r"--([a-z0-9-]+):\s*([^;]+);", root_block):
    VARS[name] = re.sub(r"/\*.*?\*/", "", value).strip()

COMMENTS = {}
for name, note in re.findall(r"--([a-z0-9-]+):[^;]+;\s*/\*\s*(.*?)\s*\*/", root_block):
    COMMENTS[name] = note

def resolve(v):
    m = re.fullmatch(r"var\(--([a-z0-9-]+)\)", v)
    return resolve(VARS[m.group(1)]) if m else v

def ref(v):
    """Turn var(--ot-navy) into a DTCG alias {color.navy}."""
    m = re.fullmatch(r"var\(--ot-([a-z0-9-]+)\)", v)
    return "{color.%s}" % m.group(1) if m else resolve(v)

def clamp_parts(v):
    m = re.fullmatch(r"clamp\(([^,]+),\s*([^,]+),\s*([^)]+)\)", v)
    return (m.group(1).strip(), m.group(3).strip()) if m else (v, v)

# ------------------------------------------------------------------ DTCG tokens
color = {k[3:]: {"$type": "color", "$value": v.upper(), **({"$description": COMMENTS[k]} if k in COMMENTS else {})}
         for k, v in VARS.items() if k.startswith("ot-")}
semantic = {}
for prefix in ("surface", "text", "rule", "border", "focus", "hover", "press", "disabled", "input"):
    for k, v in VARS.items():
        if k.startswith(prefix + "-") or k == prefix:
            val = ref(v)
            if val.startswith("{") or val.startswith("#") or val.startswith("rgb") or val.startswith("color-mix"):
                semantic.setdefault(prefix, {})[k[len(prefix) + 1:] or "default"] = {"$type": "color", "$value": val}
status = {}
for s in ("success", "warning", "error", "info"):
    status[s] = {"$type": "color", "$value": ref(VARS[s])}
    status[s + "-bg"] = {"$type": "color", "$value": VARS[s + "-bg"]}

def dim(v):
    lo, hi = clamp_parts(v)
    t = {"$type": "dimension", "$value": hi}
    if lo != hi:
        t["$extensions"] = {"com.oberdorfer.fluid": {"min": lo, "max": hi, "css": v, "viewport": "360px → 1440px"}}
    return t

font_size = {k[5:]: dim(v) for k, v in VARS.items() if k.startswith("step-")}
space = {k[6:]: {"$type": "dimension", "$value": v} for k, v in VARS.items() if re.fullmatch(r"space-\d+", k)}
layout = {k: dim(VARS[k]) for k in ("section-y", "gutter", "page-margin", "content-max", "frame-inset", "frame-gap", "tick")}
tokens = {
    "$description": "The Oberdorfer Team design tokens, exported from assets/css/tokens.css. Proposed, awaiting approval.",
    "color": color,
    "semantic": semantic,
    "status": status,
    "font": {
        "family": {"display": {"$type": "fontFamily", "$value": [f.strip().strip('"') for f in VARS["font-display"].split(",")]},
                   "sans": {"$type": "fontFamily", "$value": [f.strip().strip('"') for f in VARS["font-sans"].split(",")]}},
        "weight": {k[7:]: {"$type": "fontWeight", "$value": int(v)} for k, v in VARS.items() if k.startswith("weight-")},
        "size": font_size,
        "line-height": {k[8:]: {"$type": "number", "$value": float(v)} for k, v in VARS.items() if k.startswith("leading-")},
        "letter-spacing": {k[9:]: {"$type": "dimension", "$value": v} for k, v in VARS.items() if k.startswith("tracking-")},
    },
    "space": space,
    "layout": layout,
    "radius": {"default": {"$type": "dimension", "$value": "0px"}},
    "shadow": {k[7:]: {"$type": "shadow", "$value": v} for k, v in VARS.items() if k.startswith("shadow-")},
    "motion": {
        "easing": {k[5:]: {"$type": "cubicBezier", "$value": [float(x) for x in re.findall(r"[\d.]+", v)]} for k, v in VARS.items() if k.startswith("ease-")},
        "duration": {k[4:]: {"$type": "duration", "$value": v} for k, v in VARS.items() if k.startswith("dur-")},
    },
    "breakpoint": {k[3:]: {"$type": "dimension", "$value": v} for k, v in VARS.items() if k.startswith("bp-")},
    "z-index": {k[2:]: {"$type": "number", "$value": int(v)} for k, v in VARS.items() if k.startswith("z-")},
}
with open(os.path.join(OUT, "tokens.json"), "w") as f:
    json.dump(tokens, f, indent=2)

# ------------------------------------------------------------------ theme.json
NAMES = {"navy": "Midnight Navy", "navy-800": "Navy 800", "navy-700": "Navy 700", "ink": "Text Ink", "ivory": "Warm Ivory",
         "paper": "Paper", "sandstone": "Sandstone", "sandstone-2": "Sandstone Deep", "gold": "Antique Gold",
         "gold-ink": "Gold Ink", "gold-pale": "Gold Pale", "slate": "Slate", "mist": "Mist"}
palette = [{"slug": k, "name": NAMES.get(k, k.title()), "color": v["$value"]} for k, v in color.items()]
sizes = []
for k, v in VARS.items():
    if not k.startswith("step-"):
        continue
    lo, hi = clamp_parts(v)
    entry = {"slug": k[5:], "name": k[5:].replace("-", " ").title(), "size": hi}
    entry["fluid"] = {"min": lo, "max": hi} if lo != hi else False
    sizes.append(entry)
SPACE_NAMES = ["4px", "8px", "12px", "16px", "24px", "32px", "48px", "64px", "96px", "128px"]
spacing = [{"slug": str(i * 10), "name": SPACE_NAMES[i - 1], "size": VARS[f"space-{i}"]} for i in range(1, 11)]
theme = {
    "$schema": "https://schemas.wp.org/trunk/theme.json",
    "version": 3,
    "settings": {
        "appearanceTools": True,
        "useRootPaddingAwareAlignments": True,
        "color": {"palette": palette, "defaultPalette": False, "defaultGradients": False, "custom": False, "customGradient": False},
        "typography": {
            "defaultFontSizes": False, "customFontSize": False, "fluid": {"minViewportWidth": "360px", "maxViewportWidth": "1440px"},
            "fontFamilies": [
                {"slug": "display", "name": "Cormorant Garamond", "fontFamily": VARS["font-display"],
                 "fontFace": [{"fontFamily": "Cormorant Garamond", "fontWeight": "300 700", "fontStyle": s,
                               "src": [f"file:./assets/fonts/cormorant-garamond{'-italic' if s == 'italic' else ''}-variable.woff2"]} for s in ("normal", "italic")]},
                {"slug": "sans", "name": "Manrope", "fontFamily": VARS["font-sans"],
                 "fontFace": [{"fontFamily": "Manrope", "fontWeight": "200 800", "fontStyle": "normal", "src": ["file:./assets/fonts/manrope-variable.woff2"]}]},
            ],
            "fontSizes": sizes,
        },
        "spacing": {"defaultSpacingSizes": False, "spacingSizes": spacing, "units": ["px", "rem", "em", "%", "vw", "vh", "ch"],
                    "padding": True, "margin": True, "blockGap": True},
        "layout": {"contentSize": "46rem", "wideSize": VARS["content-max"]},
        "border": {"radius": False, "color": True, "style": True, "width": True},
        "shadow": {"defaultPresets": False, "presets": [{"slug": k[7:], "name": f"Elevation {k[-1]}", "shadow": v} for k, v in VARS.items() if k.startswith("shadow-")]},
        "custom": {
            "measure": VARS["measure"], "sectionY": VARS["section-y"], "gutter": VARS["gutter"], "pageMargin": VARS["page-margin"],
            "frameInset": VARS["frame-inset"], "radius": "0",
            "ease": {"out": VARS["ease-out"], "inOut": VARS["ease-in-out"]},
            "duration": {k[4:]: v for k, v in VARS.items() if k.startswith("dur-")},
            "tracking": {"label": VARS["tracking-label"], "button": VARS["tracking-button"], "display": VARS["tracking-display"]},
        },
    },
    "styles": {
        "color": {"background": "var(--wp--preset--color--ivory)", "text": "var(--wp--preset--color--ink)"},
        "typography": {"fontFamily": "var(--wp--preset--font-family--sans)", "fontSize": "var(--wp--preset--font-size--body)", "lineHeight": VARS["leading-body"]},
        "spacing": {"padding": {"left": VARS["page-margin"], "right": VARS["page-margin"]}, "blockGap": VARS["space-5"]},
        "elements": {
            "heading": {"typography": {"fontFamily": "var(--wp--preset--font-family--display)", "fontWeight": "300", "lineHeight": VARS["leading-heading"], "letterSpacing": VARS["tracking-display"]},
                        "color": {"text": "var(--wp--preset--color--navy)"}},
            **{f"h{i}": {"typography": {"fontSize": f"var(--wp--preset--font-size--h{i})"}} for i in range(1, 7)},
            "link": {"color": {"text": "var(--wp--preset--color--navy)"}, ":hover": {"color": {"text": "var(--wp--preset--color--gold-ink)"}},
                     "typography": {"textDecoration": "underline"}},
            "button": {"color": {"background": "var(--wp--preset--color--navy)", "text": "var(--wp--preset--color--ivory)"},
                       "border": {"radius": "0"},
                       "typography": {"fontFamily": "var(--wp--preset--font-family--sans)", "fontWeight": "600", "fontSize": "0.78rem", "letterSpacing": VARS["tracking-button"], "textTransform": "uppercase"}},
            "caption": {"typography": {"fontSize": "var(--wp--preset--font-size--caption)"}, "color": {"text": "var(--wp--preset--color--slate)"}},
        },
    },
}
with open(os.path.join(OUT, "theme.json"), "w") as f:
    json.dump(theme, f, indent=2)
print("wrote assets/tokens/tokens.json (%d colors, %d sizes) and assets/tokens/theme.json" % (len(color), len(font_size)))
