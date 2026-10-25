<?php
/**
 * Title: Buying and selling paths
 * Slug: oberdorfer/buy-sell-paths
 * Categories: oberdorfer
 * Inserter: yes
 * Description: Two side-by-side panels, navy for buyers and sandstone for sellers, each with a house line drawing, an answering headline and two actions.
 */
$art = fn( $n ) => oberdorfer_html( '<svg class="path__art" viewBox="0 0 400 260" aria-hidden="true"><use href="' . esc_url( oberdorfer_asset( 'assets/img/illustrations.svg' ) ) . '#' . $n . '"/></svg>' );
$buy = $art( 'colonial' )
	. oberdorfer_group( oberdorfer_p( 'If you are buying', 'label' ) . oberdorfer_h( oberdorfer_lines( array( 'Get ready', 'before you find the one.' ) ), 2, 'display-m answer' ) . oberdorfer_p( 'Preapproval, everyday priorities, and the costs beyond the listing price. A calm plan for your first home or your next one.', 'ot-measure-40' ), 'stack ot-stack-4' )
	. oberdorfer_buttons( oberdorfer_button( 'Start your search', oberdorfer_url( '/buy/' ) ) . oberdorfer_button( 'The buying process', oberdorfer_url( '/buy/#process' ), 'link-arrow ot-text-ivory' ) );
$sell = $art( 'cape' )
	. oberdorfer_group( oberdorfer_p( 'If you are selling', 'label' ) . oberdorfer_h( oberdorfer_lines( array( 'Make your home', 'feel exceptional.' ) ), 2, 'display-m answer' ) . oberdorfer_p( 'Thoughtful presentation. Clear pricing. A plan that fits your life.', 'ot-measure-40' ), 'stack ot-stack-4' )
	. oberdorfer_buttons( oberdorfer_button( 'Request a market review', oberdorfer_url( '/sell/#consult' ) ) . oberdorfer_button( 'How we sell', oberdorfer_url( '/sell/' ), 'link-arrow' ) );
echo oberdorfer_group( oberdorfer_group( $buy, 'path path--buy surface-inverse' ) . oberdorfer_group( $sell, 'path path--sell surface-muted' ), 'paths', 'section', '', 'Buying and selling' ); // phpcs:ignore WordPress.Security.EscapeOutput
