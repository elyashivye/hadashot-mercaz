/* Hadashot Mercaz – Professional Updates: frontend interactions */
( function () {
	'use strict';

	function initPopups( root ) {
		var overlay = root.querySelector( '.hm-popup-overlay' );
		if ( ! overlay ) {
			return;
		}
		var body = overlay.querySelector( '.hm-popup-body' );
		var closeBtn = overlay.querySelector( '.hm-popup-close' );

		function open( id ) {
			var tmpl = root.querySelector( '.hm-popup-item[data-hm-update-id="' + id + '"]' );
			if ( ! tmpl || ! body ) {
				return;
			}
			body.innerHTML = tmpl.innerHTML;
			overlay.classList.add( 'is-open' );
			document.body.classList.add( 'hm-popup-locked' );
			if ( closeBtn ) {
				closeBtn.focus();
			}
		}

		function close() {
			overlay.classList.remove( 'is-open' );
			document.body.classList.remove( 'hm-popup-locked' );
		}

		root.addEventListener( 'click', function ( e ) {
			var trigger = e.target.closest( '[data-hm-update]' );
			if ( trigger ) {
				e.preventDefault();
				open( trigger.getAttribute( 'data-hm-update' ) );
			}
		} );

		root.addEventListener( 'keydown', function ( e ) {
			if ( 'Enter' !== e.key && ' ' !== e.key ) {
				return;
			}
			var trigger = e.target.closest( '[data-hm-update]' );
			if ( trigger ) {
				e.preventDefault();
				open( trigger.getAttribute( 'data-hm-update' ) );
			}
		} );

		if ( closeBtn ) {
			closeBtn.addEventListener( 'click', close );
		}

		overlay.addEventListener( 'click', function ( e ) {
			if ( e.target === overlay ) {
				close();
			}
		} );

		document.addEventListener( 'keydown', function ( e ) {
			if ( 'Escape' === e.key && overlay.classList.contains( 'is-open' ) ) {
				close();
			}
		} );
	}

	function initCarousel( root ) {
		var wrap = root.querySelector( '.hm-carousel-wrap' );
		if ( ! wrap ) {
			return;
		}
		var track = wrap.querySelector( '.hm-carousel' );
		var slides = Array.prototype.slice.call( track.querySelectorAll( '.hm-carousel-slide' ) );
		if ( ! slides.length ) {
			return;
		}
		var prevBtn = wrap.querySelector( '.hm-carousel-prev' );
		var nextBtn = wrap.querySelector( '.hm-carousel-next' );
		var dots = Array.prototype.slice.call( root.querySelectorAll( '.hm-carousel-dot' ) );
		var currentIndex = 0;
		var autoplayTimer = null;

		function goTo( index ) {
			index = Math.max( 0, Math.min( slides.length - 1, index ) );
			slides[ index ].scrollIntoView( { behavior: 'smooth', inline: 'start', block: 'nearest' } );
		}

		function setActive( index ) {
			currentIndex = index;
			dots.forEach( function ( dot, i ) {
				dot.classList.toggle( 'is-active', i === index );
			} );
		}

		if ( prevBtn ) {
			prevBtn.addEventListener( 'click', function () {
				goTo( currentIndex - 1 );
			} );
		}
		if ( nextBtn ) {
			nextBtn.addEventListener( 'click', function () {
				goTo( currentIndex + 1 );
			} );
		}
		dots.forEach( function ( dot, i ) {
			dot.addEventListener( 'click', function () {
				goTo( i );
			} );
		} );

		if ( 'IntersectionObserver' in window ) {
			var observer = new IntersectionObserver(
				function ( entries ) {
					entries.forEach( function ( entry ) {
						if ( entry.isIntersecting && entry.intersectionRatio >= 0.6 ) {
							var idx = slides.indexOf( entry.target );
							if ( idx > -1 ) {
								setActive( idx );
							}
						}
					} );
				},
				{ root: track, threshold: [ 0.6 ] }
			);
			slides.forEach( function ( slide ) {
				observer.observe( slide );
			} );
		}

		var autoplay = '1' === track.getAttribute( 'data-autoplay' );
		var speed = parseInt( track.getAttribute( 'data-autoplay-speed' ), 10 ) || 4000;

		function startAutoplay() {
			if ( ! autoplay || slides.length < 2 ) {
				return;
			}
			stopAutoplay();
			autoplayTimer = setInterval( function () {
				goTo( ( currentIndex + 1 ) % slides.length );
			}, speed );
		}

		function stopAutoplay() {
			if ( autoplayTimer ) {
				clearInterval( autoplayTimer );
				autoplayTimer = null;
			}
		}

		startAutoplay();
		wrap.addEventListener( 'mouseenter', stopAutoplay );
		wrap.addEventListener( 'mouseleave', startAutoplay );
		wrap.addEventListener( 'touchstart', stopAutoplay, { passive: true } );
	}

	function initRoot( root ) {
		if ( ! root || root.getAttribute( 'data-hm-init' ) ) {
			return;
		}
		root.setAttribute( 'data-hm-init', '1' );
		initPopups( root );
		initCarousel( root );
	}

	function initAll( context ) {
		var scope = context || document;
		var roots = scope.querySelectorAll ? scope.querySelectorAll( '.hm-widget-root' ) : [];
		Array.prototype.forEach.call( roots, initRoot );
	}

	if ( window.elementorFrontend && window.elementorFrontend.hooks ) {
		window.elementorFrontend.hooks.addAction( 'frontend/element_ready/hm-updates.default', function ( $scope ) {
			var el = $scope && $scope[ 0 ] ? $scope[ 0 ] : null;
			if ( el ) {
				initAll( el );
			}
		} );
	}

	if ( 'loading' !== document.readyState ) {
		initAll();
	} else {
		document.addEventListener( 'DOMContentLoaded', function () {
			initAll();
		} );
	}
} )();
