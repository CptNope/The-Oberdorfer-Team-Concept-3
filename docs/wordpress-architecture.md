# WordPress architecture: what is built in, what is a plugin

**Status:** proposed 2026-10-22; first build (theme and `ot-core`) released as v0.1.0, a pre-release for staging, 2026-10-25 (`docs/wordpress-build.md`). **Hosting:** Cloudways, PHP 8.4, WordPress 7.x.

The production site for oberdorferteam.com is a custom block theme plus a small set of first-party plugins. Every responsibility below sits in exactly one place. The rule for choosing is simple: **if losing it on a theme change would lose data or break saved content, it is not in the theme.**

## The layers

| Layer | What it is | Holds | Why here |
|---|---|---|---|
| **Theme** `oberdorfer` | Block theme | `theme.json` (generated from `assets/css/tokens.css`), templates, template parts (header, footer), patterns, block style variations, front-end CSS and JS | Presentation only. A redesign replaces the theme without touching content, leads or SEO. |
| **Must-use plugin** `ot-core` | Always on; cannot be deactivated from the admin | Custom post types and taxonomies, registered post meta, Block Bindings sources, JSON-LD, SEO fields and output, sitemap additions, `llms.txt` | The content model and search output must survive any theme change. |
| **Plugin** `ot-blocks` | Custom `ot/*` blocks | The blocks in the design system's WordPress mapping (`design-system/index.html`) that store content in posts: market table, quick facts, stat panel, history, testimonial, action bar and so on | Saved posts reference these blocks; they must not disappear with the theme. |
| **Plugin** `ot-leads` | The built-in CRM, in modules | Forms, lead storage, routing, pipeline, tasks, notifications, consent, connectors, nurture, AI. See `docs/leads-crm-nurture-spec.md`. | Leads are business records with their own database tables, permissions and privacy duties. |
| **Third-party plugins** | Installed, not built | IDX/MLS provider plugin; 2FA | Licensed data or security-critical code is not worth building. |
| **Host and services** | Configured, not built | Cloudways caching (Varnish, Breeze), backups, firewall, SSL, staging; email delivery service; SMS provider (later); AI API | Infrastructure. |

## Who owns which responsibility

| Responsibility | Where | Notes |
|---|---|---|
| Colors, type, spacing | Theme (`theme.json`) | Generated from `tokens.css` by `tools/export_tokens.py`; never hand-edited |
| Templates and template parts | Theme | One per board on the FSE canvas |
| Patterns | Theme | The pattern names on the FSE boards' blue chips |
| CPTs: agent, community, office, testimonial, publication | `ot-core` | Taxonomies: region, specialty, language, service area. `community` is hierarchical: a neighborhood guide is a child of its city (`/communities/worcester/tatnuck/`), uses the same fields plus boundaries and areas, and shows the city's market figures and tax rate rather than its own. |
| Custom fields | `ot-core` | `register_post_meta` with `show_in_rest`; no ACF or other fields plugin |
| Field display in templates | `ot-core` + theme | `ot-core` registers Block Bindings sources; the theme's templates bind core blocks to them. Bindings reach only some attributes of a few core blocks (paragraph, heading, image, button and a few others), and `core/post-meta` needs `show_in_rest` and a key without a leading underscore. Structured fields go through `ot/*` blocks. |
| JSON-LD (RealEstateAgent, Person, ProfilePage, City, WebPage, BreadcrumbList, Dataset) | `ot-core` | Same graph as the prototype, with `@id` links; no FAQPage on new templates |
| Titles, meta descriptions, canonicals, robots | `ot-core` | A title and description field on every post type; no SEO plugin needed. An SEO plugin is optional, but only one may output meta tags. |
| XML sitemap | WordPress core + `ot-core` | Core's `wp-sitemap.xml` includes public CPTs automatically; `ot-core` excludes demo items with `wp_sitemaps_posts_query_args` (unpublished items are never listed) |
| `llms.txt` | `ot-core` | Generated route; same content rules as the prototype |
| Custom blocks `ot/*` | `ot-blocks` | Built with `@wordpress/scripts`; server-rendered where SEO matters |
| Forms, including `ot/inquiry-form` | `ot-leads` | No form plugin. Spam protection: Cloudflare Turnstile, a honeypot and rate limiting. |
| Leads, pipeline, routing, tasks | `ot-leads` | Custom database tables, not posts |
| CRM connectors | `ot-leads` | Webhook first, then Follow Up Boss |
| Email nurture, newsletter, AI drafting | `ot-leads` (modules) | Each module can be switched off |
| Listings and search | IDX provider plugin (open decision: `docs/idx-options.md`) | Licensed MLS PIN data; the theme styles its output with `ot/listing-card`, and vendor lead events (registrations, saved searches, inquiries) go to `ot-leads`. A future own feed would live in a separate `ot-idx` plugin, never in `ot-leads` or the theme. |
| Outgoing email delivery | Email service (Amazon SES, Postmark or Mailgun) | SPF, DKIM and DMARC on oberdorferteam.com. WordPress core mail goes through the same service. |
| Two-factor login | Third-party 2FA plugin | Required for every admin and agent account |
| Caching, backups, staging | Cloudways | Exclude form endpoints and the admin from page cache |
| CDN, DNS, edge cache, firewall | Cloudflare (free plan) | Settings in `docs/coming-soon-launch.md` section 2b; purge the cache after publishing |
| Analytics | `ot-core` setting | One GA4 measurement ID field; Search Console is verified at the domain (DNS) |

## Deliberately not used

| Not used | Why |
|---|---|
| Page builders | The block theme and patterns cover layout; builders lock content into their markup. |
| ACF or other fields plugins | Core post meta and Block Bindings do the job (decided 2026-10-22). |
| Form plugins | `ot-leads` owns capture, so every lead lands in one place with consent and routing. |
| A separate email-marketing plugin (for example Omnisend) | `ot-leads` nurture and newsletter modules cover it, or the connected CRM does. |
| An SEO plugin (by default) | `ot-core` outputs the same structured data the prototype was built and tested with. |

## Code layout

```text
wp-content/
  themes/oberdorfer/          theme.json, templates/, parts/, patterns/, styles/, assets/
  mu-plugins/ot-core.php       loader
  mu-plugins/ot-core/          cpt/, meta/, bindings/, schema/, seo/
  plugins/ot-blocks/           src/<block>/block.json, build/
  plugins/ot-leads/            src/ (PHP), admin/ (React), migrations/, connectors/, modules/
```

**Must-use plugin caveats:** WordPress does not auto-update must-use plugins and runs no activation hooks for them. `ot-core` keeps a stored schema version and flushes rewrite rules and runs migrations when that version changes. Deployments update it with the theme.

**Sitemap:** remove core's users (author) provider; the agent CPT pages are the people pages, and author archives would expose usernames.

Each first-party plugin has its own version, changelog and database migrations, so the plugins and the theme can ship on separate schedules.

## Launch checklist (search)

From the SEO audit of October 22, 2026 (`seo/reports/oberdorferteam.com-audit/`). Work through it on staging, then again on production after the DNS switch.

**Indexing (the prototype is `noindex` on every page; none of that may carry over)**
- [ ] Settings → Reading: "Discourage search engines" is **off** on production and **on** on staging. Staging also sits behind Cloudways basic auth.
- [ ] `ot-core` outputs `index, follow` on public pages and `noindex` only on demo content, the agent template, search results, and `?s=`, `/wp-admin/` and `ot-leads` endpoints.
- [ ] Every public page has a self-referencing canonical on `https://oberdorferteam.com/` (no `/site/`, no trailing `index.html`).
- [ ] `www` and `http` redirect to `https://oberdorferteam.com/` in one hop; HSTS is on.

**robots.txt** (served by WordPress through `ot-core`'s `robots_txt` filter, not a static file)

```text
User-agent: *
Disallow: /wp-admin/
Allow: /wp-admin/admin-ajax.php
Disallow: /?s=
Disallow: /search/

# AI crawlers: see the decision "AI search crawlers" on the decisions page.
# Recommended: allow, so answer engines can cite the town guides.
User-agent: GPTBot
Allow: /
User-agent: OAI-SearchBot
Allow: /
User-agent: ClaudeBot
Allow: /
User-agent: PerplexityBot
Allow: /
User-agent: Google-Extended
Allow: /

Sitemap: https://oberdorferteam.com/wp-sitemap.xml
```

**Search Console and discovery**
- [ ] Verify `oberdorferteam.com` as a Domain property (DNS TXT), owned by the team's Google Workspace account.
- [ ] Submit `https://oberdorferteam.com/wp-sitemap.xml`; confirm the community, agent and page sitemaps list every public URL and no demo listings.
- [ ] Serve `llms.txt` at the root (an `ot-core` route) with the same content rules as the prototype.
- [ ] Connect Search Console to HarborRank.

**Structured data and speed**
- [ ] Rich Results Test passes on the home, a town guide, an agent profile and the market page; the `RealEstateAgent` node has `url`, `logo`, `image`, and `address` and `sameAs` once confirmed.
- [ ] Fonts are self-hosted in the theme (Cormorant Garamond 300 to 600 with italics, Manrope variable), as WOFF2 with `font-display: swap`. Preload the two most used files. The prototype still uses Google Fonts because the font packages were not reachable from the build workspace.
- [ ] CSS is bundled and minified. Site templates do not load the documentation stylesheet (`docs.css`).
- [ ] The hero image on each template is preloaded with `fetchpriority="high"`. Every `<img>` has `width` and `height`.
- [ ] Field Core Web Vitals (LCP, INP, CLS) are checked in Search Console four weeks after launch.
