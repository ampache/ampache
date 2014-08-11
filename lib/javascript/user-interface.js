// vim:set softtabstop=4 shiftwidth=4 expandtab:
//
// Copyright 2001 - 2013 Ampache.org
// All rights reserved.
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License v2
// as published by the Free Software Foundation.
//
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

/****** Handle user rating ******/
function toggleStarIcons(baseId, baseValue, index) {
    for (var i = 0; i < 5; ++i) {
        $('#' + baseId + i).toggleClass('fa-star', (i <= index)).toggleClass('fa-star-o', (i > index));      
    }
}

function saveStarIcons(baseUrl, baseId, index) {
    ajaxPut(baseUrl, baseId);
    for (var i = 0; i < 5; ++i) {
        $('#' + baseId + i).toggleClass('save-on', (i <= index));
    }
    resetStarIcons(baseId);
}

function resetStarIcons(baseId) {
    for (var i = 0; i < 5; ++i) {
        var isOn = $('#' + baseId + i).hasClass('save-on');
        $('#' + baseId + i).toggleClass('fa-star', isOn).toggleClass('fa-star-o', !isOn);
    }
}
/********************************/

/****** Handle media summary More/Less ******/
function resizeSummary() {
    var clientHeight = $('.metadata-summary').prop('clientHeight'); // max size of container
    var scrollHeight = $('.metadata-summary').prop('scrollHeight'); // current size of summary
    
    $('.summary-divider').toggleClass('hidden', (scrollHeight <= clientHeight));
}

function toogleSummary(more, less, default_size) {
    var clientHeight = $('.metadata-summary').prop('clientHeight'); // max size of container
    var scrollHeight = $('.metadata-summary').prop('scrollHeight'); // current size of summary
    
    $('.summary-divider-btn').html((scrollHeight > clientHeight) ? less : more);
    $('.metadata-summary').css("max-height", ((scrollHeight > clientHeight) ? scrollHeight : default_size) + "px");
}
/*******************************************/