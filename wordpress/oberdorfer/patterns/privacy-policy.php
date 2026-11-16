<?php
/**
 * Title: Privacy policy
 * Slug: oberdorfer/privacy-policy
 * Categories: oberdorfer
 * Inserter: no
 * Description: The privacy policy's body (the page template adds the title). Written from data/privacy-policy.json, copied from tools/data/ by tools/build_wp.py; the importer puts it in the Privacy policy page once, and after that the team edits the page itself.
 */
echo oberdorfer_policy_blocks( 'privacy-policy' ); // phpcs:ignore WordPress.Security.EscapeOutput
