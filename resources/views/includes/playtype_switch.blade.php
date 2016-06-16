<div id="play_type_switch">
    <form method="post" id="play_type_form" action="javascript.void(0);">
        <select id="play_type_select" name="type">
            @if (Config::get('allow_stream_playback'))
                <option value="stream" {{ (Setting::get('play_type') === 'stream') ? 'selected' : '' }}>{{ T_('Stream') }}</option>
            @endif
            @if (Config::get('allow_localplay_playback'))
                <option value="localplay" {{ (Setting::get('play_type') === 'localplay') ? 'selected' : '' }}>{{ T_('Localplay') }}</option>
            @endif
            @if (Config::get('allow_democratic_playback'))
                <option value="democratic" {{ (Setting::get('play_type') === 'democratic') ? 'selected' : '' }}>{{ T_('Democratic') }}</option>
            @endif
            <option value="web_player" {{ (Setting::get('play_type', 'web_player') === 'web_player') ? 'selected' : '' }}>{{ T_('Web Player') }}</option>
        </select>
        {!! Ajax::observe('play_type_select','change',Ajax::action('?page=stream&action=set_play_type','play_type_select','play_type_form')) !!}
    </form>
</div>
