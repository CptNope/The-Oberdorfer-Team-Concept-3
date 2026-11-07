/**
 * Editor side of the ot-core blocks: each is server-rendered, so the editor shows a live
 * preview with ServerSideRender and saves only the block comment and its attributes.
 * No build step: plain ES5 against the wp.* globals.
 */
( function ( wp ) {
	var el = wp.element.createElement;
	var SSR = wp.serverSideRender;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var TextControl = wp.components.TextControl;

	var Disabled = wp.components.Disabled;

	// Where each section's content is edited, shown above its preview for people new to WordPress.
	var HINTS = {
		'ot/agent-profile': 'Filled from the "Agent details" box ("Meta Boxes" at the bottom of the screen). The biography is the text area on the page.',
		'ot/listing': 'Filled from the "Listing details" box ("Meta Boxes" at the bottom of the screen). Main photo: the sidebar.',
		'ot/team-listings': 'Shows your listings. Add or change them under Listings in the left menu.',
		'ot/community-section': 'Built from this guide\'s research (facts, prices, sources), kept separate so it stays accurate. The headline and summary above are editable.',
		'ot/community-index': 'The list of town guides. Show or hide guides under Communities > Show or hide guides.',
		'ot/community-cards': 'Town guide cards.',
		'ot/market-table': 'Prices from each town guide\'s research.',
		'ot/town-stats': 'Prices from the town guides\' research.',
		'ot/inquiry-form': 'Contact form.',
		'ot/listings-placeholder': 'Listings search (IDX): Settings > Listings (IDX).'
	};

	function editFor( name ) {
		return function ( props ) {
			var type = wp.blocks.getBlockType( name );
			var attrs = ( type && type.attributes ) || {};
			var controls = [];
			Object.keys( attrs ).forEach( function ( key ) {
				var a = attrs[ key ];
				if ( a.enum ) {
					controls.push( el( SelectControl, {
						key: key, label: key, value: props.attributes[ key ],
						options: a.enum.map( function ( v ) { return { label: v, value: v }; } ),
						onChange: function ( v ) { var o = {}; o[ key ] = v; props.setAttributes( o ); }
					} ) );
				} else if ( a.type === 'string' ) {
					controls.push( el( TextControl, {
						key: key, label: key, value: props.attributes[ key ] || '',
						onChange: function ( v ) { var o = {}; o[ key ] = v; props.setAttributes( o ); }
					} ) );
				} else if ( a.type === 'array' ) {
					controls.push( el( TextControl, {
						key: key, label: key + ' (comma separated)', value: ( props.attributes[ key ] || [] ).join( ', ' ),
						onChange: function ( v ) { var o = {}; o[ key ] = v.split( ',' ).map( function ( s ) { return s.trim(); } ).filter( Boolean ); props.setAttributes( o ); }
					} ) );
				}
			} );
			var postId = props.context && props.context.postId;
			var hint = HINTS[ name ];
			// Settings only where the layout itself is edited (Site Editor), never on a guide, profile or listing.
			var mode = wp.blockEditor.useBlockEditingMode ? wp.blockEditor.useBlockEditingMode() : 'default';
			return el( 'div', useBlockProps(),
				controls.length && mode === 'default' ? el( InspectorControls, null, el( PanelBody, { title: 'Settings' }, controls ) ) : null,
				// The note on where this section is edited shows while the section is selected.
				hint && props.isSelected ? el( 'p', { className: 'ot-editor-hint', style: { margin: '0 0 4px', padding: '6px 10px', font: '13px/1.45 -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif', background: '#f0f6fc', color: '#1e3a5f', borderLeft: '3px solid #2271b1' } }, hint ) : null,
				el( Disabled, null, el( SSR, { block: name, attributes: props.attributes, urlQueryArgs: postId ? { post_id: postId } : {},
					// In a template (Site Editor) there is no guide, profile or listing to draw from: name the section instead.
					EmptyResponsePlaceholder: function () {
						// On a guide, profile or listing an empty section simply has nothing to show yet
						// (no intro video, no listings): show nothing, or the hint while it is selected.
						if ( postId ) {
							return hint && props.isSelected ? el( 'p', { style: { margin: 0, padding: '10px 12px', font: '13px/1.4 -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif', color: '#3c434a', border: '1px dashed #8c8f94' } }, 'Nothing to show yet. ' + hint ) : null;
						}
						var t = wp.blocks.getBlockType( name );
						var label = ( t && t.title ) || name;
						var part = props.attributes.section || props.attributes.part || '';
						return el( 'div', { style: { padding: '14px 16px', border: '1px dashed #8c8f94', font: '13px/1.4 -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif', color: '#3c434a' } }, label + ( part ? ': ' + part : '' ) + ' (filled from each post)' );
					} } ) )
			);
		};
	}

	[
		'ot/breadcrumbs', 'ot/page-hero', 'ot/community-section', 'ot/community-index', 'ot/community-cards',
		'ot/market-table', 'ot/town-stats', 'ot/agent-profile', 'ot/agent-cards', 'ot/inquiry-form', 'ot/listings-placeholder', 'ot/team-listings', 'ot/listing'
	].forEach( function ( name ) {
		if ( wp.blocks.getBlockType( name ) && wp.blocks.getBlockType( name ).edit ) {
			return;
		}
		wp.blocks.registerBlockType( name, {
			apiVersion: 3,
			icon: 'admin-home',
			edit: editFor( name ),
			save: function () { return null; }
		} );
	} );

	/*
	 * Full-page editing for people new to WordPress. Guides, profiles and listings open showing the
	 * whole page (template-locked mode), where WordPress only lets the title and body be edited.
	 * Here, blocks bound to this post's fields (Block Bindings, core/post-meta) are unlocked so the
	 * headline, summary or role can be clicked and typed into, and the ot/* sections become
	 * selectable so each can show where it is edited. A short notice says how the page works.
	 */
	var NOTICES = {
		ot_agent: 'Click the role line, the summary or the biography on the page and type to change them. Phone, title, specialties and headshot are in "Agent details": open "Meta Boxes" at the bottom of the screen. Sections shared by both profiles (how we can help, client stories, contact) are edited once in Appearance > Editor > Templates > Agent profile. Then click Save.',
		ot_community: 'Click the headline or the summary on the page and type to change them. The research sections below (facts, prices, sources) are kept separate so they stay accurate; click one to see where it comes from. Then click Save.',
		ot_listing: 'Type the address as the title and write the description on the page. Price, status, rooms and more photos are in "Listing details": open "Meta Boxes" at the bottom of the screen; the main photo is "Featured image" in the sidebar. Then click Publish or Save.',
		ot_office: 'Click the text on the page to change it, then click Save.',
		page: 'Click any text on the page and type to change it; click a photo to replace it. The header and footer are shared by every page: change them in Appearance > Editor > Patterns. Then click Save.'
	};
	var noticeShown = false;
	var busy = false;
	function hasMetaBinding( block ) {
		var b = block.attributes && block.attributes.metadata && block.attributes.metadata.bindings;
		if ( ! b ) { return false; }
		return Object.keys( b ).some( function ( k ) { return b[ k ] && b[ k ].source === 'core/post-meta'; } );
	}
	function unlock() {
		if ( busy ) { return; }
		var ed = wp.data.select( 'core/editor' );
		if ( ! ed || ! ed.getRenderingMode || ed.getRenderingMode() !== 'template-locked' ) { return; }
		var type = ed.getCurrentPostType();
		if ( ! NOTICES[ type ] ) { return; }
		var be = wp.data.select( 'core/block-editor' );
		var dispatch = wp.data.dispatch( 'core/block-editor' );
		busy = true;
		( function walk( ids ) {
			ids.forEach( function ( id ) {
				var block = be.getBlock( id );
				if ( block && ( hasMetaBinding( block ) || block.name.indexOf( 'ot/' ) === 0 ) && be.getBlockEditingMode( id ) === 'disabled' ) {
					dispatch.setBlockEditingMode( id, 'contentOnly' );
				}
				walk( be.getBlockOrder( id ) );
			} );
		} )( be.getBlockOrder() );
		busy = false;
		showFirstName( type );
		if ( ! noticeShown ) {
			noticeShown = true;
			wp.data.dispatch( 'core/notices' ).createInfoNotice( NOTICES[ type ], { id: 'ot-how-to-edit', isDismissible: true } );
		}
	}
	/*
	 * Text shared by both profiles is written with {first name} in the template. On a profile, the
	 * editor shows the agent's name there instead, as the site does. Display only: those blocks
	 * belong to the template, are not editable here and are never saved from this screen.
	 */
	var watching = null;
	function showFirstName( type ) {
		if ( 'ot_agent' !== type ) { return; }
		var frame = document.querySelector( 'iframe[name="editor-canvas"]' );
		var doc = frame && frame.contentDocument;
		if ( ! doc || ! doc.body || watching === doc ) { return; }
		watching = doc;
		var title = wp.data.select( 'core/editor' ).getEditedPostAttribute( 'title' ) || '';
		var given = /&| and /.test( title ) ? title.replace( / Oberdorfer$/, '' ) : title.split( ' ' )[ 0 ];
		var timer = null;
		function swap() {
			var walker = doc.createTreeWalker( doc.body, NodeFilter.SHOW_TEXT ), n;
			while ( ( n = walker.nextNode() ) ) {
				// Only in the template's locked blocks; never in text the person can edit here.
				if ( n.nodeValue.indexOf( '{first name}' ) !== -1 && n.parentElement && n.parentElement.closest( '.is-editing-disabled' ) ) {
					n.nodeValue = n.nodeValue.split( '{first name}' ).join( given );
				}
			}
		}
		new MutationObserver( function () { clearTimeout( timer ); timer = setTimeout( swap, 100 ); } ).observe( doc.body, { childList: true, subtree: true, characterData: true } );
		swap();
	}
	if ( wp.data && wp.data.subscribe ) {
		wp.data.subscribe( unlock, 'core/block-editor' );
	}
} )( window.wp );
