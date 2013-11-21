// vim:set softtabstop=4 shiftwidth=4 expandtab:
//
// Copyright 2001 - 2013 Ampache.org
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License v2
// as published by the Free Software Foundation.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

// Some cutesy flashing thing while we run
$(document).ajaxSend(function () {
    $('#ajax-loading').show();
});
$(document).ajaxComplete(function () {
    $('#ajax-loading').hide();
});

// ajaxPost
// Post the contents of a form.
function ajaxPost(url, input, source) {
    //$('#' + input)
    if ($(source)) {
        $(source).off('click', function(){ ajaxPost(url, input, source); });
    } 
    $.ajax(url, { success: processContents, type: 'post', data: $('#'+input).serialize() });
} // ajaxPost

// ajaxPut
// Get response from the specified URL.
function ajaxPut(url, source) {
    if ($(source)) {
        $(source).off('click', function(){ ajaxPut(url, source); });
    }
    $.ajax(url, { success: processContents, type: 'post', dataType: 'xml' });
} // ajaxPut

// ajaxState
// Post the contents of a form without doing any observe() things.
function ajaxState(url, input) {
    new Ajax.Request(url, {
        method: 'post',
        parameters: $(input).serialize(true),
        onSuccess: processContents
    });
} // ajaxState


// processContents
// Iterate over a response and do any updates we received.
function processContents(data, status) {
    $(data).find('content').each(function () {
        $('#' + $(this).attr('div')).html($(this).text())
        /*var text = $.parseHTML($(this).text(), document, true);
        var dom = $(text);
        dom.filter('script').each(function(){
            $.globalEval(this.text || this.textContent || this.innerHtml || '');
        });*/
    })
} // processContents
