
function getFormData($form){
  var unindexed_array = $form.serializeArray();
  var indexed_array = {};

  $.map(unindexed_array, function(n, i){
    indexed_array[n['name']] = n['value'];
  });

  return indexed_array;
}

// Cookies
function createCookie(name, value, days) {
  if (days) {
    var date = new Date();
    if (days) {date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
    var expires = "; expires=" + date.toGMTString();
  }
  else var expires = "";

  document.cookie = name + "=" + value + expires + "; path=/";
}

function readCookie(name) {
  var nameEQ = name + "=";
  var ca = document.cookie.split(';');
  for (var i = 0; i < ca.length; i++) {
    var c = ca[i];
    while (c.charAt(0) == ' ') c = c.substring(1, c.length);
    if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
  }
  return null;
}

function eraseCookie(name) {
  createCookie(name, "", -1);
}

$(document).on('click', '.tagit-new', function(){
  var utilsApiEnpoint = '//utils.dev/api/genre/add';
  var currentForm = $(this).closest('form.edit_dialog_content');
  var formId = currentForm.attr('id');

  console.debug(formId);
  //var tuneid = currentForm.find("input[name='id']");

  //console.debug(getFormData(currentForm));

  $( ".ui-dialog-buttonset span:contains('Save')" ).first().on('click', function(){
    var currentData = getFormData(currentForm);

    console.debug(getFormData(currentForm));

    $.getJSON(utilsApiEnpoint, {
      edit_tags: currentData.edit_tags,
      object_id: currentData.id,
      object_type: currentData.type.replace('_row', ''),
      user: readCookie('ampache_user')
    },
    function (response){
      console.debug(response)
    });

  });

  //console.debug(currentForm.);
})

