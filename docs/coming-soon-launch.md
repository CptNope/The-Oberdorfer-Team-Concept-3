# Coming-soon launch: getting oberdorferteam.com live

**Status:** ready to install, 2026-10-22. **Theme:** `wordpress/oberdorfer-coming-soon/` (zip: `wordpress/dist/oberdorfer-coming-soon.zip`). **Preview:** `coming-soon/` on the project site.

The aim is to put a real, indexable page on the domain now, so it starts building history in Google and can be linked from a Business Profile, while the full site is planned with the team. The coming-soon theme is a one-page block theme built from the design system. When the full theme is ready, it replaces this one; nothing here needs migrating.

## Before you start: get approval

The page is advertising for a licensed team, so **REWAP's broker of record approves the text before it goes live**. That covers the team name, "Top Producer", the phone number, the Worcester office address and the disclosure line ("The Oberdorfer Team is a real estate team operating under REWAP Brokerage LLC, Massachusetts. Office: 652 Park Ave, Unit B, Worcester, MA 01603."). If REWAP requires a license number in team advertising, add it to the footer paragraph in the Site Editor; don't publish a bracketed placeholder.

## 1. Cloudways: server and application

| Setting | Value | Why |
|---|---|---|
| Application | WordPress (the latest version; not the "WordPress with WooCommerce" or multisite options) | Single site |
| PHP | 8.4 (Server → Settings & Packages) | Matches the full build |
| Database | MariaDB, Cloudways default | |
| Primary domain | `oberdorferteam.com` (Application → Domain Management) | The bare domain is canonical |
| Additional domain | `www.oberdorferteam.com` | Redirected to the bare domain (below) |
| SSL | Let's Encrypt for both names, auto-renew **on** (Application → SSL Certificate) | |
| Force HTTPS | **On** (Application → Settings → General) | |
| Caching | Breeze plugin + Varnish **on**; Object Cache Pro or Redis can wait for the full site | The page is static |
| Backups | Server backups daily, keep 4 weeks (Server → Backups); take an on-demand backup before switching themes | |
| Staging | Not needed for the coming-soon page. Create a staging app later for the full theme | |
| Application credentials | Change the default WordPress admin username and use a 20-character password | |

## 2. DNS (at the domain registrar)

Find the server's public IP under Server → Master Credentials.

| Type | Name | Value | TTL |
|---|---|---|---|
| A | `@` | the Cloudways server IP | 300 while setting up, then 3600 |
| CNAME | `www` | `oberdorferteam.com` | 3600 |
| TXT | `@` | the Google Search Console verification string (step 5) | 3600 |

DNS has since moved to Cloudflare (section 2b); edit records there, not at GoDaddy.

- **Do not touch MX records** if email already runs on the domain. When the team sets up Google Workspace (the recommended team email), add Google's MX records plus SPF (`v=spf1 include:_spf.google.com ~all`), DKIM from the Workspace admin console, and DMARC (`v=DMARC1; p=none; rua=mailto:<a team address>`), tightening DMARC to `quarantine` after a month of clean reports.
- If the registrar offers DNSSEC, turn it on after the records settle.
- Check propagation with `dig oberdorferteam.com +short` before issuing the SSL certificate.

## 2a. The other domains (all registered at GoDaddy, renewing October 19 to 20, 2027)

One domain is the site; every other domain sends visitors to it with a permanent (301) redirect. Several live domains with the same content would split search signals and look like duplicates.

| Domain | Role | Setup |
|---|---|---|
| `oberdorferteam.com` | **Primary**: the site, email and Search Console | A and CNAME records to Cloudways (section 2) |
| `theoberdorferteam.com` | Redirect | 301 to `https://oberdorferteam.com/`, keeping the path |
| `oberdorferhomes.com` | Redirect | 301 to the same |
| `oberdorferluxury.com` | Redirect | 301 to the same |
| `maluxuryhomes.com` | Redirect for now (possible future brand) | 301 to the same; any other use is a team decision |
| `maluxury.homes` | Redirect for now (possible future brand) | 301 to the same |

Don't buy more variants such as `maluxuryhomes.net`. The six cover the names that matter.

**Status, 2026-10-22:** all six domains point to the Cloudways app (45.55.88.148); oberdorferteam.com is the primary domain; one Let's Encrypt certificate covers all 12 names (auto-renew on, expires January 20, 2027); the five other domains have SPF `-all` and DMARC `p=reject`. The coming-soon theme (1.0.1 and later) sends every other host to `https://oberdorferteam.com` with a 301, so the `.htaccess` rule below is only needed if the theme is not active.

**How to redirect (recommended: through Cloudways, so HTTPS works on every name)**
1. At GoDaddy, for each redirect domain: open DNS. Set the `@` A record to the Cloudways server IP (replacing GoDaddy's "Parked" record), and point the `www` CNAME to `@`. Remove any GoDaddy Forwarding on the domain.
2. In Cloudways → Domain Management, add each domain and its `www` as an additional domain. Then reissue the Let's Encrypt certificate so it covers every name. The free certificate allows up to 100 names, and here there are 12.
3. In the application's `.htaccess` (above the `# BEGIN WordPress` block), add:

   ```apache
   # Send every other domain (and www) to the primary domain, keeping the path.
   RewriteEngine On
   RewriteCond %{HTTP_HOST} !^oberdorferteam\.com$ [NC]
   RewriteRule ^ https://oberdorferteam.com%{REQUEST_URI} [R=301,L]
   ```

4. Test each one: `curl -sI https://theoberdorferteam.com/anything` should return `301` with `location: https://oberdorferteam.com/anything`, with no certificate warning, and likewise for `http://` and `www.`.

**Simpler alternative: GoDaddy Forwarding.** On each redirect domain, set Forwarding to `https://oberdorferteam.com`, type **Permanent (301)**, "Forward only" (not masking). It needs no Cloudways setup, but check `https://` on each domain afterwards. If the browser shows a certificate warning, use the Cloudways method instead.

**Protect the domains that don't send email.** Anyone can forge mail "from" a domain that has no email records. On each redirect domain, add:

| Type | Name | Value |
|---|---|---|
| TXT | `@` | `v=spf1 -all` |
| TXT | `_dmarc` | `v=DMARC1; p=reject;` |
| MX | `@` | none (delete any default GoDaddy MX records) |

Leave `oberdorferteam.com` without these. It gets the Google Workspace records instead (section 2).

**At GoDaddy, for all six:**
- Auto-renew stays **on**.
- Domain lock **on**.
- Privacy protection **on**.
- The account has two-step verification.
- The registrant contact is the business, with an email the team controls.

## 2b. Cloudflare (free plan)

**Status, 2026-10-22:** all six domains were added to Cloudflare (free plan) and their nameservers at GoDaddy were changed to `marlowe.ns.cloudflare.com` and `yahir.ns.cloudflare.com`. Cloudflare activates each zone once the change propagates (minutes to 24 hours). DNS is now edited **in Cloudflare, not GoDaddy**. This is the free Cloudflare plan in its own account, not the Cloudways Cloudflare add-on. For now the zones sit in the developer's Cloudflare account; move them to a team account (or add a team member under Manage account → Members) at handover.

| Area | Setting | Why |
|---|---|---|
| DNS | Records copied from GoDaddy: `A @` and `CNAME www` proxied (orange cloud); `_domainconnect` DNS only; the SPF, DMARC and Google verification TXT records unchanged | Same records, now behind Cloudflare |
| SSL/TLS | **Full (strict)** on all six zones | Cloudways keeps its Let's Encrypt certificate; Cloudflare checks it |
| Edge certificates (primary) | Always Use HTTPS on, minimum TLS 1.2, TLS 1.3 on, Automatic HTTPS Rewrites on | |
| Speed (primary) | HTTP/2, HTTP/3, 0-RTT, Early Hints and Speed Brain on; Rocket Loader off | Free-plan speed features; Rocket Loader can break block-theme scripts |
| Caching (primary) | Browser Cache TTL: respect existing headers; Crawler Hints on | |
| Cache rule (primary) | "Cache pages for visitors": host `oberdorferteam.com`, eligible for cache, except `/wp-admin`, `/wp-login.php`, `/wp-json`, `wp-cron.php`, preview URLs and the `wordpress_logged_in`, `wp-postpass` and `comment_author` cookies | HTML is served from Cloudflare's edge (default 2 hours, since WordPress sends no cache header). **After editing the page, purge it**: Caching → Configuration → Purge Everything |
| Rate limiting (primary) | "Limit login attempts": more than 5 POSTs to `/wp-login.php` from one IP in 10 seconds are blocked for 10 seconds | The free plan's one rate-limiting rule; stops password guessing that gets past the challenge |
| Security rules (primary) | Block `/xmlrpc.php`; Managed Challenge on `/wp-login.php` and everything under `/wp-admin` except `/wp-admin/admin-ajax.php` (front-end features call it) | Free plan allows five custom rules; the challenge is passed once per session |
| AI crawlers | Allowed (search, agent and training); Bot Preference Sync **off** so `robots.txt` comes only from the theme | Matches the decisions page |
| Redirects | Account-level Bulk Redirect list `oberdorfer_alias_domains`: the five other domains (and `www`) → `https://oberdorferteam.com/` with a 301, keeping the path and query string | Visitors are redirected at Cloudflare without reaching the server; the theme's redirect stays as a fallback |

**Activation:** all six zones were active on 2026-10-22; Google's public DNS returns Cloudflare's addresses for oberdorferteam.com.

**DNSSEC:** on for all six zones since 2026-10-22. The DS records below were added at GoDaddy the same night (Domain → DNS → DS Records → Add), and Cloudflare reported the zones active (oberdorferluxury.com, added last, was still finishing). Every zone uses key tag **2371**, algorithm **13** (ECDSA P-256 SHA-256), digest type **2** (SHA-256), with its own digest:

| Domain | Digest |
|---|---|
| oberdorferteam.com | `DBE4A4BFE1CC7EC4EB6E6435ED799356760CF330AC4E66ED4A80AF91DC8F903B` |
| theoberdorferteam.com | `830B158B615D823E36AF602C4ABB70E99C079BFA651AA675CDA7F6950F59DA2F` |
| oberdorferhomes.com | `B556B33D18220DFB6A346AEE6758C0B94E728C45A04B77AF6B0B0C10A080B226` |
| oberdorferluxury.com | `B5B3A3849AFC7A0C9E41601E41D4F60E04445EE431B7EA3CB2A9E748C0B943EB` |
| maluxuryhomes.com | `A4DB7F638AD2E54B23617D96A61E4E18A2ADF49C82B8E66F0D924A669CB5FF47` |
| maluxury.homes | `0505F8ABECFEEDC63DD62D4C3999B9481180231E32D4AC150F1B32BEFEE87D1F` |

Cloudflare's DNS → Settings page shows the same values and switches each zone to *active* once it sees the DS record (usually within an hour). **If the domains ever leave Cloudflare, remove the DS records at GoDaddy first**, or the domains stop resolving.

**Still to do:**
- Confirm oberdorferluxury.com shows DNSSEC *active* in Cloudflare (DNS → Settings).
- Check `curl -sI https://oberdorferteam.com/` shows `server: cloudflare` and, on a second request, `cf-cache-status: HIT`; check that each alias domain returns a 301 to `https://oberdorferteam.com/`.
- When Google Workspace is chosen, add its MX, SPF, DKIM and DMARC records in **Cloudflare** DNS (DNS only, grey cloud). For receiving only, Cloudflare Email Routing can forward addresses such as `team@oberdorferteam.com` to Brandon's and Kaitlyn's Gmail for free.
- Bot Fight Mode is left off: on the free plan it can't be bypassed for Let's Encrypt renewals or uptime monitors.

## 3. WordPress settings

**Status, 2026-10-22:** set as below (Site Title, Tagline, `https` addresses, New York timezone, `F j, Y` dates, registration off, search engines allowed, comments and pingbacks off, post-name permalinks). Theme 1.0.1 is active. "Hello world!", "Sample Page" and the draft Privacy Policy are in the Trash. The Two Factor plugin (WordPress.org) is active, with auto-updates on for it and Breeze. Still to do by hand: each administrator turns on two-factor in Users → Profile; delete Akismet and Hello Dolly; delete two of the three default themes; empty the Trash.

| Screen | Setting | Value |
|---|---|---|
| Settings → General | Site Title | `The Oberdorfer Team` |
| | Tagline | `Worcester & Massachusetts Real Estate` (the page title becomes "The Oberdorfer Team \| Worcester & Massachusetts Real Estate") |
| | WordPress Address and Site Address | `https://oberdorferteam.com` (no `www`, no trailing slash) |
| | Administration Email | the team's own address once it exists; until then the person who manages the site |
| | Membership | Anyone can register: **off** |
| | Timezone | `New York` |
| | Date format | `F j, Y` |
| Settings → Reading | Your homepage displays | Your latest posts (the theme's front-page template shows the coming-soon page either way) |
| | Search engine visibility | **Unchecked**, so the page can be indexed |
| Settings → Discussion | Allow people to submit comments | **Off**; also turn off pingbacks and trackbacks |
| Settings → Permalinks | Structure | Post name (`/%postname%/`), the same as the full build |
| Settings → Media | Organize uploads into month- and year-based folders | On (default) |
| Appearance → Themes | Active theme | **The Oberdorfer Team (Coming Soon)**: Add New → Upload Theme → the zip |
| Appearance → Editor | Site Icon | Optional; the theme already supplies the favicon |
| Posts, Pages | Default content | Delete "Hello world!", "Sample Page" and the draft Privacy Policy page (it would be indexable once published) |
| Plugins | Installed | Delete Akismet and Hello Dolly. Keep Breeze. Add a two-factor plugin (for example, Two Factor) for every administrator |
| Themes | Installed | Keep one default Twenty-something theme as a fallback; delete the others |
| Dashboard → Updates | Auto-updates | On for minor core releases and plugins |

Do **not** install a "maintenance mode" or "coming soon" plugin. They return a 503 status, which tells Google the site is down; this theme returns a normal page instead.

## 4. What the theme does

- **One page.** The home page returns 200 with the coming-soon content. Every other URL returns a 404 with the same content, so only the home page can be indexed.
- **Search head.** It outputs a description, a canonical link, Open Graph tags and `RealEstateAgent` JSON-LD with only confirmed facts: name, phone, Worcester, Massachusetts, REWAP.
- **Sitemap.** WordPress's `wp-sitemap.xml` lists only pages; users and taxonomies are removed.
- **robots.txt.** WordPress's default rules plus the sitemap line and an explicit allow for AI crawlers (GPTBot, OAI-SearchBot, ClaudeBot, PerplexityBot, Google-Extended). If the team decides otherwise on the decisions page, change the `robots_txt` filter in `functions.php`.
- **Closed off.** No comments, feeds, author archives, search results or attachment pages.
- **No forms.** Until there is a team email and a privacy policy, the only actions are calling and texting 406-250-7620.
- **Fonts.** These load from Google Fonts on this page; the full theme self-hosts them.
- **Editing.** All text is editable in Appearance → Editor → Patterns → *Coming soon*, or in Templates → Front Page.

## 5. After DNS and SSL work

1. **Check the page.** Load `https://oberdorferteam.com/` and confirm:
   - the `www` and `http` versions redirect to it in one hop;
   - `https://oberdorferteam.com/robots.txt` shows the sitemap line;
   - `https://oberdorferteam.com/wp-sitemap.xml` lists the home page.
2. **Search Console.** *Done 2026-10-22:* Domain property `sc-domain:oberdorferteam.com` verified by a TXT record at GoDaddy (keep that record), sitemap submitted and home-page indexing requested. It is owned for now by the developer's Google account; add the team's Google account as an **Owner** (Settings → Users and permissions) once it exists, then hand over. Original step: add a **Domain** property for `oberdorferteam.com`, verify it with the DNS TXT record, and submit `https://oberdorferteam.com/wp-sitemap.xml`. Use URL Inspection → Request indexing on the home page. Own it from the team's Google account, not a personal one.
3. **Rich Results Test.** Check the home page; the `RealEstateAgent` item should be detected with no errors.
4. **Google Business Profile.** The address is confirmed (652 Park Ave, Unit B, Worcester, MA 01603). Verify the profile, entering the address exactly as on the site, and set its website to `https://oberdorferteam.com/`.
5. **HarborRank.** Connect Search Console to the project.
6. **PageSpeed Insights.** Run it once on mobile. The page should score in the green, with LCP under 2.5 s and CLS near 0.

## 6. Moving to the full site later

- Build the full theme and the `ot-core`, `ot-blocks` and `ot-leads` plugins on a Cloudways **staging** application, with search engines discouraged and basic auth on.
- Push staging to live, activate the full theme, and work through the launch checklist in `docs/wordpress-architecture.md`.
- The coming-soon theme stores no content, so switching away from it loses nothing. Keep it installed but inactive for a month, then delete it.
