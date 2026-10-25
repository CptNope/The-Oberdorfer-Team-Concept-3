#!/usr/bin/env python3
"""Build the WordPress block theme `oberdorfer` and the plugin `ot-core` for release.

    python3 tools/build_wp.py

Sources: wordpress/oberdorfer/ (theme: templates, parts, patterns, functions.php, wp.css,
theme-overrides.json) and wordpress/ot-core/ (plugin PHP). This script, safe to run repeatedly:
  1. writes the theme's theme.json from assets/tokens/theme.json (the design tokens), merged
     with wordpress/oberdorfer/theme-overrides.json;
  2. copies the design system's CSS, JS, icons, illustrations, photos and logos into the theme;
  3. snapshots the researched content (tools/data/communities.json, the agents in
     tools/build_site.py, page titles and descriptions from the prototype in site/) into
     wordpress/ot-core/data/content.json for the importer;
  4. zips both to wordpress/dist/oberdorfer.zip and wordpress/dist/ot-core.zip.
Contract between the two: docs/wordpress-build.md.
"""
import copy, html, json, os, re, shutil, sys, zipfile

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
sys.path.insert(0, os.path.join(ROOT, "tools"))
import build_site as S  # noqa: E402  (data and the tested title/description rules; does not rebuild the site)

THEME = os.path.join(ROOT, "wordpress", "oberdorfer")
CORE = os.path.join(ROOT, "wordpress", "ot-core")
DIST = os.path.join(ROOT, "wordpress", "dist")
VERSION = "0.1.1"

ASSET_DIRS = ["assets/img/photo", "assets/logo"]
ASSET_FILES = ["assets/css/tokens.css", "assets/css/base.css", "assets/css/components.css", "assets/css/site.css",
               "assets/js/site.js", "assets/js/site-demo.js",
               "assets/img/icons.svg", "assets/img/illustrations.svg", "assets/img/rewap-logo.webp",
               "assets/img/share-oberdorfer-team-v1.jpg", "assets/img/source-pages/page-1.webp",
               "assets/publications/wakefield-at-home-fall-2026.pdf"]


def deep_merge(a, b):
    out = copy.deepcopy(a)
    for k, v in b.items():
        if isinstance(v, dict) and isinstance(out.get(k), dict):
            out[k] = deep_merge(out[k], v)
        else:
            out[k] = copy.deepcopy(v)
    return out


def theme_json():
    src = json.load(open(os.path.join(ROOT, "assets", "tokens", "theme.json")))
    for fam in src["settings"]["typography"].get("fontFamilies", []):
        fam.pop("fontFace", None)  # Google Fonts for v0.1; self-hosting is a launch task
    over = os.path.join(THEME, "theme-overrides.json")
    if os.path.exists(over):
        src = deep_merge(src, json.load(open(over)))
    with open(os.path.join(THEME, "theme.json"), "w") as f:
        json.dump(src, f, indent="\t", ensure_ascii=False)
        f.write("\n")


def copy_assets():
    for d in ASSET_DIRS:
        dst = os.path.join(THEME, d)
        if os.path.isdir(dst):
            shutil.rmtree(dst)
        shutil.copytree(os.path.join(ROOT, d), dst, ignore=shutil.ignore_patterns("*-contact.jpg"))
    for f in ASSET_FILES:
        dst = os.path.join(THEME, f)
        os.makedirs(os.path.dirname(dst), exist_ok=True)
        shutil.copyfile(os.path.join(ROOT, f), dst)


def page_meta(rel):
    """Title and meta description of a prototype page (site/<rel>/index.html)."""
    p = os.path.join(ROOT, "site", rel, "index.html") if rel else os.path.join(ROOT, "site", "index.html")
    doc = open(p).read()
    t = html.unescape(re.search(r"<title>(.*?)</title>", doc).group(1))
    d = html.unescape(re.search(r'<meta name="description" content="(.*?)"', doc).group(1))
    return {"ot_seo_title": t, "ot_seo_description": d}


def community_entries():
    out = []
    for i, t in enumerate(S.TOWNS):
        rec = {k: v for k, v in t.items() if k not in ("neighborhoods", "search_notes", "notes")}
        out.append({
            "slug": t["slug"], "parent": None, "title": t["name"], "order": i, "region": t.get("region_key"),
            "excerpt": S.first_sentence(t["answer"]),
            "meta": {"ot_record": rec, "ot_headline": "\n".join(t.get("headline", [])), "ot_answer": t["answer"],
                     "ot_kicker": f"Living in {t['name']}, MA", "ot_county": t.get("county", ""),
                     "ot_population": str(t.get("population_2020") or ""),
                     "ot_seo_title": S.town_title(t["name"]), "ot_seo_description": S.town_desc(t)},
        })
        for j, n in enumerate(t.get("neighborhoods", [])):
            nrec = {k: v for k, v in n.items() if k not in ("search_notes", "notes")}
            out.append({
                "slug": n["slug"], "parent": t["slug"], "title": n["name"], "order": j, "region": t.get("region_key"),
                "excerpt": S.first_sentence(n["answer"]),
                "meta": {"ot_record": nrec, "ot_headline": "\n".join(n.get("headline", [])), "ot_answer": n["answer"],
                         "ot_kicker": f"Living in {n['name']}, {t['name']}, MA", "ot_county": t.get("county", ""),
                         "ot_population": str(n.get("population_2020") or ""),
                         "ot_seo_title": S.nbhd_title(n["name"], t["name"]), "ot_seo_description": S.nbhd_desc(n)},
            })
    return out


# Confirmed 2026-10-22 from the team's Blinq digital cards (CLAUDE.md, ground rule 2). Accolades are
# the team's own claims and are shown with their source, nothing added.
AGENT_EXTRA = {
    "brandon-oberdorfer": {"title": "MA Licensed Realtor", "accolades": [
        {"text": "Top Producer", "source": "Wakefield at Home, Fall 2026"},
        {"text": "Realtor's Choice Winner", "source": "Brandon's digital card"}],
        "same_as": ["https://www.instagram.com/oberdorferteam/", "https://blinq.me/cmere9vg301rxs60mq7nu5g5g"]},
    "kaitlyn-oberdorfer": {"title": "MA Licensed Realtor", "accolades": [
        {"text": "Top Producing Agent", "source": "Kaitlyn's digital card"},
        {"text": "Record Breaking", "source": "Kaitlyn's digital card"}],
        "same_as": ["https://www.instagram.com/oberdorferteam/", "https://blinq.me/WXgsLzUBTC2V"]},
}


def agent_entries():
    out = []
    for i, a in enumerate(S.AGENTS):
        rec = {k: a[k] for k in ("name", "given", "family", "role", "partner", "relation", "photo", "photo_w", "photo_h", "photo_note", "email", "direct")}
        rec["card"] = a.get("card")
        rec.update(AGENT_EXTRA.get(a["slug"], {}))
        meta = {"ot_record": rec, "ot_role": a["role"], "ot_job_title": rec.get("title", ""), "ot_phone": a["direct"] or "",
                "ot_email": a["email"] or "", "ot_instagram": "https://www.instagram.com/oberdorferteam/", "ot_photo": a["photo"],
                "ot_card_url": next((u for u in rec.get("same_as", []) if "blinq" in u), "")}
        meta.update(page_meta(f"agents/{a['slug']}"))
        out.append({"slug": a["slug"], "title": a["name"], "order": i + 1, "meta": meta})
    team = {"kind": "team", "name": "Brandon & Kaitlyn Oberdorfer", "given": "Brandon & Kaitlyn", "prose": [
        "Home holds the everyday moments that become a life: dinners around the table, celebrations with friends, and a familiar place to land at the end of the day.",
        "We are Brandon and Kaitlyn Oberdorfer: The Oberdorfer Team, a husband-and-wife real estate partnership. Our work reflects the values we bring to our own family: care, honesty, hard work, and showing up for the people who count on us.",
        "Whether you are buying your first home, making room for your next chapter, or preparing to sell, we want you to feel heard, informed, and supported from the first conversation through closing."]}
    meta = {"ot_record": team}
    meta.update(page_meta("agents/brandon-kaitlyn-oberdorfer"))
    out.insert(0, {"slug": "brandon-kaitlyn-oberdorfer", "title": "Brandon & Kaitlyn Oberdorfer", "order": 0, "meta": meta})
    return out


PAGES = [  # slug, title, prototype path for title and description, menu order
    ("home", "Home", "", 0), ("buy", "Buy", "buy", 1), ("sell", "Sell", "sell", 2), ("market", "Market insights", "market", 3),
    ("team", "Our Team", "team", 4), ("journal", "Journal", "journal", 5), ("join", "Join Our Team", "join", 6),
    ("contact", "Contact", "contact", 7),
]


def content():
    regions = [{"key": k, "name": n, "blurb": b, "order": i} for i, (k, n, b) in enumerate(S.REGIONS)]
    office = {"slug": "worcester", "title": "The Oberdorfer Team Office, Worcester", "meta": {
        "ot_record": {"city": "Worcester", "street": S.STREET, "postcode": S.POSTCODE, "maps": S.MAPS,
                      "hours": None, "status": "A team office of REWAP Brokerage LLC (formal status to be confirmed)"},
        "ot_street": S.STREET, "ot_city": "Worcester", "ot_postcode": S.POSTCODE, **page_meta("office")}}
    pages = [{"slug": s, "title": t, "order": o, "meta": page_meta(rel)} for s, t, rel, o in PAGES]
    data = {"version": f"{VERSION} ({S.UPDATED})", "front_page": "home", "posts_page": "journal",
            "regions": regions, "communities": community_entries(), "agents": agent_entries(),
            "offices": [office], "pages": pages}
    os.makedirs(os.path.join(CORE, "data"), exist_ok=True)
    with open(os.path.join(CORE, "data", "content.json"), "w") as f:
        json.dump(data, f, indent=1, ensure_ascii=False)
    return data


def zip_dir(src, name):
    os.makedirs(DIST, exist_ok=True)
    out = os.path.join(DIST, name + ".zip")
    with zipfile.ZipFile(out, "w", zipfile.ZIP_DEFLATED) as z:
        for dp, dns, fs in os.walk(src):
            dns[:] = sorted(d for d in dns if not d.startswith("."))
            for f in sorted(fs):
                if f.startswith(".") or f == "theme-overrides.json":
                    continue
                full = os.path.join(dp, f)
                z.write(full, os.path.join(name, os.path.relpath(full, src)))
    return out


def main():
    if not os.path.isdir(THEME):
        sys.exit("wordpress/oberdorfer/ not found")
    theme_json()
    copy_assets()
    data = content()
    vdst = os.path.join(CORE, "data", "vcard")  # contact cards, served by ot-core at /?vcard=<slug>
    os.makedirs(vdst, exist_ok=True)
    for name in os.listdir(os.path.join(ROOT, "assets", "vcard")):
        shutil.copyfile(os.path.join(ROOT, "assets", "vcard", name), os.path.join(vdst, name))
    a = zip_dir(THEME, "oberdorfer")
    b = zip_dir(CORE, "ot-core")
    print(f"content: {len(data['communities'])} communities, {len(data['agents'])} agents, {len(data['pages'])} pages")
    for p in (a, b):
        print("wrote", os.path.relpath(p, ROOT), os.path.getsize(p), "bytes")


if __name__ == "__main__":
    main()
