# Coming-soon launch: getting oberdorferteam.com live

**Status:** ready to install, 2026-10-22. **Theme:** `wordpress/oberdorfer-coming-soon/` (zip: `wordpress/dist/oberdorfer-coming-soon.zip`). **Preview:** `coming-soon/` on the project site.

The aim is to put a real, indexable page on the domain now, so it starts building history in Google and can be linked from a Business Profile, while the full site is planned with the team. The coming-soon theme is a one-page block theme built from the design system. When the full theme is ready, it replaces this one; nothing here needs migrating.

## Before you start: get approval

The page is advertising for a licensed team, so **REWAP's broker of record approves the text before it goes live**. That covers the team name, "Top Producer", the phone number, the Worcester office mention and the disclosure line ("The Oberdorfer Team is a real estate team operating under REWAP Brokerage LLC, Massachusetts."). If REWAP requires a license number or an office address in team advertising, add it to the footer paragraph in the Site Editor; don't publish a bracketed placeholder.

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

- **Do not touch MX records** if email already runs on the domain. When the team sets up Google Workspace (the recommended team email), add Google's MX records plus SPF (`v=spf1 include:_spf.google.com ~all`), DKIM from the Workspace admin console, and DMARC (`v=DMARC1; p=none; rua=mailto:<a team address>`), tightening DMARC to `quarantine` after a month of clean reports.
- If the registrar offers DNSSEC, turn it on after the records settle.
- Check propagation with `dig oberdorferteam.com +short` before issuing the SSL certificate.

## 3. WordPress settings

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
2. **Search Console.** Add a **Domain** property for `oberdorferteam.com`, verify it with the DNS TXT record, and submit `https://oberdorferteam.com/wp-sitemap.xml`. Use URL Inspection → Request indexing on the home page. Own it from the team's Google account, not a personal one.
3. **Rich Results Test.** Check the home page; the `RealEstateAgent` item should be detected with no errors.
4. **Google Business Profile.** When the Worcester address is confirmed, verify the profile and set its website to `https://oberdorferteam.com/`.
5. **HarborRank.** Connect Search Console to the project.
6. **PageSpeed Insights.** Run it once on mobile. The page should score in the green, with LCP under 2.5 s and CLS near 0.

## 6. Moving to the full site later

- Build the full theme and the `ot-core`, `ot-blocks` and `ot-leads` plugins on a Cloudways **staging** application, with search engines discouraged and basic auth on.
- Push staging to live, activate the full theme, and work through the launch checklist in `docs/wordpress-architecture.md`.
- The coming-soon theme stores no content, so switching away from it loses nothing. Keep it installed but inactive for a month, then delete it.
