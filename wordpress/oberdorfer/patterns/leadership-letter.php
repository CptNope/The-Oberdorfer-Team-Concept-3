<?php
/**
 * Title: Letter from Brandon and Kaitlyn
 * Slug: oberdorfer/leadership-letter
 * Categories: oberdorfer
 * Inserter: yes
 * Description: The recruiting letter (drop cap, signature, marked as draft copy for the team to approve) beside the list of values.
 */
$values = array( 'Care', 'Honesty', 'Hard work', 'Personal service', 'Thoughtful guidance', 'Attention to detail', 'Creative marketing' );
$items  = '';
foreach ( $values as $v ) {
	$items .= "<!-- wp:list-item -->\n<li><span>{$v}</span></li>\n<!-- /wp:list-item -->\n\n";
}
$list   = "<!-- wp:list {\"className\":\"toc-mini values-list ot-fs-1\"} -->\n<ul class=\"wp-block-list toc-mini values-list ot-fs-1\">" . trim( $items ) . "</ul>\n<!-- /wp:list -->\n\n";
$letter = oberdorfer_group(
	oberdorfer_p( 'A letter from Brandon and Kaitlyn', 'label' )
	. oberdorfer_p( 'When we started The Oberdorfer Team, we built it on what we bring to our own family: care, honesty, hard work, and showing up for the people who count on us. As we prepare to open our team office, we want to share that standard with agents who feel the same way.', 'body-l letter__first' )
	. oberdorfer_p( 'Our clients range from first-time buyers to families selling a home they have loved for decades. Every one of them gets the same thoughtful presentation, clear guidance and personal attention. If that is how you want to work, we would love to talk.' )
	. oberdorfer_p( 'Brandon &amp; Kaitlyn', 'signature' )
	. oberdorfer_p( 'Draft copy for the team to edit and approve.', 'caption' ),
	'letter ot-col-1-7'
);
$aside = oberdorfer_group( oberdorfer_p( 'What we value', 'label' ) . $list, 'stack ot-col-9-4' );
echo oberdorfer_section( oberdorfer_group( $letter . $aside, 'container split ot-align-start' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
