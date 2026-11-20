<?php
/**
 * The mortgage lender the team introduces buyers to (Settings > Lender, block ot/lender).
 *
 * The section is advertising for a mortgage loan originator, so it carries what the law asks
 * of that and nothing more:
 *  - SAFE Act and 209 CMR 42.00 (Massachusetts Division of Banks): the loan officer's NMLS ID,
 *    the company's NMLS ID and its Massachusetts license number, with a link to NMLS Consumer
 *    Access to check them.
 *  - RESPA section 8: no fee or thing of value for a referral, a plain "you are free to choose any
 *    lender", and that using the lender is not a condition of working with the team. A shared-
 *    advertising arrangement must be paid at fair value and is disclosed when there is one.
 *  - Regulation Z: no rates, APRs, payments or loan terms (they would need full disclosures).
 *  - Fair lending: the Equal Housing Opportunity statement.
 *
 * It renders nothing on the site until the team has recorded the business arrangement and ticked
 * that REWAP and the lender's compliance team approved it. The details live in an option, so a
 * theme change loses nothing; the defaults come from data/lender.json (tools/data/lender.json).
 */

defined( 'ABSPATH' ) || exit;

const OT_LENDER_OPTION = 'ot_lender';

/** The text fields, in screen order: key => [label, help]. */
function ot_lender_fields() {
	return array(
		'name'          => array( 'Name', '' ),
		'title'         => array( 'Title', '' ),
		'nmls'          => array( 'Loan officer NMLS ID', 'Required in every advertisement (SAFE Act).' ),
		'company'       => array( 'Company (legal name)', '' ),
		'company_short' => array( 'Company (short name)', 'Used in sentences.' ),
		'company_nmls'  => array( 'Company NMLS ID', '' ),
		'ma_license'    => array( 'Massachusetts license number', 'The Division of Banks mortgage broker and lender license, shown in every advertisement (209 CMR 42.00).' ),
		'office'        => array( 'Office phone', '' ),
		'office_ext'    => array( 'Office extension', '' ),
		'mobile'        => array( 'Mobile phone', 'Leave empty to hide.' ),
		'email'         => array( 'Email', '' ),
		'street'        => array( 'Street', '' ),
		'city'          => array( 'City', '' ),
		'postcode'      => array( 'ZIP code', '' ),
		'url'           => array( 'Lender\'s page', '' ),
		'intro'         => array( 'Introduction', 'Written by the team. No rates, payments, "best" or "lowest" claims, and nothing promising approval.' ),
		'bio'           => array( 'About the lender', 'The lender\'s own words, word for word. Leave empty to hide.' ),
		'bio_source'    => array( 'Source of those words', '' ),
	);
}

/** Saved details merged over the defaults from data/lender.json. */
function ot_lender() {
	static $defaults = null;
	if ( null === $defaults ) {
		$f        = OT_CORE_DIR . '/data/lender.json';
		$defaults = is_readable( $f ) ? (array) json_decode( (string) file_get_contents( $f ), true ) : array(); // phpcs:ignore WordPress.WP.AlternativeFunctions
		unset( $defaults['_about'] );
	}
	$saved = get_option( OT_LENDER_OPTION, array() );
	$l     = array_merge( array_fill_keys( array_keys( ot_lender_fields() ), '' ), $defaults, is_array( $saved ) ? $saved : array() );
	$l['arrangement'] = in_array( $l['arrangement'] ?? '', array( 'none', 'shared' ), true ) ? $l['arrangement'] : '';
	$l['rewap_ok']    = ! empty( $l['rewap_ok'] );
	$l['lender_ok']   = ! empty( $l['lender_ok'] );
	return $l;
}

/** What still stops the section from showing (empty when it shows). */
function ot_lender_missing( $l = null ) {
	$l    = $l ?: ot_lender();
	$miss = array();
	if ( '' === $l['arrangement'] ) {
		$miss[] = 'the business arrangement with the lender';
	}
	if ( ! $l['rewap_ok'] ) {
		$miss[] = 'REWAP\'s approval';
	}
	if ( ! $l['lender_ok'] ) {
		$miss[] = 'the lender\'s approval';
	}
	foreach ( array( 'name', 'nmls', 'company', 'company_nmls', 'ma_license' ) as $k ) {
		if ( '' === trim( (string) $l[ $k ] ) ) {
			$miss[] = strtolower( ot_lender_fields()[ $k ][0] );
		}
	}
	return $miss;
}

function ot_nmls_url( $kind, $id ) {
	return 'https://www.nmlsconsumeraccess.org/EntityDetails.aspx/' . $kind . '/' . rawurlencode( preg_replace( '/\D/', '', (string) $id ) );
}

/**
 * The disclosure under the section (prototype lender_disclosure()): two caption paragraphs.
 * With no arrangement, the first is the team's own statement (wording supplied by the team and
 * approved with REWAP and the lender, 2026-11-19), word for word.
 */
const OT_LENDER_STATEMENT = 'REWAP Brokerage and its affiliated real estate professionals do not receive any compensation, referral fees, kickbacks, or other financial incentives, directly or indirectly, in connection with referrals to our preferred lending partners. Our preferred lenders are featured solely as a resource and convenience to our clients. Clients are under no obligation to use any recommended lender and are encouraged to explore and select the financing provider that best meets their individual needs.';

function ot_lender_disclosure( $l ) {
	$co    = esc_html( $l['company_short'] ?: $l['company'] );
	$first = 'shared' === $l['arrangement']
		? 'You are free to choose any lender, and using ' . $co . ' is not a condition of working with us. ' . $co . ' pays its proportional share of the cost of advertising that features it. Neither The Oberdorfer Team nor REWAP Brokerage LLC receives any fee or other payment for referring you.'
		: esc_html( OT_LENDER_STATEMENT );
	return '<p class="caption">' . $first . '</p><p class="caption">'
		. esc_html( $l['company'] ) . ' is a separate company, not part of The Oberdorfer Team or REWAP Brokerage LLC. Loan approval, rates and terms come from the lender and depend on your application; this is not an offer or commitment to lend. '
		. esc_html( $l['name'] ) . ', NMLS ID ' . esc_html( $l['nmls'] ) . '. ' . esc_html( $l['company'] ) . ', NMLS ID ' . esc_html( $l['company_nmls'] ) . ', Massachusetts Mortgage Broker and Lender license ' . esc_html( $l['ma_license'] )
		. '. Check both on <a href="https://www.nmlsconsumeraccess.org/">NMLS Consumer Access</a>. Equal Housing Opportunity.</p>';
}

/** The Buy page's financing section (prototype lender_section()). */
function ot_render_lender() {
	$l    = ot_lender();
	$miss = ot_lender_missing( $l );
	if ( $miss ) {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST && current_user_can( 'edit_pages' ) ) { // editor preview
			return '<div class="panel"><p class="label">Lender (hidden)</p><p class="body-s">This section stays off the site until Settings &gt; Lender records ' . esc_html( implode( ', ', $miss ) ) . '.</p></div>';
		}
		return '';
	}
	$tel  = fn( $n, $ext = '' ) => 'tel:+1' . preg_replace( '/\D/', '', $n ) . ( $ext ? ',' . preg_replace( '/\D/', '', $ext ) : '' );
	$rows = '<dt>NMLS ID</dt><dd><a href="' . esc_url( ot_nmls_url( 'INDIVIDUAL', $l['nmls'] ) ) . '">' . esc_html( $l['nmls'] ) . '</a></dd>'
		. '<dt>Company NMLS</dt><dd><a href="' . esc_url( ot_nmls_url( 'COMPANY', $l['company_nmls'] ) ) . '">' . esc_html( $l['company_nmls'] ) . '</a></dd>'
		. '<dt>MA license</dt><dd>Mortgage Broker and Lender ' . esc_html( $l['ma_license'] ) . '</dd>';
	if ( $l['office'] ) {
		$rows .= '<dt>Office</dt><dd><a href="' . esc_attr( $tel( $l['office'], $l['office_ext'] ) ) . '">' . esc_html( $l['office'] ) . ( $l['office_ext'] ? ' ext. ' . esc_html( $l['office_ext'] ) : '' ) . '</a></dd>';
	}
	if ( $l['mobile'] ) {
		$rows .= '<dt>Mobile</dt><dd><a href="' . esc_attr( $tel( $l['mobile'] ) ) . '">' . esc_html( $l['mobile'] ) . '</a></dd>';
	}
	if ( is_email( $l['email'] ) ) {
		$rows .= '<dt>Email</dt><dd style="overflow-wrap:anywhere"><a href="mailto:' . esc_attr( $l['email'] ) . '">' . esc_html( $l['email'] ) . '</a></dd>';
	}
	if ( $l['street'] ) {
		$rows .= '<dt>Office address</dt><dd>' . esc_html( $l['street'] ) . ', ' . esc_html( $l['city'] ) . ', MA ' . esc_html( $l['postcode'] ) . '</dd>';
	}
	$bio  = $l['bio'] ? '<figure class="stack" style="--stack:var(--space-2);margin-inline:0"><blockquote class="body-s" style="margin:0">' . esc_html( $l['bio'] ) . '</blockquote>' . ( $l['bio_source'] ? '<figcaption class="caption">' . esc_html( $l['bio_source'] ) . '</figcaption>' : '' ) . '</figure>' : '';
	$link = $l['url'] ? '<a class="link-arrow" href="' . esc_url( $l['url'] ) . '">' . esc_html( ( $l['company_short'] ?: $l['company'] ) . ': ' . $l['name'] ) . ' ' . ot_icon( 'external' ) . '</a>' : '';
	return '<section class="section surface-muted" id="financing" aria-labelledby="lender-title">
  <div class="container split" style="align-items:start">
    <div class="stack" style="grid-column:1/span 5;--stack:var(--space-5)">
      <p class="label">Financing</p>
      <h2 class="display-m answer" id="lender-title"><span>Start with</span><span>a preapproval.</span></h2>
      <p>' . esc_html( $l['intro'] ) . '</p>' . $bio . '
    </div>
    <div class="office-card" style="grid-column:7/span 6">
      <div class="stack" style="--stack:var(--space-1)"><p class="label">Mortgage lender</p><h3 class="h3">' . esc_html( $l['name'] ) . '</h3><p class="body-s">' . esc_html( $l['title'] ) . ', ' . esc_html( $l['company'] ) . '</p></div>
      <dl>' . $rows . '</dl>' . $link . '
    </div>
  </div>
  <div class="container"><div class="stack" style="margin-top:var(--space-6);max-width:80ch;--stack:var(--space-3)">' . ot_lender_disclosure( $l ) . '</div></div>
</section>';
}

/* --------------------------------------------------------------------- settings screen */

add_action( 'admin_menu', function () {
	add_options_page( 'Lender', 'Lender', 'edit_pages', 'ot-lender', 'ot_lender_screen' );
} );

add_action( 'admin_post_ot_lender_save', function () {
	if ( ! current_user_can( 'edit_pages' ) ) {
		wp_die( 'Not allowed.' );
	}
	check_admin_referer( 'ot_lender_save' );
	$in  = wp_unslash( $_POST['ot_lender'] ?? array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$out = array();
	foreach ( array_keys( ot_lender_fields() ) as $k ) {
		$v         = (string) ( $in[ $k ] ?? '' );
		$out[ $k ] = match ( $k ) {
			'email'               => sanitize_email( $v ),
			'url'                 => esc_url_raw( $v ),
			'intro', 'bio'        => sanitize_textarea_field( $v ),
			default               => sanitize_text_field( $v ),
		};
	}
	$out['arrangement'] = in_array( $in['arrangement'] ?? '', array( 'none', 'shared' ), true ) ? $in['arrangement'] : '';
	$out['rewap_ok']    = ! empty( $in['rewap_ok'] );
	$out['lender_ok']   = ! empty( $in['lender_ok'] );
	$out['updated_by']  = wp_get_current_user()->display_name;
	$out['updated_at']  = current_time( 'Y-m-d' );
	update_option( OT_LENDER_OPTION, $out, false );
	wp_safe_redirect( admin_url( 'options-general.php?page=ot-lender&saved=1' ) );
	exit;
} );

function ot_lender_screen() {
	if ( ! current_user_can( 'edit_pages' ) ) {
		return;
	}
	$l    = ot_lender();
	$miss = ot_lender_missing( $l );
	echo '<div class="wrap"><h1>Lender</h1>';
	if ( isset( $_GET['saved'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		echo '<div class="notice notice-success inline"><p>Saved. Purge the page caches (Breeze, then Cloudflare) if the Buy page does not change.</p></div>';
	}
	echo '<p>The mortgage lender the team introduces buyers to, shown in the Financing section of the Buy page (block "Lender"). Brandon and Kaitlyn advertise him here, so the section follows the rules for mortgage advertising: his NMLS ID, the company\'s NMLS ID and Massachusetts license, a "free to choose any lender" disclosure and Equal Housing Opportunity are always printed, and rates, payments and loan terms are never shown.</p>';
	echo $miss
		? '<div class="notice notice-warning inline"><p><strong>Hidden on the site.</strong> Still needed: ' . esc_html( implode( '; ', $miss ) ) . '.</p></div>'
		: '<div class="notice notice-info inline"><p><strong>Shown on the Buy page.</strong> Last changed ' . esc_html( ( $l['updated_at'] ?? '' ) . ( ! empty( $l['updated_by'] ) ? ' by ' . $l['updated_by'] : '' ) ) . '.</p></div>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="ot_lender_save">';
	wp_nonce_field( 'ot_lender_save' );

	echo '<h2>Before it is shown</h2><table class="form-table" role="presentation"><tbody>';
	$opts = array(
		''       => 'Not recorded yet (hidden)',
		'none'   => 'None: nobody on the team or at REWAP receives anything of value from the lender',
		'shared' => 'Shared advertising: the lender pays its fair share of advertising that features it, and nothing for referrals',
	);
	echo '<tr><th scope="row"><label for="ot-lender-arr">Business arrangement</label></th><td><select id="ot-lender-arr" name="ot_lender[arrangement]">';
	foreach ( $opts as $v => $t ) {
		echo '<option value="' . esc_attr( $v ) . '"' . selected( $l['arrangement'], $v, false ) . '>' . esc_html( $t ) . '</option>';
	}
	echo '</select><p class="description">Federal law (RESPA section 8) forbids any fee, gift, lunch, marketing credit or other thing of value for sending buyers to a lender. Shared advertising must be paid in proportion to what each side gets, in writing, and is disclosed on the page. If any lender, title or attorney arrangement involves ownership, an Affiliated Business Arrangement disclosure is needed instead: ask REWAP.</p></td></tr>';
	printf( '<tr><th scope="row">REWAP approval</th><td><label><input type="checkbox" name="ot_lender[rewap_ok]" value="1"%s> REWAP Brokerage LLC approved this section</label></td></tr>', checked( $l['rewap_ok'], true, false ) );
	printf( '<tr><th scope="row">Lender approval</th><td><label><input type="checkbox" name="ot_lender[lender_ok]" value="1"%s> The lender (and its compliance team) approved these details and this wording</label></td></tr>', checked( $l['lender_ok'], true, false ) );
	echo '</tbody></table>';

	echo '<h2>Details</h2><table class="form-table" role="presentation"><tbody>';
	foreach ( ot_lender_fields() as $k => list( $label, $help ) ) {
		$id = 'ot-lender-' . $k;
		$f  = in_array( $k, array( 'intro', 'bio' ), true )
			? '<textarea class="large-text" rows="4" id="' . $id . '" name="ot_lender[' . $k . ']">' . esc_textarea( $l[ $k ] ) . '</textarea>'
			: '<input class="regular-text" id="' . $id . '" name="ot_lender[' . $k . ']" value="' . esc_attr( $l[ $k ] ) . '">';
		echo '<tr><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label></th><td>' . $f . ( $help ? '<p class="description">' . esc_html( $help ) . '</p>' : '' ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</tbody></table>';
	submit_button( 'Save lender' );
	echo '</form><h2>Disclosure printed under the section</h2><div style="max-width:70em">' . wp_kses_post( ot_lender_disclosure( $l ) ) . '</div></div>';
}
