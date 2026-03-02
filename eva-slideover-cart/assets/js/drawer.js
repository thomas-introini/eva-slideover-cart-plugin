/**
 * Eva Slideover Cart — drawer.js
 *
 * Vanilla JS. jQuery is used only as a bridge to WooCommerce events when
 * it is present on the page (it always is on a standard WooCommerce site).
 *
 * Global: evaScData (wp_localize_script)
 */
/* global evaScData, jQuery */

( function () {
	'use strict';

	// -------------------------------------------------------------------------
	// State
	// -------------------------------------------------------------------------
	var data      = ( typeof evaScData !== 'undefined' ) ? evaScData : {};
	var ajaxUrl   = data.ajaxUrl   || '';
	var nonce     = data.nonce     || '';
	var openOnAdd = !! data.openOnAdd;
	var i18n      = data.i18n      || {};

	var overlay = document.querySelector( '.eva-sc-overlay' );
	var drawer  = document.getElementById( 'eva-sc-drawer' );

	if ( ! overlay || ! drawer ) {
		return;
	}

	// Body scroll lock: record scroll position before locking.
	var scrollYBeforeLock = 0;

	// -------------------------------------------------------------------------
	// Open / Close
	// -------------------------------------------------------------------------
	function openDrawer() {
		scrollYBeforeLock = window.scrollY;
		document.documentElement.style.setProperty( '--eva-sc-scroll-y', '-' + scrollYBeforeLock + 'px' );
		document.documentElement.classList.add( 'eva-sc-locked' );
		drawer.classList.add( 'eva-sc-open' );
		drawer.setAttribute( 'aria-hidden', 'false' );
		overlay.classList.add( 'eva-sc-open' );

		var trigger = document.querySelector( '.eva-sc-trigger' );
		if ( trigger ) {
			trigger.setAttribute( 'aria-expanded', 'true' );
		}

		// Move focus to the close button for accessibility.
		var closeBtn = drawer.querySelector( '.eva-sc-close' );
		if ( closeBtn ) {
			setTimeout( function () { closeBtn.focus(); }, 50 );
		}
	}

	function closeDrawer() {
		document.documentElement.classList.remove( 'eva-sc-locked' );
		window.scrollTo( 0, scrollYBeforeLock );
		drawer.classList.remove( 'eva-sc-open' );
		drawer.setAttribute( 'aria-hidden', 'true' );
		overlay.classList.remove( 'eva-sc-open' );

		var trigger = document.querySelector( '.eva-sc-trigger' );
		if ( trigger ) {
			trigger.setAttribute( 'aria-expanded', 'false' );
			trigger.focus();
		}
	}

	function isOpen() {
		return drawer.classList.contains( 'eva-sc-open' );
	}

	// -------------------------------------------------------------------------
	// Focus trap
	// -------------------------------------------------------------------------
	function getFocusables() {
		return Array.prototype.slice.call(
			drawer.querySelectorAll(
				'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
			)
		);
	}

	function handleFocusTrap( e ) {
		if ( ! isOpen() ) {
			return;
		}
		if ( e.key !== 'Tab' ) {
			return;
		}
		var focusables = getFocusables();
		if ( focusables.length === 0 ) {
			return;
		}
		var first = focusables[ 0 ];
		var last  = focusables[ focusables.length - 1 ];

		if ( e.shiftKey && document.activeElement === first ) {
			e.preventDefault();
			last.focus();
		} else if ( ! e.shiftKey && document.activeElement === last ) {
			e.preventDefault();
			first.focus();
		}
	}

	// -------------------------------------------------------------------------
	// Swipe-to-dismiss (mobile)
	// -------------------------------------------------------------------------
	var touchStartX = null;

	drawer.addEventListener( 'touchstart', function ( e ) {
		touchStartX = e.touches[ 0 ].clientX;
	}, { passive: true } );

	drawer.addEventListener( 'touchend', function ( e ) {
		if ( touchStartX === null ) {
			return;
		}
		var delta = e.changedTouches[ 0 ].clientX - touchStartX;
		touchStartX = null;
		// Swipe right > 80 px closes the drawer.
		if ( delta > 80 ) {
			closeDrawer();
		}
	}, { passive: true } );

	// -------------------------------------------------------------------------
	// Event listeners — open / close
	// -------------------------------------------------------------------------
	document.addEventListener( 'click', function ( e ) {
		// Trigger button.
		if ( e.target.closest( '.eva-sc-trigger' ) ) {
			isOpen() ? closeDrawer() : openDrawer();
			return;
		}
		// Close button inside drawer.
		if ( e.target.closest( '.eva-sc-close' ) ) {
			closeDrawer();
			return;
		}
	} );

	// Overlay click.
	overlay.addEventListener( 'click', closeDrawer );

	// ESC key.
	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'Escape' && isOpen() ) {
			closeDrawer();
		}
		handleFocusTrap( e );
	} );

	// -------------------------------------------------------------------------
	// Fragment application
	// -------------------------------------------------------------------------
	function applyFragments( fragments ) {
		if ( ! fragments || typeof fragments !== 'object' ) {
			return;
		}
		Object.keys( fragments ).forEach( function ( selector ) {
			var els = document.querySelectorAll( selector );
			els.forEach( function ( el ) {
				var tmp = document.createElement( 'div' );
				tmp.innerHTML = fragments[ selector ];
				var newEl = tmp.firstElementChild;
				if ( newEl ) {
					el.parentNode.replaceChild( newEl, el );
				}
			} );
		} );
	}

	// Notify wc-cart-fragments to re-sync its session.
	function triggerWooRefresh() {
		if ( typeof jQuery !== 'undefined' ) {
			jQuery( document.body ).trigger( 'wc_fragment_refresh' );
		}
	}

	// -------------------------------------------------------------------------
	// AJAX helpers
	// -------------------------------------------------------------------------
	function cartAjax( action, params, itemRow ) {
		if ( itemRow ) {
			itemRow.classList.add( 'eva-sc-loading' );
		}

		var body = new URLSearchParams( {
			action: action,
			nonce:  nonce,
		} );

		Object.keys( params ).forEach( function ( k ) {
			body.set( k, params[ k ] );
		} );

		return fetch( ajaxUrl, {
			method:      'POST',
			credentials: 'same-origin',
			headers:     { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body:        body.toString(),
		} )
			.then( function ( res ) { return res.json(); } )
			.then( function ( json ) {
				if ( itemRow ) {
					itemRow.classList.remove( 'eva-sc-loading' );
				}

				if ( json.success && json.data && json.data.fragments ) {
					applyFragments( json.data.fragments );
					triggerWooRefresh();
				} else {
					console.warn( 'Eva SC:', ( json.data && json.data.message ) || i18n.errorGeneric );
				}
				return json;
			} )
			.catch( function () {
				if ( itemRow ) {
					itemRow.classList.remove( 'eva-sc-loading' );
				}
				console.warn( 'Eva SC:', i18n.errorGeneric );
			} );
	}

	// -------------------------------------------------------------------------
	// Quantity stepper
	// -------------------------------------------------------------------------
	function updateQty( key, qty, itemRow ) {
		cartAjax( 'eva_sc_update_qty', { cart_item_key: key, quantity: qty }, itemRow );
	}

	function bindQtyHandlers() {
		// Delegated on .eva-sc-body to survive fragment replacement.
		var body = drawer.querySelector( '.eva-sc-body' );
		if ( ! body ) {
			return;
		}

		body.addEventListener( 'click', function ( e ) {
			var minusBtn, plusBtn, key, input, current, next, maxVal, row;

			// Minus.
			minusBtn = e.target.closest( '.eva-sc-qty-minus' );
			if ( minusBtn ) {
				key     = minusBtn.dataset.key;
				input   = getQtyInput( key );
				current = input ? parseInt( input.value, 10 ) : 1;
				next    = Math.max( 1, current - 1 );
				if ( input ) { input.value = next; }
				row = minusBtn.closest( '.eva-sc-item' );
				updateQty( key, next, row );
				return;
			}

			// Plus.
			plusBtn = e.target.closest( '.eva-sc-qty-plus' );
			if ( plusBtn ) {
				key     = plusBtn.dataset.key;
				input   = getQtyInput( key );
				current = input ? parseInt( input.value, 10 ) : 1;
				maxVal  = plusBtn.dataset.max ? parseInt( plusBtn.dataset.max, 10 ) : Infinity;
				next    = Math.min( maxVal, current + 1 );
				if ( input ) { input.value = next; }
				row = plusBtn.closest( '.eva-sc-item' );
				updateQty( key, next, row );
				return;
			}
		} );

		// Direct input change.
		body.addEventListener( 'change', function ( e ) {
			var input = e.target.closest( '.eva-sc-qty-input' );
			if ( ! input ) {
				return;
			}
			var key = input.dataset.key;
			var qty = Math.max( 1, parseInt( input.value, 10 ) || 1 );
			input.value = qty;
			var row = input.closest( '.eva-sc-item' );
			updateQty( key, qty, row );
		} );
	}

	function getQtyInput( key ) {
		return drawer.querySelector( '.eva-sc-qty-input[data-key="' + key + '"]' );
	}

	// -------------------------------------------------------------------------
	// Remove item
	// -------------------------------------------------------------------------
	function bindRemoveHandlers() {
		var body = drawer.querySelector( '.eva-sc-body' );
		if ( ! body ) {
			return;
		}

		body.addEventListener( 'click', function ( e ) {
			var btn = e.target.closest( '.eva-sc-remove' );
			if ( ! btn ) {
				return;
			}
			var key = btn.dataset.key;
			var row = btn.closest( '.eva-sc-item' );
			cartAjax( 'eva_sc_remove_item', { cart_item_key: key }, row );
		} );
	}

	// -------------------------------------------------------------------------
	// WooCommerce event bridge (jQuery)
	// -------------------------------------------------------------------------
	function initJQueryBridge() {
		if ( typeof jQuery === 'undefined' ) {
			return;
		}

		// Open drawer when a product is added to cart (via Woo's AJAX add-to-cart).
		jQuery( document.body ).on( 'added_to_cart', function () {
			if ( openOnAdd ) {
				openDrawer();
			}
		} );

		// Rebind handlers after Woo refreshes fragments (wc-cart-fragments.js).
		jQuery( document.body ).on( 'wc_fragments_refreshed', function () {
			// Handlers are already delegated on .eva-sc-body — no rebind needed.
			// Re-apply any state-based UI if required in the future.
		} );
	}

	// -------------------------------------------------------------------------
	// Init
	// -------------------------------------------------------------------------
	bindQtyHandlers();
	bindRemoveHandlers();
	initJQueryBridge();

} () );
