<?php
/**
 * Title: Page not found
 * Slug: oberdorfer/not-found
 * Categories: oberdorfer
 * Inserter: no
 * Description: The 404 page: hero with "Page not found" and links to the home page, the Buy page, the community guides and Contact.
 */
echo oberdorfer_page_hero( array( 'This page', 'is not here.' ), 'Page not found', 'The page may have moved or no longer exists. Start again from one of these pages, or call ' . OBERDORFER_PHONE . '.' ) // phpcs:ignore WordPress.Security.EscapeOutput
	. oberdorfer_section( // phpcs:ignore WordPress.Security.EscapeOutput
		oberdorfer_group(
			oberdorfer_buttons(
				oberdorfer_button( 'Home', oberdorfer_url( '/' ), 'link-arrow' )
				. oberdorfer_button( 'Buy a home', oberdorfer_url( '/buy/' ), 'link-arrow' )
				. oberdorfer_button( 'Community guides', oberdorfer_url( '/communities/' ), 'link-arrow' )
				. oberdorfer_button( 'Contact', oberdorfer_url( '/contact/' ), 'link-arrow' ),
				'cluster ot-cluster-6'
			),
			'container'
		)
	);
