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
    stop();
    current_playlist_item = event.findElement().getStorage().get('playlist_item');
    play();
}
function adjust_buttons()
{
    if(!current_playlist_item.player.paused)
    {
        $('play').addClassName('inactive');
        $('pause').removeClassName('inactive');
        $('stop').removeClassName('inactive');
    }
    else
    {
        $('play').removeClassName('inactive');
        $('pause').addClassName('inactive');
        $('stop').addClassName('inactive');
    }
}
function stop(event)
{
    if(current_playlist_item)
    {
        current_playlist_item.player.pause();
        current_playlist_item.player.currentTime = 0;
        if(current_playlist_item.player.currentTime)
        {
            var src=current_playlist_item.player.src;
            current_playlist_item.player.src=null;
            current_playlist_item.player.src=src;
        }
        current_playlist_item.element.removeClassName('playing');
        adjust_buttons();
    }
}
function pause(event)
{
    if(current_playlist_item)
    {
        current_playlist_item.player.pause();
        adjust_buttons();
    }
}
function play(event)
{
    if(current_playlist_item)
    {
        $('title').update(current_playlist_item.info_url);
        $('title').select('a')[0].writeAttribute('target', '_new');
        $('album').update(current_playlist_item.f_album_link);
        //$('album').select('a')[0].writeAttribute('target', '_new');
        $('artist').update(current_playlist_item.author);
        //$('artist').select('a')[0].writeAttribute('target', '_new');
        $('albumart').update(new Element('img', {src: current_playlist_item.albumart_url + '&thumb=4'}));
        dequeue(current_playlist_item.element);
        current_playlist_item.player.writeAttribute('preload', 'auto');
        current_playlist_item.player.play();
        if(current_playlist_item.element.offsetTop - $('playlist').offsetTop - $('playlist').scrollTop > $('playlist').measure('height') || current_playlist_item.element.offsetTop - $('playlist').offsetTop - $('playlist').scrollTop < 0)
        {
            $('playlist').scrollTop = current_playlist_item.element.offsetTop - $('playlist').offsetTop;
        }
        current_playlist_item.element.addClassName('playing');
        adjust_buttons();
    }
}
function next(event)
{
    if(current_playlist_item && current_playlist_item.next)
    {
        stop();
        var next = current_playlist_item.next;
        $$('[data-queue-id=1]').each(function(e) { next=e.getStorage().get('playlist_item') });
        current_playlist_item = next;
        play();
    }
}
function previous(event)
{
    if(current_playlist_item && current_playlist_item.previous)
    {
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
        $('progress_text').update(seconds_to_string(current_playlist_item.player.currentTime) + "/" + seconds_to_string(current_playlist_item.time));
        if(current_playlist_item.player.currentTime > current_playlist_item.time / 2)
        {
            if(current_playlist_item.next)
            {
                current_playlist_item.next.player.writeAttribute('preload', 'auto');
            }
        }
        //fix for chrome where ended is not thrown properly
        if(current_playlist_item.player.currentTime >= current_playlist_item.time)
        {
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
    var search = new RegExp(".*" + event.findElement().value + ".*", "i");
    for(var item = $('playlist').firstDescendant(); item; item = item.next())
    {
        if(!search.test(item.textContent != undefined ? item.textContent : item.innerText))
        {
            item.hide();
        }
        else
        {
            item.show();
        }
    }
}
function clear_search(event)
{
    event.findElement().value = "";
    search(event);
}
function queue(event)
{
    var queue_id=0;
    
    if(event.findElement().getAttribute('data-queue-id'))
    {
        return dequeue(event.findElement());
    }

    $$('[data-queue-id]').each(
        function(e)
        {
            if(parseInt(e.getAttribute('data-queue-id')) > queue_id)
            {
                queue_id = parseInt(e.getAttribute('data-queue-id'));
            }
        });
    event.findElement().setAttribute('data-queue-id', queue_id+1);
}
function dequeue(element)
{
    var queue_id=0;

    $$('[data-queue-id]').sort(
        function(x, y)
        {
            return parseInt(x.getAttribute('data-queue-id'))-parseInt(y.getAttribute('data-queue-id'));
        })
        .each(
            function(e)
            {
                if(queue_id)
                {
                    e.setAttribute('data-queue-id', queue_id++);
                }
                if(e==element)
                {
                    queue_id=parseInt(e.getAttribute('data-queue-id'));
                    e.removeAttribute('data-queue-id');
                }
            });
}
document.observe("dom:loaded", function()
{
    var last_item = null, first_item = null;
    for(id in playlist_items)
    {
        var li = new Element('li');
        $('playlist').insert(li);
        playlist_items[id].play_url += '&transcode_to=' + (Prototype.Browser.IE || Prototype.Browser.WebKit || Prototype.Browser.MobileSafari ? 'mp3' : 'ogg');
        li.update(playlist_items[id].title);
playlist_items[id].player = new Element("audio", {preload: Prototype.Browser.IE ? 'auto' : 'none', src : playlist_items[id].play_url});
        li.insert(playlist_items[id].player);
        li.getStorage().set('playlist_item', playlist_items[id]);
        li.observe('click', play_item);
        li.observe('mousedown', function(event) {if(event.which==2 || event.which==3) queue(event)});
        li.setAttribute('data-tooltip', playlist_items[id].album + ' - ' + playlist_items[id].author);
        playlist_items[id].player.observe('ended', ended);
        playlist_items[id].player.observe('timeupdate', timeupdate);
        playlist_items[id].element = li;
        if(last_item)
        {
            last_item.next = playlist_items[id];
        }
        if(first_item == null)
        {
            first_item = playlist_items[id];
        }
        playlist_items[id].previous = last_item;
        last_item = playlist_items[id];
    }
    if(first_item)
    {
        // first_item.previous = last_item;
        // last_item.next = first_item;
        current_playlist_item = first_item;
        play();
    }
    $('stop').observe('click', stop);
    $('play').observe('click', play);
    $('pause').observe('click', pause);
    $('next').observe('click', next);
    $('previous').observe('click', previous);
    $('input_search').observe('keyup', search);
    $('input_search').observe('html5_player:clear_search', clear_search);
    $('input_search').observe('focus', clear_search);
    $('clear_search').observe('click', function() {
        $('input_search').fire('html5_player:clear_search')
    });
});
