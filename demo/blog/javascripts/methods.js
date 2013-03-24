document.on('click', 'a[data-method]', function(evt, elm){
  var method = elm.readAttribute('data-method');
  if(method == 'delete'){
    evt.stop();
    var form = new Element('form', {method: 'post'});
    form.action = elm.href;
    var params = elm.href.parseQuery();
    form.insert(new Element('input', {type: 'hidden', name: 'action', value: 'destroy'}));
    form.insert(new Element('input', {type: 'hidden', name: 'id', value: params.id}));
    var confirmation_message = elm.readAttribute('data-confirm');
    if(confirm(confirmation_message)){
      form.submit();
    }
  }
});
