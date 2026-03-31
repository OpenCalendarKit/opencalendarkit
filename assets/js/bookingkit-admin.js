(function($){

  function openModal(dateISO, reason, closedEvent, closedRule){
    var $m = $('#bkit-closedday-modal');

    $m.data('date', dateISO || '');
    $m.data('closedEvent', closedEvent ? 1 : 0);
    $m.data('closedRule', closedRule ? 1 : 0);

    $m.find('input[name="date"]').val(dateISO || '');
    $m.find('input[name="reason"]').val(reason || '');
    $m.find('.bkit-feedback').hide().text('');

    // "Open day" nur anbieten, wenn der Tag wegen Event geschlossen ist (nicht wegen Regel)
    if (closedEvent && !closedRule) {
      $('#bkit-open-day').show();
    } else {
      $('#bkit-open-day').hide();
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
    if(!date) return;
    openModal(String(date), String(reason), closedEvent, closedRule);
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
      action: 'bkit_mvp_save_closed_day',
      nonce:  BKIT_MVP_ADMIN.nonce,
      date:   $(this).find('input[name="date"]').val(),
      reason: $(this).find('input[name="reason"]').val()
    };

    $.post(ajaxurl, data, function(resp){
      if (resp && resp.success){
        $fb.text(resp.data.msg).css('color','#2ecc71').show();
        setTimeout(function(){ window.location.reload(); }, 600);
      } else {
        var msg = (resp && resp.data && resp.data.msg) || 'Error';
        $fb.text(msg).css('color','#e74c3c').show();
      }
    }).fail(function(){
      $fb.text('Error').css('color','#e74c3c').show();
    });
  });

  // Öffnen (Closed Day löschen)
  $(document).on('click', '#bkit-open-day', function(e){
    e.preventDefault();

    if(!confirm('Diesen geschlossenen Tag entfernen (wieder öffnen)?')) return;

    var $m  = $('#bkit-closedday-modal');
    var $fb = $m.find('.bkit-feedback');
    var date = $m.data('date') || $m.find('input[name="date"]').val();

    var data = {
      action: 'bkit_mvp_delete_closed_day',
      nonce:  BKIT_MVP_ADMIN.nonce,
      date:   date
    };

    $.post(ajaxurl, data, function(resp){
      if (resp && resp.success){
        $fb.text(resp.data.msg).css('color','#2ecc71').show();
        setTimeout(function(){ window.location.reload(); }, 600);
      } else {
        var msg = (resp && resp.data && resp.data.msg) || 'Error';
        $fb.text(msg).css('color','#e74c3c').show();
      }
    }).fail(function(){
      $fb.text('Error').css('color','#e74c3c').show();
    });
  });

})(jQuery);
