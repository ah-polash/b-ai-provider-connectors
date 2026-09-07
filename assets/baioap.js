/**
 * AI Provider Connectors — injects a "Test Connection Again" button beside the
 * "Connected" badge on the Connectors screen.
 *
 * @package BAIProviderConnectors
 */
( function ( window, document ) {
	'use strict';

	var data = window.baioapData;
	if ( ! data || ! data.restRoot || ! Array.isArray( data.connectors ) ) {
		return;
	}

	var apiFetch = window.wp && window.wp.apiFetch ? window.wp.apiFetch : null;
	var i18n     = ( window.wp && window.wp.i18n ) || { __: function ( s ) { return s; } };
	var __       = i18n.__;

	var BUTTON_MARKER     = 'data-baioap-button';
	var BADGE_MARKER      = 'data-baioap-bound';
	var SETTINGS_MARKER   = 'data-baioap-settings-bound';
	var RESULT_MARKER     = 'data-baioap-result';
	var BADGE_TEXT        = __( 'Connected' );
	var BUTTON_TEXT       = __( 'Test Connection', 'b-ai-provider-connectors' );
	var BUTTON_TEXT_BUSY  = __( 'Testing…', 'b-ai-provider-connectors' );
	var TESTABLE_TYPES    = { ai_provider: true };

	/**
	 * Returns true when a connector descriptor is eligible for live testing.
	 *
	 * @param {object} connector Connector descriptor.
	 * @return {boolean}
	 */
	function isTestable( connector ) {
		return !! ( connector && TESTABLE_TYPES[ connector.type ] );
	}

	/**
	 * Looks up a connector by display name (matches the h2 within the card).
	 *
	 * @param {string} name Connector display name.
	 * @return {object|null}
	 */
	function findConnectorByName( name ) {
		if ( ! name ) {
			return null;
		}
		var normalized = name.trim().toLowerCase();
		for ( var i = 0; i < data.connectors.length; i++ ) {
			if ( data.connectors[ i ].name && data.connectors[ i ].name.trim().toLowerCase() === normalized ) {
				return data.connectors[ i ];
			}
		}
		return null;
	}

	/**
	 * Locates the connector card element that contains the given node.
	 *
	 * @param {Node} node Starting node.
	 * @return {Element|null}
	 */
	function findConnectorCard( node ) {
		var el = node;
		while ( el && el.nodeType === 1 ) {
			if ( el.classList && el.classList.contains( 'components-item' ) ) {
				return el;
			}
			el = el.parentElement;
		}
		return null;
	}

	/**
	 * Reads the connector's display name from a card element.
	 *
	 * @param {Element} card Card element.
	 * @return {string}
	 */
	function getConnectorNameFromCard( card ) {
		if ( ! card ) {
			return '';
		}
		var heading = card.querySelector( 'h2' );
		return heading ? heading.textContent || '' : '';
	}

	/**
	 * Creates the "Test Connection Again" button element.
	 *
	 * @return {HTMLButtonElement}
	 */
	function createTestButton() {
		var button = document.createElement( 'button' );
		button.type = 'button';
		button.className = 'components-button is-tertiary is-compact baioap__button';
		button.setAttribute( BUTTON_MARKER, '1' );
		button.textContent = BUTTON_TEXT;
		return button;
	}

	/**
	 * Creates the tooltip element shown above the button after a test completes.
	 *
	 * @return {HTMLSpanElement}
	 */
	function createResultElement() {
		var span = document.createElement( 'span' );
		span.className = 'baioap__tooltip';
		span.setAttribute( RESULT_MARKER, '1' );
		span.setAttribute( 'role', 'status' );
		span.setAttribute( 'aria-live', 'polite' );
		span.hidden = true;
		document.body.appendChild( span );
		return span;
	}

	/**
	 * Positions the tooltip above the given button using viewport coordinates.
	 *
	 * @param {Element} tooltip Tooltip element (in document.body).
	 * @param {Element} button  Button the tooltip points to.
	 */
	function positionTooltip( tooltip, button ) {
		var rect = button.getBoundingClientRect();
		// Measure first so we can flip below if there is not enough space above.
		tooltip.style.left = '0px';
		tooltip.style.top  = '0px';
		var ttRect = tooltip.getBoundingClientRect();

		var gap     = 8;
		var topPos  = rect.top - ttRect.height - gap;
		var flipped = topPos < 4;
		if ( flipped ) {
			topPos = rect.bottom + gap;
		}

		var leftPos = rect.left + ( rect.width / 2 ) - ( ttRect.width / 2 );
		var maxLeft = window.innerWidth - ttRect.width - 4;
		if ( leftPos < 4 ) {
			leftPos = 4;
		} else if ( leftPos > maxLeft ) {
			leftPos = maxLeft;
		}

		tooltip.style.left = Math.round( leftPos ) + 'px';
		tooltip.style.top  = Math.round( topPos ) + 'px';
		tooltip.classList.toggle( 'is-below', flipped );
	}

	/**
	 * Renders the result of a connection test as a tooltip near the button.
	 *
	 * @param {Element} tooltip Tooltip element associated with the button.
	 * @param {Element} button  Button that triggered the test.
	 * @param {boolean} success Whether the test succeeded.
	 * @param {string}  message Message to render.
	 */
	function renderResult( tooltip, button, success, message ) {
		tooltip.textContent = message;
		tooltip.classList.remove( 'is-success', 'is-error' );
		tooltip.classList.add( success ? 'is-success' : 'is-error' );
		tooltip.hidden = false;
		positionTooltip( tooltip, button );

		if ( tooltip._hideTimer ) {
			window.clearTimeout( tooltip._hideTimer );
		}
		tooltip._hideTimer = window.setTimeout( function () {
			tooltip.hidden = true;
			tooltip.textContent = '';
			tooltip.classList.remove( 'is-success', 'is-error', 'is-below' );
		}, 4000 );
	}

	/**
	 * Runs a connection test for the given connector.
	 *
	 * @param {object} connector Connector descriptor from baioapData.
	 * @param {HTMLButtonElement} button Button that initiated the test.
	 * @param {Element} resultEl Element used to render the test result.
	 */
	function runTest( connector, button, resultEl ) {
		if ( ! apiFetch ) {
			renderResult( resultEl, button, false, __( 'wp.apiFetch is not available.', 'b-ai-provider-connectors' ) );
			return;
		}

		button.disabled = true;
		button.classList.add( 'is-busy' );
		button.textContent = BUTTON_TEXT_BUSY;
		resultEl.hidden = true;
		resultEl.textContent = '';
		resultEl.classList.remove( 'is-success', 'is-error' );
		if ( resultEl._hideTimer ) {
			window.clearTimeout( resultEl._hideTimer );
			resultEl._hideTimer = null;
		}

		apiFetch( {
			path: '/baioap/v1/test/' + encodeURIComponent( connector.id ),
			method: 'POST'
		} ).then( function ( response ) {
			var success = !! ( response && response.success );
			var message = ( response && response.message ) || ( success
				? __( 'Connection successful.', 'b-ai-provider-connectors' )
				: __( 'Connection failed.', 'b-ai-provider-connectors' )
			);
			renderResult( resultEl, button, success, message );
		} ).catch( function ( error ) {
			var message = ( error && error.message ) || __( 'Connection test failed.', 'b-ai-provider-connectors' );
			renderResult( resultEl, button, false, message );
		} ).finally( function () {
			button.disabled = false;
			button.classList.remove( 'is-busy' );
			button.textContent = BUTTON_TEXT;
		} );
	}

	/**
	 * Injects a "Test Connection Again" button beside the given badge.
	 *
	 * @param {Element} badge Connected badge element.
	 */
	function attachToBadge( badge ) {
		if ( ! badge || badge.getAttribute( BADGE_MARKER ) === '1' ) {
			return;
		}

		var card = findConnectorCard( badge );
		if ( ! card ) {
			return;
		}

		var name = getConnectorNameFromCard( card );
		var connector = findConnectorByName( name );
		if ( ! isTestable( connector ) ) {
			// Only testable connectors get a button; mark to avoid re-evaluating.
			badge.setAttribute( BADGE_MARKER, '1' );
			return;
		}

		badge.setAttribute( BADGE_MARKER, '1' );

		var wrapper = document.createElement( 'span' );
		wrapper.className = 'baioap__wrapper';

		var button   = createTestButton();
		var resultEl = createResultElement();

		button.addEventListener( 'click', function () {
			runTest( connector, button, resultEl );
		} );

		wrapper.appendChild( button );

		if ( badge.parentNode ) {
			if ( badge.nextSibling ) {
				badge.parentNode.insertBefore( wrapper, badge.nextSibling );
			} else {
				badge.parentNode.appendChild( wrapper );
			}
		}
	}

	/**
	 * Injects a "Test Connection Again" button into an expanded
	 * connector settings panel, next to its Save / Remove button.
	 *
	 * @param {Element} settings Element with class `connector-settings`.
	 */
	function attachToSettings( settings ) {
		if ( ! settings || settings.getAttribute( SETTINGS_MARKER ) === '1' ) {
			return;
		}

		var card = findConnectorCard( settings );
		if ( ! card ) {
			return;
		}

		var name = getConnectorNameFromCard( card );
		var connector = findConnectorByName( name );
		if ( ! isTestable( connector ) ) {
			settings.setAttribute( SETTINGS_MARKER, '1' );
			return;
		}

		// The action HStack is the last direct child of `.connector-settings`
		// and contains the Save / Remove button. Bail if it is not present.
		var actionRow = settings.lastElementChild;
		if ( ! actionRow || ! actionRow.querySelector( 'button' ) ) {
			return;
		}

		settings.setAttribute( SETTINGS_MARKER, '1' );

		var button   = createTestButton();
		var resultEl = createResultElement();

		button.addEventListener( 'click', function () {
			runTest( connector, button, resultEl );
		} );

		actionRow.appendChild( button );
	}

	/**
	 * Returns whether a node looks like the Connected badge.
	 *
	 * @param {Node} node Node to test.
	 * @return {boolean}
	 */
	function isConnectedBadge( node ) {
		if ( ! node || node.nodeType !== 1 || node.tagName !== 'SPAN' ) {
			return false;
		}
		// The badge has no class; identify it by exact text + parent location.
		if ( ( node.textContent || '' ).trim() !== BADGE_TEXT ) {
			return false;
		}
		// Must live inside a connectors card.
		return !! findConnectorCard( node );
	}

	/**
	 * Scans for existing Connected badges already present in the DOM.
	 *
	 * @param {Element} root Root element to scan.
	 */
	function scan( root ) {
		var spans = root.querySelectorAll( 'span' );
		for ( var i = 0; i < spans.length; i++ ) {
			if ( isConnectedBadge( spans[ i ] ) ) {
				attachToBadge( spans[ i ] );
			}
		}

		var settings = root.querySelectorAll( '.connector-settings' );
		for ( var k = 0; k < settings.length; k++ ) {
			attachToSettings( settings[ k ] );
		}
	}

	/**
	 * Initialises the observer once the connectors app is in the DOM.
	 */
	function init() {
		var app = document.getElementById( 'options-connectors-wp-admin-app' )
			|| document.body;

		scan( app );

		// Debounced re-scan: on every batch of mutations, run a single scan
		// of the whole app. Markers prevent re-attaching to already-handled
		// nodes, so this is cheap and immune to React rendering nodes in
		// stages or inside unexpected wrappers.
		var pendingScan = false;
		var requestScan = function () {
			if ( pendingScan ) {
				return;
			}
			pendingScan = true;
			window.requestAnimationFrame( function () {
				pendingScan = false;
				scan( app );
			} );
		};

		var observer = new MutationObserver( function ( mutations ) {
			requestScan();
			for ( var i = 0; i < mutations.length; i++ ) {
				var added = mutations[ i ].addedNodes;
				for ( var j = 0; j < added.length; j++ ) {
					var node = added[ j ];
					if ( node.nodeType !== 1 ) {
						continue;
					}
					if ( isConnectedBadge( node ) ) {
						attachToBadge( node );
					} else if ( node.classList && node.classList.contains( 'connector-settings' ) ) {
						attachToSettings( node );
					} else if ( node.querySelectorAll ) {
						scan( node );
					}
				}
			}
		} );

		observer.observe( app, { childList: true, subtree: true } );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )( window, document );
