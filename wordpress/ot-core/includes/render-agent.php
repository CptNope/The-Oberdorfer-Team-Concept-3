<?php
/**
 * Agent profiles, ported from page_profile() and page_agent() in tools/build_site.py.
 * Only confirmed facts are stored; anything unconfirmed renders as a [bracketed placeholder]
 * with a "To confirm" badge. The team profile (kind "team") is the joint page for Brandon and Kaitlyn.
 */

defined( 'ABSPATH' ) || exit;

function ot_ax( $post_id ) {
	$p = get_post( $post_id );
	if ( ! $p || 'ot_agent' !== $p->post_type ) {
		return null;
	}
	$rec = json_decode( (string) get_post_meta( $p->ID, 'ot_record', true ), true ) ?: array();
	$rec['name'] = $rec['name'] ?? $p->post_title;
	$agents      = ot_agents();
	$partner     = ! empty( $rec['partner'] ) && isset( $agents[ $rec['partner'] ] ) ? $agents[ $rec['partner'] ] : null;
	return array(
		'p'       => $p,
		'r'       => $rec,
		'g'       => esc_html( $rec['given'] ?? $rec['name'] ),
		'name'    => esc_html( $rec['name'] ),
		'url'     => get_permalink( $p ),
		'team'    => ( $rec['kind'] ?? '' ) === 'team',
		'partner' => $partner,
		'agents'  => $agents,
	);
}

function ot_tel_href( $phone ) {
	return 'tel:+1' . preg_replace( '/\D/', '', $phone );
}

function ot_agent_card( $a ) {
	$r    = $a['rec'];
	$href = esc_url( $a['url'] );
	$name = esc_html( $r['name'] );
	return '<article class="agent-card" data-specialty="buyers sellers first-time move-up" data-area="massachusetts wakefield worcester boston" data-lang="english">
  <a class="agent-card__photo" href="' . $href . '"><img src="' . esc_url( ot_asset( $r['photo'] ) ) . '" alt="' . esc_attr( $r['name'] ) . '" width="' . (int) ( $r['photo_w'] ?? 440 ) . '" height="' . (int) ( $r['photo_h'] ?? 550 ) . '" loading="lazy"></a>
  <div class="stack" style="--stack:var(--space-2)"><p class="agent-card__title">' . esc_html( $r['role'] ) . ' · The Oberdorfer Team</p><h3 class="agent-card__name"><a href="' . $href . '" style="text-decoration:none;color:inherit">' . $name . '</a></h3><p class="body-s muted">Husband. Wife. Your Team.</p></div>
  <div class="agent-card__meta"><span class="chip">Buyers</span><span class="chip">Sellers</span><span class="chip">Massachusetts</span></div>
  <div class="cluster" style="--cluster:var(--space-5)"><a class="link-arrow" href="' . $href . '">Meet ' . esc_html( $r['given'] ) . ' ' . ot_arrow() . '</a><a class="link-arrow" href="tel:' . OT_TEL . '">' . OT_PHONE . '</a></div>
</article>';
}

function ot_render_agent_cards( $with_seat = true ) {
	$out = '';
	foreach ( ot_agents() as $a ) {
		$out .= ot_agent_card( $a );
	}
	if ( $with_seat ) {
		$out .= '<div class="seat"><div class="stack" style="--stack:var(--space-3);display:grid;justify-items:center">' . ot_icon( 'user' ) . '<p class="h4">Your seat at the table</p><p class="body-s">The team is growing. New agent profiles appear here as they join.</p>' . ot_link_arrow( ot_page_url( 'join' ), 'Join our team' ) . '</div></div>';
	}
	return $out;
}

/** Questions people ask about an agent; confirmed facts only (prototype agent_faq()). */
function ot_agent_faq( $x ) {
	$g = $x['g'];
	$n = $x['name'];
	$qs = array(
		array( 'How do I contact ' . $n . '?', 'Call or text The Oberdorfer Team at ' . OT_PHONE . ', or send a message from this page. Messages sent here go to ' . $g . ' and are attributed to ' . $g . ' in the team\'s lead routing.' ),
		array( 'Which areas does ' . $n . ' serve?', $g . ' helps buyers and sellers across Massachusetts. The team works from Worcester, and its community guides run from Central Massachusetts and MetroWest to Boston, the South Shore, Cape Cod, the South Coast and the Berkshires, including Wakefield, where the team publishes <em>Wakefield at Home</em>.' ),
		array( 'Which brokerage is ' . $n . ' with?', $g . ' is part of The Oberdorfer Team, a real estate team operating under REWAP Brokerage LLC in Massachusetts.' ),
	);
	if ( $x['partner'] ) {
		$pr = $x['partner']['rec'];
		array_splice( $qs, 1, 0, array( array(
			'Do ' . $g . ' and ' . esc_html( $pr['given'] ) . ' work together?',
			'Yes. ' . $g . ' leads The Oberdorfer Team with ' . esc_html( $x['r']['relation'] ) . ', ' . esc_html( $pr['name'] ) . '. The team describes itself as “Husband. Wife. Your Team.”: two partners invested in your goals.',
		) ) );
	}
	return $qs;
}

function ot_render_agent_section( $section, $post_id ) {
	$x = ot_ax( $post_id );
	if ( ! $x ) {
		return '';
	}
	return $x['team'] ? ot_render_team_section( $section, $x ) : ot_render_person_section( $section, $x );
}

function ot_render_person_section( $section, $x ) {
	$r      = $x['r'];
	$g      = $x['g'];
	$name   = $x['name'];
	$verify = '<span class="badge badge--verify">To confirm</span>';

	switch ( $section ) {
		case 'hero':
			$trail   = array( array( 'Home', home_url( '/' ) ), array( 'Our Team', ot_page_url( 'team' ) ), array( $name, $x['url'] ) );
			$partner = $x['partner'] ? $x['partner']['rec'] : null;
			$summary = $name . ' is a Massachusetts real estate agent who leads The Oberdorfer Team with ' . esc_html( $r['relation'] ?? '' ) . ( $partner ? ', ' . esc_html( $partner['name'] ) : '' ) . '. The husband-and-wife team works from Worcester, helps buyers and sellers across the state and operates under REWAP Brokerage LLC.';
			$direct  = $r['direct'] ?? '';
			if ( $direct && OT_PHONE === $direct ) {
				$phone = '<a href="tel:' . OT_TEL . '">' . OT_PHONE . '</a> (direct, and the team line)';
			} elseif ( $direct ) {
				$phone = '<a href="' . esc_attr( ot_tel_href( $direct ) ) . '">' . esc_html( $direct ) . '</a> (direct) · team line <a href="tel:' . OT_TEL . '">' . OT_PHONE . '</a>';
			} else {
				$phone = '<a href="tel:' . OT_TEL . '">' . OT_PHONE . '</a> (team line) · direct line ' . ot_ph( 'to confirm' );
			}
			$recog = array();
			foreach ( (array) ( $r['accolades'] ?? array() ) as $acc ) {
				$recog[] = esc_html( $acc['text'] ) . ' <span class="badge badge--source">' . esc_html( $acc['source'] ) . '</span>';
			}
			$facts = array(
				array( 'Role', esc_html( $r['role'] ) . ', The Oberdorfer Team' ),
				array( 'Title', ! empty( $r['title'] ) ? esc_html( $r['title'] ) : ot_ph( 'Professional title to confirm' ) ),
				array( 'Brokerage', 'REWAP Brokerage LLC' ),
				array( 'Recognition', $recog ? implode( '<br>', $recog ) : ot_ph( 'Verified awards only' ) ),
				array( 'Service area', 'Massachusetts' ),
				array( 'Specialties', ot_ph( 'Specialties to confirm' ) ),
				array( 'Languages', ot_ph( 'Languages to confirm' ) ),
				array( 'Phone', $phone ),
				array( 'Email', ! empty( $r['email'] ) ? '<a href="mailto:' . esc_attr( $r['email'] ) . '">' . esc_html( $r['email'] ) . '</a>' : ot_ph( 'Email to confirm' ) ),
				array( 'License', ot_ph( 'MA license number to be supplied and verified' ) ),
			);
			$facts_html = '';
			foreach ( $facts as $f ) {
				$facts_html .= '<dt>' . $f[0] . '</dt><dd>' . $f[1] . '</dd>';
			}
			$photo = '<figure class="profile__photo"><img src="' . esc_url( ot_asset( $r['photo'] ) ) . '" alt="' . esc_attr( $r['name'] . ', ' . strtolower( $r['role'] ) . ' of The Oberdorfer Team' ) . '" width="' . (int) ( $r['photo_w'] ?? 440 ) . '" height="' . (int) ( $r['photo_h'] ?? 550 ) . '"><figcaption class="caption">' . ot_txt( $r['photo_note'] ?? '' ) . ' <span class="badge badge--verify">Interim photo</span></figcaption></figure>';
			return '<section class="section profile-hero" style="padding-top:var(--space-7)"><div class="container stack" style="--stack:var(--space-6)">
  ' . ot_breadcrumbs_html( $trail ) . '
  <div class="profile">
    ' . $photo . '
    <div class="profile__body">
      <p class="label">' . esc_html( $r['role'] ) . ' · The Oberdorfer Team</p>
      <h1 class="display-l" id="page-title">' . $name . '</h1>
      <p class="lead profile__summary">' . $summary . '</p>
      <div class="cluster profile__actions"><a class="btn" href="#contact-agent">Message ' . $g . '</a><a class="btn btn--secondary" href="tel:' . OT_TEL . '">Call ' . OT_PHONE . '</a></div>
      <p class="caption profile__trust">Husband. Wife. Your Team. · REWAP Brokerage LLC · Serving Massachusetts</p>
      <dl class="quick-facts" aria-label="' . esc_attr( $r['given'] ) . ' at a glance">' . $facts_html . '</dl>
      <p class="caption">Profile updated ' . ot_updated_time() . '.</p>
    </div>
  </div>
</div></section>';

		case 'about':
			$pg  = $x['partner'] ? esc_html( $x['partner']['rec']['given'] ) : 'the team';
			$bio = '<div class="placeholder-block"><p class="label">' . $g . '\'s biography · in ' . $g . '\'s own words</p>
<p>[' . $g . '\'s biography to be supplied: 120 to 250 words in the first person. How ' . $g . ' came to real estate, the part of each move ' . $g . ' leads for clients, and a Massachusetts place ' . $g . ' knows well. Verified facts only.]</p>' . $verify . '</div>
<div class="prose"><p>What ' . $g . ' and ' . $pg . ' share is written in <em>Wakefield at Home</em>: “Our work reflects the values we bring to our own family: care, honesty, hard work, and showing up for the people who count on us.”</p>
<p>Whether you are buying your first home, making room for your next chapter, or preparing to sell, the goal is the same: that you feel heard, informed, and supported from the first conversation through closing.</p></div>';
			// Anything the team writes in the post body is shown after the placeholder (it replaces it once a real bio exists).
			$content = trim( (string) $x['p']->post_content );
			if ( '' !== $content ) {
				$bio = '<div class="prose">' . apply_filters( 'the_content', $content ) . '</div>';
			}
			$partner_html = '';
			if ( $x['partner'] ) {
				$pr  = $x['partner']['rec'];
				$rel = (string) ( $x['r']['relation'] ?? '' );
				$partner_html = '<div class="panel"><p class="label">Works alongside</p><a class="agent-mini" href="' . esc_url( $x['partner']['url'] ) . '" style="text-decoration:none;color:inherit"><img src="' . esc_url( ot_asset( $pr['photo'] ) ) . '" alt="" width="' . (int) ( $pr['photo_w'] ?? 440 ) . '" height="' . (int) ( $pr['photo_h'] ?? 550 ) . '"><span><span class="agent-mini__name">' . esc_html( $pr['name'] ) . '</span><br><span class="agent-mini__meta">' . esc_html( $pr['role'] ) . ' · ' . esc_html( ucfirst( $rel ) ) . '</span></span></a>' . ot_link_arrow( ot_agent_url( 'brandon-kaitlyn-oberdorfer' ), 'Their story together' ) . '</div>';
			}
			return '<section class="section surface-muted"><div class="container split" style="align-items:start">
  <div class="stack" style="grid-column:1/span 7;--stack:var(--space-5)"><p class="label">About ' . $g . '</p><h2 class="display-m">Meet ' . $g . '.</h2>' . $bio . '</div>
  <aside class="stack" style="grid-column:9/span 4;--stack:var(--space-5)">' . $partner_html . '</aside>
</div></section>';

		case 'help':
			return '<section class="section"><div class="container">' . ot_section_head( 'How ' . $g . ' can help', 'Services', array( 'A plan for', 'your next move.' ), null, null, 'h2', 'help-title' ) . '
  <div class="cols cols--3 col-rule">
    <div class="stack"><h3 class="h4">Buying a home</h3><p class="body-s">Preapproval, everyday priorities and the costs beyond the listing price, then showings shaped around your life.</p>' . ot_link_arrow( ot_page_url( 'buy' ), 'Start a home search' ) . '</div>
    <div class="stack"><h3 class="h4">Selling a home</h3><p class="body-s">Pricing from comparable closed sales, preparation, photography and a launch planned around your calendar.</p>' . ot_link_arrow( ot_page_url( 'sell' ), 'Plan your sale' ) . '</div>
    <div class="stack"><h3 class="h4">A market review</h3><p class="body-s">A property-specific look at recent closed sales before you decide what to offer or where to price.</p>' . ot_link_arrow( ot_page_url( 'sell', 'consult' ), 'Request a market review' ) . '</div>
  </div>
</div></section>';

		case 'stories':
			return '<section class="section surface-inverse"><div class="container split" style="align-items:center">
  <div class="stack" style="grid-column:1/span 5"><p class="label">Client stories</p><h2 class="display-m answer"><span>In their</span><span>own words.</span></h2></div>
  <div style="grid-column:7/span 6"><figure class="testimonial testimonial--placeholder"><span class="testimonial__mark"></span><blockquote>Verified client words about ' . $g . ' appear here, quoted exactly, with the client’s written permission on file.</blockquote><figcaption class="caption">Client first name, town · Year <span class="badge badge--proposed">Empty state</span></figcaption></figure><p class="caption" style="margin-top:var(--space-4)">No review or rating markup is published until real, permitted reviews exist.</p></div>
</div></section>';

		case 'questions':
			$faq = '';
			foreach ( ot_agent_faq( $x ) as $i => $q ) {
				$faq .= '<details' . ( 0 === $i ? ' open' : '' ) . '><summary>' . $q[0] . '</summary><div class="accordion__body body-s"><p>' . $q[1] . '</p></div></details>';
			}
			return '<section class="section"><div class="container split" style="align-items:start">
  <div class="stack" style="grid-column:1/span 5"><p class="label">Questions</p><h2 class="display-m answer"><span>Asked about</span><span>' . $g . '.</span></h2><p class="body-s muted">Plain answers to the questions people ask before they call.</p></div>
  <div class="accordion" style="grid-column:7/span 6">' . $faq . '</div>
</div></section>';

		case 'contact':
			$form = ot_inquiry_form( 'pf', 'Message ' . $x['r']['given'], array( 'Buying', 'Selling', 'Both', 'A market review', 'Something else' ), 'Send to ' . $x['r']['given'], 'agent:' . $x['p']->post_name, 'h3', 'Your message goes to ' . $x['r']['given'] . ' and is used only to respond to your request. [Privacy policy to be published before launch.]' );
			return '<section class="section surface-muted" id="contact-agent"><div class="container split" style="align-items:start">
  <div style="grid-column:1/span 5" class="stack"><p class="label">Contact ' . $g . '</p><h2 class="display-m answer"><span>Start with</span><span>a conversation.</span></h2><p>Tell ' . $g . ' what you are planning. Expect a reply by your preferred method; no account, no obligation.</p><a class="cta-panel__phone" href="tel:' . OT_TEL . '" style="color:var(--ot-navy);font-size:var(--step-h2)">' . OT_PHONE . '</a></div>
  <div style="grid-column:7/span 6" class="panel">' . $form . '</div>
</div></section>
<nav class="action-bar" aria-label="Contact ' . $g . '"><a class="btn btn--gold" href="tel:' . OT_TEL . '">' . ot_icon( 'phone' ) . 'Call</a><a class="btn" href="#contact-agent" style="--btn-bg:var(--ot-ivory);--btn-fg:var(--ot-navy);--btn-border:var(--ot-ivory)">Message ' . $g . '</a></nav>';
	}
	return '';
}

/** The joint profile of Brandon and Kaitlyn (prototype page_agent()). */
function ot_render_team_section( $section, $x ) {
	$r = $x['r'];
	switch ( $section ) {
		case 'hero':
			$trail = array( array( 'Home', home_url( '/' ) ), array( 'Our Team', ot_page_url( 'team' ) ), array( 'Brandon &amp; Kaitlyn Oberdorfer', $x['url'] ) );
			$prose = '';
			foreach ( (array) ( $r['prose'] ?? array() ) as $p ) {
				$prose .= '<p>' . ot_txt( $p ) . '</p>';
			}
			return '<section class="section" style="padding-top:var(--space-7)"><div class="container stack" style="--stack:var(--space-7)">
  ' . ot_breadcrumbs_html( $trail ) . '
  <div class="profile">
    <figure class="profile__photo"><img src="' . esc_url( ot_asset( 'assets/img/photo/team-portrait.webp' ) ) . '" alt="Brandon and Kaitlyn Oberdorfer" width="1400" height="1680"></figure>
    <div class="profile__body">
      <p class="label">Team leaders · Top Producer</p>
      <h1 class="display-l answer" id="page-title"><span>Brandon &amp; Kaitlyn</span><span>Oberdorfer</span></h1>
      <p class="label label--quiet">Husband. Wife. Your Team.</p>
      <div class="prose">' . $prose . '</div>
      <dl>
        <dt>Phone</dt><dd><a href="tel:' . OT_TEL . '">' . OT_PHONE . '</a></dd>
        <dt>Email</dt><dd>[team email to confirm]</dd>
        <dt>Specialties</dt><dd>Buyers · Sellers · First homes · Move-up moves</dd>
        <dt>Service area</dt><dd>Massachusetts</dd>
        <dt>Brokerage</dt><dd>REWAP Brokerage LLC</dd>
        <dt>License</dt><dd>[MA license numbers to be added once supplied]</dd>
      </dl>
      <div class="cluster"><a class="btn" href="#contact-agent">Send a message</a><a class="btn btn--secondary" href="tel:' . OT_TEL . '">Call ' . OT_PHONE . '</a></div>
    </div>
  </div>
</div></section>';

		case 'about':
			return '<section class="section surface-inverse"><div class="container"><div class="triad">
  <div><p class="label">Personal</p><p class="h3">Two partners invested in your goals.</p></div>
  <div><p class="label">Purposeful</p><p class="h3">A plan shaped around your home and life.</p></div>
  <div><p class="label">Prepared</p><p class="h3">Clear guidance and attention to the details.</p></div>
</div></div></section>';

		case 'help':
			return '<section class="section"><div class="container">' . ot_section_head( 'Meet each of us', 'Individual profiles', array( 'Two partners,', 'one standard.' ), null, null, 'h2', 'each-title' ) . '<div class="card-grid-3">' . ot_render_agent_cards( false ) . '</div></div></section>';

		case 'contact':
			$form = ot_inquiry_form( 'agent', 'Message Brandon & Kaitlyn', array( 'Buying', 'Selling', 'Both', 'Something else' ), 'Send my message', 'agent:brandon-kaitlyn', 'h3' );
			return '<section class="section surface-muted" id="contact-agent"><div class="container split" style="align-items:start">
  <div style="grid-column:1/span 5" class="stack"><p class="label">Contact</p><h2 class="display-m answer"><span>We would love</span><span>to be a resource.</span></h2><p>Messages from this page are attributed to Brandon and Kaitlyn in the team\'s lead routing.</p></div>
  <div style="grid-column:7/span 6" class="panel">' . $form . '</div>
</div></section>';
	}
	return '';
}
