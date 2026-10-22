# `ot-leads`: built-in CRM, connectors and AI nurture

**Status:** proposed spec, 2026-10-22. **Owner:** the `ot-leads` plugin (see `docs/wordpress-architecture.md`).

`ot-leads` is the team's built-in CRM. Every form on oberdorferteam.com saves into it, and it can also forward leads to an outside CRM if the team chooses one. It nurtures leads by email, drafted with AI and approved by a person. Its admin screens are mocked up on the FSE canvas in the "Lead management" row.

Nothing in this spec is legal advice. Consent wording, disclosures and retention periods must be approved by REWAP's principal broker before launch.

## 1. Principles

1. **The site always keeps its own record.** Even when a lead is forwarded to an outside CRM, `ot-leads` keeps the lead, its consent and the delivery log. No lead is ever lost because a third party was down.
2. **No invented facts, here too.** The AI writes only from the lead's own record and the site's sourced content. It follows the same rule as the site and the town guides.
3. **Fair housing applies to every message.** Personalization uses towns, price, timing and property type. It never uses who a person is or seems to be. The AI never describes who lives somewhere.
4. **A person approves by default.** AI drafts wait in a queue. Automatic sending is something the team switches on for each sequence, deliberately.
5. **Modules can be switched off.** Capture and the inbox are always on. Pipeline, connectors, nurture, newsletter and AI are modules.

## 2. Modules and what each one contains

| Module | Contains | Always on? | Phase |
|---|---|---|---|
| **Capture** | `ot/inquiry-form` block, field sets per lead type, consent checkboxes, spam protection, a thank-you state, UTM and page capture | Yes | 1 |
| **Inbox** | Leads list, lead detail, assignment, notes, CSV export, delete-on-request | Yes | 1 |
| **Routing and alerts** | Routing rules, instant email alerts, escalation when nobody responds | Yes | 1 |
| **Pipeline** | Stages, a board view, tasks and reminders, activity timeline, audit log | Switchable | 2 |
| **Connectors** | Outbox, webhook, Follow Up Boss, delivery log, retries | Switchable | 1 (webhook), 2 (Follow Up Boss) |
| **Nurture** | Sequences, templates, enrollment rules, sending through the email service, suppression list | Switchable | 3 |
| **Newsletter** | Subscribers for the *At Home* issues and monthly town updates | Switchable | 3 |
| **AI** | Drafting, summaries, next-step suggestions, intent signals, the approval queue | Switchable | 4 |
| **SMS** | Texting through a provider, after 10DLC registration | Switchable | 5 |

## 3. Data model

Leads are stored in custom database tables, not as posts. That keeps personal data out of the posts REST API, out of search, and out of exports of site content. It also scales to many rows.

| Table | One row per | Key fields |
|---|---|---|
| `ot_leads` | Person | id, first_name, last_name, email, phone, preferred_contact, lead_type, status, stage, assigned_user_id, towns (slugs), price_min, price_max, timing, property_type, property_address (sellers), score, tags, dedupe_key, created_at, last_activity_at, crm_sync_state |
| `ot_lead_events` | Thing that happened | lead_id, type (form, page_view, email_sent, email_opened, reply, note, stage_change, call_logged, assigned, exported, viewed_by), payload JSON, actor, created_at |
| `ot_consents` | Consent given or withdrawn | lead_id, channel (email_marketing, sms), granted (bool), wording_version, page_url, ip_hash, created_at |
| `ot_tasks` | Follow-up task | lead_id, assignee, due_at, kind, done_at |
| `ot_sequences` and `ot_sequence_steps` | Nurture plan and its steps | name, enrollment rule, exit rules; step delay, channel, template or AI brief, approval mode |
| `ot_enrollments` | Lead in a sequence | lead_id, sequence_id, current_step, state (active, paused, finished, exited), exit_reason |
| `ot_messages` | Message drafted or sent | lead_id, step_id, channel, subject, body, source (template, ai), status (draft, approved, sent, skipped, failed), approved_by, provider_message_id |
| `ot_outbox` | Delivery to an outside CRM | lead_id, connector, payload, attempts, next_attempt_at, state, last_error |
| `ot_suppressions` | Address that must not be emailed | email, reason (unsubscribe, bounce, complaint, manual), created_at |
| `ot_ai_log` | AI call | purpose, model, context ids, prompt hash, output, validator results, cost estimate, created_at |

**Lead types:** buyer, seller (market review), showing, community (town guide), agent (profile page), recruiting, office appointment, general, newsletter. These match the `data-lead-type` values the prototype's forms already carry, for example `community:worcester` and `agent:brandon-oberdorfer`.

**Duplicates:** a new form submission with the same email or phone is attached to the existing lead as an event, not created again. The lead's towns and interests are merged.

**Never collected:** Social Security numbers, driver's license or state ID numbers, and financial account, credit or debit card numbers. Combined with a name, these are the "personal information" that 201 CMR 17.00 covers. Forms must not ask for them, which keeps the lead data out of the regulation's scope (a lead's name, email and phone alone are not covered). Dates of birth are not collected either.

## 4. Capture

- **The block:** one `ot/inquiry-form` block with a "lead type" setting. Each type has a fixed field set; editors cannot add free fields, so data stays clean.
- **Context is captured automatically:** the source page, the town or agent or listing it relates to, UTM parameters, the first page of the visit, and the referrer.
- **Consent:**
  - The email marketing consent checkbox is separate and unchecked by default. Replying to an inquiry does not need it; nurture sequences and the newsletter do.
  - The SMS consent checkbox appears only once the SMS module is on. Its wording is approved by the broker and versioned in `ot_consents`.
- **Spam:** Cloudflare Turnstile (each token is verified server-side through Siteverify; tokens are single-use and expire after 5 minutes), a hidden honeypot field, and a per-IP rate limit. Spam never reaches the inbox and never triggers alerts.
- **After submitting:** the existing inline success state (as on the prototype), and an optional confirmation email to the lead from the assigned agent.

- **IDX lead events:** registrations, saved searches, favorites and showing requests from the IDX vendor arrive through its webhook or API (or are polled where no webhook exists). Each becomes a lead or an event on an existing lead, with the MLS number and listing URL as references only. The vendor itself is an open decision; see `docs/idx-options.md`.

## 5. Routing and alerts

Rules run in this order; the first match wins.

1. **Named agent.** An `agent:<slug>` lead goes to that agent.
2. **Town owner.** A `community:<town>` lead goes to the agent assigned to that town, if one is set on the community post.
3. **Lead type.** For example: recruiting goes to Brandon and Kaitlyn; seller goes to the listing lead.
4. **Round robin** among agents marked available, if the team has more than two agents.
5. **Fallback:** Brandon and Kaitlyn.

**Alerts:**
- An instant email to the assignee, with a one-tap "I'm on it" link that marks the lead as contacted.
- If nobody marks it contacted within the team's response window (setting, for example 15 minutes in business hours), the alert escalates to the team leaders.

## 6. Pipeline

**Stages:** New → Contacted → Appointment set → Active (showing or listing prep) → Under agreement → Closed. A lead can also move to Nurture (long-term) or Lost (with a reason).

- **Board view:** columns by stage, cards draggable, filters by agent, type, town and date.
- **Tasks:** call, email, text, meeting, other, with due dates. Due tasks appear on the inbox and in a daily digest email.
- **Timeline:** every event in one stream: forms, emails, opens, replies, notes, stage changes, who viewed or exported the lead.

**Roles and capabilities:**

| Capability | Team leader | Agent | Coordinator |
|---|---|---|---|
| See own leads | Yes | Yes | Yes |
| See all leads | Yes | No | Yes |
| Reassign leads | Yes | No | Yes |
| Approve AI messages | Yes | Own leads | No |
| Edit sequences and routing | Yes | No | No |
| Connectors, AI and privacy settings | Yes | No | No |
| Export and delete-on-request | Yes | No | No |

**Scheduling:** reminders, retries and sends run through the Action Scheduler library, triggered by a real server cron on Cloudways (not page-load WP-Cron).

## 7. Connectors: using an outside CRM

**Modes** (settings):

| Mode | What happens |
|---|---|
| **Built-in only** (launch default) | Leads stay in `ot-leads`. |
| **Built-in + forward** (recommended once a CRM is chosen) | Leads are stored here and forwarded. The team works in either place. |
| **Forward only** | Leads are forwarded; `ot-leads` keeps only a receipt, the consent record and the delivery log. Nurture and AI are off. |

**How delivery works:**
- Every lead goes into the outbox first and is sent from there.
- Failures retry after 1 minute, 5 minutes, 30 minutes, 2 hours and 12 hours, then park in a "needs attention" list with the error. Nothing is dropped.

**Connectors:**
- **Webhook (phase 1):** a signed JSON POST (HMAC header) to any URL. It covers Zapier, Make and most CRMs: Lofty, kvCORE, HubSpot, BoomTown and others.
- **Follow Up Boss (phase 2):** native, using its public API. New leads go to `POST /v1/events`, which Follow Up Boss recommends over `POST /v1/people` because it de-duplicates, runs lead flow and action plans, and alerts the agent. Requests carry the `X-System` and `X-System-Key` headers from registering the integration with Follow Up Boss. The lead's source, type, town or agent, and message are mapped. Confirm the field mapping against the current API docs at build time.
- **Later:** two-way sync, where a stage change in the CRM comes back to the site. This is only needed if the team works in both places.

## 8. Nurture

**Sequences** are written by the team. An AI brief can stand in for any step's fixed template. The starter set:

| Sequence | Enrolls when | Steps (days after enrollment) | Exits when |
|---|---|---|---|
| New buyer | Buyer or community lead with email consent | 0, 2, 7, 14, then monthly | Reply, Appointment set, unsubscribe, or the agent pauses it |
| New seller | Seller market-review lead with email consent | 0 (confirmation), 3, 10, then monthly | Same |
| Relocation | Community lead whose "I'm interested in" is "Comparing towns" | 0, 3, 10, then monthly | Same |
| Long-term nurture | Stage set to Nurture | Monthly town update | Re-engages, or unsubscribe |
| Past client | Stage Closed | Home anniversary, then quarterly | Unsubscribe |

**Monthly town updates:** one email per followed town. Each is built from that town's community record and the latest market table (source, period and geography shown, as on the site). They are drafted by AI, then approved.

**The *At Home* newsletter:** issues sent to newsletter subscribers. This replaces the need for Omnisend or a similar tool.

**Sending rules:**
- **Business hours:** send only 8 a.m. to 8 p.m. Eastern, on weekdays and Saturdays.
- **Frequency cap:** at most one marketing email per lead per 3 days.
- **Sender:** the assigned agent's name, from an oberdorferteam.com address. Replies go to the agent's inbox and are logged on the lead's timeline (phase 5 adds inbound parsing).

**Email delivery requirements:**
- A dedicated email service (Amazon SES, Postmark or Mailgun), with SPF, DKIM and DMARC published for oberdorferteam.com.
- One-click unsubscribe (RFC 8058): a `List-Unsubscribe` HTTPS URL plus `List-Unsubscribe-Post: List-Unsubscribe=One-Click`, both covered by the DKIM signature, and a visible unsubscribe link. The endpoint unsubscribes on POST only, without a login or confirmation; a GET (link scanners prefetch) never unsubscribes. Unsubscribes take effect immediately (Gmail and Yahoo allow at most two days).
- A valid physical postal address in every marketing email (CAN-SPAM): a street address, or a P.O. box or private mailbox registered to the business. **The Worcester street address, or a registered box, is therefore a launch blocker for nurture.**
- Bounces and complaints from the provider's webhooks go to `ot_suppressions` automatically.

## 9. AI module

**Provider:** an AI API such as Anthropic's Claude.
- The key lives in `wp-config.php` or an environment variable, never in the database.
- Calls run server-side only.
- Confirm the provider's data-use and retention terms before launch.

**What the AI does:**

| Job | Input | Output | Who sees it |
|---|---|---|---|
| Draft a nurture message | The sequence step's brief, the lead's record, the grounding pack | Subject and body | Approval queue |
| Summarize a lead | The lead's events | Three lines on the lead detail screen | Assigned agent |
| Suggest a next step | Stage, last activity, open tasks | One suggested action with a reason | Assigned agent |
| Intent signal | Return visits, pages viewed, opens, replies | Warm, Cooling or Quiet, with the reasons listed | Inbox |
| Draft a reply (phase 5) | An inbound email and the lead's record | Reply draft | Approval queue |

**Grounding pack:** the only material the AI may use.
- The lead's own fields and events. The data minimum is first name, towns, stage, timing, price range and interests; never phone or email.
- The community records for the lead's towns, from the same data as the town guides, with their sources.
- The current market table rows for those towns, with source, period and geography.
- The Buy and Sell explainers.
- The assigned agent's confirmed profile fields.
- Listings, only from the IDX feed, with MLS attribution.

**Prompt rules** (system prompt, versioned in the plugin):
- Write in the team's voice: warm, plain, specific, no hype.
- Use only facts present in the grounding pack. Any number must carry its source and period.
- No promises about prices, appreciation or investment returns.
- Fair housing: no descriptions of who lives in a place. No "safe", "exclusive", "family-friendly" or similar. No school ratings; link to the DESE profile instead. Never reference a lead's protected characteristics, even if they mentioned them.
- No legal, tax or lending advice; point to an attorney or lender.
- The system adds the signature, unsubscribe link and address; the AI does not write them.

**Validator:** runs on every draft before it reaches the queue.
- Every number in the draft appears in the grounding pack.
- No term from the fair-housing deny list appears.
- Length is within limits, and the subject has no unsupported claim.
- **Failures** are regenerated once; if the draft still fails, it is shown with the problem flagged, never sent.

**Approval queue:**
- Each draft offers approve, edit, regenerate or skip.
- A sequence can be switched to automatic sending only by a team leader. The screen suggests it after a set number of drafts in a row are approved without edits.
- Every send records who or what approved it.

**Audit:** `ot_ai_log` keeps the context ids, prompt hash, output, validator results and estimated cost of every call. A monthly cost total shows in settings.

## 10. Texting (phase 5)

- **Provider:** for example Twilio, after US A2P 10DLC brand and campaign registration.
- **Consent:**
  - Texts go only to leads with recorded SMS consent (prior express written consent for marketing texts under the TCPA).
  - STOP and HELP are handled automatically.
  - Quiet hours apply: no marketing calls or texts before 8 a.m. or after 8 p.m. at the recipient's location (Massachusetts, M.G.L. c. 159C § 3; the federal window is 8 a.m. to 9 p.m.).
- **Do not call:** check the National Do Not Call Registry and the Massachusetts registry (M.G.L. c. 159C, 201 CMR 12.00) before any outbound marketing calls or texts.
- **No AI voice calls:** since the FCC's February 2024 declaratory ruling, AI-generated voices count as "artificial" voices under the TCPA, so such calls need prior express consent (written consent for marketing).

## 11. Privacy, security and retention

- **Access:**
  - Admin and agent accounts require 2FA.
  - Personal data never appears in URLs, page caches or logs.
  - Lead screens send no-cache headers and are excluded from Cloudways page cache.
- **Audit:** every view, export and delete of a lead is logged.
- **Retention:**
  - Unconverted leads with no activity are deleted after a period the team sets (suggested 24 months).
  - Consent and suppression records are kept as proof for as long as the law requires.
- **Requests:** "export my data" and "delete my data" are handled from the lead screen, and the actions are logged.
- **Before launch:** the privacy policy (an open item) must describe lead storage, forwarding to a CRM, the email and AI providers, and retention.

## 11a. Engineering notes from the technical review

| Area | Requirement |
|---|---|
| Page cache and forms | Public forms post to a REST endpoint that does not depend on a WordPress nonce (Varnish would serve stale nonces). Protection comes from Turnstile (validated server-side, single-use token), the honeypot and the rate limit. |
| "I'm on it" links in alerts | Signed, expiring token that opens a confirmation page; the lead changes only on POST, so email security scanners that open links cannot mark leads contacted. |
| IP records | Store `ip_hash` as a keyed HMAC with a secret in `wp-config.php`, not a plain hash. |
| Webhook security | The HMAC signature covers a timestamp and a unique id; receivers reject old or repeated requests. |
| WordPress privacy tools | Register personal-data exporters and erasers, so Tools → Export / Erase Personal Data covers the `ot-leads` tables. |
| Backups | Cloudways backups contain lead data: restrict who can download them, and include them in the retention policy. |
| AI data minimum | Strip phone numbers and email addresses from events and replies before any AI call. |
| Intent signals | Do not rely on email opens (Apple Mail Privacy Protection inflates them); weight return visits, form submissions and replies. |
| Duplicates | Merge automatically on email; a phone-only match is suggested to the agent, not merged. |
| Confirmation emails | Rate-limit per address, so the form cannot be used to send mail to someone else. |
| Scheduling | Set `DISABLE_WP_CRON` and run a system cron that executes `wp action-scheduler run` every minute. |
| Consent revocation | Accept opt-outs by any reasonable means (reply, email, call), not only STOP or the unsubscribe link; agents can record them on the lead. |

## 12. Admin screens

They use WordPress admin components (`@wordpress/components` and DataViews), so they look native and accessible. DataViews is installed from npm (`@wordpress/dataviews/wp`) and bundled into the plugin's build; it is still marked experimental, so pin its version. The team's navy and gold appear as accents only.

| Screen | Purpose | Canvas board |
|---|---|---|
| Leads inbox | List with saved views (New today, Mine, Unassigned, Warm), filters, bulk assign | `AdminLeadsInbox` |
| Lead detail | Facts, consent, AI summary and next step, timeline, tasks, nurture status | `AdminLeadDetail` |
| Approval queue | AI drafts waiting for approval, with their grounding sources shown | `AdminApprovalQueue` |
| Pipeline board | Kanban by stage | Later |
| Sequences | Steps, briefs, enrollment and exit rules, approval mode | Later |
| Routing, connectors, AI and privacy settings | Rules, CRM mode and delivery log, AI switches, retention | Later |

## 13. Phases

| Phase | Ships | Launch blocker? |
|---|---|---|
| 1 | Capture, inbox, routing and alerts, spam protection, consent records, CSV export, webhook connector | Yes |
| 2 | Pipeline board, tasks and reminders, roles, audit log, Follow Up Boss connector | No |
| 3 | Email service, template sequences, suppressions, newsletter, monthly town updates without AI | Needs a postal address (the office street address or a registered P.O. box) |
| 4 | AI drafting, validator, approval queue, summaries and intent signals | No |
| 5 | SMS (after 10DLC), inbound email parsing, two-way CRM sync | No |

## 14. Open questions for the team

1. **Response window:** the target time to first response before an alert escalates.
2. **Town ownership:** which agent owns each town, now and as the team grows.
3. **Outside CRM:** none at launch, or a specific one? (Follow Up Boss is the first native connector planned.)
4. **Retention:** the period for unconverted leads.
5. **Broker approval:** consent wording and any REWAP advertising rules for automated email.
6. **Email:** the email service, and the sender address pattern (for example `brandon@oberdorferteam.com`).
7. **Automatic sending:** whether any sequence may ever send without approval, and who may switch that on.
