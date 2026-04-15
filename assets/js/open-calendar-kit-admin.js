/**
 * Admin calendar interactions.
 *
 * @package OpenCalendarKit
 */

(function ($) {
	function openModal(dateISO, reason, closedEvent, closedRule, openOverride) {
		var $modal               = $( '#bkit-closedday-modal' );
		var $openExceptionButton = $( '#bkit-toggle-open-exception' );

		$modal.data( 'date', dateISO || '' );
		$modal.data( 'closedEvent', closedEvent ? 1 : 0 );
		$modal.data( 'closedRule', closedRule ? 1 : 0 );
		$modal.data( 'openOverride', openOverride ? 1 : 0 );

		$modal.find( 'input[name="date"]' ).val( dateISO || '' );
		$modal.find( 'input[name="reason"]' ).val( reason || '' );
		$modal.find( '.bkit-feedback' ).hide().text( '' );

		if (closedEvent && ! closedRule) {
			$( '#bkit-open-day' ).show();
		} else {
			$( '#bkit-open-day' ).hide();
		}

		if (closedRule) {
			$openExceptionButton
			.text( openOverride ? OPEN_CALENDAR_KIT_ADMIN.remove_exceptional_opening : OPEN_CALENDAR_KIT_ADMIN.open_day_exceptionally )
			.show();
		} else {
			$openExceptionButton.hide().text( '' );
		}

		$modal.show().css( 'display', 'flex' );
	}

	function closeModal() {
		$( '#bkit-closedday-modal' ).hide();
	}

	function reloadAdminCalendar(month) {
		var $root = $( '[data-openkit-admin-calendar-root]' );
		if ( ! $root.length || ! month) {
			return;
		}

		$root.addClass( 'is-loading' );

		$.post(
			ajaxurl,
			{
				action: OPEN_CALENDAR_KIT_ADMIN.action,
				nonce: OPEN_CALENDAR_KIT_ADMIN.nonce,
				month: month
			},
			function (response) {
				if (response && response.success && response.data && response.data.html) {
					$root.replaceWith( response.data.html );
				}
			}
		).always(
			function () {
				$( '[data-openkit-admin-calendar-root].is-loading' ).removeClass( 'is-loading' );
			}
		);
	}

	$( document ).on(
		'click',
		'.bkit-admin-cal .bkit-cell.day',
		function () {
			var date         = $( this ).data( 'date' ) || '';
			var reason       = $( this ).data( 'reason' ) || '';
			var closedEvent  = String( $( this ).data( 'closedEvent' ) || '0' ) === '1';
			var closedRule   = String( $( this ).data( 'closedRule' ) || '0' ) === '1';
			var openOverride = String( $( this ).data( 'openOverride' ) || '0' ) === '1';

			if ( ! date) {
				return;
			}

			openModal( String( date ), String( reason ), closedEvent, closedRule, openOverride );
		}
	);

	$( document ).on(
		'click',
		'#bkit-closedday-modal .bkit-close, #bkit-cancel',
		function (event) {
			event.preventDefault();
			closeModal();
		}
	);

	$( document ).on(
		'click',
		'.bkit-admin-cal .bkit-nav',
		function (event) {
			var month = String( $( this ).data( 'targetMonth' ) || '' );
			if ( ! month) {
				return;
			}

			event.preventDefault();
			reloadAdminCalendar( month );
		}
	);

	$( document ).on(
		'submit',
		'#bkit-closedday-form',
		function (event) {
			event.preventDefault();

			var $modal    = $( '#bkit-closedday-modal' );
			var $feedback = $modal.find( '.bkit-feedback' );
			var date      = $( this ).find( 'input[name="date"]' ).val();
			var month     = String( date || '' ).slice( 0, 7 );

			$.post(
				ajaxurl,
				{
					action: OPEN_CALENDAR_KIT_ADMIN.save_closed_day_action,
					nonce: OPEN_CALENDAR_KIT_ADMIN.nonce,
					date: date,
					reason: $( this ).find( 'input[name="reason"]' ).val()
				},
				function (response) {
					if (response && response.success) {
						$feedback.text( response.data.msg ).css( 'color', '#2ecc71' ).show();
						setTimeout(
							function () {
								closeModal();
								reloadAdminCalendar( month );
							},
							300
						);
					} else {
						var message = (response && response.data && response.data.msg) || OPEN_CALENDAR_KIT_ADMIN.generic_error;
						$feedback.text( message ).css( 'color', '#e74c3c' ).show();
					}
				}
			).fail(
				function () {
					$feedback.text( OPEN_CALENDAR_KIT_ADMIN.generic_error ).css( 'color', '#e74c3c' ).show();
				}
			);
		}
	);

	$( document ).on(
		'click',
		'#bkit-open-day',
		function (event) {
			event.preventDefault();

			if ( ! confirm( OPEN_CALENDAR_KIT_ADMIN.confirm_reopen )) {
				return;
			}

			var $modal    = $( '#bkit-closedday-modal' );
			var $feedback = $modal.find( '.bkit-feedback' );
			var date      = String( $modal.data( 'date' ) || $modal.find( 'input[name="date"]' ).val() || '' );
			var month     = date.slice( 0, 7 );

			$.post(
				ajaxurl,
				{
					action: OPEN_CALENDAR_KIT_ADMIN.delete_closed_day_action,
					nonce: OPEN_CALENDAR_KIT_ADMIN.nonce,
					date: date
				},
				function (response) {
					if (response && response.success) {
						$feedback.text( response.data.msg ).css( 'color', '#2ecc71' ).show();
						setTimeout(
							function () {
								closeModal();
								reloadAdminCalendar( month );
							},
							300
						);
					} else {
						var message = (response && response.data && response.data.msg) || OPEN_CALENDAR_KIT_ADMIN.generic_error;
						$feedback.text( message ).css( 'color', '#e74c3c' ).show();
					}
				}
			).fail(
				function () {
					$feedback.text( OPEN_CALENDAR_KIT_ADMIN.generic_error ).css( 'color', '#e74c3c' ).show();
				}
			);
		}
	);

	$( document ).on(
		'click',
		'#bkit-toggle-open-exception',
		function (event) {
			event.preventDefault();

			var $modal       = $( '#bkit-closedday-modal' );
			var $feedback    = $modal.find( '.bkit-feedback' );
			var date         = String( $modal.data( 'date' ) || $modal.find( 'input[name="date"]' ).val() || '' );
			var month        = date.slice( 0, 7 );
			var openOverride = String( $modal.data( 'openOverride' ) || '0' ) === '1';

			if (openOverride && ! confirm( OPEN_CALENDAR_KIT_ADMIN.confirm_remove_exceptional_opening )) {
				return;
			}

			$.post(
				ajaxurl,
				{
					action: openOverride ? OPEN_CALENDAR_KIT_ADMIN.delete_open_exception_action : OPEN_CALENDAR_KIT_ADMIN.save_open_exception_action,
					nonce: OPEN_CALENDAR_KIT_ADMIN.nonce,
					date: date
				},
				function (response) {
					if (response && response.success) {
						$feedback.text( response.data.msg ).css( 'color', '#2ecc71' ).show();
						setTimeout(
							function () {
								closeModal();
								reloadAdminCalendar( month );
							},
							300
						);
					} else {
						var message = (response && response.data && response.data.msg) || OPEN_CALENDAR_KIT_ADMIN.generic_error;
						$feedback.text( message ).css( 'color', '#e74c3c' ).show();
					}
				}
			).fail(
				function () {
					$feedback.text( OPEN_CALENDAR_KIT_ADMIN.generic_error ).css( 'color', '#e74c3c' ).show();
				}
			);
		}
	);
})( jQuery );
