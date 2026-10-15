#!/usr/bin/env python3
"""Build the static example website into /site.

Run from the repo root:  python3 tools/build_site.py

Shared partials (header, footer, cards, forms) live here as functions, the way
they will become PHP template parts and Gutenberg blocks in the WordPress
theme. Everything is plain HTML that uses the design-system CSS.
"""
import html, json, os, textwrap

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
COMMUNITIES = [
    dict(slug="wakefield", name="Wakefield", county="Middlesex County", ready=True),
    dict(slug=None, name="Worcester", county="Worcester County", ready=False, art="triple-decker"),
    dict(slug=None, name="Boston", county="Suffolk County", ready=False, art="townscape"),
    dict(slug=None, name="Reading", county="Middlesex County", ready=False, art="cape"),
    dict(slug=None, name="Stoneham", county="Middlesex County", ready=False, art="colonial"),
    dict(slug=None, name="Melrose", county="Middlesex County", ready=False, art="townscape"),
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
        {"@type": "ListItem", "position": i + 1, "name": html.unescape(l)} for i, (l, _) in enumerate(trail)]}

def page_hero(R, trail, title_lines, lead=None, aside=None, navy=False, photo=None, photo_alt="", focal="50% 50%"):
    spans = "".join(f"<span>{t}</span>" for t in title_lines)
    cls = "page-hero"
    img = ""
    if navy or photo: cls += " page-hero--navy surface-inverse"
    if photo:
        cls += " page-hero--photo"
        img = f'<img src="{R}{photo}" alt="{e(photo_alt)}" style="object-position:{focal}" fetchpriority="high">'
    out = f'<section class="{cls}" aria-labelledby="page-title">{img}<div class="container page-hero__grid">'
    out += breadcrumbs(R, trail)
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

def community_card(R, c):
    if c["ready"]:
        return f'''<a class="community-card" href="{R}site/communities/{c['slug']}/"><img src="{R}assets/img/photo/lake-quannapowitt.webp" alt="" loading="lazy" style="object-position:30% 50%"><div class="community-card__body"><span class="community-card__meta">{c['county']}</span><span class="community-card__name">{c['name']}</span></div></a>'''
    return f'''<div class="community-card" aria-label="{c['name']}, guide in preparation"><div class="demo-illu" style="position:absolute;inset:0">{art(R, c['art'])}</div><div class="community-card__body"><span class="community-card__meta">{c['county']} · Guide in preparation</span><span class="community-card__name">{c['name']}</span></div></div>'''

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
def head(R, title, desc, extra_ld=None, canonical_path=""):
    ld = {"@context": "https://schema.org", "@type": "RealEstateAgent", "name": "The Oberdorfer Team",
          "telephone": PHONE, "areaServed": {"@type": "State", "name": "Massachusetts"},
          "slogan": "Luxury at every price point.",
          "parentOrganization": {"@type": "Organization", "name": "REWAP Brokerage LLC"}}
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
<link rel="canonical" href="https://[domain-to-confirm]/{canonical_path}">
<meta property="og:title" content="{e(title)}"><meta property="og:description" content="{e(desc)}"><meta property="og:image" content="{R}assets/logo/social-avatar.png">
<link rel="icon" href="{R}assets/logo/favicon.svg" type="image/svg+xml"><link rel="apple-touch-icon" href="{R}assets/logo/apple-touch-icon.png">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,300;1,400&family=Manrope:wght@300;400;500;600;700&display=swap">
<link rel="stylesheet" href="{R}assets/css/tokens.css"><link rel="stylesheet" href="{R}assets/css/base.css"><link rel="stylesheet" href="{R}assets/css/components.css"><link rel="stylesheet" href="{R}assets/css/docs.css"><link rel="stylesheet" href="{R}assets/css/site.css">
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

def footer(R):
    return f'''<footer class="site-footer">
  <div class="container">
    <div class="site-footer__grid">
      <div class="stack" style="--stack:var(--space-5)">
        <img src="{R}assets/logo/logo-primary-gold.svg" alt="The Oberdorfer Team" width="300" height="40" style="width:17rem">
        <p class="body-s" style="max-width:30ch">Luxury at every price point. For the home you have. For the life you are building.</p>
        <a class="cta-panel__phone" href="tel:{TEL}" style="font-size:2rem">{PHONE}</a>
        <form class="newsletter" style="color:var(--ot-ivory)" data-newsletter aria-label="Seasonal publication sign-up"><label class="visually-hidden" for="f-news">Email address</label><input class="input" id="f-news" type="email" autocomplete="email" placeholder="Email for the At Home issues" style="color:var(--ot-ivory)"><button class="btn btn--small" type="submit">Subscribe</button></form>
      </div>
      <div><h2>Explore</h2><ul><li><a href="{R}site/buy/">Buy a home</a></li><li><a href="{R}site/sell/">Sell your home</a></li><li><a href="{R}site/market/">Market insights</a></li><li><a href="{R}site/communities/">Communities</a></li></ul></div>
      <div><h2>Team</h2><ul><li><a href="{R}site/team/">Meet the team</a></li><li><a href="{R}site/agents/brandon-kaitlyn-oberdorfer/">Brandon &amp; Kaitlyn</a></li><li><a href="{R}site/join/">Join our team</a></li><li><a href="{R}site/office/">Team office</a></li></ul></div>
      <div><h2>Read</h2><ul><li><a href="{R}site/journal/">Journal</a></li><li><a href="{R}site/journal/#archive">At Home archive</a></li><li><a href="{R}site/contact/">Contact</a></li></ul></div>
    </div>
    <div class="attribution">
      <div class="attribution__plate"><img src="{R}assets/img/rewap-logo.webp" alt="REWAP Brokerage" width="900" height="300"><small>Real Estate Wealth Acquisition and Preservation Brokerage</small></div>
      <div class="stack" style="--stack:var(--space-3)">
        <p class="disclosure"><strong>The Oberdorfer Team</strong> is a real estate team operating under <strong>REWAP Brokerage LLC</strong>, Massachusetts. Brokerage license number, required team disclosures and office address [to be supplied and approved by the principal broker]. Listing information is provided by [MLS name] and is deemed reliable but not guaranteed. Listings on this prototype are demonstrations.</p>
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

def write(rel, R, title, desc, active, body, overlay=False, ld=None):
    path = os.path.join(OUT, rel, "index.html")
    os.makedirs(os.path.dirname(path), exist_ok=True)
    doc = head(R, title, desc, ld, "site/" + rel) + header(R, active, overlay) + f'<main id="main">{body}</main>' + footer(R)
    with open(path, "w") as f: f.write(doc)
    print("wrote", os.path.relpath(path, ROOT))

def cta_panel(R, heading_html="For the home you have. <em>For the life you are building.</em>", label="A personal standard of service", text="Buying, selling, or simply curious about your next move? We would love to be a resource.", cta=("Request a consultation", "site/contact/")):
    return f'''<section class="section"><div class="container"><div class="cta-panel folio-frame" style="--frame-inset:.85rem">
<div class="split" style="align-items:end"><div class="stack" style="grid-column:1/span 7;--stack:var(--space-5)"><p class="label" style="color:var(--ot-gold)">{label}</p><h2 class="h1">{heading_html}</h2><p class="body-l" style="color:#D9D5CC;max-width:44ch">{text}</p></div>
<div class="stack" style="grid-column:9/span 4;--stack:var(--space-4)"><a class="cta-panel__phone" href="tel:{TEL}">{PHONE}</a><p class="caption" style="color:var(--ot-mist)">Brandon &amp; Kaitlyn Oberdorfer · Husband-and-wife team · Top Producer</p><div class="cluster"><a class="btn" href="{R}{cta[1]}">{cta[0]}</a></div></div></div></div></div></section>'''

# =========================================================================== PAGES
def page_home():
    R = "../"
    feat = "".join(listing_card(R, l, f"{R}site/buy/{l['slug']}/") for l in LISTINGS[:3])
    comm = "".join(community_card(R, c) for c in COMMUNITIES[:4])
    arts = "".join(article_card(R, a) for a in ARTICLES[:3])
    body = f'''
<section class="hero-cover home-hero folio-frame" aria-labelledby="home-title">
  <div class="hero-cover__media"><img src="{R}assets/img/photo/cover-portrait.webp" alt="Brandon and Kaitlyn Oberdorfer outside a Massachusetts home in autumn" width="1103" height="1426" fetchpriority="high"></div>
  <div class="hero-cover__inner">
    <div class="home-hero__copy">
      <p class="label home-hero__issue">Massachusetts real estate · Husband-and-wife team</p>
      <h1 class="display-xl answer" id="home-title"><span>Luxury at</span><span>every price point.</span></h1>
      <p class="lead">Thoughtful service. Creative marketing. A personal connection to every move.</p>
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
    {section_head("Communities", "Across Massachusetts", ["Close to home,", "wherever home is."], lead="Local guides written the way we write <em>At Home</em>: researched, credited and useful.", aside=link_arrow(R, R+"site/communities/", "All communities"), title_id="comm-title")}
    <div class="card-grid-4">{comm}</div>
    <p class="caption" style="margin-top:var(--space-4)">Lake Quannapowitt Panorama, RedSoxFan274 / Wikimedia Commons, CC BY-SA 4.0, cropped.</p>
  </div>
</section>

<section class="section" aria-labelledby="mkt-title">
  <div class="container split" style="align-items:center">
    <div style="grid-column:1/span 5" class="stack">
      <p class="label">Market insight · Wakefield</p>
      <h2 class="display-m answer" id="mkt-title"><span>Know the market.</span><span>Love the home.</span></h2>
      <p>A useful snapshot helps you ask better questions. A thoughtful comparison helps you make better decisions.</p>
      {link_arrow(R, R+"site/market/", "Read the market insight")}
    </div>
    <div style="grid-column:7/span 6">
      <div class="stat-panel">
        <div class="stat"><span class="stat__value tabular">$786,979</span><span class="stat__label">Median sale price</span><span class="stat__period">3 months ending August 2026</span></div>
        <div class="stat"><span class="stat__value tabular">21</span><span class="stat__label">Median days on market</span><span class="stat__period">August 2026</span></div>
        <div class="stat"><span class="stat__value tabular">$483</span><span class="stat__label">Median sold price / sq. ft.</span><span class="stat__period">Rolling 3-month snapshot</span></div>
        <div class="stat"><span class="stat__value tabular">103.3%</span><span class="stat__label">Sale-to-list price</span><span class="stat__period">August 2026</span></div>
      </div>
      <p class="stat-source">Source: Redfin, Wakefield Housing Market, accessed October 10, 2026. Wakefield town, all home types. Figures may be revised; not a valuation of any home.</p>
    </div>
  </div>
</section>

<section class="section surface-muted" aria-labelledby="team-title">
  <div class="container split" style="align-items:center">
    <div style="grid-column:1/span 6" class="stack">
      <p class="label">A growing team</p>
      <h2 class="display-m answer" id="team-title"><span>One standard,</span><span>shared by every agent.</span></h2>
      <p>The Oberdorfer Team is preparing to open a team office and welcome new agents who share its care for clients and presentation.</p>
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
    write("", R, "The Oberdorfer Team · Luxury at every price point", "Brandon and Kaitlyn Oberdorfer, a husband-and-wife Massachusetts real estate team. Thoughtful service, creative marketing and a personal connection to every move.", "Home", body, overlay=False)

def page_buy():
    R = "../../"
    cards = "".join(listing_card(R, l, f"{R}site/buy/{l['slug']}/") for l in LISTINGS)
    trail = [("Home", "site/"), ("Buy", "site/buy/")]
    body = page_hero(R, trail, ["Find the home", "that fits your life."], lead="Search homes across Massachusetts, then test the routine as well as the floor plan.", aside=f'<span class="saved-count" data-saved-count>{icon(R,"heart")}<span>0 saved</span></span>') + f'''
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
    write("buy", R, "Buy a home in Massachusetts · The Oberdorfer Team", "Search homes across Massachusetts and plan your purchase with Brandon and Kaitlyn Oberdorfer: preapproval, priorities and the true cost of ownership.", "Buy", body, ld=[crumbs_ld(trail)])

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
    write(f"buy/{l['slug']}", R, f"{l['name']} (demo) · {l['town']} · The Oberdorfer Team", f"Demonstration listing page: {l['style']} in {l['town']}, Massachusetts.", "Buy", body, ld=[crumbs_ld(trail)])

def page_sell():
    R = "../../"
    trail = [("Home", "site/"), ("Sell", "site/sell/")]
    faq = [("When should I list?", "Agree on a pricing strategy before choosing a launch date. Your timing, showing preferences and next move shape the calendar, and a clear preparation plan makes the many decisions easier to manage."),
           ("How do you price a home?", "With recent closed sales that resemble your property in location, size, condition and style. Lot size, renovation quality, layout, parking and condominium fees all belong in the comparison. A listing price is a starting point; a closed sale shows what a buyer actually paid."),
           ("Do I need to stage?", "Usually a few small changes matter most: clear the entry, simplify the rooms, and show the home in good light. We help you decide what is worth doing and what is not."),
           ("What does the marketing include?", "Professional photography, a written property story and editorial presentation across the website, print and social channels. The specific package is confirmed in your consultation.")]
    faq_html = "".join(f'<details{" open" if i==0 else ""}><summary>{q}</summary><div class="accordion__body body-s"><p>{a}</p></div></details>' for i,(q,a) in enumerate(faq))
    faq_ld = {"@context":"https://schema.org","@type":"FAQPage","mainEntity":[{"@type":"Question","name":q,"acceptedAnswer":{"@type":"Answer","text":a}} for q,a in faq]}
    body = page_hero(R, trail, ["Make your home", "feel exceptional."], lead="Thoughtful presentation. Clear pricing. A plan that fits your life.", navy=True, aside=f'<a class="btn btn--gold" href="#consult">Request a market review</a>') + f'''
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
<section class="section surface-muted" id="consult"><div class="container split" style="align-items:start">
  <div style="grid-column:1/span 5" class="stack"><p class="label">Seller consultation</p><h2 class="display-m answer" id="consult-title"><span>Ask for a</span><span>property-specific review.</span></h2><p>The townwide median is a starting point. Your best comparisons are recent closed sales that resemble your home. We prepare them before we meet.</p><div class="accordion" style="margin-top:var(--space-5)">{faq_html}</div></div>
  <div style="grid-column:7/span 6" class="panel">{form(R, "seller", "Request a market review", contact_fields("sf") + field("sf-address","Property address",req=True,auto="street-address",hint="Used only to prepare comparable sales for your home.") + '<div class="form__row">' + select("sf-type","Home type",["Single-family","Condominium","Multi-family","Other"]) + select("sf-timing","Timing",["Within 3 months","3–6 months","6–12 months","Just curious"]) + '</div>' + reach_pref("sf") + field("sf-msg","Anything we should know?","textarea",opt=True,ph="Recent updates, timing, your next move…"), "Request my market review", "seller", "h3")}</div>
</div></section>
{cta_panel(R)}'''
    write("sell", R, "Sell your home · The Oberdorfer Team", "Pricing strategy, preparation, photography and launch. Request a property-specific market review from Brandon and Kaitlyn Oberdorfer.", "Sell", body, ld=[crumbs_ld(trail), faq_ld])

def page_market():
    R = "../../"
    trail = [("Home", "site/"), ("Market insights", "site/market/")]
    body = page_hero(R, trail, ["Know the market.", "Love the home."], lead="A useful snapshot helps you ask better questions. A thoughtful comparison helps you make better decisions.") + f'''
<section class="section"><div class="container split" style="align-items:start">
  <div style="grid-column:1/span 7">
    <p class="label" style="margin-bottom:var(--space-3)">Data through August 2026 / All home types / Wakefield</p>
    <div class="stat-panel">
      <div class="stat"><span class="stat__value tabular">$786,979</span><span class="stat__label">Median sale price</span><span class="stat__period">3 months ending August 2026</span></div>
      <div class="stat"><span class="stat__value tabular">21</span><span class="stat__label">Median days on market</span><span class="stat__period">August 2026</span></div>
      <div class="stat"><span class="stat__value tabular">$483</span><span class="stat__label">Median sold price / sq. ft.</span><span class="stat__period">Redfin rolling 3-month snapshot</span></div>
      <div class="stat"><span class="stat__value tabular">103.3%</span><span class="stat__label">Sale-to-list price</span><span class="stat__period">August 2026</span></div>
    </div>
    <p class="stat-source">Source: Redfin, Wakefield Housing Market, accessed October 10, 2026. Geography: Wakefield town; all home types. Price and price-per-square-foot use the site's rolling snapshot; days on market and sale-to-list are displayed for August 2026. Figures may be revised; this is not a valuation of an individual home.</p>
  </div>
  <div style="grid-column:9/span 4" class="stack" >
    <h2 class="h3">What these numbers can tell you</h2>
    <p class="body-s">The townwide median is a starting point. Your best comparisons are recent closed sales that resemble your property in location, size, condition, and style. A condo, an older single-family home, and a newly built residence can tell very different stories.</p>
    <p class="caption">Market reports for other Massachusetts communities will be added as the team publishes them, each with its own source and date.</p>
  </div>
</div></section>
<section class="section surface-muted"><div class="container"><div class="cols cols--3 col-rule">
  <div><h2 class="h4">Sold vs. asking</h2><p class="body-s" style="margin-top:var(--space-2)">A listing price is a seller's starting point. A closed sale shows what a buyer actually paid.</p></div>
  <div><h2 class="h4">Beyond square feet</h2><p class="body-s" style="margin-top:var(--space-2)">Lot size, renovation quality, layout, parking, and condominium fees all belong in the comparison.</p></div>
  <div><h2 class="h4">Your next step</h2><p class="body-s" style="margin-top:var(--space-2)">Ask for a property-specific market review before deciding what to offer or where to price.</p>{link_arrow(R, R+"site/sell/#consult", "Request a review")}</div>
</div></div></section>
{cta_panel(R, "Ready for a personal plan? <em>Call Brandon and Kaitlyn.</em>", "Property-specific market review")}'''
    write("market", R, "Market insights · Wakefield, August 2026 · The Oberdorfer Team", "Wakefield housing market snapshot through August 2026 (Redfin), and how to read it for your own home.", "", body, ld=[crumbs_ld(trail)])

def page_communities():
    R = "../../"
    trail = [("Home", "site/"), ("Communities", "site/communities/")]
    cards = "".join(community_card(R, c) for c in COMMUNITIES)
    body = page_hero(R, trail, ["Close to home,", "wherever home is."], lead="Guides to the Massachusetts communities we serve, written the way we write <em>At Home</em>: researched, credited and useful.") + f'''
<section class="section"><div class="container">
  <div class="card-grid-3" style="gap:var(--gutter)">{cards}</div>
  <p class="caption" style="margin-top:var(--space-4)">Lake Quannapowitt Panorama, RedSoxFan274 / Wikimedia Commons, CC BY-SA 4.0, cropped. Line drawings stand in for community photography still to be commissioned.</p>
  <p class="form-note" style="margin-top:var(--space-5)">Each guide is published when it is ready, never generated in bulk. The final list of communities is being confirmed by the team.</p>
</div></section>
{cta_panel(R, "Exploring a move? <em>Let's compare towns together.</em>", "Relocating within Massachusetts")}'''
    write("communities", R, "Massachusetts communities · The Oberdorfer Team", "Local guides to the Massachusetts communities served by The Oberdorfer Team.", "Communities", body, ld=[crumbs_ld(trail)])

def page_wakefield():
    R = "../../../"
    trail = [("Home", "site/"), ("Communities", "site/communities/"), ("Wakefield", "site/communities/wakefield/")]
    homes = "".join(listing_card(R, l, f"{R}site/buy/{l['slug']}/") for l in LISTINGS if l["town"] in ("Wakefield", "Reading", "Stoneham"))
    body = page_hero(R, trail, ["Close to Boston.", "Closer to what matters."], lead="A lakeside walk, a downtown errand, a train into the city. Wakefield brings those everyday moments together.", photo="assets/img/photo/lake-quannapowitt.webp", photo_alt="Lake Quannapowitt at dusk", focal="35% 55%") + f'''
<section class="section"><div class="container article">
  <div class="article__body">
    <p class="caption caption--serif">An evening on Lake Quannapowitt. Photo: RedSoxFan274 / Wikimedia Commons, CC BY-SA 4.0, cropped.</p>
    <h2 id="rhythm">Find your rhythm</h2>
    <div class="cols cols--3 col-rule" style="margin-top:var(--space-5)">
      <div><h3 class="h4">Lakeside moments</h3><p class="body-s" style="margin-top:var(--space-2)">Lake Quannapowitt is one of Wakefield's signature gathering places. Its shoreline offers a setting for walking, running, and community events.</p></div>
      <div><h3 class="h4">A town center</h3><p class="body-s" style="margin-top:var(--space-2)">The Common, Main Street, and Albion Street help anchor downtown life. Walk the area, browse the local businesses, and see how the streets fit into your day.</p></div>
      <div><h3 class="h4" id="transit">Room to explore</h3><p class="body-s" style="margin-top:var(--space-2)">Wakefield and Greenwood offer commuter rail access. Compare the trip from each home, then explore nearby Reading, Stoneham, and Melrose.</p></div>
    </div>
    <h2 id="history">History is part of the appeal</h2>
    <div class="history"><div><b>1812</b><p class="body-s">Established as South Reading.</p></div><div><b>1868</b><p class="body-s">Renamed in honor of industrialist Cyrus Wakefield.</p></div><div><p class="body-s">Look up the story of a street, a landmark, or a home before your next walk.</p></div></div>
    <p class="caption">Sources: Town of Wakefield, About Wakefield; Wakefield Historical Society, Anniversary Thinking.</p>
    <h2 id="market">The market</h2>
    <p>The median sale price was $786,979 for the three months ending August 2026, with a median of 21 days on market and a 103.3% sale-to-list ratio in August (Redfin, accessed October 10, 2026). {link_arrow(R, R+"site/market/", "Full market insight")}</p>
    <h2 id="season">This season</h2>
    <p>Six ways to enjoy Wakefield this fall, from the farmers market at Hall Park to the trails of Breakheart Reservation. {link_arrow(R, R+"site/journal/the-best-plans-are-close-to-home/", "The fall field guide")}</p>
  </div>
  <aside class="article__aside" aria-label="On this page">
    <p class="label">On this page</p>
    <ul class="toc-mini"><li><a href="#rhythm">Find your rhythm</a></li><li><a href="#history">History</a></li><li><a href="#market">The market</a></li><li><a href="#season">This season</a></li><li><a href="#homes">Homes nearby</a></li></ul>
    <div class="panel"><p class="h5">Your Wakefield guides</p><p class="body-s">Brandon &amp; Kaitlyn Oberdorfer</p><a class="btn btn--small" href="tel:{TEL}">{PHONE}</a></div>
  </aside>
</div></section>
<section class="section surface-muted" id="homes"><div class="container">{section_head("Homes nearby", "Demonstration listings", ["Homes in and", "around Wakefield."], aside=link_arrow(R, R+"site/buy/?q=wakefield", "Search Wakefield"), title_id="homes-title")}<div class="listing-grid">{homes}</div></div></section>
{cta_panel(R, "Buying, selling, or simply curious <em>about Wakefield?</em>", "Wakefield and nearby towns", "Call Brandon and Kaitlyn, or ask for a property-specific market review.")}'''
    place = {"@context":"https://schema.org","@type":"Place","name":"Wakefield, Massachusetts"}
    write("communities/wakefield", R, "Living in Wakefield, MA · The Oberdorfer Team", "Lake Quannapowitt, the town center, commuter rail and history: a local guide to Wakefield, Massachusetts.", "Communities", body, ld=[crumbs_ld(trail), place])

def page_team():
    R = "../../"
    trail = [("Home", "site/"), ("Our Team", "site/team/")]
    body = page_hero(R, trail, ["A husband-and-wife team.", "A shared commitment."], lead="Brandon and Kaitlyn lead The Oberdorfer Team. As the team grows, every agent shares the same standard of service.") + f'''
<section class="section"><div class="container">
  <form class="directory-bar" data-directory aria-label="Filter the team">
    {select("d-spec","Specialty",["All specialties","Buyers","Sellers","First-time buyers","Move-up"])}
    {select("d-area","Service area",["All of Massachusetts","Wakefield","Worcester","Boston"])}
    {select("d-lang","Language",["Any language","English"])}
    <button class="btn btn--secondary" type="reset">Reset</button>
  </form>
  <p class="results-bar__count" data-team-count aria-live="polite" style="margin-bottom:var(--space-6)">1 team profile</p>
  <h2 class="visually-hidden">Team profiles</h2><div class="card-grid-3" data-team-grid>{agent_card_bk(R)}{seat_card(R)}{seat_card(R)}</div>
</div></section>
{cta_panel(R, "Grow with a team that <em>presents every home like a feature.</em>", "Join our team", "The team is growing. Start with a confidential conversation with Brandon and Kaitlyn.", ("Start a conversation", "site/join/#recruit"))}'''
    write("team", R, "Meet the team · The Oberdorfer Team", "Brandon and Kaitlyn Oberdorfer lead The Oberdorfer Team, a husband-and-wife real estate team serving Massachusetts.", "Our Team", body, ld=[crumbs_ld(trail)])

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
<section class="section"><div class="container">{section_head("Listings", "Demonstration", ["Homes we are", "presenting now."], title_id="al-title")}<div class="listing-grid">{homes}</div></div></section>
<section class="section surface-muted"><div class="container">{section_head("From the journal", "Brandon &amp; Kaitlyn", ["Recent writing."], title_id="aj-title")}<div class="card-grid-3">{arts}</div></div></section>
<section class="section" id="contact-agent"><div class="container split" style="align-items:start">
  <div style="grid-column:1/span 5" class="stack"><p class="label">Contact</p><h2 class="display-m answer"><span>We would love</span><span>to be a resource.</span></h2><p>Messages from this page are attributed to Brandon and Kaitlyn in the team's lead routing.</p></div>
  <div style="grid-column:7/span 6" class="panel">{form(R, "agent", "Message Brandon &amp; Kaitlyn", contact_fields("af") + select("af-topic","I'm interested in",["Buying","Selling","Both","Something else"]) + field("af-msg","Message","textarea",opt=True) + reach_pref("af"), "Send my message", "agent:brandon-kaitlyn", "h3")}</div>
</div></section>'''
    person = {"@context":"https://schema.org","@type":"RealEstateAgent","name":"Brandon & Kaitlyn Oberdorfer","telephone":PHONE,"worksFor":{"@type":"Organization","name":"The Oberdorfer Team"},"areaServed":"Massachusetts"}
    write("agents/brandon-kaitlyn-oberdorfer", R, "Brandon & Kaitlyn Oberdorfer · The Oberdorfer Team", "Meet Brandon and Kaitlyn Oberdorfer, the husband-and-wife team leaders and Top Producers of The Oberdorfer Team.", "Our Team", body, ld=[crumbs_ld(trail), person])

def page_journal():
    R = "../../"
    trail = [("Home", "site/"), ("Journal", "site/journal/")]
    cards = "".join(article_card(R, a) for a in ARTICLES[1:])
    cats = ["All", "Local living", "Real estate insights", "Seasonal features", "Recipes and entertaining", "Home inspiration", "Market updates", "Community guides"]
    chips = "".join(f'<button type="button" data-cat-filter="{c}" aria-pressed="{"true" if c=="All" else "false"}">{c}</button>' for c in cats)
    body = page_hero(R, trail, ["Local living.", "Beautiful homes."], lead="Real estate insight, community guides, seasonal adventures and recipes to share. The online home of the <em>At Home</em> publications.") + f'''
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
    write("journal", R, "Journal · At Home · The Oberdorfer Team", "Local living, real estate insight, seasonal guides and recipes from The Oberdorfer Team and the At Home publications.", "Journal", body, ld=[crumbs_ld(trail)])

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
    write("journal/the-best-plans-are-close-to-home", R, "The best plans are close to home · Fall field guide · The Oberdorfer Team", "Six ways to enjoy Wakefield and the surrounding area this fall: the lake, the farmers market, Breakheart, Beebe Library, Brooksby Farm and Connors Farm.", "Journal", body, ld=[crumbs_ld(trail), art_ld])

def page_join():
    R = "../../"
    trail = [("Home", "site/"), ("Join Our Team", "site/join/")]
    body = page_hero(R, trail, ["Grow with a team", "that cares how it shows up."], lead="The Oberdorfer Team is growing. We are looking for agents who believe every client deserves luxury-level service, at every price point.", navy=True) + f'''
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
    <div><h3 class="h4">The office</h3><p class="body-s" style="margin-top:var(--space-2)">A planned team office under REWAP Brokerage. Location and timing are being confirmed.</p></div>
    <div><h3 class="h4">Marketing and technology</h3><p class="body-s" style="margin-top:var(--space-2)">[Support details to be confirmed by the team before publication.]</p></div>
    <div><h3 class="h4">Training and mentorship</h3><p class="body-s" style="margin-top:var(--space-2)">[Program details to be confirmed by the team before publication.]</p></div>
  </div>
  <p class="form-note" style="margin-top:var(--space-6)">Compensation, splits and fees are discussed privately and are never stated on this site.</p>
</div></section>
<section class="section"><div class="container split" style="align-items:start">
  <div style="grid-column:1/span 5" class="stack"><p class="label">Confidential conversation</p><h2 class="display-m answer"><span>Start with</span><span>a conversation.</span></h2><p>Your inquiry goes only to Brandon and Kaitlyn and is kept confidential.</p></div>
  <div style="grid-column:7/span 6" class="panel">{form(R, "recruit", "Recruiting inquiry", contact_fields("rf") + '<div class="form__row">' + select("rf-licensed","Are you licensed in Massachusetts?",["Yes, salesperson","Yes, broker","In progress","Not yet"]) + select("rf-years","Years in real estate",["New to real estate","1–3","4–10","10+"]) + '</div>' + field("rf-area","Where you work or live",opt=True,ph="e.g. Worcester County") + field("rf-msg","What are you looking for in a team?","textarea",opt=True), "Start the conversation", "recruiting", "h3", note="Your inquiry is confidential and goes only to Brandon and Kaitlyn.")}</div>
</div></section>'''
    write("join", R, "Join our team · The Oberdorfer Team", "The Oberdorfer Team is growing. A confidential conversation with Brandon and Kaitlyn about joining a Massachusetts real estate team.", "Join Our Team", body, ld=[crumbs_ld(trail)])

def page_office():
    R = "../../"
    trail = [("Home", "site/"), ("Team office", "site/office/")]
    body = page_hero(R, trail, ["A place to sit down", "and plan your move."], lead="The Oberdorfer Team is preparing to open its own team office. Details will appear here once they are confirmed.", aside='<span class="badge badge--verify">Planned · details to be confirmed</span>') + f'''
<section class="section"><div class="container split" style="align-items:start">
  <div style="grid-column:1/span 7" class="stack" >
    <div class="map-frame"><svg viewBox="0 0 800 500" role="img" aria-label="Illustrative map placeholder"><rect width="800" height="500" fill="#E9E2D5"/><g stroke="#FBF9F5" stroke-width="10" fill="none" stroke-linecap="round"><path d="M0 300C200 280 360 330 800 260"/><path d="M300 0C320 160 280 340 330 500"/><path d="M800 420C600 400 450 440 300 500"/></g><g transform="translate(338 284)"><circle r="20" fill="#071D2D"/><circle r="27" fill="none" stroke="#B49A65"/></g></svg><p class="map-frame__note"><span class="badge badge--demo">Illustrative map · no real location</span></p></div>
    <div class="demo-illu demo-illu--light" style="aspect-ratio:16/7;overflow:hidden">{art(R,'townscape','Illustration standing in for office photography')}</div>
  </div>
  <div style="grid-column:9/span 4" class="stack" >
    <div class="office-card">
      <h2 class="h4">The Oberdorfer Team Office</h2>
      <dl><dt>Address</dt><dd>To be confirmed</dd><dt>Phone</dt><dd><a href="tel:{TEL}">{PHONE}</a></dd><dt>Hours</dt><dd>To be confirmed</dd><dt>Brokerage</dt><dd>A team office of REWAP Brokerage LLC (formal status to be confirmed)</dd><dt>Agents</dt><dd><a href="{R}site/agents/brandon-kaitlyn-oberdorfer/">Brandon &amp; Kaitlyn Oberdorfer</a></dd></dl>
    </div>
    <div class="panel">{form(R, "appt", "Request an appointment", contact_fields("ap") + select("ap-topic","Topic",["Buying","Selling","Joining the team","Other"]), "Request an appointment", "office", "h3")}</div>
  </div>
</div></section>'''
    write("office", R, "Team office · The Oberdorfer Team", "The Oberdorfer Team is preparing to open a team office under REWAP Brokerage. Request an appointment.", "", body, ld=[crumbs_ld(trail)])

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
    body = page_hero(R, trail, ["We would love", "to hear from you."], lead="Call, text or send a message. Your inquiry goes to Brandon and Kaitlyn.") + f'''
<section class="section"><div class="container contact-grid">
  <div class="stack" style="--stack:var(--space-6)">
    <a class="cta-panel__phone" href="tel:{TEL}" style="color:var(--ot-navy);font-size:var(--step-display-m)">{PHONE}</a>
    <div>
      <div class="contact-line">{icon(R,'phone')}<div><p class="h5">Call or text</p><p class="body-s">Brandon &amp; Kaitlyn Oberdorfer · <a href="tel:{TEL}">{PHONE}</a></p></div></div>
      <div class="contact-line">{icon(R,'mail')}<div><p class="h5">Email</p><p class="body-s">[Team email to be confirmed]</p></div></div>
      <div class="contact-line">{icon(R,'door')}<div><p class="h5">Team office</p><p class="body-s">Planned. <a href="{R}site/office/">Details as they are confirmed</a>.</p></div></div>
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
    write("contact", R, "Contact · The Oberdorfer Team", "Contact Brandon and Kaitlyn Oberdorfer at 406-250-7620, or send a buyer, seller, recruiting or general inquiry.", "Contact", body, ld=[crumbs_ld(trail)])

if __name__ == "__main__":
    page_home(); page_buy(); [page_listing(l) for l in LISTINGS]; page_sell(); page_market()
    page_communities(); page_wakefield(); page_team(); page_agent(); page_journal(); page_article()
    page_join(); page_office(); page_contact()
