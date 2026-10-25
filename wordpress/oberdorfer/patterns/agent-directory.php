<?php
/**
 * Title: Agent directory
 * Slug: oberdorfer/agent-directory
 * Categories: oberdorfer
 * Inserter: yes
 * Description: Directory filters (specialty, service area, language), the profile count, and the agent cards with the open seat (ot/agent-cards).
 *
 * The filter bar and the live count are HTML fragments that site-demo.js drives
 * ([data-directory], [data-team-count]); the grid carries data-team-grid.
 */
$sel = function ( $id, $label, $opts ) {
	$o = '';
	foreach ( $opts as $x ) {
		$o .= '<option>' . esc_html( $x ) . '</option>';
	}
	return '<div class="field"><label class="field__label" for="' . $id . '">' . esc_html( $label ) . '</label><select class="select" id="' . $id . '" name="' . substr( $id, 2 ) . '">' . $o . '</select></div>';
};
$n     = function_exists( 'ot_agents' ) ? count( ot_agents() ) : 0;
$count = $n ? $n . ( 1 === $n ? ' team profile' : ' team profiles' ) : 'Team profiles';
$bar   = oberdorfer_html( '<form class="directory-bar" data-directory aria-label="Filter the team">
    ' . $sel( 'd-spec', 'Specialty', array( 'All specialties', 'Buyers', 'Sellers', 'First-time buyers', 'Move-up' ) ) . '
    ' . $sel( 'd-area', 'Service area', array( 'All of Massachusetts', 'Wakefield', 'Worcester', 'Boston' ) ) . '
    ' . $sel( 'd-lang', 'Language', array( 'Any language', 'English' ) ) . '
    <button class="btn btn--secondary" type="reset">Reset</button>
  </form>
  <p class="results-bar__count" data-team-count aria-live="polite" style="margin-bottom:var(--space-6)">' . esc_html( $count ) . '</p>' );
echo oberdorfer_section( // phpcs:ignore WordPress.Security.EscapeOutput
	oberdorfer_group(
		$bar
		. oberdorfer_h( 'Team profiles', 2, 'visually-hidden' )
		. oberdorfer_group( '<!-- wp:ot/agent-cards /-->', 'card-grid-3 ot-data-team-grid' ),
		'container'
	)
);
