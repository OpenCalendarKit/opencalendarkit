/**
 * Admin calendar interactions.
 *
 * @package OpenCalendarKit
 */

(function ($) {
	var __ = ( window.wp && window.wp.i18n && window.wp.i18n.__ )
		? window.wp.i18n.__
		: function ( text ) {
			return text;
		};

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
			.text(
				openOverride
					? __( 'Remove exceptional opening', 'open-calendar-kit' )
					: __( 'Open day exceptionally', 'open-calendar-kit' )
			)
			.show();
		} else {
			$openExceptionButton.hide().text( '' );
		}

		$modal.show().css( 'display', 'flex' );
	}

	function closeModal() {
		$( '#bkit-closedday-modal' ).hide();
	}

	function getCurrentAdminCalendarMonth() {
		return String( $( '[data-openkit-admin-calendar]' ).attr( 'data-month' ) || '' );
	}

	function syncBulkClosedDaysPanel() {
		var $toggle = $( '#openkit_bulk_closed_days_toggle' );
		var $wrapper = $( '.openkit-bulk-closed-days' );

		if ( ! $toggle.length || ! $wrapper.length ) {
			return;
		}

		$wrapper.toggleClass( 'is-open', $toggle.is( ':checked' ) );
	}

	function createTextBlockKey(nextIndex) {
		return 'text_block_' + String( Date.now() ) + '_' + String( nextIndex );
	}

	function resetCalendarEventRow($row) {
		if ( ! $row.length ) {
			return;
		}

		$row.find( 'input[type="date"], input[type="text"]' ).val( '' );
		$row.find( 'select' ).each(
			function () {
				if ( $( this ).hasClass( 'openkit-calendar-events__color' ) ) {
					$( this ).val( 'blue' );
					return;
				}

				$( this ).val( '' );
			}
		);
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
						var message = (response && response.data && response.data.msg) || __( 'Error', 'open-calendar-kit' );
						$feedback.text( message ).css( 'color', '#e74c3c' ).show();
					}
				}
			).fail(
				function () {
					$feedback.text( __( 'Error', 'open-calendar-kit' ) ).css( 'color', '#e74c3c' ).show();
				}
			);
		}
	);

	$( document ).on(
		'click',
		'#bkit-open-day',
		function (event) {
			event.preventDefault();

			if ( ! confirm( __( 'Remove this closed day and mark it open again?', 'open-calendar-kit' ) )) {
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
						var message = (response && response.data && response.data.msg) || __( 'Error', 'open-calendar-kit' );
						$feedback.text( message ).css( 'color', '#e74c3c' ).show();
					}
				}
			).fail(
				function () {
					$feedback.text( __( 'Error', 'open-calendar-kit' ) ).css( 'color', '#e74c3c' ).show();
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

			if (openOverride && ! confirm( __( 'Remove this exceptional opening and use the normal weekday rule again?', 'open-calendar-kit' ) )) {
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
						var message = (response && response.data && response.data.msg) || __( 'Error', 'open-calendar-kit' );
						$feedback.text( message ).css( 'color', '#e74c3c' ).show();
					}
				}
			).fail(
				function () {
					$feedback.text( __( 'Error', 'open-calendar-kit' ) ).css( 'color', '#e74c3c' ).show();
				}
			);
		}
	);

	$( document ).on(
		'change',
		'#openkit_bulk_closed_days_toggle',
		function () {
			syncBulkClosedDaysPanel();
		}
	);

	$( document ).on(
		'click',
		'#openkit_save_closed_day_range',
		function (event) {
			var $panel = $( '[data-openkit-bulk-closed-panel]' );
			var $feedback = $panel.find( '.openkit-bulk-closed-days__feedback' );
			var month = getCurrentAdminCalendarMonth();

			event.preventDefault();

			$.post(
				ajaxurl,
				{
					action: OPEN_CALENDAR_KIT_ADMIN.save_closed_day_range_action,
					nonce: OPEN_CALENDAR_KIT_ADMIN.nonce,
					date_from: $( '#openkit_bulk_closed_days_from' ).val(),
					date_to: $( '#openkit_bulk_closed_days_to' ).val(),
					reason: $( '#openkit_bulk_closed_days_reason' ).val()
				},
				function (response) {
					if (response && response.success) {
						$feedback.text( response.data.msg ).css( 'color', '#2ecc71' ).show();
						setTimeout(
							function () {
								reloadAdminCalendar( month || String( $( '#openkit_bulk_closed_days_from' ).val() || '' ).slice( 0, 7 ) );
							},
							300
						);
					} else {
						var message = (response && response.data && response.data.msg) || __( 'Error', 'open-calendar-kit' );
						$feedback.text( message ).css( 'color', '#e74c3c' ).show();
					}
				}
			).fail(
				function () {
					$feedback.text( __( 'Error', 'open-calendar-kit' ) ).css( 'color', '#e74c3c' ).show();
				}
			);
		}
	);

	$( document ).on(
		'click',
		'[data-openkit-add-calendar-event]',
		function (event) {
			var $rows = $( '[data-openkit-calendar-event-rows]' );
			var $template = $( '[data-openkit-calendar-event-template]' );
			var templateHtml;
			var nextIndex;

			event.preventDefault();

			if ( ! $rows.length || ! $template.length) {
				return;
			}

			nextIndex = $rows.find( '[data-openkit-calendar-event-row]' ).length;
			templateHtml = String( $template.html() || '' ).replace( /__INDEX__/g, String( nextIndex ) );
			$rows.append( templateHtml );
		}
	);

	$( document ).on(
		'click',
		'[data-openkit-remove-calendar-event]',
		function (event) {
			var $rows = $( '[data-openkit-calendar-event-rows]' );
			var $row = $( this ).closest( '[data-openkit-calendar-event-row]' );

			event.preventDefault();

			if ( ! $row.length) {
				return;
			}

			if ( $rows.find( '[data-openkit-calendar-event-row]' ).length <= 1 ) {
				resetCalendarEventRow( $row );
				return;
			}

			$row.remove();
		}
	);

	$( document ).on(
		'click',
		'[data-openkit-add-text-block]',
		function (event) {
			var $rows = $( '[data-openkit-text-block-rows]' );
			var $template = $( '[data-openkit-text-block-template]' );
			var nextIndex;
			var templateHtml;

			event.preventDefault();

			if ( ! $rows.length || ! $template.length ) {
				return;
			}

			nextIndex = $rows.find( '[data-openkit-text-block-row]' ).length;
			templateHtml = String( $template.html() || '' )
				.replace( /__INDEX__/g, String( nextIndex ) )
				.replace( /__KEY__/g, createTextBlockKey( nextIndex ) );
			$rows.append( templateHtml );
		}
	);

	$( document ).on(
		'click',
		'[data-openkit-remove-text-block]',
		function (event) {
			event.preventDefault();
			$( this ).closest( '[data-openkit-text-block-row]' ).remove();
		}
	);

	$( syncBulkClosedDaysPanel );
})( jQuery );
