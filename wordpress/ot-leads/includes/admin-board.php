<?php
/**
 * Leads > Board: the pipeline by stage. Drag a card to another column (or use its menu) to move
 * the lead; Lost leads are listed at the foot.
 */

defined( 'ABSPATH' ) || exit;

function ot_leads_board_screen() {
	global $wpdb;
	$t     = ot_leads_table( 'leads' );
	$own   = current_user_can( 'ot_leads_view_all' ) ? '' : $wpdb->prepare( ' AND assigned_agent = %s', ot_leads_user_agent() );
	$agent = current_user_can( 'ot_leads_view_all' ) && ! empty( $_GET['agent'] ) ? ot_leads_agent_where( sanitize_title( wp_unslash( $_GET['agent'] ) ) ) : '';
	$rows  = $wpdb->get_results( "SELECT id, first_name, last_name, email, lead_type, stage, assigned_agent, towns, listing_id, created_at, last_activity_at FROM $t WHERE stage <> 'lost'$own$agent ORDER BY last_activity_at DESC LIMIT 500", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
	$by    = array();
	foreach ( $rows as $r ) {
		$by[ $r['stage'] ][] = $r;
	}
	ot_leads_shell( 'board' );
	echo '<div class="wrap ot-wrap" style="max-width:none">';
	ot_leads_head( 'Pipeline', 'Drag a person to another stage, or use the menu on their card.', current_user_can( 'ot_leads_view_all' ) ? ot_leads_agent_chips( 'ot-leads-board', sanitize_title( wp_unslash( $_GET['agent'] ?? '' ) ) ) : '' );
	ot_leads_notice();
	echo ot_leads_form( 'move', 0, 'id="ot-move"' ) . '<input type="hidden" name="id" value=""><input type="hidden" name="stage" value=""></form>'; // phpcs:ignore WordPress.Security.EscapeOutput
	echo '<div class="ot-board">';
	foreach ( OT_LEAD_STAGES as $stage => $label ) {
		if ( 'lost' === $stage ) {
			continue;
		}
		$cards = $by[ $stage ] ?? array();
		echo '<section class="ot-col" data-stage="' . esc_attr( $stage ) . '" aria-label="' . esc_attr( $label ) . '"><h2><span>' . esc_html( $label ) . '</span><span class="ot-muted">' . count( $cards ) . '</span></h2>';
		foreach ( $cards as $c ) {
			echo '<article class="ot-bcard" draggable="true" data-id="' . (int) $c['id'] . '"><a href="' . esc_url( ot_leads_admin_link( $c['id'] ) ) . '">' . esc_html( ot_lead_name( $c ) ) . '</a><small>' . esc_html( implode( ' · ', array_filter( array( OT_LEAD_TYPES[ $c['lead_type'] ] ?? $c['lead_type'], $c['listing_id'] ? get_the_title( $c['listing_id'] ) : ot_leads_towns_label( $c['towns'] ) ) ) ) ) . '</small><small>' . esc_html( ot_leads_agent_name( $c['assigned_agent'] ) . ' · last activity ' . human_time_diff( strtotime( $c['last_activity_at'] . ' UTC' ) ) . ' ago' ) . '</small>'
				. '<label class="screen-reader-text" for="mv' . (int) $c['id'] . '">Move to</label><select id="mv' . (int) $c['id'] . '" data-move="' . (int) $c['id'] . '" style="margin-top:6px;max-width:100%"><option value="">Move to…</option>';
			foreach ( OT_LEAD_STAGES as $s => $l ) {
				if ( $s !== $stage ) {
					echo '<option value="' . esc_attr( $s ) . '">' . esc_html( $l ) . '</option>';
				}
			}
			echo '</select></article>';
		}
		echo '</section>';
	}
	echo '</div>';
	$lost = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t WHERE stage = 'lost'$own" ); // phpcs:ignore WordPress.DB.PreparedSQL
	echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=ot-leads&f_stage=lost' ) ) . '">Lost (' . $lost . ')</a></p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	ot_leads_shell_end();
	?>
<script>
( function () {
	var form = document.getElementById( 'ot-move' ), drag = null;
	function move( id, stage ) { form.elements.id.value = id; form.elements.stage.value = stage; form.submit(); }
	document.querySelectorAll( '[data-move]' ).forEach( function ( s ) { s.addEventListener( 'change', function () { if ( s.value ) { move( s.getAttribute( 'data-move' ), s.value ); } } ); } );
	document.querySelectorAll( '.ot-bcard' ).forEach( function ( c ) { c.addEventListener( 'dragstart', function () { drag = c; } ); } );
	document.querySelectorAll( '.ot-col' ).forEach( function ( col ) {
		col.addEventListener( 'dragover', function ( e ) { e.preventDefault(); col.classList.add( 'is-over' ); } );
		col.addEventListener( 'dragleave', function () { col.classList.remove( 'is-over' ); } );
		col.addEventListener( 'drop', function ( e ) { e.preventDefault(); col.classList.remove( 'is-over' ); if ( drag && drag.parentNode !== col ) { move( drag.getAttribute( 'data-id' ), col.getAttribute( 'data-stage' ) ); } } );
	} );
} )();
</script>
	<?php
}
