<?php
/**
 * Title: Team standard
 * Slug: oberdorfer/team-standard
 * Categories: oberdorfer
 * Inserter: yes
 * Description: Sandstone section "One standard, shared by every agent." with links to the team and to joining, beside the team leaders' card.
 *
 * The team leaders' card is an HTML fragment (ot/agent-cards lists the individual agents only).
 */
$u    = fn( $p ) => esc_url( oberdorfer_url( $p ) );
$card = '<article class="agent-card" style="grid-column:1/-1" data-specialty="buyers sellers first-time move-up" data-area="massachusetts wakefield worcester boston" data-lang="english">
  <a class="agent-card__photo" href="' . $u( '/agents/brandon-kaitlyn-oberdorfer/' ) . '"><img src="' . esc_url( oberdorfer_asset( 'assets/img/photo/team-portrait.webp' ) ) . '" alt="Brandon and Kaitlyn Oberdorfer" width="1400" height="1680" loading="lazy"></a>
  <div class="stack" style="--stack:var(--space-2)"><p class="agent-card__title">Team leaders · Top Producer</p><h3 class="agent-card__name"><a href="' . $u( '/agents/brandon-kaitlyn-oberdorfer/' ) . '" style="text-decoration:none;color:inherit">Brandon &amp; Kaitlyn Oberdorfer</a></h3><p class="body-s muted">Husband. Wife. Your Team.</p></div>
  <div class="agent-card__meta"><span class="chip">Buyers</span><span class="chip">Sellers</span><span class="chip">Massachusetts</span></div>
  <div class="cluster" style="--cluster:var(--space-5)"><a class="link-arrow" href="' . $u( '/agents/brandon-kaitlyn-oberdorfer/' ) . '">View profile ' . oberdorfer_arrow() . '</a><a class="link-arrow" href="tel:' . OBERDORFER_TEL . '">' . OBERDORFER_PHONE . '</a></div>
</article>';
$copy = oberdorfer_p( 'A growing team', 'label' )
	. oberdorfer_h( oberdorfer_lines( array( 'One standard,', 'shared by every agent.' ) ), 2, 'display-m answer', 'team-title' )
	. oberdorfer_p( 'The Oberdorfer Team works from Worcester and is welcoming new agents who share its care for clients and presentation.' )
	. oberdorfer_buttons( oberdorfer_button( 'Meet the team', oberdorfer_url( '/team/' ), 'link-arrow' ) . oberdorfer_button( 'Join our team', oberdorfer_url( '/join/' ), 'link-arrow' ) );
echo oberdorfer_section( oberdorfer_group( oberdorfer_group( $copy, 'stack ot-col-1-6' ) . oberdorfer_group( oberdorfer_html( $card ), 'cols ot-col-8-5' ), 'container split ot-align-center' ), 'section surface-muted', '', 'team-title' ); // phpcs:ignore WordPress.Security.EscapeOutput
