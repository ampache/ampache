/* vim:set tabstop=4 softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2013 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */
var current_playlist_item = null;

function play_item(event)
{
    op();
    current_playlist_item = event.findElement().getStorage().get('playlist_item');
    play();
}
function adjust_buttons()
{
    if(!current_playlist_item.player.paused) {
        $('#play').addClass('inactive');
        $('#pause').removeClass('inactive');
        $('#stop').removeClass('inactive');
    }
    else {
        $('#play').removeClass('inactive');
        $('#pause').addClass('inactive');
        $('#stop').addClass('inactive');
    }
}
function stop(event)
{
    if(current_playlist_item) {
        current_playlist_item.player.pause();
        current_playlist_item.player.currentTime = 0;
        current_playlist_item.element.removeClass('playing');
        adjust_buttons();
    }
}
function pause(event)
{
    if(current_playlist_item) {
        current_playlist_item.player.pause();
        adjust_buttons();
    }
}
function play(event)
{
    if(current_playlist_item)
    {
        var info = $(current_playlist_item.info_url).attr('target', '_new')
        $('#title').html(info);
        $('#album').text(current_playlist_item.album);
        $('#artist').text(current_playlist_item.author);
        $('#albumart').html($('<img />').attr('src', current_playlist_item.albumart_url));
        $(current_playlist_item.player).attr('preload', 'auto');
        current_playlist_item.player.play();
        current_playlist_item.element.addClass('playing');
        adjust_buttons();
    }
}
function next(event)
{
    if(current_playlist_item && current_playlist_item.next) {
        stop();
        var next = current_playlist_item.next;
        current_playlist_item = next;
        play();
    }
}
function previous(event)
{
    if(current_playlist_item && current_playlist_item.previous) {
        stop();
        current_playlist_item = current_playlist_item.previous;
        play();
    }
}
function seconds_to_string(seconds)
{
    return Math.floor(seconds / 60) + ":" + (Math.floor(seconds % 60) < 10 ? '0' : '') + Math.floor(seconds % 60);
}
function timeupdate(event)
{
    if(current_playlist_item)
    {
        $('#progress_text').text(seconds_to_string(current_playlist_item.player.currentTime) + "/" + seconds_to_string(current_playlist_item.time));
        if(current_playlist_item.player.currentTime > current_playlist_item.time / 2) {
            if(current_playlist_item.next) {
                current_playlist_item.next.player.writeAttribute('preload', 'auto');
            }
        }
        //fix for chrome where ended is not thrown properly
        if(current_playlist_item.player.currentTime >= current_playlist_item.time) {
            ended(event);
        }
    }
}
function ended(event)
{
    next(event);
}
function search(event)
{
    var search = new RegExp(".*" + $('#input_search').val() + ".*", "i");
    $.each(playlist_items, function (index, item) {
        if (!search.test(item.title)) {
            item.element.hide();
        }
        else {
            item.element.show();
        }
    });
}
function clear_search(event)
{
    $('#input_search').val('');
    search(event);
}

$(document).ready(function() {
    var last_item = null, first_item = null;
    for(id in playlist_items)
    {
        var li = $('<li>');
        $('#playlist').append(li);
        li.html($('<span>').append(playlist_items[id].title));
        playlist_items[id].player = $('<audio>').attr('preload', 'none').attr('src', playlist_items[id].play_url)[0];
        var player = $(playlist_items[id].player);
        li.append(playlist_items[id].player);
        li.data('playlist_item', playlist_items[id]);
        li.click(play_item);
        li.attr('data-tooltip', playlist_items[id].album + ' - ' + playlist_items[id].author);
        player.on('ended', ended);
        player.on('timeupdate', timeupdate);
        playlist_items[id].element = li;
        if(last_item) {
            last_item.next = playlist_items[id];
        }
        playlist_items[id].previous = last_item;
        last_item = playlist_items[id];
        if(first_item == null) {
            first_item = playlist_items[id];
        }
    }
    if(first_item) {
        first_item.previous = last_item;
        last_item.next = first_item;
        current_playlist_item = first_item;
        play();
    }
    $('#stop').click(stop);
    $('#play').click(play);
    $('#pause').click(pause);
    $('#next').click(next);
    $('#previous').click(previous);
    $('#input_search').keyup(search);
    $('#input_search').focus(clear_search);
});
