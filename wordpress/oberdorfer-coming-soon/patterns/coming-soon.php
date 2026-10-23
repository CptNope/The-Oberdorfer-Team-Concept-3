<?php
/**
 * Title: Coming soon
 * Slug: oberdorfer-coming-soon/coming-soon
 * Categories: featured
 * Inserter: no
 * Description: The single coming-soon page: logo, the coming-soon announcement, team portrait, search-facing heading, brand line, contact details and the brokerage disclosure.
 *
 * Every text block here is editable in Appearance > Editor. Nothing on this page may be
 * invented. The copy was supplied by the team on 2026-10-22, with Kaitlyn's and Brandon's
 * phone numbers and emails; the Worcester office address and the REWAP relationship are
 * confirmed (CLAUDE.md, ground rule 2). The disclosure line is approved by REWAP's broker
 * of record before the domain goes live.
 */
$img = get_theme_file_uri( 'assets/img/' );
?>
<!-- wp:group {"tagName":"main","anchor":"main","className":"cs-page","layout":{"type":"default"}} -->
<main id="main" class="wp-block-group cs-page"><!-- wp:group {"className":"cs-frame","layout":{"type":"default"}} -->
<div class="wp-block-group cs-frame"><!-- wp:group {"className":"cs-brand","layout":{"type":"default"}} -->
<div class="wp-block-group cs-brand"><!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"cs-logo"} -->
<figure class="wp-block-image size-full cs-logo"><img src="<?php echo esc_url( $img . 'logo-primary-gold.svg' ); ?>" alt="The Oberdorfer Team"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph {"className":"cs-powered"} -->
<p class="cs-powered">Powered by REWAP</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"cs-announce","layout":{"type":"default"}} -->
<div class="wp-block-group cs-announce"><!-- wp:paragraph {"className":"cs-announce__pre"} -->
<p class="cs-announce__pre">Our new website is</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"cs-announce__big"} -->
<p class="cs-announce__big">Coming soon</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"cs-grid","layout":{"type":"default"}} -->
<div class="wp-block-group cs-grid"><!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"cs-portrait"} -->
<figure class="wp-block-image size-full cs-portrait"><img src="<?php echo esc_url( $img . 'team-portrait.webp' ); ?>" alt="Kaitlyn and Brandon Oberdorfer" width="1400" height="1680" fetchpriority="high"/></figure>
<!-- /wp:image -->

<!-- wp:group {"className":"cs-copy","layout":{"type":"default"}} -->
<div class="wp-block-group cs-copy"><!-- wp:heading {"level":1,"className":"cs-kicker"} -->
<h1 class="wp-block-heading cs-kicker">Massachusetts real estate</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"cs-display"} -->
<p class="cs-display">Luxury at <em>every price point.</em></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"cs-pillars"} -->
<p class="cs-pillars">Exceptional homes. Thoughtful representation. Personal attention.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"cs-lead"} -->
<p class="cs-lead">From distinctive estates to thoughtfully chosen first homes, Brandon and Kaitlyn Oberdorfer bring strategic marketing, skilled negotiation and attentive guidance to buyers and sellers throughout Massachusetts.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"cs-soon"} -->
<p class="cs-soon">Your next chapter can begin today.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"className":"cs-actions"} -->
<div class="wp-block-buttons cs-actions"><!-- wp:button {"className":"cs-btn cs-btn--gold"} -->
<div class="wp-block-button cs-btn cs-btn--gold"><a class="wp-block-button__link wp-element-button" href="#contact">Connect With Our Team</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"cs-btn"} -->
<div class="wp-block-button cs-btn"><a class="wp-block-button__link wp-element-button" href="mailto:kaitlynoberdorfer@gmail.com,brandonoberdorfer@gmail.com?subject=Hello%20from%20oberdorferteam.com">Send Us a Message</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:group {"anchor":"contact","className":"cs-contacts","layout":{"type":"default"}} -->
<div id="contact" class="wp-block-group cs-contacts"><!-- wp:paragraph {"className":"cs-person"} -->
<p class="cs-person"><strong>Kaitlyn Oberdorfer <span class="cs-role">MA Licensed Realtor</span></strong> <a href="tel:+19782574938">978-257-4938</a> <a href="mailto:kaitlynoberdorfer@gmail.com">kaitlynoberdorfer@gmail.com</a> <a class="cs-vcard" href="<?php echo esc_url( home_url( '/?vcard=kaitlyn-oberdorfer' ) ); ?>" aria-label="Add Kaitlyn Oberdorfer to your contacts">Add to contacts</a> <a class="cs-minor" href="https://calendly.com/kaitlynoberdorfer/30min" rel="noopener">Book a meeting</a> <a class="cs-minor" href="https://instagram.com/_ma_real_estate" rel="noopener">Instagram</a> <a class="cs-minor" href="https://blinq.me/WXgsLzUBTC2V" rel="noopener">Digital card</a></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"cs-person"} -->
<p class="cs-person"><strong>Brandon Oberdorfer <span class="cs-role">MA Licensed Realtor</span></strong> <a href="tel:+14062507620">406-250-7620</a> <a href="mailto:brandonoberdorfer@gmail.com">brandonoberdorfer@gmail.com</a> <a class="cs-vcard" href="<?php echo esc_url( home_url( '/?vcard=brandon-oberdorfer' ) ); ?>" aria-label="Add Brandon Oberdorfer to your contacts">Add to contacts</a> <a class="cs-minor" href="https://www.instagram.com/oberdorferrealtygroup/" rel="noopener">Instagram</a> <a class="cs-minor" href="https://blinq.me/cmere9vg301rxs60mq7nu5g5g" rel="noopener">Digital card</a></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"cs-saveall"} -->
<p class="cs-saveall"><a class="cs-vcard" href="<?php echo esc_url( home_url( '/?vcard=oberdorfer-team' ) ); ?>">Save both contacts</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:paragraph {"className":"cs-serving"} -->
<p class="cs-serving">Serving clients across the Bay State.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:html -->
<svg class="cs-townscape" viewBox="0 0 400 260" aria-hidden="true" focusable="false"><g fill="none" stroke="currentColor" stroke-linecap="square" stroke-linejoin="miter" vector-effect="non-scaling-stroke"> <path stroke-width="1.2" d="M0 206L400 206M44 206V152L74 130L96 145V206M96 145L110 156V206M140 206V134H202V206M136 134L171 114L206 134M220 206V138H280V206M216 138L250 116L284 138M240 124V96H260V124M242 92V74H258V92M243 60L250 18L257 60M310 174H362L336 152Z"/> <path stroke-width="0.8" d="M20 216L380 216M56 225L344 225M66 134V112H76V126M165 98h12v14h-12ZM162 98L171 88L180 98M164 206V180A7 7 0 0 1 178 180V206M238 206V180A12 12 0 0 1 262 180V206M237 92h26v4h-26ZM240 70h20v4h-20ZM244 70V60H256V70M314 206V196H358V206M316 196L316 174M330 196L330 174M342 196L342 174M356 196L356 174M9 166.61C9 147.13 16.15 140 22 140C27.85 140 35 147.13 35 166.61C35 181.58 28.5 187.52 22 187.52C15.5 187.52 9 181.58 9 166.61ZM22 206L22 187.52M114 170.19C114 152.48 119.5 146 124 146C128.5 146 134 152.48 134 170.19C134 183.8 129 189.2 124 189.2C119 189.2 114 183.8 114 170.19ZM124 206L124 189.2M287 171.39C287 154.26 292.5 148 297 148C301.5 148 307 154.26 307 171.39C307 184.54 302 189.76 297 189.76C292 189.76 287 184.54 287 171.39ZM297 206L297 189.76M370 176.16C370 161.4 375.5 156 380 156C384.5 156 390 161.4 390 176.16C390 187.5 385 192 380 192C375 192 370 187.5 370 176.16ZM380 206L380 192"/> <path stroke-width="0.55" d="M104 234L296 234M160 243L240 243M40 155L74 127L114 159M51 160h10v14h-10ZM51 184h10v14h-10ZM79 160h10v14h-10ZM79 184h10v14h-10ZM100 184h6v22h-6ZM145 131L171 117L197 131M171 88L171 80M147 146h10v16h-10ZM147 176h10v16h-10ZM185 146h10v16h-10ZM185 176h10v16h-10ZM164 146h14v16h-14ZM224 135L250 119L276 135M250 168L250 206M225 186V160A5 5 0 0 1 235 160V186ZM265 186V160A5 5 0 0 1 275 160V186ZM246 90V80A4 4 0 0 1 254 80V90M250 18L250 10M318 196L318 205M322 196L322 205M326 196L326 205M330 196L330 205M334 196L334 205M338 196L338 205M342 196L342 205M346 196L346 205M350 196L350 205M354 196L354 205M314 177L358 177M336 152L336 145M22 187.52V153.07M22 175.64L15.5 160.2M22 168.51L28.5 155.44M22 161.38L18.1 150.69M124 189.2V157.88M124 178.4L119 164.36M124 171.92L129 160.04M124 165.44L121 155.72M297 189.76V159.48M297 179.32L292 165.75M297 173.06L302 161.57M297 166.79L294 157.4M380 192V165.9M380 183L375 171.3M380 177.6L385 167.7M380 172.2L377 164.1"/> </g></svg>
<!-- /wp:html --></div>
<!-- /wp:group --></main>
<!-- /wp:group -->

<!-- wp:group {"tagName":"footer","className":"cs-footer","layout":{"type":"default"}} -->
<footer class="wp-block-group cs-footer"><!-- wp:group {"className":"cs-footer__inner","layout":{"type":"default"}} -->
<div class="wp-block-group cs-footer__inner"><!-- wp:image {"width":"180px","sizeSlug":"full","linkDestination":"none","className":"cs-rewap"} -->
<figure class="wp-block-image size-full is-resized cs-rewap"><img src="<?php echo esc_url( $img . 'rewap-logo.webp' ); ?>" alt="REWAP Brokerage" style="width:180px"/></figure>
<!-- /wp:image -->

<!-- wp:group {"className":"cs-footer__text","layout":{"type":"default"}} -->
<div class="wp-block-group cs-footer__text"><!-- wp:paragraph {"className":"cs-disclosure"} -->
<p class="cs-disclosure"><strong>The Oberdorfer Team</strong> is a real estate team operating under <strong>REWAP Brokerage LLC</strong>, Massachusetts. Office: 652 Park Ave, Unit B, Worcester, MA 01603.</p>
<!-- /wp:paragraph -->

<!-- wp:html -->
<p class="cs-eho"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 11L12 4L21 11M5 9.5V20H19V9.5"/><path d="M9 12H15M9 15.5H15"/></svg>Equal Housing Opportunity</p>
<!-- /wp:html -->

<!-- wp:paragraph {"className":"cs-copy"} -->
<p class="cs-copy">© 2026 The Oberdorfer Team</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></footer>
<!-- /wp:group -->
