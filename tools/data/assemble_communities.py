"""Assemble tools/data/communities.json from the researched town records.

Each town was researched into one JSON record (sources on every fact; see README.md).
This script orders them by region and tier, attaches the region name, and writes the
single data file that tools/build_site.py reads. Run it after adding or editing a town:

    python3 tools/data/assemble_communities.py <folder of town .json files>
"""
import json, os, sys

HERE = os.path.dirname(os.path.abspath(__file__))
# (slug, region key, tier). Tiers are the proposed priority from the October 2026 SEO
# review; the team has not confirmed the final community list.
PLAN = [
    ("worcester", "central", 1), ("shrewsbury", "central", 1), ("grafton", "central", 1),
    ("westborough", "central", 1), ("holden", "central", 1), ("leominster", "central", 2),
    ("framingham", "metrowest", 2), ("marlborough", "metrowest", 2),
    ("wakefield", "north", 1), ("reading", "north", 1), ("melrose", "north", 1),
    ("stoneham", "north", 2), ("lowell", "north", 2),
    ("newton", "boston", 2), ("quincy", "boston", 2),
    ("springfield", "west", 2),
    # Tier 3, added 2026-10-22 at the team's request: one Boston city guide (neighborhood
    # guides later) and anchor towns for the remaining regions.
    ("boston", "boston", 3), ("cambridge", "boston", 3), ("somerville", "boston", 3),
    ("plymouth", "southshore", 3), ("braintree", "southshore", 3),
    ("barnstable", "cape", 3), ("falmouth", "cape", 3),
    ("new-bedford", "southcoast", 3), ("fall-river", "southcoast", 3),
    ("pittsfield", "berkshires", 3), ("great-barrington", "berkshires", 3),
    # Tier 4, added 2026-10-22: towns around the Worcester office.
    ("auburn", "central", 4), ("millbury", "central", 4), ("northborough", "central", 4),
    ("paxton", "central", 4), ("sutton", "central", 4), ("boylston", "central", 4),
]
# Neighborhood guides, in page order, from tools/data/neighborhoods/<city>/<slug>.json.
# They are attached to their city's record as "neighborhoods" and get their own pages.
NEIGHBORHOODS = {
    "worcester": ["downtown", "canal-district", "shrewsbury-street", "main-south",
                  "grafton-hill", "tatnuck", "greendale", "quinsigamond-village"],
    "boston": ["back-bay", "beacon-hill", "south-end", "charlestown",
               "jamaica-plain", "south-boston", "west-roxbury", "dorchester"],
}
REGION_NAMES = {"central": "Central Massachusetts", "metrowest": "MetroWest", "north": "North of Boston",
                "boston": "Greater Boston", "southshore": "South Shore",
                "cape": "Cape Cod", "southcoast": "South Coast", "west": "Pioneer Valley",
                "berkshires": "The Berkshires"}
# Editorial extras that are not part of the research records.
EXTRA = {
    "wakefield": {
        "photo": "assets/img/photo/lake-quannapowitt.webp", "photo_alt": "Lake Quannapowitt at dusk", "focal": "35% 55%",
        "photo_credit": "An evening on Lake Quannapowitt. Photo: RedSoxFan274 / Wikimedia Commons, CC BY-SA 4.0, cropped.",
        "extra_market_html": "<p class=\"body-s\" style=\"margin-top:var(--space-4)\">More recent, all home types: the median sale price was $786,979 for the three months ending August 2026, with a median of 21 days on market and a 103.3% sale-to-list ratio in August (Redfin, accessed October 10, 2026). <a href=\"../../../site/journal/the-best-plans-are-close-to-home/\">Six ways to enjoy Wakefield this fall</a>.</p>",
    },
}
ART = {"worcester": "triple-decker", "lowell": "townscape", "springfield": "triple-decker", "quincy": "townscape",
       "newton": "colonial", "framingham": "colonial", "marlborough": "cape", "leominster": "cape",
       "shrewsbury": "colonial", "grafton": "cape", "westborough": "colonial", "holden": "cape",
       "reading": "colonial", "melrose": "townscape", "stoneham": "cape",
       "boston": "townscape", "cambridge": "colonial", "somerville": "triple-decker", "plymouth": "cape",
       "braintree": "colonial", "barnstable": "cape", "falmouth": "cape", "new-bedford": "triple-decker",
       "fall-river": "triple-decker", "pittsfield": "colonial", "great-barrington": "townscape",
       "auburn": "cape", "millbury": "townscape", "northborough": "colonial", "paxton": "cape",
       "sutton": "colonial", "boylston": "cape"}

def condo_data():
    """Condo rows read separately from the same Warren Group report (tools/data/condo-2025.json).
    They only fill fields a town record leaves empty."""
    p = os.path.join(HERE, "condo-2025.json")
    return json.load(open(p)).get("towns", {}) if os.path.exists(p) else {}

def neighborhoods(city):
    out = []
    for slug in NEIGHBORHOODS.get(city, []):
        p = os.path.join(HERE, "neighborhoods", city, slug + ".json")
        if os.path.exists(p):
            out.append(json.load(open(p)))
        else:
            print("missing neighborhood", city, slug)
    return out

def main(src):
    out = []
    condos = condo_data()
    for slug, region, tier in PLAN:
        p = os.path.join(src, slug + ".json")
        if not os.path.exists(p):
            print("missing", slug); continue
        t = json.load(open(p))
        t["region_key"], t["region"], t["tier"] = region, REGION_NAMES[region], tier
        if slug in ART: t["art"] = ART[slug]
        t.update(EXTRA.get(slug, {}))
        c, m = condos.get(slug), t.get("market")
        if c and m and not c.get("caveat"):   # caveated rows (Boston, Barnstable) are not whole-town figures
            for k in ("condo_sales_2025", "condo_sales_2024", "condo_median_2025", "condo_median_2024"):
                if m.get(k) is None and c.get(k) is not None: m[k] = c[k]
            if c.get("row"): m["condo_row"] = c["row"]
        nb = neighborhoods(slug)
        if nb: t["neighborhoods"] = nb
        out.append(t)
    with open(os.path.join(HERE, "communities.json"), "w") as f:
        json.dump(out, f, indent=1, ensure_ascii=False)
    print(f"wrote tools/data/communities.json ({len(out)} towns)")

if __name__ == "__main__":
    main(sys.argv[1] if len(sys.argv) > 1 else os.path.join(HERE, "towns"))
