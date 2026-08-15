/* Hadashot Mercaz – Professional Updates: admin metabox interactions */
jQuery( function ( $ ) {
	'use strict';

	$( '.hm-media-block' ).each( function () {
		var $block = $( this );

		$block.find( 'input[type="radio"]' ).on( 'change', function () {
			var value = $( this ).val();
			$block.find( '.hm-media-upload, .hm-media-embed' ).hide();
			if ( 'upload' === value ) {
				$block.find( '.hm-media-upload' ).show();
			} else if ( 'embed' === value ) {
				$block.find( '.hm-media-embed' ).show();
			}
		} );
	} );

	var hmMediaFrame;

	$( '.hm-select-media' ).on( 'click', function ( e ) {
		e.preventDefault();

		var $button = $( this );
		var target = $button.data( 'target' );
		var $field = $button.closest( '.hm-media-upload' );

		hmMediaFrame = wp.media( {
			title: 'audio' === target ? hmMediaL10n.chooseAudio : hmMediaL10n.chooseVideo,
			library: { type: 'audio' === target ? 'audio' : 'video' },
			multiple: false,
			button: { text: hmMediaL10n.select },
		} );

		hmMediaFrame.on( 'select', function () {
			var attachment = hmMediaFrame.state().get( 'selection' ).first().toJSON();
			$field.find( '.hm-attachment-id' ).val( attachment.id );
			$field.find( '.hm-filename' ).text( attachment.filename || attachment.title || '' );
		} );

		hmMediaFrame.open();
	} );
} );
