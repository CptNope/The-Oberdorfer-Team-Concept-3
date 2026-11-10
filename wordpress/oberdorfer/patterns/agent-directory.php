<?php
/**
 * Title: Agent directory
 * Slug: oberdorfer/agent-directory
 * Categories: oberdorfer
 * Inserter: yes
 * Description: The agent cards with the open seat (ot/agent-cards). The filter bar (specialty, service area, language) was removed on 2026-11-10 while the team has two agents; bring it back from git history when it grows.
 */
echo oberdorfer_section( // phpcs:ignore WordPress.Security.EscapeOutput
	oberdorfer_group(
		oberdorfer_h( 'Team profiles', 2, 'visually-hidden' )
		. oberdorfer_group( '<!-- wp:ot/agent-cards /-->', 'card-grid-3 ot-data-team-grid' ),
		'container'
	)
);
