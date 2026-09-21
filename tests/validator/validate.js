/**
 * Checks block markup with the validator of the block editor.
 *
 * Usage: node validate.js <file.html>...
 * Exit code 1 when a block is not valid.
 */
const { VirtualConsole } = require( 'jsdom' );

// A silent virtual console hides the stylesheet messages of jsdom.
require( 'global-jsdom' )( undefined, { url: 'http://localhost/', virtualConsole: new VirtualConsole() } );

const noop = () => {};
window.matchMedia = window.matchMedia || ( () => ( { matches: false, addListener: noop, removeListener: noop, addEventListener: noop, removeEventListener: noop } ) );
global.ResizeObserver = global.ResizeObserver || class { observe() {} unobserve() {} disconnect() {} };
global.IntersectionObserver = global.IntersectionObserver || class { observe() {} unobserve() {} disconnect() {} };
global.MutationObserver = window.MutationObserver;
global.requestIdleCallback = global.requestIdleCallback || ( ( callback ) => setTimeout( callback, 0 ) );
global.CSS = global.CSS || { supports: () => false, escape: ( value ) => value };

// The validator logs each difference. The script prints its own summary.
const logged = [];
[ 'error', 'warn', 'info', 'log' ].forEach( ( level ) => {
	const original = console[ level ];
	console[ level ] = ( ...args ) => {
		if ( process.env.VERBOSE ) {
			original( ...args );
		}
		logged.push( args.map( String ).join( ' ' ) );
	};
} );

const fs = require( 'fs' );
const { parse, validateBlock } = require( '@wordpress/blocks' );
const { registerCoreBlocks } = require( '@wordpress/block-library' );

registerCoreBlocks();

function check( blocks, path, failures ) {
	blocks.forEach( ( block, index ) => {
		const here = path + '/' + block.name + '[' + index + ']';
		const before = logged.length;
		const [ isValid ] = validateBlock( block );

		if ( ! isValid || ! block.isValid || 'core/missing' === block.name || 'core/freeform' === block.name ) {
			failures.push( { here, detail: logged.slice( before ).join( '\n' ) } );
		}
		check( block.innerBlocks, here, failures );
	} );
}

let failed = false;

process.argv.slice( 2 ).forEach( ( file ) => {
	const failures = [];
	check( parse( fs.readFileSync( file, 'utf8' ) ), '', failures );

	if ( failures.length ) {
		failed = true;
		process.stdout.write( 'FAIL ' + file + '\n' );
		failures.forEach( ( failure ) => process.stdout.write( '  ' + failure.here + '\n' + failure.detail.replace( /^/gm, '      ' ) + '\n' ) );
	} else {
		process.stdout.write( 'ok   ' + file + '\n' );
	}
} );

process.exit( failed ? 1 : 0 );
