<?php
/**
 * The lead tables (spec § 3). Created and updated with dbDelta; never dropped by the plugin.
 */

defined( 'ABSPATH' ) || exit;

/** A table's full name: ot_leads_table( 'leads' ) is wp_ot_leads. */
function ot_leads_table( $name ) {
	global $wpdb;
	return $wpdb->prefix . 'ot_' . $name;
}

function ot_leads_install() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$c = $wpdb->get_charset_collate();
	$t = 'ot_leads_table';
	$sql = array(
		"CREATE TABLE {$t('leads')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  last_activity_at datetime NOT NULL,
  first_name varchar(100) NOT NULL DEFAULT '',
  last_name varchar(100) NOT NULL DEFAULT '',
  email varchar(190) NOT NULL DEFAULT '',
  phone varchar(40) NOT NULL DEFAULT '',
  preferred varchar(10) NOT NULL DEFAULT '',
  lead_type varchar(40) NOT NULL DEFAULT 'general',
  context varchar(190) NOT NULL DEFAULT '',
  stage varchar(20) NOT NULL DEFAULT 'new',
  lost_reason varchar(190) NOT NULL DEFAULT '',
  assigned_agent varchar(100) NOT NULL DEFAULT '',
  towns varchar(255) NOT NULL DEFAULT '',
  listing_id bigint(20) unsigned NOT NULL DEFAULT 0,
  topic varchar(190) NOT NULL DEFAULT '',
  tags varchar(255) NOT NULL DEFAULT '',
  source_url varchar(255) NOT NULL DEFAULT '',
  first_url varchar(255) NOT NULL DEFAULT '',
  referrer varchar(255) NOT NULL DEFAULT '',
  utm text NULL,
  contacted_at datetime NULL,
  escalated tinyint(1) NOT NULL DEFAULT 0,
  crm_state varchar(20) NOT NULL DEFAULT '',
  PRIMARY KEY  (id),
  KEY email (email),
  KEY phone (phone),
  KEY stage (stage),
  KEY assigned_agent (assigned_agent),
  KEY created_at (created_at)
) $c;",
		"CREATE TABLE {$t('lead_events')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  lead_id bigint(20) unsigned NOT NULL,
  type varchar(30) NOT NULL,
  payload longtext NULL,
  actor varchar(100) NOT NULL DEFAULT '',
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY lead_id (lead_id),
  KEY type (type)
) $c;",
		"CREATE TABLE {$t('consents')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  lead_id bigint(20) unsigned NOT NULL DEFAULT 0,
  email varchar(190) NOT NULL DEFAULT '',
  channel varchar(30) NOT NULL,
  granted tinyint(1) NOT NULL,
  wording text NOT NULL,
  wording_version varchar(20) NOT NULL DEFAULT '',
  page_url varchar(255) NOT NULL DEFAULT '',
  ip_hash varchar(64) NOT NULL DEFAULT '',
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY lead_id (lead_id),
  KEY email (email)
) $c;",
		"CREATE TABLE {$t('tasks')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  lead_id bigint(20) unsigned NOT NULL,
  assignee varchar(100) NOT NULL DEFAULT '',
  kind varchar(20) NOT NULL DEFAULT 'call',
  note varchar(255) NOT NULL DEFAULT '',
  due_at datetime NOT NULL,
  done_at datetime NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY lead_id (lead_id),
  KEY due_at (due_at)
) $c;",
		"CREATE TABLE {$t('enrollments')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  lead_id bigint(20) unsigned NOT NULL,
  sequence varchar(40) NOT NULL,
  step int(11) NOT NULL DEFAULT 0,
  state varchar(20) NOT NULL DEFAULT 'active',
  next_at datetime NULL,
  exit_reason varchar(100) NOT NULL DEFAULT '',
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY lead_id (lead_id),
  KEY state_next (state, next_at)
) $c;",
		"CREATE TABLE {$t('messages')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  lead_id bigint(20) unsigned NOT NULL DEFAULT 0,
  enrollment_id bigint(20) unsigned NOT NULL DEFAULT 0,
  kind varchar(20) NOT NULL DEFAULT 'nurture',
  step int(11) NOT NULL DEFAULT 0,
  to_email varchar(190) NOT NULL DEFAULT '',
  subject varchar(255) NOT NULL DEFAULT '',
  body longtext NULL,
  source varchar(20) NOT NULL DEFAULT 'template',
  status varchar(20) NOT NULL DEFAULT 'draft',
  checks longtext NULL,
  approved_by varchar(100) NOT NULL DEFAULT '',
  provider_id varchar(190) NOT NULL DEFAULT '',
  error varchar(255) NOT NULL DEFAULT '',
  created_at datetime NOT NULL,
  sent_at datetime NULL,
  PRIMARY KEY  (id),
  KEY lead_id (lead_id),
  KEY status (status)
) $c;",
		"CREATE TABLE {$t('outbox')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  lead_id bigint(20) unsigned NOT NULL,
  connector varchar(120) NOT NULL,
  payload longtext NOT NULL,
  attempts int(11) NOT NULL DEFAULT 0,
  next_attempt_at datetime NOT NULL,
  state varchar(20) NOT NULL DEFAULT 'pending',
  last_error varchar(255) NOT NULL DEFAULT '',
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY state_next (state, next_attempt_at)
) $c;",
		"CREATE TABLE {$t('suppressions')} (
  email varchar(190) NOT NULL,
  reason varchar(20) NOT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (email)
) $c;",
		"CREATE TABLE {$t('ai_log')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  purpose varchar(30) NOT NULL,
  model varchar(60) NOT NULL DEFAULT '',
  lead_id bigint(20) unsigned NOT NULL DEFAULT 0,
  prompt_hash varchar(64) NOT NULL DEFAULT '',
  output longtext NULL,
  checks longtext NULL,
  input_tokens int(11) NOT NULL DEFAULT 0,
  output_tokens int(11) NOT NULL DEFAULT 0,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY created_at (created_at)
) $c;",
	);
	foreach ( $sql as $s ) {
		dbDelta( $s );
	}
	update_option( 'ot_leads_db', OT_LEADS_DB, false );
}
