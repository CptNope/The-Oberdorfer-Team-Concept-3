<?php
/**
 * Online bookings (JAT Calendar Sync) in the CRM.
 *
 * A confirmed booking becomes part of a lead: the person's existing lead (matched by email)
 * or a new one, assigned to the agent they booked. The lead moves to "Appointment set", the
 * timeline shows the booking, and the call is a task on the agent's Today screen at the
 * booked time. Moving or cancelling the booking (by the visitor, or in Google Calendar)
 * moves or removes the task and is logged too.
 *
 * The booking plugin already emails the visitor and the agent, so nothing here sends email.
 * A booking gives no marketing consent, so it never starts a nurture sequence; it is
 * forwarded to an agent's own CRM like any other inquiry.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'jatcs_booking_confirmed', 'ot_leads_booking_confirmed' );
add_action( 'jatcs_booking_rescheduled', 'ot_leads_booking_moved', 10, 2 );
add_action( 'jatcs_booking_cancelled', 'ot_leads_booking_cancelled', 10, 2 );

/** The agent (ot-core profile slug) a booking was made with, or '' (the site owner's calendar). */
function ot_leads_booking_agent( $booking ) {
	if ( ! class_exists( 'JATCS_Hosts' ) ) {
		return '';
	}
	$host = JATCS_Hosts::of_booking( $booking );
	if ( '' === $host ) {
		return '';
	}
	foreach ( array_keys( ot_leads_agents() ) as $slug ) {
		$p    = get_page_by_path( $slug, OBJECT, 'ot_agent' );
		$want = $p ? sanitize_key( (string) get_post_meta( $p->ID, 'ot_booking_host', true ) ) : '';
		if ( ( '' !== $want ? $want : $slug ) === $host ) {
			return $slug;
		}
	}
	return '';
}

/** UTC datetime string for a Unix time (the tables' format). */
function ot_leads_booking_utc( $ts ) {
	return gmdate( 'Y-m-d H:i:s', (int) $ts );
}

/** The task's note: what was booked. */
function ot_leads_booking_note( $b ) {
	return substr( 'Booked: ' . ( $b['type_name'] ?: 'call' ), 0, 255 );
}

/** A confirmed booking: find or create the lead, assign it, log it and add the call task. */
function ot_leads_booking_confirmed( $b ) {
	global $wpdb;
	$email = strtolower( sanitize_email( (string) ( $b['email'] ?? '' ) ) );
	if ( ! is_email( $email ) || empty( $b['id'] ) || get_post_meta( (int) $b['id'], '_ot_lead_id', true ) ) {
		return; // No address to match on, or already recorded (confirmations can repeat).
	}
	$agent = ot_leads_booking_agent( $b );
	$t     = ot_leads_table( 'leads' );
	$now   = ot_leads_now();
	list( $first, $last ) = ot_leads_split_name( (string) $b['name'] );
	$phone   = preg_replace( '/[^0-9+()\-\s.]/', '', (string) ( $b['phone'] ?? '' ) );
	$page    = $agent ? (string) ( ot_leads_agents()[ $agent ]['url'] ?? '' ) : home_url( '/' );
	$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $t WHERE email = %s ORDER BY id ASC LIMIT 1", $email ), ARRAY_A );

	if ( $existing ) {
		$id  = (int) $existing['id'];
		$upd = array();
		foreach ( array( 'first_name' => $first, 'last_name' => $last, 'phone' => $phone ) as $k => $v ) {
			if ( $v && ! $existing[ $k ] ) {
				$upd[ $k ] = $v;
			}
		}
		// A team lead (no single owner) goes to the agent they chose; an agent's lead stays theirs.
		if ( $agent && '' === (string) $existing['assigned_agent'] ) {
			$upd['assigned_agent'] = $agent;
		}
		if ( in_array( $existing['stage'], array( 'new', 'contacted', 'nurture', 'lost' ), true ) ) {
			$upd['stage']     = 'appointment';
			$upd['escalated'] = 0;
		}
		if ( $upd ) {
			ot_lead_update( $id, $upd, 'booking' );
		}
	} else {
		$wpdb->insert( $t, array(
			'created_at'       => $now,
			'updated_at'       => $now,
			'last_activity_at' => $now,
			'first_name'       => $first,
			'last_name'        => $last,
			'email'            => $email,
			'phone'            => $phone,
			'preferred'        => '',
			'lead_type'        => 'booking',
			'context'          => substr( 'booking:' . ( $b['type'] ?? '' ), 0, 190 ),
			'stage'            => 'appointment',
			'towns'            => '',
			'listing_id'       => 0,
			'topic'            => substr( (string) ( $b['type_name'] ?? '' ), 0, 190 ),
			'source_url'       => substr( $page, 0, 255 ),
			'first_url'        => '',
			'referrer'         => '',
			'utm'              => null,
			'assigned_agent'   => $agent,
		) );
		$id = (int) $wpdb->insert_id;
		if ( ! $id ) {
			return;
		}
		if ( $agent ) {
			ot_lead_event( $id, 'assigned', array( 'to' => $agent ), 'booking' );
		}
	}

	$lead = ot_lead( $id );
	ot_lead_event( $id, 'booking', array(
		'status'  => 'booked',
		'when'    => (int) $b['start'],
		'type'    => (string) ( $b['type_name'] ?? '' ),
		'with'    => $agent,
		'meet'    => (string) ( $b['meet_link'] ?? '' ),
		'notes'   => (string) ( $b['notes'] ?? '' ),
		'answers' => array_map( fn( $a ) => ( $a['label'] ?? '' ) . ': ' . ( $a['value'] ?? '' ), (array) ( $b['answers'] ?? array() ) ),
		'booking' => (int) $b['id'],
		'owner'   => (string) $lead['assigned_agent'],
	), 'visitor' );

	// The call, on the booked agent's Today screen at the booked time.
	$wpdb->insert( ot_leads_table( 'tasks' ), array(
		'lead_id'    => $id,
		'assignee'   => $agent ?: (string) $lead['assigned_agent'],
		'kind'       => 'call',
		'note'       => ot_leads_booking_note( $b ),
		'due_at'     => ot_leads_booking_utc( $b['start'] ),
		'created_at' => $now,
	) );
	$task = (int) $wpdb->insert_id; // Read now: the meta writes below change insert_id.
	update_post_meta( (int) $b['id'], '_ot_lead_id', $id );
	update_post_meta( (int) $b['id'], '_ot_task_id', $task );

	// Same as any inquiry: an agent's own CRM hears about it.
	$message = 'Booked ' . ( $b['type_name'] ?: 'a call' ) . ' for ' . wp_date( 'l, F j, g:i a', (int) $b['start'] ) . '.' . ( ! empty( $b['notes'] ) ? "\n" . $b['notes'] : '' );
	$form    = array(
		'name'      => (string) $b['name'],
		'email'     => $email,
		'phone'     => $phone,
		'preferred' => '',
		'topic'     => (string) ( $b['type_name'] ?? '' ),
		'message'   => $message,
		'page'      => $page,
		'first'     => '',
		'referrer'  => '',
		'utm'       => array(),
		'type'      => 'booking',
		'context'   => 'booking:' . ( $b['type'] ?? '' ),
		'consent'   => false,
		'consent_text' => '',
	);
	ot_leads_outbox_add( ot_lead( $id ), $form );
	do_action( 'ot_lead_booked', $id, $b );
}

/** A booking moved (by the visitor, or in Google Calendar): move its task, log it. */
function ot_leads_booking_moved( $b, $old_start = 0 ) {
	global $wpdb;
	$id = (int) get_post_meta( (int) $b['id'], '_ot_lead_id', true );
	if ( ! $id || ! ot_lead( $id ) ) {
		return;
	}
	$task = (int) get_post_meta( (int) $b['id'], '_ot_task_id', true );
	if ( $task ) {
		$wpdb->update( ot_leads_table( 'tasks' ), array( 'due_at' => ot_leads_booking_utc( $b['start'] ), 'done_at' => null ), array( 'id' => $task, 'lead_id' => $id ) );
	}
	ot_lead_event( $id, 'booking', array(
		'status'  => 'moved',
		'when'    => (int) $b['start'],
		'from'    => (int) $old_start,
		'type'    => (string) ( $b['type_name'] ?? '' ),
		'with'    => ot_leads_booking_agent( $b ),
		'booking' => (int) $b['id'],
	), 'visitor' );
}

/** A booking cancelled: remove its task, log who cancelled. */
function ot_leads_booking_cancelled( $b, $by = '' ) {
	global $wpdb;
	$id = (int) get_post_meta( (int) $b['id'], '_ot_lead_id', true );
	if ( ! $id || ! ot_lead( $id ) ) {
		return;
	}
	$task = (int) get_post_meta( (int) $b['id'], '_ot_task_id', true );
	if ( $task ) {
		// Only an open task: a call already logged as done stays in the history.
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . ot_leads_table( 'tasks' ) . ' WHERE id = %d AND lead_id = %d AND done_at IS NULL', $task, $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	}
	ot_lead_event( $id, 'booking', array(
		'status'  => 'cancelled',
		'when'    => (int) $b['start'],
		'type'    => (string) ( $b['type_name'] ?? '' ),
		'with'    => ot_leads_booking_agent( $b ),
		'by'      => in_array( $by, array( 'visitor', 'admin', 'calendar' ), true ) ? $by : 'visitor',
		'booking' => (int) $b['id'],
	), 'visitor' === $by ? 'visitor' : 'site' );
}

/** Timeline text for a booking event. */
function ot_leads_booking_event_text( $p ) {
	$when = ! empty( $p['when'] ) ? ot_leads_time( ot_leads_booking_utc( $p['when'] ), 'D M j, g:i a' ) : '';
	$with = ! empty( $p['with'] ) ? ot_leads_agent_name( $p['with'] ) : '';
	$by   = array( 'visitor' => 'by them', 'admin' => 'in WordPress', 'calendar' => 'in Google Calendar' );
	switch ( $p['status'] ?? '' ) {
		case 'moved':
			$head = 'Moved to ' . $when . ( ! empty( $p['from'] ) ? ' (was ' . ot_leads_time( ot_leads_booking_utc( $p['from'] ), 'D M j, g:i a' ) . ')' : '' );
			break;
		case 'cancelled':
			$head = 'Cancelled ' . ( $by[ $p['by'] ?? '' ] ?? '' ) . ' · was ' . $when;
			break;
		default:
			$head = $when;
	}
	$out = esc_html( trim( $head . ( ! empty( $p['type'] ) ? ' · ' . $p['type'] : '' ) . ( $with ? ' · with ' . $with : '' ) ) );
	if ( ! empty( $p['owner'] ) && ! empty( $p['with'] ) && $p['owner'] !== $p['with'] ) {
		$out .= '<br><span class="ot-muted">' . esc_html( 'The lead stays with ' . ot_leads_agent_name( $p['owner'] ) . '.' ) . '</span>';
	}
	if ( ! empty( $p['meet'] ) ) {
		$out .= '<br><a href="' . esc_url( $p['meet'] ) . '" target="_blank" rel="noopener">Video link</a>';
	}
	foreach ( (array) ( $p['answers'] ?? array() ) as $line ) {
		$out .= '<br>' . esc_html( $line );
	}
	if ( ! empty( $p['notes'] ) ) {
		$out .= '<br><em>' . nl2br( esc_html( $p['notes'] ) ) . '</em>';
	}
	return $out;
}
