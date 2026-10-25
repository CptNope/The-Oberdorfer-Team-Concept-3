<?php
/**
 * Title: Three decisions that shape a sale
 * Slug: oberdorfer/seller-steps
 * Categories: oberdorfer
 * Inserter: yes
 * Description: Section head and three numbered steps: comparisons, first impression, a plan around your life.
 *
 * The steps are an HTML fragment (heading plus paragraph per item, on the prototype's grid).
 */
$steps = oberdorfer_html( '<ol class="steps" style="grid-template-columns:repeat(auto-fit,minmax(min(100%,18rem),1fr));display:grid;gap:var(--space-7) var(--gutter)">
    <li><div><h3>Start with the right comparisons.</h3><p class="body-s">Review recent closed sales with similar location, style, condition, and living area. Agree on a pricing strategy before choosing a launch date.</p></div></li>
    <li><div><h3>Create a strong first impression.</h3><p class="body-s">Clear the entry, simplify the rooms, and show the home in good light. Let professional photography and a thoughtful property story lead the presentation.</p></div></li>
    <li><div><h3>Build a plan around your life.</h3><p class="body-s">Discuss your timing, showing preferences, and next move. A clear preparation calendar makes the many decisions easier to manage.</p></div></li>
  </ol>' );
echo oberdorfer_section( oberdorfer_group( oberdorfer_section_head( 'If you are selling', 'Sell', array( 'Three decisions', 'that shape a sale.' ), '', null, 'steps-title' ) . $steps, 'container' ), 'section', '', 'steps-title' ); // phpcs:ignore WordPress.Security.EscapeOutput
