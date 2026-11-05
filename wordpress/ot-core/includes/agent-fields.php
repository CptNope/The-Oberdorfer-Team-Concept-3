<?php
/**
 * Editable agent profiles. Each agent's edit screen has an "Agent details" box; what the team
 * saves there overrides the imported record, and the biography is the post body (the main
 * editor). The importer never overwrites these fields once they hold a value
 * (see ot_upsert's $keep list), so edits made on the site are safe.
 *
 * Empty fields keep rendering the [bracketed placeholder] with its "To confirm" badge, so the
 * profile never shows an unconfirmed fact as confirmed.
 */

defined( 'ABSPATH' ) || exit;

/** Meta key => [record key, label, type, help]. */
function ot_agent_field_defs() {
	return array(
		'ot_role'         => array( 'role', 'Role on the team', 'text', 'For example, Team leader.' ),
		'ot_job_title'    => array( 'title', 'Professional title', 'text', 'For example, MA Licensed Realtor.' ),
		'ot_summary'      => array( 'summary', 'One-sentence summary', 'textarea', 'Shown under the name, and read by search engines. Empty uses the standard sentence about leading the team.' ),
		'ot_phone'        => array( 'direct', 'Direct phone', 'text', 'For example, 978-257-4938. The Call button dials it.' ),
		'ot_email'        => array( 'email', 'Email', 'text', '' ),
		'ot_specialties'  => array( 'specialties', 'Specialties', 'text', 'For example, First-time buyers · Sellers · Multi-family.' ),
		'ot_languages'    => array( 'languages', 'Languages', 'text', 'For example, English, Spanish.' ),
		'ot_service_area' => array( 'service_area', 'Service area', 'text', 'Empty shows Massachusetts.' ),
		'ot_license'      => array( 'license', 'MA license number', 'text', 'Shown only once entered; verify it with the Board of Registration first.' ),
		'ot_accolades'    => array( 'accolades', 'Recognition', 'accolades', 'One per line: the award, a vertical bar, then where it comes from. For example: Top Producer | Wakefield at Home, Fall 2026. Verified claims only.' ),
		'ot_card_url'     => array( 'card', 'Digital business card', 'text', 'The full link, for example https://blinq.me/...' ),
		'ot_photo_id'     => array( 'photo_id', 'Headshot', 'photo', 'A 4:5 portrait. Empty uses the interim crop of the team portrait.' ),
		'ot_photo_note'   => array( 'photo_note', 'Photo caption', 'text', 'Optional, for example the photographer\'s credit.' ),
	);
}

add_action( 'init', function () {
	$auth = fn() => current_user_can( 'edit_posts' );
	foreach ( array( 'ot_summary', 'ot_specialties', 'ot_languages', 'ot_service_area', 'ot_license', 'ot_accolades', 'ot_photo_note' ) as $k ) {
		register_post_meta( 'ot_agent', $k, array( 'type' => 'string', 'single' => true, 'show_in_rest' => true, 'auth_callback' => $auth ) );
	}
	register_post_meta( 'ot_agent', 'ot_photo_id', array( 'type' => 'integer', 'single' => true, 'show_in_rest' => true, 'auth_callback' => $auth ) );
} );

/** "Award | Source" lines => [ [text, source], ... ]. */
function ot_parse_accolades( $text ) {
	$out = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $text ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		$parts = array_map( 'trim', explode( '|', $line, 2 ) );
		$out[] = array( 'text' => $parts[0], 'source' => $parts[1] ?? '' );
	}
	return $out;
}

/** An agent's record: the imported data, overridden by what the team saved in "Agent details". */
function ot_agent_rec( $post ) {
	$post = get_post( $post );
	$rec  = json_decode( (string) get_post_meta( $post->ID, 'ot_record', true ), true ) ?: array();
	$rec['name'] = $rec['name'] ?? $post->post_title;
	foreach ( ot_agent_field_defs() as $meta => $d ) {
		$v = get_post_meta( $post->ID, $meta, true );
		if ( get_post_meta( $post->ID, $meta . '_cleared', true ) ) {
			$rec[ $d[0] ] = 'accolades' === $d[2] ? array() : '';
			continue;
		}
		if ( '' === $v || null === $v || false === $v ) {
			continue;
		}
		$rec[ $d[0] ] = 'accolades' === $d[2] ? ot_parse_accolades( $v ) : $v;
	}
	// Photo: an uploaded headshot wins over the theme's interim crop.
	if ( ! empty( $rec['photo_id'] ) && wp_attachment_is_image( (int) $rec['photo_id'] ) ) {
		$src = wp_get_attachment_image_src( (int) $rec['photo_id'], 'large' );
		$rec['photo_url'] = $src[0];
		$rec['photo_w']   = $src[1];
		$rec['photo_h']   = $src[2];
		$rec['photo_interim'] = false;
	} else {
		$rec['photo_url']     = ! empty( $rec['photo'] ) ? ot_asset( $rec['photo'] ) : ot_asset( 'assets/img/photo/team-portrait.webp' );
		$rec['photo_interim'] = true;
	}
	return $rec;
}

/** Text form of a record value, to prefill the box with what the page shows now. */
function ot_agent_field_value( $post, $meta, $d ) {
	$v = get_post_meta( $post->ID, $meta, true );
	if ( ( '' !== $v && null !== $v && false !== $v ) || get_post_meta( $post->ID, $meta . '_cleared', true ) ) {
		return (string) $v;
	}
	$rec = json_decode( (string) get_post_meta( $post->ID, 'ot_record', true ), true ) ?: array();
	$rv  = $rec[ $d[0] ] ?? '';
	if ( 'accolades' === $d[2] && is_array( $rv ) ) {
		return implode( "\n", array_map( fn( $a ) => $a['text'] . ( $a['source'] ? ' | ' . $a['source'] : '' ), $rv ) );
	}
	return is_scalar( $rv ) ? (string) $rv : '';
}

/* --------------------------------------------------------------------- the box */

add_action( 'add_meta_boxes_ot_agent', function ( $post ) {
	add_meta_box( 'ot-agent-details', 'Agent details', 'ot_agent_box', 'ot_agent', 'normal', 'high' );
} );

function ot_agent_box( $post ) {
	wp_nonce_field( 'ot_agent_save', 'ot_agent_nonce' );
	$rec = json_decode( (string) get_post_meta( $post->ID, 'ot_record', true ), true ) ?: array();
	if ( ( $rec['kind'] ?? '' ) === 'team' ) {
		echo '<p>This is the joint profile of Brandon and Kaitlyn. Write its introduction in the main editor above; while the editor is empty, the page shows the three paragraphs from <em>Wakefield at Home</em>. Each person\'s details are edited on their own profile.</p>';
		return;
	}
	wp_enqueue_media();
	echo '<p><strong>The biography is the main editor above</strong> (120 to 250 words, in the first person). While it is empty, the page shows a placeholder asking for it. The fields below fill the profile\'s quick facts; an empty field shows a [bracketed placeholder] with a "To confirm" badge, so only confirmed facts appear as facts.</p><table class="form-table" role="presentation"><tbody>';
	foreach ( ot_agent_field_defs() as $meta => $d ) {
		$v    = ot_agent_field_value( $post, $meta, $d );
		$id   = esc_attr( $meta );
		$help = $d[3] ? '<p class="description">' . esc_html( $d[3] ) . '</p>' : '';
		echo '<tr><th scope="row"><label for="' . $id . '">' . esc_html( $d[1] ) . '</label></th><td>';
		if ( 'textarea' === $d[2] || 'accolades' === $d[2] ) {
			echo '<textarea class="large-text" rows="3" id="' . $id . '" name="' . $id . '">' . esc_textarea( $v ) . '</textarea>';
		} elseif ( 'photo' === $d[2] ) {
			$pid = (int) $v;
			echo '<input type="hidden" id="' . $id . '" name="' . $id . '" value="' . ( $pid ?: '' ) . '"><div id="ot-headshot-preview" style="margin-bottom:8px">' . ( $pid ? wp_get_attachment_image( $pid, array( 120, 150 ) ) : '' ) . '</div><button type="button" class="button" id="ot-headshot-pick">Choose headshot</button> <button type="button" class="button-link" id="ot-headshot-clear">Use the interim photo</button>';
		} else {
			echo '<input class="regular-text" type="text" id="' . $id . '" name="' . $id . '" value="' . esc_attr( $v ) . '">';
		}
		// What the box showed when the page loaded: on save, a field still holding it is left
		// alone, so an edit typed on the page (bound blocks save the same meta) is not overwritten.
		echo '<input type="hidden" name="ot_orig[' . $id . ']" value="' . esc_attr( 'photo' === $d[2] ? (string) ( (int) $v ?: '' ) : (string) $v ) . '">';
		echo $help . '</td></tr>';
	}
	echo '</tbody></table>';
	?>
	<script>
	( function () {
		var pick = document.getElementById( 'ot-headshot-pick' ), field = document.getElementById( 'ot_photo_id' ), prev = document.getElementById( 'ot-headshot-preview' ), frame;
		if ( ! pick ) { return; }
		pick.addEventListener( 'click', function () {
			if ( ! frame ) {
				frame = wp.media( { title: 'Headshot', button: { text: 'Use this photo' }, multiple: false, library: { type: 'image' } } );
				frame.on( 'select', function () {
					var a = frame.state().get( 'selection' ).first().toJSON();
					field.value = a.id;
					prev.innerHTML = '<img src="' + ( a.sizes && a.sizes.medium ? a.sizes.medium.url : a.url ) + '" style="max-width:120px;height:auto">';
				} );
			}
			frame.open();
		} );
		document.getElementById( 'ot-headshot-clear' ).addEventListener( 'click', function () { field.value = ''; prev.innerHTML = ''; } );
	} )();
	</script>
	<?php
}

add_action( 'save_post_ot_agent', function ( $post_id ) {
	if ( ! isset( $_POST['ot_agent_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['ot_agent_nonce'] ), 'ot_agent_save' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	foreach ( ot_agent_field_defs() as $meta => $d ) {
		if ( ! isset( $_POST[ $meta ] ) ) {
			continue;
		}
		$raw = wp_unslash( $_POST[ $meta ] );
		if ( isset( $_POST['ot_orig'][ $meta ] ) && (string) $raw === (string) wp_unslash( $_POST['ot_orig'][ $meta ] ) ) {
			continue; // unchanged in the box
		}
		switch ( $d[2] ) {
			case 'textarea':
			case 'accolades':
				$val = sanitize_textarea_field( $raw );
				break;
			case 'photo':
				$val = (int) $raw ?: '';
				break;
			default:
				$val = 'ot_card_url' === $meta ? esc_url_raw( $raw ) : sanitize_text_field( $raw );
		}
		if ( 'ot_email' === $meta && $val && ! is_email( $val ) ) {
			continue; // keep the previous value rather than store an invalid address
		}
		// A field the team empties stays empty: the "_cleared" marker stops the imported value
		// from showing through again.
		if ( '' === $val ) {
			update_post_meta( $post_id, $meta, '' );
			update_post_meta( $post_id, $meta . '_cleared', 1 );
		} else {
			update_post_meta( $post_id, $meta, $val );
			delete_post_meta( $post_id, $meta . '_cleared' );
		}
	}
} );
