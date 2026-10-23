#!/usr/bin/env python3
"""Build the static example website into /site.

Run from the repo root:  python3 tools/build_site.py

Shared partials (header, footer, cards, forms) live here as functions, the way
they will become PHP template parts and Gutenberg blocks in the WordPress
theme. Everything is plain HTML that uses the design-system CSS.
"""
import html, json, os, re, textwrap

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
OUT = os.path.join(ROOT, "site")
PHONE, TEL = "406-250-7620", "+14062507620"
e = html.escape

# --------------------------------------------------------------------------- data
LISTINGS = [
    dict(slug="demo-residence-one", name="Demo Residence One", town="Wakefield", county="Middlesex", price=949000, beds=4, baths="2.5", sqft=2400, kind="Single-family", style="Colonial", art="colonial", dark=True, open_house=True, rail=True, new=False, reduced=False, year="1928", lot="0.21 acres"),
    dict(slug="demo-residence-two", name="Demo Residence Two", town="Worcester", county="Worcester", price=729000, beds=6, baths="3", sqft=3600, kind="Multi-family", style="Triple-decker, 3 units", art="triple-decker", dark=False, open_house=False, rail=True, new=True, reduced=False, year="1915", lot="0.12 acres"),
    dict(slug="demo-residence-three", name="Demo Residence Three", town="Reading", county="Middlesex", price=679000, beds=3, baths="2", sqft=1650, kind="Single-family", style="Cape", art="cape", dark=True, open_house=True, rail=False, new=False, reduced=True, year="1952", lot="0.25 acres"),
    dict(slug="demo-residence-four", name="Demo Residence Four", town="Boston", county="Suffolk", price=585000, beds=2, baths="2", sqft=1120, kind="Condominium", style="Brownstone condominium", art="townscape", dark=False, open_house=False, rail=True, new=True, reduced=False, year="1890", lot="n/a"),
    dict(slug="demo-residence-five", name="Demo Residence Five", town="Melrose", county="Middlesex", price=1250000, beds=5, baths="3.5", sqft=3200, kind="Single-family", style="Colonial", art="colonial", dark=False, open_house=False, rail=True, new=False, reduced=False, year="1906", lot="0.33 acres"),
    dict(slug="demo-residence-six", name="Demo Residence Six", town="Stoneham", county="Middlesex", price=549000, beds=3, baths="1.5", sqft=1400, kind="Single-family", style="Cape", art="cape", dark=True, open_house=False, rail=False, new=True, reduced=False, year="1948", lot="0.18 acres"),
]
NAV = [("Buy", "buy/"), ("Sell", "sell/"), ("Communities", "communities/"), ("Our Team", "team/"),
       ("Journal", "journal/"), ("Join Our Team", "join/"), ("Contact", "contact/")]

def money(n): return f"${n:,.0f}"

# --------------------------------------------------------------------------- atoms
def icon(R, name, cls="icon", vb=None):
    v = f' viewBox="{vb}"' if vb else ""
    return f'<svg class="{cls}" aria-hidden="true"{v}><use href="{R}assets/img/icons.svg#i-{name}"/></svg>'

def arrow(R): return icon(R, "arrow", vb="0 0 34 12")

def art(R, name, label=None):
    a = f'role="img" aria-label="{e(label)}"' if label else 'aria-hidden="true"'
    return f'<svg viewBox="0 0 400 260" {a}><use href="{R}assets/img/illustrations.svg#{name}"/></svg>'

def link_arrow(R, href, text, style=""):
    s = f' style="{style}"' if style else ""
    return f'<a class="link-arrow" href="{href}"{s}>{text} {arrow(R)}</a>'

def section_head(run_left, run_right, title_lines, lead=None, aside=None, level="h2", title_id=None):
    spans = "".join(f"<span>{t}</span>" for t in title_lines)
    tid = f' id="{title_id}"' if title_id else ""
    out = f'<div class="section-head"><div class="running-head label"><span>{run_left}</span><span class="label--quiet">{run_right}</span></div>'
    out += f'<{level} class="section-head__title display-m answer"{tid}>{spans}</{level}>'
    if aside: out += f'<div class="section-head__aside">{aside}</div>'
    if lead: out += f'<p class="section-head__lead lead">{lead}</p>'
    return out + "</div>"

def breadcrumbs(R, trail):
    items = []
    for i, (label, href) in enumerate(trail):
        if i == len(trail) - 1: items.append(f'<li aria-current="page">{label}</li>')
        else: items.append(f'<li><a href="{R}{href}">{label}</a></li>')
    return f'<nav class="breadcrumbs" aria-label="Breadcrumb"><ol>{"".join(items)}</ol></nav>'

def crumbs_ld(trail):
    return {"@context": "https://schema.org", "@type": "BreadcrumbList", "itemListElement": [
        {"@type": "ListItem", "position": i + 1, "name": html.unescape(l), "item": prod(h)} for i, (l, h) in enumerate(trail)]}

def page_hero(R, trail, title_lines, lead=None, aside=None, navy=False, photo=None, photo_alt="", focal="50% 50%", h1=None):
    """h1: the search-facing page heading (e.g. "Living in Worcester, MA"). When given, it is
    the page's <h1>, set as the kicker above the display line, and the brand line becomes a <p>."""
    spans = "".join(f"<span>{t}</span>" for t in title_lines)
    cls = "page-hero"
    img = ""
    if navy or photo: cls += " page-hero--navy surface-inverse"
    if photo:
        cls += " page-hero--photo"
        img = f'<img src="{R}{photo}" alt="{e(photo_alt)}" style="object-position:{focal}" fetchpriority="high">'
    out = f'<section class="{cls}" aria-labelledby="page-title">{img}<div class="container page-hero__grid">'
    out += breadcrumbs(R, trail)
    if h1:
        out += f'<h1 class="label page-hero__kicker" id="page-title">{h1}</h1>'
        out += f'<p class="page-hero__title display-l answer">{spans}</p>'
    else:
        out += f'<h1 class="page-hero__title display-l answer" id="page-title">{spans}</h1>'
    if aside: out += f'<div class="page-hero__aside">{aside}</div>'
    if lead: out += f'<p class="page-hero__lead lead">{lead}</p>'
    return out + "</div></section>"

def folio(left, n):
    return f'<div class="folio-bar" style="margin-top:var(--space-9)"><span>The Oberdorfer Team</span><span>{left}</span><span>{n}</span></div>'

# --------------------------------------------------------------------------- cards
def listing_card(R, l, here_href):
    dark = "" if l["dark"] else " demo-illu--light"
    flags = []
    if l["open_house"]: flags.append("open")
    if l["rail"]: flags.append("rail")
    if l["new"]: flags.append("new")
    if l["reduced"]: flags.append("reduced")
    return f'''<article class="listing-card" data-town="{l['town'].lower()}" data-price="{l['price']}" data-beds="{l['beds']}" data-kind="{l['kind'].lower()}" data-flags="{' '.join(flags)}" data-new="{1 if l['new'] else 0}">
  <div class="listing-card__media demo-illu{dark}">{art(R, l['art'], f"Illustration standing in for photos of {l['name']}")}<span class="badge badge--demo">Demo listing</span><button class="icon-btn listing-card__save" type="button" data-save="{l['name']}" data-save-id="{l['slug']}" aria-pressed="false" aria-label="Save {l['name']}">{icon(R, 'heart')}</button></div>
  <p class="listing-card__price tabular">{money(l['price'])}</p>
  <h3 class="listing-card__address"><a href="{here_href}" style="text-decoration:none;color:inherit">{l['name']} · {l['town']}, MA</a></h3>
  <p class="listing-card__specs"><span><b>{l['beds']}</b> beds</span><span><b>{l['baths']}</b> baths</span><span><b>{l['sqft']:,}</b> sq ft</span><span>{l['style']}</span></p>
  <p class="listing-card__courtesy">{"Open house Sunday · " if l['open_house'] else ""}Listing courtesy of [Listing brokerage] · Demo data</p>
</article>'''

def agent_card_bk(R):
    return f'''<article class="agent-card" data-specialty="buyers sellers first-time move-up" data-area="massachusetts wakefield worcester boston" data-lang="english">
  <a class="agent-card__photo" href="{R}site/agents/brandon-kaitlyn-oberdorfer/"><img src="{R}assets/img/photo/team-portrait.webp" alt="Brandon and Kaitlyn Oberdorfer" width="1400" height="1680" loading="lazy"></a>
  <div class="stack" style="--stack:var(--space-2)"><p class="agent-card__title">Team leaders · Top Producer</p><h3 class="agent-card__name"><a href="{R}site/agents/brandon-kaitlyn-oberdorfer/" style="text-decoration:none;color:inherit">Brandon &amp; Kaitlyn Oberdorfer</a></h3><p class="body-s muted">Husband. Wife. Your Team.</p></div>
  <div class="agent-card__meta"><span class="chip">Buyers</span><span class="chip">Sellers</span><span class="chip">Massachusetts</span></div>
  <div class="cluster" style="--cluster:var(--space-5)"><a class="link-arrow" href="{R}site/agents/brandon-kaitlyn-oberdorfer/">View profile {arrow(R)}</a><a class="link-arrow" href="tel:{TEL}">{PHONE}</a></div>
</article>'''

def seat_card(R):
    return f'''<div class="seat"><div class="stack" style="--stack:var(--space-3);display:grid;justify-items:center">{icon(R, 'user', 'icon')}<p class="h4">Your seat at the table</p><p class="body-s">The team is growing. New agent profiles appear here as they join.</p>{link_arrow(R, R + "site/join/", "Join our team")}</div></div>'''

ARTICLES = [
    dict(slug="the-best-plans-are-close-to-home", cat="Seasonal features", title="The best plans are close to home", dek="Six ways to enjoy Wakefield and the surrounding area this season.", img="assets/img/photo/breakheart-reservation.webp", issue="Wakefield at Home · Fall 2026", full=True),
    dict(slug=None, cat="Real estate insights", title="Know the market. Love the home.", dek="What a townwide median can and cannot tell you about your home.", art="colonial", issue="Wakefield at Home · Fall 2026", href="site/market/"),
    dict(slug=None, cat="Local living", title="Close to Boston. Closer to what matters.", dek="A lakeside walk, a downtown errand, a train into the city.", img="assets/img/photo/lake-quannapowitt.webp", issue="Wakefield at Home · Fall 2026", href="site/communities/wakefield/"),
    dict(slug=None, cat="Recipes and entertaining", title="Gather around", dek="Roasted squash and apple soup, and a maple apple crisp, for a cozy night at home.", art="cape", issue="Wakefield at Home · Fall 2026", href="site/journal/#archive"),
    dict(slug=None, cat="Home inspiration", title="The 30-minute fall edit", dek="The welcome, the light, the calm: three small changes before a showing.", art="colonial", issue="Wakefield at Home · Fall 2026", href="site/sell/#prepare"),
    dict(slug=None, cat="Home inspiration", title="Make your home feel exceptional", dek="Thoughtful presentation. Clear pricing. A plan that fits your life.", art="triple-decker", issue="Wakefield at Home · Fall 2026", href="site/sell/"),
]

def article_card(R, a):
    href = R + (f"site/journal/{a['slug']}/" if a.get("slug") else a["href"])
    media = f'<img src="{R}{a["img"]}" alt="" loading="lazy">' if a.get("img") else f'<div class="demo-illu demo-illu--light" style="width:100%;height:100%">{art(R, a["art"])}</div>'
    cat = a["cat"].lower().split()[0]
    return f'''<a class="article-card" href="{href}" data-cat="{e(a['cat'])}"><div class="article-card__media">{media}</div><p class="label">{a['cat']}</p><h3 class="article-card__title">{a['title']}</h3><p class="body-s muted">{a['dek']}</p><p class="article-card__meta">{a['issue']}</p></a>'''

# --------------------------------------------------------------------------- forms
def field(fid, label, typ="text", req=False, auto=None, ph=None, hint=None, pattern=None, pattern_msg=None, opt=False):
    attrs = [f'id="{fid}"', f'name="{fid.split("-",1)[-1]}"']
    if typ != "textarea": attrs.append(f'type="{typ}"')
    if req: attrs += ["required", f'data-label="{e(label)}"']
    if auto: attrs.append(f'autocomplete="{auto}"')
    if ph: attrs.append(f'placeholder="{e(ph)}"')
    if pattern: attrs += [f'pattern="{pattern}"', f'data-pattern-msg="{e(pattern_msg)}"']
    desc = [f"{fid}-e"] + ([f"{fid}-h"] if hint else [])
    attrs.append(f'aria-describedby="{" ".join(desc)}"')
    tag = "label"
    marker = ' <span class="req">(required)</span>' if req else (' <span class="opt">(optional)</span>' if opt else "")
    ctl = f'<textarea class="textarea" {" ".join(attrs)}></textarea>' if typ == "textarea" else f'<input class="input" {" ".join(attrs)}>'
    h = f'<p class="field__hint" id="{fid}-h">{hint}</p>' if hint else ""
    return f'<div class="field"><{tag} class="field__label" for="{fid}">{label}{marker}</{tag}>{ctl}{h}<p class="field__error" id="{fid}-e" hidden><svg class="icon" aria-hidden="true"><use href="ICONS#i-alert"/></svg><span></span></p></div>'

def select(fid, label, options, opt=False):
    o = "".join(f"<option>{x}</option>" for x in options)
    marker = ' <span class="opt">(optional)</span>' if opt else ""
    return f'<div class="field"><label class="field__label" for="{fid}">{label}{marker}</label><select class="select" id="{fid}" name="{fid.split("-",1)[-1]}">{o}</select></div>'

def reach_pref(prefix):
    return f'''<fieldset class="choice-group"><legend class="field__label">Best way to reach you</legend><div class="choices">
<label class="choice"><input type="radio" name="{prefix}-pref" value="call" checked><span>Call</span></label>
<label class="choice"><input type="radio" name="{prefix}-pref" value="text"><span>Text</span></label>
<label class="choice"><input type="radio" name="{prefix}-pref" value="email"><span>Email</span></label></div></fieldset>'''

def form(R, fid, title, inner, submit, lead_type, title_level="h2", note=None):
    note = note or "Your inquiry goes to Brandon and Kaitlyn and is used only to respond to your request. [Privacy policy to be published before launch.]"
    return f'''<form class="form" id="{fid}" data-demo-form data-icons="{R}assets/img/icons.svg" data-lead-type="{lead_type}" aria-labelledby="{fid}-title">
<{title_level} class="h3" id="{fid}-title">{title}</{title_level}>
{inner.replace("ICONS", R + "assets/img/icons.svg")}
<p class="form-note">{note}</p>
<div><button class="btn" type="submit">{submit}</button></div>
<div data-form-status hidden tabindex="-1" role="status"></div>
</form>'''

def contact_fields(p, phone_opt=True):
    return (f'<div class="form__row">{field(p+"-name","Full name",req=True,auto="name")}{field(p+"-email","Email","email",req=True,auto="email")}</div>'
            f'<div class="form__row">{field(p+"-phone","Phone","tel",auto="tel",opt=True,pattern="[0-9()+\\-\\s.]{7,}",pattern_msg="Use digits, spaces or dashes, for example 406-250-7620.")}</div>')

# --------------------------------------------------------------------------- chrome
SITE = "https://oberdorferteam.com/"   # production domain, confirmed 2026-10-22

def prod(path=""):
    """Production URL for a prototype path: the WordPress site has no /site/ prefix."""
    return SITE + path.removeprefix("site/")
TEAM_ID = SITE + "#team"
BROKER_ID = SITE + "#brokerage"
UPDATED = "2026-10-22"
# Office address confirmed 2026-10-22 (as supplied by the team; hours and formal status still open).
STREET, POSTCODE = "652 Park Ave, Unit B", "01603"
ADDRESS = f"{STREET}, Worcester, MA {POSTCODE}"
MAPS = "https://www.google.com/maps/search/?api=1&query=" + ADDRESS.replace(" ", "+").replace(",", "%2C")
WRITTEN = []   # every page path, for sitemap.xml and llms.txt

def head(R, title, desc, extra_ld=None, canonical_path="", og_type="website", og_image=None):
    ld = {"@context": "https://schema.org", "@type": "RealEstateAgent", "@id": TEAM_ID, "name": "The Oberdorfer Team",
          "url": SITE, "logo": SITE + "assets/logo/social-avatar.png", "image": SITE + "assets/img/photo/team-portrait.webp",
          "telephone": PHONE, "areaServed": {"@type": "State", "name": "Massachusetts"},
          "address": {"@type": "PostalAddress", "streetAddress": STREET, "addressLocality": "Worcester", "addressRegion": "MA", "postalCode": POSTCODE, "addressCountry": "US"},
          "slogan": "Luxury at every price point.",
          "parentOrganization": {"@type": "Organization", "@id": BROKER_ID, "name": "REWAP Brokerage LLC", "url": "https://rewapbrokerage.com/"}}
    blocks = [ld] + (extra_ld or [])
    lds = "".join(f'<script type="application/ld+json">{json.dumps(b)}</script>' for b in blocks)
    return f'''<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{title}</title>
<meta name="description" content="{e(desc)}">
<meta name="robots" content="noindex">
<link rel="canonical" href="{prod(canonical_path.rstrip('/') + '/')}">
<meta property="og:type" content="{og_type}"><meta property="og:title" content="{e(title)}"><meta property="og:description" content="{e(desc)}"><meta property="og:image" content="{R}{og_image or 'assets/img/share-oberdorfer-team-v1.jpg'}">{'' if og_image else '<meta property="og:image:width" content="1200"><meta property="og:image:height" content="630">'}<meta name="twitter:card" content="summary_large_image">
<link rel="icon" href="{R}assets/logo/favicon.ico" sizes="32x32"><link rel="icon" href="{R}assets/logo/favicon.svg" type="image/svg+xml"><link rel="apple-touch-icon" href="{R}assets/logo/apple-touch-icon.png"><link rel="manifest" href="{R}assets/logo/site.webmanifest"><meta name="theme-color" content="#071D2D">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,300;1,400&family=Manrope:wght@300;400;500;600;700&display=swap">
<link rel="stylesheet" href="{R}assets/css/tokens.css"><link rel="stylesheet" href="{R}assets/css/base.css"><link rel="stylesheet" href="{R}assets/css/components.css"><link rel="stylesheet" href="{R}assets/css/site.css">
{lds}
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<div id="live" class="visually-hidden" aria-live="polite"></div>
<p class="demo-ribbon">Concept prototype: listings, future agents and form submissions are demonstrations. <a href="{R}index.html">Project overview</a></p>'''

def header(R, active, overlay=False):
    links = "".join(f'<a href="{R}site/{h}"{" aria-current=page" if l == active else ""}>{l}</a>' for l, h in NAV if l != "Join Our Team")
    cls = "site-header" + (" site-header--overlay" if overlay else "")
    logo = f'{R}assets/logo/logo-compact-ivory.svg'
    mm = "".join(f'<li><a href="{R}site/{h}">{l}{" <span>Search homes</span>" if l=="Buy" else (" <span>Market review</span>" if l=="Sell" else "")}</a></li>' for l, h in NAV)
    return f'''<header class="{cls}">
  <div class="container site-header__inner">
    <a class="site-header__brand" href="{R}site/"><img src="{logo}" alt="The Oberdorfer Team, home" width="320" height="68" style="height:2.1rem"></a>
    <nav class="nav" aria-label="Primary">{links}</nav>
    <div class="site-header__actions">
      <a class="icon-btn" href="{R}site/buy/#search" aria-label="Search properties">{icon(R, 'search')}</a>
      <a class="btn btn--gold btn--small" href="tel:{TEL}">{PHONE}</a>
      <button class="icon-btn nav-toggle" type="button" data-menu-toggle aria-controls="mobile-menu" aria-expanded="false" aria-label="Open menu">{icon(R, 'menu')}</button>
    </div>
  </div>
</header>
<div class="mobile-menu" id="mobile-menu" role="dialog" aria-modal="true" aria-label="Menu" aria-hidden="true">
  <button class="icon-btn mobile-menu__close" type="button" data-close aria-label="Close menu" style="color:var(--ot-ivory)">{icon(R, 'close')}</button>
  <ul>{mm}</ul>
  <div class="mobile-menu__foot"><a class="btn btn--gold" href="tel:{TEL}">Call {PHONE}</a><p class="caption" style="color:var(--ot-mist)">Brandon &amp; Kaitlyn Oberdorfer · Husband-and-wife team</p></div>
</div>'''

FOOTER_TOWNS = [("worcester", "Worcester"), ("shrewsbury", "Shrewsbury"), ("framingham", "Framingham"), ("wakefield", "Wakefield"), ("boston", "Boston"), ("springfield", "Springfield")]

def footer(R):
    return f'''<footer class="site-footer">
  <div class="container">
    <div class="site-footer__grid">
      <div class="stack" style="--stack:var(--space-5)">
        <img src="{R}assets/logo/logo-primary-gold.svg" alt="The Oberdorfer Team" width="300" height="40" style="width:17rem">
        <p class="body-s" style="max-width:30ch">Luxury at every price point. A husband-and-wife real estate team based in Worcester, serving all of Massachusetts.</p>
        <a class="cta-panel__phone" href="tel:{TEL}" style="font-size:2rem">{PHONE}</a>
        <form class="newsletter" style="color:var(--ot-ivory)" data-newsletter aria-label="Seasonal publication sign-up"><label class="visually-hidden" for="f-news">Email address</label><input class="input" id="f-news" type="email" autocomplete="email" placeholder="Email for the At Home issues" style="color:var(--ot-ivory)"><button class="btn btn--small" type="submit">Subscribe</button></form>
      </div>
      <div><h2>Explore</h2><ul><li><a href="{R}site/buy/">Buy a home</a></li><li><a href="{R}site/sell/">Sell your home</a></li><li><a href="{R}site/market/">Market insights</a></li><li><a href="{R}site/communities/">Communities</a></li></ul></div>
      <div><h2>Communities</h2><ul>{"".join(f'<li><a href="{R}site/communities/{k}/">{n}, MA</a></li>' for k, n in FOOTER_TOWNS)}<li><a href="{R}site/communities/">All town guides</a></li></ul></div>
      <div><h2>Team</h2><ul><li><a href="{R}site/team/">Meet the team</a></li><li><a href="{R}site/agents/brandon-kaitlyn-oberdorfer/">Brandon &amp; Kaitlyn</a></li><li><a href="{R}site/agents/brandon-oberdorfer/">Brandon Oberdorfer</a></li><li><a href="{R}site/agents/kaitlyn-oberdorfer/">Kaitlyn Oberdorfer</a></li><li><a href="{R}site/join/">Join our team</a></li><li><a href="{R}site/office/">Team office</a></li></ul></div>
      <div><h2>Read</h2><ul><li><a href="{R}site/journal/">Journal</a></li><li><a href="{R}site/journal/#archive">At Home archive</a></li><li><a href="{R}site/contact/">Contact</a></li></ul></div>
    </div>
    <div class="attribution">
      <div class="attribution__plate"><img src="{R}assets/img/rewap-logo.webp" alt="REWAP Brokerage" width="900" height="300"><small>Real Estate Wealth Acquisition and Preservation Brokerage</small></div>
      <div class="stack" style="--stack:var(--space-3)">
        <p class="disclosure"><strong>The Oberdorfer Team</strong> is a real estate team operating under <strong>REWAP Brokerage LLC</strong>, Massachusetts. Office: {ADDRESS}. Brokerage license number and required team disclosures [to be supplied and approved by the principal broker]. Listing information is provided by [MLS name] and is deemed reliable but not guaranteed. Listings on this prototype are demonstrations.</p>
        <p class="eho">{icon(R, 'eho')}Equal Housing Opportunity</p>
        <p class="disclosure">© 2026 The Oberdorfer Team · <a href="{R}site/contact/#privacy">Privacy</a> · <a href="{R}site/contact/#accessibility">Accessibility</a> · <a href="{R}site/contact/#fair-housing">Fair housing</a></p>
      </div>
    </div>
  </div>
</footer>
<script src="{R}assets/js/site.js" defer></script>
<script src="{R}assets/js/site-demo.js" defer></script>
</body>
</html>'''

# Intrinsic sizes for photos, so every <img> reserves its space (prevents layout shift).
PHOTO_DIMS = {"lake-quannapowitt.webp": (2200, 735), "breakheart-reservation.webp": (1400, 1051),
              "team-portrait.webp": (1400, 1680), "cover-portrait.webp": (1103, 1426)}

def add_img_dims(doc):
    def fix(m):
        tag = m.group(0)
        if " width=" in tag: return tag
        for name, (w, h) in PHOTO_DIMS.items():
            if name in tag: return tag.replace("<img ", f'<img width="{w}" height="{h}" ', 1)
        return tag
    return re.sub(r"<img\b[^>]*>", fix, doc)

def write(rel, R, title, desc, active, body, overlay=False, ld=None, og_type="website", og_image=None, index=True):
    path = os.path.join(OUT, rel, "index.html")
    os.makedirs(os.path.dirname(path), exist_ok=True)
    WRITTEN.append(dict(rel=rel, title=title, desc=desc, index=index))
    doc = head(R, title, desc, ld, "site/" + rel, og_type, og_image) + header(R, active, overlay) + f'<main id="main">{body}</main>' + footer(R)
    doc = add_img_dims(doc)
    # Preload the hero image (the LCP element) so it starts before the stylesheets finish.
    m = re.search(r'<img\b[^>]*fetchpriority="high"[^>]*>', body)
    if m:
        src = re.search(r'src="([^"]+)"', m.group(0)).group(1)
        doc = doc.replace('<link rel="preconnect"', f'<link rel="preload" as="image" href="{src}" fetchpriority="high">\n<link rel="preconnect"', 1)
    with open(path, "w") as f: f.write(doc)
    print("wrote", os.path.relpath(path, ROOT))

def cta_panel(R, heading_html="For the home you have. <em>For the life you are building.</em>", label="A personal standard of service", text="Buying, selling, or simply curious about your next move? We would love to be a resource.", cta=("Request a consultation", "site/contact/")):
    return f'''<section class="section"><div class="container"><div class="cta-panel folio-frame" style="--frame-inset:.85rem">
<div class="split" style="align-items:end"><div class="stack" style="grid-column:1/span 7;--stack:var(--space-5)"><p class="label" style="color:var(--ot-gold)">{label}</p><h2 class="h1">{heading_html}</h2><p class="body-l" style="color:#D9D5CC;max-width:44ch">{text}</p></div>
<div class="stack" style="grid-column:9/span 4;--stack:var(--space-4)"><a class="cta-panel__phone" href="tel:{TEL}">{PHONE}</a><p class="caption" style="color:var(--ot-mist)">Brandon &amp; Kaitlyn Oberdorfer · Husband-and-wife team · Top Producer</p><div class="cluster"><a class="btn" href="{R}{cta[1]}">{cta[0]}</a></div></div></div></div></div></section>'''

# =========================================================================== PAGES
def home_town_stats(R):
    out = ""
    for k in ("worcester", "wakefield"):
        t = TOWN_BY_SLUG.get(k)
        m = (t or {}).get("market") or {}
        if m.get("sf_median_2025"):
            out += f'<div class="stat"><span class="stat__value tabular">{money(m["sf_median_2025"])}</span><span class="stat__label">{t["name"]} single-family median</span><span class="stat__period">2025 · <a href="{R}site/communities/{k}/">{t["name"]} guide</a></span></div>'
    return out

def page_home():
    R = "../"
    feat = "".join(listing_card(R, l, f"{R}site/buy/{l['slug']}/") for l in LISTINGS[:3])
    comm = "".join(community_card(R, t, i) for i, t in enumerate([TOWN_BY_SLUG[k] for k in ("worcester", "shrewsbury", "wakefield", "framingham") if k in TOWN_BY_SLUG]))
    arts = "".join(article_card(R, a) for a in ARTICLES[:3])
    body = f'''
<section class="hero-cover home-hero folio-frame" aria-labelledby="home-title">
  <div class="hero-cover__media"><img src="{R}assets/img/photo/cover-portrait.webp" alt="Brandon and Kaitlyn Oberdorfer outside a Massachusetts home in autumn" width="1103" height="1426" fetchpriority="high"></div>
  <div class="hero-cover__inner">
    <div class="home-hero__copy">
      <h1 class="label home-hero__issue" id="home-title">Massachusetts real estate team · Based in Worcester</h1>
      <p class="display-xl answer"><span>Luxury at</span><span>every price point.</span></p>
      <p class="lead">Brandon and Kaitlyn Oberdorfer are a husband-and-wife real estate team based in Worcester, helping buyers and sellers across Massachusetts. Thoughtful service. Creative marketing. A personal connection to every move.</p>
    </div>
    <form class="search-bar" role="search" action="{R}site/buy/" method="get" aria-label="Search homes">
      <div class="field"><label class="field__label" for="h-q">Location</label><input class="input" id="h-q" name="q" type="text" placeholder="Town, neighborhood or ZIP" autocomplete="off"></div>
      <div class="field"><label class="field__label" for="h-price">Price</label><select class="select" id="h-price" name="price"><option value="">Any price</option><option value="0-600000">Up to $600k</option><option value="600000-900000">$600k – $900k</option><option value="900000-99999999">$900k +</option></select></div>
      <div class="field"><label class="field__label" for="h-beds">Beds</label><select class="select" id="h-beds" name="beds"><option value="">Any</option><option value="2">2+</option><option value="3">3+</option><option value="4">4+</option></select></div>
      <div class="field"><label class="field__label" for="h-type">Home type</label><select class="select" id="h-type" name="type"><option value="">All types</option><option value="single-family">Single-family</option><option value="condominium">Condominium</option><option value="multi-family">Multi-family</option></select></div>
      <button class="btn" type="submit">{icon(R,'search')}Search</button>
    </form>
  </div>
</section>

<section class="section" aria-labelledby="intro-title">
  <div class="container intro">
    <figure class="intro__photo reveal"><img src="{R}assets/img/photo/team-portrait.webp" alt="Brandon and Kaitlyn Oberdorfer" width="1400" height="1680" loading="lazy"></figure>
    <div class="intro__body">
      <p class="label">The people behind the promise</p>
      <h2 class="display-m answer" id="intro-title"><span>Husband. Wife.</span><span>Your Team.</span></h2>
      <p class="body-l">Home holds the everyday moments that become a life: dinners around the table, celebrations with friends, and a familiar place to land at the end of the day.</p>
      <p>We are Brandon and Kaitlyn Oberdorfer, a husband-and-wife real estate partnership. Our work reflects the values we bring to our own family: care, honesty, hard work, and showing up for the people who count on us.</p>
      <div class="triad" style="margin-top:var(--space-4)">
        <div><p class="label">Personal</p><p class="h5">Two partners invested in your goals.</p></div>
        <div><p class="label">Purposeful</p><p class="h5">A plan shaped around your home and life.</p></div>
        <div><p class="label">Prepared</p><p class="h5">Clear guidance and attention to the details.</p></div>
      </div>
      <div class="cluster" style="--cluster:var(--space-6)">{link_arrow(R, R+"site/agents/brandon-kaitlyn-oberdorfer/", "Our story")}<span class="caption">Top Producer · REWAP Brokerage</span></div>
    </div>
  </div>
</section>

<section class="section surface-muted" aria-labelledby="feat-title">
  <div class="container">
    {section_head("Featured homes", "Demonstration listings", ["Homes presented", "like a feature."], aside=link_arrow(R, R+"site/buy/", "All homes"), title_id="feat-title")}
    <div class="listing-grid">{feat}</div>
    <p class="form-note" style="margin-top:var(--space-7)">Listings appear here through the team's authorized IDX feed once it is connected. These three are demonstrations.</p>
  </div>
</section>

<section class="paths" aria-label="Buying and selling">
  <div class="path path--buy surface-inverse"><svg class="path__art" viewBox="0 0 400 260" aria-hidden="true"><use href="{R}assets/img/illustrations.svg#colonial"/></svg>
    <div class="stack" style="--stack:var(--space-4)"><p class="label">If you are buying</p><h2 class="display-m answer"><span>Get ready</span><span>before you find the one.</span></h2><p style="max-width:40ch">Preapproval, everyday priorities, and the costs beyond the listing price. A calm plan for your first home or your next one.</p></div>
    <div class="cluster"><a class="btn" href="{R}site/buy/">Start your search</a>{link_arrow(R, R+"site/buy/#process", "The buying process", "color:var(--ot-ivory)")}</div></div>
  <div class="path path--sell surface-muted"><svg class="path__art" viewBox="0 0 400 260" aria-hidden="true"><use href="{R}assets/img/illustrations.svg#cape"/></svg>
    <div class="stack" style="--stack:var(--space-4)"><p class="label">If you are selling</p><h2 class="display-m answer"><span>Make your home</span><span>feel exceptional.</span></h2><p style="max-width:40ch">Thoughtful presentation. Clear pricing. A plan that fits your life.</p></div>
    <div class="cluster"><a class="btn" href="{R}site/sell/#consult">Request a market review</a>{link_arrow(R, R+"site/sell/", "How we sell")}</div></div>
</section>

<section class="section" aria-labelledby="promise-title">
  <div class="container promise">
    <p class="label">Our promise</p>
    <h2 class="display-l" id="promise-title">The same care for a first condo as for a family estate.</h2>
    <hr class="dim-rule" style="width:min(30rem,80%)">
    <div class="cols cols--3 col-rule" style="text-align:left;width:100%">
      <div><h3 class="h4">Thoughtful service</h3><p class="body-s" style="margin-top:var(--space-2)">You work with the two people who lead the team, from the first conversation through closing.</p></div>
      <div><h3 class="h4">Creative marketing</h3><p class="body-s" style="margin-top:var(--space-2)">Professional photography, a property story and editorial presentation for every listing.</p></div>
      <div><h3 class="h4">Clear guidance</h3><p class="body-s" style="margin-top:var(--space-2)">Pricing and offers grounded in comparable closed sales, never headlines.</p></div>
    </div>
  </div>
</section>

<section class="section surface-muted" aria-labelledby="comm-title">
  <div class="container">
    {section_head("Communities", "Across Massachusetts", ["Close to home,", "wherever home is."], lead=f"{len(TOWNS)} town guides, from Worcester to Wakefield, written the way we write <em>At Home</em>: researched, credited and useful.", aside=link_arrow(R, R+"site/communities/", "All town guides"), title_id="comm-title")}
    <div class="card-grid-4">{comm}</div>
    <p class="caption" style="margin-top:var(--space-4)">Lake Quannapowitt Panorama, RedSoxFan274 / Wikimedia Commons, CC BY-SA 4.0, cropped. Line drawings stand in for community photography still to be commissioned.</p>
  </div>
</section>

<section class="section" aria-labelledby="mkt-title">
  <div class="container split" style="align-items:center">
    <div style="grid-column:1/span 5" class="stack">
      <p class="label">Market insight · Massachusetts, 2025</p>
      <h2 class="display-m answer" id="mkt-title"><span>Know the market.</span><span>Love the home.</span></h2>
      <p>The Massachusetts single-family median sale price was $638,000 in 2025. Town by town it ranges widely, which is why every plan starts with comparable closed sales.</p>
      {link_arrow(R, R+"site/market/", "Compare 2025 prices by town")}
    </div>
    <div style="grid-column:7/span 6">
      <div class="stat-panel">
        <div class="stat"><span class="stat__value tabular">$638,000</span><span class="stat__label">Massachusetts single-family median</span><span class="stat__period">2025, up 3.7% from 2024</span></div>
        <div class="stat"><span class="stat__value tabular">$537,000</span><span class="stat__label">Massachusetts condominium median</span><span class="stat__period">2025</span></div>
        {home_town_stats(R)}
      </div>
      <p class="stat-source">Source: The Warren Group, MA December 2025 Sales (statewide) and Prices by Town (single-family, calendar year 2025). Not live data and not a valuation of any home.</p>
    </div>
  </div>
</section>

<section class="section surface-muted" aria-labelledby="team-title">
  <div class="container split" style="align-items:center">
    <div style="grid-column:1/span 6" class="stack">
      <p class="label">A growing team</p>
      <h2 class="display-m answer" id="team-title"><span>One standard,</span><span>shared by every agent.</span></h2>
      <p>The Oberdorfer Team works from Worcester and is welcoming new agents who share its care for clients and presentation.</p>
      <div class="cluster">{link_arrow(R, R+"site/team/", "Meet the team")}{link_arrow(R, R+"site/join/", "Join our team")}</div>
    </div>
    <div style="grid-column:8/span 5" class="cols">{agent_card_bk(R).replace('class="agent-card"','class="agent-card" style="grid-column:1/-1"')}</div>
  </div>
</section>

<section class="section" aria-labelledby="journal-title">
  <div class="container">
    {section_head("The journal", "At Home", ["Local living.", "Beautiful homes."], aside=link_arrow(R, R+"site/journal/", "Read the journal"), title_id="journal-title")}
    <div class="card-grid-3">{arts}</div>
  </div>
</section>
{cta_panel(R)}'''
    write("", R, "Worcester & Massachusetts Real Estate | The Oberdorfer Team", "Brandon and Kaitlyn Oberdorfer are a husband-and-wife real estate team based in Worcester, helping buyers and sellers across Massachusetts. Call 406-250-7620.", "Home", body, overlay=False)

def page_buy():
    R = "../../"
    cards = "".join(listing_card(R, l, f"{R}site/buy/{l['slug']}/") for l in LISTINGS)
    trail = [("Home", "site/"), ("Buy", "site/buy/")]
    body = page_hero(R, trail, ["Find the home", "that fits your life."], h1="Buy a home in Massachusetts", lead="Search homes across Massachusetts, then test the routine as well as the floor plan.", aside=f'<span class="saved-count" data-saved-count>{icon(R,"heart")}<span>0 saved</span></span>') + f'''
<section class="section--tight" id="search" aria-label="Property search">
  <div class="container">
    <form class="search-bar" role="search" data-listing-search aria-label="Search homes (demo data)">
      <div class="field"><label class="field__label" for="b-q">Location</label><input class="input" id="b-q" name="q" type="text" placeholder="Town, neighborhood or ZIP" autocomplete="off"></div>
      <div class="field"><label class="field__label" for="b-price">Price</label><select class="select" id="b-price" name="price"><option value="">Any price</option><option value="0-600000">Up to $600k</option><option value="600000-900000">$600k – $900k</option><option value="900000-99999999">$900k +</option></select></div>
      <div class="field"><label class="field__label" for="b-beds">Beds</label><select class="select" id="b-beds" name="beds"><option value="">Any</option><option value="2">2+</option><option value="3">3+</option><option value="4">4+</option></select></div>
      <div class="field"><label class="field__label" for="b-type">Home type</label><select class="select" id="b-type" name="type"><option value="">All types</option><option value="single-family">Single-family</option><option value="condominium">Condominium</option><option value="multi-family">Multi-family</option></select></div>
      <button class="btn" type="submit">{icon(R,'search')}Search</button>
    </form>
    <div class="filters" style="margin-top:var(--space-5)" role="group" aria-label="Quick filters">
      <span class="caption" style="margin-right:var(--space-2)">Refine</span>
      <label class="choice"><input type="checkbox" data-flag="open"><span>Open houses</span></label>
      <label class="choice"><input type="checkbox" data-flag="new"><span>New this week</span></label>
      <label class="choice"><input type="checkbox" data-flag="rail"><span>Near commuter rail</span></label>
      <label class="choice"><input type="checkbox" data-flag="reduced"><span>Price reduced</span></label>
      <label class="choice"><input type="checkbox" data-flag="saved"><span>Saved only</span></label>
    </div>
    <div class="results-bar">
      <p class="results-bar__count" data-results-count aria-live="polite">6 demonstration homes</p>
      <div class="cluster" style="--cluster:var(--space-3)"><label class="caption" for="b-sort">Sort</label><select class="select" id="b-sort" data-sort><option value="new">Newest</option><option value="price-asc">Price, low to high</option><option value="price-desc">Price, high to low</option><option value="beds">Most bedrooms</option></select></div>
    </div>
    <h2 class="visually-hidden">Demonstration listings</h2><div class="listing-grid" data-listing-grid>{cards}</div>
    <div class="empty-state" data-empty hidden><p class="h3">No demonstration homes match.</p><p class="body-s">Widen the price range or clear a filter. On the live site, you could also save this search and be notified when a match is listed.</p><button class="btn btn--secondary" type="button" data-clear>Clear all filters</button></div>
    <p class="form-note" style="margin-top:var(--space-7)">Demonstration data. The live search will come from the team's authorized IDX provider [to be selected], with MLS attribution and update times shown on every listing.</p>
  </div>
</section>

<section class="section surface-inverse" id="process" aria-labelledby="proc-title">
  <div class="container">
    {section_head("The buying process", "Buy", ["Three steps", "before the first showing."], title_id="proc-title")}
    <div class="cols cols--3 col-rule">
      <div class="stack"><p class="label">01 · Preapproval</p><h3 class="h4">Get ready before you find the one.</h3><p class="body-s">Connect with a lender for preapproval and review a comfortable monthly budget. We can help you get that conversation started.</p><a class="link-arrow" href="https://www.consumerfinance.gov/owning-a-home/" style="color:var(--ot-ivory)">CFPB: Get a preapproval letter {icon(R,"external")}</a></div>
      <div class="stack"><p class="label">02 · Priorities</p><h3 class="h4">Know your everyday priorities.</h3><p class="body-s">Decide what matters most: a layout that works, outdoor space, a commute, or access to the places you enjoy. Test the routine as well as the floor plan.</p></div>
      <div class="stack"><p class="label">03 · True cost</p><h3 class="h4">Look beyond the listing price.</h3><p class="body-s">Consider taxes, insurance, condominium fees, and maintenance. Talk through inspections and the documents you need to understand the property.</p></div>
    </div>
  </div>
</section>

<section class="section" id="massachusetts" aria-labelledby="ma-buy-title">
  <div class="container split" style="align-items:start">
    <div class="stack" style="grid-column:1/span 5;--stack:var(--space-5)">
      <p class="label">Buying in Massachusetts</p>
      <h2 class="display-m answer" id="ma-buy-title"><span>What is different</span><span>about buying here.</span></h2>
      <p>In Massachusetts, a signed offer to purchase is usually a binding contract, a longer purchase and sale agreement follows the home inspection, and an attorney conducts the closing. Older homes and homes on septic systems come with their own required paperwork.</p>
    </div>
    <ol class="steps" style="grid-column:7/span 6">
      <li><div><h3>The offer to purchase</h3><p class="body-s">A short standard form sets the price, deposit and contingencies, such as the home inspection and financing. Because the signed offer is usually binding on its own, the contingencies belong in it from the start.</p></div></li>
      <li><div><h3>The home inspection</h3><p class="body-s">The inspection period is set in the offer, often about a week to ten days. Repairs or credits are negotiated before the next contract is signed.</p></div></li>
      <li><div><h3>The purchase and sale agreement</h3><p class="body-s">The longer, detailed contract, usually drafted or reviewed by attorneys. It becomes the final agreement between buyer and seller.</p></div></li>
      <li><div><h3>An attorney-led closing</h3><p class="body-s">Massachusetts closings are conducted by attorneys. The closing attorney searches the title, prepares the closing documents and records the deed and mortgage at the county registry of deeds.</p></div></li>
      <li><div><h3>Homes built before 1978</h3><p class="body-s">Before you sign a purchase and sale agreement, the seller gives you the state's Property Transfer Lead Paint Notification and any lead reports for the home.</p></div></li>
      <li><div><h3>Homes on a septic system</h3><p class="body-s">A Title 5 inspection is generally required within two years before the sale. Ask for the report early.</p></div></li>
    </ol>
  </div>
  <div class="container" style="margin-top:var(--space-7)">
    <div class="panel"><p class="label">First-time buyers</p><p class="body-s" style="margin-top:var(--space-2)">The Massachusetts Housing Partnership's ONE+ Mortgage offers discounted fixed rates, no private mortgage insurance and up to $50,000 in down payment and closing cost assistance to eligible first-time buyers in the 26 Gateway Cities, Framingham, Randolph and Boston. Income, asset and credit limits apply, and a homebuyer class is required.</p><p class="caption" style="margin-top:var(--space-3)">Source: <a href="https://www.mhp.net/news/2024/one-plus-launch">Massachusetts Housing Partnership</a>. Check current terms with MHP and a participating lender.</p></div>
    <p class="caption" style="margin-top:var(--space-5)">Sources: <a href="https://www.nolo.com/legal-encyclopedia/relocating-what-know-before-buying-home-massachusetts.html">Nolo, Relocating to Massachusetts</a>; <a href="https://www.mass.gov/service-details/property-transfer-lead-paint-notification">Mass.gov, Property Transfer Lead Paint Notification</a>; <a href="https://www.mass.gov/guides/buying-or-selling-property-with-a-septic-system">Mass.gov, Buying or selling property with a septic system</a>. General information, not legal advice; your attorney confirms the details for your purchase.</p>
  </div>
</section>

<section class="section" aria-labelledby="buyer-form-title">
  <div class="container split" style="align-items:start">
    <div class="stack" style="grid-column:1/span 5;--stack:var(--space-5)">
      <p class="label">Buyer consultation</p>
      <h2 class="display-m answer" id="buyer-form-title"><span>Tell us what home</span><span>should feel like.</span></h2>
      <p>A short conversation about timing, budget and priorities, then a search shaped around you. No account needed.</p>
      <ul class="toc-mini"><li><a href="{R}site/communities/">Explore communities</a></li><li><a href="{R}site/market/">Read the market insight</a></li><li><a href="{R}site/journal/">Buyer guides in the journal</a></li></ul>
    </div>
    <div style="grid-column:7/span 6" class="panel">
      {form(R, "buyer", "Request a buyer consultation", contact_fields("bf") + '<div class="form__row">' + select("bf-timing", "When would you like to move?", ["Within 3 months","3–6 months","6–12 months","Just exploring"]) + select("bf-pre", "Preapproved?", ["Not yet","In progress","Yes"]) + '</div>' + field("bf-towns","Towns you are considering",ph="e.g. Wakefield, Melrose, Worcester",opt=True) + reach_pref("bf"), "Request my consultation", "buyer", "h3")}
    </div>
  </div>
</section>'''
    write("buy", R, "Buy a Home in Massachusetts | The Oberdorfer Team", "How buying a home works in Massachusetts, from preapproval and the offer to the purchase and sale agreement and closing. Search homes with Brandon and Kaitlyn.", "Buy", body, ld=[crumbs_ld(trail)])

def page_listing(l):
    R = "../../../"
    trail = [("Home", "site/"), ("Buy", "site/buy/"), (l["name"], f"site/buy/{l['slug']}/")]
    dark = "" if l["dark"] else " demo-illu--light"
    others = [x for x in LISTINGS if x["slug"] != l["slug"]][:3]
    more = "".join(listing_card(R, x, f"{R}site/buy/{x['slug']}/") for x in others)
    body = f'''
<section class="section--tight"><div class="container stack" style="--stack:var(--space-5)">
  {breadcrumbs(R, trail)}
  <div class="cluster" style="justify-content:space-between;align-items:end">
    <div class="stack" style="--stack:var(--space-3)"><span class="badge badge--demo">Demo listing · not a real property</span><h1 class="display-m" id="page-title">{l['name']}</h1><p class="lead" style="max-width:none">{l['town']}, Massachusetts · {l['county']} County</p></div>
    <div class="stack" style="--stack:var(--space-2);text-align:right"><p class="display-m tabular" style="font-size:var(--step-h1)">{money(l['price'])}</p><button class="btn btn--secondary btn--small" type="button" data-save="{l['name']}" data-save-id="{l['slug']}" aria-pressed="false" aria-label="Save {l['name']}">{icon(R,'heart')}Save</button></div>
  </div>
  <div class="gallery" style="aspect-ratio:16/7.5">
    <div class="demo-illu{dark}">{art(R, l['art'], 'Illustration standing in for the front elevation photo')}</div>
    <div class="demo-illu demo-illu--light">{art(R, 'townscape', 'Illustration standing in for a street view photo')}</div>
    <div class="demo-illu gallery__more">{art(R, 'cape', 'Illustration standing in for a garden photo')}<a class="btn btn--small" href="#details" style="--btn-bg:var(--ot-ivory);--btn-fg:var(--ot-navy);--btn-border:var(--ot-ivory)">All photos (demo)</a></div>
  </div>
</div></section>
<section class="section" id="details" style="padding-top:var(--space-6)"><div class="container detail">
  <div class="stack" style="--stack:var(--space-7)">
    <div class="facts"><div><b>{l['beds']}</b><span>Bedrooms</span></div><div><b>{l['baths']}</b><span>Baths</span></div><div><b>{l['sqft']:,}</b><span>Sq. ft.</span></div><div><b>{l['year']}</b><span>Built</span></div></div>
    <div class="prose stack"><h2 class="h3">The property</h2><p class="article__lede">Placeholder property story. On a real listing, Brandon and Kaitlyn write this after a visit: the morning light, the flow from kitchen to garden, the walk to the places you enjoy.</p><p>This {l['style'].lower()} in {l['town']} is a demonstration created to show how a listing page is laid out. Every detail on this page is illustrative, and the drawing stands in for professional photography.</p></div>
    <div><h2 class="h3" style="margin-bottom:var(--space-4)">Details</h2><table class="spec-table"><tbody>
      <tr><th scope="row">Home type</th><td>{l['kind']}</td></tr><tr><th scope="row">Style</th><td>{l['style']}</td></tr><tr><th scope="row">Lot</th><td>{l['lot']}</td></tr>
      <tr><th scope="row">Commuter rail nearby</th><td>{"Yes (demo)" if l['rail'] else "No (demo)"}</td></tr><tr><th scope="row">Taxes, fees</th><td>[From MLS data]</td></tr><tr><th scope="row">MLS number</th><td>[From MLS data]</td></tr>
    </tbody></table></div>
    <div class="map-frame"><svg viewBox="0 0 800 500" role="img" aria-label="Stylised map placeholder"><rect width="800" height="500" fill="#E9E2D5"/><g stroke="#FBF9F5" stroke-width="10" fill="none" stroke-linecap="round"><path d="M0 300C200 280 360 330 800 260"/><path d="M300 0C320 160 280 340 330 500"/><path d="M90 0L210 500"/></g><g stroke="#DDD3C2" stroke-width="4" fill="none"><path d="M0 120H500M0 200C150 190 300 220 480 180M120 380H800"/></g><g transform="translate(338 284)"><circle r="20" fill="#071D2D"/><circle r="27" fill="none" stroke="#B49A65"/></g></svg><p class="map-frame__note"><span class="badge badge--demo">Illustrative map</span></p></div>
    <p class="form-note">Listing courtesy of [Listing brokerage]. Information deemed reliable but not guaranteed. Data from [MLS name], last updated [timestamp]. Demonstration listing.</p>
  </div>
  <aside class="detail__aside" aria-label="Contact about this home">
    <div class="panel">{form(R, "show", "Request a showing", contact_fields("sh") + select("sh-when","Preferred time",["This week, weekday","This week, weekend","Next week","Flexible"]) + reach_pref("sh"), "Request a showing", "showing", "h2", note=f"Your request is attached to {l['name']} and goes to Brandon and Kaitlyn.")}</div>
    <div class="panel" style="grid-template-columns:5rem 1fr;align-items:center;gap:var(--space-4)"><img src="{R}assets/img/photo/team-portrait.webp" alt="" style="aspect-ratio:1;object-fit:cover;object-position:50% 15%"><div><p class="h5">Brandon &amp; Kaitlyn Oberdorfer</p><a href="tel:{TEL}" class="body-s">{PHONE}</a></div></div>
  </aside>
</div></section>
<section class="section surface-muted"><div class="container">{section_head("More homes", "Demonstration listings", ["You may also like"], title_id="more-title")}<div class="listing-grid">{more}</div></div></section>'''
    write(f"buy/{l['slug']}", R, f"{l['name']} (demo) · {l['town']} · The Oberdorfer Team", f"Demonstration listing page: {l['style']} in {l['town']}, Massachusetts.", "Buy", body, ld=[crumbs_ld(trail)], index=False)

def page_sell():
    R = "../../"
    trail = [("Home", "site/"), ("Sell", "site/sell/")]
    faq = [("When should I list?", "Agree on a pricing strategy before choosing a launch date. Your timing, showing preferences and next move shape the calendar, and a clear preparation plan makes the many decisions easier to manage."),
           ("How do you price a home?", "With recent closed sales that resemble your property in location, size, condition and style. Lot size, renovation quality, layout, parking and condominium fees all belong in the comparison. A listing price is a starting point; a closed sale shows what a buyer actually paid."),
           ("Do I need to stage?", "Usually a few small changes matter most: clear the entry, simplify the rooms, and show the home in good light. We help you decide what is worth doing and what is not."),
           ("What does the marketing include?", "Professional photography, a written property story and editorial presentation across the website, print and social channels. The specific package is confirmed in your consultation.")]
    faq_html = "".join(f'<details{" open" if i==0 else ""}><summary>{q}</summary><div class="accordion__body body-s"><p>{a}</p></div></details>' for i,(q,a) in enumerate(faq))
    faq_ld = {"@context":"https://schema.org","@type":"FAQPage","mainEntity":[{"@type":"Question","name":q,"acceptedAnswer":{"@type":"Answer","text":a}} for q,a in faq]}
    body = page_hero(R, trail, ["Make your home", "feel exceptional."], h1="Sell your home in Massachusetts", lead="Thoughtful presentation. Clear pricing. A plan that fits your life.", navy=True, aside=f'<a class="btn btn--gold" href="#consult">Request a market review</a>') + f'''
<section class="section"><div class="container">
  {section_head("If you are selling", "Sell", ["Three decisions", "that shape a sale."], title_id="steps-title")}
  <ol class="steps" style="grid-template-columns:repeat(auto-fit,minmax(min(100%,18rem),1fr));display:grid;gap:var(--space-7) var(--gutter)">
    <li><div><h3>Start with the right comparisons.</h3><p class="body-s">Review recent closed sales with similar location, style, condition, and living area. Agree on a pricing strategy before choosing a launch date.</p></div></li>
    <li><div><h3>Create a strong first impression.</h3><p class="body-s">Clear the entry, simplify the rooms, and show the home in good light. Let professional photography and a thoughtful property story lead the presentation.</p></div></li>
    <li><div><h3>Build a plan around your life.</h3><p class="body-s">Discuss your timing, showing preferences, and next move. A clear preparation calendar makes the many decisions easier to manage.</p></div></li>
  </ol>
</div></section>
<section class="section surface-muted" id="prepare"><div class="container split" style="align-items:center">
  <div style="grid-column:1/span 6" class="stack"><p class="label">Preparation</p><h2 class="display-m answer"><span>The 30-minute</span><span>fall edit.</span></h2><p>Small, seasonal changes before photography and showings.</p></div>
  <div style="grid-column:7/span 6" class="triad" >
    <div><p class="label">The welcome</p><p class="h5">Sweep the entry and style one simple seasonal planter.</p></div>
    <div><p class="label">The light</p><p class="h5">Open the shades and give the windows a quick clean.</p></div>
    <div><p class="label">The calm</p><p class="h5">Clear one surface and add a warm, soft layer.</p></div>
  </div>
</div></section>
<section class="section"><div class="container">
  {section_head("Marketing and launch", "Sell", ["Every home,", "presented like a feature."], lead="The same editorial care that goes into our <em>At Home</em> publications goes into every listing.", title_id="mkt-title")}
  <div class="cols cols--4 col-rule">
    <div><h3 class="h4">Pricing strategy</h3><p class="body-s" style="margin-top:var(--space-2)">Comparable closed sales, not headlines, with the reasoning shared in writing.</p></div>
    <div><h3 class="h4">Photography</h3><p class="body-s" style="margin-top:var(--space-2)">Professional photography in natural light, with straight lines and lights on.</p></div>
    <div><h3 class="h4">Property story</h3><p class="body-s" style="margin-top:var(--space-2)">A concrete, written story of how the home lives, with no generic superlatives.</p></div>
    <div><h3 class="h4">Launch</h3><p class="body-s" style="margin-top:var(--space-2)">A launch date chosen around your calendar, with showing preferences agreed up front.</p></div>
  </div>
</div></section>
<section class="section surface-muted" id="massachusetts" aria-labelledby="ma-sell-title"><div class="container split" style="align-items:start">
  <div style="grid-column:1/span 5" class="stack"><p class="label">Selling in Massachusetts</p><h2 class="display-m answer" id="ma-sell-title"><span>What sellers here</span><span>need before closing.</span></h2><p>Massachusetts sellers usually need a smoke and carbon monoxide certificate from the local fire department, a Title 5 report if the home has a septic system, and the state lead paint notification if the home was built before 1978. The deed excise is paid at closing.</p></div>
  <div style="grid-column:7/span 6"><table class="spec-table"><tbody>
    <tr><th scope="row">Smoke and CO certificate</th><td>The local fire department inspects the smoke and carbon monoxide alarms and issues a Certificate of Compliance before the sale (M.G.L. c. 148, §§ 26F and 26F½). Book it early; buyers and lenders expect it at closing.</td></tr>
    <tr><th scope="row">Title 5 septic inspection</th><td>For homes on a septic system, an inspection within two years before the sale. If weather prevents it, it can take place up to six months after.</td></tr>
    <tr><th scope="row">Lead paint notification</th><td>For homes built before 1978, give buyers the Property Transfer Lead Paint Notification and any lead reports before the purchase and sale agreement is signed.</td></tr>
    <tr><th scope="row">Deed excise</th><td>Under M.G.L. c. 64D, $2 for each $500 of the price; Barnstable County has its own rate. Your closing attorney confirms the exact amount.</td></tr>
  </tbody></table>
  <p class="caption" style="margin-top:var(--space-4)">Sources: <a href="https://www.mass.gov/doc/consumer-guide-to-smoke-detectors-when-selling-home/download">Mass.gov, Consumer guide to smoke detectors when selling a home</a>; <a href="https://www.mass.gov/guides/buying-or-selling-property-with-a-septic-system">Mass.gov, septic systems</a>; <a href="https://www.mass.gov/service-details/property-transfer-lead-paint-notification">Mass.gov, lead paint notification</a>; <a href="https://malegislature.gov/Laws/GeneralLaws/PartI/TitleIX/Chapter64D/Section1">M.G.L. c. 64D, § 1</a>. General information, not legal advice.</p></div>
</div></section>
<section class="section " id="consult"><div class="container split" style="align-items:start">
  <div style="grid-column:1/span 5" class="stack"><p class="label">Seller consultation</p><h2 class="display-m answer" id="consult-title"><span>Ask for a</span><span>property-specific review.</span></h2><p>The townwide median is a starting point. Your best comparisons are recent closed sales that resemble your home. We prepare them before we meet.</p><div class="accordion" style="margin-top:var(--space-5)">{faq_html}</div></div>
  <div style="grid-column:7/span 6" class="panel">{form(R, "seller", "Request a market review", contact_fields("sf") + field("sf-address","Property address",req=True,auto="street-address",hint="Used only to prepare comparable sales for your home.") + '<div class="form__row">' + select("sf-type","Home type",["Single-family","Condominium","Multi-family","Other"]) + select("sf-timing","Timing",["Within 3 months","3–6 months","6–12 months","Just curious"]) + '</div>' + reach_pref("sf") + field("sf-msg","Anything we should know?","textarea",opt=True,ph="Recent updates, timing, your next move…"), "Request my market review", "seller", "h3")}</div>
</div></section>
{cta_panel(R)}'''
    write("sell", R, "Sell Your Home in Massachusetts | The Oberdorfer Team", "Pricing from closed sales, the certificates Massachusetts sellers need, preparation, photography and launch. Request a market review from Brandon and Kaitlyn.", "Sell", body, ld=[crumbs_ld(trail), faq_ld])

# --------------------------------------------------------------------------- communities and market
# Town data lives in tools/data/communities.json: one record per guide, researched and sourced
# (see tools/data/README.md). Region order and tiers come from REGIONS below. The tiers are the
# proposed priority from the October 2026 SEO review; the team has not confirmed the final list.
DATA = os.path.join(ROOT, "tools", "data")
with open(os.path.join(DATA, "communities.json")) as _f:
    TOWNS = json.load(_f)
TOWN_BY_SLUG = {t["slug"]: t for t in TOWNS}
REGIONS = [
    ("central", "Central Massachusetts", "Worcester and the towns around it, home base for the team."),
    ("metrowest", "MetroWest", "Between Worcester and Boston, along the Massachusetts Turnpike and the Framingham/Worcester Line."),
    ("north", "North of Boston", "Wakefield, where the team publishes <em>At Home</em>, its neighbors, and the Merrimack Valley."),
    ("boston", "Greater Boston", "Boston itself and its neighbors, from Cambridge and Somerville to the villages of Newton and the Quincy shore."),
    ("southshore", "South Shore", "South of Boston along the coast and the Old Colony lines, from Braintree to Plymouth."),
    ("cape", "Cape Cod", "The Cape's villages and harbors, from Falmouth on Buzzards Bay to Barnstable and Hyannis."),
    ("southcoast", "South Coast", "The mill cities and working harbors of Bristol County, now on the commuter rail."),
    ("west", "Pioneer Valley", "The Connecticut River valley in Western Massachusetts."),
    ("berkshires", "The Berkshires", "The hill towns and mill towns of Berkshire County, from Pittsfield to the southern Berkshires."),
]
WARREN = "The Warren Group, MA December 2025 Sales, Prices by Town (single-family, calendar year 2025)"
WARREN_URL = "https://www.thewarrengroup.com/wp-content/uploads/2026/01/MA-December-2025-Sales_Town.pdf"
ART_CYCLE = ["colonial", "cape", "triple-decker", "townscape"]

def pct(a, b):
    if not a or not b: return None
    return (b - a) / a * 100

def fmt_pct(ch):
    if ch is None: return "n/a"
    return "No change" if abs(ch) < 0.05 else f"{ch:+.1f}%"

def town_art(t, i=0):
    return t.get("art") or ART_CYCLE[i % len(ART_CYCLE)]

def community_card(R, t, i=0):
    m = t.get("market") or {}
    med = f" · 2025 median {money(m['sf_median_2025'])}" if m.get("sf_median_2025") and m.get("citywide", True) else ""
    if t["slug"] == "wakefield":
        media = f'<img src="{R}assets/img/photo/lake-quannapowitt.webp" alt="" loading="lazy" style="object-position:30% 50%">'
    else:
        media = f'<div class="demo-illu" style="position:absolute;inset:0">{art(R, town_art(t, i))}</div>'
    return f'''<a class="community-card" href="{R}site/communities/{t['slug']}/">{media}<div class="community-card__body"><span class="community-card__meta">{t['county']}{med}</span><span class="community-card__name">{t['name']}</span></div></a>'''

def towns_in(region_key):
    return [t for t in TOWNS if t["region_key"] == region_key]

def page_communities():
    R = "../../"
    trail = [("Home", "site/"), ("Communities", "site/communities/")]
    regions = ""
    for i, (key, name, blurb) in enumerate(REGIONS):
        ts = towns_in(key)
        if not ts: continue
        cards = "".join(community_card(R, t, j) for j, t in enumerate(ts))
        muted = " surface-muted" if i % 2 else ""
        regions += f'''<section class="section{muted}" id="{key}" aria-labelledby="{key}-title"><div class="container">
  {section_head(name, f"{len(ts)} guide{'s' if len(ts) != 1 else ''}", [name + "."], lead=blurb, title_id=key + "-title")}
  <div class="card-grid-3" style="gap:var(--gutter)">{cards}</div>
</div></section>'''
    cities = [t for t in TOWNS if t.get("neighborhoods")]
    nb_cols = "".join(f'''<div class="stack" style="--stack:var(--space-3)"><h3 class="h4"><a href="{R}site/communities/{t["slug"]}/">{t["name"]}</a></h3><ul class="toc-mini">{"".join(f'<li><a href="{R}site/communities/{t["slug"]}/{n["slug"]}/">{n["name"]}</a></li>' for n in t["neighborhoods"])}</ul></div>''' for t in cities)
    if cities:
        regions += f'''<section class="section" id="neighborhoods" aria-labelledby="nbh-title"><div class="container">
  {section_head("Neighborhood guides", f"{sum(len(t['neighborhoods']) for t in cities)} neighborhoods", ["Street by street", "in the cities."], lead="Closer looks at the neighborhoods of Worcester, home base for the team, and Boston, each with its own streets, homes, transit and history.", title_id="nbh-title")}
  <div class="cols cols--{max(2, len(cities))} col-rule">{nb_cols}</div>
</div></section>'''
    toc = "".join(f'<li><a href="#{k}">{n}</a></li>' for k, n, _ in REGIONS if towns_in(k)) + ('<li><a href="#neighborhoods">Neighborhoods</a></li>' if cities else "")
    names = ", ".join(t["name"] for t in TOWNS)
    body = page_hero(R, trail, ["Close to home,", "wherever home is."], h1="Massachusetts community guides",
                     lead=f"The Oberdorfer Team works from Worcester and helps buyers and sellers across Massachusetts. These {len(TOWNS)} guides cover housing, neighborhoods, commuting, schools and 2025 home prices, each with its sources.",
                     aside='<span class="badge badge--proposed">Proposed list · team to confirm</span>') + f'''
<section class="section--tight"><div class="container"><nav aria-label="Regions"><ul class="toc-mini" style="display:flex;flex-wrap:wrap;gap:var(--space-3) var(--space-6)">{toc}</ul></nav></div></section>
{regions}
<section class="section"><div class="container split" style="align-items:start">
  <div class="stack" style="grid-column:1/span 6"><p class="label">Elsewhere in Massachusetts</p><h2 class="h2">Not seeing your town?</h2><p>The team helps clients across the state. Guides are added one at a time as the team works in a town, each researched and sourced, never generated in bulk.</p>{link_arrow(R, R+"site/contact/", "Ask about your town")}</div>
  <div class="stack" style="grid-column:8/span 5"><p class="caption">Line drawings stand in for community photography still to be commissioned. Wakefield photo: Lake Quannapowitt Panorama, RedSoxFan274 / Wikimedia Commons, CC BY-SA 4.0, cropped.</p><p class="caption">Home prices: {WARREN}. <a href="{R}site/market/">All towns compared</a>.</p></div>
</div></section>
{cta_panel(R, "Exploring a move? <em>Let's compare towns together.</em>", "Relocating within Massachusetts")}'''
    item_list = {"@context": "https://schema.org", "@type": "ItemList", "name": "Massachusetts community guides",
                 "itemListElement": [{"@type": "ListItem", "position": i + 1, "name": f"{t['name']}, Massachusetts", "url": prod(f"site/communities/{t['slug']}/")} for i, t in enumerate(TOWNS)]}
    write("communities", R, "Massachusetts Town Guides | The Oberdorfer Team",
          f"Local guides to {len(TOWNS)} Massachusetts communities, from Worcester and Boston to Cape Cod and the Berkshires: housing, commuting, schools and 2025 home prices.",
          "Communities", body, ld=[crumbs_ld(trail), item_list])

def fmt_people(n): return f"{n:,}"

def first_sentence(text):
    return re.split(r'(?<=[a-z0-9\)])\. (?=[A-Z])', text)[0].rstrip(".") + "."

def tax_fact(t):
    """'$13.70 per $1,000 (FY2026 residential)' from a town's sourced tax record, or None."""
    x = t.get("tax") or {}
    if not x.get("residential_rate"): return None
    s = f"${x['residential_rate']:.2f} per $1,000 ({x.get('fy', '')} residential)".replace("( ", "(")
    if x.get("residential_exemption"): s += "; residential exemption available"
    return s + ' <span class="badge badge--source">Mass. DLS</span>' if "dls" in (x.get("source") or "").lower() else s

def town_title(name):
    """About 60 characters, town first, brand last (the SEO convention)."""
    for t in (f"Living in {name}, MA: Homes and Prices | The Oberdorfer Team", f"Living in {name}, MA: Town Guide | The Oberdorfer Team",
              f"Living in {name}, MA | The Oberdorfer Team"):
        if len(t) <= 62: return t
    return t

def town_desc(t):
    """Meta description from the town's own sourced answer: its first sentence plus the
    longest tail that keeps it within about 160 characters. A record can override with "meta"."""
    if t.get("meta"): return t["meta"]
    first = re.split(r'(?<=[a-z0-9\)])\. (?=[A-Z])', t["answer"])[0].rstrip(".") + "."
    for tail in (" Neighborhoods, transit, schools and 2025 home prices, with sources.",
                 " Housing, transit, schools and 2025 prices, with sources.",
                 " Homes, transit and 2025 prices, with sources.",
                 " Homes and 2025 prices, with sources."):
        if len(first + tail) <= 160: return first + tail
    return first

def page_town(t):
    R = "../../../"
    slug, name = t["slug"], t["name"]
    url_rel = f"site/communities/{slug}/"
    trail = [("Home", "site/"), ("Communities", "site/communities/"), (name, url_rel)]
    m = t.get("market") or {}
    c = t.get("commute") or {}
    sch = t.get("schools") or {}
    rail = c.get("rail") or []
    rail_txt = "; ".join(f"{r['line']} ({', '.join(r['stations'])})" for r in rail) if rail else "No commuter rail station in town"
    facts = [("County", t["county"]), ("Region", t["region"])]
    if t.get("population_2020"): facts.append(("Population", f"{fmt_people(t['population_2020'])} (2020 Census)"))
    facts.append(("Rail", rail_txt))
    if c.get("highways"): facts.append(("Highways", ", ".join(c["highways"][:4])))
    if sch.get("district"): facts.append(("Schools", f'<a href="{sch["dese_url"]}">{sch["district"]}</a> (DESE profile)'))
    if m.get("sf_median_2025"): facts.append(("Single-family median" if m.get("citywide", True) else f"Single-family median ({name} row)", f"{money(m['sf_median_2025'])} in 2025 <span class=\"badge badge--source\">Warren Group</span>"))
    if tax_fact(t): facts.append(("Property tax", tax_fact(t)))
    facts.append(("Guide updated", f'<time datetime="{UPDATED}">October 22, 2026</time>'))
    facts_html = "".join(f"<dt>{k}</dt><dd>{v}</dd>" for k, v in facts)
    overview = "".join(f"<p>{p}</p>" for p in t["overview"])
    housing = ""
    if t.get("housing") and t["housing"].get("text"):
        hs = t["housing"].get("source")
        housing = f'<h3 class="h4" style="margin-top:var(--space-6)">Homes in {name}</h3><p>{t["housing"]["text"]}</p>'
    villages = ""
    nbs = t.get("neighborhoods") or []
    if nbs:
        # Cities with neighborhood guides: one card per guide, then any village without its own page.
        have = {n["name"].lower() for n in nbs}
        items = "".join(f'<div><h3 class="h4"><a href="{R}site/communities/{slug}/{n["slug"]}/">{n["name"]}</a></h3><p class="body-s" style="margin-top:var(--space-2)">{first_sentence(n["answer"])}</p><p class="body-s" style="margin-top:var(--space-2)">{link_arrow(R, f"{R}site/communities/{slug}/{n["slug"]}/", f"The {n["name"]} guide")}</p></div>' for n in nbs)
        items += "".join(f'<div><h3 class="h4">{v["name"]}</h3><p class="body-s" style="margin-top:var(--space-2)">{v["line"]}</p></div>' for v in t.get("villages", []) if v["name"].lower() not in have)
        villages = f'''<section class="section surface-muted" id="neighborhoods" aria-labelledby="nb-title"><div class="container">
  {section_head("Neighborhood guides", f"{len(nbs)} {name} neighborhoods", ["Where you might", f"live in {name}."], title_id="nb-title")}
  <div class="cols cols--3 col-rule">{items}</div>
</div></section>'''
    elif t.get("villages"):
        items = "".join(f'<div><h3 class="h4">{v["name"]}</h3><p class="body-s" style="margin-top:var(--space-2)">{v["line"]}</p></div>' for v in t["villages"])
        villages = f'''<section class="section surface-muted" id="neighborhoods" aria-labelledby="nb-title"><div class="container">
  {section_head("Neighborhoods and villages", name, ["Where you might", f"live in {name}."], title_id="nb-title")}
  <div class="cols cols--3 col-rule">{items}</div>
</div></section>'''
    rail_list = "".join(f"<li><b>{r['line']}</b>: {', '.join(r['stations'])}</li>" for r in rail)
    rail_block = f'<ul class="body-s" style="padding-left:1.1rem;display:grid;gap:var(--space-2)">{rail_list}</ul>' if rail else ""
    places = "".join(f'<div><h3 class="h4">{p["name"]}</h3><p class="body-s" style="margin-top:var(--space-2)">{p["line"]}</p></div>' for p in t.get("places", []))
    hist = ""
    if t.get("history"):
        hist = '<div class="history">' + "".join(f'<div><b>{h["year"]}</b><p class="body-s">{h["text"]}</p></div>' for h in t["history"][:3]) + "</div>"
    # market table
    rows = ""
    if m.get("sf_median_2025"):
        ch = pct(m.get("sf_median_2024"), m["sf_median_2025"])
        chs = fmt_pct(ch)
        rows += f'<tr><th scope="row">Single-family median sale price</th><td class="tabular">{money(m["sf_median_2024"]) if m.get("sf_median_2024") else "n/a"}</td><td class="tabular">{money(m["sf_median_2025"])}</td><td class="tabular">{chs}</td></tr>'
    if m.get("sf_sales_2025"):
        ch = pct(m.get("sf_sales_2024"), m["sf_sales_2025"])
        chs = fmt_pct(ch)
        rows += f'<tr><th scope="row">Single-family sales</th><td class="tabular">{fmt_people(m["sf_sales_2024"]) if m.get("sf_sales_2024") else "n/a"}</td><td class="tabular">{fmt_people(m["sf_sales_2025"])}</td><td class="tabular">{chs}</td></tr>'
    if m.get("condo_median_2025"):
        ch = pct(m.get("condo_median_2024"), m["condo_median_2025"])
        chs = fmt_pct(ch)
        rows += f'<tr><th scope="row">Condominium median sale price</th><td class="tabular">{money(m["condo_median_2024"]) if m.get("condo_median_2024") else "n/a"}</td><td class="tabular">{money(m["condo_median_2025"])}</td><td class="tabular">{chs}</td></tr>'
    market_html = ""
    vrows = m.get("village_rows") or []
    if not rows and vrows:
        # Some towns (Barnstable) are reported by village, with no town-wide row.
        body_rows = "".join(f'<tr><th scope="row">{v["name"]}</th><td class="tabular">{fmt_people(v["sf_sales_2025"])}</td><td class="tabular">{money(v["sf_median_2024"])}</td><td class="tabular">{money(v["sf_median_2025"])}</td><td class="tabular">{fmt_pct(pct(v["sf_median_2024"], v["sf_median_2025"]))}</td></tr>' for v in sorted(vrows, key=lambda v: v["sf_median_2025"]))
        market_html = f'''<div class="table-scroll" tabindex="0" role="region" aria-label="{name} single-family prices by village"><table class="spec-table market-table"><thead><tr><th scope="col">Village</th><th scope="col">Sales, 2025</th><th scope="col">Median, 2024</th><th scope="col">Median, 2025</th><th scope="col">Change</th></tr></thead><tbody>{body_rows}</tbody></table></div>
<p class="stat-source">Source: <a href="{WARREN_URL}">The Warren Group, MA December 2025 Sales, Prices by Town</a>, single-family. The report lists {name} by village, with no town-wide figure, so none is given here. Calendar years 2024 and 2025. Not live data and not a valuation of any home.</p>'''
    if rows:
        market_html = f'''<table class="spec-table market-table"><thead><tr><th scope="col">Calendar year</th><th scope="col">2024</th><th scope="col">2025</th><th scope="col">Change</th></tr></thead><tbody>{rows}</tbody></table>
<p class="stat-source">Source: <a href="{WARREN_URL}">The Warren Group, MA December 2025 Sales, Prices by Town</a>. Geography: {m.get("geography") or f"town of {name}"}. Year-to-date figures through December, so calendar years 2024 and 2025. Not live data and not a valuation of any home.</p>'''
    elif not market_html:
        market_html = '<p class="placeholder-block">Town-level sale prices to be added from a dated, sourced report.</p>'
    extra_market = t.get("extra_market_html", "")
    # FAQ (visible; no FAQPage markup: Google retired FAQ rich results in May 2026)
    faq = t.get("faq", [])
    faq_html = "".join(f'<details{" open" if i == 0 else ""}><summary>{f["q"]}</summary><div class="accordion__body body-s"><p>{f["a"]}</p></div></details>' for i, f in enumerate(faq))
    # nearby guides
    near = []
    for n in t.get("nearby", []):
        k = n.lower().replace(" ", "-")
        if k in TOWN_BY_SLUG and k != slug: near.append(TOWN_BY_SLUG[k])
    near = near[:4]
    near_html = ""
    if near:
        near_html = "".join(f'<li><a href="{R}site/communities/{x["slug"]}/">{x["name"]}</a></li>' for x in near)
        near_html = f'<p class="label" style="margin-top:var(--space-6)">Nearby guides</p><ul class="toc-mini">{near_html}</ul>'
    # More guides in the same region, so every guide links to (and is linked from) its region.
    region_more = [x for x in towns_in(t["region_key"]) if x["slug"] != slug and x not in near][:6]
    if region_more:
        items = "".join(f'<li><a href="{R}site/communities/{x["slug"]}/">{x["name"]}</a></li>' for x in region_more)
        rname = "the " + t["region"][4:] if t["region"].startswith("The ") else t["region"]
        near_html += f'<p class="label" style="margin-top:var(--space-6)">More guides in {rname}</p><ul class="toc-mini">{items}</ul><p class="body-s" style="margin-top:var(--space-3)"><a href="{R}site/communities/#{t["region_key"]}">Every guide in {rname}</a></p>'
    homes = [l for l in LISTINGS if l["town"] == name]
    homes_html = ""
    if homes:
        homes_html = f'''<section class="section" id="homes"><div class="container">{section_head("Homes for sale", "Demonstration listings", [f"Homes in {name}."], aside=link_arrow(R, R+f"site/buy/?q={slug}", f"Search {name}"), title_id="homes-title")}<div class="listing-grid">{"".join(listing_card(R, l, f"{R}site/buy/{l['slug']}/") for l in homes)}</div></div></section>'''
    # sources
    srcs = []
    def addsrc(label, u):
        if u and u not in [x[1] for x in srcs]: srcs.append((label, u))
    addsrc(f"Town of {name}" if "city" not in t.get("official_site", "") else f"City of {name}", t.get("official_site"))
    addsrc("2020 Census population", t.get("population_source"))
    addsrc("MBTA commuter rail", c.get("rail_source"))
    addsrc("Massachusetts DESE district profile", sch.get("dese_url"))
    addsrc("Massachusetts DESE regional district profile", sch.get("dese_url_regional"))
    addsrc("The Warren Group, 2025 sales by town", WARREN_URL)
    if t.get("housing"): addsrc("Housing", t["housing"].get("source"))
    if t.get("tax"): addsrc(f"Property tax rates, {t['tax'].get('fy', '')}".strip(", "), t["tax"].get("source"))
    for v in t.get("villages", []): addsrc(v["name"], v.get("source"))
    for p in t.get("places", []): addsrc(p["name"], p.get("source"))
    for h in t.get("history", []): addsrc(f"History ({h['year']})", h.get("source"))
    src_html = "".join(f'<li><a href="{u}">{e(l)}</a></li>' for l, u in srcs)
    photo = t.get("photo")
    hero = page_hero(R, trail, t["headline"], h1=f"Living in {name}, MA", lead=t["answer"],
                     photo=photo, photo_alt=t.get("photo_alt", ""), focal=t.get("focal", "50% 50%"))
    commute_txt = c.get("text", "")
    body = hero + f'''
<section class="section" aria-labelledby="about-title"><div class="container split" style="align-items:start">
  <div class="stack prose" style="grid-column:1/span 7;--stack:var(--space-4)">
    {f'<p class="caption caption--serif">{t["photo_credit"]}</p>' if t.get("photo_credit") else ""}
    <h2 class="h2" id="about-title">About {name}</h2>
    {overview}
    {housing}
  </div>
  <aside class="stack" style="grid-column:9/span 4;--stack:var(--space-5)" aria-label="{name} at a glance">
    <dl class="quick-facts quick-facts--aside">{facts_html}</dl>
    <div class="panel"><p class="h5">Your {name} guides</p><p class="body-s">Brandon &amp; Kaitlyn Oberdorfer · Based in Worcester</p><div class="cluster"><a class="btn btn--small" href="#contact-town">Ask about {name}</a><a class="link-arrow" href="tel:{TEL}">{PHONE}</a></div></div>
  </aside>
</div></section>
{villages}
<section class="section" id="commute" aria-labelledby="commute-title"><div class="container split" style="align-items:start">
  <div class="stack" style="grid-column:1/span 6;--stack:var(--space-4)"><p class="label">Getting around</p><h2 class="h2" id="commute-title">Commuting from {name}</h2><p>{commute_txt}</p>{rail_block}<p class="caption">Check current schedules with the <a href="https://www.mbta.com/schedules/commuter-rail">MBTA</a>; compare the trip from each address before you buy.</p></div>
  <div class="stack" style="grid-column:8/span 5;--stack:var(--space-4)"><p class="label">Schools</p><h2 class="h2">Public schools</h2><p>{name} is served by {sch.get("district", "[district to confirm]")}. Review the district's profile with the Massachusetts Department of Elementary and Secondary Education, and confirm school assignment for any address with the district.</p>{link_arrow(R, sch.get("dese_url", "#"), "DESE district profile")}{link_arrow(R, sch["dese_url_regional"], "DESE regional district profile") if sch.get("dese_url_regional") else ""}</div>
</div></section>
<section class="section surface-muted" id="places" aria-labelledby="places-title"><div class="container">
  {section_head("Things to do", name, ["Places to know", f"in {name}."], title_id="places-title")}
  <div class="cols cols--3 col-rule">{places}</div>
  {f'<h2 class="h3" style="margin-top:var(--space-8);margin-bottom:var(--space-5)">History</h2>{hist}' if hist else ""}
</div></section>
<section class="section" id="market" aria-labelledby="market-title"><div class="container split" style="align-items:start">
  <div class="stack" style="grid-column:1/span 7;--stack:var(--space-4)"><p class="label">Home prices</p><h2 class="h2" id="market-title">The {name} housing market</h2>{market_html}{extra_market}</div>
  <div class="stack" style="grid-column:9/span 4;--stack:var(--space-4)"><h3 class="h4">Read it for your own home</h3><p class="body-s">A townwide median is a starting point. Your best comparisons are recent closed sales that resemble your home in location, size, condition and style.</p>{link_arrow(R, R+"site/sell/#consult", "Request a market review")}{link_arrow(R, R+"site/market/", "Compare all towns")}</div>
</div></section>
{homes_html}
<section class="section surface-muted" id="questions" aria-labelledby="faq-title"><div class="container split" style="align-items:start">
  <div class="stack" style="grid-column:1/span 5"><p class="label">Questions</p><h2 class="display-m answer" id="faq-title"><span>Asked about</span><span>{name}.</span></h2>{near_html}</div>
  <div class="accordion" style="grid-column:7/span 6">{faq_html}</div>
</div></section>
<section class="section" id="contact-town" aria-labelledby="ct-title"><div class="container split" style="align-items:start">
  <div class="stack" style="grid-column:1/span 5;--stack:var(--space-5)"><p class="label">Thinking about {name}?</p><h2 class="display-m answer" id="ct-title"><span>Let's talk about</span><span>your move.</span></h2><p>Buying in {name}, selling here, or comparing it with nearby towns: tell us what you are planning and we will reply by your preferred method.</p><a class="cta-panel__phone" href="tel:{TEL}" style="color:var(--ot-navy);font-size:var(--step-h2)">{PHONE}</a></div>
  <div class="panel" style="grid-column:7/span 6">{form(R, "town", f"Ask about {name}", contact_fields("tw") + select("tw-topic", "I'm interested in", [f"Buying in {name}", f"Selling in {name}", "Both", "Comparing towns"]) + field("tw-msg", "Message", "textarea", opt=True) + reach_pref("tw"), "Send my question", f"community:{slug}", "h3")}</div>
</div></section>
<section class="section--tight" aria-labelledby="src-title"><div class="container"><h2 class="label" id="src-title">Sources for this guide</h2><ul class="source-list caption">{src_html}</ul><p class="caption" style="margin-top:var(--space-3)">Researched by The Oberdorfer Team. Updated <time datetime="{UPDATED}">October 22, 2026</time>. Places and facts can change; check with the source before you rely on them.</p></div></section>'''
    city = {"@type": "City", "name": f"{name}, Massachusetts",
            "containedInPlace": [{"@type": "AdministrativeArea", "name": f"{t['county']}, Massachusetts"}, {"@type": "State", "name": "Massachusetts"}]}
    if t.get("official_site"): city["sameAs"] = [t["official_site"]]
    page_ld = {"@context": "https://schema.org", "@type": "WebPage", "@id": prod(url_rel) + "#page", "url": prod(url_rel),
               "name": f"Living in {name}, MA", "about": city, "dateModified": UPDATED, "inLanguage": "en-US",
               "publisher": {"@id": TEAM_ID}, "mainEntityOfPage": prod(url_rel)}
    title = town_title(name)
    desc = town_desc(t)
    write(f"communities/{slug}", R, title, desc, "Communities", body, ld=[crumbs_ld(trail), page_ld])

def nbhd_title(name, city):
    """About 60 characters, neighborhood and city first, brand last."""
    for t in (f"Living in {name}, {city}, MA | The Oberdorfer Team",
              f"{name}, {city}: Neighborhood Guide | The Oberdorfer Team",
              f"{name}, {city} MA | The Oberdorfer Team"):
        if len(t) <= 62: return t
    return t

def nbhd_desc(n):
    if n.get("meta"): return n["meta"]
    first = first_sentence(n["answer"])
    for tail in (" Streets, homes, transit and places to know, with sources.",
                 " Homes, transit and places to know, with sources.",
                 " Homes and transit, with sources."):
        if len(first + tail) <= 160: return first + tail
    return first

def nb_condo_row(nm):
    """The neighborhood's own Warren Group condominium row, when the record has one."""
    if not nm.get("condo_median_2025"):
        return ""
    return (f'<tr><th scope="row">Condominium median sale price</th><td class="tabular">{money(nm["condo_median_2024"]) if nm.get("condo_median_2024") else "n/a"}</td>'
            f'<td class="tabular">{money(nm["condo_median_2025"])}</td><td class="tabular">{fmt_pct(pct(nm.get("condo_median_2024"), nm["condo_median_2025"]))}</td></tr>')


def page_neighborhood(n, city):
    """A neighborhood guide under its city's guide: site/communities/<city>/<slug>/.
    Same sourced record format as a town (tools/data/RESEARCH-BRIEF.md), with boundaries and areas."""
    R = "../../../../"
    slug, name, cname, cslug = n["slug"], n["name"], city["name"], city["slug"]
    url_rel = f"site/communities/{cslug}/{slug}/"
    trail = [("Home", "site/"), ("Communities", "site/communities/"), (cname, f"site/communities/{cslug}/"), (name, url_rel)]
    c = n.get("commute") or {}
    sch = n.get("schools") or city.get("schools") or {}
    cm = city.get("market") or {}
    rail = c.get("rail") or []
    rail_txt = "; ".join(f"{r['line']} ({', '.join(r['stations'])})" for r in rail) if rail else "See commuting below"
    facts = [("City", f'<a href="{R}site/communities/{cslug}/">{cname}, Massachusetts</a>'), ("County", city["county"])]
    if n.get("population_2020"):
        facts.append(("Population", f"{fmt_people(n['population_2020'])}" + (f" ({n['population_label']})" if n.get("population_label") else "")))
    facts.append(("Transit", rail_txt))
    if sch.get("district"): facts.append(("Schools", f'<a href="{sch["dese_url"]}">{sch["district"]}</a> (DESE profile)'))
    nm = n.get("market") or {}
    if nm.get("sf_median_2025"):
        facts.append(("Single-family median", f"{money(nm['sf_median_2025'])} in 2025 ({name} row) <span class=\"badge badge--source\">Warren Group</span>"))
        if nm.get("condo_median_2025"):
            facts.append(("Condominium median", f"{money(nm['condo_median_2025'])} in 2025 ({name} row) <span class=\"badge badge--source\">Warren Group</span>"))
    elif cm.get("sf_median_2025") and cm.get("citywide", True):
        facts.append((f"{cname} single-family median", f"{money(cm['sf_median_2025'])} in 2025, citywide <span class=\"badge badge--source\">Warren Group</span>"))
    elif cm.get("sf_median_2025"):
        facts.append((f"{cname} row single-family median", f"{money(cm['sf_median_2025'])} in 2025 (central neighborhoods) <span class=\"badge badge--source\">Warren Group</span>"))
    if tax_fact(city): facts.append(("Property tax", tax_fact(city) + " (citywide)"))
    facts.append(("Guide updated", f'<time datetime="{UPDATED}">October 22, 2026</time>'))
    facts_html = "".join(f"<dt>{k}</dt><dd>{v}</dd>" for k, v in facts)
    b = n.get("boundaries") or {}
    where = f'<h3 class="h4">Where it is</h3><p>{b["text"]}</p>' if b.get("text") else ""
    overview = "".join(f"<p>{p}</p>" for p in n["overview"])
    housing = f'<h3 class="h4" style="margin-top:var(--space-6)">Homes in {name}</h3><p>{n["housing"]["text"]}</p>' if (n.get("housing") or {}).get("text") else ""
    areas = ""
    if n.get("areas"):
        items = "".join(f'<div><h3 class="h4">{a["name"]}</h3><p class="body-s" style="margin-top:var(--space-2)">{a["line"]}</p></div>' for a in n["areas"])
        areas = f'''<section class="section surface-muted" id="areas" aria-labelledby="ar-title"><div class="container">
  {section_head("Within the neighborhood", name, ["Corners of", f"{name}."], title_id="ar-title")}
  <div class="cols cols--3 col-rule">{items}</div>
</div></section>'''
    rail_list = "".join(f"<li><b>{r['line']}</b>: {', '.join(r['stations'])}</li>" for r in rail)
    rail_block = f'<ul class="body-s" style="padding-left:1.1rem;display:grid;gap:var(--space-2)">{rail_list}</ul>' if rail else ""
    places = "".join(f'<div><h3 class="h4">{p["name"]}</h3><p class="body-s" style="margin-top:var(--space-2)">{p["line"]}</p></div>' for p in n.get("places", []))
    hist = ""
    if n.get("history"):
        hist = '<div class="history">' + "".join(f'<div><b>{h["year"]}</b><p class="body-s">{h["text"]}</p></div>' for h in n["history"][:4]) + "</div>"
    nm = n.get("market") or {}
    if nm.get("sf_median_2025"):
        market_html = f'''<table class="spec-table market-table"><thead><tr><th scope="col">Calendar year</th><th scope="col">2024</th><th scope="col">2025</th><th scope="col">Change</th></tr></thead><tbody><tr><th scope="row">Single-family median sale price</th><td class="tabular">{money(nm["sf_median_2024"]) if nm.get("sf_median_2024") else "n/a"}</td><td class="tabular">{money(nm["sf_median_2025"])}</td><td class="tabular">{fmt_pct(pct(nm.get("sf_median_2024"), nm["sf_median_2025"]))}</td></tr>{nb_condo_row(nm)}</tbody></table>
<p class="stat-source">Source: <a href="{WARREN_URL}">The Warren Group, MA December 2025 Sales, Prices by Town</a>. Geography: {name}, as the report defines it. Calendar years 2024 and 2025. Not live data and not a valuation of any home.</p>'''
    else:
        if cm.get("sf_median_2025") and not cm.get("citywide", True):
            lead_line = f"The Warren Group does not report {name} on its own row; it falls within {cm.get('geography', 'the city row')}."
            city_line = f"That row's single-family median was {money(cm['sf_median_2025'])} in 2025, across {fmt_people(cm['sf_sales_2025'])} sales" + (f", and its condominium median was {money(cm['condo_median_2025'])}" if cm.get("condo_median_2025") else "")
        else:
            lead_line = f"Sale prices are reported for the city of {cname} as a whole, not by neighborhood."
            city_line = f"{cname}'s single-family median was {money(cm['sf_median_2025'])} in 2025" if cm.get("sf_median_2025") else f"{cname}'s figures are in the city guide"
        market_html = f'''<p>{lead_line} {city_line}, according to <a href="{WARREN_URL}">The Warren Group</a>. Prices within {name} depend on the street, the building and the type of home, so the best guide is recent closed sales nearby.</p>{link_arrow(R, f"{R}site/communities/{cslug}/#market", f"{cname} prices in the city guide")}'''
    faq = n.get("faq", [])
    faq_html = "".join(f'<details{" open" if i == 0 else ""}><summary>{f["q"]}</summary><div class="accordion__body body-s"><p>{f["a"]}</p></div></details>' for i, f in enumerate(faq))
    sibs = {x["slug"]: x for x in city.get("neighborhoods", [])}
    near = [sibs[k] for k in n.get("nearby", []) if k in sibs and k != slug][:4]
    others = [x for x in city.get("neighborhoods", []) if x["slug"] != slug and x not in near]
    near_html = ""
    if near:
        near_html += f'<p class="label" style="margin-top:var(--space-6)">Nearby neighborhoods</p><ul class="toc-mini">' + "".join(f'<li><a href="{R}site/communities/{cslug}/{x["slug"]}/">{x["name"]}</a></li>' for x in near) + "</ul>"
    if others:
        near_html += f'<p class="label" style="margin-top:var(--space-6)">More {cname} neighborhoods</p><ul class="toc-mini">' + "".join(f'<li><a href="{R}site/communities/{cslug}/{x["slug"]}/">{x["name"]}</a></li>' for x in others) + "</ul>"
    near_html += f'<p class="body-s" style="margin-top:var(--space-3)"><a href="{R}site/communities/{cslug}/">The {cname} city guide</a></p>'
    srcs = []
    def addsrc(label, u):
        if u and u not in [x[1] for x in srcs]: srcs.append((label, u))
    addsrc(f"City of {cname}", city.get("official_site"))
    if n.get("population_source"): addsrc("Population", n["population_source"])
    addsrc("Boundaries", b.get("source"))
    addsrc("Transit", c.get("rail_source"))
    addsrc("Massachusetts DESE district profile", sch.get("dese_url"))
    addsrc("The Warren Group, 2025 sales by town", WARREN_URL)
    if (city.get("tax") or {}).get("source"): addsrc(f"{cname} property tax rates", city["tax"]["source"])
    if n.get("housing"): addsrc("Housing", n["housing"].get("source"))
    for a in n.get("areas", []): addsrc(a["name"], a.get("source"))
    for p_ in n.get("places", []): addsrc(p_["name"], p_.get("source"))
    for h in n.get("history", []): addsrc(f"History ({h['year']})", h.get("source"))
    src_html = "".join(f'<li><a href="{u}">{e(l)}</a></li>' for l, u in srcs)
    hero = page_hero(R, trail, n["headline"], h1=f"Living in {name}, {cname}, MA", lead=n["answer"])
    body = hero + f'''
<section class="section" aria-labelledby="about-title"><div class="container split" style="align-items:start">
  <div class="stack prose" style="grid-column:1/span 7;--stack:var(--space-4)">
    <h2 class="h2" id="about-title">About {name}</h2>
    {where}
    {overview}
    {housing}
  </div>
  <aside class="stack" style="grid-column:9/span 4;--stack:var(--space-5)" aria-label="{name} at a glance">
    <dl class="quick-facts quick-facts--aside">{facts_html}</dl>
    <div class="panel"><p class="h5">Your {cname} guides</p><p class="body-s">Brandon &amp; Kaitlyn Oberdorfer · Based in Worcester</p><div class="cluster"><a class="btn btn--small" href="#contact-town">Ask about {name}</a><a class="link-arrow" href="tel:{TEL}">{PHONE}</a></div></div>
  </aside>
</div></section>
{areas}
<section class="section" id="commute" aria-labelledby="commute-title"><div class="container split" style="align-items:start">
  <div class="stack" style="grid-column:1/span 6;--stack:var(--space-4)"><p class="label">Getting around</p><h2 class="h2" id="commute-title">Commuting from {name}</h2><p>{c.get("text", "")}</p>{rail_block}<p class="caption">Check current schedules with the <a href="https://www.mbta.com/">MBTA</a>{' and the <a href="https://therta.com/">WRTA</a>' if cslug == "worcester" else ""}; compare the trip from each address before you buy.</p></div>
  <div class="stack" style="grid-column:8/span 5;--stack:var(--space-4)"><p class="label">Schools</p><h2 class="h2">Public schools</h2><p>{name} is served by {sch.get("district", "[district to confirm]")}. School assignment depends on the address and the district's own rules; confirm it with the district, and review its profile with the Massachusetts Department of Elementary and Secondary Education.</p>{link_arrow(R, sch.get("dese_url", "#"), "DESE district profile")}</div>
</div></section>
<section class="section surface-muted" id="places" aria-labelledby="places-title"><div class="container">
  {section_head("Things to do", name, ["Places to know", f"in {name}."], title_id="places-title")}
  <div class="cols cols--3 col-rule">{places}</div>
  {f'<h2 class="h3" style="margin-top:var(--space-8);margin-bottom:var(--space-5)">History</h2>{hist}' if hist else ""}
</div></section>
<section class="section" id="market" aria-labelledby="market-title"><div class="container split" style="align-items:start">
  <div class="stack" style="grid-column:1/span 7;--stack:var(--space-4)"><p class="label">Home prices</p><h2 class="h2" id="market-title">Buying in {name}</h2>{market_html}</div>
  <div class="stack" style="grid-column:9/span 4;--stack:var(--space-4)"><h3 class="h4">Read it for your own home</h3><p class="body-s">A citywide median is a starting point. Your best comparisons are recent closed sales that resemble the home in location, size, condition and style.</p>{link_arrow(R, R+"site/sell/#consult", "Request a market review")}</div>
</div></section>
<section class="section surface-muted" id="questions" aria-labelledby="faq-title"><div class="container split" style="align-items:start">
  <div class="stack" style="grid-column:1/span 5"><p class="label">Questions</p><h2 class="display-m answer" id="faq-title"><span>Asked about</span><span>{name}.</span></h2>{near_html}</div>
  <div class="accordion" style="grid-column:7/span 6">{faq_html}</div>
</div></section>
<section class="section" id="contact-town" aria-labelledby="ct-title"><div class="container split" style="align-items:start">
  <div class="stack" style="grid-column:1/span 5;--stack:var(--space-5)"><p class="label">Thinking about {name}?</p><h2 class="display-m answer" id="ct-title"><span>Let's talk about</span><span>your move.</span></h2><p>Buying in {name}, selling here, or comparing it with other parts of {cname}: tell us what you are planning and we will reply by your preferred method.</p><a class="cta-panel__phone" href="tel:{TEL}" style="color:var(--ot-navy);font-size:var(--step-h2)">{PHONE}</a></div>
  <div class="panel" style="grid-column:7/span 6">{form(R, "town", f"Ask about {name}", contact_fields("tw") + select("tw-topic", "I'm interested in", [f"Buying in {name}", f"Selling in {name}", "Both", f"Comparing {cname} neighborhoods"]) + field("tw-msg", "Message", "textarea", opt=True) + reach_pref("tw"), "Send my question", f"community:{cslug}/{slug}", "h3")}</div>
</div></section>
<section class="section--tight" aria-labelledby="src-title"><div class="container"><h2 class="label" id="src-title">Sources for this guide</h2><ul class="source-list caption">{src_html}</ul><p class="caption" style="margin-top:var(--space-3)">Researched by The Oberdorfer Team. Updated <time datetime="{UPDATED}">October 22, 2026</time>. Places and facts can change; check with the source before you rely on them.</p></div></section>'''
    place = {"@type": "Place", "name": f"{name}, {cname}, Massachusetts",
             "containedInPlace": {"@type": "City", "name": f"{cname}, Massachusetts", "url": prod(f"site/communities/{cslug}/")}}
    page_ld = {"@context": "https://schema.org", "@type": "WebPage", "@id": prod(url_rel) + "#page", "url": prod(url_rel),
               "name": f"Living in {name}, {cname}, MA", "about": place, "dateModified": UPDATED, "inLanguage": "en-US",
               "isPartOf": {"@id": prod(f"site/communities/{cslug}/") + "#page"},
               "publisher": {"@id": TEAM_ID}, "mainEntityOfPage": prod(url_rel)}
    write(f"communities/{cslug}/{slug}", R, nbhd_title(name, cname), nbhd_desc(n), "Communities", body, ld=[crumbs_ld(trail), page_ld])

def page_market():
    R = "../../"
    trail = [("Home", "site/"), ("Market insights", "site/market/")]
    rows = village_rows = ""
    for t in sorted(TOWNS, key=lambda x: (x.get("market") or {}).get("sf_median_2025") or 0, reverse=True):
        m = t.get("market") or {}
        if not m.get("sf_median_2025"):
            if m.get("village_rows"):
                village_rows += f'<tr><th scope="row"><a href="{R}site/communities/{t["slug"]}/">{t["name"]}</a></th><td>{t["region"]}</td><td colspan="3">Reported by village, with no town-wide figure; see the guide</td></tr>'
            continue
        ch = pct(m.get("sf_median_2024"), m["sf_median_2025"])
        rows += f'<tr><th scope="row"><a href="{R}site/communities/{t["slug"]}/">{t["name"]}</a></th><td>{t["region"]}</td><td class="tabular">{fmt_people(m["sf_sales_2025"]) if m.get("sf_sales_2025") else "n/a"}</td><td class="tabular">{money(m["sf_median_2025"])}</td><td class="tabular">{fmt_pct(ch)}</td></tr>'
    body = page_hero(R, trail, ["Know the market.", "Love the home."], h1="Massachusetts housing market insights",
                     lead="The statewide median single-family sale price was $638,000 in 2025, up 3.7% from 2024, and the condominium median was $537,000, according to The Warren Group. Town by town, the picture varies widely.") + f'''
<section class="section"><div class="container split" style="align-items:start">
  <div style="grid-column:1/span 7">
    <p class="label" style="margin-bottom:var(--space-3)">Calendar year 2025 · Massachusetts statewide</p>
    <div class="stat-panel">
      <div class="stat"><span class="stat__value tabular">$638,000</span><span class="stat__label">Single-family median sale price</span><span class="stat__period">2025, up 3.7% from $615,000 in 2024</span></div>
      <div class="stat"><span class="stat__value tabular">$537,000</span><span class="stat__label">Condominium median sale price</span><span class="stat__period">2025, $535,000 in 2024</span></div>
    </div>
    <p class="stat-source">Source: <a href="https://www.thewarrengroup.com/wp-content/uploads/2026/01/MA-December-2025-Sales.pdf">The Warren Group, MA Median Home Sale Price Reaches $638K in 2025</a>, January 20, 2026. Geography: Massachusetts statewide. Not live data.</p>
  </div>
  <div style="grid-column:9/span 4" class="stack">
    <h2 class="h3">What these numbers can tell you</h2>
    <p class="body-s">A statewide or townwide median is a starting point. Your best comparisons are recent closed sales that resemble your property in location, size, condition, and style. A condo, an older single-family home, and a newly built residence can tell very different stories.</p>
  </div>
</div></section>
<section class="section surface-muted" id="towns" aria-labelledby="towns-title"><div class="container">
  {section_head("Town by town", "Calendar year 2025", ["Single-family prices", "in our guide towns."], title_id="towns-title")}
  <div class="table-scroll" tabindex="0" role="region" aria-labelledby="towns-title"><table class="spec-table market-table"><thead><tr><th scope="col">Town</th><th scope="col">Region</th><th scope="col">Sales, 2025</th><th scope="col">Median, 2025</th><th scope="col">Median vs. 2024</th></tr></thead><tbody>{rows}{village_rows}</tbody></table></div>
  <p class="stat-source">Source: <a href="{WARREN_URL}">{WARREN}</a>. Single-family homes; year-to-date figures through December. Not live data and not a valuation of any home.</p>
</div></section>
<section class="section" id="wakefield" aria-labelledby="wk-title"><div class="container split" style="align-items:start">
  <div style="grid-column:1/span 7">
    <p class="label" style="margin-bottom:var(--space-3)">Spotlight · Wakefield · Data through August 2026 · All home types</p>
    <h2 class="h3" id="wk-title" style="margin-bottom:var(--space-4)">Wakefield, from <em>Wakefield at Home</em></h2>
    <div class="stat-panel">
      <div class="stat"><span class="stat__value tabular">$786,979</span><span class="stat__label">Median sale price</span><span class="stat__period">3 months ending August 2026</span></div>
      <div class="stat"><span class="stat__value tabular">21</span><span class="stat__label">Median days on market</span><span class="stat__period">August 2026</span></div>
      <div class="stat"><span class="stat__value tabular">$483</span><span class="stat__label">Median sold price / sq. ft.</span><span class="stat__period">Redfin rolling 3-month snapshot</span></div>
      <div class="stat"><span class="stat__value tabular">103.3%</span><span class="stat__label">Sale-to-list price</span><span class="stat__period">August 2026</span></div>
    </div>
    <p class="stat-source">Source: Redfin, Wakefield Housing Market, accessed October 10, 2026. Geography: Wakefield town; all home types. Figures may be revised; this is not a valuation of an individual home.</p>
  </div>
  <div style="grid-column:9/span 4" class="stack"><p class="body-s">Different sources measure different things: the Warren Group table counts single-family sales for a full calendar year, while this Redfin snapshot covers all home types over recent months. Compare like with like.</p>{link_arrow(R, R+"site/communities/wakefield/", "The Wakefield guide")}</div>
</div></section>
<section class="section surface-muted"><div class="container"><div class="cols cols--3 col-rule">
  <div><h2 class="h4">Sold vs. asking</h2><p class="body-s" style="margin-top:var(--space-2)">A listing price is a seller's starting point. A closed sale shows what a buyer actually paid.</p></div>
  <div><h2 class="h4">Beyond square feet</h2><p class="body-s" style="margin-top:var(--space-2)">Lot size, renovation quality, layout, parking, and condominium fees all belong in the comparison.</p></div>
  <div><h2 class="h4">Your next step</h2><p class="body-s" style="margin-top:var(--space-2)">Ask for a property-specific market review before deciding what to offer or where to price.</p>{link_arrow(R, R+"site/sell/#consult", "Request a review")}</div>
</div></div></section>
{cta_panel(R, "Ready for a personal plan? <em>Call Brandon and Kaitlyn.</em>", "Property-specific market review")}'''
    dataset = {"@context": "https://schema.org", "@type": "Dataset", "name": "Single-family home sales and median prices by town, Massachusetts, 2025",
               "description": "Calendar-year 2025 single-family sales counts and median sale prices for the towns covered by The Oberdorfer Team's community guides, as published by The Warren Group.",
               "temporalCoverage": "2025", "spatialCoverage": {"@type": "State", "name": "Massachusetts"},
               "isBasedOn": WARREN_URL, "creator": {"@id": TEAM_ID}}
    write("market", R, "Massachusetts Housing Market by Town | The Oberdorfer Team",
          "The Massachusetts single-family median was $638,000 in 2025 (The Warren Group). Compare 2025 sales and medians in Worcester, Boston and 25 more towns.",
          "", body, ld=[crumbs_ld(trail), dataset])

def page_team():
    R = "../../"
    trail = [("Home", "site/"), ("Our Team", "site/team/")]
    body = page_hero(R, trail, ["A husband-and-wife team.", "A shared commitment."], h1="Meet The Oberdorfer Team", lead="Brandon and Kaitlyn lead The Oberdorfer Team. As the team grows, every agent shares the same standard of service.") + f'''
<section class="section"><div class="container">
  <form class="directory-bar" data-directory aria-label="Filter the team">
    {select("d-spec","Specialty",["All specialties","Buyers","Sellers","First-time buyers","Move-up"])}
    {select("d-area","Service area",["All of Massachusetts","Wakefield","Worcester","Boston"])}
    {select("d-lang","Language",["Any language","English"])}
    <button class="btn btn--secondary" type="reset">Reset</button>
  </form>
  <p class="results-bar__count" data-team-count aria-live="polite" style="margin-bottom:var(--space-6)">{len(AGENTS)} team profiles</p>
  <h2 class="visually-hidden">Team profiles</h2><div class="card-grid-3" data-team-grid>{"".join(agent_card(R, a) for a in AGENTS)}{seat_card(R)}</div>
  <p class="caption" style="margin-top:var(--space-5)">Every new agent receives the same profile: <a href="{R}site/agents/agent-template/">see the template</a>.</p>
</div></section>
{cta_panel(R, "Grow with a team that <em>presents every home like a feature.</em>", "Join our team", "The team is growing. Start with a confidential conversation with Brandon and Kaitlyn.", ("Start a conversation", "site/join/#recruit"))}'''
    write("team", R, "Our Massachusetts Real Estate Team | The Oberdorfer Team", "Brandon and Kaitlyn Oberdorfer lead The Oberdorfer Team, a husband-and-wife real estate team based in Worcester and serving all of Massachusetts.", "Our Team", body, ld=[crumbs_ld(trail)])

def page_agent():
    R = "../../../"
    trail = [("Home", "site/"), ("Our Team", "site/team/"), ("Brandon &amp; Kaitlyn Oberdorfer", "site/agents/brandon-kaitlyn-oberdorfer/")]
    homes = "".join(listing_card(R, l, f"{R}site/buy/{l['slug']}/") for l in LISTINGS[:3])
    arts = "".join(article_card(R, a) for a in ARTICLES[:3])
    body = f'''
<section class="section" style="padding-top:var(--space-7)"><div class="container stack" style="--stack:var(--space-7)">
  {breadcrumbs(R, trail)}
  <div class="profile">
    <figure class="profile__photo"><img src="{R}assets/img/photo/team-portrait.webp" alt="Brandon and Kaitlyn Oberdorfer" width="1400" height="1680"></figure>
    <div class="profile__body">
      <p class="label">Team leaders · Top Producer</p>
      <h1 class="display-l answer" id="page-title"><span>Brandon &amp; Kaitlyn</span><span>Oberdorfer</span></h1>
      <p class="label label--quiet">Husband. Wife. Your Team.</p>
      <div class="prose">
        <p>Home holds the everyday moments that become a life: dinners around the table, celebrations with friends, and a familiar place to land at the end of the day.</p>
        <p>We are Brandon and Kaitlyn Oberdorfer: The Oberdorfer Team, a husband-and-wife real estate partnership. Our work reflects the values we bring to our own family: care, honesty, hard work, and showing up for the people who count on us.</p>
        <p>Whether you are buying your first home, making room for your next chapter, or preparing to sell, we want you to feel heard, informed, and supported from the first conversation through closing.</p>
      </div>
      <dl>
        <dt>Phone</dt><dd><a href="tel:{TEL}">{PHONE}</a></dd>
        <dt>Email</dt><dd>[team email to confirm]</dd>
        <dt>Specialties</dt><dd>Buyers · Sellers · First homes · Move-up moves</dd>
        <dt>Service area</dt><dd>Massachusetts</dd>
        <dt>Brokerage</dt><dd>REWAP Brokerage LLC</dd>
        <dt>License</dt><dd>[MA license numbers to be added once supplied]</dd>
      </dl>
      <div class="cluster"><a class="btn" href="#contact-agent">Send a message</a><a class="btn btn--secondary" href="tel:{TEL}">Call {PHONE}</a></div>
    </div>
  </div>
</div></section>
<section class="section surface-inverse"><div class="container"><div class="triad">
  <div><p class="label">Personal</p><p class="h3">Two partners invested in your goals.</p></div>
  <div><p class="label">Purposeful</p><p class="h3">A plan shaped around your home and life.</p></div>
  <div><p class="label">Prepared</p><p class="h3">Clear guidance and attention to the details.</p></div>
</div></div></section>
<section class="section"><div class="container">{section_head("Meet each of us", "Individual profiles", ["Two partners,", "one standard."], title_id="each-title")}<div class="card-grid-3">{"".join(agent_card(R, a) for a in AGENTS)}</div></div></section>
<section class="section surface-muted"><div class="container">{section_head("Listings", "Demonstration", ["Homes we are", "presenting now."], title_id="al-title")}<div class="listing-grid">{homes}</div></div></section>
<section class="section"><div class="container">{section_head("From the journal", "Brandon &amp; Kaitlyn", ["Recent writing."], title_id="aj-title")}<div class="card-grid-3">{arts}</div></div></section>
<section class="section surface-muted" id="contact-agent"><div class="container split" style="align-items:start">
  <div style="grid-column:1/span 5" class="stack"><p class="label">Contact</p><h2 class="display-m answer"><span>We would love</span><span>to be a resource.</span></h2><p>Messages from this page are attributed to Brandon and Kaitlyn in the team's lead routing.</p></div>
  <div style="grid-column:7/span 6" class="panel">{form(R, "agent", "Message Brandon &amp; Kaitlyn", contact_fields("af") + select("af-topic","I'm interested in",["Buying","Selling","Both","Something else"]) + field("af-msg","Message","textarea",opt=True) + reach_pref("af"), "Send my message", "agent:brandon-kaitlyn", "h3")}</div>
</div></section>'''
    person = {"@context":"https://schema.org","@type":"AboutPage","url":prod("site/agents/brandon-kaitlyn-oberdorfer/"),"dateModified":UPDATED,
              "mainEntity":{"@id":TEAM_ID},"mentions":[{"@id":prod(f"site/agents/{a['slug']}/#person")} for a in AGENTS]}
    write("agents/brandon-kaitlyn-oberdorfer", R, "Brandon & Kaitlyn Oberdorfer | The Oberdorfer Team", "Meet Brandon and Kaitlyn Oberdorfer, the husband-and-wife leaders and Top Producers of The Oberdorfer Team, based in Worcester and serving all of Massachusetts.", "Our Team", body, ld=[crumbs_ld(trail), person])

# --------------------------------------------------------------------------- agent profiles
# One record per agent. Only confirmed facts go in plain fields; anything not yet
# supplied stays None and renders as a [bracketed placeholder] with a Verify badge.
# The same record drives the page, its JSON-LD, the team card, sitemap.xml and llms.txt.
AGENTS = [
    dict(slug="brandon-oberdorfer", name="Brandon Oberdorfer", given="Brandon", family="Oberdorfer",
         role="Team leader", partner="kaitlyn-oberdorfer", relation="his wife",
         photo="assets/img/photo/brandon-oberdorfer.webp", photo_w=440, photo_h=550,
         photo_note="Cropped from the team portrait in <em>Wakefield at Home</em>. Individual headshot to be supplied.",
         title=None, license=None, email="brandonoberdorfer@gmail.com", direct="406-250-7620", languages=None, specialties=None, bio=None, socials=None,
         lead="agent:brandon-oberdorfer"),
    dict(slug="kaitlyn-oberdorfer", name="Kaitlyn Oberdorfer", given="Kaitlyn", family="Oberdorfer",
         role="Team leader", partner="brandon-oberdorfer", relation="her husband",
         photo="assets/img/photo/kaitlyn-oberdorfer.webp", photo_w=440, photo_h=550,
         photo_note="Cropped from the team portrait in <em>Wakefield at Home</em>. Individual headshot to be supplied.",
         title=None, license=None, email="kaitlynoberdorfer@gmail.com", direct="978-257-4938", languages=None, specialties=None, bio=None, socials=None,
         lead="agent:kaitlyn-oberdorfer"),
]
TEMPLATE_AGENT = dict(slug="agent-template", name="[Agent name]", given="[First name]", family="[Last name]",
         role="[Role on the team]", partner=None, relation=None, photo=None,
         title=None, license=None, email=None, direct=None, languages=None, specialties=None, bio=None, socials=None,
         lead="agent:[agent-slug]", template=True)
AGENT_BY_SLUG = {a["slug"]: a for a in AGENTS}

def ph(text):
    """An unconfirmed value: bracketed, and flagged for verification."""
    return f'<span class="placeholder-value">[{text}]</span>'

def agent_card(R, a):
    href = f"{R}site/agents/{a['slug']}/"
    return f'''<article class="agent-card" data-specialty="buyers sellers first-time move-up" data-area="massachusetts wakefield worcester boston" data-lang="english">
  <a class="agent-card__photo" href="{href}"><img src="{R}{a['photo']}" alt="{a['name']}" width="{a['photo_w']}" height="{a['photo_h']}" loading="lazy"></a>
  <div class="stack" style="--stack:var(--space-2)"><p class="agent-card__title">{a['role']} · The Oberdorfer Team</p><h3 class="agent-card__name"><a href="{href}" style="text-decoration:none;color:inherit">{a['name']}</a></h3><p class="body-s muted">Husband. Wife. Your Team.</p></div>
  <div class="agent-card__meta"><span class="chip">Buyers</span><span class="chip">Sellers</span><span class="chip">Massachusetts</span></div>
  <div class="cluster" style="--cluster:var(--space-5)"><a class="link-arrow" href="{href}">Meet {a['given']} {arrow(R)}</a><a class="link-arrow" href="tel:{TEL}">{PHONE}</a></div>
</article>'''

def agent_faq(a):
    """Answer-first questions people (and answer engines) ask about an agent. Confirmed facts only."""
    g, n = a["given"], a["name"]
    partner = AGENT_BY_SLUG.get(a.get("partner") or "", None)
    qs = [
        (f"How do I contact {n}?",
         f"Call or text The Oberdorfer Team at {PHONE}, or send a message from this page. Messages sent here go to {g} and are attributed to {g} in the team's lead routing."),
        (f"Which areas does {n} serve?",
         f"{g} helps buyers and sellers across Massachusetts. The team works from Worcester, and its community guides run from Central Massachusetts and MetroWest to Boston, the South Shore, Cape Cod, the South Coast and the Berkshires, including Wakefield, where the team publishes <em>Wakefield at Home</em>."),
        (f"Which brokerage is {n} with?",
         f"{g} is part of The Oberdorfer Team, a real estate team operating under REWAP Brokerage LLC in Massachusetts."),
    ]
    if partner:
        qs.insert(1, (f"Do {g} and {partner['given']} work together?",
                      f"Yes. {g} leads The Oberdorfer Team with {a['relation']}, {partner['name']}. The team describes itself as “Husband. Wife. Your Team.”: two partners invested in your goals."))
    return qs

def profile_ld(R, a, url, faq):
    pid = prod(f"site/agents/{a['slug']}/#person")
    person = {"@type": "Person", "@id": pid, "name": a["name"], "givenName": a["given"], "familyName": a["family"],
              "url": url, "image": SITE + a["photo"], "telephone": a.get("direct") or PHONE,
              **({"email": a["email"]} if a.get("email") else {}),
              "jobTitle": "Real estate team leader",
              "worksFor": {"@id": TEAM_ID}, "affiliation": {"@id": BROKER_ID},
              "areaServed": {"@type": "State", "name": "Massachusetts"},
              "knowsAbout": ["Buying a home in Massachusetts", "Selling a home in Massachusetts", "Wakefield, Massachusetts real estate"]}
    if a.get("partner"):
        person["spouse"] = {"@id": prod(f"site/agents/{a['partner']}/#person")}
    page = {"@context": "https://schema.org", "@type": "ProfilePage", "url": url, "dateModified": UPDATED,
            "mainEntity": person}
    faq_ld = {"@context": "https://schema.org", "@type": "FAQPage", "mainEntity": [
        {"@type": "Question", "name": html.unescape(q), "acceptedAnswer": {"@type": "Answer", "text": re_tags(a_)}} for q, a_ in faq]}
    return [page, faq_ld]

def re_tags(t):
    import re
    return html.unescape(re.sub(r"<[^>]+>", "", t))

def page_profile(a):
    """Individual agent profile: /agents/<slug>/. Built for search (one entity per page,
    answer-first summary, quick facts, FAQ, ProfilePage + FAQPage JSON-LD) and for
    conversion (two actions above the fold, a mobile action bar, an attributed form)."""
    R = "../../../"
    tpl = a.get("template", False)
    g = a["given"]
    url = prod(f"site/agents/{a['slug']}/")
    trail = [("Home", "site/"), ("Our Team", "site/team/"), (a["name"], f"site/agents/{a['slug']}/")]
    partner = AGENT_BY_SLUG.get(a.get("partner") or "", None)
    faq = agent_faq(a)
    verify = '<span class="badge badge--verify">To confirm</span>'

    if tpl:
        photo = f'''<figure class="profile__photo"><div class="headshot-pending">{icon(R, 'user')}<p class="h5">Headshot pending</p><p class="body-s">4:5, natural light, ivory or navy backdrop. Required before the profile is published.</p></div></figure>'''
    else:
        photo = f'''<figure class="profile__photo"><img src="{R}{a['photo']}" alt="{a['name']}, {a['role'].lower()} of The Oberdorfer Team" width="{a['photo_w']}" height="{a['photo_h']}"><figcaption class="caption">{a['photo_note']} <span class="badge badge--verify">Interim photo</span></figcaption></figure>'''

    summary = (f"[One-sentence summary: who {g} is, what they help clients do, and where. For example: “{g} is a Massachusetts real estate agent on The Oberdorfer Team who helps first-time buyers in Worcester County.”]"
               if tpl else
               f"{a['name']} is a Massachusetts real estate agent who leads The Oberdorfer Team with {a['relation']}, {partner['name']}. The husband-and-wife team works from Worcester, helps buyers and sellers across the state and operates under REWAP Brokerage LLC.")
    facts = [
        ("Role", f"{a['role']}, The Oberdorfer Team" if not tpl else ph("Role on the team")),
        ("Title", ph("Professional title to confirm")),
        ("Brokerage", "REWAP Brokerage LLC"),
        ("Recognition", ph("Verified awards only") if tpl else 'Top Producer <span class="badge badge--source">Wakefield at Home, Fall 2026</span>'),
        ("Service area", "Massachusetts" if not tpl else ph("Service areas")),
        ("Specialties", ph("Specialties to confirm")),
        ("Languages", ph("Languages to confirm")),
        ("Phone", ph("Direct phone") if tpl else
                  (f'<a href="tel:{TEL}">{PHONE}</a> (direct, and the team line)' if a.get("direct") == PHONE else
                   f'<a href="tel:+1{a["direct"].replace("-", "")}">{a["direct"]}</a> (direct) · team line <a href="tel:{TEL}">{PHONE}</a>' if a.get("direct") else
                   f'<a href="tel:{TEL}">{PHONE}</a> (team line) · direct line ' + ph("to confirm"))),
        ("Email", f'<a href="mailto:{a["email"]}">{a["email"]}</a>' if a.get("email") and not tpl else ph("Email to confirm")),
        ("License", ph("MA license number to be supplied and verified")),
    ]
    facts_html = "".join(f"<dt>{k}</dt><dd>{v}</dd>" for k, v in facts)

    if tpl:
        bio = f'''<div class="placeholder-block"><p class="label">Biography · 120 to 250 words, first person</p>
<p>[Their story in their own words: how they came to real estate, the clients they serve best, a local detail only they would know, and one sentence on how they work with Brandon and Kaitlyn's standard of service. Facts only; nothing that cannot be verified.]</p>{verify}</div>'''
    else:
        bio = f'''<div class="placeholder-block"><p class="label">{g}'s biography · in {g}'s own words</p>
<p>[{g}'s biography to be supplied: 120 to 250 words in the first person. How {g} came to real estate, the part of each move {g} leads for clients, and a Massachusetts place {g} knows well. Verified facts only.]</p>{verify}</div>
<div class="prose"><p>What {g} and {partner['given']} share is written in <em>Wakefield at Home</em>: “Our work reflects the values we bring to our own family: care, honesty, hard work, and showing up for the people who count on us.”</p>
<p>Whether you are buying your first home, making room for your next chapter, or preparing to sell, the goal is the same: that you feel heard, informed, and supported from the first conversation through closing.</p></div>'''

    homes = "".join(listing_card(R, l, f"{R}site/buy/{l['slug']}/") for l in LISTINGS[:3])
    arts = "".join(article_card(R, x) for x in ARTICLES[:3])
    faq_html = "".join(f'<details{" open" if i == 0 else ""}><summary>{q}</summary><div class="accordion__body body-s"><p>{ans}</p></div></details>' for i, (q, ans) in enumerate(faq))

    partner_html = ""
    if partner:
        partner_html = f'''<div class="panel"><p class="label">Works alongside</p><a class="agent-mini" href="{R}site/agents/{partner['slug']}/" style="text-decoration:none;color:inherit"><img src="{R}{partner['photo']}" alt="" width="{partner['photo_w']}" height="{partner['photo_h']}"><span><span class="agent-mini__name">{partner['name']}</span><br><span class="agent-mini__meta">{partner['role']} · {a['relation'][0].upper() + a['relation'][1:]}</span></span></a>{link_arrow(R, R + "site/agents/brandon-kaitlyn-oberdorfer/", "Their story together")}</div>'''
    elif tpl:
        partner_html = f'''<div class="panel"><p class="label">Profile checklist</p><ul class="checklist body-s"><li>Verified MA license number</li><li>Headshot, 4:5</li><li>Biography, 120 to 250 words</li><li>Specialties and service areas</li><li>Languages</li><li>Direct phone and email</li><li>Principal broker approval</li></ul><p class="caption">The profile stays unpublished until every item is complete.</p></div>'''

    stories = ('<figure class="testimonial testimonial--placeholder"><span class="testimonial__mark"></span>'
               f'<blockquote>Verified client words about {g} appear here, quoted exactly, with the client\u2019s written permission on file.</blockquote>'
               '<figcaption class="caption">Client first name, town · Year <span class="badge badge--proposed">Empty state</span></figcaption></figure>'
               '<p class="caption" style="margin-top:var(--space-4)">No review or rating markup is published until real, permitted reviews exist.</p>')

    note = f"Your message goes to {g} and is used only to respond to your request. [Privacy policy to be published before launch.]"
    contact_form = form(R, "pf", f"Message {g}" if not tpl else f"Message {g}",
                        contact_fields("pf") + select("pf-topic", "I'm interested in", ["Buying", "Selling", "Both", "A market review", "Something else"]) + field("pf-msg", "Message", "textarea", opt=True) + reach_pref("pf"),
                        f"Send to {g}" if not tpl else "Send my message", a["lead"], "h3", note=note)

    ribbon = '<p class="template-ribbon"><span class="badge badge--demo">Template preview</span> Not a real agent. This is the profile every future team member receives, with the fields they must supply.</p>' if tpl else ""

    body = f'''
<section class="section profile-hero" style="padding-top:var(--space-7)"><div class="container stack" style="--stack:var(--space-6)">
  {breadcrumbs(R, trail)}{ribbon}
  <div class="profile">
    {photo}
    <div class="profile__body">
      <p class="label">{a['role']} · The Oberdorfer Team</p>
      <h1 class="display-l" id="page-title">{a['name']}</h1>
      <p class="lead profile__summary">{summary}</p>
      <div class="cluster profile__actions"><a class="btn" href="#contact-agent">Message {g}</a><a class="btn btn--secondary" href="tel:{TEL}">Call {PHONE}</a></div>
      <p class="caption profile__trust">Husband. Wife. Your Team. · REWAP Brokerage LLC · Serving Massachusetts</p>
      <dl class="quick-facts" aria-label="{g} at a glance">{facts_html}</dl>
      <p class="caption">Profile updated <time datetime="{UPDATED}">October 22, 2026</time>.</p>
    </div>
  </div>
</div></section>
<section class="section surface-muted"><div class="container split" style="align-items:start">
  <div class="stack" style="grid-column:1/span 7;--stack:var(--space-5)"><p class="label">About {g}</p><h2 class="display-m">Meet {g}.</h2>{bio}</div>
  <aside class="stack" style="grid-column:9/span 4;--stack:var(--space-5)">{partner_html}</aside>
</div></section>
<section class="section"><div class="container">{section_head("How " + g + " can help", "Services", ["A plan for", "your next move."], title_id="help-title")}
  <div class="cols cols--3 col-rule">
    <div class="stack"><h3 class="h4">Buying a home</h3><p class="body-s">Preapproval, everyday priorities and the costs beyond the listing price, then showings shaped around your life.</p>{link_arrow(R, R + "site/buy/", "Start a home search")}</div>
    <div class="stack"><h3 class="h4">Selling a home</h3><p class="body-s">Pricing from comparable closed sales, preparation, photography and a launch planned around your calendar.</p>{link_arrow(R, R + "site/sell/", "Plan your sale")}</div>
    <div class="stack"><h3 class="h4">A market review</h3><p class="body-s">A property-specific look at recent closed sales before you decide what to offer or where to price.</p>{link_arrow(R, R + "site/sell/#consult", "Request a market review")}</div>
  </div>
</div></section>
<section class="section surface-inverse"><div class="container split" style="align-items:center">
  <div class="stack" style="grid-column:1/span 5"><p class="label">Client stories</p><h2 class="display-m answer"><span>In their</span><span>own words.</span></h2></div>
  <div style="grid-column:7/span 6">{stories}</div>
</div></section>
<section class="section"><div class="container">{section_head("Listings", "Demonstration", ["Homes " + g + " is", "presenting now."], title_id="al-title")}<div class="listing-grid">{homes}</div><p class="form-note" style="margin-top:var(--space-6)">On the live site this shows only listings {g} is authorized to advertise, from the IDX feed, with MLS attribution.</p></div></section>
<section class="section surface-muted"><div class="container">{section_head("From the journal", g, ["Recent writing."], title_id="aj-title")}<div class="card-grid-3">{arts}</div></div></section>
<section class="section"><div class="container split" style="align-items:start">
  <div class="stack" style="grid-column:1/span 5"><p class="label">Questions</p><h2 class="display-m answer"><span>Asked about</span><span>{g}.</span></h2><p class="body-s muted">Plain answers to the questions people ask before they call.</p></div>
  <div class="accordion" style="grid-column:7/span 6">{faq_html}</div>
</div></section>
<section class="section surface-muted" id="contact-agent"><div class="container split" style="align-items:start">
  <div style="grid-column:1/span 5" class="stack"><p class="label">Contact {g}</p><h2 class="display-m answer"><span>Start with</span><span>a conversation.</span></h2><p>Tell {g} what you are planning. Expect a reply by your preferred method; no account, no obligation.</p><a class="cta-panel__phone" href="tel:{TEL}" style="color:var(--ot-navy);font-size:var(--step-h2)">{PHONE}</a></div>
  <div style="grid-column:7/span 6" class="panel">{contact_form}</div>
</div></section>
<nav class="action-bar" aria-label="Contact {g}"><a class="btn btn--gold" href="tel:{TEL}">{icon(R, 'phone')}Call</a><a class="btn" href="#contact-agent" style="--btn-bg:var(--ot-ivory);--btn-fg:var(--ot-navy);--btn-border:var(--ot-ivory)">Message {g}</a></nav>'''

    if tpl:
        write(f"agents/{a['slug']}", R, "[Agent name], Real Estate Agent | The Oberdorfer Team",
              "[Meta description, 150 to 160 characters: name, role, areas served, and the one thing clients come to them for.]",
              "Our Team", body, ld=[crumbs_ld(trail)], og_type="profile", index=False)
    else:
        title = f"{a['name']}, Real Estate Agent | The Oberdorfer Team"
        desc = f"Meet {a['name']}, who leads The Oberdorfer Team with {a['relation']}, {partner['name']}. Helping buyers and sellers across Massachusetts. Call {PHONE}."
        write(f"agents/{a['slug']}", R, title, desc, "Our Team", body,
              ld=[crumbs_ld(trail)] + profile_ld(R, a, url, faq), og_type="profile", og_image=a["photo"])

def write_discovery_files():
    """sitemap.xml for search engines and llms.txt for answer engines. Both use the
    production domain placeholder until the domain is confirmed."""
    urls = "".join(f"  <url><loc>{prod(p['rel'] + '/' if p['rel'] else '')}</loc><lastmod>{UPDATED}</lastmod></url>\n" for p in WRITTEN if p["index"])
    with open(os.path.join(OUT, "sitemap.xml"), "w") as f:
        f.write(f'<?xml version="1.0" encoding="UTF-8"?>\n<!-- Prototype: production URLs on oberdorferteam.com. WordPress generates the live sitemap at launch. -->\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n{urls}</urlset>\n')
    people = "\n".join(f"- [{a['name']}]({prod(f"site/agents/{a['slug']}/")}): {a['role']}, The Oberdorfer Team. Leads the team with {a['relation']}, {AGENT_BY_SLUG[a['partner']]['name']}." for a in AGENTS)
    pages = "\n".join(f"- [{html.unescape(p['title'])}]({prod(p['rel'] + '/' if p['rel'] else '')}): {html.unescape(p['desc'])}" for p in WRITTEN if p["index"])
    with open(os.path.join(OUT, "llms.txt"), "w") as f:
        f.write(f"""# The Oberdorfer Team

> A husband-and-wife real estate team led by Brandon and Kaitlyn Oberdorfer. The team works from Worcester, Massachusetts, serves buyers and sellers across the state, and operates under REWAP Brokerage LLC. Phone: {PHONE}. Promise: "Luxury at every price point."

Facts on this site are sourced and dated. Town market figures are calendar-year 2025 single-family sales and medians from The Warren Group (MA December 2025 Sales, Prices by Town). The Wakefield snapshot is from Redfin, accessed October 10, 2026. None of the figures are live. Community guides describe places, housing, transit and history; school information is limited to links to each district's Massachusetts DESE profile. Listings on this prototype are demonstrations.

## People
{people}

## Pages
{pages}
""")
    print("wrote site/sitemap.xml and site/llms.txt")

def page_journal():
    R = "../../"
    trail = [("Home", "site/"), ("Journal", "site/journal/")]
    cards = "".join(article_card(R, a) for a in ARTICLES[1:])
    cats = ["All", "Local living", "Real estate insights", "Seasonal features", "Recipes and entertaining", "Home inspiration", "Market updates", "Community guides"]
    chips = "".join(f'<button type="button" data-cat-filter="{c}" aria-pressed="{"true" if c=="All" else "false"}">{c}</button>' for c in cats)
    body = page_hero(R, trail, ["Local living.", "Beautiful homes."], h1="The journal: Massachusetts living and real estate", lead="Real estate insight, community guides, seasonal adventures and recipes to share. The online home of the <em>At Home</em> publications.") + f'''
<section class="section"><div class="container stack" style="--stack:var(--space-8)">
  <a class="feature-article" href="{R}site/journal/the-best-plans-are-close-to-home/"><div class="feature-article__media"><img src="{R}assets/img/photo/breakheart-reservation.webp" alt="Autumn at Breakheart Reservation"></div><div class="feature-article__body"><p class="label">Seasonal features · Fall field guide</p><h2 class="display-m">The best plans are close to home.</h2><p>Six ways to enjoy Wakefield and the surrounding area this season.</p><span class="link-arrow">Read the guide {arrow(R)}</span></div></a>
  <div class="stack" style="--stack:var(--space-6)"><div class="chip-filter" role="group" aria-label="Filter by category">{chips}</div>
  <div class="card-grid-3" data-journal-grid>{cards}</div>
  <p class="body-s muted" data-journal-empty hidden>No articles in this category yet. New issues add to the journal each season.</p></div>
</div></section>
<section class="section surface-muted" id="archive"><div class="container split" style="align-items:center">
  <div style="grid-column:1/span 6" class="stack"><p class="label">The publication archive</p><h2 class="display-m answer"><span>At Home,</span><span>in print and online.</span></h2><p>Each issue is written for one community and one season. Download past issues, or subscribe to receive the next one.</p>
    <form class="newsletter" data-newsletter aria-label="Subscribe to the At Home publications"><label class="visually-hidden" for="j-news">Email address</label><input class="input" id="j-news" type="email" autocomplete="email" placeholder="Email address"><button class="btn btn--small" type="submit">Subscribe</button></form></div>
  <div style="grid-column:8/span 5" class="archive-card"><img src="{R}assets/img/source-pages/page-1.webp" alt="Wakefield at Home, Fall 2026 cover" width="900" height="1157" loading="lazy"><div class="stack" style="--stack:var(--space-3)"><p class="h4">Wakefield at Home</p><p class="body-s muted">Fall 2026 · 8 pages</p><a class="link-arrow" href="{R}assets/publications/wakefield-at-home-fall-2026.pdf" download>Download PDF (3 MB) {icon(R,"download")}</a></div></div>
</div></section>'''
    write("journal", R, "Massachusetts Living Journal | The Oberdorfer Team", "Local living, real estate insight, seasonal guides and recipes from The Oberdorfer Team and its At Home publications, for Massachusetts buyers and sellers.", "Journal", body, ld=[crumbs_ld(trail)])

def page_article():
    R = "../../../"
    trail = [("Home", "site/"), ("Journal", "site/journal/"), ("The best plans are close to home", "site/journal/the-best-plans-are-close-to-home/")]
    items = [("Walk the lake", "Choose a stretch of the Lake Quannapowitt shoreline, bring a warm drink, and leave time to pause at the Common.", "wakefieldma.gov", "https://www.wakefield.ma.us/"),
             ("Shop the harvest", "Wakefield Farmers Market: Saturdays, 9 AM–1 PM, through October 31 at Hall Park, 468 North Avenue.", "wakefieldfarmersmarket.org", "https://www.wakefieldfarmersmarket.org/"),
             ("Follow the foliage", "Breakheart Reservation offers forest trails and lakes. The Wakefield entrance is at 100 Hemlock Road; choose a route that suits your group.", "mass.gov / Breakheart Reservation", "https://www.mass.gov/locations/breakheart-reservation"),
             ("Make a library day", "Browse Beebe Library's programs or reserve an eligible museum pass. A rainy day can become a very good plan.", "wakefieldlibrary.org", "https://www.wakefieldlibrary.org/"),
             ("Pick an autumn favorite", "Nearby Brooksby Farm in Peabody offers seasonal apple picking, cider donuts, and a farm store. Check crop availability before heading over.", "brooksbyfarm.org", "https://www.brooksbyfarm.org/"),
             ("Get lost in the fun", "Connors Farm in Danvers offers a fall corn maze and daytime farm activities. Check admission, hours, and weather updates before your visit.", "connorsfarm.com", "https://www.connorsfarm.com/")]
    lis = "".join(f'<li><div><h2>{t}</h2><p class="body-s">{d}</p><a class="caption" href="{u}" rel="noopener">{s}</a></div></li>' for t, d, s, u in items)
    body = page_hero(R, trail, ["The best plans", "are close to home."], lead="Six ways to enjoy Wakefield and the surrounding area this season.", photo="assets/img/photo/breakheart-reservation.webp", photo_alt="Autumn at Breakheart Reservation", focal="50% 45%") + f'''
<section class="section"><div class="container article">
  <article class="article__body">
    <p class="caption">From <em>Wakefield at Home</em>, Fall 2026 · Seasonal features · Photo: Joseph Reagle / Wikimedia Commons, CC BY-SA 4.0, cropped.</p>
    <p class="article__lede">A little fresh air. A few good memories. The best autumn plans rarely need a long drive.</p>
    <ol class="guide-list">{lis}</ol>
    <p class="caption">Seasonal schedules, crops, and access can change. Check each destination before you go. Listed details as published in October 2026.</p>
  </article>
  <aside class="article__aside" aria-label="About this article">
    <p class="label">Wakefield at Home</p><p class="body-s">Fall 2026 issue, page 5.</p>
    <a class="link-arrow" href="{R}site/communities/wakefield/">Wakefield community guide {arrow(R)}</a>
    <a class="link-arrow" href="{R}assets/publications/wakefield-at-home-fall-2026.pdf" download>Download the issue {icon(R,"download")}</a>
  </aside>
</div></section>
{cta_panel(R, "Buying, selling, or simply curious <em>about Wakefield?</em>", "Wakefield and nearby towns", "Call Brandon and Kaitlyn, or ask for a property-specific market review.")}'''
    art_ld = {"@context":"https://schema.org","@type":"Article","headline":"The best plans are close to home","datePublished":"2026-10","author":{"@type":"Organization","name":"The Oberdorfer Team"}}
    write("journal/the-best-plans-are-close-to-home", R, "The Best Plans Are Close to Home | The Oberdorfer Team", "Six ways to enjoy Wakefield and the surrounding area this fall: the lake, the farmers market, Breakheart, Beebe Library, Brooksby Farm and Connors Farm.", "Journal", body, ld=[crumbs_ld(trail), art_ld])

def page_join():
    R = "../../"
    trail = [("Home", "site/"), ("Join Our Team", "site/join/")]
    body = page_hero(R, trail, ["Grow with a team", "that cares how it shows up."], h1="Join The Oberdorfer Team", lead="The Oberdorfer Team is growing. We are looking for agents who believe every client deserves luxury-level service, at every price point.", navy=True) + f'''
<section class="section"><div class="container split" style="align-items:start">
  <div style="grid-column:1/span 7" class="letter">
    <p class="label">A letter from Brandon and Kaitlyn</p>
    <p class="body-l letter__first">When we started The Oberdorfer Team, we built it on what we bring to our own family: care, honesty, hard work, and showing up for the people who count on us. As we prepare to open our team office, we want to share that standard with agents who feel the same way.</p>
    <p>Our clients range from first-time buyers to families selling a home they have loved for decades. Every one of them gets the same thoughtful presentation, clear guidance and personal attention. If that is how you want to work, we would love to talk.</p>
    <p class="signature">Brandon &amp; Kaitlyn</p>
    <p class="caption">Draft copy for the team to edit and approve.</p>
  </div>
  <div style="grid-column:9/span 4" class="stack"><p class="label">What we value</p><ul class="toc-mini values-list" style="font-size:1rem"><li><span>Care</span></li><li><span>Honesty</span></li><li><span>Hard work</span></li><li><span>Personal service</span></li><li><span>Thoughtful guidance</span></li><li><span>Attention to detail</span></li><li><span>Creative marketing</span></li></ul></div>
</div></section>
<section class="section surface-muted"><div class="container">
  {section_head("What you would join", "Join Our Team", ["A brand built", "on presentation."], title_id="j-title")}
  <div class="cols cols--4 col-rule">
    <div><h3 class="h4">The brand</h3><p class="body-s" style="margin-top:var(--space-2)">The Oberdorfer Team identity and the <em>At Home</em> publications. [What agents receive to be confirmed by the team.]</p></div>
    <div><h3 class="h4">The office</h3><p class="body-s" style="margin-top:var(--space-2)">The team works from {ADDRESS}, under REWAP Brokerage.</p></div>
    <div><h3 class="h4">Marketing and technology</h3><p class="body-s" style="margin-top:var(--space-2)">[Support details to be confirmed by the team before publication.]</p></div>
    <div><h3 class="h4">Training and mentorship</h3><p class="body-s" style="margin-top:var(--space-2)">[Program details to be confirmed by the team before publication.]</p></div>
  </div>
  <p class="form-note" style="margin-top:var(--space-6)">Compensation, splits and fees are discussed privately and are never stated on this site.</p>
</div></section>
<section class="section"><div class="container split" style="align-items:start">
  <div style="grid-column:1/span 5" class="stack"><p class="label">Confidential conversation</p><h2 class="display-m answer"><span>Start with</span><span>a conversation.</span></h2><p>Your inquiry goes only to Brandon and Kaitlyn and is kept confidential.</p></div>
  <div style="grid-column:7/span 6" class="panel">{form(R, "recruit", "Recruiting inquiry", contact_fields("rf") + '<div class="form__row">' + select("rf-licensed","Are you licensed in Massachusetts?",["Yes, salesperson","Yes, broker","In progress","Not yet"]) + select("rf-years","Years in real estate",["New to real estate","1–3","4–10","10+"]) + '</div>' + field("rf-area","Where you work or live",opt=True,ph="e.g. Worcester County") + field("rf-msg","What are you looking for in a team?","textarea",opt=True), "Start the conversation", "recruiting", "h3", note="Your inquiry is confidential and goes only to Brandon and Kaitlyn.")}</div>
</div></section>'''
    write("join", R, "Join a Massachusetts Real Estate Team | The Oberdorfer Team", "The Oberdorfer Team is growing. A confidential conversation with Brandon and Kaitlyn about joining a Massachusetts real estate team.", "Join Our Team", body, ld=[crumbs_ld(trail)])

def page_office():
    R = "../../"
    trail = [("Home", "site/"), ("Team office", "site/office/")]
    body = page_hero(R, trail, ["A place to sit down", "and plan your move."], h1="Our office in Worcester, Massachusetts", lead="The Oberdorfer Team is opening its new office in Worcester and serves clients across Massachusetts. Visits are by appointment.", aside='<span class="badge badge--verify">Hours to be confirmed</span>') + f'''
<section class="section"><div class="container split" style="align-items:start">
  <div style="grid-column:1/span 7" class="stack" >
    <div class="map-frame"><svg viewBox="0 0 800 500" role="img" aria-label="Illustrative map placeholder"><rect width="800" height="500" fill="#E9E2D5"/><g stroke="#FBF9F5" stroke-width="10" fill="none" stroke-linecap="round"><path d="M0 300C200 280 360 330 800 260"/><path d="M300 0C320 160 280 340 330 500"/><path d="M800 420C600 400 450 440 300 500"/></g><g transform="translate(338 284)"><circle r="20" fill="#071D2D"/><circle r="27" fill="none" stroke="#B49A65"/></g></svg><p class="map-frame__note"><span class="badge badge--demo">Illustrative map · no real location</span></p></div>
    <div class="demo-illu demo-illu--light" style="aspect-ratio:16/7;overflow:hidden">{art(R,'townscape','Illustration standing in for office photography')}</div>
  </div>
  <div style="grid-column:9/span 4" class="stack" >
    <div class="office-card">
      <h2 class="h4">The Oberdorfer Team Office</h2>
      <dl><dt>City</dt><dd>Worcester, Massachusetts</dd><dt>Address</dt><dd>{STREET}<br>Worcester, MA {POSTCODE}<br><a href="{MAPS}" rel="noopener">Get directions</a></dd><dt>Phone</dt><dd><a href="tel:{TEL}">{PHONE}</a></dd><dt>Hours</dt><dd><span class="placeholder-value">[Hours to be confirmed]</span></dd><dt>Brokerage</dt><dd>A team office of REWAP Brokerage LLC (formal status to be confirmed)</dd><dt>Agents</dt><dd><a href="{R}site/agents/brandon-kaitlyn-oberdorfer/">Brandon &amp; Kaitlyn Oberdorfer</a></dd></dl>
    </div>
    <div class="panel">{form(R, "appt", "Request an appointment", contact_fields("ap") + select("ap-topic","Topic",["Buying","Selling","Joining the team","Other"]), "Request an appointment", "office", "h3")}</div>
  </div>
</div></section>'''
    write("office", R, "Worcester, MA Office | The Oberdorfer Team", "The Oberdorfer Team works from Worcester, Massachusetts, under REWAP Brokerage LLC, and serves buyers and sellers statewide. Request an appointment.", "", body, ld=[crumbs_ld(trail)])

def page_contact():
    R = "../../"
    trail = [("Home", "site/"), ("Contact", "site/contact/")]
    tabs = [("general","General"),("buyer","Buying"),("seller","Selling"),("recruit","Joining the team")]
    tabbtn = "".join(f'<button role="tab" id="tab-{k}" aria-controls="panel-{k}" aria-selected="{"true" if i==0 else "false"}" tabindex="{0 if i==0 else -1}" type="button">{v}</button>' for i,(k,v) in enumerate(tabs))
    forms = {
      "general": form(R,"cg","Send a message",contact_fields("cg") + field("cg-msg","How can we help?","textarea",req=True) + reach_pref("cg"),"Send my message","general","h2"),
      "buyer": form(R,"cb","Buyer consultation",contact_fields("cb") + select("cb-timing","When would you like to move?",["Within 3 months","3–6 months","6–12 months","Just exploring"]) + field("cb-towns","Towns you are considering",opt=True) + reach_pref("cb"),"Request my consultation","buyer","h2"),
      "seller": form(R,"cs","Seller consultation",contact_fields("cs") + field("cs-address","Property address",req=True,auto="street-address") + select("cs-timing","Timing",["Within 3 months","3–6 months","6–12 months","Just curious"]) + reach_pref("cs"),"Request my market review","seller","h2"),
      "recruit": form(R,"cr","Recruiting inquiry",contact_fields("cr") + select("cr-licensed","Are you licensed in Massachusetts?",["Yes, salesperson","Yes, broker","In progress","Not yet"]) + field("cr-msg","Tell us about yourself","textarea",opt=True),"Start the conversation","recruiting","h2",note="Confidential. Goes only to Brandon and Kaitlyn."),
    }
    panels = "".join(f'<div class="tabpanel" role="tabpanel" id="panel-{k}" aria-labelledby="tab-{k}"{"" if i==0 else " hidden"}>{forms[k]}</div>' for i,(k,_) in enumerate(tabs))
    body = page_hero(R, trail, ["We would love", "to hear from you."], h1="Contact The Oberdorfer Team", lead="Call, text or send a message. Your inquiry goes to Brandon and Kaitlyn.") + f'''
<section class="section"><div class="container contact-grid">
  <div class="stack" style="--stack:var(--space-6)">
    <a class="cta-panel__phone" href="tel:{TEL}" style="color:var(--ot-navy);font-size:var(--step-display-m)">{PHONE}</a>
    <div>
      <div class="contact-line">{icon(R,'phone')}<div><p class="h5">Call or text</p><p class="body-s">Brandon &amp; Kaitlyn Oberdorfer · <a href="tel:{TEL}">{PHONE}</a></p></div></div>
      <div class="contact-line">{icon(R,'mail')}<div><p class="h5">Email</p><p class="body-s">[Team email to be confirmed]</p></div></div>
      <div class="contact-line">{icon(R,'door')}<div><p class="h5">Team office</p><p class="body-s">Worcester, Massachusetts. By appointment. <a href="{R}site/office/">Office details</a></p></div></div>
      <div class="contact-line">{icon(R,'pin')}<div><p class="h5">Service area</p><p class="body-s">Massachusetts</p></div></div>
    </div>
  </div>
  <div class="panel"><div class="tabs" role="tablist" aria-label="Type of inquiry">{tabbtn}</div>{panels}</div>
</div></section>
<section class="section surface-muted"><div class="container cols cols--3 col-rule body-s">
  <div id="privacy"><h2 class="h4">Privacy</h2><p style="margin-top:var(--space-2)">Form details are used only to respond to your request and are never sold. [Full privacy policy to be written and approved before launch.]</p></div>
  <div id="accessibility"><h2 class="h4">Accessibility</h2><p style="margin-top:var(--space-2)">This site is designed to meet WCAG 2.2 AA. If anything is hard to use, call {PHONE} and we will help.</p></div>
  <div id="fair-housing"><h2 class="h4">Fair housing</h2><p style="margin-top:var(--space-2)">The Oberdorfer Team and REWAP Brokerage LLC are committed to the Fair Housing Act and Equal Housing Opportunity. [Required Massachusetts notices to be supplied by the principal broker.]</p></div>
</div></section>'''
    write("contact", R, "Contact The Oberdorfer Team | Worcester, MA Real Estate", "Call or text Brandon and Kaitlyn Oberdorfer at 406-250-7620, or send a buyer, seller, recruiting or general inquiry. Based in Worcester, serving Massachusetts.", "Contact", body, ld=[crumbs_ld(trail)])

if __name__ == "__main__":
    page_home(); page_buy(); [page_listing(l) for l in LISTINGS]; page_sell(); page_market()
    page_communities(); [page_town(t) for t in TOWNS]; [page_neighborhood(n, t) for t in TOWNS for n in t.get("neighborhoods", [])]; page_team(); page_agent(); page_journal(); page_article()
    page_join(); page_office(); page_contact()
    for a in AGENTS: page_profile(a)
    page_profile(TEMPLATE_AGENT)
    write_discovery_files()
