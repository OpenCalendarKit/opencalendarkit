(function ($) {
  function showModal() {
    var $m = $('.bkit-modal');
    $m.find('.bkit-feedback').hide().text('');
    $m.show().css('display', 'flex');
  }

  function closeModal() {
    $('.bkit-modal').hide();
  }

  function openModalForClosed(dateISO, datePretty, reason) {
    var $m = $('.bkit-modal');

    $m.find('.bkit-closed-info').show();
    $m.find('.bkit-closed-date').text(datePretty || '');
    $m.find('.bkit-closed-reason').text(reason ? (OPEN_CALENDAR_KIT.reason_label + ' ' + reason) : '');

    showModal();
  }

  // Geschlossene Tage → Info (Reason kommt aus data-reason, kein Ajax)
$(document).on('click', '.bkit-cell.day.closed.clickable', function () {
    var date = $(this).data('date') || '';
    var reason = $(this).data('reason') || '';
    var pretty;
    try {
      pretty = new Date(date + 'T00:00:00').toLocaleDateString(OPEN_CALENDAR_KIT.locale || undefined, { year: 'numeric', month: 'long', day: 'numeric' });
    } catch (e) {
      pretty = date;
    }
    openModalForClosed(date, pretty, reason);
  });

  // Schließen
  $(document).on('click', '.bkit-cancel, .bkit-modal .bkit-close', function (e) {
    e.preventDefault();
    closeModal();
  });

  // Kalender-Monat wechseln ohne Page-Reload (AJAX)
  $(document).on('click', '.bkit-calendar .bkit-nav', function (e) {
    var $a = $(this);
    if ($a.hasClass('disabled')) return;

    var href = $a.attr('href') || '';
    if (!href || href === '#') return;

    // Month aus Query ziehen
    var m = href.match(/okit_month=([0-9]{4}-[0-9]{2})/);
    var month = m ? m[1] : '';
    if (!month) return; // fallback: normaler Link

    var $cal = $a.closest('[data-okit-calendar]');
    if (!$cal.length) return;

    e.preventDefault();
    $cal.addClass('is-loading');

    var showLegend = $cal.attr('data-show-legend');
    var weekStartsOn = $cal.attr('data-week-starts-on');
    var maxWidth = $cal.attr('data-max-width');

    $.post(OPEN_CALENDAR_KIT.ajax_url, {
      action: 'okit_calendar_month',
      nonce: OPEN_CALENDAR_KIT.nonce,
      month: month,
      show_legend: showLegend !== undefined ? String(showLegend) : '',
      week_starts_on: weekStartsOn !== undefined ? String(weekStartsOn) : '',
      max_width: maxWidth !== undefined ? String(maxWidth) : ''
    }, function (resp) {
      if (resp && resp.success && resp.data && resp.data.html) {
        $cal.replaceWith(resp.data.html);
        try { window.history.pushState(null, '', href); } catch (err) {}
      }
    }).always(function () {
      $('.bkit-calendar.is-loading').removeClass('is-loading');
    });
  });

})(jQuery);
