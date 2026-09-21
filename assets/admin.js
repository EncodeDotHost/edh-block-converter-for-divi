/* global wp, edhDiviGutenberg */
( function () {
	'use strict';

	const { apiFetch } = wp;
	const { __, sprintf } = wp.i18n;
	const base = edhDiviGutenberg.path;

	const rows = document.getElementById( 'edh-dg-rows' );
	const status = document.getElementById( 'edh-dg-status' );
	const progress = document.getElementById( 'edh-dg-progress' );
	const selectAll = document.getElementById( 'edh-dg-select-all' );
	const convertSelected = document.getElementById( 'edh-dg-convert-selected' );
	const restoreSelected = document.getElementById( 'edh-dg-restore-selected' );
	const preview = document.getElementById( 'edh-dg-preview' );

	let posts = [];
	let busy = false;

	function element( tag, text, attributes ) {
		const node = document.createElement( tag );
		if ( text ) {
			node.textContent = text;
		}
		Object.keys( attributes || {} ).forEach( ( name ) => node.setAttribute( name, attributes[ name ] ) );
		return node;
	}

	function selectedIds() {
		return Array.from( rows.querySelectorAll( 'input[type=checkbox]:checked' ) ).map( ( box ) => parseInt( box.value, 10 ) );
	}

	function updateToolbar() {
		const ids = selectedIds();
		const selected = posts.filter( ( post ) => ids.includes( post.id ) );
		convertSelected.disabled = busy || ! selected.some( ( post ) => ! post.converted );
		restoreSelected.disabled = busy || ! selected.some( ( post ) => post.converted );
	}

	function actionButton( label, handler ) {
		const button = element( 'button', label, { type: 'button', class: 'button button-small' } );
		button.addEventListener( 'click', handler );
		return button;
	}

	function renderRow( post ) {
		const row = element( 'tr' );

		const check = element( 'th', '', { scope: 'row', class: 'check-column' } );
		const box = element( 'input', '', { type: 'checkbox', value: String( post.id ), 'aria-label': sprintf( /* translators: %s: post title. */ __( 'Select %s', 'edh-divi-gutenberg' ), post.title ) } );
		box.addEventListener( 'change', updateToolbar );
		check.appendChild( box );
		row.appendChild( check );

		const title = element( 'td' );
		title.appendChild( element( 'a', post.title || '#' + post.id, { href: post.edit_url || '#' } ) );
		if ( post.view_url ) {
			title.appendChild( document.createTextNode( ' · ' ) );
			title.appendChild( element( 'a', __( 'View', 'edh-divi-gutenberg' ), { href: post.view_url, target: '_blank', rel: 'noopener' } ) );
		}
		row.appendChild( title );

		row.appendChild( element( 'td', post.type + ' (' + post.status + ')' ) );

		let state = post.converted ? __( 'Converted', 'edh-divi-gutenberg' ) : __( 'Divi', 'edh-divi-gutenberg' );
		if ( post.is_divi5 ) {
			state = __( 'Divi 5 (not supported at this time)', 'edh-divi-gutenberg' );
		}
		row.appendChild( element( 'td', state ) );
		row.appendChild( element( 'td', String( post.modules ) ) );
		row.appendChild( element( 'td', post.unsupported.join( ', ' ) ) );

		const actions = element( 'td', '', { class: 'edh-dg-actions' } );
		actions.appendChild( actionButton( __( 'Preview', 'edh-divi-gutenberg' ), () => showPreview( post ) ) );
		if ( post.converted ) {
			actions.appendChild( actionButton( __( 'Restore', 'edh-divi-gutenberg' ), () => run( 'restore', [ post.id ] ) ) );
		} else if ( ! post.is_divi5 ) {
			actions.appendChild( actionButton( __( 'Convert', 'edh-divi-gutenberg' ), () => run( 'convert', [ post.id ] ) ) );
		}
		row.appendChild( actions );

		return row;
	}

	function render() {
		rows.textContent = '';
		selectAll.checked = false;

		if ( ! posts.length ) {
			const row = element( 'tr' );
			row.appendChild( element( 'td', __( 'No posts use the Divi builder.', 'edh-divi-gutenberg' ), { colspan: '7' } ) );
			rows.appendChild( row );
		}
		posts.forEach( ( post ) => rows.appendChild( renderRow( post ) ) );
		updateToolbar();
	}

	async function scan() {
		status.textContent = __( 'Scan in progress…', 'edh-divi-gutenberg' );
		posts = [];

		try {
			let page = 1;
			let total = 0;
			do {
				const data = await apiFetch( { path: base + '/scan?per_page=100&page=' + page } );
				total = data.total;
				posts = posts.concat( data.posts );
				page++;
			} while ( posts.length < total && page < 200 );
			status.textContent = sprintf( /* translators: %d: number of posts. */ __( 'Posts found: %d.', 'edh-divi-gutenberg' ), posts.length );
		} catch ( error ) {
			status.textContent = error.message;
		}
		render();
	}

	async function showPreview( post ) {
		status.textContent = __( 'Preview in progress…', 'edh-divi-gutenberg' );
		try {
			const data = await apiFetch( { path: base + '/preview/' + post.id, method: 'POST' } );
			const list = document.getElementById( 'edh-dg-preview-report' );

			document.getElementById( 'edh-dg-preview-title' ).textContent = sprintf( /* translators: %s: post title. */ __( 'Preview: %s', 'edh-divi-gutenberg' ), post.title );
			document.getElementById( 'edh-dg-preview-markup' ).value = data.markup;
			list.textContent = '';

			if ( ! data.report.notices.length ) {
				list.appendChild( element( 'li', __( 'No notices. All modules have an equivalent.', 'edh-divi-gutenberg' ) ) );
			}
			data.report.notices.forEach( ( notice ) => {
				list.appendChild( element( 'li', '[' + notice.level + '] ' + notice.tag + ': ' + notice.message, { class: 'edh-dg-' + notice.level } ) );
			} );

			preview.hidden = false;
			preview.scrollIntoView( { behavior: 'smooth' } );
			status.textContent = '';
		} catch ( error ) {
			status.textContent = error.message;
		}
	}

	// One request for each post keeps each request short, and shows the progress.
	async function run( action, ids ) {
		if ( busy || ! ids.length ) {
			return;
		}
		if ( 'restore' === action && ! window.confirm( __( 'Restore the original Divi content of the selected posts?', 'edh-divi-gutenberg' ) ) ) { // eslint-disable-line no-alert
			return;
		}

		busy = true;
		updateToolbar();
		progress.hidden = false;
		progress.max = ids.length;
		progress.value = 0;

		const errors = [];
		for ( const id of ids ) {
			try {
				const data = await apiFetch( { path: base + '/' + action + '/' + id, method: 'POST' } );
				posts = posts.map( ( post ) => ( post.id === id && data.post ? data.post : post ) );
			} catch ( error ) {
				errors.push( '#' + id + ': ' + error.message );
			}
			progress.value++;
		}

		busy = false;
		progress.hidden = true;
		status.textContent = errors.length ? errors.join( ' ' ) : __( 'Done.', 'edh-divi-gutenberg' );
		render();
	}

	selectAll.addEventListener( 'change', () => {
		rows.querySelectorAll( 'input[type=checkbox]' ).forEach( ( box ) => {
			box.checked = selectAll.checked;
		} );
		updateToolbar();
	} );

	convertSelected.addEventListener( 'click', () => {
		const ids = selectedIds();
		run( 'convert', posts.filter( ( post ) => ids.includes( post.id ) && ! post.converted && ! post.is_divi5 ).map( ( post ) => post.id ) );
	} );

	restoreSelected.addEventListener( 'click', () => {
		const ids = selectedIds();
		run( 'restore', posts.filter( ( post ) => ids.includes( post.id ) && post.converted ).map( ( post ) => post.id ) );
	} );

	document.getElementById( 'edh-dg-refresh' ).addEventListener( 'click', scan );

	scan();
}() );
