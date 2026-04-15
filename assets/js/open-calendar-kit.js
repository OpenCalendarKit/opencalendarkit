(function ($) {
  function showModal() {
    var $modal = $('.bkit-modal');
    $modal.find('.bkit-feedback').hide().text('');
    $modal.show().css('display', 'flex');
  }

  function closeModal() {
    $('.bkit-modal').hide();
  }

  function openModalForClosed(dateISO, datePretty, reason) {
    var $modal = $('.bkit-modal');

    $modal.find('.bkit-closed-info').show();
    $modal.find('.bkit-closed-date').text(datePretty || '');
    $modal.find('.bkit-closed-reason').text(reason ? (OPEN_CALENDAR_KIT.reason_label + ' ' + reason) : '');

    showModal();
  }

  $(document).on('click', '.bkit-cell.day.closed.clickable', function () {
    var date = $(this).data('date') || '';
    var reason = $(this).data('reason') || '';
    var pretty;

    try {
      pretty = new Date(date + 'T00:00:00').toLocaleDateString(
        OPEN_CALENDAR_KIT.locale || undefined,
        { year: 'numeric', month: 'long', day: 'numeric' }
      );
    } catch (error) {
      pretty = date;
    }

    openModalForClosed(date, pretty, reason);
  });

  $(document).on('click', '.bkit-cancel, .bkit-modal .bkit-close', function (event) {
    event.preventDefault();
    closeModal();
  });

  $(document).on('click', '.bkit-calendar .bkit-nav', function (event) {
    var $link = $(this);
    var $calendar = $link.closest('[data-openkit-calendar]');
    var month = String($link.data('targetMonth') || '');

    if ($link.hasClass('disabled') || !$calendar.length || !month) {
      return;
    }

    event.preventDefault();
    $calendar.addClass('is-loading');

    $.post(OPEN_CALENDAR_KIT.ajax_url, {
      action: OPEN_CALENDAR_KIT.action,
      nonce: OPEN_CALENDAR_KIT.nonce,
      month: month,
      show_legend: String($calendar.attr('data-show-legend') || ''),
      week_starts_on: String($calendar.attr('data-week-starts-on') || ''),
      max_width: String($calendar.attr('data-max-width') || '')
    }, function (response) {
      if (response && response.success && response.data && response.data.html) {
        $calendar.replaceWith(response.data.html);
      }
    }).always(function () {
      $('.bkit-calendar.is-loading').removeClass('is-loading');
    });
  });
})(jQuery);
