---
version: 1
slug: "wordpress-ot-leads-includes-admin-php"
primary_target: "wordpress/ot-leads/includes/admin.php"
related_targets: ["wordpress/ot-leads/includes/admin-today.php"]
---

# Leads workspace (ot-leads admin)

Scope: every Leads screen in wp-admin (Today, Inbox, Pipeline, Approvals, Reports, Sequences, Newsletter, Settings, the lead screen). Mode: Operate. Users: Brandon and Kaitlyn (and team leaders), on desktop and phone equally. Job: answer new people fast, keep follow-ups moving, never lose a returning visitor. Constraints: WordPress admin markup and forms stay (security, nonces, list table); WCAG 2.2 AA; no invented data.

## Direction contract

THESIS: Today is a day log, one timeline of people who need a reply, follow-ups due and people back on the site, with the reply action on every row; it refuses the stock WordPress dashboard of grey widgets and a list table as home.

OWN-WORLD: ivory ground, navy rail with a gold active rule, white paper panels with sandstone hairlines, square corners, Cormorant for people's names and screen titles, Manrope for all UI, outlined status tags (navy new, red late, gold-ink warm, green done). Hairline rules, no shadows.

STORY: they open Today, read one sentence telling them who to answer first, tap Call, Text or Email, then Log what happened; the next follow-up books itself.

FIRST VIEWPORT: navy rail left (Today, All leads, Pipeline, Approvals, Reports, then Sequences, Newsletter, Settings); main column: the headline sentence in Cormorant with its answering line in gold-ink italic, the date and agent filter chips on one line, then "Needs you now" rows; right column: This week agenda and reply speed. On phones the rail becomes a top strip and rows stack.

FORM: 1 Day log, dealt index 7 of 7, seed key 5d422a6e.

FINISH: unreviewed and undocumented is unfinished; this build ends with the finish review, the verdict, DESIGN.md, and every shipping raster carrying its provenance

Signature interaction: the Log disclosure on each row opens with the top-down clip wipe (DESIGN.md motion) and books the next follow-up in one tap.
