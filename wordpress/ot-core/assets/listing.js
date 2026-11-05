/**
 * Listing and agent pages: click-to-load video and 3D tours, share and copy link, and the
 * payment estimate. Plain ES5, no dependencies; everything works as links without it.
 */
( function () {
	var live = document.getElementById( 'live' );
	function say( msg ) {
		if ( live ) { live.textContent = ''; setTimeout( function () { live.textContent = msg; }, 50 ); }
	}

	// Video and 3D tours: replace the poster with the player only when asked.
	Array.prototype.forEach.call( document.querySelectorAll( '[data-embed]' ), function ( box ) {
		var btn = box.querySelector( '.embed__play' );
		if ( ! btn ) { return; }
		btn.addEventListener( 'click', function () {
			var f = document.createElement( 'iframe' );
			f.src = box.getAttribute( 'data-embed' );
			f.title = box.getAttribute( 'data-embed-title' ) || 'Video';
			f.allow = 'autoplay; fullscreen; picture-in-picture; xr-spatial-tracking; gyroscope; accelerometer';
			f.setAttribute( 'allowfullscreen', '' );
			f.referrerPolicy = 'strict-origin-when-cross-origin';
			box.innerHTML = '';
			box.appendChild( f );
			f.focus();
		} );
	} );

	function copy( text ) {
		if ( navigator.clipboard && window.isSecureContext ) {
			return navigator.clipboard.writeText( text ).then( function () { say( 'Link copied.' ); } );
		}
		var t = document.createElement( 'textarea' );
		t.value = text; t.setAttribute( 'readonly', '' ); t.style.position = 'fixed'; t.style.opacity = '0';
		document.body.appendChild( t ); t.select();
		try { document.execCommand( 'copy' ); say( 'Link copied.' ); } catch ( e ) { say( 'Copy this link: ' + text ); }
		document.body.removeChild( t );
	}

	Array.prototype.forEach.call( document.querySelectorAll( '[data-ot-copy]' ), function ( b ) {
		b.addEventListener( 'click', function () { copy( b.getAttribute( 'data-ot-copy' ) ); } );
	} );

	// Share: the phone's own share sheet where there is one, else copy the link.
	Array.prototype.forEach.call( document.querySelectorAll( '[data-ot-share]' ), function ( b ) {
		b.addEventListener( 'click', function () {
			var data = { title: b.getAttribute( 'data-title' ), url: b.getAttribute( 'data-url' ) };
			if ( navigator.share ) {
				navigator.share( data ).catch( function () {} );
			} else {
				copy( data.url );
			}
		} );
	} );

	// Payment estimate: principal and interest by the standard amortization formula, plus the
	// monthly share of taxes, fee and insurance. No rate, no estimate.
	Array.prototype.forEach.call( document.querySelectorAll( '[data-ot-estimator]' ), function ( box ) {
		var $ = function ( id ) { return box.querySelector( '#' + id ); };
		var num = function ( id ) { var v = parseFloat( $( id ).value ); return isFinite( v ) && v > 0 ? v : 0; };
		var money = function ( n ) { return '$' + Math.round( n ).toLocaleString( 'en-US' ); };
		function run() {
			var price = num( 'est-price' ), down = Math.min( num( 'est-down' ), 100 ), rate = num( 'est-rate' ), years = parseInt( $( 'est-term' ).value, 10 ) || 30;
			var total = box.querySelector( '[data-est-total]' ), parts = box.querySelector( '[data-est-parts]' );
			if ( ! price || ! rate ) {
				total.textContent = 'Enter a rate'; parts.textContent = ''; return;
			}
			var loan = price * ( 1 - down / 100 ), r = rate / 100 / 12, n = years * 12;
			var pi = loan * r / ( 1 - Math.pow( 1 + r, -n ) );
			var tax = num( 'est-tax' ) / 12, hoa = num( 'est-hoa' ), ins = num( 'est-ins' );
			total.textContent = money( pi + tax + hoa + ins );
			var bits = [ money( pi ) + ' principal and interest on a ' + money( loan ) + ' loan' ];
			if ( tax ) { bits.push( money( tax ) + ' taxes' ); }
			if ( hoa ) { bits.push( money( hoa ) + ' fee' ); }
			if ( ins ) { bits.push( money( ins ) + ' insurance' ); }
			parts.textContent = bits.join( ' · ' ) + '.';
		}
		box.addEventListener( 'input', run );
		box.addEventListener( 'change', run );
		run();
	} );
} )();
