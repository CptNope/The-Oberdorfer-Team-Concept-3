"""Build the coming-soon WordPress theme, its static preview and the installable zip.

    python3 tools/build_coming_soon.py

Source: wordpress/oberdorfer-coming-soon/ (hand-written PHP, pattern, templates and CSS).
This script, which is safe to run repeatedly:
  1. writes the theme's theme.json from assets/tokens/theme.json (the design tokens), without
     the self-hosted font faces, which the full theme ships;
  2. copies the logo, icons and REWAP plate into the theme's assets/img/;
  3. inlines the current townscape drawing from assets/img/illustrations.svg into the pattern;
  4. renders coming-soon/index.html, a static preview of the page for client review (noindex),
     with a copy of the theme's assets so it can be published on its own;
  5. zips the theme to wordpress/dist/oberdorfer-coming-soon.zip.
Run tools/screenshot.py afterwards to refresh the theme's screenshot.png (see the end of main()).
"""
import json, os, re, shutil, zipfile

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
THEME = os.path.join(ROOT, "wordpress", "oberdorfer-coming-soon")
PREVIEW = os.path.join(ROOT, "coming-soon")
DIST = os.path.join(ROOT, "wordpress", "dist")

ASSETS = {
    "assets/logo/logo-primary-gold.svg": "logo-primary-gold.svg",
    "assets/logo/favicon.svg": "favicon.svg",
    "assets/logo/favicon.ico": "favicon.ico",
    "assets/logo/apple-touch-icon.png": "apple-touch-icon.png",
    "assets/logo/icon-192.png": "icon-192.png",
    "assets/logo/icon-512.png": "icon-512.png",
    "assets/logo/site.webmanifest": "site.webmanifest",
    "assets/logo/social-avatar.png": "social-avatar.png",
    "assets/img/rewap-logo.webp": "rewap-logo.webp",
}

def theme_json():
    src = json.load(open(os.path.join(ROOT, "assets", "tokens", "theme.json")))
    for fam in src["settings"]["typography"].get("fontFamilies", []):
        fam.pop("fontFace", None)   # Google Fonts on this page; the full theme self-hosts
    src["styles"]["color"] = {"background": "var(--wp--preset--color--navy)", "text": "var(--wp--preset--color--ivory)"}
    src["styles"]["spacing"] = {"padding": {"top": "0", "right": "0", "bottom": "0", "left": "0"}, "blockGap": "0"}
    src["templateParts"] = []
    src["patterns"] = []
    with open(os.path.join(THEME, "theme.json"), "w") as f:
        json.dump(src, f, indent="\t", ensure_ascii=False)
        f.write("\n")

def copy_assets():
    out = os.path.join(THEME, "assets", "img")
    os.makedirs(out, exist_ok=True)
    for src, name in ASSETS.items():
        shutil.copyfile(os.path.join(ROOT, src), os.path.join(out, name))

def inline_townscape():
    lib = open(os.path.join(ROOT, "assets", "img", "illustrations.svg")).read()
    m = re.search(r'<symbol id="townscape"[^>]*>(.*?)</symbol>', lib, re.S)
    inner = re.sub(r"\s+", " ", m.group(1)).strip()
    p = os.path.join(THEME, "patterns", "coming-soon.php")
    s = open(p).read()
    s2 = re.sub(r'(<svg class="cs-townscape"[^>]*>).*?(</svg>)', lambda mm: mm.group(1) + inner + mm.group(2), s, count=1, flags=re.S)
    if s2 != s:
        open(p, "w").write(s2)
    return s2

def preview(pattern):
    """The pattern's block markup as a plain page, with the theme's CSS."""
    body = pattern[pattern.index("?>") + 2:]
    body = re.sub(r"<!-- /?wp:[^>]*-->\n?", "", body)
    body = body.replace("<?php echo esc_url( $img . '", "assets/img/").replace("' ); ?>", "")
    head_desc = ("Brandon and Kaitlyn Oberdorfer are a husband-and-wife real estate team opening an office in Worcester "
                 "and serving all of Massachusetts. Call or text 406-250-7620.")
    html = f"""<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>The Oberdorfer Team | Worcester &amp; Massachusetts Real Estate</title>
<meta name="description" content="{head_desc}">
<!-- Preview of the WordPress coming-soon theme (wordpress/oberdorfer-coming-soon). Not the live site. -->
<meta name="robots" content="noindex">
<link rel="icon" href="assets/img/favicon.ico" sizes="32x32">
<link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="assets/img/apple-touch-icon.png">
<meta name="theme-color" content="#071D2D">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;1,300&family=Manrope:wght@400;600&display=swap">
<link rel="stylesheet" href="assets/css/coming-soon.css">
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<div class="wp-site-blocks">
{body.strip()}
</div>
</body>
</html>
"""
    # The preview is published on its own (Pages copies coming-soon/), so it carries copies of the theme's assets.
    if os.path.isdir(os.path.join(PREVIEW, "assets")):
        shutil.rmtree(os.path.join(PREVIEW, "assets"))
    shutil.copytree(os.path.join(THEME, "assets"), os.path.join(PREVIEW, "assets"))
    with open(os.path.join(PREVIEW, "index.html"), "w") as f:
        f.write(html)

def zip_theme():
    os.makedirs(DIST, exist_ok=True)
    out = os.path.join(DIST, "oberdorfer-coming-soon.zip")
    with zipfile.ZipFile(out, "w", zipfile.ZIP_DEFLATED) as z:
        for dp, _, fs in os.walk(THEME):
            for f in sorted(fs):
                full = os.path.join(dp, f)
                z.write(full, os.path.join("oberdorfer-coming-soon", os.path.relpath(full, THEME)))
    return out

def main():
    theme_json()
    copy_assets()
    pattern = inline_townscape()
    preview(pattern)
    out = zip_theme()
    print("wrote theme.json, assets, coming-soon/index.html and", os.path.relpath(out, ROOT))
    print("screenshot: python3 tools/screenshot.py http://localhost:8765/coming-soon/ "
          "wordpress/oberdorfer-coming-soon/screenshot.png 1200  (crop to 1200x900), then re-run to re-zip")

if __name__ == "__main__":
    main()
