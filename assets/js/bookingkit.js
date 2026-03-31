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
    $m.find('.bkit-closed-reason').text(reason ? ('Grund: ' + reason) : '');

    showModal();
  }

  // Geschlossene Tage → Info (Reason kommt aus data-reason, kein Ajax)
$(document).on('click', '.bkit-cell.day.closed.clickable', function () {
    var date = $(this).data('date') || '';
    var reason = $(this).data('reason') || '';
    var pretty;
    try {
      pretty = new Date(date + 'T00:00:00').toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });
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
    var m = href.match(/bk_month=([0-9]{4}-[0-9]{2})/);
    var month = m ? m[1] : '';
    if (!month) return; // fallback: normaler Link

    var $cal = $a.closest('[data-bkit-calendar]');
    if (!$cal.length) return;

    e.preventDefault();
    $cal.addClass('is-loading');

    $.post(BKIT_MVP.ajax_url, {
      action: 'bkit_mvp_calendar_month',
      nonce: BKIT_MVP.nonce,
      month: month
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
