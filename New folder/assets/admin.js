(function($){
  $(document).on('change', '.rshf-toggle', function(){
    var $ch = $(this);
    var id = $ch.data('id');
    $ch.prop('disabled', true);
    $.ajax({
      method: 'POST',
      url: (RSHF && RSHF.ajax) ? RSHF.ajax : ajaxurl,
      data: { action:'rshf_toggle_active', id:id, nonce:RSHF.nonce }
    }).done(function(res){
      if(!res || !res.success){
        $ch.prop('checked', !$ch.prop('checked'));
        if(res && res.data && res.data.message){ alert(res.data.message); }
      }
    }).fail(function(){
      $ch.prop('checked', !$ch.prop('checked'));
      alert('Network error');
    }).always(function(){
      $ch.prop('disabled', false);
    });
  });
})(jQuery);