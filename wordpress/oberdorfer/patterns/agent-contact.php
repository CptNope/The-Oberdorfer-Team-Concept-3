<?php
/**
 * Title: Agent profile: contact
 * Slug: oberdorfer/agent-contact
 * Categories: oberdorfer
 * Inserter: no
 * Description: The contact section at the foot of every agent profile: editable text and the team phone on the left, the agent's own message form (ot/agent-profile contact-form) on the right.
 */
$left = oberdorfer_p( 'Contact {first name}', 'label' )
	. oberdorfer_h( oberdorfer_lines( array( 'Start with', 'a conversation.' ) ), 2, 'display-m answer' )
	. oberdorfer_p( 'Tell {first name} what you are planning. Expect a reply by your preferred method; no account, no obligation.' )
	. oberdorfer_phone_link( 'cta-panel__phone ot-text-navy ot-fs-h2' );
$right = oberdorfer_group( "<!-- wp:ot/agent-profile {\"section\":\"contact-form\"} /-->\n\n", 'panel ot-col-7-6' );
echo oberdorfer_section( oberdorfer_group( oberdorfer_group( $left, 'stack ot-col-1-5' ) . $right, 'container split ot-align-start' ), 'section surface-muted', 'contact-agent' ); // phpcs:ignore WordPress.Security.EscapeOutput
