/* Hadashot Mercaz – Update Editor interactions */
jQuery( function ( $ ) {
	'use strict';

	var $form = $( '#hm-ed-form' );
	if ( ! $form.length ) {
		return;
	}

	/* ---------------------------------------------------------------- */
	/* Content type switch                                               */
	/* ---------------------------------------------------------------- */

	var $switch    = $form.find( '.hm-ed-type-switch' );
	var $indicator = $switch.find( '.hm-ed-type-indicator' );
	var $options   = $switch.find( '.hm-ed-type-option' );

	function positionIndicator() {
		var $checked = $switch.find( 'input[name="hm_update_type"]:checked' );
		var $label   = $checked.closest( '.hm-ed-type-option' );
		if ( ! $label.length ) {
			return;
		}
		$indicator.css( {
			width: $label[ 0 ].offsetWidth + 'px',
			left: $label[ 0 ].offsetLeft + 'px',
		} );
	}

	function syncType() {
		var type = $switch.find( 'input[name="hm_update_type"]:checked' ).val();
		$options.each( function () {
			$( this ).toggleClass( 'is-active', $( this ).data( 'type' ) === type );
		} );
		$form.find( '.hm-ed-panel' ).each( function () {
			var isActive = $( this ).data( 'panel' ) === type;
			$( this ).prop( 'hidden', ! isActive );
			$( this ).find( 'input, textarea' ).prop( 'disabled', ! isActive );
		} );
		$( '.hm-ed-featured-hint' ).prop( 'hidden', 'image' !== type );
		positionIndicator();
	}

	$switch.on( 'change', 'input[name="hm_update_type"]', syncType );
	$( window ).on( 'resize', positionIndicator );
	syncType();

	/* ---------------------------------------------------------------- */
	/* Media source toggles (upload vs. embed), per panel                */
	/* ---------------------------------------------------------------- */

	$form.find( '.hm-ed-source-toggle' ).each( function () {
		var $toggle = $( this );
		var $panel  = $toggle.closest( '.hm-ed-panel' );

		function sync() {
			var source = $toggle.find( 'input:checked' ).val();
			$toggle.find( '.hm-ed-chip' ).each( function () {
				$( this ).toggleClass( 'is-active', $( this ).find( 'input' ).val() === source );
			} );
			$panel.find( '.hm-ed-source-pane' ).each( function () {
				$( this ).prop( 'hidden', $( this ).data( 'source-pane' ) !== source );
			} );
		}

		$toggle.on( 'change', 'input', sync );
		sync();
	} );

	/* ---------------------------------------------------------------- */
	/* Word count                                                        */
	/* ---------------------------------------------------------------- */

	var $textarea  = $form.find( '.hm-ed-textarea' );
	var $wordCount = $( '#hm-ed-word-count' );

	function updateWordCount() {
		var text = $textarea.val().trim();
		var count = text ? text.split( /\s+/ ).length : 0;
		$wordCount.text( count );
	}

	$textarea.on( 'input', updateWordCount );
	updateWordCount();

	/* ---------------------------------------------------------------- */
	/* Status buttons                                                    */
	/* ---------------------------------------------------------------- */

	$form.find( '[data-set-status]' ).on( 'click', function () {
		$( '#hm-ed-status-field' ).val( $( this ).data( 'set-status' ) );
	} );

	/* ---------------------------------------------------------------- */
	/* Media pickers (wp.media)                                          */
	/* ---------------------------------------------------------------- */

	var mediaFrame;

	$form.find( '.hm-ed-choose-media' ).on( 'click', function ( e ) {
		e.preventDefault();

		var $button = $( this );
		var media   = $button.data( 'media' );
		var $pane   = $button.closest( '.hm-ed-source-pane' );

		mediaFrame = wp.media( {
			title: 'audio' === media ? hmEditorL10n.chooseAudio : hmEditorL10n.chooseVideo,
			library: { type: media },
			multiple: false,
			button: { text: hmEditorL10n.select },
		} );

		mediaFrame.on( 'select', function () {
			var attachment = mediaFrame.state().get( 'selection' ).first().toJSON();
			$pane.find( '.hm-ed-attachment-id' ).val( attachment.id );
			$pane.find( '.hm-ed-filename' ).text( attachment.filename || attachment.title || '' );

			var $preview = $pane.find( '.hm-ed-media-preview' );
			if ( ! $preview.length ) {
				$preview = $( '<div class="hm-ed-media-preview"></div>' ).appendTo( $pane );
			}
			if ( 'audio' === media ) {
				$preview.html( $( '<audio controls></audio>' ).attr( 'src', attachment.url ) );
			} else {
				$preview.html( $( '<video controls muted></video>' ).attr( 'src', attachment.url ) );
			}
		} );

		mediaFrame.open();
	} );

	var featuredFrame;

	$( '.hm-ed-choose-featured-trigger' ).on( 'click', function ( e ) {
		e.preventDefault();

		featuredFrame = wp.media( {
			title: hmEditorL10n.chooseImage,
			library: { type: 'image' },
			multiple: false,
			button: { text: hmEditorL10n.select },
		} );

		featuredFrame.on( 'select', function () {
			var attachment = featuredFrame.state().get( 'selection' ).first().toJSON();
			var url = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
			$( '#hm-ed-featured-id' ).val( attachment.id );
			$( '.hm-ed-featured-mirror' ).html( $( '<img>' ).attr( 'src', url ) );
			$( '.hm-ed-remove-featured-trigger' ).prop( 'hidden', false );
		} );

		featuredFrame.open();
	} );

	$( '.hm-ed-remove-featured-trigger' ).on( 'click', function ( e ) {
		e.preventDefault();
		$( '#hm-ed-featured-id' ).val( '' );
		$( '.hm-ed-featured-mirror' ).html( '' );
		$( '.hm-ed-remove-featured-trigger' ).prop( 'hidden', true );
	} );
} );
