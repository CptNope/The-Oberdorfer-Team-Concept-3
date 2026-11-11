<?php
/**
 * Leads > Settings: routing and alerts, spam protection, email (Brevo), CRM connectors, AI and
 * privacy, plus a status panel. Secrets can live in wp-config.php instead (the field then says
 * so and cannot be edited here).
 */

defined( 'ABSPATH' ) || exit;

add_action( 'ot_leads_admin_action', function ( $do ) {
	if ( ! current_user_can( 'ot_leads_manage' ) ) {
		return;
	}
	$back = admin_url( 'admin.php?page=ot-leads-settings' );
	if ( 'settings' === $do ) {
		$in  = wp_unslash( $_POST );
		$cur = wp_parse_args( (array) get_option( 'ot_leads_settings', array() ), ot_leads_defaults() );
		$txt = array( 'leaders', 'turnstile_site', 'from_domain', 'from_team', 'webhook_url', 'fub_system', 'ai_model', 'consent_text' );
		foreach ( $txt as $k ) {
			if ( isset( $in[ $k ] ) ) {
				$cur[ $k ] = 'webhook_url' === $k ? esc_url_raw( trim( $in[ $k ] ) ) : sanitize_text_field( $in[ $k ] );
			}
		}
		foreach ( array( 'turnstile_secret', 'brevo_key', 'webhook_secret', 'fub_key', 'fub_system_key', 'ai_key' ) as $k ) {
			// Secret fields are blank on screen; a blank submission keeps the stored value.
			if ( isset( $in[ $k ] ) && '' !== trim( $in[ $k ] ) && ! ot_leads_opt_locked( $k ) ) {
				$cur[ $k ] = trim( sanitize_text_field( $in[ $k ] ) );
			}
			if ( ! empty( $in[ 'clear_' . $k ] ) ) {
				$cur[ $k ] = '';
			}
		}
		foreach ( array( 'response_minutes' => array( 5, 240 ), 'hours_start' => array( 0, 23 ), 'hours_end' => array( 1, 24 ), 'retention_months' => array( 6, 120 ), 'freq_days' => array( 1, 30 ) ) as $k => $r ) {
			if ( isset( $in[ $k ] ) ) {
				$cur[ $k ] = min( $r[1], max( $r[0], (int) $in[ $k ] ) );
			}
		}
		$cur['confirm_email'] = empty( $in['confirm_email'] ) ? 0 : 1;
		$cur['ai_enabled']    = empty( $in['ai_enabled'] ) ? 0 : 1;
		$cur['crm_mode']      = in_array( $in['crm_mode'] ?? '', array( 'builtin', 'both', 'forward' ), true ) ? $in['crm_mode'] : 'builtin';
		$cur['town_owners']   = array_filter( array_map( 'sanitize_title', (array) ( $in['town_owners'] ?? array() ) ) );
		$cur['type_owners']   = array_filter( array_map( 'sanitize_title', (array) ( $in['type_owners'] ?? array() ) ) );
		$cur['alert_cc']      = array();
		foreach ( (array) ( $in['alert_cc'] ?? array() ) as $slug => $list ) {
			$ok = array_filter( array_map( fn( $e ) => sanitize_email( trim( $e ) ), explode( ',', (string) $list ) ), 'is_email' );
			if ( $ok && isset( ot_leads_agents()[ sanitize_title( $slug ) ] ) ) {
				$cur['alert_cc'][ sanitize_title( $slug ) ] = implode( ', ', $ok );
			}
		}
		if ( empty( $cur['brevo_token'] ) ) {
			$cur['brevo_token'] = wp_generate_password( 32, false );
		}
		update_option( 'ot_leads_settings', $cur, false );
		update_option( 'ot_leads_alerts_auto', empty( $in['alerts_auto'] ) ? 0 : 1, false );
		wp_safe_redirect( add_query_arg( 'ot_msg', rawurlencode( 'Settings saved.' ), $back ) );
		exit;
	}
	if ( 'test_email' === $do ) {
		list( $ok, $info ) = ot_leads_mail( wp_get_current_user()->user_email, 'Test from Oberdorfer Leads', '<p>This is a test of the site\'s email. If you are reading it, sending works' . ( ot_leads_opt( 'brevo_key' ) ? ' through Brevo' : ' through WordPress mail' ) . '.</p>', array( 'tags' => array( 'test' ) ) );
		wp_safe_redirect( add_query_arg( 'ot_msg', rawurlencode( $ok ? 'Test email sent to ' . wp_get_current_user()->user_email . ' (' . $info . ').' : 'Test failed: ' . $info ), $back ) );
		exit;
	}
	if ( 'retry_outbox' === $do ) {
		global $wpdb;
		$wpdb->query( 'UPDATE ' . ot_leads_table( 'outbox' ) . " SET state = 'pending', attempts = 0, next_attempt_at = UTC_TIMESTAMP() WHERE state = 'failed'" );
		ot_leads_outbox_run();
		wp_safe_redirect( add_query_arg( 'ot_msg', rawurlencode( 'Retried failed deliveries.' ), $back ) );
		exit;
	}
} );

function ot_leads_secret_field( $key, $label, $help = '' ) {
	$const = array( 'brevo_key' => 'OT_LEADS_BREVO_KEY', 'turnstile_secret' => 'OT_LEADS_TURNSTILE_SECRET', 'webhook_secret' => 'OT_LEADS_WEBHOOK_SECRET', 'fub_key' => 'OT_LEADS_FUB_KEY', 'ai_key' => 'OT_LEADS_ANTHROPIC_KEY' )[ $key ] ?? '';
	$set   = (bool) ot_leads_opt( $key );
	$out   = '<tr><th><label for="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td>';
	if ( ot_leads_opt_locked( $key ) ) {
		$out .= '<span class="ot-pill ot-pill--ok">Set in wp-config.php</span>';
	} else {
		$out .= '<input type="password" autocomplete="off" class="regular-text" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" placeholder="' . ( $set ? 'Saved. Type to replace.' : '' ) . '">' . ( $set ? ' <label><input type="checkbox" name="clear_' . esc_attr( $key ) . '" value="1"> Remove</label>' : '' );
		if ( $const ) {
			$out .= '<p class="description">Or, more secure: <code>define( \'' . esc_html( $const ) . '\', \'…\' );</code> in wp-config.php.</p>';
		}
	}
	return $out . ( $help ? '<p class="description">' . $help . '</p>' : '' ) . '</td></tr>';
}

function ot_leads_settings_screen() {
	global $wpdb;
	$o   = fn( $k ) => ot_leads_opt( $k );
	$row = fn( $label, $html, $help = '' ) => '<tr><th>' . $label . '</th><td>' . $html . ( $help ? '<p class="description">' . $help . '</p>' : '' ) . '</td></tr>';
	$tick = (int) get_option( 'ot_leads_last_tick' );

	ot_leads_shell( 'settings' );
	echo '<div class="wrap ot-wrap" style="max-width:980px">';
	ot_leads_head( 'Settings', 'Routing, alerts, email, spam protection, an outside CRM, AI and privacy for every form on the site.' );
	ot_leads_notice();
	echo '<div class="ot-card"><h2>Status</h2><table class="form-table">'
		. $row( 'Email', ot_leads_opt( 'brevo_key' ) ? '<span class="ot-pill ot-pill--ok">Brevo</span>' : '<span class="ot-pill ot-pill--bad">WordPress mail</span> Add a Brevo API key below for reliable delivery.' )
		. $row( 'Spam protection', ot_leads_opt( 'turnstile_site' ) && ot_leads_opt( 'turnstile_secret' ) ? '<span class="ot-pill ot-pill--ok">Turnstile, honeypot, rate limit</span>' : '<span class="ot-pill">Honeypot and rate limit</span> Add Turnstile keys for stronger protection.' )
		. $row( 'Scheduler', $tick ? 'Last ran ' . esc_html( human_time_diff( $tick ) ) . ' ago' . ( $tick < time() - 600 ? ' <span class="ot-pill ot-pill--bad">Not running often</span> Set up the server cron (see below).' : '' ) : '<span class="ot-pill ot-pill--bad">Not yet run</span>' )
		. $row( 'Postal address in emails', esc_html( OT_LEADS_ADDRESS ) )
		. $row( 'Health', ot_leads_health_html() )
		. '</table>' . ot_leads_form( 'test_email' ) . '<button class="button">Send me a test email</button></form></div>'; // phpcs:ignore WordPress.Security.EscapeOutput

	echo ot_leads_form( 'settings' ); // phpcs:ignore WordPress.Security.EscapeOutput

	// Routing.
	echo '<div class="ot-card"><h2>Routing and alerts</h2><table class="form-table">'
		. $row( '<label for="leaders">Team leaders</label>', '<input class="large-text" id="leaders" name="leaders" value="' . esc_attr( $o( 'leaders' ) ) . '">', 'Emails, comma-separated. They get team-inbox leads and escalations.' )
		. implode( '', array_map( fn( $slug, $ag ) => $row( '<label for="cc-' . esc_attr( $slug ) . '">Also alert, for ' . esc_html( strtok( $ag['name'], ' ' ) ) . '</label>', '<input class="large-text" id="cc-' . esc_attr( $slug ) . '" name="alert_cc[' . esc_attr( $slug ) . ']" value="' . esc_attr( ( (array) ( $o( 'alert_cc' ) ?: array() ) )[ $slug ] ?? '' ) . '" placeholder="name@oberdorferteam.com">', esc_html( ( $ag['email'] ?: 'The address on their profile' ) . ' always gets ' . strtok( $ag['name'], ' ' ) . '\'s alerts. Add more addresses here, comma-separated; they are not shown on the site.' ) ), array_keys( ot_leads_agents() ), ot_leads_agents() ) )
		. $row( '<label for="rm">Response window</label>', '<input type="number" id="rm" name="response_minutes" min="5" max="240" value="' . (int) $o( 'response_minutes' ) . '"> minutes', 'If a new lead is not marked contacted in this time during business hours, the team leaders are alerted.' )
		. $row( 'Business hours', '<input type="number" name="hours_start" min="0" max="23" value="' . (int) $o( 'hours_start' ) . '" style="width:60px"> to <input type="number" name="hours_end" min="1" max="24" value="' . (int) $o( 'hours_end' ) . '" style="width:60px"> (24-hour, Massachusetts time), Monday to Saturday', 'Escalations and marketing email happen only within these hours.' )
		. $row( 'Confirmation email', '<label><input type="checkbox" name="confirm_email" value="1"' . checked( (bool) $o( 'confirm_email' ), true, false ) . '> Send each person a short "we have your message" email from their agent</label>' );
	$owners = (array) $o( 'town_owners' );
	$towns  = get_posts( array( 'post_type' => 'ot_community', 'post_status' => 'publish', 'post_parent' => 0, 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
	$cells  = '';
	foreach ( $towns as $t ) {
		$cells .= '<label style="display:flex;justify-content:space-between;gap:8px;margin:0 0 6px"><span>' . esc_html( $t->post_title ) . '</span>' . ot_leads_agent_select( 'town_owners[' . $t->post_name . ']', $owners[ $t->post_name ] ?? '', 'Team' ) . '</label>';
	}
	echo $row( 'Town owners', '<div style="columns:2 22rem;max-width:56rem">' . $cells . '</div>', 'A town-guide lead goes to the town\'s owner; neighborhoods follow their city. "Team" goes to Brandon and Kaitlyn.' ); // phpcs:ignore WordPress.Security.EscapeOutput
	$types = (array) $o( 'type_owners' );
	$tc    = '';
	foreach ( array( 'buyer', 'seller', 'recruiting', 'office', 'newsletter', 'general' ) as $ty ) {
		$tc .= '<label style="display:flex;justify-content:space-between;gap:8px;max-width:28rem;margin:0 0 6px"><span>' . esc_html( OT_LEAD_TYPES[ $ty ] ) . '</span>' . ot_leads_agent_select( 'type_owners[' . $ty . ']', $types[ $ty ] ?? '', 'Team' ) . '</label>';
	}
	echo $row( 'Lead-type owners', $tc, 'Used when the form names no agent, listing or owned town.' ) . '</table></div>'; // phpcs:ignore WordPress.Security.EscapeOutput

	// Spam.
	echo '<div class="ot-card"><h2>Spam protection (Cloudflare Turnstile)</h2><table class="form-table">'
		. $row( '<label for="ts">Site key</label>', '<input class="regular-text" id="ts" name="turnstile_site" value="' . esc_attr( $o( 'turnstile_site' ) ) . '">', 'Cloudflare dashboard > Turnstile > Add widget, for oberdorferteam.com (and the staging host). Managed mode; most people never see a challenge.' )
		. ot_leads_secret_field( 'turnstile_secret', 'Secret key' )
		. '</table></div>';

	// Email.
	$hook = rest_url( 'ot-leads/v1/brevo' );
	echo '<div class="ot-card"><h2>Email (Brevo)</h2><table class="form-table">'
		. ot_leads_secret_field( 'brevo_key', 'Brevo API key', 'Brevo > SMTP &amp; API > API keys. Without it the site uses WordPress mail.' )
		. $row( 'Sender', '<input name="from_team" value="' . esc_attr( $o( 'from_team' ) ) . '" style="width:8rem"> @ <input name="from_domain" value="' . esc_attr( $o( 'from_domain' ) ) . '" style="width:14rem">', 'Every email is sent from this address with the agent\'s name; replies go to the agent\'s own inbox. Authenticate the domain in Brevo (Senders, domains &amp; dedicated IPs) and add its DNS records.' )
		. $row( 'Frequency', 'At most one nurture email per person every <input type="number" name="freq_days" min="1" max="30" value="' . (int) $o( 'freq_days' ) . '" style="width:60px"> days' )
		. $row( 'New-listing alerts', '<label><input type="checkbox" name="alerts_auto" value="1"' . checked( (bool) get_option( 'ot_leads_alerts_auto' ), true, false ) . '> Send automatically when a listing is published (otherwise they wait in the approval queue)</label>' )
		. $row( 'Brevo webhook', '<code>' . esc_html( $hook ) . '</code><br>Authentication: Bearer token <code>' . esc_html( $o( 'brevo_token' ) ?: '(save settings to create one)' ) . '</code>', 'Brevo > Transactional > Settings > Webhook: events hard bounce, invalid email, blocked, spam, unsubscribed and click. Bounces and complaints are then never emailed again.' )
		. $row( '<label for="ct">Consent wording</label>', '<textarea class="large-text" id="ct" name="consent_text" rows="2">' . esc_textarea( $o( 'consent_text' ) ) . '</textarea>', 'Shown beside the email-updates box on every form and stored with each consent. Changes need REWAP\'s approval.' )
		. '</table></div>';

	// CRM.
	echo '<div class="ot-card"><h2>Outside CRM</h2><table class="form-table">'
		. $row( 'Mode', '<select name="crm_mode"><option value="builtin"' . selected( $o( 'crm_mode' ), 'builtin', false ) . '>Built-in only</option><option value="both"' . selected( $o( 'crm_mode' ), 'both', false ) . '>Built-in and forward</option><option value="forward"' . selected( $o( 'crm_mode' ), 'forward', false ) . '>Forward only (nurture off)</option></select>' )
		. $row( '<label for="wh">Webhook URL</label>', '<input class="large-text" id="wh" name="webhook_url" value="' . esc_attr( $o( 'webhook_url' ) ) . '" placeholder="https://hooks.zapier.com/...">', 'Each lead is POSTed as JSON, signed with X-OT-Signature (HMAC-SHA256 of timestamp.id.body).' )
		. ot_leads_secret_field( 'webhook_secret', 'Webhook secret' )
		. ot_leads_secret_field( 'fub_key', 'Follow Up Boss API key' )
		. $row( 'Follow Up Boss system', '<input name="fub_system" value="' . esc_attr( $o( 'fub_system' ) ) . '" placeholder="X-System name">', 'From registering the integration with Follow Up Boss.' )
		. ot_leads_secret_field( 'fub_system_key', 'Follow Up Boss system key' )
		. '</table>';
	$out = $wpdb->get_results( 'SELECT o.*, l.email FROM ' . ot_leads_table( 'outbox' ) . ' o LEFT JOIN ' . ot_leads_table( 'leads' ) . ' l ON l.id = o.lead_id ORDER BY o.id DESC LIMIT 10', ARRAY_A );
	if ( $out ) {
		echo '<p><strong>Recent deliveries</strong></p><table class="widefat striped"><tbody>';
		foreach ( $out as $r ) {
			echo '<tr><td>' . esc_html( ot_leads_time( $r['created_at'] ) ) . '</td><td>' . esc_html( $r['connector'] ) . '</td><td>' . esc_html( $r['email'] ) . '</td><td><span class="ot-pill' . ( 'sent' === $r['state'] ? ' ot-pill--ok' : ( 'failed' === $r['state'] ? ' ot-pill--bad' : '' ) ) . '">' . esc_html( $r['state'] ) . '</span> ' . esc_html( $r['last_error'] ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}
	echo '</div>';

	// AI.
	list( $calls, $in_t, $out_t ) = ot_ai_month_usage();
	echo '<div class="ot-card"><h2>AI (Claude)</h2><table class="form-table">'
		. $row( 'AI drafting', '<label><input type="checkbox" name="ai_enabled" value="1"' . checked( (bool) $o( 'ai_enabled' ), true, false ) . '> On: AI drafts for "AI draft" steps, lead summaries and next steps</label>', 'Uses only each lead\'s record and the site\'s sourced guides; phone numbers and emails are never sent to the AI. Every draft is checked and, unless a sequence is set to send automatically, approved by a person.' )
		. ot_leads_secret_field( 'ai_key', 'Anthropic API key' )
		. $row( '<label for="am">Model</label>', '<input id="am" name="ai_model" value="' . esc_attr( $o( 'ai_model' ) ) . '">' )
		. $row( 'This month', (int) $calls . ' calls · ' . number_format( $in_t ) . ' input and ' . number_format( $out_t ) . ' output tokens' )
		. '</table></div>';

	// Privacy.
	echo '<div class="ot-card"><h2>Privacy</h2><table class="form-table">'
		. $row( 'Retention', 'Delete leads with no activity for <input type="number" name="retention_months" min="6" max="120" value="' . (int) $o( 'retention_months' ) . '" style="width:60px"> months', 'Leads that are active, under agreement or closed are kept. Consent and unsubscribe records are kept as proof.' )
		. $row( 'Requests', 'Tools > Export Personal Data and Erase Personal Data include leads. Each lead also has Export and Delete on its screen.' )
		. '</table></div>';

	echo '<p><button class="button button-primary">Save settings</button></p></form>';
	echo ot_leads_agent_crm_card(); // phpcs:ignore WordPress.Security.EscapeOutput
	echo '<div class="ot-card"><h2>Server cron (Cloudways)</h2><p>So alerts escalate and emails send on time, add <code>define( \'DISABLE_WP_CRON\', true );</code> to wp-config.php and, in Cloudways > Application Settings > Cron Job Management, a job every minute:</p><p><code>* * * * * wget -q -O - "' . esc_html( site_url( 'wp-cron.php?doing_wp_cron' ) ) . '" &gt;/dev/null 2&gt;&amp;1</code></p></div></div>';
	ot_leads_shell_end();
}
