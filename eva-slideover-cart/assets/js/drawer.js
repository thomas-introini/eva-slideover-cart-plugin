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
	var quantityDebounce = 400;

	var overlay = document.querySelector( '.eva-sc-overlay' );
	var drawer  = document.getElementById( 'eva-sc-drawer' );
	var statusLive = drawer ? drawer.querySelector( '.eva-sc-status-live' ) : null;
	var alertBox = drawer ? drawer.querySelector( '.eva-sc-alert' ) : null;
	var alertText = drawer ? drawer.querySelector( '.eva-sc-alert-text' ) : null;
	var alertRetryBtn = drawer ? drawer.querySelector( '.eva-sc-alert-retry' ) : null;
	var undoBox = drawer ? drawer.querySelector( '.eva-sc-undo' ) : null;
	var undoMessage = drawer ? drawer.querySelector( '.eva-sc-undo-message' ) : null;
	var undoLink = drawer ? drawer.querySelector( '.eva-sc-undo-link' ) : null;

	var pendingRequests = {};
	var quantityTimers = {};
	var pendingVisualStates = {};
	var undoItemKey = '';
	var undoTimer = null;
	var retryAction = null;
	var lastTrigger = null;
	var inertedElements = [];

	if ( ! overlay || ! drawer ) {
		return;
	}

	if ( alertRetryBtn ) {
		alertRetryBtn.textContent = i18n.retry || 'Retry';
	}
	if ( undoLink ) {
		undoLink.textContent = i18n.undo || 'Undo';
	}

	function setUndoLinkEnabled( isEnabled ) {
		if ( ! undoLink ) {
			return;
		}
		if ( isEnabled ) {
			undoLink.removeAttribute( 'aria-disabled' );
			undoLink.classList.remove( 'eva-sc-undo-link--disabled' );
			return;
		}
		undoLink.setAttribute( 'aria-disabled', 'true' );
		undoLink.classList.add( 'eva-sc-undo-link--disabled' );
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

	function hideUndo() {
		if ( undoTimer ) {
			window.clearTimeout( undoTimer );
			undoTimer = null;
		}
		undoItemKey = '';
		if ( undoBox ) {
			undoBox.hidden = true;
		}
		setUndoLinkEnabled( true );
	}

	function showUndo( key ) {
		if ( ! undoBox || ! undoMessage || ! undoLink ) {
			return;
		}
		if ( undoTimer ) {
			window.clearTimeout( undoTimer );
		}
		undoItemKey = key;
		setUndoLinkEnabled( true );
		undoMessage.textContent = i18n.removedItemUndo || 'Item removed from cart.';
		undoBox.hidden = false;
		undoTimer = window.setTimeout( hideUndo, 5000 );
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

	if ( undoLink ) {
		undoLink.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			var key = undoItemKey;
			if ( ! key || undoLink.getAttribute( 'aria-disabled' ) === 'true' ) {
				return;
			}
			setUndoLinkEnabled( false );
			restoreItem( key );
		} );
	}

	// Body scroll lock: record scroll position before locking.
	var scrollYBeforeLock = 0;

	function setBackgroundInert( shouldInert ) {
		if ( shouldInert ) {
			inertedElements = [];
			Array.prototype.forEach.call( document.body.children, function ( element ) {
				if ( element === overlay || element === drawer || element.tagName === 'SCRIPT' || element.tagName === 'STYLE' ) {
					return;
				}
				inertedElements.push( {
					element: element,
					inert: element.inert,
					ariaHidden: element.getAttribute( 'aria-hidden' )
				} );
				element.inert = true;
				element.setAttribute( 'aria-hidden', 'true' );
			} );
			return;
		}

		inertedElements.forEach( function ( state ) {
			state.element.inert = state.inert;
			if ( state.ariaHidden === null ) {
				state.element.removeAttribute( 'aria-hidden' );
			} else {
				state.element.setAttribute( 'aria-hidden', state.ariaHidden );
			}
		} );
		inertedElements = [];
	}

	function setTriggersExpanded( expanded ) {
		document.querySelectorAll( '.eva-sc-trigger' ).forEach( function ( trigger ) {
			trigger.setAttribute( 'aria-expanded', expanded ? 'true' : 'false' );
		} );
	}

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
		overlay.setAttribute( 'aria-hidden', 'false' );
		setBackgroundInert( true );
		setTriggersExpanded( true );

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
		overlay.setAttribute( 'aria-hidden', 'true' );
		setBackgroundInert( false );
		setTriggersExpanded( false );

		if ( lastTrigger && document.contains( lastTrigger ) ) {
			lastTrigger.focus();
		}
		lastTrigger = null;
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
		var trigger = e.target.closest( '.eva-sc-trigger' );
		if ( trigger ) {
			lastTrigger = trigger;
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
		applyProductBackgroundColors();
	}

	/**
	 * Copy a product card's --eva-bg-color custom property to the matching drawer
	 * thumbnail. Custom properties do not inherit between separate DOM trees.
	 */
	function applyProductBackgroundColors() {
		drawer.querySelectorAll( '.eva-sc-item[data-product-id]' ).forEach( function ( item ) {
			var productId = item.dataset.productId;
			var cardButton = document.querySelector( '.add_to_cart_button[data-product_id="' + productId + '"]' );
			var productCard = cardButton ? cardButton.closest( '.product, .wc-block-product' ) : null;
			var colorSource = productCard || cardButton;
			var thumbnail = item.querySelector( '.eva-sc-item-thumb' );

			if ( ! colorSource || ! thumbnail ) {
				return;
			}

			var backgroundColor = window.getComputedStyle( colorSource ).getPropertyValue( '--eva-bg-color' ).trim();
			if ( backgroundColor ) {
				thumbnail.style.setProperty( '--eva-sc-item-image-bg', backgroundColor );
			}
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

	function setCartUpdating( requestKey, isUpdating ) {
		if ( isUpdating ) {
			pendingVisualStates[ requestKey ] = true;
		} else {
			delete pendingVisualStates[ requestKey ];
		}

		var hasPendingUpdates = Object.keys( pendingVisualStates ).length > 0;
		document.documentElement.classList.toggle( 'eva-sc-cart-updating', hasPendingUpdates );
		drawer.setAttribute( 'aria-busy', hasPendingUpdates ? 'true' : 'false' );
	}

	function setRowLoading( itemRow, isLoading, isQuantityUpdate ) {
		if ( ! itemRow ) {
			return;
		}
		itemRow.classList.toggle( 'eva-sc-loading', !! isLoading && ! isQuantityUpdate );
		itemRow.classList.toggle( 'eva-sc-qty-loading', !! isLoading && !! isQuantityUpdate );
		if ( isLoading ) {
			itemRow.setAttribute( 'aria-busy', 'true' );
		} else {
			itemRow.removeAttribute( 'aria-busy' );
		}
	}

	function cartAjax( action, params, itemRow, options ) {
		options = options || {};
		var requestKey = getRequestKey( action, params );
		var existingRequest = pendingRequests[ requestKey ];
		if ( existingRequest ) {
			if ( ! options.cancelPrevious ) {
				return existingRequest.promise;
			}
			existingRequest.superseded = true;
			if ( existingRequest.controller ) {
				existingRequest.controller.abort();
			}
		}

		if ( ! ajaxUrl ) {
			showAlert( i18n.errorConfig || i18n.errorGeneric );
			setCartUpdating( requestKey, false );
			setRowLoading( itemRow, false, action === 'eva_sc_update_qty' );
			return Promise.resolve();
		}

		hideAlert();
		setCartUpdating( requestKey, true );
		setRowLoading( itemRow, true, action === 'eva_sc_update_qty' );

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

		var requestEntry = {
			controller: controller,
			promise: null,
			superseded: false,
		};

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

				if ( pendingRequests[ requestKey ] !== requestEntry ) {
					return json;
				}

				if ( ! json || ! json.success || ! json.data || ! json.data.fragments ) {
					var errorMessage = ( json && json.data && json.data.message ) || getStatusMessage( response.status );
					showAlert( errorMessage, function () {
						cartAjax( action, params, itemRow, options );
					} );
					console.warn( 'Eva SC:', errorMessage );
					return json;
				}

				applyFragments( json.data.fragments );
				triggerWooRefresh();

				if ( action === 'eva_sc_remove_item' ) {
					announceStatus( i18n.removedItem || i18n.updatedCart );
				} else if ( action === 'eva_sc_restore_item' ) {
					announceStatus( i18n.restoredItem || i18n.updatedCart );
				} else if ( action === 'eva_sc_update_qty' ) {
					announceStatus( i18n.updatedQty || i18n.updatedCart );
				} else {
					announceStatus( i18n.updatedCart );
				}

				if ( typeof options.onSuccess === 'function' ) {
					options.onSuccess( json );
				}

				return json;
			} )
			.catch( function ( error ) {
				if ( requestEntry.superseded ) {
					return null;
				}
				var errorMessage = getErrorMessage( error, 0 );
				showAlert( errorMessage, function () {
					cartAjax( action, params, itemRow, options );
				} );
				console.warn( 'Eva SC:', errorMessage );
				return null;
			} )
			.then( function ( result ) {
				if ( timeoutId ) {
					window.clearTimeout( timeoutId );
				}
				if ( pendingRequests[ requestKey ] === requestEntry ) {
					setRowLoading( itemRow, false, action === 'eva_sc_update_qty' );
					setCartUpdating( requestKey, false );
					delete pendingRequests[ requestKey ];
				}
				return result;
			} );

		requestEntry.promise = request;
		pendingRequests[ requestKey ] = requestEntry;

		return request;
	}

	// -------------------------------------------------------------------------
	// Quantity stepper
	// -------------------------------------------------------------------------
	function updateQty( key, qty, itemRow ) {
		cartAjax(
			'eva_sc_update_qty',
			{ cart_item_key: key, quantity: qty },
			itemRow,
			{ cancelPrevious: true }
		).then( function ( json ) {
			if ( ! json || json.success || ! json.data || typeof json.data.actual_qty === 'undefined' ) {
				return;
			}

			var value = getQtyValue( key );
			if ( value ) {
				value.textContent = json.data.actual_qty;
			}
			syncQtyControls( itemRow, json.data.actual_qty );
		} );
	}

	function scheduleQtyUpdate( key, qty, itemRow ) {
		var requestKey = getRequestKey( 'eva_sc_update_qty', { cart_item_key: key } );
		if ( quantityTimers[ key ] ) {
			window.clearTimeout( quantityTimers[ key ] );
		}
		setCartUpdating( requestKey, true );
		setRowLoading( itemRow, true, true );
		quantityTimers[ key ] = window.setTimeout( function () {
			delete quantityTimers[ key ];
			updateQty( key, qty, itemRow );
		}, quantityDebounce );
	}

	function cancelQuantityUpdate( key ) {
		var requestKey = getRequestKey( 'eva_sc_update_qty', { cart_item_key: key } );
		var request = pendingRequests[ requestKey ];

		if ( quantityTimers[ key ] ) {
			window.clearTimeout( quantityTimers[ key ] );
			delete quantityTimers[ key ];
		}
		if ( request ) {
			request.superseded = true;
			if ( request.controller ) {
				request.controller.abort();
			}
			delete pendingRequests[ requestKey ];
		}
		setCartUpdating( requestKey, false );
	}

	function syncQtyControls( itemRow, qty ) {
		if ( ! itemRow ) {
			return;
		}
		var wrap = itemRow.querySelector( '.eva-sc-qty-wrap' );
		var plusBtn = itemRow.querySelector( '.eva-sc-qty-plus' );
		var maxVal = wrap && wrap.dataset.max ? parseInt( wrap.dataset.max, 10 ) : Infinity;
		if ( plusBtn ) {
			plusBtn.disabled = qty >= maxVal;
		}
	}

	function bindQtyHandlers() {
		// Delegated on .eva-sc-body to survive fragment replacement.
		var body = drawer.querySelector( '.eva-sc-body' );
		if ( ! body ) {
			return;
		}

		body.addEventListener( 'click', function ( e ) {
			var minusBtn, plusBtn, key, value, current, next, minVal, maxVal, row, wrap;

			// Minus.
			minusBtn = e.target.closest( '.eva-sc-qty-minus' );
			if ( minusBtn ) {
				key     = minusBtn.dataset.key;
				value   = getQtyValue( key );
				current = value ? parseInt( value.textContent, 10 ) : 1;
				row = minusBtn.closest( '.eva-sc-item' );
				wrap = minusBtn.closest( '.eva-sc-qty-wrap' );
				minVal = wrap && wrap.dataset.min ? parseInt( wrap.dataset.min, 10 ) : 1;
				if ( current <= minVal ) {
					removeItem( key, row );
					return;
				}
				next = current - 1;
				if ( value ) { value.textContent = next; }
				syncQtyControls( row, next );
				scheduleQtyUpdate( key, next, row );
				return;
			}

			// Plus.
			plusBtn = e.target.closest( '.eva-sc-qty-plus' );
			if ( plusBtn ) {
				key     = plusBtn.dataset.key;
				value   = getQtyValue( key );
				current = value ? parseInt( value.textContent, 10 ) : 1;
				row     = plusBtn.closest( '.eva-sc-item' );
				wrap    = plusBtn.closest( '.eva-sc-qty-wrap' );
				maxVal  = wrap && wrap.dataset.max ? parseInt( wrap.dataset.max, 10 ) : Infinity;
				next    = Math.min( maxVal, current + 1 );
				if ( value ) { value.textContent = next; }
				syncQtyControls( row, next );
				scheduleQtyUpdate( key, next, row );
				return;
			}
		} );

	}

	function getQtyValue( key ) {
		return drawer.querySelector( '.eva-sc-qty-value[data-key="' + key + '"]' );
	}

	// -------------------------------------------------------------------------
	// Remove item
	// -------------------------------------------------------------------------
	function removeItem( key, itemRow ) {
		cancelQuantityUpdate( key );
		cartAjax(
			'eva_sc_remove_item',
			{ cart_item_key: key },
			itemRow,
			{
				onSuccess: function () {
					showUndo( key );
				}
			}
		);
	}

	function restoreItem( key ) {
		cartAjax(
			'eva_sc_restore_item',
			{ cart_item_key: key },
			null,
			{
				onSuccess: hideUndo
			}
		).then( function ( json ) {
			if ( ! json || ! json.success ) {
				setUndoLinkEnabled( true );
			}
		} );
	}

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
			removeItem( key, row );
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
			applyProductBackgroundColors();
		} );
	}

	// -------------------------------------------------------------------------
	// Init
	// -------------------------------------------------------------------------
	bindQtyHandlers();
	bindRemoveHandlers();
	initJQueryBridge();
	applyProductBackgroundColors();

	window.addEventListener( 'online', function () {
		hideAlert();
		announceStatus( i18n.backOnline || 'Connection restored.' );
	} );

} () );
