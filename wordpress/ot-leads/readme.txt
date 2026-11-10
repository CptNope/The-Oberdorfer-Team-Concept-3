=== Oberdorfer Leads ===
Contributors: oberdorferteam
Requires at least: 7.1
Requires PHP: 8.3
Stable tag: 0.9.0
License: GPL-2.0-or-later

The Oberdorfer Team's built-in CRM: lead capture, routing and alerts, inbox and pipeline, CRM forwarding, email nurture through Brevo, and AI drafts a person approves.

== Description ==

Every form on oberdorferteam.com saves here first. Leads are routed to the right agent with an instant alert, worked in an inbox and a pipeline board, optionally forwarded to an outside CRM, and nurtured by email with sequences, monthly town updates, new-listing alerts and the At Home newsletter. Setup: docs/leads-setup.md in the project repository. Spec: docs/leads-crm-nurture-spec.md.

Requires the ot-core plugin.

== Changelog ==

= 0.9.0 =
* Leads > Today: new leads waiting for a first reply, follow-ups due or overdue, people back on the site, the week ahead.
* One-tap logging (Called, Voicemail, No answer, Texted, Emailed, Met, They replied) that counts as the first reply, closes the task and books the next follow-up.
* Morning email per person, combining their own and the team's leads.
* Signals: return visits after 7+ days, listing views, new town guides (added to their towns) and three pages in a day create a call task and email the agent, at most every 3 days.
* A daily check-in task for working leads quiet for 14 days.
* Home anniversary sequence, yearly from the closing day.
* Each agent's own CRM (webhook or Follow Up Boss): forwarding on arrival and reassignment, a choice of who sends follow-up (never both), unsubscribes sent to their webhook, and an endpoint for CRM updates back (unsubscribed, stage).
* Reports: response times, sources, campaigns, pages, towns, stages, by month, by agent; duplicate finder with merge.
* Health check on Today, the dashboard, Settings and Site Health.
* Database: the outbox connector column holds longer names (upgrades itself).

= 0.8.2 =
* Email footer: "The Oberdorfer Team · 652 Park Ave, Unit B, Worcester, MA 01603 · Operating under REWAP Brokerage LLC", so Unit B is not read as REWAP's address.

= 0.8.1 =
* Version moves with the theme 0.8.1. No changes.

= 0.8.0 =
* Version moves with the release. No changes.

= 0.7.6 =
* Version moves with the theme 0.7.6. No changes.

= 0.7.5 =
* Version moves with the theme 0.7.5. No changes.

= 0.7.4 =
* Version moves with ot-core 0.7.4. No changes.

= 0.7.3 =
* Version moves with ot-core 0.7.3. No changes.

= 0.7.2 =
* Version moves with ot-core 0.7.2. No changes.

= 0.7.1 =
* Messages and the form's error text use the team line (now 978-257-4938) instead of a fixed number.

= 0.7.0 =
* Version moves with ot-core 0.7.0. No changes.

= 0.6.2 =
* Version moves with ot-core 0.6.2. No changes.

= 0.6.1 =
* Version moves with ot-core 0.6.1. No changes.

= 0.6.0 =
* First release: capture (Turnstile, honeypot, rate limit, consent records, merging), routing and alerts with "I'm on it" and escalation, inbox, lead screen, board, approval queue, sequences, newsletter, settings, Brevo sending with one-click unsubscribe and the bounce webhook, webhook and Follow Up Boss connectors with retries, AI drafting with checks, roles, privacy export and erase, retention.
