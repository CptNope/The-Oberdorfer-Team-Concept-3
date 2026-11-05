<?php
/**
 * Title: Agent profile: about heading
 * Slug: oberdorfer/agent-about-intro
 * Categories: oberdorfer
 * Inserter: no
 * Description: "About {first name}" and "Meet {first name}." above the biography on an agent profile. {first name} is replaced with the agent's first name.
 */
echo oberdorfer_p( 'About {first name}', 'label' ) . oberdorfer_h( 'Meet {first name}.', 2, 'display-m' ); // phpcs:ignore WordPress.Security.EscapeOutput
