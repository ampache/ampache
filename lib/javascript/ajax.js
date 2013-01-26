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
Ajax.Responders.register({
    onCreate: function(){
        $('ajax-loading').style.display = 'block';
    },
    onComplete: function() {
        $('ajax-loading').style.display = 'none';
    }
});

// ajaxPost
// Post the contents of a form.
function ajaxPost(url, input, source) {
    if ($(source)) {
        Event.stopObserving(source, 'click', function() { ajaxPost(url, input, source); });
    }

    new Ajax.Request(url, {
        method: 'post',
        parameters: $(input).serialize(true),
        onSuccess: processContents
    });
} // ajaxPost

// ajaxPut
// Get response from the specified URL.
function ajaxPut(url, source) {
    if ($(source)) { 
        Event.stopObserving(source, 'click', function(){ ajaxPut(url, source); });
    } 

    new Ajax.Request(url, { 
        method: 'put',
        onSuccess: processContents 
    });
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
function processContents(transport) {
    $A(transport.responseXML.getElementsByTagName('content')).each(updateElement);
} // processContents

// updateElement
// This isn't an anonymous function because I'm ornery.  Does the actual
// updates for processContents.
function updateElement(contentXML) {
    var newID = contentXML.getAttribute('div');
    if($(newID)) {
        $(newID).update(contentXML.firstChild.nodeValue);
    }
} // updateElement
