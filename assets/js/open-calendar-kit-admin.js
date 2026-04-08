(function($){

  function openModal(dateISO, reason, closedEvent, closedRule, openOverride){
    var $m = $('#bkit-closedday-modal');
    var $openExceptionButton = $('#bkit-toggle-open-exception');

    $m.data('date', dateISO || '');
    $m.data('closedEvent', closedEvent ? 1 : 0);
    $m.data('closedRule', closedRule ? 1 : 0);
    $m.data('openOverride', openOverride ? 1 : 0);

    $m.find('input[name="date"]').val(dateISO || '');
    $m.find('input[name="reason"]').val(reason || '');
    $m.find('.bkit-feedback').hide().text('');

    // "Open day" nur anbieten, wenn der Tag nur wegen Event geschlossen ist.
    if (closedEvent && !closedRule) {
      $('#bkit-open-day').show();
    } else {
      $('#bkit-open-day').hide();
    }

    // Regel-geschlossene Tage koennen immer eine Ausnahme-Oeffnung bekommen.
    if (closedRule) {
      $openExceptionButton
        .text(openOverride ? OPEN_CALENDAR_KIT_ADMIN.remove_exceptional_opening : OPEN_CALENDAR_KIT_ADMIN.open_day_exceptionally)
        .show();
    } else {
      $openExceptionButton.hide().text('');
    }

    $m.show().css('display','flex');
  }

  function closeModal(){ $('#bkit-closedday-modal').hide(); }

  // Klick im Admin-Kalender
  $(document).on('click', '.bkit-admin-cal .bkit-cell.day', function(){
    var date       = $(this).data('date') || '';
    var reason     = $(this).data('reason') || '';
    var closedEvent= String($(this).data('closedEvent') || '0') === '1';
    var closedRule = String($(this).data('closedRule') || '0') === '1';
    var openOverride = String($(this).data('openOverride') || '0') === '1';
    if(!date) return;
    openModal(String(date), String(reason), closedEvent, closedRule, openOverride);
  });

  // Modal schließen
  $(document).on('click', '#bkit-closedday-modal .bkit-close, #bkit-cancel', function(e){
    e.preventDefault();
    closeModal();
  });

  // Speichern
  $(document).on('submit', '#bkit-closedday-form', function(e){
    e.preventDefault();

    var $m  = $('#bkit-closedday-modal');
    var $fb = $m.find('.bkit-feedback');

    var data = {
      action: 'okit_save_closed_day',
      nonce:  OPEN_CALENDAR_KIT_ADMIN.nonce,
      date:   $(this).find('input[name="date"]').val(),
      reason: $(this).find('input[name="reason"]').val()
    };

    $.post(ajaxurl, data, function(resp){
      if (resp && resp.success){
        $fb.text(resp.data.msg).css('color','#2ecc71').show();
        setTimeout(function(){ window.location.reload(); }, 600);
      } else {
        var msg = (resp && resp.data && resp.data.msg) || OPEN_CALENDAR_KIT_ADMIN.generic_error;
        $fb.text(msg).css('color','#e74c3c').show();
      }
    }).fail(function(){
      $fb.text(OPEN_CALENDAR_KIT_ADMIN.generic_error).css('color','#e74c3c').show();
    });
  });

  // Öffnen (Closed Day löschen)
  $(document).on('click', '#bkit-open-day', function(e){
    e.preventDefault();

    if(!confirm(OPEN_CALENDAR_KIT_ADMIN.confirm_reopen)) return;

    var $m  = $('#bkit-closedday-modal');
    var $fb = $m.find('.bkit-feedback');
    var date = $m.data('date') || $m.find('input[name="date"]').val();

    var data = {
      action: 'okit_delete_closed_day',
      nonce:  OPEN_CALENDAR_KIT_ADMIN.nonce,
      date:   date
    };

    $.post(ajaxurl, data, function(resp){
      if (resp && resp.success){
        $fb.text(resp.data.msg).css('color','#2ecc71').show();
        setTimeout(function(){ window.location.reload(); }, 600);
      } else {
        var msg = (resp && resp.data && resp.data.msg) || OPEN_CALENDAR_KIT_ADMIN.generic_error;
        $fb.text(msg).css('color','#e74c3c').show();
      }
    }).fail(function(){
      $fb.text(OPEN_CALENDAR_KIT_ADMIN.generic_error).css('color','#e74c3c').show();
    });
  });

  $(document).on('click', '#bkit-toggle-open-exception', function(e){
    e.preventDefault();

    var $m  = $('#bkit-closedday-modal');
    var $fb = $m.find('.bkit-feedback');
    var date = $m.data('date') || $m.find('input[name="date"]').val();
    var openOverride = String($m.data('openOverride') || '0') === '1';

    if(openOverride && !confirm(OPEN_CALENDAR_KIT_ADMIN.confirm_remove_exceptional_opening)) {
      return;
    }

    var data = {
      action: openOverride ? 'okit_delete_open_exception' : 'okit_save_open_exception',
      nonce:  OPEN_CALENDAR_KIT_ADMIN.nonce,
      date:   date
    };

    $.post(ajaxurl, data, function(resp){
      if (resp && resp.success){
        $fb.text(resp.data.msg).css('color','#2ecc71').show();
        setTimeout(function(){ window.location.reload(); }, 600);
      } else {
        var msg = (resp && resp.data && resp.data.msg) || OPEN_CALENDAR_KIT_ADMIN.generic_error;
        $fb.text(msg).css('color','#e74c3c').show();
      }
    }).fail(function(){
      $fb.text(OPEN_CALENDAR_KIT_ADMIN.generic_error).css('color','#e74c3c').show();
    });
  });

})(jQuery);
