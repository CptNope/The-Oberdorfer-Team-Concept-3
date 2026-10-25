"""Build the coming-soon WordPress theme, its static preview and the installable zip.

    python3 tools/build_coming_soon.py

Source: wordpress/oberdorfer-coming-soon/ (hand-written PHP, pattern, templates and CSS).
This script, which is safe to run repeatedly:
  1. writes the theme's theme.json from assets/tokens/theme.json (the design tokens), without
     the self-hosted font faces, which the full theme ships;
  2. copies the logo, icons and REWAP plate into the theme's assets/img/;
  2b. writes the two contact cards (assets/vcard/*.vcf) from CONTACTS below;
  3. inlines the current townscape drawing from assets/img/illustrations.svg into the pattern;
  4. renders coming-soon/index.html, a static preview of the page for client review (noindex),
     with a copy of the theme's assets so it can be published on its own;
  5. zips the theme to wordpress/dist/oberdorfer-coming-soon.zip.
Run tools/screenshot.py afterwards to refresh the theme's screenshot.png (see the end of main()).
"""
import base64, io, json, os, re, shutil, zipfile
from PIL import Image

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
    "assets/img/photo/team-portrait.webp": "team-portrait.webp",
    "assets/img/share-oberdorfer-team-v1.jpg": "share-oberdorfer-team-v1.jpg",
}

# Contact cards ("Add to contacts"). Every value comes from the team: the confirmed contacts
# (CLAUDE.md, ground rule 2) and their own Blinq digital business cards, read 2026-10-22
# (Brandon: blinq.me/cmere9vg301rxs60mq7nu5g5g, Kaitlyn: blinq.me/WXgsLzUBTC2V). No license
# numbers: they are not on the cards and are still unconfirmed. The photos are the Blinq profile
# photos, supplied by Jeremy on 2026-10-22 (assets/img/photo/*-contact.jpg).
#
# Format: vCard 3.0, the version iPhone, Android (Google Contacts), Outlook and macOS all import.
# CRLF line endings, lines folded at 75 octets, commas and semicolons escaped in text values.
# Apple's grouped labels (itemN.X-ABLabel) name the extra links; other apps ignore them safely.
OFFICE = ("652 Park Ave, Unit B", "Worcester", "MA", "01603", "USA")
REV = "2026-10-22T00:00:00Z"
CONTACTS = [
    {"slug": "kaitlyn-oberdorfer", "given": "Kaitlyn", "family": "Oberdorfer", "maiden": "Flynn",
     "uid": "urn:uuid:6f1d0c52-6a1e-4f5b-9a37-0b5f3f8c1a01",
     "title": "MA Licensed Realtor", "tel": "+19782574938", "tel_type": "CELL",
     "email": "kaitlynoberdorfer@gmail.com",
     "links": [("Digital card", "https://blinq.me/WXgsLzUBTC2V")],
     "instagram": ("oberdorferteam", "https://www.instagram.com/oberdorferteam/"),
     "note": "Luxury service at every price point. Top Producing Agent. Record Breaking. MA Licensed Realtor with The Oberdorfer Team, powered by REWAP Brokerage LLC.",
     "photo": "assets/img/photo/kaitlyn-oberdorfer-contact.jpg"},
    {"slug": "brandon-oberdorfer", "given": "Brandon", "family": "Oberdorfer",
     "uid": "urn:uuid:6f1d0c52-6a1e-4f5b-9a37-0b5f3f8c1a02",
     "title": "MA Licensed Realtor", "tel": "+14062507620", "tel_type": "CELL",
     "email": "brandonoberdorfer@gmail.com",
     "links": [("Digital card", "https://blinq.me/cmere9vg301rxs60mq7nu5g5g")],
     "instagram": ("oberdorferteam", "https://www.instagram.com/oberdorferteam/"),
     "note": "Luxury at every price point. Top Producer. Realtor's Choice Winner. MA Licensed Realtor with The Oberdorfer Team, powered by REWAP Brokerage LLC. Also the team line.",
     "photo": "assets/img/photo/brandon-oberdorfer-contact.jpg"},
]

def _esc(v):
    return v.replace("\\", "\\\\").replace(",", "\\,").replace(";", "\\;").replace("\n", "\\n")

def _fold(line):
    """vCard lines fold at 75 octets; continuation lines start with a space (ASCII content)."""
    out = [line[:75]]
    line = line[75:]
    while line:
        out.append(" " + line[:74])
        line = line[74:]
    return "\r\n".join(out)

def _card(c):
    im = Image.open(os.path.join(ROOT, c["photo"])).convert("RGB")
    w, h = im.size
    side = min(w, h)
    im = im.crop(((w - side) // 2, 0, (w + side) // 2, side)).resize((min(side, 400),) * 2, Image.LANCZOS)
    buf = io.BytesIO()
    im.save(buf, "JPEG", quality=82, optimize=True, progressive=False)
    street, city, region, code, country = OFFICE
    fn = f"{c['given']} {c['family']}"
    L = ["BEGIN:VCARD", "VERSION:3.0", "PRODID:-//The Oberdorfer Team//oberdorferteam.com//EN",
         f"UID:{c['uid']}", f"REV:{REV}",
         f"N:{_esc(c['family'])};{_esc(c['given'])};;;", f"FN:{_esc(fn)}"]
    if c.get("maiden"):
        L.append(f"X-MAIDENNAME:{_esc(c['maiden'])}")
    L += [f"ORG:The Oberdorfer Team;REWAP Brokerage LLC",
          f"TITLE:{_esc(c['title'])}",
          f"TEL;TYPE={c['tel_type']},VOICE,pref:{c['tel']}",
          f"EMAIL;TYPE=INTERNET,WORK,pref:{c['email']}",
          f"ADR;TYPE=WORK,pref:;;{_esc(street)};{_esc(city)};{_esc(region)};{code};{_esc(country)}",
          f"LABEL;TYPE=WORK:{_esc(street)}\\n{_esc(city)}\\, {region} {code}\\n{_esc(country)}",
          "URL;TYPE=WORK,pref:https://oberdorferteam.com/"]
    n = 1
    for label, url in c["links"]:
        L += [f"item{n}.URL:{url}", f"item{n}.X-ABLabel:{_esc(label)}"]
        n += 1
    user, ig = c["instagram"]
    L += [f"X-SOCIALPROFILE;TYPE=instagram;X-USER={user}:{ig}",
          f"item{n}.URL:{ig}", f"item{n}.X-ABLabel:Instagram",
          "CATEGORIES:Real Estate,The Oberdorfer Team",
          f"NOTE:{_esc(c['note'])}",
          "PHOTO;ENCODING=b;TYPE=JPEG:" + base64.b64encode(buf.getvalue()).decode(),
          "END:VCARD"]
    return "\r\n".join(_fold(l) for l in L) + "\r\n"

def vcards():
    out = os.path.join(THEME, "assets", "vcard")
    os.makedirs(out, exist_ok=True)
    both = ""
    for c in CONTACTS:
        card = _card(c)
        both += card
        with open(os.path.join(out, c["slug"] + ".vcf"), "w", newline="", encoding="utf-8") as f:
            f.write(card)
    with open(os.path.join(out, "oberdorfer-team.vcf"), "w", newline="", encoding="utf-8") as f:
        f.write(both)
    # The website links the same cards (assets/vcard/ is published on Pages and copied into the WordPress build).
    site_out = os.path.join(ROOT, "assets", "vcard")
    os.makedirs(site_out, exist_ok=True)
    for name in os.listdir(out):
        shutil.copyfile(os.path.join(out, name), os.path.join(site_out, name))

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
    body = re.sub(r"<\?php echo esc_url\( home_url\( '/\?vcard=([a-z-]+)' \) \); \?>", r"assets/vcard/\1.vcf", body)
    body = body.replace("<?php echo esc_url( $img . '", "assets/img/").replace("' ); ?>", "")
    head_desc = ("Brandon and Kaitlyn Oberdorfer bring strategic marketing, skilled negotiation "
                 "and attentive guidance to buyers and sellers across Massachusetts.")
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
    vcards()
    pattern = inline_townscape()
    preview(pattern)
    out = zip_theme()
    print("wrote theme.json, assets, coming-soon/index.html and", os.path.relpath(out, ROOT))
    print("screenshot: python3 tools/screenshot.py http://localhost:8765/coming-soon/ "
          "wordpress/oberdorfer-coming-soon/screenshot.png 1200  (crop to 1200x900), then re-run to re-zip")

if __name__ == "__main__":
    main()
