<?php
/**
 * Title: Terms of use
 * Slug: oberdorfer/terms-of-use
 * Categories: oberdorfer
 * Inserter: no
 * Description: The terms of use's body (the page template adds the title). Written from data/terms-of-use.json, copied from tools/data/ by tools/build_wp.py; the importer puts it in the Terms page once, and after that the team edits the page itself.
 */
echo oberdorfer_policy_blocks( 'terms-of-use' ); // phpcs:ignore WordPress.Security.EscapeOutput
