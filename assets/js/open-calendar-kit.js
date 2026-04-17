/**
 * Frontend calendar interactions.
 *
 * @package OpenCalendarKit
 */

(function ($) {
	var __ = ( window.wp && window.wp.i18n && window.wp.i18n.__ )
		? window.wp.i18n.__
		: function ( text ) {
			return text;
		};

	function showModal() {
		var $modal = $( '.bkit-modal' );
		$modal.find( '.bkit-feedback' ).hide().text( '' );
		$modal.show().css( 'display', 'flex' );
	}

	function closeModal() {
		$( '.bkit-modal' ).hide();
	}

	function hideModalPanels() {
		var $modal = $( '.bkit-modal' );
		$modal.find( '.bkit-closed-info, .bkit-event-info' ).hide();
	}

	function openModalForClosed(dateISO, datePretty, reason) {
		var $modal = $( '.bkit-modal' );

		hideModalPanels();
		$modal.find( '.bkit-closed-info' ).show();
		$modal.find( '.bkit-closed-date' ).text( datePretty || '' );
		$modal.find( '.bkit-closed-reason' ).text( reason ? (__( 'Reason:', 'open-calendar-kit' ) + ' ' + reason) : '' );

		showModal();
	}

	function openModalForEvent(dateISO, datePretty, eventText) {
		var $modal = $( '.bkit-modal' );

		hideModalPanels();
		$modal.find( '.bkit-event-info' ).show();
		$modal.find( '.bkit-event-date' ).text( datePretty || '' );
		$modal.find( '.bkit-event-text' ).text( eventText || '' );

		showModal();
	}

	$( document ).on(
		'click',
		'.bkit-cell.day.clickable',
		function () {
			var date      = $( this ).data( 'date' ) || '';
			var reason    = $( this ).data( 'reason' ) || '';
			var eventText = $( this ).data( 'eventText' ) || '';
			var pretty;

			try {
				pretty = new Date( date + 'T00:00:00' ).toLocaleDateString(
					OPEN_CALENDAR_KIT.locale || undefined,
					{ year: 'numeric', month: 'long', day: 'numeric' }
				);
			} catch (error) {
				pretty = date;
			}

			if (eventText) {
				openModalForEvent( date, pretty, String( eventText ) );
				return;
			}

			openModalForClosed( date, pretty, reason );
		}
	);

	$( document ).on(
		'click',
		'.bkit-cancel, .bkit-modal .bkit-close',
		function (event) {
			event.preventDefault();
			closeModal();
		}
	);

	$( document ).on(
		'click',
		'.bkit-calendar .bkit-nav',
		function (event) {
			var $link     = $( this );
			var $calendar = $link.closest( '[data-openkit-calendar]' );
			var month     = String( $link.data( 'targetMonth' ) || '' );

			if ($link.hasClass( 'disabled' ) || ! $calendar.length || ! month) {
				return;
			}

			event.preventDefault();
			$calendar.addClass( 'is-loading' );

			$.post(
				OPEN_CALENDAR_KIT.ajax_url,
				{
					action: OPEN_CALENDAR_KIT.action,
					nonce: OPEN_CALENDAR_KIT.nonce,
					month: month,
					show_legend: String( $calendar.attr( 'data-show-legend' ) || '' ),
					week_starts_on: String( $calendar.attr( 'data-week-starts-on' ) || '' ),
					max_width: String( $calendar.attr( 'data-max-width' ) || '' )
				},
				function (response) {
					if (response && response.success && response.data && response.data.html) {
						$calendar.replaceWith( response.data.html );
					}
				}
			).always(
				function () {
					$( '.bkit-calendar.is-loading' ).removeClass( 'is-loading' );
				}
			);
		}
	);
})( jQuery );
