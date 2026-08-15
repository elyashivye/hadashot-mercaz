/* Hadashot Mercaz – visual slot editor interactions */
jQuery( function ( $ ) {
	'use strict';

	$( '.hm-set-slot[data-slot]' ).each( function () {
		var $slot = $( this );

		function syncType() {
			var type = $slot.find( 'input[name$="_type"]:checked' ).val();
			$slot.find( '.hm-set-image-fields' ).toggle( 'image' === type );
			$slot.find( '.hm-set-video-fields' ).toggle( 'video' === type );
		}

		function syncVideoType() {
			var videoType = $slot.find( '.hm-set-video-type-toggle input:checked' ).val();
			$slot.find( '.hm-set-video-upload' ).toggle( 'upload' === videoType );
			$slot.find( '.hm-set-video-embed' ).toggle( 'embed' === videoType );
			$slot.find( '.hm-set-autoplay' ).toggle( 'upload' === videoType );
			$slot.find( '.hm-set-autoplay-note' ).toggle( 'embed' === videoType );
		}

		$slot.find( '.hm-set-type-toggle input' ).on( 'change', syncType );
		$slot.find( '.hm-set-video-type-toggle input' ).on( 'change', syncVideoType );

		syncType();
		syncVideoType();
	} );

	var hmSlotMediaFrame;

	$( '.hm-select-slot-media' ).on( 'click', function ( e ) {
		e.preventDefault();

		var $button = $( this );
		var side = $button.data( 'side' );
		var mediaType = $button.data( 'media' );
		var $field = $button.closest( '.hm-set-image-fields, .hm-set-video-fields, .hm-set-video-upload' );
		var $preview = $( '.hm-set-slot-preview[data-preview="' + side + '"]' );

		hmSlotMediaFrame = wp.media( {
			title: 'image' === mediaType ? hmSlotL10n.chooseImage : hmSlotL10n.chooseVideo,
			library: { type: mediaType },
			multiple: false,
			button: { text: hmSlotL10n.select },
		} );

		hmSlotMediaFrame.on( 'select', function () {
			var attachment = hmSlotMediaFrame.state().get( 'selection' ).first().toJSON();
			$field.find( '.hm-attachment-id' ).val( attachment.id );

			if ( 'image' === mediaType ) {
				var imgUrl = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
				$preview.html( $( '<img>' ).attr( 'src', imgUrl ) );
			} else {
				var $video = $( '<video controls muted></video>' ).attr( 'src', attachment.url );
				$preview.empty().append( $video );
			}
		} );

		hmSlotMediaFrame.open();
	} );
} );
