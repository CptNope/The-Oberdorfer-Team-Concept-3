<?php
/**
 * Leads > Sequences: the follow-up emails each kind of lead receives, written for people who
 * are not technical.
 *
 * - Each sequence is a card with an On/Off switch and one choice: wait for my OK before each
 *   email, or send on its own.
 * - Its emails read as a timeline ("Right away", "2 days later"). Each opens to edit:
 *   - when it goes out;
 *   - what kind it is: I write it, a monthly town update, or an AI draft from my instructions;
 *   - the subject and the message in plain text. Paragraphs are blank lines, and personal
 *     details are inserted as [First name] style placeholders from buttons, never typed codes;
 *   - an optional repeat, and a live preview with a sample person.
 * - "Add an email" and "Remove this email" do what they say.
 *
 * Storage is unchanged (option ot_leads_sequences; HTML bodies with {merge_tags}). Plain text is
 * turned into HTML on save. An email whose HTML is more than paragraphs and simple links keeps
 * an HTML box, so nothing a developer wrote is lost.
 */

defined( 'ABSPATH' ) || exit;

/** Merge tags and the words people see for them. */
function ot_seq_tokens() {
	return array(
		'{first_name}'   => 'First name',
		'{agent_name}'   => 'Agent name',
		'{agent_first}'  => 'Agent first name',
		'{agent_phone}'  => 'Agent phone',
		'{agent_email}'  => 'Agent email',
		'{towns}'        => 'Their towns',
		'{town_links}'   => 'Links to their town guides',
		'{listing_link}' => 'Link to the listing they asked about',
		'{buy_url}'      => 'Buying guide link',
		'{sell_url}'     => 'Selling guide link',
		'{market_url}'   => 'Market page link',
	);
}

/** "{first_name}" to "[First name]". */
function ot_seq_to_words( $s ) {
	$map = array();
	foreach ( ot_seq_tokens() as $tag => $label ) {
		$map[ $tag ] = '[' . $label . ']';
	}
	return strtr( (string) $s, $map );
}

/** "[First name]" back to "{first_name}" (any letter case). */
function ot_seq_to_tags( $s ) {
	foreach ( ot_seq_tokens() as $tag => $label ) {
		$s = preg_replace( '/\[' . preg_quote( $label, '/' ) . '\]/i', $tag, (string) $s );
	}
	return $s;
}

/** The plain text of an HTML body, or null when it has formatting plain text cannot hold. */
function ot_seq_html_to_text( $html ) {
	$h = trim( str_replace( array( "\r\n", "\r" ), "\n", (string) $html ) );
	// A link whose text is its own address becomes the address.
	$h = preg_replace( '#<a href="([^"]+)">\1</a>#', '$1', $h );
	$probe = preg_replace( '#</?p>|<br\s*/?>#i', '', $h );
	if ( preg_match( '/<[a-z!\/]/i', $probe ) ) {
		return null;
	}
	$h = preg_replace( '#<br\s*/?>\n?#i', "\n", $h );
	$h = preg_replace( '#</p>\s*<p>#i', "\n\n", $h );
	$h = preg_replace( '#</?p>#i', '', $h );
	return ot_seq_to_words( trim( html_entity_decode( $h, ENT_QUOTES, 'UTF-8' ) ) );
}

/** Plain text (blank line = new paragraph) to the HTML the mailer sends. */
function ot_seq_text_to_html( $text ) {
	$text  = ot_seq_to_tags( trim( str_replace( array( "\r\n", "\r" ), "\n", (string) $text ) ) );
	$paras = preg_split( "/\n\s*\n/", $text );
	$out   = '';
	foreach ( $paras as $p ) {
		$p = trim( $p );
		if ( '' === $p ) {
			continue;
		}
		$p = htmlspecialchars( $p, ENT_NOQUOTES, 'UTF-8' ); // text between tags; quotes need no escaping
		// Link tags and web addresses become links.
		$p = preg_replace( '/\{(buy_url|sell_url|market_url)\}/', '<a href="{$1}">{$1}</a>', $p );
		$p = preg_replace( '#(?<![">])(https?://[^\s<]+)#', '<a href="$1">$1</a>', $p );
		$out .= '<p>' . str_replace( "\n", '<br>', $p ) . '</p>';
	}
	return $out;
}

add_action( 'ot_leads_admin_action', function ( $do ) {
	if ( 'sequences' !== $do || ! current_user_can( 'ot_leads_manage' ) ) {
		return;
	}
	if ( ! empty( $_POST['reset'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- checked in the handler
		delete_option( 'ot_leads_sequences' );
		wp_safe_redirect( add_query_arg( 'ot_msg', rawurlencode( 'All sequences are back to the starter emails.' ), admin_url( 'admin.php?page=ot-leads-sequences' ) ) );
		exit;
	}
	$in  = (array) wp_unslash( $_POST['seq'] ?? array() ); // phpcs:ignore WordPress.Security.NonceVerification
	$cur = ot_nurture_sequences();
	$out = array();
	foreach ( $cur as $key => $s ) {
		$p     = (array) ( $in[ $key ] ?? array() );
		$steps = array();
		foreach ( (array) ( $p['steps'] ?? array() ) as $st ) {
			$mode = in_array( $st['mode'] ?? '', array( 'template', 'ai', 'town_update' ), true ) ? $st['mode'] : 'template';
			$subj = sanitize_text_field( ot_seq_to_tags( $st['subject'] ?? '' ) );
			if ( ! empty( $st['delete'] ) || ( '' === trim( $subj ) && 'town_update' !== $mode ) ) {
				continue;
			}
			$body = 'html' === ( $st['format'] ?? '' ) ? wp_kses_post( $st['body'] ?? '' ) : ot_seq_text_to_html( $st['body'] ?? '' );
			$steps[] = array_filter( array(
				'day'     => max( 0, min( 3650, (int) ( $st['day'] ?? 0 ) ) ),
				'subject' => $subj,
				'mode'    => $mode,
				'body'    => 'template' === $mode ? $body : '',
				'brief'   => 'ai' === $mode ? sanitize_textarea_field( $st['brief'] ?? '' ) : '',
				'repeat'  => ! empty( $st['repeat_on'] ) ? max( 1, (int) ( $st['repeat'] ?? 30 ) ) : 0,
			), fn( $v ) => '' !== $v && 0 !== $v ) + array( 'day' => 0 );
		}
		usort( $steps, fn( $a, $b ) => $a['day'] <=> $b['day'] );
		$out[ $key ] = array(
			'name'   => sanitize_text_field( $p['name'] ?? $s['name'] ) ?: $s['name'],
			'when'   => $s['when'] ?? '',
			'active' => empty( $p['active'] ) ? 0 : 1,
			'mode'   => 'auto' === ( $p['mode'] ?? '' ) ? 'auto' : 'manual',
			'steps'  => $steps ?: $s['steps'],
		);
	}
	update_option( 'ot_leads_sequences', $out, false );
	wp_safe_redirect( add_query_arg( 'ot_msg', rawurlencode( 'Saved. New emails follow these steps from now on.' ), admin_url( 'admin.php?page=ot-leads-sequences' ) ) );
	exit;
} );

/** "Right away", "The next day", "5 days later". */
function ot_seq_day_words( $d ) {
	$d = (int) $d;
	return 0 === $d ? 'Right away' : ( 1 === $d ? 'The next day' : $d . ' days later' );
}

/** One email in a sequence; $i is its index or "__I__" for the template. */
function ot_seq_step_html( $k, $i, $st, $open = false ) {
	$n     = 'seq[' . $k . '][steps][' . $i . ']';
	$id    = 'ot-' . $k . '-' . $i;
	$mode  = $st['mode'] ?? 'template';
	$text  = ot_seq_html_to_text( $st['body'] ?? '' );
	$html  = null === $text;
	$day   = (int) ( $st['day'] ?? 0 );
	$rep   = (int) ( $st['repeat'] ?? 0 );
	$kinds = array( 'template' => 'I write it', 'town_update' => 'Monthly town update', 'ai' => 'AI drafts it' );
	$subj  = ot_seq_to_words( $st['subject'] ?? '' );
	$o  = '<details class="ot-step"' . ( $open ? ' open' : '' ) . ' data-step>';
	$o .= '<summary class="ot-step__sum"><span class="ot-step__when" data-when>' . esc_html( ot_seq_day_words( $day ) ) . '</span><span class="ot-step__title" data-title>' . esc_html( $subj ?: 'New email' ) . '</span><span class="ot-step__kind" data-kind>' . esc_html( $kinds[ $mode ] ?? '' ) . ( $rep ? ' · repeats every ' . $rep . ' days' : '' ) . '</span><span class="ot-step__edit">Edit<span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span></span></summary>';
	$o .= '<div class="ot-step__body">';

	// When.
	$o .= '<fieldset class="ot-field"><legend>When does it go out?</legend><label class="ot-inline">Send it <input type="number" min="0" max="3650" name="' . esc_attr( $n ) . '[day]" value="' . $day . '" class="ot-num" data-day> days after they start <span class="ot-muted">(0 means right away)</span></label>'
		. '<label class="ot-check"><input type="checkbox" name="' . esc_attr( $n ) . '[repeat_on]" value="1"' . checked( $rep > 0, true, false ) . ' data-repeat-on> Then keep sending it</label>'
		. '<label class="ot-inline ot-repeat">every <input type="number" min="1" max="3650" name="' . esc_attr( $n ) . '[repeat]" value="' . ( $rep ?: 30 ) . '" class="ot-num" data-repeat> days</label></fieldset>';

	// Kind.
	$o .= '<fieldset class="ot-field"><legend>What kind of email?</legend><div class="ot-kinds">';
	$help = array(
		'template'    => 'You write the words. They go out as you wrote them, with the person\'s details filled in.',
		'town_update' => 'Built each month from the guides of the towns they follow: recent prices with their source, your listings there and the guide link. You only write the subject.',
		'ai'          => 'Tell the AI what this email should do. It writes a draft using only their inquiry and the site\'s guides, and the draft waits for your OK.',
	);
	foreach ( $kinds as $mk => $ml ) {
		$o .= '<label class="ot-kind"><input type="radio" name="' . esc_attr( $n ) . '[mode]" value="' . esc_attr( $mk ) . '"' . checked( $mode, $mk, false ) . ' data-mode><span><b>' . esc_html( $ml ) . '</b>' . esc_html( $help[ $mk ] ) . '</span></label>';
	}
	$o .= '</div></fieldset>';

	// Subject, message or instructions.
	$o .= '<div class="ot-field"><label for="' . esc_attr( $id ) . '-s" class="ot-label">Subject line</label><input type="text" id="' . esc_attr( $id ) . '-s" name="' . esc_attr( $n ) . '[subject]" value="' . esc_attr( $subj ) . '" class="large-text" data-subject placeholder="For example: The first steps to buying in Massachusetts"></div>';
	$o .= '<div class="ot-field ot-only-template"><div class="ot-label-row"><label for="' . esc_attr( $id ) . '-b" class="ot-label">Message</label>' . ( $html ? '' : '<span class="ot-muted">Leave a blank line between paragraphs.</span>' ) . '</div>';
	if ( $html ) {
		$o .= '<p class="ot-muted">This email has custom formatting, so it is shown as HTML. Change the words between the tags.</p><input type="hidden" name="' . esc_attr( $n ) . '[format]" value="html"><textarea id="' . esc_attr( $id ) . '-b" name="' . esc_attr( $n ) . '[body]" rows="10" class="ot-code" data-body>' . esc_textarea( $st['body'] ?? '' ) . '</textarea>';
	} else {
		$o .= '<input type="hidden" name="' . esc_attr( $n ) . '[format]" value="text"><textarea id="' . esc_attr( $id ) . '-b" name="' . esc_attr( $n ) . '[body]" rows="10" data-body placeholder="Hi [First name],&#10;&#10;Write your message here.&#10;&#10;[Agent name]&#10;The Oberdorfer Team · [Agent phone]">' . esc_textarea( (string) $text ) . '</textarea>';
	}
	$o .= '<div class="ot-insert" role="group" aria-label="Insert a detail"><span class="ot-muted">Insert:</span>';
	foreach ( ot_seq_tokens() as $tag => $label ) {
		$o .= '<button type="button" class="ot-chip" data-insert="[' . esc_attr( $label ) . ']">' . esc_html( $label ) . '</button>';
	}
	$o .= '</div></div>';
	$o .= '<div class="ot-field ot-only-ai"><label for="' . esc_attr( $id ) . '-a" class="ot-label">What should this email do?</label><textarea id="' . esc_attr( $id ) . '-a" name="' . esc_attr( $n ) . '[brief]" rows="3" placeholder="For example: check in a week later, mention the market page, and offer a 20-minute call.">' . esc_textarea( $st['brief'] ?? '' ) . '</textarea></div>';

	// Preview and remove.
	$o .= '<div class="ot-field ot-only-template"><p class="ot-label">Preview <span class="ot-muted">with a sample person, Dana</span></p><div class="ot-preview" data-preview aria-live="polite"></div></div>';
	$o .= '<div class="ot-step__foot"><label class="ot-remove"><input type="checkbox" name="' . esc_attr( $n ) . '[delete]" value="1"> Remove this email</label></div>';
	return $o . '</div></details>';
}

function ot_leads_sequences_screen() {
	ot_leads_shell( 'sequence' );
	echo '<div class="wrap ot-wrap" style="max-width:1040px">';
	ot_leads_head( 'Follow-up emails', 'Each kind of inquiry gets its own series of emails. They stop by themselves when the person replies, books an appointment or unsubscribes, or when you stop them on their lead page.' );
	ot_leads_notice();

	$a      = array_values( array_filter( ot_leads_agents(), fn( $x ) => empty( $x['team'] ) ) )[0] ?? array( 'name' => 'Brandon Oberdorfer', 'given' => 'Brandon', 'phone' => OT_PHONE, 'email' => '' );
	$page   = fn( $s ) => function_exists( 'ot_page_url' ) ? ot_page_url( $s ) : home_url( '/' . $s . '/' );
	$sample = array(
		'First name'                           => 'Dana',
		'Agent name'                           => $a['name'],
		'Agent first name'                     => $a['given'] ?? strtok( $a['name'], ' ' ),
		'Agent phone'                          => $a['phone'] ?: OT_PHONE,
		'Agent email'                          => $a['email'] ?? '',
		'Their towns'                          => 'Worcester and Holden',
		'Links to their town guides'           => '<u>Worcester</u>, <u>Holden</u>',
		'Link to the listing they asked about' => '<u>12 Sample Street</u>',
		'Buying guide link'                    => '<u>' . esc_html( $page( 'buy' ) ) . '</u>',
		'Selling guide link'                   => '<u>' . esc_html( $page( 'sell' ) ) . '</u>',
		'Market page link'                     => '<u>' . esc_html( $page( 'market' ) ) . '</u>',
	);

	echo ot_leads_form( 'sequences', 0, 'class="ot-seqs"' ); // phpcs:ignore WordPress.Security.EscapeOutput
	foreach ( ot_nurture_sequences() as $key => $s ) {
		$k  = esc_attr( $key );
		$on = ! empty( $s['active'] );
		echo '<section class="ot-card ot-seq' . ( $on ? '' : ' is-off' ) . '" data-seq="' . $k . '">';
		echo '<div class="ot-seq__head"><div class="ot-seq__name"><label class="screen-reader-text" for="ot-name-' . $k . '">Name of this series</label><input id="ot-name-' . $k . '" type="text" name="seq[' . $k . '][name]" value="' . esc_attr( $s['name'] ) . '" class="ot-seq__title"><p class="ot-muted">Starts for: ' . esc_html( $s['when'] ?? '' ) . '</p></div>';
		echo '<label class="ot-switch"><input type="checkbox" name="seq[' . $k . '][active]" value="1"' . checked( $on, true, false ) . ' data-switch><span class="ot-switch__track" aria-hidden="true"></span><span class="ot-switch__on">On</span><span class="ot-switch__off">Off</span></label></div>';
		echo '<fieldset class="ot-seq__mode"><legend class="screen-reader-text">Before each email goes out</legend><span class="ot-muted">Before each email goes out:</span>'
			. '<label><input type="radio" name="seq[' . $k . '][mode]" value="manual"' . checked( ( $s['mode'] ?? 'manual' ) !== 'auto', true, false ) . '> Wait for my OK <span class="ot-muted">(it waits in Approvals)</span></label>'
			. '<label><input type="radio" name="seq[' . $k . '][mode]" value="auto"' . checked( $s['mode'] ?? '', 'auto', false ) . '> Send it on its own</label></fieldset>';
		echo '<div class="ot-steps-list" data-steps>';
		foreach ( $s['steps'] as $i => $st ) {
			echo ot_seq_step_html( $key, $i, $st ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo '</div><button type="button" class="ot-btn" data-add="' . $k . '" data-next="' . count( $s['steps'] ) . '"><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>Add an email</button>';
		echo '<template data-template="' . $k . '">' . ot_seq_step_html( $key, '__I__', array( 'day' => 7, 'subject' => '', 'mode' => 'template', 'body' => '' ), true ) . '</template>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '</section>';
	}
	echo '<div class="ot-savebar"><button class="button button-primary">Save changes</button><span class="ot-muted">Nothing changes until you save.</span></div>';
	echo '<p class="ot-seq-foot"><span class="ot-muted">The office address and an unsubscribe link are added at the bottom of every email.</span><button class="button-link ot-reset" name="reset" value="1" onclick="return confirm(\'Put every series back to the starter emails? Your changes here will be lost.\')">Start over with the starter emails</button></p></form></div>';
	?>
<script>
( function () {
	var sample = <?php echo wp_json_encode( $sample ); ?>, tags = <?php echo wp_json_encode( ot_seq_tokens() ); ?>;
	function esc( s ) { return s.replace( /[&<>"]/g, function ( c ) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[ c ]; } ); }
	function fill( s ) { s = s.replace( /\{[a-z_]+\}/g, function ( t ) { return tags[ t ] ? '[' + tags[ t ] + ']' : t; } ); return s.replace( /\[([^\]]+)\]/g, function ( m, k ) { var hit = Object.keys( sample ).filter( function ( x ) { return x.toLowerCase() === k.toLowerCase(); } )[ 0 ]; return hit ? sample[ hit ] : m; } ); }
	function words( d ) { d = parseInt( d, 10 ) || 0; return d === 0 ? 'Right away' : ( d === 1 ? 'The next day' : d + ' days later' ); }
	var kinds = { template: 'I write it', town_update: 'Monthly town update', ai: 'AI drafts it' };
	function refresh( step ) {
		var body = step.querySelector( '[data-body]' ), out = step.querySelector( '[data-preview]' ), subj = step.querySelector( '[data-subject]' );
		var mode = ( step.querySelector( '[data-mode]:checked' ) || {} ).value || 'template';
		var rep = step.querySelector( '[data-repeat-on]' ).checked ? ', repeats every ' + ( step.querySelector( '[data-repeat]' ).value || 30 ) + ' days' : '';
		step.querySelector( '[data-when]' ).textContent = words( step.querySelector( '[data-day]' ).value );
		step.querySelector( '[data-title]' ).textContent = subj.value || 'New email';
		step.querySelector( '[data-kind]' ).textContent = kinds[ mode ] + rep.replace( ',', ' ·' );
		if ( out && body ) {
			var html = body.classList.contains( 'ot-code' ) ? body.value : body.value.split( /\n\s*\n/ ).filter( function ( p ) { return p.trim(); } ).map( function ( p ) { return '<p>' + esc( p.trim() ).replace( /\n/g, '<br>' ) + '</p>'; } ).join( '' );
			out.innerHTML = '<p class="ot-preview__subj">' + esc( fill( subj.value ) || '(no subject yet)' ).replace( /&lt;u&gt;|&lt;\/u&gt;/g, '' ) + '</p>' + ( fill( html ) || '<p class="ot-muted">Your message shows here as you type.</p>' );
		}
	}
	function wire( step ) {
		step.addEventListener( 'input', function () { refresh( step ); } );
		step.addEventListener( 'change', function () { refresh( step ); } );
		step.querySelectorAll( '[data-insert]' ).forEach( function ( b ) {
			b.addEventListener( 'click', function () {
				var t = step.querySelector( '[data-body]' ), v = b.getAttribute( 'data-insert' ), s = t.selectionStart || t.value.length;
				t.value = t.value.slice( 0, s ) + v + t.value.slice( t.selectionEnd || s );
				t.focus(); t.selectionStart = t.selectionEnd = s + v.length; refresh( step );
			} );
		} );
		refresh( step );
	}
	document.querySelectorAll( '[data-step]' ).forEach( wire );
	document.querySelectorAll( '[data-add]' ).forEach( function ( b ) {
		b.addEventListener( 'click', function () {
			var k = b.getAttribute( 'data-add' ), i = parseInt( b.getAttribute( 'data-next' ), 10 );
			var tpl = document.querySelector( 'template[data-template="' + k + '"]' ).innerHTML.replace( /__I__/g, i );
			b.setAttribute( 'data-next', i + 1 );
			var box = b.parentNode.querySelector( '[data-steps]' );
			box.insertAdjacentHTML( 'beforeend', tpl );
			var step = box.lastElementChild; wire( step ); step.querySelector( '[data-subject]' ).focus();
		} );
	} );
	document.querySelectorAll( '[data-switch]' ).forEach( function ( s ) { s.addEventListener( 'change', function () { s.closest( '.ot-seq' ).classList.toggle( 'is-off', ! s.checked ); } ); } );
} )();
</script>
	<?php
	ot_leads_shell_end();
}
