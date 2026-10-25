<?php
/**
 * Title: Team introduction
 * Slug: oberdorfer/team-intro
 * Categories: oberdorfer
 * Inserter: yes
 * Description: The team portrait beside "Husband. Wife. Your Team.", the introduction, the three principles (Personal, Purposeful, Prepared) and a link to the team's story.
 */
$photo = '<!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"intro__photo reveal"} -->
<figure class="wp-block-image size-full intro__photo reveal"><img src="' . esc_url( oberdorfer_asset( 'assets/img/photo/team-portrait.webp' ) ) . '" alt="Brandon and Kaitlyn Oberdorfer"/></figure>
<!-- /wp:image -->

';
$triad = oberdorfer_group(
	oberdorfer_group( oberdorfer_p( 'Personal', 'label' ) . oberdorfer_p( 'Two partners invested in your goals.', 'h5' ) )
	. oberdorfer_group( oberdorfer_p( 'Purposeful', 'label' ) . oberdorfer_p( 'A plan shaped around your home and life.', 'h5' ) )
	. oberdorfer_group( oberdorfer_p( 'Prepared', 'label' ) . oberdorfer_p( 'Clear guidance and attention to the details.', 'h5' ) ),
	'triad ot-mt-4'
);
$body = oberdorfer_p( 'The people behind the promise', 'label' )
	. oberdorfer_h( oberdorfer_lines( array( 'Husband. Wife.', 'Your Team.' ) ), 2, 'display-m answer', 'intro-title' )
	. oberdorfer_p( 'Home holds the everyday moments that become a life: dinners around the table, celebrations with friends, and a familiar place to land at the end of the day.', 'body-l' )
	. oberdorfer_p( 'We are Brandon and Kaitlyn Oberdorfer, a husband-and-wife real estate partnership. Our work reflects the values we bring to our own family: care, honesty, hard work, and showing up for the people who count on us.' )
	. $triad
	. oberdorfer_group( oberdorfer_link_arrow( 'Our story', oberdorfer_url( '/agents/brandon-kaitlyn-oberdorfer/' ) ) . oberdorfer_p( 'Top Producer · REWAP Brokerage', 'caption' ), 'cluster ot-cluster-6' );
echo oberdorfer_section( oberdorfer_group( $photo . oberdorfer_group( $body, 'intro__body' ), 'container intro' ), 'section', '', 'intro-title' ); // phpcs:ignore WordPress.Security.EscapeOutput
