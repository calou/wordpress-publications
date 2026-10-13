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
		let activeId = null;

		for ( const section of sections ) {
			// Skip year groups hidden by the client-side filter.
			if ( section.offsetParent === null ) {
				continue;
			}
			const top = section.getBoundingClientRect().top + window.scrollY;
			if ( top <= triggerAt ) {
				activeId = section.id;
			} else {
				break;
			}
		}

		if ( activeId === null ) {
			const firstVisible = sections.find(
				( section ) => section.offsetParent !== null
			);
			activeId = firstVisible ? firstVisible.id : sections[ 0 ].id;
		}

		// When scrolled to the very bottom, always highlight the last visible year.
		const lastVisible = [ ...sections ]
			.reverse()
			.find( ( section ) => section.offsetParent !== null );

		if (
			lastVisible &&
			window.innerHeight + window.scrollY >=
				document.documentElement.scrollHeight - 2
		) {
			activeId = lastVisible.id;
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

/**
 * Initialize the client-side search filter for a single Publications block.
 *
 * @param {HTMLElement} block The block wrapper element.
 */
function initFilter( block ) {
	const input = block.querySelector( '.wp-publications-filter-input' );
	if ( ! input ) {
		return;
	}

	const items = Array.from( block.querySelectorAll( '.wp-publications-item' ) );
	const yearGroups = Array.from(
		block.querySelectorAll( '.wp-publications-year-group' )
	);
	const timelineLinks = Array.from(
		block.querySelectorAll( '.wp-publications-timeline-link' )
	);
	const noResults = block.querySelector( '.wp-publications-filter-empty' );

	if ( items.length === 0 ) {
		return;
	}

	const linkForGroup = ( group ) =>
		timelineLinks.find(
			( link ) => link.getAttribute( 'href' ) === '#' + group.id
		);

	/**
	 * Apply the current search term to the list.
	 */
	function applyFilter() {
		const query = input.value.trim().toLowerCase();
		let visible = 0;

		items.forEach( ( item ) => {
			const matches =
				query === '' || item.textContent.toLowerCase().includes( query );
			item.classList.toggle( 'is-hidden', ! matches );
			if ( matches ) {
				visible++;
			}
		} );

		// Hide any year group (and its timeline link) that has no matches.
		yearGroups.forEach( ( group ) => {
			const hasVisible = Boolean(
				group.querySelector( '.wp-publications-item:not(.is-hidden)' )
			);
			group.classList.toggle( 'is-hidden', ! hasVisible );

			// Update the publication count shown next to the year in the body.
			const visibleCount = group.querySelectorAll(
				'.wp-publications-item:not(.is-hidden)'
			).length;
			const countEl = group.querySelector( '.wp-publications-year-count' );
			if ( countEl ) {
				countEl.textContent = '(' + visibleCount + ')';
			}

			const link = linkForGroup( group );
			if ( link ) {
				link.classList.toggle( 'is-hidden', ! hasVisible );

				// Update the publication count shown next to the year in the timeline.
				const linkCount = link.querySelector( '.wp-publications-timeline-count' );
				if ( linkCount ) {
					linkCount.textContent = '(' + visibleCount + ')';
				}
			}
		} );

		if ( noResults ) {
			noResults.classList.toggle( 'is-hidden', visible > 0 );
		}
	}

	let timer;
	input.addEventListener( 'input', () => {
		window.clearTimeout( timer );
		timer = window.setTimeout( applyFilter, 120 );
	} );

	applyFilter();
}

document.addEventListener( 'DOMContentLoaded', () => {
	document
		.querySelectorAll( '.wp-block-create-block-publications' )
		.forEach( ( block ) => {
			initTimeline( block );
			initFilter( block );
		} );
} );
