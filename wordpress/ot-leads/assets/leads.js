/**
 * The site's forms, for real (ot-leads). Replaces the theme's demonstration behavior for
 * [data-ot-lead] inquiry forms, [data-newsletter] forms and [data-ot-signup] sign-ups:
 * validates in place, posts to /wp-json/ot-leads/v1/lead, shows the result. Also records the
 * first page of the visit and its UTM tags, and logs page visits for known leads.
 * Plain ES5, no dependencies.
 */
( function () {
	var cfg = window.otLeads || {};
	var live = document.getElementById( 'live' );
	function say( t ) { if ( live ) { live.textContent = t; } }

	// First page and campaign tags of the visit, kept for the session.
	var first = {};
	try {
		first = JSON.parse( sessionStorage.getItem( 'ot_first' ) || 'null' );
		if ( ! first ) {
			var q = new URLSearchParams( location.search ), utm = {};
			[ 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'fbclid' ].forEach( function ( k ) { if ( q.get( k ) ) { utm[ k ] = q.get( k ); } } );
			first = { url: location.href.split( '#' )[ 0 ], referrer: document.referrer || '', utm: utm };
			sessionStorage.setItem( 'ot_first', JSON.stringify( first ) );
		}
	} catch ( e ) { first = { url: '', referrer: document.referrer || '', utm: {} }; }

	// Known visitor: log this page view (no personal data leaves the page).
	if ( /(?:^|;\s*)ot_lid=/.test( document.cookie ) && cfg.endpoint ) {
		try {
			fetch( cfg.endpoint + 'seen', { method: 'POST', credentials: 'same-origin', keepalive: true, headers: { 'Content-Type': 'application/json' }, body: JSON.stringify( { path: location.pathname, title: document.title } ) } );
		} catch ( e ) {}
	}

	// Turnstile widgets, rendered when the script is ready.
	var widgets = [];
	function renderTurnstile() {
		if ( ! window.turnstile ) { return; }
		Array.prototype.forEach.call( document.querySelectorAll( '.ot-turnstile' ), function ( el ) {
			if ( el.getAttribute( 'data-rendered' ) ) { return; }
			el.setAttribute( 'data-rendered', '1' );
			widgets.push( { el: el, id: window.turnstile.render( el, { sitekey: el.getAttribute( 'data-sitekey' ), size: 'flexible', appearance: 'interaction-only' } ) } );
		} );
	}
	if ( cfg.turnstile ) {
		var tries = 0, t = setInterval( function () { tries++; if ( window.turnstile ) { clearInterval( t ); renderTurnstile(); } else if ( tries > 40 ) { clearInterval( t ); } }, 250 );
	}
	function tokenFor( form ) {
		var w = widgets.filter( function ( x ) { return form.contains( x.el ); } )[ 0 ];
		return w && window.turnstile ? window.turnstile.getResponse( w.id ) || '' : '';
	}
	function resetToken( form ) {
		var w = widgets.filter( function ( x ) { return form.contains( x.el ); } )[ 0 ];
		if ( w && window.turnstile ) { window.turnstile.reset( w.id ); }
	}

	function icon( name ) {
		return cfg.icons ? "<svg class='icon' aria-hidden='true'><use href='" + cfg.icons + '#i-' + name + "'/></svg>" : '';
	}
	function status( form, ok, title, text ) {
		var s = form.querySelector( '[data-form-status]' );
		if ( ! s ) { say( title + ' ' + text ); return; }
		s.hidden = false;
		s.className = 'form-status form-status--' + ( ok ? 'success' : 'error' );
		s.innerHTML = icon( ok ? 'check' : 'alert' ) + '<div><strong></strong><span></span></div>';
		s.querySelector( 'strong' ).textContent = title;
		s.querySelector( 'span' ).textContent = ' ' + text;
		s.focus();
	}

	function msgFor( input ) {
		if ( input.validity.valueMissing ) { return ( input.getAttribute( 'data-label' ) || 'This field' ) + ' is required.'; }
		if ( input.validity.typeMismatch && input.type === 'email' ) { return 'Enter an email address like name@example.com.'; }
		if ( input.validity.patternMismatch ) { return input.getAttribute( 'data-pattern-msg' ) || 'Check the format.'; }
		return '';
	}
	function check( input ) {
		var m = msgFor( input ), field = input.closest( '.field' );
		input.setAttribute( 'aria-invalid', m ? 'true' : 'false' );
		if ( field ) {
			field.classList.toggle( 'field--error', !! m );
			var err = field.querySelector( '.field__error' );
			if ( err ) { err.hidden = ! m; var t = err.querySelector( 'span' ); if ( t ) { t.textContent = m; } }
		}
		return ! m;
	}

	function payload( form ) {
		var d = { lead_type: form.getAttribute( 'data-lead-type' ) || '', page: location.href.split( '#' )[ 0 ], first: first.url, referrer: first.referrer, utm: first.utm, turnstile: tokenFor( form ) };
		Array.prototype.forEach.call( form.elements, function ( el ) {
			if ( el.type === 'email' && el.value ) { d.email = el.value; return; } // the newsletter field has no name
			if ( ! el.name || el.disabled ) { return; }
			var key = /-pref$/.test( el.name ) ? 'pref' : ( el.name === 'msg' ? 'message' : el.name );
			if ( el.type === 'radio' ) { if ( el.checked ) { d[ key ] = el.value; } return; }
			if ( el.type === 'checkbox' ) { if ( el.checked ) { d[ key ] = el.value; d.consent_text = ( el.closest( 'label' ) || {} ).textContent || ''; } return; }
			if ( el.type === 'email' ) { d.email = el.value; return; }
			d[ key ] = el.value;
		} );
		if ( form.hasAttribute( 'data-consent' ) ) { d.consent_email = '1'; d.consent_text = form.getAttribute( 'data-consent' ); }
		if ( form.hasAttribute( 'data-newsletter' ) ) { d.lead_type = 'newsletter'; d.consent_email = '1'; d.consent_text = 'Subscribe to the At Home issues from The Oberdorfer Team.'; }
		return d;
	}

	function send( form, done ) {
		var btn = form.querySelector( 'button[type=submit]' );
		if ( btn ) { btn.setAttribute( 'aria-busy', 'true' ); btn.disabled = true; }
		fetch( cfg.endpoint + 'lead', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify( payload( form ) ) } )
			.then( function ( r ) { return r.json().then( function ( j ) { return { ok: r.ok, j: j }; } ); } )
			.then( function ( res ) { done( res.ok && res.j.ok, ( res.j && res.j.message ) || '' ); } )
			.catch( function () { done( false, 'We could not send this. Check your connection, or call ' + ( cfg.phone || 'us' ) + '.' ); } )
			.then( function () { if ( btn ) { btn.removeAttribute( 'aria-busy' ); btn.disabled = false; } resetToken( form ); } );
	}

	// Runs before the theme's own handlers (capture phase), so the demo behavior never fires.
	document.addEventListener( 'submit', function ( ev ) {
		var form = ev.target;
		if ( ! form.matches || ! form.matches( 'form[data-ot-lead], form[data-newsletter], form[data-ot-signup]' ) ) { return; }
		ev.preventDefault();
		ev.stopImmediatePropagation();
		var ok = true, firstBad = null;
		Array.prototype.forEach.call( form.querySelectorAll( '[required], [type=email], [pattern]' ), function ( i ) { if ( ! check( i ) ) { ok = false; firstBad = firstBad || i; } } );
		if ( ! ok ) {
			firstBad.focus();
			status( form, false, 'A few details are missing.', 'Fix the highlighted fields and send again.' );
			return;
		}
		send( form, function ( good, msg ) {
			if ( form.hasAttribute( 'data-newsletter' ) ) {
				var b = form.querySelector( 'button' );
				if ( good && b ) { b.textContent = 'Subscribed'; b.disabled = true; }
				say( good ? 'You are subscribed. Check your inbox for the next issue.' : msg );
				return;
			}
			if ( good ) {
				status( form, true, 'Thank you.', msg.replace( /^Thank you\.\s*/, '' ) );
				if ( form.hasAttribute( 'data-reset-after' ) ) { setTimeout( function () { form.reset(); var s = form.querySelector( '[data-form-status]' ); if ( s ) { s.hidden = true; } form.querySelector( 'input' ).focus(); }, 4000 ); } else { form.reset(); }
			} else {
				status( form, false, 'Not sent.', msg );
			}
		} );
	}, true );

	// The theme's demo validation also attached to these forms: drop its marker so it stays idle.
	Array.prototype.forEach.call( document.querySelectorAll( 'form[data-ot-lead]' ), function ( f ) { f.setAttribute( 'novalidate', '' ); } );
} )();
