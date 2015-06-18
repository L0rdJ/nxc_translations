var NXC = NXC || {};

NXC.FrontEndTranslation = {

	install: function() {
		jQuery.each( jQuery( '.nxc-translations-icon' ), function( index, el ) {
			var el = jQuery( this );
			el.data( 'url', el.attr( 'alt' ) );
			el.attr( 'alt', '' );
		} );

		var expr = new RegExp( /nxc-translations-message-id-([0-9a-f]{32})/ );
		jQuery( '.nxc-translations-translatable' ).click( function( e ) {
			e.preventDefault();
			var el = jQuery( this );
			var id = el.attr( 'class' ).match( expr );
			id = id[1];

			jQuery.ajax( {
				url: el.data( 'url' ).replace( 'TRANSLATION_HASH', id ),
				success: function( data ) {
					var wrapper = jQuery( '<div></div>' );
					wrapper.html( data );
					wrapper.dialog( {
						'width': 650,
						'maxWidth': 800,
						'maxHeight': 600,
						'close': function( event, ui ) {
							wrapper.parent().remove();
						}
					} );
					NXC.FrontEndTranslation.installEditMessageForm( wrapper );
				}
			} );
		} );
	},

	installEditMessageForm: function( container ) {
		jQuery( 'form.nxc-translations-edit-message', container ).submit( function( e ) {
			e.preventDefault();

			var el = jQuery( this );
			jQuery.post(
				el.attr( 'action' ),
				el.serialize() + '&Update=Update',
				function( data ) {
					var parent = el.parent();
					parent.html( data );
					NXC.FrontEndTranslation.installEditMessageForm( parent );
				}
			);
		} );
	}
}