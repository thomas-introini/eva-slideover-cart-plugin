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
	var requestTimeout = parseInt( data.requestTimeout, 10 ) || 15000;
	var drawerPosition = data.position === 'left' ? 'left' : 'right';

	var overlay = document.querySelector( '.eva-sc-overlay' );
	var drawer  = document.getElementById( 'eva-sc-drawer' );
	var statusLive = drawer ? drawer.querySelector( '.eva-sc-status-live' ) : null;
	var alertBox = drawer ? drawer.querySelector( '.eva-sc-alert' ) : null;
	var alertText = drawer ? drawer.querySelector( '.eva-sc-alert-text' ) : null;
	var alertRetryBtn = drawer ? drawer.querySelector( '.eva-sc-alert-retry' ) : null;

	var pendingRequests = {};
	var retryAction = null;

	if ( ! overlay || ! drawer ) {
		return;
	}

	if ( alertRetryBtn ) {
		alertRetryBtn.textContent = i18n.retry || 'Retry';
	}

	function announceStatus( message ) {
		if ( ! statusLive || ! message ) {
			return;
		}
		statusLive.textContent = '';
		window.setTimeout( function () {
			statusLive.textContent = message;
		}, 20 );
	}

	function showAlert( message, onRetry ) {
		if ( ! alertBox || ! alertText ) {
			return;
		}
		alertText.textContent = message || i18n.errorGeneric || 'Something went wrong.';
		alertBox.hidden = false;
		retryAction = typeof onRetry === 'function' ? onRetry : null;
		if ( alertRetryBtn ) {
			alertRetryBtn.hidden = ! retryAction;
		}
	}

	function hideAlert() {
		if ( ! alertBox || ! alertText ) {
			return;
		}
		alertBox.hidden = true;
		alertText.textContent = '';
		retryAction = null;
	}

	if ( alertRetryBtn ) {
		alertRetryBtn.addEventListener( 'click', function () {
			if ( ! retryAction ) {
				return;
			}
			var run = retryAction;
			hideAlert();
			run();
		} );
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
		// Swipe from drawer edge direction closes the panel.
		if ( ( drawerPosition === 'right' && delta > 80 ) || ( drawerPosition === 'left' && delta < -80 ) ) {
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
	function getStatusMessage( statusCode ) {
		if ( statusCode === 400 ) {
			return i18n.errorValidation || i18n.errorGeneric;
		}
		if ( statusCode === 401 || statusCode === 403 ) {
			return i18n.errorPermission || i18n.errorGeneric;
		}
		if ( statusCode === 404 ) {
			return i18n.errorNotFound || i18n.errorGeneric;
		}
		if ( statusCode === 429 ) {
			return i18n.errorRateLimit || i18n.errorGeneric;
		}
		if ( statusCode >= 500 ) {
			return i18n.errorServer || i18n.errorGeneric;
		}
		return i18n.errorGeneric;
	}

	function getErrorMessage( error, statusCode ) {
		if ( typeof navigator !== 'undefined' && navigator.onLine === false ) {
			return i18n.errorOffline || i18n.errorGeneric;
		}
		if ( error && error.name === 'AbortError' ) {
			return i18n.errorTimeout || i18n.errorGeneric;
		}
		return getStatusMessage( statusCode || 0 );
	}

	function getRequestKey( action, params ) {
		return action + ':' + ( params.cart_item_key || 'global' );
	}

	function setRowLoading( itemRow, isLoading ) {
		if ( ! itemRow ) {
			return;
		}
		itemRow.classList.toggle( 'eva-sc-loading', !! isLoading );
		if ( isLoading ) {
			itemRow.setAttribute( 'aria-busy', 'true' );
		} else {
			itemRow.removeAttribute( 'aria-busy' );
		}
	}

	function cartAjax( action, params, itemRow ) {
		var requestKey = getRequestKey( action, params );
		if ( pendingRequests[ requestKey ] ) {
			return pendingRequests[ requestKey ];
		}

		if ( ! ajaxUrl ) {
			showAlert( i18n.errorConfig || i18n.errorGeneric );
			return Promise.resolve();
		}

		hideAlert();
		setRowLoading( itemRow, true );

		var body = new URLSearchParams( {
			action: action,
			nonce:  nonce,
		} );

		Object.keys( params ).forEach( function ( k ) {
			body.set( k, params[ k ] );
		} );

		var controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
		var timeoutId = null;
		if ( controller ) {
			timeoutId = window.setTimeout( function () {
				controller.abort();
			}, requestTimeout );
		}

		var fetchOptions = {
			method:      'POST',
			credentials: 'same-origin',
			headers:     { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body:        body.toString(),
		};

		if ( controller ) {
			fetchOptions.signal = controller.signal;
		}

		var request = fetch( ajaxUrl, fetchOptions )
			.then( function ( res ) {
				return res.text().then( function ( text ) {
					var json = null;
					try {
						json = JSON.parse( text );
					} catch ( parseError ) {
						json = null;
					}
					return {
						res: res,
						json: json,
					};
				} );
			} )
			.then( function ( payload ) {
				var response = payload.res;
				var json = payload.json;

				if ( ! json || ! json.success || ! json.data || ! json.data.fragments ) {
					var errorMessage = ( json && json.data && json.data.message ) || getStatusMessage( response.status );
					showAlert( errorMessage, function () {
						cartAjax( action, params, itemRow );
					} );
					console.warn( 'Eva SC:', errorMessage );
					return json;
				}

				applyFragments( json.data.fragments );
				triggerWooRefresh();

				if ( action === 'eva_sc_remove_item' ) {
					announceStatus( i18n.removedItem || i18n.updatedCart );
				} else if ( action === 'eva_sc_update_qty' ) {
					announceStatus( i18n.updatedQty || i18n.updatedCart );
				} else {
					announceStatus( i18n.updatedCart );
				}

				return json;
			} )
			.catch( function ( error ) {
				var errorMessage = getErrorMessage( error, 0 );
				showAlert( errorMessage, function () {
					cartAjax( action, params, itemRow );
				} );
				console.warn( 'Eva SC:', errorMessage );
				return null;
			} )
			.then( function ( result ) {
				if ( timeoutId ) {
					window.clearTimeout( timeoutId );
				}
				setRowLoading( itemRow, false );
				delete pendingRequests[ requestKey ];
				return result;
			} );

		pendingRequests[ requestKey ] = request;

		return request;
	}

	// -------------------------------------------------------------------------
	// Quantity stepper
	// -------------------------------------------------------------------------
	function updateQty( key, qty, itemRow ) {
		if ( itemRow && itemRow.classList.contains( 'eva-sc-loading' ) ) {
			return;
		}
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
			var row = input.closest( '.eva-sc-item' );
			if ( row && row.classList.contains( 'eva-sc-loading' ) ) {
				return;
			}
			var key = input.dataset.key;
			var maxVal = input.getAttribute( 'max' ) ? parseInt( input.getAttribute( 'max' ), 10 ) : Infinity;
			var qty = Math.max( 1, parseInt( input.value, 10 ) || 1 );
			qty = Math.min( maxVal, qty );
			input.value = qty;
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
			if ( row && row.classList.contains( 'eva-sc-loading' ) ) {
				return;
			}
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

	window.addEventListener( 'online', function () {
		hideAlert();
		announceStatus( i18n.backOnline || 'Connection restored.' );
	} );

} () );
