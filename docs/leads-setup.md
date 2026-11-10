# Setting up Oberdorfer Leads (`ot-leads`) on Cloudways

**For:** the developer, once, per site (staging, then production). **Plugin:** `wordpress/ot-leads/` (spec: `docs/leads-crm-nurture-spec.md`). **Decided 2026-11-05:** Brevo sends the site's email; Rackspace mailboxes (Cloudways add-on) are where people read and reply.

Until the steps below are done, the plugin still works: forms save every lead, alerts go out through WordPress mail, and spam protection is the hidden field plus rate limit. Do the steps in order; DNS changes can take an hour to be seen.

## 1. Install

1. Plugins > Add New > Upload `ot-leads.zip` (from the GitHub release or `https://cptnope.github.io/The-Oberdorfer-Team-Concept-3/wordpress/ot-leads.zip`) and activate. It needs `ot-core` active.
2. Open the WordPress dashboard once. The tables are created on activation; nothing else to run.
3. Leads > Settings > **Send me a test email**.

## 2. Mailboxes: Rackspace on Cloudways

1. Cloudways > Add-ons > Rackspace Email: add the domain `oberdorferteam.com` and the mailboxes. Proposed: `brandon@`, `kaitlyn@` and a shared `hello@` (the address the site sends from; replies to it reach a person). Cloudways listed $1.50 per mailbox a month from Sept 1, 2026.
2. In Cloudflare DNS for oberdorferteam.com (all records **DNS only**, grey cloud):

| Type | Name | Value | Priority |
|---|---|---|---|
| MX | `@` | `mx1.emailsrvr.com` | 10 |
| MX | `@` | `mx2.emailsrvr.com` | 20 |
| CNAME | `autodiscover` | `autodiscover.emailsrvr.com` | |

3. Once mail arrives, change Leads > Settings > **Team leaders** to the new addresses, and each agent's **Email** in Agents > (profile) > Agent details. Alerts go to the agent's email; replies to the site's emails go there too.

## 3. Sending: Brevo

1. Create the Brevo account (owner: Brandon or Kaitlyn). The free plan allows 300 emails a day, shared by everything, and adds "Sent with Brevo" branding; choose a paid plan before launch (check current pricing at brevo.com).
2. Brevo > Settings > Senders, domains & dedicated IPs > **Domains** > Add `oberdorferteam.com` and **Authenticate**. Brevo shows the exact records for this account: a `brevo-code` TXT record and DKIM records (current accounts get two CNAMEs, `brevo1._domainkey` and `brevo2._domainkey`). Add them in Cloudflare as **DNS only**, then press Verify.
3. **One SPF record** for both services (replace any existing `v=spf1` record; a domain may have only one):

| Type | Name | Value |
|---|---|---|
| TXT | `@` | `v=spf1 include:emailsrvr.com include:spf.brevo.com ~all` |

4. **DMARC** (Gmail and Yahoo require one for bulk senders). Start in monitoring mode, then tighten to `p=quarantine` after two to four weeks of clean reports:

| Type | Name | Value |
|---|---|---|
| TXT | `_dmarc` | `v=DMARC1; p=none; rua=mailto:hello@oberdorferteam.com` |

5. Brevo > Senders: add the address Leads > Settings > Sender uses. It must be a real mailbox at the domain.
6. Brevo > SMTP & API > **API keys** > Generate. Put it in `wp-config.php` (preferred) or Leads > Settings > Email:
   ```php
   define( 'OT_LEADS_BREVO_KEY', 'xkeysib-…' );
   ```
7. Brevo > Transactional > Settings > **Webhook** (it opens Settings > Webhooks) > Add webhook > **Outbound**:
   - Name: letters, numbers, hyphens and underscores only (e.g. `website-leads-live`).
   - URL: shown in Leads > Settings > Email.
   - Authentication: **Token**, with the token shown there (the code only, without "Bearer").
   - Event category **Transactional email**. Leave on only Blocked, Invalid, Hard Bounced, Clicked, Complaint and Unsubscribed; turn the others off.

   Bounced and complaining addresses are then never emailed again.
8. Leads > Settings > **Send me a test email**: the Status panel should say Brevo, and Brevo > Transactional > Logs should show it delivered.

**Done on 2026-11-10, for staging.** The Brevo account belongs to brandon@oberdorferteam.com. The domain is authenticated (the `brevo-code` TXT record plus `brevo1`/`brevo2._domainkey` CNAMEs in Cloudflare). The sender is "The Oberdorfer Team" <brandon@oberdorferteam.com> (Leads > Settings > Sender: `brandon`). The webhook `website-leads-staging` points at the staging host. The test email was delivered. DMARC is still the GoDaddy record (`p=quarantine`, reports to onsecureserver.net); point `rua` at brandon@ when convenient.

**At launch (the DNS and domain steps carry over; only the site-specific parts change):**
- **API key:** if live is a clone of staging it carries over. Otherwise paste it, or better, generate a second key named "live site", so the staging key can be revoked after launch.
- **Webhook:** edit `website-leads-staging` rather than adding a second one.
  - Set its URL to `https://oberdorferteam.com/wp-json/ot-leads/v1/brevo`.
  - Rename it to `website-leads-live`.
  - If live is a fresh install (not a clone), paste the live site's own token from its Leads > Settings.
- **Test:** send the test email from the live site's Leads > Settings.

## 4. Spam protection: Cloudflare Turnstile

**Done on 2026-11-10:**
- The widget "The Oberdorfer Team forms" covers `oberdorferteam.com` and the staging host, so the same keys work after the push.
- Its keys are set on staging.
- All 69 pages with forms carry the check.
- DMARC reports now go to brandon@ (`p=quarantine` kept).
- The every-minute `wp-cron.php` job is set on both Cloudways apps (staging and live), because a push does not copy cron jobs.

Cloudflare > Turnstile > Add widget: hostnames `oberdorferteam.com` and the staging host, mode **Managed**. Paste the site key into Leads > Settings, and the secret into wp-config.php:

```php
define( 'OT_LEADS_TURNSTILE_SECRET', '0x…' );
```

## 5. The scheduler

Alerts escalate and emails send from a once-a-minute job. In `wp-config.php`:

```php
define( 'DISABLE_WP_CRON', true );
```

Cloudways > Application > Application Settings > **Cron Job Management** > Add, every minute:

```
* * * * * wget -q -O - "https://oberdorferteam.com/wp-cron.php?doing_wp_cron" >/dev/null 2>&1
```

Leads > Settings > Status shows when it last ran.

## 6. People and routing

- **Users:** create a WordPress user for each agent with the role **Team agent**, and set Users > (user) > **Agent profile**. They see only their own leads. Team leaders are Administrators; a coordinator can be an Editor (sees every lead, cannot change settings).
- **Town owners and response window:** Leads > Settings > Routing. Until agents join, leave towns on "Team" (Brandon and Kaitlyn) and the window at 15 minutes.
- **Secret for signed links** (optional; otherwise the WordPress salts are used):
  ```php
  define( 'OT_LEADS_SECRET', 'a long random string' );
  ```

## 7. Optional

- **An agent's own CRM:** Leads > Settings > Each agent's CRM. Their leads are forwarded there; choose whether their CRM or this site sends their follow-up emails. To let a CRM tell the site about unsubscribes or stage changes, give it the endpoint and bearer token shown on that card.

- **Outside CRM:** Leads > Settings > Outside CRM. A Zapier or Make webhook, or Follow Up Boss (API key, plus the X-System name and key from registering the integration with Follow Up Boss).
- **AI drafting:** an Anthropic API key (`define( 'OT_LEADS_ANTHROPIC_KEY', '…' );`), then tick AI drafting. Off by default; every draft waits for approval unless a team leader sets a sequence to send automatically.

## Before launch (blockers)

- REWAP approves the consent wording (Leads > Settings > Email) and any rules for automated email.
- The privacy policy (/privacy/, written 2026-11-10 from `tools/data/privacy-policy.json`) covers lead storage, the page-visit cookie (`ot_lid`), Brevo, an outside CRM and the AI provider if used, and the 24-month retention. REWAP reviews it; update the JSON and rebuild if any of these change.
- The postal address in every marketing email is the office: 652 Park Ave, Unit B, Worcester, MA 01603.
