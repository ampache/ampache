<ul id="rb_action">
    <li>
        {!! Ajax::button('?page=stream&action=basket', 'all', T_('Play'), 'rightbar_play') !!}
    </li>
    @if (Auth::check() && Auth::user()->isRegisteredUser())
        <li id="pl_add">
            <img src="{!! url_icon('playlist_add') !!}" alt="{{ T_('Add to Playlist') }}" />
            <ul id="pl_action_additems" class="submenu">
                <li>
                    {!! Ajax::text('?page=playlist&action=append_item', T_('Add to New Playlist'), 'rb_create_playlist') !!}
                </li>
                @foreach (Auth::user()->playlists() as $playlist)
                    <li>
                        {!! Ajax::text('?page=playlist&action=append_item&playlist_id=' . $playlist->id, $playlist->name, 'rb_append_playlist_' . $playlist->id) !!}
                    </li>
                @endforeach
            </ul>
        </li>
    @endif
    @if (Config::get('feature.allow_zip_download'))
        <li>
            <a rel="nohtml" href="{!! url('batch/playlist/' . Session::get('tmp_playlist')) !!}">
                <img src="{!! url_icon('batch_download') !!}" alt="{{ T_('Batch Download') }}" />
            </a>
        </li>
    @endif
    <li>
        {!! Ajax::button('?action=basket&type=clear_all', 'delete', T_('Clear Playlist'), 'rb_clear_playlist') !!}
    </li>
    <li id="rb_add">
        <img src="{!! url_icon('add') !!}" alt="{{ T_('Add Dynamic Items') }}" />
        <ul id="rb_action_additems" class="submenu">
            <li>
                {!! Ajax::text('?page=random&action=song', T_('Random Song'), 'rb_add_random_song') !!}
            </li>
            <li>
                {!! Ajax::text('?page=random&action=artist', T_('Random Artist'),'rb_add_random_artist') !!}
            </li>
            <li>
                {!! Ajax::text('?page=random&action=album', T_('Random Album'), 'rb_add_random_album') !!}
            </li>
            <li>
                {!! Ajax::text('?page=random&action=playlist', T_('Random Playlist'),'rb_add_random_playlist') !!}
            </li>
        </ul>
    </li>
</ul>
@if (Setting::get('play_type') === 'localplay')
    @include('show_localplay_control')
@endif

<script type="text/javascript">
    @if (Playlist::hasItems(Session::get('tmp_playlist')) || (Setting::get('play_type') === 'localplay'))
        $("#content").removeClass("content-right-wild", 500);
        $("#footer").removeClass("footer-wild", 500);
        $("#rightbar").removeClass("hidden");
        $("#rightbar").show("slow");
    @else
        $("#content").addClass("content-right-wild", 500);
        $("#footer").addClass("footer-wild", 500);
        $("#rightbar").hide("slow");
    @endif
</script>

<ul id="rb_current_playlist">
    @foreach (Playlist::getTracks(Session::get('tmp_playlist')) as $track)
    <li class="{{ UI::flip_class() }}" >
        {!! $track->getItem()->getLinkHTML() !!}
        {!! Ajax::button('?action=current_playlist&type=delete&id=' . $uid, 'delete', T_('Delete'), 'rightbar_delete_' . $track->track, '', 'delitem') !!}
    </li>
    @endforeach
</ul>

{{-- Stream::run_playlist_method() --}}
