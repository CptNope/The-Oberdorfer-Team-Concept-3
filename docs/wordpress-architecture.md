# WordPress architecture: what is built in, what is a plugin

**Status:** proposed, 2026-10-22. **Hosting:** Cloudways, PHP 8.4, WordPress 7.x.

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
| CPTs: agent, community, office, testimonial, publication | `ot-core` | Taxonomies: region, specialty, language, service area |
| Custom fields | `ot-core` | `register_post_meta` with `show_in_rest`; no ACF or other fields plugin |
| Field display in templates | `ot-core` + theme | `ot-core` registers Block Bindings sources; the theme's templates bind core blocks to them |
| JSON-LD (RealEstateAgent, Person, ProfilePage, City, WebPage, BreadcrumbList, Dataset) | `ot-core` | Same graph as the prototype, with `@id` links; no FAQPage on new templates |
| Titles, meta descriptions, canonicals, robots | `ot-core` | A title and description field on every post type; no SEO plugin needed. An SEO plugin is optional, but only one may output meta tags. |
| XML sitemap | WordPress core + `ot-core` | Core's `wp-sitemap.xml`, with CPTs included and demo or unpublished items excluded |
| `llms.txt` | `ot-core` | Generated route; same content rules as the prototype |
| Custom blocks `ot/*` | `ot-blocks` | Built with `@wordpress/scripts`; server-rendered where SEO matters |
| Forms, including `ot/inquiry-form` | `ot-leads` | No form plugin. Spam protection: Cloudflare Turnstile, a honeypot and rate limiting. |
| Leads, pipeline, routing, tasks | `ot-leads` | Custom database tables, not posts |
| CRM connectors | `ot-leads` | Webhook first, then Follow Up Boss |
| Email nurture, newsletter, AI drafting | `ot-leads` (modules) | Each module can be switched off |
| Listings and search | IDX provider plugin | Required by MLS rules; the theme styles its output with `ot/listing-card` |
| Outgoing email delivery | Email service (Amazon SES, Postmark or Mailgun) | SPF, DKIM and DMARC on oberdorferteam.com. WordPress core mail goes through the same service. |
| Two-factor login | Third-party 2FA plugin | Required for every admin and agent account |
| Caching, CDN, backups, firewall, staging | Cloudways | Exclude form endpoints and the admin from page cache |
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

Each first-party plugin has its own version, changelog and database migrations, so the plugins and the theme can ship on separate schedules.
