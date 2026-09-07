/**
 * AI Provider Connectors — Settings → AI Providers screen.
 *
 * Progressive enhancement over the server-rendered page: tabs, enable/disable
 * switches, live connection checks, the custom-provider form, and the priority list.
 *
 * @package BAIProviderConnectors
 */
( function ( window, document ) {
	'use strict';

	var cfg = window.baioapAdmin;
	if ( ! cfg || ! window.wp || ! window.wp.apiFetch ) {
		return;
	}

	var apiFetch = window.wp.apiFetch;
	var i18n     = window.wp.i18n || {};
	var TD       = 'b-ai-provider-connectors';
	var __       = i18n.__ ? function ( s ) { return i18n.__( s, TD ); } : function ( s ) { return s; };
	var sprintf  = i18n.sprintf || function ( s ) {
		var args = Array.prototype.slice.call( arguments, 1 ), i = 0;
		return s.replace( /%[sd]/g, function () { return args[ i++ ]; } );
	};

	function qs( sel, root ) { return ( root || document ).querySelector( sel ); }
	function qsa( sel, root ) { return Array.prototype.slice.call( ( root || document ).querySelectorAll( sel ) ); }
	function api( path, method, data ) {
		return apiFetch( { path: cfg.restBase + path, method: method || 'GET', data: data } );
	}
	function errorMessage( e ) {
		return ( e && e.message ) || __( 'Something went wrong. Please try again.' );
	}

	/* ------------------------------------------------------------------ */
	/* Notices                                                              */
	/* ------------------------------------------------------------------ */

	var noticesEl = qs( '#baioap-notices' );

	function notice( type, text ) {
		if ( ! noticesEl ) {
			return;
		}
		var el = document.createElement( 'div' );
		el.className = 'notice notice-' + type + ' is-dismissible';
		var p = document.createElement( 'p' );
		p.textContent = text;
		el.appendChild( p );
		var btn = document.createElement( 'button' );
		btn.type = 'button';
		btn.className = 'notice-dismiss';
		btn.innerHTML = '<span class="screen-reader-text">' + __( 'Dismiss this notice.' ) + '</span>';
		btn.addEventListener( 'click', function () { el.remove(); } );
		el.appendChild( btn );
		noticesEl.innerHTML = '';
		noticesEl.appendChild( el );
		if ( 'success' === type ) {
			window.setTimeout( function () { if ( el.parentNode ) { el.remove(); } }, 6000 );
		}
	}

	// A flash message stored before a reload.
	try {
		var flash = window.sessionStorage.getItem( 'baioapFlash' );
		if ( flash ) {
			window.sessionStorage.removeItem( 'baioapFlash' );
			notice( 'success', flash );
		}
	} catch ( e ) { /* storage unavailable */ }

	function reloadWithFlash( tab, text ) {
		try { window.sessionStorage.setItem( 'baioapFlash', text ); } catch ( e ) { /* ignore */ }
		window.location.href = cfg.pageUrl + '&tab=' + encodeURIComponent( tab );
	}

	/* ------------------------------------------------------------------ */
	/* Tabs                                                                 */
	/* ------------------------------------------------------------------ */

	function activateTab( name ) {
		qsa( '.baioap-tab' ).forEach( function ( section ) {
			section.hidden = section.dataset.tab !== name;
		} );
		qsa( '.baioap-tabs .nav-tab' ).forEach( function ( link ) {
			var active = link.dataset.tab === name;
			link.classList.toggle( 'nav-tab-active', active );
			if ( active ) { link.setAttribute( 'aria-current', 'page' ); } else { link.removeAttribute( 'aria-current' ); }
		} );
		if ( window.history && window.history.replaceState ) {
			window.history.replaceState( null, '', cfg.pageUrl + '&tab=' + encodeURIComponent( name ) );
		}
	}

	qsa( '.baioap-tabs .nav-tab' ).forEach( function ( link ) {
		link.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			activateTab( link.dataset.tab );
		} );
	} );

	/* ------------------------------------------------------------------ */
	/* Provider cards                                                       */
	/* ------------------------------------------------------------------ */

	function setBadge( card, state, text, message ) {
		var badge = qs( '[data-role="badge"]', card );
		var msg   = qs( '[data-role="message"]', card );
		if ( badge ) {
			badge.className = 'baioap-badge is-' + state;
			badge.textContent = text;
		}
		if ( msg ) {
			msg.textContent = message || '';
		}
	}

	function restingBadge( card ) {
		if ( '0' === card.dataset.enabled ) {
			setBadge( card, 'muted', __( 'Disabled' ) );
		} else if ( 'none' === card.dataset.keySource ) {
			setBadge( card, 'idle', __( 'No API key' ) );
		} else {
			setBadge( card, 'info', __( 'Key set' ) );
		}
	}

	function testCard( card, silent ) {
		var id = card.dataset.id;
		card.classList.add( 'is-testing' );
		setBadge( card, 'checking', __( 'Checking…' ) );

		return api( 'test/' + encodeURIComponent( id ), 'POST' ).then( function ( r ) {
			if ( r && r.success ) {
				setBadge( card, 'success', __( 'Connected' ), silent ? '' : r.message );
			} else if ( r && 'missing_key' === r.status ) {
				setBadge( card, 'idle', __( 'No API key' ), silent ? '' : r.message );
			} else if ( r && 'invalid' === r.status ) {
				setBadge( card, 'error', __( 'Invalid key' ), r.message );
			} else {
				setBadge( card, 'error', __( 'Not reachable' ), ( r && r.message ) || '' );
			}
		} ).catch( function ( e ) {
			setBadge( card, 'error', __( 'Error' ), errorMessage( e ) );
		} ).finally( function () {
			card.classList.remove( 'is-testing' );
		} );
	}

	// Check every enabled provider that has a key, one at a time, on load.
	function autoCheck() {
		var queue = qsa( '.baioap-card[data-enabled="1"]' ).filter( function ( c ) {
			return 'none' !== c.dataset.keySource;
		} );
		( function next() {
			var card = queue.shift();
			if ( card ) {
				testCard( card, true ).then( next, next );
			}
		} )();
	}

	function updateStats() {
		var managed = qsa( '.baioap-card[data-source="bundled"], .baioap-card[data-source="custom"]' );
		var enabled = managed.filter( function ( c ) { return '1' === c.dataset.enabled; } ).length;
		var keyed   = qsa( '.baioap-card' ).filter( function ( c ) { return 'none' !== c.dataset.keySource; } ).length;
		var el;
		if ( ( el = qs( '[data-stat="enabled"]' ) ) ) { el.textContent = enabled; }
		if ( ( el = qs( '[data-stat="keyed"]' ) ) ) { el.textContent = keyed; }
	}

	function applyRow( card, row ) {
		card.dataset.enabled   = row.enabled ? '1' : '0';
		card.dataset.keySource = row.keySource || 'none';
		card.classList.toggle( 'is-disabled', ! row.enabled );
		var test = qs( '.baioap-test', card );
		if ( test ) { test.disabled = ! row.enabled; }
		restingBadge( card );
		updateStats();
	}

	document.addEventListener( 'change', function ( e ) {
		var input = e.target.closest( '.baioap-switch__input' );
		if ( ! input ) {
			return;
		}
		var card    = input.closest( '.baioap-card' );
		var enabled = input.checked;
		var name    = card.dataset.name;
		input.disabled = true;

		api( 'providers/' + encodeURIComponent( card.dataset.id ), 'POST', { enabled: enabled } ).then( function ( r ) {
			applyRow( card, r.row );
			notice( 'success', enabled
				? sprintf( __( '%s is enabled and now appears on the Connectors screen.' ), name )
				: sprintf( __( '%s is disabled. It is hidden from the Connectors screen and will not be used.' ), name ) );
			if ( enabled && 'none' !== card.dataset.keySource ) {
				testCard( card, true );
			}
		} ).catch( function ( err ) {
			input.checked = ! enabled;
			notice( 'error', errorMessage( err ) );
		} ).finally( function () {
			input.disabled = false;
		} );
	} );

	document.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest( '.baioap-test' );
		if ( btn ) {
			testCard( btn.closest( '.baioap-card' ), false );
		}
	} );

	/* ------------------------------------------------------------------ */
	/* Custom providers                                                     */
	/* ------------------------------------------------------------------ */

	var panel  = qs( '#baioap-custom-panel' );
	var form   = panel ? qs( '#baioap-custom-form', panel ) : null;
	var addBtn = qs( '#baioap-add-custom' );

	function field( name ) { return form ? form.elements[ name ] : null; }
	function fieldWrap( name ) { var f = field( name ); return f ? f.closest( '.baioap-field' ) : null; }

	function clearErrors() {
		qsa( '.baioap-field', form ).forEach( function ( w ) {
			w.classList.remove( 'is-invalid' );
			var err = qs( '[data-role="error"]', w );
			if ( err ) { err.textContent = ''; }
		} );
	}

	function showFieldError( name, text ) {
		var w = fieldWrap( name );
		if ( ! w ) {
			notice( 'error', text );
			return;
		}
		w.classList.add( 'is-invalid' );
		var err = qs( '[data-role="error"]', w );
		if ( err ) { err.textContent = text; }
		field( name ).focus();
	}

	function slugify( s ) {
		return s.toLowerCase().replace( /[^a-z0-9]+/g, '-' ).replace( /^-+|-+$/g, '' ).slice( 0, 40 );
	}

	var idTouched = false;

	function openPanel( card ) {
		if ( ! form ) {
			return;
		}
		clearErrors();
		form.reset();
		idTouched = false;
		var editing = !! card;
		form.dataset.slot = editing ? card.dataset.slot : '';
		qs( '[data-role="panel-title"]', form ).textContent = editing
			? sprintf( __( 'Edit %s' ), card.dataset.name )
			: __( 'Add a custom provider' );
		field( 'id' ).readOnly = editing;
		if ( editing ) {
			field( 'name' ).value            = card.dataset.name;
			field( 'id' ).value              = card.dataset.id;
			field( 'base_url' ).value        = card.dataset.baseUrl;
			field( 'credentials_url' ).value = card.dataset.credentialsUrl;
			field( 'description' ).value     = card.dataset.description;
		}
		panel.hidden = false;
		panel.scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
		field( 'name' ).focus();
	}

	function closePanel() {
		if ( panel ) {
			panel.hidden = true;
		}
	}

	if ( form ) {
		if ( addBtn ) {
			addBtn.addEventListener( 'click', function () { openPanel( null ); } );
		}
		qs( '[data-role="cancel"]', form ).addEventListener( 'click', closePanel );

		field( 'id' ).addEventListener( 'input', function () { idTouched = true; } );
		field( 'name' ).addEventListener( 'input', function () {
			if ( ! idTouched && ! field( 'id' ).readOnly ) {
				field( 'id' ).value = slugify( field( 'name' ).value );
			}
		} );

		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			clearErrors();

			var slot = form.dataset.slot;
			var data = {
				name:            field( 'name' ).value.trim(),
				id:              field( 'id' ).value.trim(),
				base_url:        field( 'base_url' ).value.trim(),
				credentials_url: field( 'credentials_url' ).value.trim(),
				description:     field( 'description' ).value.trim()
			};

			if ( ! data.name ) { showFieldError( 'name', __( 'Please enter a name.' ) ); return; }
			if ( ! /^[a-z0-9][a-z0-9_-]{1,39}$/.test( data.id ) ) { showFieldError( 'id', __( 'Use 2–40 lowercase letters, numbers, hyphens or underscores.' ) ); return; }
			if ( ! /^https?:\/\/\S+$/i.test( data.base_url ) ) { showFieldError( 'base_url', __( 'Enter a full URL starting with http:// or https://.' ) ); return; }

			form.classList.add( 'is-busy' );
			qs( '[data-role="submit"]', form ).disabled = true;

			api( slot ? 'custom/' + slot : 'custom', slot ? 'PUT' : 'POST', data ).then( function ( r ) {
				reloadWithFlash( 'custom', slot
					? sprintf( __( '%s was updated.' ), data.name )
					: sprintf( __( '%s was added. Next, save its API key on the Connectors screen.' ), data.name ) );
			} ).catch( function ( err ) {
				form.classList.remove( 'is-busy' );
				qs( '[data-role="submit"]', form ).disabled = false;
				if ( err && err.data && err.data.field ) {
					showFieldError( err.data.field, errorMessage( err ) );
				} else {
					notice( 'error', errorMessage( err ) );
				}
			} );
		} );

		document.addEventListener( 'click', function ( e ) {
			var edit = e.target.closest( '.baioap-edit' );
			if ( edit ) {
				openPanel( edit.closest( '.baioap-card' ) );
				return;
			}
			var del = e.target.closest( '.baioap-delete' );
			if ( del ) {
				var card = del.closest( '.baioap-card' );
				if ( ! window.confirm( sprintf( __( 'Delete %s? Its saved API key will be removed as well.' ), card.dataset.name ) ) ) {
					return;
				}
				del.disabled = true;
				api( 'custom/' + card.dataset.slot, 'DELETE' ).then( function () {
					reloadWithFlash( 'custom', sprintf( __( '%s was deleted.' ), card.dataset.name ) );
				} ).catch( function ( err ) {
					del.disabled = false;
					notice( 'error', errorMessage( err ) );
				} );
			}
		} );
	}

	/* ------------------------------------------------------------------ */
	/* Priority                                                             */
	/* ------------------------------------------------------------------ */

	var list     = qs( '#baioap-priority' );
	var saveBtn  = qs( '#baioap-priority-save' );
	var resetBtn = qs( '#baioap-priority-reset' );
	var hint     = qs( '#baioap-priority-hint' );
	var dirty    = false;

	function renumber() {
		qsa( '.baioap-priority__item', list ).forEach( function ( li, i ) {
			qs( '[data-role="rank"]', li ).textContent = i + 1;
		} );
	}

	function markDirty() {
		dirty = true;
		if ( saveBtn ) { saveBtn.disabled = false; }
		if ( hint ) { hint.textContent = __( 'Unsaved changes.' ); }
	}

	if ( list ) {
		var dragging = null;

		list.addEventListener( 'dragstart', function ( e ) {
			dragging = e.target.closest( '.baioap-priority__item' );
			if ( ! dragging ) { return; }
			dragging.classList.add( 'is-dragging' );
			e.dataTransfer.effectAllowed = 'move';
			try { e.dataTransfer.setData( 'text/plain', dragging.dataset.id ); } catch ( err ) { /* IE */ }
		} );

		list.addEventListener( 'dragover', function ( e ) {
			if ( ! dragging ) { return; }
			e.preventDefault();
			var target = e.target.closest( '.baioap-priority__item' );
			if ( ! target || target === dragging ) { return; }
			var rect  = target.getBoundingClientRect();
			var after = ( e.clientY - rect.top ) > rect.height / 2;
			list.insertBefore( dragging, after ? target.nextSibling : target );
		} );

		list.addEventListener( 'drop', function ( e ) { e.preventDefault(); } );

		list.addEventListener( 'dragend', function () {
			if ( ! dragging ) { return; }
			dragging.classList.remove( 'is-dragging' );
			dragging = null;
			renumber();
			markDirty();
		} );

		list.addEventListener( 'click', function ( e ) {
			var up = e.target.closest( '.baioap-up' );
			var down = e.target.closest( '.baioap-down' );
			if ( ! up && ! down ) { return; }
			var li = e.target.closest( '.baioap-priority__item' );
			if ( up && li.previousElementSibling ) {
				list.insertBefore( li, li.previousElementSibling );
			} else if ( down && li.nextElementSibling ) {
				list.insertBefore( li.nextElementSibling, li );
			} else {
				return;
			}
			( up || down ).focus();
			renumber();
			markDirty();
		} );

		if ( saveBtn ) {
			saveBtn.addEventListener( 'click', function () {
				var order = qsa( '.baioap-priority__item', list ).map( function ( li ) { return li.dataset.id; } );
				saveBtn.disabled = true;
				api( 'priority', 'POST', { order: order } ).then( function () {
					dirty = false;
					if ( hint ) { hint.textContent = ''; }
					if ( resetBtn ) { resetBtn.disabled = false; }
					notice( 'success', __( 'Provider order saved.' ) );
				} ).catch( function ( err ) {
					saveBtn.disabled = false;
					notice( 'error', errorMessage( err ) );
				} );
			} );
		}

		if ( resetBtn ) {
			resetBtn.addEventListener( 'click', function () {
				if ( ! window.confirm( __( 'Reset the order to the default (the order providers were registered in)?' ) ) ) {
					return;
				}
				resetBtn.disabled = true;
				api( 'priority', 'DELETE' ).then( function () {
					reloadWithFlash( 'priority', __( 'Provider order reset to default.' ) );
				} ).catch( function ( err ) {
					resetBtn.disabled = false;
					notice( 'error', errorMessage( err ) );
				} );
			} );
		}

		window.addEventListener( 'beforeunload', function ( e ) {
			if ( dirty ) {
				e.preventDefault();
				e.returnValue = '';
			}
		} );
	}

	/* ------------------------------------------------------------------ */
	/* Videos                                                               */
	/* ------------------------------------------------------------------ */

	// Only contact YouTube once the visitor presses play.
	qsa( '[data-baioap-video]' ).forEach( function ( facade ) {
		facade.addEventListener( 'click', function () {
			var id    = facade.getAttribute( 'data-baioap-video' );
			var frame = document.createElement( 'iframe' );
			var label = facade.querySelector( '.screen-reader-text' );

			frame.src = 'https://www.youtube-nocookie.com/embed/' + encodeURIComponent( id ) +
				'?autoplay=1&rel=0&modestbranding=1&origin=' + encodeURIComponent( window.location.origin );
			frame.title = label ? label.textContent : 'Video';
			frame.allow = 'accelerometer; autoplay; encrypted-media; picture-in-picture; web-share';
			frame.allowFullscreen = true;
			// Sites sending "Referrer-Policy: same-origin" otherwise break the player (Error 153).
			frame.referrerPolicy = 'strict-origin-when-cross-origin';

			facade.parentNode.replaceChild( frame, facade );
		} );
	} );

	// Show / hide strip. State is stored per user on the server so it survives reloads.
	( function () {
		var panel = qs( '[data-baioap-videos]' );
		if ( ! panel ) { return; }

		var toggle  = panel.querySelector( '[data-baioap-videos-toggle]' );
		var label   = panel.querySelector( '[data-baioap-videos-label]' );
		var grid    = panel.querySelector( '.baioap-videos__grid' );
		var note    = panel.querySelector( '.baioap-videos__note' );
		var strings = cfg.i18n || {};

		if ( ! toggle || ! grid ) { return; }

		toggle.addEventListener( 'click', function () {
			var hidden = ! panel.classList.contains( 'is-collapsed' );

			panel.classList.toggle( 'is-collapsed', hidden );
			grid.hidden = hidden;
			if ( note ) { note.hidden = hidden; }
			toggle.setAttribute( 'aria-expanded', hidden ? 'false' : 'true' );
			if ( label ) {
				label.textContent = hidden ? ( strings.show || 'Show videos' ) : ( strings.hide || 'Hide videos' );
			}

			// Stop any playing embed when the strip is closed.
			if ( hidden ) {
				qsa( 'iframe', grid ).forEach( function ( frame ) { frame.src = frame.src; } );
			}

			if ( ! cfg.ajaxUrl || ! cfg.videosNonce ) { return; }

			var body = new window.FormData();
			body.append( 'action', 'baioap_toggle_videos' );
			body.append( '_wpnonce', cfg.videosNonce );
			body.append( 'hidden', hidden ? '1' : '0' );
			window.fetch( cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body } ).catch( function () {} );
		} );
	}() );

	/* ------------------------------------------------------------------ */
	/* Init                                                                 */
	/* ------------------------------------------------------------------ */

	activateTab( cfg.tab || 'providers' );
	autoCheck();
} )( window, document );
