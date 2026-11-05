<?php
/**
 * Listing media and the tools that turn a visit into an inquiry.
 *
 * - Video (YouTube, Vimeo) and 3D tours (Matterport, iGuide, Kuula, CloudPano) from a pasted
 *   link. Only these hosts are accepted, and nothing loads from them until the visitor clicks:
 *   the page shows the main photo with a play button (a "facade"), so the listing stays fast
 *   for Core Web Vitals and no third party sees a visit that never plays the video.
 * - Facebook, Instagram and TikTok posts about the listing, as links. Their embeds need Meta's
 *   app token and load several hundred kilobytes of tracking script, so a link is the fast,
 *   private choice; the post itself is one tap away.
 * - Floor plan (image or PDF) and a brochure (PDF) from the media library.
 * - Share (the phone's share sheet, copy link, Facebook, email, text), add-to-calendar for the
 *   open house (.ics and Google Calendar), a payment estimate and a request-a-showing button
 *   that stays at the bottom of a phone screen.
 *
 * Nothing is filled in automatically: each piece appears only when the agent enters it.
 */

defined( 'ABSPATH' ) || exit;

/** Hosts a 3D tour may come from (the iframe source must be one of these). */
const OT_TOUR_HOSTS = array( 'my.matterport.com', 'matterport.com', 'youriguide.com', 'kuula.co', 'app.cloudpano.com', 'cloudpano.com' );

/**
 * What a pasted video or tour link is: [ type, embed URL, thumbnail URL or '', label ], or null
 * when the link is not from an accepted host.
 */
function ot_media_embed( $url ) {
	$url = trim( (string) $url );
	// A format check only: wp_http_validate_url() resolves the host, which a locked-down server may not.
	if ( ! $url || ! filter_var( $url, FILTER_VALIDATE_URL ) || ! in_array( strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) ), array( 'https', 'http' ), true ) ) {
		return null;
	}
	$p    = wp_parse_url( $url );
	$host = strtolower( preg_replace( '/^www\./', '', $p['host'] ?? '' ) );
	$path = $p['path'] ?? '';
	parse_str( $p['query'] ?? '', $q );

	if ( in_array( $host, array( 'youtube.com', 'm.youtube.com', 'youtu.be', 'youtube-nocookie.com' ), true ) ) {
		$id = '';
		if ( 'youtu.be' === $host ) {
			$id = trim( $path, '/' );
		} elseif ( ! empty( $q['v'] ) ) {
			$id = $q['v'];
		} elseif ( preg_match( '#^/(?:embed|shorts|live)/([^/?]+)#', $path, $m ) ) {
			$id = $m[1];
		}
		if ( ! preg_match( '/^[A-Za-z0-9_-]{6,20}$/', $id ) ) {
			return null;
		}
		return array( 'youtube', 'https://www.youtube-nocookie.com/embed/' . $id . '?autoplay=1&rel=0&modestbranding=1', 'https://i.ytimg.com/vi/' . $id . '/hqdefault.jpg', 'Play the video tour' );
	}
	if ( in_array( $host, array( 'vimeo.com', 'player.vimeo.com' ), true ) && preg_match( '#/(?:video/)?(\d{6,12})#', $path, $m ) ) {
		return array( 'vimeo', 'https://player.vimeo.com/video/' . $m[1] . '?autoplay=1&dnt=1', '', 'Play the video tour' );
	}
	if ( in_array( $host, OT_TOUR_HOSTS, true ) ) {
		if ( str_contains( $host, 'matterport' ) ) {
			if ( empty( $q['m'] ) || ! preg_match( '/^[A-Za-z0-9]{6,20}$/', $q['m'] ) ) {
				return null;
			}
			return array( 'tour', 'https://my.matterport.com/show/?m=' . $q['m'] . '&play=1&qs=1', '', 'Explore the 3D tour' );
		}
		return array( 'tour', esc_url_raw( 'https://' . $host . $path . ( ! empty( $p['query'] ) ? '?' . $p['query'] : '' ) ), '', 'Explore the 3D tour' );
	}
	return null;
}

/** A click-to-load player: the poster and a button; listing.js swaps in the iframe on click. */
function ot_embed_facade( $info, $poster, $title ) {
	$label = $info[3];
	$icon  = 'tour' === $info[0] ? 'cube' : 'play';
	return '<div class="embed" data-embed="' . esc_url( $info[1] ) . '" data-embed-title="' . esc_attr( $title ) . '">'
		. '<button class="embed__play" type="button" aria-label="' . esc_attr( $label . ': ' . $title ) . '">' . $poster
		. '<span class="embed__label">' . ot_icon( $icon ) . esc_html( $label ) . '</span></button></div>';
}

/**
 * The poster behind a play button: the listing's main photo (served from this site, so the page
 * contacts no video host before a click), else the video's own thumbnail, else a drawing.
 */
function ot_embed_poster( $info, $l ) {
	if ( $l['photo_id'] ) {
		return wp_get_attachment_image( $l['photo_id'], 'large', false, array( 'alt' => '', 'loading' => 'lazy' ) );
	}
	if ( $info[2] ) {
		return '<img src="' . esc_url( $info[2] ) . '" alt="" loading="lazy" decoding="async">';
	}
	return '<div class="demo-illu">' . ot_art( 'colonial' ) . '</div>';
}

/** Facebook, Instagram and TikTok posts about the listing, as plain links. */
function ot_listing_social_links( $l ) {
	$out = '';
	foreach ( array( 'facebook' => array( 'facebook.com', 'See it on Facebook' ), 'instagram_post' => array( 'instagram.com', 'See it on Instagram' ), 'tiktok' => array( 'tiktok.com', 'See it on TikTok' ) ) as $k => $d ) {
		$u    = (string) ( $l[ $k ] ?? '' );
		$host = strtolower( (string) wp_parse_url( $u, PHP_URL_HOST ) );
		if ( $u && ( $host === $d[0] || str_ends_with( $host, '.' . $d[0] ) || ( 'facebook.com' === $d[0] && 'fb.watch' === $host ) ) ) {
			$out .= '<a class="btn btn--secondary btn--small" href="' . esc_url( $u ) . '" target="_blank" rel="noopener">' . esc_html( $d[1] ) . ot_icon( 'external' ) . '</a>';
		}
	}
	return $out ? '<div class="stack" style="--stack:var(--space-3)"><p class="label">On social</p><div class="cluster">' . $out . '</div></div>' : '';
}

/** A media-library file as a download link: "Floor plan (PDF, 1.2 MB)". */
function ot_file_link( $id, $label ) {
	$id  = (int) $id;
	$url = $id ? wp_get_attachment_url( $id ) : '';
	if ( ! $url ) {
		return '';
	}
	$file = get_attached_file( $id );
	$size = $file && is_readable( $file ) ? size_format( filesize( $file ), 1 ) : '';
	$ext  = strtoupper( (string) pathinfo( $url, PATHINFO_EXTENSION ) );
	return '<a class="btn btn--secondary btn--small" href="' . esc_url( $url ) . '" download>' . ot_icon( 'download' ) . esc_html( $label ) . ( $ext || $size ? ' <span class="caption">(' . esc_html( trim( $ext . ( $size ? ', ' . $size : '' ), ', ' ) ) . ')</span>' : '' ) . '</a>';
}

/** Video, 3D tour, floor plan, brochure and social links, or '' when the listing has none. */
function ot_listing_media( $l ) {
	$title  = $l['address'] . ( $l['town'] ? ', ' . $l['town'] : '' );
	$tiles  = '';
	foreach ( array( 'video' => 'Video tour', 'tour' => '3D tour' ) as $k => $h ) {
		$info = ot_media_embed( $l[ $k ] ?? '' );
		if ( $info ) {
			$tiles .= '<figure class="stack" style="--stack:var(--space-3)">' . ot_embed_facade( $info, ot_embed_poster( $info, $l ), $h . ' of ' . $title ) . '<figcaption class="caption">' . esc_html( $h ) . ( 'tour' === $info[0] ? ': walk through every room, at your own pace.' : '' ) . '</figcaption></figure>';
		}
	}
	$plan = '';
	if ( ! empty( $l['floorplan'] ) && wp_attachment_is_image( (int) $l['floorplan'] ) ) {
		$full = wp_get_attachment_url( (int) $l['floorplan'] );
		$plan = '<figure class="stack" style="--stack:var(--space-3)"><a href="' . esc_url( $full ) . '">' . wp_get_attachment_image( (int) $l['floorplan'], 'large', false, array( 'alt' => 'Floor plan of ' . $title, 'loading' => 'lazy', 'class' => 'floor-plan' ) ) . '</a><figcaption class="caption">Floor plan. Select it to open full size.</figcaption></figure>';
	}
	$docs = ( ! empty( $l['floorplan'] ) && ! wp_attachment_is_image( (int) $l['floorplan'] ) ? ot_file_link( $l['floorplan'], 'Floor plan' ) : '' ) . ot_file_link( $l['brochure'] ?? 0, 'Property brochure' );
	$docs = $docs ? '<div class="stack" style="--stack:var(--space-3)"><p class="label">Documents</p><div class="cluster">' . $docs . '</div></div>' : '';
	$soc  = ot_listing_social_links( $l );
	if ( ! $tiles && ! $plan && ! $docs && ! $soc ) {
		return '';
	}
	return '<div class="stack" id="tour" style="--stack:var(--space-5)"><h2 class="h3">Tour the home</h2>'
		. ( $tiles || $plan ? '<div class="media-grid">' . $tiles . $plan . '</div>' : '' )
		. ( $docs || $soc ? '<div class="cluster" style="gap:var(--space-6);align-items:start">' . $docs . $soc . '</div>' : '' ) . '</div>';
}

/* --------------------------------------------------------------------- open house */

/** The open house as [start, end] DateTimeImmutable in Massachusetts time, or null. */
function ot_open_house_times( $l ) {
	if ( empty( $l['open_start'] ) ) {
		return null;
	}
	$tz    = new DateTimeZone( 'America/New_York' );
	$start = DateTimeImmutable::createFromFormat( 'Y-m-d\TH:i', $l['open_start'], $tz );
	if ( ! $start ) {
		return null;
	}
	$end = ! empty( $l['open_end'] ) ? DateTimeImmutable::createFromFormat( 'Y-m-d\TH:i', $l['open_end'], $tz ) : null;
	$end = $end && $end > $start ? $end : $start->modify( '+2 hours' );
	return array( $start, $end );
}

/** "Sunday, November 9, 1 to 3 pm", from the typed text or the start and end times; '' when past. */
function ot_open_house_text( $l ) {
	if ( 'sold' === $l['status'] ) {
		return '';
	}
	$t = ot_open_house_times( $l );
	if ( $t && $t[1] < new DateTimeImmutable( 'now', new DateTimeZone( 'America/New_York' ) ) ) {
		return ''; // over
	}
	if ( ! empty( $l['open_house'] ) ) {
		return (string) $l['open_house'];
	}
	if ( ! $t ) {
		return '';
	}
	$h = fn( $d ) => '00' === $d->format( 'i' ) ? $d->format( 'g' ) : $d->format( 'g:i' );
	$same = $t[0]->format( 'a' ) === $t[1]->format( 'a' );
	return $t[0]->format( 'l, F j, ' ) . $h( $t[0] ) . ( $same ? '' : ' ' . $t[0]->format( 'a' ) ) . ' to ' . $h( $t[1] ) . ' ' . $t[1]->format( 'a' );
}

/** /?ot_ics=<listing id> sends the open house as a calendar file. */
add_action( 'init', function () {
	if ( empty( $_GET['ot_ics'] ) || is_admin() ) {
		return;
	}
	$l = ot_listing( (int) $_GET['ot_ics'] );
	$t = $l && 'publish' === get_post_status( $l['id'] ) ? ot_open_house_times( $l ) : null;
	if ( ! $t ) {
		return;
	}
	$esc  = fn( $s ) => addcslashes( str_replace( array( "\r\n", "\n" ), ' ', $s ), ',;\\' );
	$addr = $l['address'] . ( $l['town'] ? ', ' . $l['town'] . ', MA' : '' ) . ( $l['zip'] ? ' ' . $l['zip'] : '' );
	$ics  = implode( "\r\n", array(
		'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//The Oberdorfer Team//Open house//EN', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH',
		'BEGIN:VEVENT',
		'UID:open-house-' . $l['id'] . '-' . $t[0]->format( 'Ymd\THi' ) . '@oberdorferteam.com',
		'DTSTAMP:' . gmdate( 'Ymd\THis\Z' ),
		'DTSTART;TZID=America/New_York:' . $t[0]->format( 'Ymd\THis' ),
		'DTEND;TZID=America/New_York:' . $t[1]->format( 'Ymd\THis' ),
		'SUMMARY:' . $esc( 'Open house: ' . $l['address'] ),
		'LOCATION:' . $esc( $addr ),
		'DESCRIPTION:' . $esc( 'Hosted by The Oberdorfer Team, REWAP Brokerage LLC. ' . OT_PHONE . '. ' . ot_prod_url( $l['url'] ) ),
		'URL:' . ot_prod_url( $l['url'] ),
		'END:VEVENT', 'END:VCALENDAR', '',
	) );
	header( 'Content-Type: text/calendar; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="open-house-' . sanitize_file_name( $l['slug'] ) . '.ics"' );
	header( 'X-Robots-Tag: noindex' );
	echo $ics; // phpcs:ignore WordPress.Security.EscapeOutput
	exit;
}, 2 );

/* --------------------------------------------------------------------- actions row */

/** Request a showing, add the open house to a calendar, and share. */
function ot_listing_actions( $l ) {
	$title = $l['address'] . ( $l['town'] ? ', ' . $l['town'] . ', MA' : '' );
	$url   = $l['url'];
	$open  = ot_open_house_text( $l );
	$cal   = '';
	$t     = ot_open_house_times( $l );
	if ( $open && $t ) {
		$g   = 'https://calendar.google.com/calendar/render?' . http_build_query( array(
			'action'   => 'TEMPLATE',
			'text'     => 'Open house: ' . $l['address'],
			'dates'    => $t[0]->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Ymd\THis\Z' ) . '/' . $t[1]->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Ymd\THis\Z' ),
			'location' => $title,
			'details'  => 'Hosted by The Oberdorfer Team. ' . $url,
		) );
		$cal = '<div class="cluster" style="gap:var(--space-3)"><a class="btn btn--secondary btn--small" href="' . esc_url( add_query_arg( 'ot_ics', $l['id'], home_url( '/' ) ) ) . '" rel="nofollow">' . ot_icon( 'calendar' ) . 'Add the open house to my calendar</a><a class="link-arrow" href="' . esc_url( $g ) . '" target="_blank" rel="noopener nofollow">Google Calendar ' . ot_arrow() . '</a></div>';
	}
	$text  = rawurlencode( $title . ' — ' . $url );
	$share = '<div class="share-row" aria-label="Share this home">'
		. '<button class="btn btn--secondary btn--small" type="button" data-ot-share data-title="' . esc_attr( $title ) . '" data-url="' . esc_url( $url ) . '">' . ot_icon( 'share' ) . 'Share</button>'
		. '<button class="btn btn--secondary btn--small" type="button" data-ot-copy="' . esc_url( $url ) . '">' . ot_icon( 'copy' ) . 'Copy link</button>'
		. '<a class="link-arrow" href="' . esc_url( 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $url ) ) . '" target="_blank" rel="noopener nofollow">Facebook ' . ot_arrow() . '</a>'
		. '<a class="link-arrow" href="mailto:?subject=' . rawurlencode( $title ) . '&amp;body=' . $text . '">Email ' . ot_arrow() . '</a>'
		. '<a class="link-arrow" href="sms:?&amp;body=' . $text . '">Text ' . ot_arrow() . '</a>'
		. '</div>';
	$cta = 'sold' === $l['status'] ? '' : '<a class="btn" href="#request-showing">Request a showing</a>';
	$has_tour = ot_media_embed( $l['tour'] ?? '' ) || ot_media_embed( $l['video'] ?? '' );
	return '<div class="listing-actions">' . '<div class="cluster" style="gap:var(--space-3)">' . $cta . ( $has_tour ? '<a class="btn btn--secondary" href="#tour">' . ot_icon( 'play' ) . 'Watch the tour</a>' : '' ) . '</div>' . $cal . $share . '</div>';
}

/** On phones, a bar at the foot of the screen keeps "Request a showing" and "Call" in reach. */
function ot_listing_cta_bar( $l ) {
	if ( 'sold' === $l['status'] ) {
		return '';
	}
	$agent = $l['agent'] ? get_page_by_path( $l['agent'], OBJECT, 'ot_agent' ) : null;
	$phone = $agent ? ( get_post_meta( $agent->ID, 'ot_phone', true ) ?: OT_PHONE ) : OT_PHONE;
	// The design system's phone action bar (components.css .action-bar), as on agent profiles.
	return '<nav class="action-bar" aria-label="Contact about this home"><a class="btn btn--gold" href="' . esc_attr( ot_tel_href( $phone ) ) . '">' . ot_icon( 'phone' ) . 'Call</a><a class="btn" href="#request-showing" style="--btn-bg:var(--ot-ivory);--btn-fg:var(--ot-navy);--btn-border:var(--ot-ivory)">Request a showing</a></nav>';
}

/* --------------------------------------------------------------------- payment estimate */

/**
 * A monthly payment estimate the visitor adjusts. Price, taxes and condo fee come from the
 * listing when entered; the down payment starts at 20 percent; the interest rate is left for
 * the visitor to enter (the site never states a rate). listing.js does the arithmetic.
 */
function ot_listing_estimator( $l ) {
	if ( ! $l['price'] || 'sold' === $l['status'] ) {
		return '';
	}
	$f = function ( $id, $label, $value, $attrs, $hint = '' ) {
		return '<div class="field"><label class="field__label" for="' . $id . '">' . $label . '</label><input class="input tabular" id="' . $id . '" name="' . $id . '" type="number" inputmode="decimal" value="' . esc_attr( $value ) . '" ' . $attrs . '>' . ( $hint ? '<span class="field__hint">' . $hint . '</span>' : '' ) . '</div>';
	};
	$fields = $f( 'est-price', 'Price ($)', (int) $l['price'], 'min="0" step="1000"' )
		. $f( 'est-down', 'Down payment (%)', 20, 'min="0" max="100" step="0.5"' )
		. $f( 'est-rate', 'Interest rate (%)', '', 'min="0" max="20" step="0.01" placeholder="Your rate"', 'From your lender.' )
		. '<div class="field"><label class="field__label" for="est-term">Loan term</label><select class="select" id="est-term" name="est-term"><option value="30">30 years</option><option value="15">15 years</option></select></div>'
		. $f( 'est-tax', 'Property tax ($ a year)', $l['taxes'] ?: '', 'min="0" step="100"', $l['taxes'] ? 'From the listing.' : 'Optional.' )
		. $f( 'est-hoa', 'Condo or HOA fee ($ a month)', $l['hoa'] ?: '', 'min="0" step="10"', $l['hoa'] ? 'From the listing.' : 'Optional.' )
		. $f( 'est-ins', 'Home insurance ($ a month)', '', 'min="0" step="10"', 'Optional.' );
	return '<div class="estimator panel" id="estimate" data-ot-estimator>
  <h2 class="h3">Estimate a monthly payment</h2>
  <div class="estimator__fields">' . $fields . '</div>
  <div class="estimator__result" aria-live="polite"><p class="label">Estimated monthly payment</p><p class="estimator__total display-m tabular" data-est-total>Enter a rate</p><p class="caption" data-est-parts></p></div>
  <p class="form-note">An estimate for planning only, not a loan offer. Ask a lender for your rate and terms; taxes and insurance vary.</p>
</div>';
}

/* --------------------------------------------------------------------- assets */

add_action( 'wp_enqueue_scripts', function () {
	if ( is_singular( array( 'ot_listing', 'ot_agent' ) ) ) {
		wp_enqueue_script( 'ot-core-listing', OT_CORE_URL . '/assets/listing.js', array(), OT_CORE_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}
} );

