<?php
/**
 * Title: Agent profile: client stories
 * Slug: oberdorfer/agent-stories
 * Categories: oberdorfer
 * Inserter: no
 * Description: The client stories section on every agent profile. Until verified, permitted client words exist it shows the empty state; no review or rating markup is published.
 */
$left  = oberdorfer_p( 'Client stories', 'label' ) . oberdorfer_h( oberdorfer_lines( array( 'In their', 'own words.' ) ), 2, 'display-m answer' );
$quote = oberdorfer_html( '<figure class="testimonial testimonial--placeholder"><span class="testimonial__mark"></span><blockquote>Verified client words about {first name} appear here, quoted exactly, with the client’s written permission on file.</blockquote><figcaption class="caption">Client first name, town · Year <span class="badge badge--proposed">Empty state</span></figcaption></figure>' )
	. oberdorfer_p( 'No review or rating markup is published until real, permitted reviews exist.', 'caption ot-mt-4' );
echo oberdorfer_section( oberdorfer_group( oberdorfer_group( $left, 'stack ot-col-1-5' ) . oberdorfer_group( $quote, 'ot-col-7-6' ), 'container split ot-align-center' ), 'section surface-inverse' ); // phpcs:ignore WordPress.Security.EscapeOutput
