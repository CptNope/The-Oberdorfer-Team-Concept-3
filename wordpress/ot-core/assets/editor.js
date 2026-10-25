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

	var defs = wp.blocks.getBlockTypes ? null : null; // server definitions are bootstrapped by core

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
			return el( 'div', useBlockProps(),
				controls.length ? el( InspectorControls, null, el( PanelBody, { title: 'Settings' }, controls ) ) : null,
				el( SSR, { block: name, attributes: props.attributes, urlQueryArgs: postId ? { post_id: postId } : {} } )
			);
		};
	}

	[
		'ot/breadcrumbs', 'ot/page-hero', 'ot/community-section', 'ot/community-index', 'ot/community-cards',
		'ot/market-table', 'ot/town-stats', 'ot/agent-profile', 'ot/agent-cards', 'ot/inquiry-form', 'ot/listings-placeholder'
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
} )( window.wp );
