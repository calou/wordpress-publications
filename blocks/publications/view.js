/**
 * Frontend view script for the Publications block.
 *
 * Implements a "scrollspy" year timeline:
 *  - lists every year as an anchor link,
 *  - highlights the year currently visible in the viewport,
 *  - smooth-scrolls to a year on click.
 */

const ACTIVE_CLASS = 'is-active';

/**
 * Initialize the timeline for a single Publications block.
 *
 * @param {HTMLElement} block The block wrapper element.
 */
function initTimeline( block ) {
	const timeline = block.querySelector( '.wp-publications-timeline' );
	if ( ! timeline ) {
		return;
	}

	const links = Array.from(
		timeline.querySelectorAll( '.wp-publications-timeline-link' )
	);

	// A timeline is only useful when there is more than one year to jump to.
	if ( links.length < 2 ) {
		timeline.style.display = 'none';
		return;
	}

	const sections = links
		.map( ( link ) => link.getAttribute( 'href' ) )
		.map( ( hash ) => document.getElementById( hash.slice( 1 ) ) )
		.filter( Boolean );

	if ( sections.length === 0 ) {
		return;
	}

	let currentId = null;

	/**
	 * Toggle the active state on a single timeline link.
	 *
	 * @param {string} id Target section id.
	 */
	function setActive( id ) {
		if ( currentId === id ) {
			return;
		}
		currentId = id;

		links.forEach( ( link ) => {
			const isActive = link.getAttribute( 'href' ) === '#' + id;
			link.classList.toggle( ACTIVE_CLASS, isActive );
			if ( isActive ) {
				link.setAttribute( 'aria-current', 'true' );
			} else {
				link.removeAttribute( 'aria-current' );
			}
		} );
	}

	/**
	 * Determine which year is currently visible.
	 * Uses the top of each section relative to the viewport.
	 */
	function updateActive() {
		// Trigger once the section top passes roughly the upper quarter of the viewport.
		const triggerAt = window.scrollY + window.innerHeight * 0.25;
		let activeId = sections[ 0 ].id;

		for ( const section of sections ) {
			const top = section.getBoundingClientRect().top + window.scrollY;
			if ( top <= triggerAt ) {
				activeId = section.id;
			} else {
				break;
			}
		}

		// When scrolled to the very bottom, always highlight the last year.
		if (
			window.innerHeight + window.scrollY >=
			document.documentElement.scrollHeight - 2
		) {
			activeId = sections[ sections.length - 1 ].id;
		}

		setActive( activeId );
	}

	// Smooth-scroll navigation on click.
	links.forEach( ( link ) => {
		link.addEventListener( 'click', ( event ) => {
			const hash = link.getAttribute( 'href' );
			const target = document.getElementById( hash.slice( 1 ) );
			if ( ! target ) {
				return;
			}

			event.preventDefault();
			target.scrollIntoView( { behavior: 'smooth', block: 'start' } );
			setActive( target.id );

			if ( window.history && window.history.pushState ) {
				window.history.pushState( null, '', hash );
			}
		} );
	} );

	const onScroll = () => {
		window.requestAnimationFrame( updateActive );
	};

	window.addEventListener( 'scroll', onScroll, { passive: true } );
	window.addEventListener( 'resize', onScroll, { passive: true } );
	updateActive();
}

document.addEventListener( 'DOMContentLoaded', () => {
	document
		.querySelectorAll( '.wp-block-create-block-publications' )
		.forEach( initTimeline );
} );
